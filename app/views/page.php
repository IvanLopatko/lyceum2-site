<?php
/** @var array $page */
$crumbs = [];
$p = $page;
$guard = 0;
while (($p['parent'] ?? '') !== '' && $guard++ < 5) {
    $p = page($p['parent']);
    if (!$p) {
        break;
    }
    array_unshift($crumbs, $p);
}
?>
<article class="page">
  <header class="page-head">
    <div class="wrap">
      <nav class="crumbs" aria-label="Ви тут">
        <a href="<?= e(url('/')) ?>">Головна</a>
        <?php foreach ($crumbs as $cr): ?><a href="<?= e(url(page_path($cr['slug']))) ?>"><?= e($cr['title']) ?></a><?php endforeach; ?>
        <span aria-current="page"><?= e($page['title']) ?></span>
      </nav>
      <h1><?= e($page['title']) ?></h1>
      <?php if (!empty($page['lead'])): ?><p class="lead"><?= e($page['lead']) ?></p><?php endif; ?>
      <?php if (!empty($page['template'])): ?><p class="tpl-badge" title="Текст і цифри на цій сторінці — приклад; їх буде замінено на фактичні."><span></span>Зразок наповнення</p><?php endif; ?>
    </div>
  </header>
  <div class="wrap page-body prose">
    <?= render_blocks($page['blocks'] ?? [], ['page' => $page]) ?>
  </div>
  <div class="wrap page-foot">
    <?= component_related($page) ?>
    <p class="updated">Оновлено: <?= e(ua_date($page['updated'] ?? today())) ?></p>
  </div>
</article>
