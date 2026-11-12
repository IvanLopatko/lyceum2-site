<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/**
 * Пошук по сайту: сторінки, новини, документи, працівники, гуртки.
 * Слова шукаються за основою, тому «розкладу» знаходить «розклад», а «гурток» — «гуртки».
 */
$q = is_string($_GET['q'] ?? null) ? trim(mb_substr($_GET['q'], 0, 100)) : '';
$results = [];

/** Основа слова: для довгих слів відкидаємо закінчення (1–2 літери). */
$stem = static function (string $w): string {
    $n = mb_strlen($w);
    return $n <= 4 ? $w : mb_substr($w, 0, $n >= 6 ? $n - 2 : $n - 1);
};

if (mb_strlen($q) >= 2) {
    $needle = mb_strtolower($q);
    $words = array_values(array_filter(preg_split('~[^\p{L}\p{N}@.]+~u', $needle) ?: [], static fn(string $w): bool => mb_strlen($w) >= 2));
    $stems = array_map($stem, $words);
    $score = static function (string $title, string $text) use ($needle, $stems): int {
        $t = mb_strtolower($title);
        $x = mb_strtolower($text);
        $all = $t . ' ' . $x;
        // точний збіг фрази — найвище
        $pts = (str_contains($t, $needle) ? 20 : 0) + min(5, substr_count($x, $needle));
        if (!$stems) {
            return $pts;
        }
        // усі слова запиту (за основою) мають бути на сторінці
        foreach ($stems as $st) {
            if (!str_contains($all, $st)) {
                return $pts;
            }
            $pts += (str_contains($t, $st) ? 6 : 0) + min(3, substr_count($x, $st));
        }
        return $pts;
    };
    foreach (pages() as $p) {
        if (in_array($p['slug'], SPECIAL_PAGES, true)) continue;
        $text = ($p['lead'] ?? '') . ' ' . strip_tags(json_encode($p['blocks'], JSON_UNESCAPED_UNICODE));
        if ($s = $score($p['title'], $text)) $results[] = [$s + 2, $p['title'], page_path($p['slug']), ($p['lead'] ?? '') ?: excerpt($text, 140), 'Сторінка'];
    }
    // розділи, які не є звичайними сторінками
    $sections = [
        ['Документи', '/dokumenty/', 'Реєстр документів ліцею: статут, освітні програми, звіти, фінанси, накази.', 'документ документи'],
        ['Кадровий склад', '/kadrovyj-sklad/', 'Адміністрація, вчителі, асистенти вчителів і фахівці.', 'працівники працівник вчителі вчитель педагоги колектив'],
        ['Гуртки і клуби', '/gurtky/', 'Гуртки, клуби й секції: керівники, дні й час занять.', 'гурток гуртки секція секції клуб'],
        ['Новини', '/novyny/', 'Події, оголошення й досягнення ліцею.', 'новина новини оголошення події'],
    ];
    foreach ($sections as [$t, $u, $d, $kw]) {
        if ($s = $score($t, $d . ' ' . $kw)) $results[] = [$s + 3, $t, $u, $d, 'Розділ'];
    }
    foreach (news_all() as $n) {
        $text = implode(' ', $n['text']);
        if ($s = $score($n['title'], $text)) $results[] = [$s, $n['title'], news_url($n), excerpt($text, 140), 'Новина · ' . ua_date($n['date'])];
    }
    foreach (documents() as $d) {
        if ($s = $score($d['title'], (string) ($d['number'] ?? ''))) $results[] = [$s, $d['title'], !empty($d['file']) ? '/' . $d['file'] : '/dokumenty/', '', 'Документ'];
    }
    foreach (staff() as $w) {
        if ($s = $score($w['name'], ($w['position'] ?? '') . ' ' . ($w['subjects'] ?? ''))) $results[] = [$s + 1, $w['name'], '/kadrovyj-sklad/#' . ($w['group'] ?? ''), trim(($w['position'] ?? '') . ((string) ($w['subjects'] ?? '') !== '' ? ' · ' . $w['subjects'] : '')), 'Працівник'];
    }
    foreach (clubs() as $c) {
        if ($s = $score($c['title'], ($c['text'] ?? '') . ' ' . ($c['leader'] ?? ''))) $results[] = [$s + 1, $c['title'], '/gurtky/', trim(($c['day'] ?? '') . ' ' . ($c['time'] ?? '')), 'Гурток'];
    }
    usort($results, static fn($a, $b) => $b[0] <=> $a[0]);
    $results = array_slice($results, 0, 40);
}
?>
<section class="page-head"><div class="wrap">
  <h1>Пошук</h1>
  <form class="search search-big" role="search" action="<?= e(url('/poshuk/')) ?>" method="get">
    <label class="sr" for="q-page">Що шукаємо</label>
    <input id="q-page" type="search" name="q" value="<?= e($q) ?>" placeholder="Наприклад: розклад, харчування, статут">
    <button type="submit" class="btn">Шукати</button>
  </form>
</div></section>
<div class="wrap sec-tight">
  <?php if ($q !== '' && !$results): ?><p class="empty">Нічого не знайдено за запитом «<?= e($q) ?>». Спробуйте інше слово або перегляньте <a href="<?= e(url('/karta-sajtu/')) ?>">карту сайту</a>.</p><?php endif; ?>
  <?php if ($results): ?><p class="note">Знайдено: <?= count($results) ?></p>
  <ul class="results"><?php foreach ($results as [$s, $t, $u, $ex, $kind]): ?>
    <li><a href="<?= e(url($u)) ?>"><span class="res-k"><?= e($kind) ?></span><b><?= e($t) ?></b><?php if ($ex): ?><span><?= e($ex) ?></span><?php endif; ?></a></li>
  <?php endforeach; ?></ul><?php endif; ?>
</div>
