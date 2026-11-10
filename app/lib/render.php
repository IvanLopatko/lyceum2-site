<?php
/**
 * Перетворення блоків сторінки на HTML.
 *
 * Типи блоків — ті самі, що в адмінці: p, h, list, timeline, cards, stats, faq,
 * table, callout, buttons, quote, compare, myths, gallery, html, component.
 */

declare(strict_types=1);

const CALLOUT_TONES = ['sun' => 'sun', 'blue' => 'blue', 'green' => 'green', 'sail' => 'sail', 'note' => 'sun', 'info' => 'blue', 'alert' => 'sail'];

function render_blocks(array $blocks, array $ctx = []): string
{
    $out = '';
    foreach ($blocks as $b) {
        $out .= render_block($b, $ctx);
    }
    return $out;
}

function render_block(array $b, array $ctx): string
{
    $t = $b['type'] ?? '';
    switch ($t) {
        case 'p':
            $cls = match ($b['style'] ?? '') {
                'vit-note', 'note' => ' class="note"',
                'lead' => ' class="lead"',
                default => '',
            };
            return "<p$cls>" . $b['text'] . "</p>\n";

        case 'h':
            $l = (int) ($b['level'] ?? 2) === 3 ? 3 : 2;
            $id = !empty($b['anchor']) ? ' id="' . e($b['anchor']) . '"' : '';
            return "<h$l$id>" . $b['text'] . "</h$l>\n";

        case 'list':
            $tag = ($b['style'] ?? '') === 'steps' ? 'ol' : 'ul';
            $cls = $tag === 'ol' ? 'steps' : 'list';
            $li = '';
            foreach ($b['items'] ?? [] as $i) {
                $li .= '<li>' . $i . '</li>';
            }
            return "<$tag class=\"$cls\">$li</$tag>\n";

        case 'timeline':
            $li = '';
            foreach ($b['items'] ?? [] as $i) {
                $li .= '<li><b>' . $i['label'] . '</b><span>' . $i['text'] . '</span></li>';
            }
            return "<ol class=\"timeline\">$li</ol>\n";

        case 'cards':
            $out = '<div class="cards">';
            foreach ($b['items'] ?? [] as $c) {
                $out .= '<article class="card">'
                    . ($c['icon'] ? '<span class="ico i-' . e($c['icon']) . '" aria-hidden="true"></span>' : '')
                    . '<h3>' . $c['title'] . '</h3><p>' . $c['text'] . '</p></article>';
            }
            return $out . "</div>\n";

        case 'stats':
            $out = '<div class="stats">';
            foreach ($b['items'] ?? [] as $s) {
                $out .= '<div class="stat"><b class="stat-n">' . $s['n'] . '</b><span class="stat-l">' . $s['label'] . '</span>'
                    . (!empty($s['note']) ? '<span class="stat-note">' . $s['note'] . '</span>' : '') . '</div>';
            }
            return $out . "</div>\n";

        case 'faq':
            $out = '<div class="faq">';
            foreach ($b['items'] ?? [] as $f) {
                $out .= '<details><summary>' . $f['q'] . '</summary><div class="faq-a"><p>' . $f['a'] . '</p></div></details>';
            }
            return $out . "</div>\n";

        case 'table':
            $h = '';
            foreach ($b['head'] ?? [] as $c) {
                $h .= '<th scope="col">' . $c . '</th>';
            }
            $r = '';
            foreach ($b['rows'] ?? [] as $row) {
                $r .= '<tr>';
                foreach ($row as $c) {
                    $r .= '<td>' . $c . '</td>';
                }
                $r .= '</tr>';
            }
            return '<div class="table-wrap" tabindex="0"><table>' . ($h ? "<thead><tr>$h</tr></thead>" : '') . "<tbody>$r</tbody></table></div>\n";

        case 'callout':
            $tone = CALLOUT_TONES[$b['tone'] ?? 'sun'] ?? 'sun';
            return '<aside class="callout is-' . $tone . '">' . (!empty($b['title']) ? '<h3>' . $b['title'] . '</h3>' : '') . '<p>' . $b['text'] . "</p></aside>\n";

        case 'buttons':
            $out = '<div class="actions">';
            foreach ($b['items'] ?? [] as $i => $btn) {
                $ext = preg_match('~^https?://~', $btn['url']) ? ' target="_blank" rel="noopener"' : '';
                $out .= '<a class="btn' . ($i ? ' btn-ghost' : '') . '" href="' . e(url($btn['url'])) . '"' . $ext . '>' . e(strip_tags($btn['label'])) . '</a>';
            }
            return $out . "</div>\n";

        case 'quote':
            return '<blockquote class="quote"><p>' . $b['text'] . '</p>' . (!empty($b['cite']) ? '<cite>' . $b['cite'] . '</cite>' : '') . "</blockquote>\n";

        case 'compare':
            $out = '<div class="compare">';
            foreach ($b['items'] ?? [] as $i => $c) {
                if ($i === 1) {
                    $out .= '<span class="compare-arrow" aria-hidden="true"></span>';
                }
                $li = '';
                foreach ($c['points'] ?? [] as $pt) {
                    $li .= '<li>' . $pt . '</li>';
                }
                $out .= '<div class="compare-col' . ($i ? ' is-next' : '') . '"><p class="eyebrow">' . e($c['label']) . '</p><h3>' . e($c['title']) . "</h3><ul class=\"list\">$li</ul></div>";
            }
            return $out . "</div>\n";

        case 'myths':
            $out = '<div class="myths">';
            foreach ($b['items'] ?? [] as $m) {
                $out .= '<div class="myth"><p class="myth-m"><span>Міф</span>«' . $m['myth'] . '»</p><p class="myth-f"><span>Факт</span>' . $m['fact'] . '</p></div>';
            }
            return $out . "</div>\n";

        case 'gallery':
            $out = '<div class="gallery">';
            foreach ($b['images'] ?? [] as $src) {
                $out .= '<a href="' . e(upload_url($src)) . '"><img src="' . e(upload_url($src)) . '" alt="" loading="lazy"></a>';
            }
            return $out . "</div>\n";

        case 'html':
            return ($b['html'] ?? '') . "\n";

        case 'component':
            $fn = 'component_' . preg_replace('~[^a-z_]~', '', (string) ($b['name'] ?? ''));
            return function_exists($fn) ? $fn((array) ($b['args'] ?? []), $ctx) . "\n" : '';
    }
    return '';
}

/** Кореневі адреси в тексті («/kontakty/») — з урахуванням підкаталогу сайту. */
function fix_links(string $html): string
{
    if (BASE === '') {
        return $html;
    }
    return preg_replace('~(href|src)="/(?!/)~', '$1="' . BASE . '/', $html) ?? $html;
}

/** Заголовки з якорями — для змісту сторінки. */
function page_anchors(array $blocks): array
{
    $out = [];
    foreach ($blocks as $b) {
        if (($b['type'] ?? '') === 'h' && !empty($b['anchor'])) {
            $out[$b['anchor']] = strip_tags($b['text']);
        }
    }
    return $out;
}
