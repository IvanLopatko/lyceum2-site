<?php
/** Точка входу: усі адреси сайту і адмінки обробляються тут. */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$path = current_path();

if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    require APP . '/admin/admin.php';
    exit;
}

require APP . '/router.php';
