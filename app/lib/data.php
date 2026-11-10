<?php
/** Доступ до вмісту: налаштування, сторінки, новини, документи, кадри, гуртки. */

declare(strict_types=1);

const SPECIAL_PAGES = ['holovna', 'novyny'];

function settings(): array
{
    return store_read('settings.json', []);
}

function setting(string $path, mixed $default = ''): mixed
{
    $v = settings();
    foreach (explode('.', $path) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) {
            return $default;
        }
        $v = $v[$k];
    }
    return $v;
}

function taxonomies(): array
{
    return store_read('taxonomies.json', ['news' => [], 'documents' => [], 'staff' => []]);
}

function tax_name(string $tax, string $slug): string
{
    foreach (taxonomies()[$tax] ?? [] as $t) {
        if ($t['slug'] === $slug) {
            return $t['name'];
        }
    }
    return $slug;
}

/* ── Сторінки ── */

function pages(): array
{
    return store_list('pages');
}

function page(string $slug): ?array
{
    return pages()[$slug] ?? null;
}

function page_children(string $slug): array
{
    $kids = array_filter(pages(), static fn(array $p): bool => ($p['parent'] ?? '') === $slug);
    uasort($kids, static fn(array $a, array $b): int => [$a['order'] ?? 0, $a['title']] <=> [$b['order'] ?? 0, $b['title']]);
    return $kids;
}

function page_path(string $slug): string
{
    $parts = [];
    $guard = 0;
    while ($slug !== '' && ($p = page($slug)) && $guard++ < 5) {
        array_unshift($parts, $slug);
        $slug = $p['parent'] ?? '';
    }
    return '/' . implode('/', $parts) . '/';
}

/** Розділи головного меню: сторінки верхнього рівня, у яких є підсторінки. */
function nav_sections(): array
{
    $top = array_filter(pages(), static fn(array $p): bool => ($p['parent'] ?? '') === '' && !in_array($p['slug'], SPECIAL_PAGES, true));
    $sections = array_filter($top, static fn(array $p): bool => page_children($p['slug']) !== []);
    uasort($sections, static fn(array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    return $sections;
}

/** Сторінка за адресою «/rozdil/storinka/» з перевіркою батьківського ланцюжка. */
function page_by_path(array $segments): ?array
{
    if (!$segments) {
        return null;
    }
    $slug = end($segments);
    $p = page($slug);
    if (!$p) {
        return null;
    }
    return trim(page_path($slug), '/') === implode('/', $segments) ? $p : null;
}

/* ── Новини ── */

function news_all(): array
{
    $all = array_values(store_list('news'));
    usort($all, static fn(array $a, array $b): int => strcmp($b['date'], $a['date']));
    return $all;
}

function news_item(int $id): ?array
{
    $n = store_read('news/' . $id . '.json', null);
    return is_array($n) ? $n : null;
}

function news_in(string $cat): array
{
    return array_values(array_filter(news_all(), static fn(array $n): bool => in_array($cat, $n['categories'] ?? [], true)));
}

function news_url(array $n): string
{
    return '/novyny/' . $n['id'] . '/';
}

function news_cover(array $n): string
{
    return $n['cover'] ?: ($n['images'][0] ?? '');
}

function news_next_id(): int
{
    $ids = array_map(static fn(array $n): int => (int) $n['id'], news_all());
    return ($ids ? max($ids) : 1000) + 1;
}

/* ── Документи, кадри, гуртки ── */

function documents(): array
{
    return store_read('documents.json', []);
}

function documents_in(string $section, bool $archive = false): array
{
    $docs = array_filter(documents(), static function (array $d) use ($section, $archive): bool {
        if (!$archive && ($d['valid'] ?? '') === 'archive') {
            return false;
        }
        return $section === '' || in_array($section, $d['sections'] ?? [], true);
    });
    usort($docs, static fn(array $a, array $b): int => strcmp($b['approved'] ?: '0', $a['approved'] ?: '0'));
    return $docs;
}

function staff(): array
{
    $all = store_read('staff.json', []);
    usort($all, static fn(array $a, array $b): int => [$a['order'] ?? 0, $a['name']] <=> [$b['order'] ?? 0, $b['name']]);
    return $all;
}

function clubs(): array
{
    return store_read('clubs.json', []);
}

function profile_plan(): array
{
    return store_read('profile-plan.json', ['stages' => [], 'months' => [], 'start' => '2027-09-01']);
}

/** Чи є на сайті зразкові дані (для смуги «Зразок сайту»). */
function has_demo(): bool
{
    foreach ([documents(), staff(), clubs()] as $list) {
        foreach ($list as $x) {
            if (!empty($x['demo'])) {
                return true;
            }
        }
    }
    foreach (pages() as $p) {
        if (!empty($p['template'])) {
            return true;
        }
    }
    return false;
}
