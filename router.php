<?php
// Router script for PHP built-in development server
// This handles routing for the development server and ignores .htaccess directives

// Get the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Remove any base path if present
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
    $uri = substr($uri, strlen($scriptDir)) ?: '/';
}

// Resolve the public directory from the project root and normalize the path.
$publicRoot = realpath(__DIR__ . '/public');
$publicPath = null;

if ($uri !== '/' && $publicRoot !== false) {
    $publicPath = $publicRoot . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uri);
}

// Serve static files directly (CSS, JS, images, etc.) if they exist under public.
if ($publicPath !== null && is_file($publicPath) && str_starts_with(realpath($publicPath), $publicRoot . DIRECTORY_SEPARATOR)) {
    return false;
}

// For all other requests, route through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/public/index.php';
