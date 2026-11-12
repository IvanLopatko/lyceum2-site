<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$q = trim((string) ($_GET['q'] ?? ''));
$cat = (string) ($_GET['cat'] ?? '');
$all = news_all(true);
if ($cat !== '') {
    $all = array_values(array_filter($all, static fn($n) => in_array($cat, $n['categories'] ?? [], true)));
}
if ($q !== '') {
    $all = array_values(array_filter($all, static fn($n) => mb_stripos($n['title'] . ' ' . implode(' ', $n['text']), $q) !== false));
}
$per = 30;
$p = max(1, (int) ($_GET['p'] ?? 1));
$pages = max(1, (int) ceil(count($all) / $per));
$list = array_slice($all, ($p - 1) * $per, $per);
?>
<header class="head"><h1>Новини</h1><a class="btn" href="<?= e(url('/admin/novyny/nova/')) ?>">+ Новина</a></header>
<form class="filters" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Пошук за словом">
  <select name="cat"><option value="">Усі рубрики</option><?php foreach (taxonomies()['news'] as $t): ?><option value="<?= e($t['slug']) ?>"<?= $cat === $t['slug'] ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select>
  <button class="btn btn-ghost" type="submit">Показати</button>
</form>
<p class="muted small">Знайдено: <?= count($all) ?></p>
<ul class="rows">
  <?php foreach ($list as $n): ?>
    <li><a href="<?= e(url('/admin/novyny/' . $n['id'] . '/')) ?>">
      <?php if ($cv = news_cover($n)): ?><img src="<?= e(thumb_url($cv)) ?>" alt="" width="56" height="42" loading="lazy"><?php else: ?><span class="thumb"></span><?php endif; ?>
      <span><?= e($n['title']) ?><?= !empty($n['draft']) ? ' <em class="pill is-due">чернетка</em>' : '' ?></span><small><?= e(ua_date($n['date'])) ?> · <?= e(tax_name('news', $n['categories'][0] ?? '')) ?></small></a></li>
  <?php endforeach; ?>
</ul>
<?php if ($pages > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="?<?= e(http_build_query(['q' => $q, 'cat' => $cat, 'p' => $i])) ?>"<?= $i === $p ? ' aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
