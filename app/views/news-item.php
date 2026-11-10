<?php
/** @var array $item */
$imgs = $item['images'] ?? [];
$cover = news_cover($item);
$more = array_values(array_filter(news_all(), static fn(array $n): bool => $n['id'] !== $item['id']));
$more = array_slice($more, 0, 3);
?>
<article class="news-item">
  <header class="page-head"><div class="wrap">
    <nav class="crumbs" aria-label="Ви тут"><a href="<?= e(url('/')) ?>">Головна</a><a href="<?= e(url('/novyny/')) ?>">Новини</a><span aria-current="page"><?= e(excerpt($item['title'], 60)) ?></span></nav>
    <p class="tags"><?php foreach ($item['categories'] as $c): ?><a class="tag" href="<?= e(url('/novyny/rubryka/' . $c . '/')) ?>"><?= e(tax_name('news', $c)) ?></a><?php endforeach; ?></p>
    <h1><?= e($item['title']) ?></h1>
    <p class="meta"><time datetime="<?= e(substr($item['date'], 0, 10)) ?>"><?= e(ua_date($item['date'])) ?></time></p>
  </div></header>
  <div class="wrap page-body prose">
    <?php if ($cover): ?><figure class="news-cover"><img src="<?= e(upload_url($cover)) ?>" alt="<?= e('Фото до допису «' . $item['title'] . '»') ?>"></figure><?php endif; ?>
    <?php foreach ($item['text'] as $p): ?><p><?= $p ?></p><?php endforeach; ?>
    <?php $gal = array_values(array_filter($imgs, static fn(string $i): bool => $i !== $cover)); if ($gal): ?>
      <div class="gallery"><?php foreach ($gal as $g): ?><a href="<?= e(upload_url($g)) ?>"><img src="<?= e(upload_url($g)) ?>" alt="" loading="lazy"></a><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if (!empty($item['source'])): ?><p class="note">Джерело: <a href="<?= e($item['source']) ?>" target="_blank" rel="noopener">спільнота ліцею у Facebook</a>.</p><?php endif; ?>
  </div>
  <?php if ($more): ?>
  <aside class="wrap more-news" aria-labelledby="h-more"><h2 id="h-more">Інші новини</h2><ul class="postlist"><?php foreach ($more as $m): echo news_row($m); endforeach; ?></ul></aside>
  <?php endif; ?>
</article>
