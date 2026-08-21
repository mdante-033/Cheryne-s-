<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\MenuController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Controllers\ReservationController;

return [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/contact' => [HomeController::class, 'contact'],
        '/sitemap.xml' => [HomeController::class, 'sitemap'],
        '/robots.txt' => [HomeController::class, 'robots'],
        '/menu' => [MenuController::class, 'index'],
        '/api/menu' => [MenuController::class, 'apiIndex'],
        '/menu/{id}' => [MenuController::class, 'show'],
        '/cart' => [OrderController::class, 'cart'],
        '/checkout' => [OrderController::class, 'checkoutForm'],
        '/payment/success' => [PaymentController::class, 'success'],
        '/payment/cancel' => [PaymentController::class, 'cancel'],
        '/reservations' => [ReservationController::class, 'form'],
        '/auth/login' => [AuthController::class, 'loginForm'],
        '/auth/register' => [AuthController::class, 'registerForm'],
        '/auth/forgot-password' => [AuthController::class, 'forgotPasswordForm'],
        '/auth/reset-password' => [AuthController::class, 'resetPasswordForm'],
        '/auth/logout' => [AuthController::class, 'logout'],
        '/admin' => [AdminController::class, 'dashboard'],
        '/admin/menu' => [AdminController::class, 'menuManage'],
        '/admin/orders' => [AdminController::class, 'orders'],
        '/admin/users' => [AdminController::class, 'users'],
        '/admin/reservations' => [AdminController::class, 'reservations'],
        '/admin/inventory' => [AdminController::class, 'inventory'],
        '/admin/suppliers' => [AdminController::class, 'suppliers'],
        '/admin/reports' => [AdminController::class, 'reports'],
        '/admin/settings' => [AdminController::class, 'settings'],
    ],
    'POST' => [
        '/cart/add' => [OrderController::class, 'addToCart'],
        '/cart/update' => [OrderController::class, 'updateCart'],
        '/cart/remove' => [OrderController::class, 'removeFromCart'],
        '/checkout' => [OrderController::class, 'checkout'],
        '/reservation' => [ReservationController::class, 'store'],
        '/webhooks/mpesa' => [PaymentController::class, 'webhookMpesa'],
        '/auth/login' => [AuthController::class, 'login'],
        '/auth/register' => [AuthController::class, 'register'],
        '/auth/forgot-password' => [AuthController::class, 'forgotPassword'],
        '/auth/reset-password' => [AuthController::class, 'resetPassword'],
        '/auth/logout' => [AuthController::class, 'logout'],
        '/admin/categories' => [AdminController::class, 'storeCategory'],
        '/admin/categories/{id}/update' => [AdminController::class, 'updateCategory'],
        '/admin/categories/{id}/delete' => [AdminController::class, 'deleteCategory'],
        '/admin/menu' => [AdminController::class, 'storeMenuItem'],
        '/admin/menu/{id}/update' => [AdminController::class, 'updateMenuItem'],
        '/admin/menu/{id}/delete' => [AdminController::class, 'deleteMenuItem'],
        '/admin/orders/{id}/status' => [AdminController::class, 'updateOrderStatus'],
        '/admin/reservations/{id}/status' => [AdminController::class, 'updateReservationStatus'],
        '/admin/inventory/{id}/update' => [AdminController::class, 'updateInventory'],
        '/admin/suppliers' => [AdminController::class, 'storeSupplier'],
        '/admin/settings' => [AdminController::class, 'updateSettings'],
    ],
];
