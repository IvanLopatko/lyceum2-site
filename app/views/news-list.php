<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/** @var string $cat @var int $pageNo */
$per = 12;
$all = $cat ? news_in($cat) : news_all();
$pages = max(1, (int) ceil(count($all) / $per));
$pageNo = min($pageNo, $pages);
$items = array_slice($all, ($pageNo - 1) * $per, $per);
$base = $cat ? '/novyny/rubryka/' . $cat . '/' : '/novyny/';
$pageUrl = static fn(int $n): string => $n === 1 ? $base : $base . 'storinka/' . $n . '/';
?>
<section class="page-head"><div class="wrap">
  <nav class="crumbs" aria-label="Ви тут"><a href="<?= e(url('/')) ?>">Головна</a><?php if ($cat): ?><a href="<?= e(url('/novyny/')) ?>">Новини</a><?php endif; ?><span aria-current="page"><?= e($heading ?? $title) ?></span></nav>
  <h1><?= e($heading ?? $title) ?></h1>
  <p class="lead">Події, оголошення й досягнення ліцею. Дописи переносимо зі спільноти ліцею у Facebook.</p>
  <nav class="chips chips-nav" aria-label="Рубрики">
    <a href="<?= e(url('/novyny/')) ?>"<?= $cat === '' ? ' aria-current="page"' : '' ?>>Усі</a>
    <?php foreach (taxonomies()['news'] as $t): if (!news_in($t['slug'])) continue; ?>
      <a href="<?= e(url('/novyny/rubryka/' . $t['slug'] . '/')) ?>"<?= $cat === $t['slug'] ? ' aria-current="page"' : '' ?>><?= e($t['name']) ?></a>
    <?php endforeach; ?>
  </nav>
</div></section>
<div class="wrap sec-tight">
  <?php if (!$items): ?><p class="empty">Новин у цій рубриці поки немає.</p><?php endif; ?>
  <ul class="news-grid">
    <?php foreach ($items as $n): ?>
      <li><a href="<?= e(url(news_url($n))) ?>">
        <?php if ($cv = news_cover($n)): ?><img src="<?= e(thumb_url($cv)) ?>" alt="" loading="lazy" width="720" height="480"><?php else: ?><span class="news-ph" aria-hidden="true"></span><?php endif; ?>
        <span class="news-body"><span class="tag is-<?= e($n['categories'][0] ?? 'zhyttia') ?>"><?= e(tax_name('news', $n['categories'][0] ?? 'zhyttia')) ?></span><b><?= e($n['title']) ?></b><time datetime="<?= e(substr($n['date'], 0, 10)) ?>"><?= e(ua_date($n['date'])) ?></time></span>
      </a></li>
    <?php endforeach; ?>
  </ul>
  <?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Сторінки новин">
    <?php if ($pageNo > 1): ?><a href="<?= e(url($pageUrl($pageNo - 1))) ?>" rel="prev">← Новіші</a><?php endif; ?>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <?php if ($i === $pageNo): ?><span aria-current="page"><?= $i ?></span><?php else: ?><a href="<?= e(url($pageUrl($i))) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($pageNo < $pages): ?><a href="<?= e(url($pageUrl($pageNo + 1))) ?>" rel="next">Старіші →</a><?php endif; ?>
  </nav>
  <?php endif; ?>
</div>
