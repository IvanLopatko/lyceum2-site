<?php
$q = trim((string) ($_GET['q'] ?? ''));
$results = [];
if (mb_strlen($q) >= 2) {
    $needle = mb_strtolower($q);
    $score = static function (string $title, string $text) use ($needle): int {
        $t = mb_strtolower($title);
        $x = mb_strtolower($text);
        return (str_contains($t, $needle) ? 10 : 0) + min(5, substr_count($x, $needle));
    };
    foreach (pages() as $p) {
        if (in_array($p['slug'], SPECIAL_PAGES, true)) continue;
        $text = $p['lead'] . ' ' . strip_tags(json_encode($p['blocks'], JSON_UNESCAPED_UNICODE));
        if ($s = $score($p['title'], $text)) $results[] = [$s, $p['title'], page_path($p['slug']), $p['lead'] ?: excerpt($text, 140), 'Сторінка'];
    }
    foreach (news_all() as $n) {
        $text = implode(' ', $n['text']);
        if ($s = $score($n['title'], $text)) $results[] = [$s, $n['title'], news_url($n), excerpt($text, 140), 'Новина · ' . ua_date($n['date'])];
    }
    foreach (documents() as $d) {
        if ($s = $score($d['title'], $d['number'])) $results[] = [$s, $d['title'], $d['file'] ? '/' . $d['file'] : '/dokumenty/', '', 'Документ'];
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
