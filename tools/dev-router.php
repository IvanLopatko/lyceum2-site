<?php
// Для локального запуску вбудованим сервером PHP:  php -S 127.0.0.1:9600 tools/dev-router.php
$file = dirname(__DIR__) . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require dirname(__DIR__) . '/index.php';
