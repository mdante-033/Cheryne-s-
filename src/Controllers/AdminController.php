<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Reservation;
use function App\Helpers\clean_string;
use function App\Helpers\flash;
use function App\Helpers\is_admin;
use function App\Helpers\redirect;
use function App\Helpers\verify_csrf_or_fail;
use function App\Helpers\view;

class AdminController
{
    public function dashboard(): void
    {
        if (!is_admin()) {
            redirect('/auth/login');
        }

        $stats = [
            'orders_30_days' => 0,
            'revenue_30_days' => 0.0,
            'upcoming_reservations' => 0,
        ];
        $orders = [];
        $reservations = [];

        try {
            $pdo = Database::connection();

            $orders30 = $pdo->query(
                "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS revenue
                 FROM orders
                 WHERE created_at >= NOW() - INTERVAL '30 days'"
            )->fetch();

            $upcomingReservations = $pdo->query(
                "SELECT COUNT(*) AS cnt
                 FROM reservations
                 WHERE reservation_date >= CURRENT_DATE"
            )->fetch();

            $stats = [
                'orders_30_days' => (int) ($orders30['cnt'] ?? 0),
                'revenue_30_days' => (float) ($orders30['revenue'] ?? 0),
                'upcoming_reservations' => (int) ($upcomingReservations['cnt'] ?? 0),
            ];

            $orders = $pdo->query(
                "SELECT id, customer_name, status, total_amount
                 FROM orders
                 ORDER BY created_at DESC
                 LIMIT 10"
            )->fetchAll();

            $reservations = $pdo->query(
                "SELECT name, reservation_date, reservation_time, guests, status
                 FROM reservations
                 WHERE reservation_date >= CURRENT_DATE
                 ORDER BY reservation_date ASC, reservation_time ASC
                 LIMIT 10"
            )->fetchAll();
        } catch (\Throwable $exception) {
            flash('danger', 'Unable to load admin dashboard data. Please verify your database connection.');
        }

        view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $stats,
            'orders' => $orders,
            'reservations' => $reservations,
        ]);
    }

    public function menuManage(): void
    {
        if (!is_admin()) {
            redirect('/auth/login');
        }

        $pdo = Database::connection();

        $categories = $pdo->query(
            "SELECT id, name, slug FROM categories ORDER BY name ASC"
        )->fetchAll();

        $items = $pdo->query(
            "SELECT id, name, description, price, image_url, category_id, is_available
             FROM menu_items
             ORDER BY name ASC"
        )->fetchAll();

        view('admin/menu', [
            'title' => 'Menu Management',
            'categories' => $categories,
            'items' => $items,
        ]);
    }

    public function orders(): void
    {
        if (!is_admin()) {
            redirect('/auth/login');
        }

        $pdo = Database::connection();

        $orders = $pdo->query(
            "SELECT id, customer_name, phone, payment_method, total_amount, status
             FROM orders
             ORDER BY created_at DESC"
        )->fetchAll();

        $reservations = $pdo->query(
            "SELECT id, name, phone, reservation_date, reservation_time, guests, status
             FROM reservations
             ORDER BY reservation_date DESC, reservation_time DESC"
        )->fetchAll();

        view('admin/orders', [
            'title' => 'Orders & Reservations',
            'orders' => $orders,
            'reservations' => $reservations,
        ]);
    }

    public function reservations(): void
    {
        if (!is_admin()) {
            redirect('/auth/login');
        }

        // Currently reusing the same view as orders(); split out if you want
        // a dedicated reservations-only admin screen later.
        $this->orders();
    }

    public function users(): void
    {
        if (!is_admin()) {
            redirect('/auth/login');
        }

        $pdo = Database::connection();

        $users = $pdo->query(
            "SELECT name, email, phone, role, created_at
             FROM users
             ORDER BY created_at DESC"
        )->fetchAll();

        view('admin/users', [
            'title' => 'Users',
            'users' => $users,
        ]);
    }

    public function storeCategory(): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();

        $name = clean_string(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW), 80);
        if ($name === '') {
            flash('danger', 'Category name is required.');
            redirect('/admin/menu');
        }

        Category::create($name, $this->slugify($name));
        flash('success', 'Category added.');
        redirect('/admin/menu');
    }

    public function updateCategory(string $id): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();

        $name = clean_string(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW), 80);
        if ($name === '') {
            flash('danger', 'Category name is required.');
            redirect('/admin/menu');
        }

        Category::update((int) $id, $name, $this->slugify($name));
        flash('success', 'Category updated.');
        redirect('/admin/menu');
    }

    public function deleteCategory(string $id): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();

        Category::delete((int) $id);
        flash('success', 'Category deleted.');
        redirect('/admin/menu');
    }

    public function storeMenuItem(): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();

        $data = $this->menuItemPayload();
        if ($data === null) {
            redirect('/admin/menu');
        }

        MenuItem::create($data);
        flash('success', 'Menu item added.');
        redirect('/admin/menu');
    }

    public function updateMenuItem(string $id): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();

        $data = $this->menuItemPayload();
        if ($data === null) {
            redirect('/admin/menu');
        }

        MenuItem::update((int) $id, $data);
        flash('success', 'Menu item updated.');
        redirect('/admin/menu');
    }

    public function deleteMenuItem(string $id): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();

        MenuItem::delete((int) $id);
        flash('success', 'Menu item deleted.');
        redirect('/admin/menu');
    }

    public function updateOrderStatus(string $id): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();

        $status = trim((string) ($_POST['status'] ?? ''));
        $allowedStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        if (Order::updateStatus((int) $id, $status)) {
            flash('success', 'Order status updated successfully.');
        } else {
            flash('danger', 'Unable to update the order status.');
        }

        redirect('/admin/orders');
    }

    public function updateReservationStatus(string $id): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();

        $status = trim((string) ($_POST['status'] ?? ''));
        $allowedStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        if (Reservation::updateStatus((int) $id, $status)) {
            flash('success', 'Reservation status updated successfully.');
        } else {
            flash('danger', 'Unable to update the reservation status.');
        }

        redirect('/admin/orders');
    }

    private function guardAdmin(): void
    {
        if (!is_admin()) {
            redirect('/auth/login');
        }
    }

    private function slugify(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '', '-'));
        return $slug !== '' ? $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6) : bin2hex(random_bytes(4));
    }

    private function menuItemPayload(): ?array
    {
        $name = clean_string(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW), 120);
        $description = clean_string(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW), 500);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
        $imageUrl = filter_input(INPUT_POST, 'image_url', FILTER_VALIDATE_URL);
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $isAvailable = filter_input(INPUT_POST, 'is_available', FILTER_VALIDATE_BOOLEAN);

        if ($name === '' || $price === false || $price === null || !$imageUrl) {
            flash('danger', 'Please provide a valid name, price, and image URL.');
            return null;
        }

        return [
            'category_id' => $categoryId ?: null,
            'name' => $name,
            'slug' => $this->slugify($name),
            'description' => $description,
            'price' => $price,
            'image_url' => $imageUrl,
            'is_available' => (bool) ($isAvailable ?? false),
        ];
    }
}