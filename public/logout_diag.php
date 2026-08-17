<?php
// Diagnostic: simulate a POST to /auth/logout exactly as the admin page would send it
chdir(__DIR__);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/auth/logout';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_USER_AGENT'] = 'DiagnosticScript';
$_POST['_csrf'] = 'placeholder';

define('BASE_PATH', __DIR__);
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/src/Helpers/functions.php';
require_once BASE_PATH . '/config/database.php';

use function App\Helpers\load_env;
load_env(BASE_PATH . '/.env');

$routes = require BASE_PATH . '/routes.php';
$method = strtoupper($_SERVER['REQUEST_METHOD']);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

echo "Method: $method\n";
echo "Path: $path\n";
echo "Routes for POST:\n";
foreach ($routes['POST'] as $pattern => $handler) {
    echo "  $pattern => " . implode('::', $handler) . "\n";
}

$matchRoute = static function (array $routesForMethod, string $requestPath): ?array {
    foreach ($routesForMethod as $pattern => $handler) {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (preg_match($regex, $requestPath, $matches) === 1) {
            return [$handler, []];
        }
    }
    return null;
};

$matched = $matchRoute($routes[$method] ?? [], $path);
echo "\nMatch result: " . ($matched === null ? "NULL (404!)" : "FOUND -> " . implode('::', $matched[0])) . "\n";
