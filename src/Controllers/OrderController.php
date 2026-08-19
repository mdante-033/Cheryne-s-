<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MpesaService;
use App\Services\PaymentService;
use App\Services\WhatsAppService;

use function App\Helpers\app_url;
use function App\Helpers\cart_items;
use function App\Helpers\cart_total;
use function App\Helpers\clean_string;
use function App\Helpers\current_user;
use function App\Helpers\flash;
use function App\Helpers\json_response;
use function App\Helpers\rate_limit;
use function App\Helpers\redirect;
use function App\Helpers\valid_phone;
use function App\Helpers\verify_csrf_or_fail;
use function App\Helpers\encrypt_data;
use function App\Helpers\get_encryption_key;
use function App\Helpers\view;

final class OrderController
{
    public function addToCart(): void
    {
        verify_csrf_or_fail();
        if (!rate_limit('cart_add', 30, 60)) {
            if ($this->isAjax()) {
                json_response(['ok' => false, 'error' => 'Too many requests.'], 429);
            }
            flash('danger', 'Too many cart updates. Please try again shortly.');
            redirect('/menu');
        }

        $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 20],
        ]) ?: 1;

        if (!$itemId) {
            flash('danger', 'Please choose a valid menu item.');
            redirect('/menu');
        }

        $item = MenuItem::find((int) $itemId);
        if ($item === null || !filter_var($item['is_available'], FILTER_VALIDATE_BOOLEAN)) {
            flash('danger', 'That item is currently unavailable.');
            redirect('/menu');
        }

        $_SESSION['cart'] ??= [];
        $existingQuantity = (int) ($_SESSION['cart'][$itemId]['quantity'] ?? 0);
        $_SESSION['cart'][$itemId] = [
            'id' => (int) $item['id'],
            'name' => $item['name'],
            'price' => (float) $item['price'],
            'image_url' => $item['image_url'],
            'quantity' => min(20, $existingQuantity + (int) $quantity),
        ];

        if (($this->isAjax())) {
            json_response(['ok' => true, 'count' => array_sum(array_column($_SESSION['cart'], 'quantity'))]);
        }

        flash('success', $item['name'] . ' was added to your cart.');
        redirect('/cart');
    }

    public function cart(): void
    {
        view('cart', [
            'title' => "Your Cart - Cheryne's",
            'description' => "Review your Cheryne's order.",
            'items' => cart_items(),
            'total' => cart_total(),
            'whatsappLink' => WhatsAppService::orderLink(cart_items(), cart_total()),
        ]);
    }

    public function updateCart(): void
    {
        verify_csrf_or_fail();
        $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 20],
        ]);

        if ($itemId && $quantity && isset($_SESSION['cart'][$itemId])) {
            $_SESSION['cart'][$itemId]['quantity'] = (int) $quantity;
            flash('success', 'Cart updated.');
        }

        redirect('/cart');
    }

    public function removeFromCart(): void
    {
        verify_csrf_or_fail();
        $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        if ($itemId) {
            unset($_SESSION['cart'][$itemId]);
            flash('success', 'Item removed from cart.');
        }

        redirect('/cart');
    }

    public function checkoutForm(): void
    {
        if (cart_items() === []) {
            flash('warning', 'Your cart is empty.');
            redirect('/menu');
        }

        view('checkout', [
            'title' => "Checkout - Cheryne's",
            'description' => "Complete your Cheryne's order.",
            'items' => cart_items(),
            'total' => cart_total(),
        ]);
    }

    public function checkout(): void
    {
        verify_csrf_or_fail();
        if (!rate_limit('checkout', 10, 60)) {
            flash('danger', 'Too many checkout attempts. Please try again shortly.');
            redirect('/checkout');
        }

        $items = cart_items();
        if ($items === []) {
            flash('warning', 'Your cart is empty.');
            redirect('/menu');
        }

        $name = clean_string(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW), 120);
        $phone = clean_string(filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW), 30);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: null;
        $notes = clean_string(filter_input(INPUT_POST, 'notes', FILTER_UNSAFE_RAW), 500);
        $paymentMethod = clean_string(filter_input(INPUT_POST, 'payment_method', FILTER_UNSAFE_RAW), 20);
        $encryptionKey = get_encryption_key();

        if ($name === '' || !valid_phone($phone) || !in_array($paymentMethod, ['cash', 'stripe', 'mpesa'], true)) {
            flash('danger', 'Please provide valid checkout details.');
            redirect('/checkout');
        }

        // Encrypt sensitive notes
        $encryptedNotes = null;
        if (!empty($notes)) {
            $encryptedNotes = encrypt_data($notes, $encryptionKey);
        }

        $user = current_user();
        $order = Order::createFromCart($user ? (int) $user['id'] : null, [
            'name' => $name,
            'phone' => $phone,
            'email' => $email, // Email is not encrypted here, but could be if deemed sensitive enough.
            'notes' => $encryptedNotes,
        ], $items, $paymentMethod);

        Payment::create([
            'order_id' => $order['id'],
            'provider' => $paymentMethod,
            'amount' => $order['total_amount'],
            'status' => $paymentMethod === 'cash' ? 'pending' : 'initiated',
        ]);

        $_SESSION['cart'] = [];

        if ($paymentMethod === 'stripe') {
            $checkoutUrl = (new PaymentService())->createStripeCheckoutSession(
                $order,
                $items,
                app_url('/payment/success?order_id=' . $order['id']),
                app_url('/payment/cancel?order_id=' . $order['id'])
            );
            header('Location: ' . $checkoutUrl);
            exit;
        }

        if ($paymentMethod === 'mpesa') {
            $result = (new MpesaService())->initiateStkPush($phone, (float) $order['total_amount'], 'ORDER-' . $order['id'], "Cheryne's order");
            if ($result['ok'] && !empty($result['reference'])) {
                Payment::setProviderReference((int) $order['id'], (string) $result['reference']);
            }
            flash($result['ok'] ? 'success' : 'warning', $result['message']);
            redirect('/payment/success?order_id=' . $order['id'] . '&mpesa=1');
        }

        flash('success', 'Your order was received. We will confirm it shortly.');
        redirect('/payment/success?order_id=' . $order['id'] . '&cash=1');
    }

    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}
