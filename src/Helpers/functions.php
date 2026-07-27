<?php
declare(strict_types=1);

namespace App\Helpers;

function base_path(string $path = ''): string
{
    $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function public_path(string $path = ''): string
{
    return base_path('public' . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR)));
}

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => $value,
    };
}

function config(string $file): array
{
    $path = base_path('config' . DIRECTORY_SEPARATOR . $file . '.php');
    return is_file($path) ? require $path : [];
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = filter_var(env('SESSION_SECURE', false), FILTER_VALIDATE_BOOLEAN);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');

    session_start();

    $now = time();

    // 1. Idle Timeout (e.g., 30 mins)
    $timeout = (int) env('SESSION_TIMEOUT', 1800);
    if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > $timeout) {
        destroy_session_completely();
        return;
    }

    // 2. Absolute Timeout (e.g., 4 hours) - prevent permanent hijacked sessions
    $absoluteLimit = (int) env('SESSION_ABSOLUTE_LIMIT', 14400);
    if (isset($_SESSION['created_at']) && ($now - (int) $_SESSION['created_at']) > $absoluteLimit) {
        destroy_session_completely();
        return;
    }

    $_SESSION['last_activity'] = $now;
    
    // Initialize created_at for new sessions
    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    }
}

/**
 * Verify User-Agent binding to prevent session hijacking.
 */
function verify_session_integrity(): void
{
    if (isset($_SESSION['user']) && isset($_SESSION['fingerprint'])) {
        $currentFingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        if (!hash_equals($_SESSION['fingerprint'], $currentFingerprint)) {
            log_event('critical', 'Session fingerprint mismatch', ['user_id' => $_SESSION['user']['id'] ?? 'guest']);
            destroy_session_completely();
            redirect('/auth/login');
        }
    }
}

function destroy_session_completely(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    session_start();
}

function send_secure_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    
    if (filter_var(env('SESSION_SECURE', false), FILTER_VALIDATE_BOOLEAN)) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://images.unsplash.com https://placehold.co; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' https://js.stripe.com; frame-src https://www.google.com https://js.stripe.com; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $app = config('app');
    $base = rtrim((string) ($app['url'] ?? env('APP_URL', 'http://localhost:3000')), '/');
    return $base . '/' . ltrim($path, '/');
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return $path === '/' ? '/' : $path;
}

function redirect(string $path, int $status = 302): void
{
    header('Location: ' . url($path), true, $status);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

/**
 * Encrypts data using AES-256-GCM.
 *
 * @param string $data The data to encrypt.
 * @param string $key The encryption key (32 bytes).
 * @return string The base64 encoded encrypted data (IV + Ciphertext + Tag).
 * @throws \RuntimeException If encryption fails.
 */
function encrypt_data(string $data, string $key): string
{
    $cipher = 'aes-256-gcm';
    if (!in_array($cipher, openssl_get_cipher_methods(true), true)) {
        throw new \RuntimeException("Cipher method '{$cipher}' not supported by OpenSSL.");
    }

    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    if ($iv === false) {
        throw new \RuntimeException('Failed to generate cryptographically secure IV.');
    }

    $tag = ''; // Will be set by openssl_encrypt
    $ciphertext = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16); // 16 is tag_length
    if ($ciphertext === false) {
        throw new \RuntimeException('Encryption failed.');
    }

    // Combine IV, ciphertext, and tag for storage. Base64 encode the result.
    return base64_encode($iv . $tag . $ciphertext);
}

/**
 * Decrypts data using AES-256-GCM.
 *
 * @param string $encryptedData The base64 encoded encrypted data (IV + Ciphertext + Tag).
 * @param string $key The encryption key (32 bytes).
 * @return string The decrypted data.
 * @throws \RuntimeException If decryption fails or data is tampered.
 */
function decrypt_data(string $encryptedData, string $key): string
{
    $cipher = 'aes-256-gcm';
    if (!in_array($cipher, openssl_get_cipher_methods(true), true)) {
        throw new \RuntimeException("Cipher method '{$cipher}' not supported by OpenSSL.");
    }

    $decoded = base64_decode($encryptedData, true);
    if ($decoded === false) {
        throw new \RuntimeException('Base64 decoding of encrypted data failed.');
    }

    $ivlen = openssl_cipher_iv_length($cipher);
    $taglen = 16; // GCM authentication tag length is typically 16 bytes

    if (strlen($decoded) < $ivlen + $taglen) {
        throw new \RuntimeException('Encrypted data is too short or malformed.');
    }

    $iv = substr($decoded, 0, $ivlen);
    $tag = substr($decoded, $ivlen, $taglen);
    $ciphertext = substr($decoded, $ivlen + $taglen);

    $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        throw new \RuntimeException('Decryption failed or data was tampered.');
    }

    return $plaintext;
}

