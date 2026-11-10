<?php
/** Маршрути публічної частини. */

declare(strict_types=1);

function view(string $name, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    include APP . '/views/' . $name . '.php';
    return (string) ob_get_clean();
}

function render_page(string $view, array $vars, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    $vars['body'] = view($view, $vars);
    echo fix_links(view('layout', $vars));
    exit;
}

$path = current_path();
$seg = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

// Адреси без кінцевої скісної риски → з рискою (як у старому сайті).
if ($path !== '/' && !str_ends_with($path, '/') && !str_contains(end($seg) ?: '', '.')) {
    redirect($path . '/', 301);
}

// Головна
if (!$seg) {
    render_page('home', ['title' => '', 'is_home' => true]);
}

// Новини: /novyny/, /novyny/storinka/2/, /novyny/rubryka/<slug>/, /novyny/<id>/
if ($seg[0] === 'novyny') {
    $cat = '';
    $pageNo = 1;
    if (($seg[1] ?? '') === 'rubryka' && isset($seg[2])) {
        $cat = $seg[2];
        if (($seg[3] ?? '') === 'storinka') {
            $pageNo = max(1, (int) ($seg[4] ?? 1));
        }
    } elseif (($seg[1] ?? '') === 'storinka') {
        $pageNo = max(1, (int) ($seg[2] ?? 1));
    } elseif (isset($seg[1]) && ctype_digit($seg[1])) {
        $n = news_item((int) $seg[1]);
        if ($n) {
            render_page('news-item', ['title' => $n['title'], 'item' => $n]);
        }
        render_page('404', ['title' => 'Сторінку не знайдено'], 404);
    }
    render_page('news-list', ['title' => $cat ? tax_name('news', $cat) : 'Новини', 'cat' => $cat, 'pageNo' => $pageNo]);
}

// Реєстр документів, кадри, гуртки, пошук
$special = [
    'dokumenty' => ['documents', 'Документи'],
    'kadrovyj-sklad' => ['staff', 'Кадровий склад'],
    'gurtky' => ['clubs', 'Гуртки і клуби'],
    'poshuk' => ['search', 'Пошук'],
];
if (count($seg) === 1 && isset($special[$seg[0]])) {
    [$v, $t] = $special[$seg[0]];
    render_page($v, ['title' => $t]);
}

// Звичайні сторінки
$page = page_by_path($seg);
if ($page && !in_array($page['slug'], SPECIAL_PAGES, true)) {
    render_page('page', ['title' => $page['title'], 'page' => $page]);
}

render_page('404', ['title' => 'Сторінку не знайдено'], 404);
