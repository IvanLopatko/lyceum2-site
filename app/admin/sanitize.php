<?php
/** Очищення даних з форм адмінки: лише відомі поля, у тексті — лише дозволені теги. */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

const BLOCK_TYPES = [
    'p' => 'Абзац', 'h' => 'Заголовок', 'list' => 'Список', 'timeline' => 'Хронологія', 'cards' => 'Картки',
    'stats' => 'Цифри', 'faq' => 'Питання й відповіді', 'table' => 'Таблиця', 'callout' => 'Виноска',
    'buttons' => 'Кнопки', 'quote' => 'Цитата', 'compare' => 'Порівняння', 'myths' => 'Міфи і факти',
    'gallery' => 'Галерея', 'component' => 'Живий блок', 'html' => 'HTML (для досвідчених)',
];

const COMPONENTS = [
    'documents' => 'Документи з реєстру', 'team' => 'Люди (кадровий склад)', 'contacts' => 'Контакти ліцею',
    'responsible' => 'Хто за що відповідає', 'map' => 'Карта', 'children' => 'Плитки підсторінок',
    'posts' => 'Новини з рубрики', 'club_list' => 'Гуртки', 'sitemap' => 'Карта сайту', 'toc' => 'Зміст сторінки',
    'countdown' => 'Відлік до академічного ліцею', 'roadmap' => 'План профільної школи',
    'alarm_shifts' => 'Тривога: дві зміни', 'numbers' => 'Ліцей у цифрах', 'today' => 'Сьогодні в ліцеї',
];

function ci(mixed $v): string
{
    return clean_inline((string) $v);
}

function ct(mixed $v): string
{
    return trim(strip_tags((string) $v));
}

function clean_blocks(array $blocks, bool $allowHtml): array
{
    $out = [];
    foreach ($blocks as $b) {
        if (!is_array($b) || !isset(BLOCK_TYPES[$b['type'] ?? ''])) {
            continue;
        }
        $t = $b['type'];
        $items = is_array($b['items'] ?? null) ? $b['items'] : [];
        $c = ['type' => $t];
        switch ($t) {
            case 'p':
                $c['text'] = ci($b['text'] ?? '');
                if (in_array($b['style'] ?? '', ['note', 'lead'], true)) {
                    $c['style'] = $b['style'];
                }
                if ($c['text'] === '') {
                    continue 2;
                }
                break;
            case 'h':
                $c['level'] = (int) ($b['level'] ?? 2) === 3 ? 3 : 2;
                $c['text'] = ct($b['text'] ?? '');
                $a = preg_replace('~[^a-z0-9-]~', '', strtolower((string) ($b['anchor'] ?? ''))) ?? '';
                if ($a !== '') {
                    $c['anchor'] = $a;
                }
                break;
            case 'list':
                $c['style'] = ($b['style'] ?? '') === 'steps' ? 'steps' : 'bullets';
                $c['items'] = array_values(array_filter(array_map('ci', $items), 'strlen'));
                break;
            case 'timeline':
                $c['items'] = array_values(array_filter(array_map(static fn($i) => ['label' => ct($i['label'] ?? ''), 'text' => ci($i['text'] ?? '')], $items), static fn($i) => $i['label'] . $i['text'] !== ''));
                break;
            case 'cards':
                $c['items'] = array_values(array_filter(array_map(static fn($i) => ['icon' => preg_replace('~[^a-z]~', '', (string) ($i['icon'] ?? '')), 'title' => ct($i['title'] ?? ''), 'text' => ci($i['text'] ?? '')], $items), static fn($i) => $i['title'] . $i['text'] !== ''));
                break;
            case 'stats':
                $c['items'] = array_values(array_filter(array_map(static fn($i) => ['n' => ct($i['n'] ?? ''), 'label' => ct($i['label'] ?? ''), 'note' => ct($i['note'] ?? '')], $items), static fn($i) => $i['n'] !== ''));
                break;
            case 'faq':
                $c['items'] = array_values(array_filter(array_map(static fn($i) => ['q' => ct($i['q'] ?? ''), 'a' => ci($i['a'] ?? '')], $items), static fn($i) => $i['q'] !== ''));
                break;
            case 'table':
                $c['head'] = array_map('ci', is_array($b['head'] ?? null) ? $b['head'] : []);
                $c['rows'] = array_map(static fn($r) => array_map('ci', is_array($r) ? $r : []), is_array($b['rows'] ?? null) ? $b['rows'] : []);
                break;
            case 'callout':
                $c['tone'] = CALLOUT_TONES[$b['tone'] ?? ''] ?? 'sun';
                $c['title'] = ct($b['title'] ?? '');
                $c['text'] = ci($b['text'] ?? '');
                break;
            case 'buttons':
                $c['items'] = array_values(array_filter(array_map(static fn($i) => ['label' => ct($i['label'] ?? ''), 'url' => safe_url((string) ($i['url'] ?? ''))], $items), static fn($i) => $i['label'] !== ''));
                break;
            case 'quote':
                $c['text'] = ci($b['text'] ?? '');
                $c['cite'] = ct($b['cite'] ?? '');
                break;
            case 'compare':
                $c['items'] = array_map(static fn($i) => ['label' => ct($i['label'] ?? ''), 'title' => ct($i['title'] ?? ''), 'points' => array_values(array_filter(array_map('ci', is_array($i['points'] ?? null) ? $i['points'] : []), 'strlen'))], array_slice($items, 0, 2));
                break;
            case 'myths':
                $c['items'] = array_values(array_filter(array_map(static fn($i) => ['myth' => ct($i['myth'] ?? ''), 'fact' => ci($i['fact'] ?? '')], $items), static fn($i) => $i['myth'] !== ''));
                break;
            case 'gallery':
                $c['images'] = array_values(array_filter(array_map('strval', is_array($b['images'] ?? null) ? $b['images'] : []), static fn($s) => str_starts_with($s, 'uploads/')));
                break;
            case 'component':
                $name = (string) ($b['name'] ?? '');
                if (!isset(COMPONENTS[$name])) {
                    continue 2;
                }
                $c['name'] = $name;
                $args = [];
                foreach ((array) ($b['args'] ?? []) as $k => $v) {
                    if (preg_match('~^[a-z_]{1,20}$~', (string) $k)) {
                        $args[$k] = ct($v);
                    }
                }
                $args = array_filter($args, 'strlen');
                $c['args'] = $args ?: new stdClass();
                break;
            case 'html':
                if (!$allowHtml) {
                    continue 2;
                }
                $c['html'] = (string) ($b['html'] ?? '');
                break;
        }
        $out[] = $c;
    }
    return $out;
}

/** Абзаци новини: з HTML редактора → масив абзаців із дозволеними тегами. */
function clean_paragraphs(array $paras): array
{
    return array_values(array_filter(array_map(static fn($p) => clean_inline((string) $p), $paras), 'strlen'));
}

function clean_date(string $d): string
{
    // лише справжні дати: 31 лютого чи 13-й місяць не приймаються
    return preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $d, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $d : '';
}