/**
 * Regenerate the CSRF token to prevent fixation or reuse after privilege changes.
 */
function rotate_csrf_token(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Retrieves and validates the encryption key from environment variables.
 *
 * @return string The raw 32-byte encryption key.
 * @throws \RuntimeException If the ENCRYPTION_KEY environment variable is not set or invalid.
 */
function get_encryption_key(): string
{
    $key = env('ENCRYPTION_KEY');
    if (!is_string($key) || empty($key)) {
        throw new \RuntimeException('ENCRYPTION_KEY environment variable is not set. Please generate a 32-byte key.');
    }

    // If the key is a 64-character hex string, convert it to raw bytes.
    if (strlen($key) === 64 && ctype_xdigit($key)) {
        $key = hex2bin($key);
    }

    if (strlen($key) !== 32) {
        throw new \RuntimeException('ENCRYPTION_KEY must be a 32-byte string (or 64-character hex string). Current length: ' . strlen($key) . ' bytes.');
    }
    return $key;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
}

function verify_csrf_or_fail(): void
{
    // Retrieve token from POST data or the X-CSRF-TOKEN header (common for AJAX)
    $token = filter_input(INPUT_POST, '_csrf', FILTER_DEFAULT);
    if ($token === null && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    if (!verify_csrf(is_string($token) ? $token : null)) {
        http_response_code(419);
        view('errors/419', [
            'title' => 'Session expired',
            'description' => 'Please refresh the page and try again.',
        ]);
        exit;
    }
}

function view(string $template, array $data = [], int $status = 200): void
{
    http_response_code($status);
    extract($data, EXTR_SKIP);

    $viewPath = base_path('src' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php');
    if (!is_file($viewPath)) {
        throw new \RuntimeException('View not found: ' . $template);
    }

    ob_start();
    require $viewPath;
    $content = ob_get_clean();
    require base_path('src' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'layout.php');
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function money(mixed $amount): string
{
    return 'KSh ' . number_format((float) $amount, 2);
}

function cart_items(): array
{
    return array_values($_SESSION['cart'] ?? []);
}

function cart_total(): float
{
    return array_reduce(
        cart_items(),
        static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0)),
        0.0
    );
}

function cart_count(): int
{
    return array_reduce(
        cart_items(),
        static fn (int $sum, array $item): int => $sum + (int) ($item['quantity'] ?? 0),
        0
    );
}

function current_user(): ?array
{
    if (empty($_SESSION['user'])) {
        return null;
    }

    return is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_admin(): bool
{
    $user = current_user();
    return ($user['role'] ?? null) === 'admin';
}

function rate_limit(string $scope, int $limit, int $seconds): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $scope = trim($scope);

    try {
        $pdo = \App\Config\Database::connection();
        $cutoff = date('Y-m-d H:i:s', time() - $seconds);
        $pdo->prepare('DELETE FROM rate_limits WHERE created_at < :cutoff')->execute(['cutoff' => $cutoff]);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE scope = :scope AND ip = :ip AND created_at >= :cutoff');
        $stmt->execute(['scope' => $scope, 'ip' => $ip, 'cutoff' => $cutoff]);

        if ((int) $stmt->fetchColumn() >= $limit) {
            return false;
        }

        $pdo->prepare('INSERT INTO rate_limits (scope, ip, created_at) VALUES (:scope, :ip, NOW())')->execute([
            'scope' => $scope,
            'ip' => $ip,
        ]);

        return true;
    } catch (\Throwable) {
        return false;
    }
}

function clean_string(?string $value, int $max = 255): string
{
    $value = trim((string) $value);
    $value = preg_replace('/[^\P{C}\t\r\n]+/u', '', $value) ?? '';
    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
}

function valid_phone(string $phone): bool
{
    return preg_match('/^[0-9 +()-]{7,20}$/', $phone) === 1;
}

function log_event(string $level, string $message, array $context = []): void
{
    $dir = base_path('logs');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $line = json_encode([
        'time' => date(DATE_ATOM),
        'level' => $level,
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL;

    file_put_contents($dir . DIRECTORY_SEPARATOR . 'app.log', $line, FILE_APPEND | LOCK_EX);
}
