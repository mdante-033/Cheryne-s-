<?php
define('BASE_PATH', __DIR__);
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/src/Helpers/functions.php';
require_once BASE_PATH . '/config/database.php';
App\Helpers\load_env(BASE_PATH . '/.env');

$item = App\Models\MenuItem::find(1);
echo "Raw item data:\n";
var_dump($item['is_available']);

echo "\nfilter_var result:\n";
var_dump(filter_var($item['is_available'], FILTER_VALIDATE_BOOLEAN));
