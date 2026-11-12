<?php
/** Маршрути публічної частини. */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

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
    header('X-Frame-Options: SAMEORIGIN');
    // Правила для браузера: скрипти — лише власні файли сайту (і один вбудований, за відбитком).
    // Навіть якщо в текст сторінки якось потрапить чужий код, браузер його не виконає.
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'sha256-" . base64_encode(hash('sha256', A11Y_BOOT, true)) . "'; "
        . "style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; media-src 'self' https:; "
        . "frame-src https://www.openstreetmap.org https://www.youtube.com https://www.youtube-nocookie.com https://www.google.com https://docs.google.com https://drive.google.com https://www.facebook.com; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    $vars['status'] = $status;
    $vars['body'] = view($view, $vars);
    echo fix_links(view('layout', $vars));
    exit;
}

function not_found(): never
{
    render_page('404', ['title' => 'Сторінку не знайдено'], 404);
}

/** Чи сайт ще закритий від пошуковиків: поки ввімкнено смугу «Зразок сайту» і є зразкові дані. */
function site_hidden_from_search(): bool
{
    return (bool) setting('site.demo_bar', true) && has_demo();
}

/** Номер сторінки зі шматка адреси: лише «2», «3»… Інакше — null (сторінки не існує). */
function page_number(?string $raw): ?int
{
    return ($raw !== null && ctype_digit($raw) && (int) $raw >= 2 && strlen($raw) < 6) ? (int) $raw : null;
}

$rawPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = current_path();
$seg = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

// Подвійні скісні риски («/navchannia//rozklad/») → одна.
if (str_contains($rawPath, '//')) {
    redirect('/' . implode('/', $seg) . ($seg ? '/' : ''), 301);
}

/* ── Службові файли для пошуковиків і браузерів ── */
if ($path === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    if (site_hidden_from_search()) {
        echo "# Сайт ще наповнюється, тому тимчасово закритий від пошуковиків.\nUser-agent: *\nDisallow: /\n";
    } else {
        echo "User-agent: *\nDisallow: " . BASE . "/admin/\nDisallow: " . BASE . "/poshuk/\n\nSitemap: " . abs_url('/sitemap.xml') . "\n";
    }
    exit;
}
if ($path === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    $urls = [['/', today()], ['/novyny/', substr((string) (news_all()[0]['date'] ?? today()), 0, 10)], ['/dokumenty/', ''], ['/kadrovyj-sklad/', ''], ['/gurtky/', '']];
    foreach (pages() as $p) {
        if (!in_array($p['slug'], SPECIAL_PAGES, true)) {
            $urls[] = [page_path($p['slug']), (string) ($p['updated'] ?? '')];
        }
    }
    foreach (news_all() as $n) {
        $urls[] = [news_url($n), substr((string) $n['date'], 0, 10)];
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as [$u, $mod]) {
        echo '  <url><loc>' . e(abs_url($u)) . '</loc>' . (preg_match('~^\d{4}-\d\d-\d\d$~', $mod) ? '<lastmod>' . $mod . '</lastmod>' : '') . "</url>\n";
    }
    echo "</urlset>\n";
    exit;
}
if ($path === '/favicon.ico') {
    redirect('/uploads/emblema.png', 301);
}

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
    $rest = array_slice($seg, 1);
    if (($rest[0] ?? '') === 'rubryka') {
        $cat = (string) ($rest[1] ?? '');
        if ($cat === '' || !in_array($cat, array_column(taxonomies()['news'], 'slug'), true)) {
            not_found();
        }
        $rest = array_slice($rest, 2);
    } elseif (count($rest) === 1 && ctype_digit($rest[0])) {
        $n = news_item((int) $rest[0]);
        if (!$n || (string) $n['id'] !== $rest[0]) {
            not_found();
        }
        if (!empty($n['draft'])) {
            // чернетку бачить лише той, хто ввійшов в адмінку
            require_once APP . '/admin/auth.php';
            if (!admin_logged_in()) {
                not_found();
            }
            header('Cache-Control: no-store');
        }
        render_page('news-item', ['title' => $n['title'], 'item' => $n, 'description' => $n['excerpt'] ?: implode(' ', $n['text']), 'image' => news_cover($n), 'noindex' => !empty($n['draft'])]);
    }
    if ($rest) {
        // далі може бути лише «storinka/N», де N ≥ 2 і така сторінка існує
        $pageNo = (count($rest) === 2 && $rest[0] === 'storinka') ? page_number($rest[1]) : null;
        $total = (int) ceil(count($cat ? news_in($cat) : news_all()) / 12);
        if ($pageNo === null || $pageNo > $total) {
            not_found();
        }
    }
    $t = $cat ? tax_name('news', $cat) : 'Новини';
    render_page('news-list', ['title' => ($cat ? 'Новини: ' . $t : $t) . ($pageNo > 1 ? ' — сторінка ' . $pageNo : ''), 'heading' => $t, 'cat' => $cat, 'pageNo' => $pageNo, 'description' => 'Новини Біляївського ліцею №2: події, оголошення й досягнення.' . ($cat ? ' Рубрика «' . $t . '».' : '')]);
}

// Реєстр документів, кадри, гуртки, пошук
$special = [
    'dokumenty' => ['documents', 'Документи', 'Реєстр документів Біляївського ліцею №2 за статтею 30 Закону України «Про освіту»: установчі документи, освітні програми, звіти, фінанси.'],
    'kadrovyj-sklad' => ['staff', 'Кадровий склад', 'Адміністрація, вчителі, асистенти вчителів і фахівці Біляївського ліцею №2.'],
    'gurtky' => ['clubs', 'Гуртки і клуби', 'Гуртки, клуби й секції Біляївського ліцею №2: керівники, дні й час занять.'],
    'poshuk' => ['search', 'Пошук', 'Пошук по сайту Біляївського ліцею №2.'],
];
if (count($seg) === 1 && isset($special[$seg[0]])) {
    [$v, $t, $d] = $special[$seg[0]];
    render_page($v, ['title' => $t, 'description' => $d, 'noindex' => $v === 'search']);
}

// Звичайні сторінки
$page = page_by_path($seg);
if ($page && !in_array($page['slug'], SPECIAL_PAGES, true)) {
    render_page('page', ['title' => $page['title'], 'page' => $page, 'description' => $page['lead'] ?? '']);
}

not_found();
