<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$vendor = BASE_PATH . '/vendor/autoload.php';
if (is_file($vendor)) {
    require $vendor;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
        $file = BASE_PATH . '/src/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

require_once BASE_PATH . '/src/Helpers/functions.php';
\App\Helpers\load_env(BASE_PATH . '/.env');

// Must be computed AFTER load_env() runs above - it calls putenv(), which is
// what makes getenv() below actually see values from .env rather than only
// real OS-level environment variables (which are never set for local dev).
define('SKIP_DB_TESTS', getenv('DB_PASS') === false || getenv('DB_PASS') === '');
