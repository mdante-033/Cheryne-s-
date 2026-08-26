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

    public function inventory(): void
    {
        $this->guardAdmin();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $items = $pdo->query(
            "SELECT mi.id, mi.name, mi.price, mi.is_available,
                    COALESCE(i.stock_quantity, 0) AS stock_quantity,
                    COALESCE(i.reorder_level, 5) AS reorder_level,
                    COALESCE(s.name, 'No supplier') AS supplier_name
             FROM menu_items mi
             LEFT JOIN inventory i ON i.menu_item_id = mi.id
             LEFT JOIN suppliers s ON s.id = i.supplier_id
             ORDER BY mi.name ASC"
        )->fetchAll();
        view('admin/inventory', ['title' => 'Inventory', 'items' => $items]);
    }

    public function suppliers(): void
    {
        $this->guardAdmin();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $suppliers = $pdo->query("SELECT id, name, phone, email, notes FROM suppliers ORDER BY name ASC")->fetchAll();
        view('admin/suppliers', ['title' => 'Suppliers', 'suppliers' => $suppliers]);
    }

    public function reports(): void
    {
        $this->guardAdmin();
        $pdo = Database::connection();
        $reports = [
            'orders' => $pdo->query("SELECT COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS revenue FROM orders WHERE created_at >= NOW() - INTERVAL '30 days'")->fetch(),
            'statuses' => $pdo->query("SELECT status, COUNT(*) AS count FROM orders GROUP BY status ORDER BY count DESC")->fetchAll(),
            'top_items' => $pdo->query("SELECT item_name, SUM(quantity) AS quantity, COALESCE(SUM(line_total), 0) AS revenue FROM order_items GROUP BY item_name ORDER BY quantity DESC LIMIT 10")->fetchAll(),
        ];
        view('admin/reports', ['title' => 'Reports', 'reports' => $reports]);
    }

    public function settings(): void
    {
        $this->guardAdmin();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $settings = $pdo->query("SELECT setting_key, setting_value FROM admin_settings ORDER BY setting_key ASC")->fetchAll();
        view('admin/settings', ['title' => 'Settings', 'settings' => $settings]);
    }

    public function staff(): void
    {
        $this->guardAdmin();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $workers = $pdo->query("SELECT id, name, role, phone, pay_rate, active FROM staff ORDER BY active DESC, name ASC")->fetchAll();
        $attendance = $pdo->query("SELECT a.work_date, s.name, a.status, a.notes FROM attendance a JOIN staff s ON s.id = a.staff_id ORDER BY a.work_date DESC, s.name ASC LIMIT 50")->fetchAll();
        $payments = $pdo->query("SELECT p.paid_on, s.name, p.amount, p.period_start, p.period_end, p.method, p.reference FROM staff_payments p JOIN staff s ON s.id = p.staff_id ORDER BY p.paid_on DESC, p.id DESC LIMIT 50")->fetchAll();
        view('admin/staff', ['title' => 'Staff, Attendance & Payroll', 'workers' => $workers, 'attendance' => $attendance, 'payments' => $payments]);
    }

    public function storeStaff(): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $name = clean_string(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW), 120);
        $role = clean_string(filter_input(INPUT_POST, 'role', FILTER_UNSAFE_RAW), 80);
        $phone = clean_string(filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW), 30);
        $payRate = filter_input(INPUT_POST, 'pay_rate', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
        if ($name === '' || $role === '' || $payRate === false || $payRate === null) {
            flash('danger', 'Worker name, role, and a valid pay rate are required.');
            redirect('/admin/staff');
        }
        $stmt = $pdo->prepare("INSERT INTO staff (name, role, phone, pay_rate) VALUES (:name, :role, :phone, :pay_rate)");
        $stmt->execute(['name' => $name, 'role' => $role, 'phone' => $phone, 'pay_rate' => $payRate]);
        flash('success', 'Worker added.');
        redirect('/admin/staff');
    }

    public function storeAttendance(): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $staffId = filter_input(INPUT_POST, 'staff_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $workDate = trim((string) ($_POST['work_date'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? ''));
        if ($staffId === false || $workDate === '' || !in_array($status, ['present', 'absent', 'leave'], true)) {
            flash('danger', 'Please provide a worker, date, and valid attendance status.');
            redirect('/admin/staff');
        }
        $stmt = $pdo->prepare("INSERT INTO attendance (staff_id, work_date, status, notes) VALUES (:staff_id, :work_date, :status, :notes) ON CONFLICT (staff_id, work_date) DO UPDATE SET status = EXCLUDED.status, notes = EXCLUDED.notes");
        $stmt->execute(['staff_id' => $staffId, 'work_date' => $workDate, 'status' => $status, 'notes' => clean_string((string) ($_POST['notes'] ?? ''), 500)]);
        flash('success', 'Attendance saved.');
        redirect('/admin/staff');
    }

    public function storeStaffPayment(): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $staffId = filter_input(INPUT_POST, 'staff_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.01]]);
        $paidOn = trim((string) ($_POST['paid_on'] ?? ''));
        $periodStart = trim((string) ($_POST['period_start'] ?? ''));
        $periodEnd = trim((string) ($_POST['period_end'] ?? ''));
        $method = trim((string) ($_POST['method'] ?? ''));
        if ($staffId === false || $amount === false || $paidOn === '' || $periodStart === '' || $periodEnd === '' || !in_array($method, ['cash', 'mpesa', 'bank'], true)) {
            flash('danger', 'Please provide complete and valid payment details.');
            redirect('/admin/staff');
        }
        $stmt = $pdo->prepare("INSERT INTO staff_payments (staff_id, amount, paid_on, period_start, period_end, method, reference, notes) VALUES (:staff_id, :amount, :paid_on, :period_start, :period_end, :method, :reference, :notes)");
        $stmt->execute(['staff_id' => $staffId, 'amount' => $amount, 'paid_on' => $paidOn, 'period_start' => $periodStart, 'period_end' => $periodEnd, 'method' => $method, 'reference' => clean_string((string) ($_POST['reference'] ?? ''), 160), 'notes' => clean_string((string) ($_POST['notes'] ?? ''), 500)]);
        flash('success', 'Worker payment recorded. Verify the transfer separately with the payment provider.');
        redirect('/admin/staff');
    }

    public function updateInventory(string $id): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $stock = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $reorder = filter_input(INPUT_POST, 'reorder_level', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($stock === false || $reorder === false) {
            flash('danger', 'Stock values must be zero or greater.');
            redirect('/admin/inventory');
        }
        $stmt = $pdo->prepare("INSERT INTO inventory (menu_item_id, stock_quantity, reorder_level) VALUES (:id, :stock, :reorder) ON CONFLICT (menu_item_id) DO UPDATE SET stock_quantity = EXCLUDED.stock_quantity, reorder_level = EXCLUDED.reorder_level");
        $stmt->execute(['id' => (int) $id, 'stock' => $stock, 'reorder' => $reorder]);
        flash('success', 'Inventory updated.');
        redirect('/admin/inventory');
    }

    public function storeSupplier(): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $name = clean_string(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW), 120);
        if ($name === '') {
            flash('danger', 'Supplier name is required.');
            redirect('/admin/suppliers');
        }
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, email, notes) VALUES (:name, :phone, :email, :notes)");
        $stmt->execute(['name' => $name, 'phone' => clean_string((string) ($_POST['phone'] ?? ''), 30), 'email' => clean_string((string) ($_POST['email'] ?? ''), 160), 'notes' => clean_string((string) ($_POST['notes'] ?? ''), 500)]);
        flash('success', 'Supplier added.');
        redirect('/admin/suppliers');
    }

    public function updateSettings(): void
    {
        $this->guardAdmin();
        verify_csrf_or_fail();
        $pdo = Database::connection();
        $this->ensureAdminModuleTables($pdo);
        $allowed = ['business_name', 'business_phone', 'business_location', 'low_stock_alerts'];
        $stmt = $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (:key, :value) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW()");
        foreach ($allowed as $key) {
            $stmt->execute(['key' => $key, 'value' => clean_string((string) ($_POST[$key] ?? ''), 255)]);
        }
        flash('success', 'Settings saved.');
        redirect('/admin/settings');
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

    private function ensureAdminModuleTables(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (id SERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, phone VARCHAR(30), email VARCHAR(160), notes TEXT, created_at TIMESTAMP NOT NULL DEFAULT NOW())");
        $pdo->exec("CREATE TABLE IF NOT EXISTS inventory (id SERIAL PRIMARY KEY, menu_item_id INTEGER NOT NULL UNIQUE REFERENCES menu_items(id) ON DELETE CASCADE, supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL, stock_quantity INTEGER NOT NULL DEFAULT 0 CHECK (stock_quantity >= 0), reorder_level INTEGER NOT NULL DEFAULT 5 CHECK (reorder_level >= 0), updated_at TIMESTAMP NOT NULL DEFAULT NOW())");
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_settings (setting_key VARCHAR(80) PRIMARY KEY, setting_value VARCHAR(255) NOT NULL DEFAULT '', updated_at TIMESTAMP NOT NULL DEFAULT NOW())");
        $pdo->exec("CREATE TABLE IF NOT EXISTS staff (id SERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, role VARCHAR(80) NOT NULL, phone VARCHAR(30), pay_rate NUMERIC(10, 2) NOT NULL CHECK (pay_rate >= 0), active BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP NOT NULL DEFAULT NOW())");
        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (id SERIAL PRIMARY KEY, staff_id INTEGER NOT NULL REFERENCES staff(id) ON DELETE CASCADE, work_date DATE NOT NULL, status VARCHAR(20) NOT NULL CHECK (status IN ('present', 'absent', 'leave')), notes TEXT, created_at TIMESTAMP NOT NULL DEFAULT NOW(), UNIQUE (staff_id, work_date))");
        $pdo->exec("CREATE TABLE IF NOT EXISTS staff_payments (id SERIAL PRIMARY KEY, staff_id INTEGER NOT NULL REFERENCES staff(id) ON DELETE RESTRICT, amount NUMERIC(10, 2) NOT NULL CHECK (amount > 0), paid_on DATE NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, method VARCHAR(20) NOT NULL CHECK (method IN ('cash', 'mpesa', 'bank')), reference VARCHAR(160), notes TEXT, created_at TIMESTAMP NOT NULL DEFAULT NOW())");
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