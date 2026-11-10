<?php
/**
 * Сайт Біляївського ліцею №2 — запуск.
 *
 * Без фреймворків і бази даних: вміст лежить у JSON-файлах у content/,
 * файли — в uploads/, налаштування доступу — в config/ (не в git).
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
define('CONTENT', ROOT . '/content');
define('UPLOADS', ROOT . '/uploads');
define('CONFIG', ROOT . '/config');

// Сайт може жити в підкаталозі (наприклад, на GitHub Pages чи тестовому хостингу).
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
define('BASE', rtrim(dirname($script), '/'));

date_default_timezone_set('Europe/Kyiv');
mb_internal_encoding('UTF-8');

require APP . '/lib/helpers.php';
require APP . '/lib/store.php';
require APP . '/lib/data.php';
require APP . '/lib/status.php';
require APP . '/lib/render.php';
require APP . '/lib/components.php';
