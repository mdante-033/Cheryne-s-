<?php
define('BASE_PATH', __DIR__);
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/src/Helpers/functions.php';
require_once BASE_PATH . '/config/database.php';
App\Helpers\load_env(BASE_PATH . '/.env');

$item = App\Models\MenuItem::find(1);
echo "is_available raw type: " . gettype($item['is_available'] ?? null) . "\n";
echo "is_available value: ";
var_export($item['is_available'] ?? null);
echo "\nfilter_var(is_available, BOOLEAN): ";
var_export(filter_var($item['is_available'] ?? null, FILTER_VALIDATE_BOOLEAN));
echo "\nDone.\n";
