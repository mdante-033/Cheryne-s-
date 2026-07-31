<?php
declare(strict_types=1);

// Step out of public/ folder to get the true project root
define('BASE_PATH', dirname(__DIR__));

// Static file serving is handled exclusively by router.php (the top-level
// script passed to `php -S`). A duplicate check here would silently swallow
// the response whenever this file is require'd, since `return false` only
// tells the built-in server to serve a file when it happens at the true
// top level - inside a require, it just ends execution with no output.

ob_start();

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
require_once BASE_PATH . '/config/database.php';

use function App\Helpers\env;
use function App\Helpers\load_env;
use function App\Helpers\log_event;
use function App\Helpers\send_secure_headers;
use function App\Helpers\start_secure_session;
use function App\Helpers\verify_session_integrity;
use function App\Helpers\view;

load_env(BASE_PATH . '/.env');
date_default_timezone_set((string) env('APP_TIMEZONE', 'Africa/Nairobi'));

$appDebug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
$appEnv = strtolower((string) env('APP_ENV', 'production'));
if ($appEnv === 'production' || $appEnv === 'prod') {
    $appDebug = false;
}

start_secure_session();
verify_session_integrity();
send_secure_headers();

$routes = require BASE_PATH . '/routes.php';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
    $path = substr($path, strlen($scriptDir)) ?: '/';
}

$matchRoute = static function (array $routesForMethod, string $requestPath): ?array {
    foreach ($routesForMethod as $pattern => $handler) {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (preg_match($regex, $requestPath, $matches) === 1) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return [$handler, $params];
        }
    }
    return null;
};

$safeError = static function (int $code, string $title, string $description): void {
    http_response_code($code);
    ob_clean();
    try {
        view("errors/$code", compact('title', 'description'), $code);
    } catch (Throwable) {
        echo "<!DOCTYPE html><html><head><title>", htmlspecialchars($title), "</title></head>",
             "<body><h1>", htmlspecialchars($title), "</h1>",
             "<p>", htmlspecialchars($description), "</p></body></html>";
    }
    exit;
};

try {
    $matched = $matchRoute($routes[$method] ?? [], $path);
    if ($matched === null) {
        $safeError(404, 'Page not found', "The requested Cheryne's page could not be found.");
    }

    [$handler, $params] = $matched;
    [$controllerClass, $action] = $handler;

    if (!class_exists($controllerClass)) {
        throw new RuntimeException("Controller not found: $controllerClass");
    }

    $controller = new $controllerClass();

    if (!method_exists($controller, $action)) {
        throw new RuntimeException("Action not found: $controllerClass::$action");
    }

    $controller->{$action}(...array_values($params));
} catch (Throwable $throwable) {
    log_event('error', $throwable->getMessage(), ['exception' => get_class($throwable)]);

    if ($appDebug) {
        ob_clean();
        throw $throwable;
    }

    $safeError(500, 'Something went wrong', "Cheryne's is having a temporary issue.");
}
