<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/** @var array $item */
$cover = news_cover($item);
$more = array_values(array_filter(news_all(), static fn(array $n): bool => $n['id'] !== $item['id']));
$more = array_slice($more, 0, 3);
?>
<article class="news-item">
  <header class="page-head"><div class="wrap">
    <nav class="crumbs" aria-label="Ви тут"><a href="<?= e(url('/')) ?>">Головна</a><a href="<?= e(url('/novyny/')) ?>">Новини</a><span aria-current="page"><?= e(excerpt($item['title'], 60)) ?></span></nav>
    <p class="tags"><?php foreach ($item['categories'] as $c): ?><a class="tag is-<?= e($c) ?>" href="<?= e(url('/novyny/rubryka/' . $c . '/')) ?>"><?= e(tax_name('news', $c)) ?></a><?php endforeach; ?></p>
    <h1><?= e($item['title']) ?></h1>
    <p class="meta"><time datetime="<?= e(substr($item['date'], 0, 10)) ?>"><?= e(ua_date($item['date'])) ?></time></p>
  </div></header>
  <div class="wrap page-body prose">
    <?php if (!empty($item['draft'])): ?><p class="callout is-sun"><b>Чернетка.</b> Цю новину бачите лише ви, поки ввійшли в адмінку. На сайті її ще немає.</p><?php endif; ?>
    <?php if ($cover): ?><figure class="news-cover"><a href="<?= e(upload_url($cover)) ?>" aria-label="Відкрити фото у повному розмірі"><img src="<?= e(upload_url($cover)) ?>" alt="<?= e(news_alt($item, $cover)) ?>"<?php if ($sz = @getimagesize(ROOT . '/' . $cover)): ?> width="<?= (int) $sz[0] ?>" height="<?= (int) $sz[1] ?>"<?php endif; ?>></a></figure><?php endif; ?>
    <?php foreach ($item['text'] as $p): ?><p><?= $p ?></p><?php endforeach; ?>
    <?php $gal = array_values(array_filter(news_photos($item), static fn(string $i): bool => $i !== $cover)); if ($gal): ?>
      <div class="gallery"><?php foreach ($gal as $k => $g): $alt = news_alt($item, $g, $k + 2); ?><a href="<?= e(upload_url($g)) ?>" aria-label="<?= e($alt . ' — відкрити у повному розмірі') ?>"><img src="<?= e(thumb_url($g)) ?>" alt="<?= e($alt) ?>" loading="lazy" width="720" height="480"></a><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if (!empty($item['source'])): ?><p class="note">Джерело: <a href="<?= e($item['source']) ?>" target="_blank" rel="noopener">спільнота ліцею у Facebook</a>.</p><?php endif; ?>
  </div>
  <?php if ($more): ?>
  <aside class="wrap more-news" aria-labelledby="h-more"><h2 id="h-more">Інші новини</h2><ul class="postlist"><?php foreach ($more as $m): echo news_row($m); endforeach; ?></ul></aside>
  <?php endif; ?>
</article>
