<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$groups = taxonomies()['staff'];
?>
<section class="page-head"><div class="wrap">
  <nav class="crumbs" aria-label="Ви тут"><a href="<?= e(url('/')) ?>">Головна</a><a href="<?= e(url('/pro-licej/')) ?>">Про ліцей</a><span aria-current="page">Кадровий склад</span></nav>
  <h1>Кадровий склад</h1>
  <p class="lead">Адміністрація, педагоги, асистенти вчителя й фахівці супроводу Біляївського ліцею №2.</p>
  <nav class="chips chips-nav" aria-label="Групи"><?php foreach ($groups as $g): if (!array_filter(staff(), static fn($p) => $p['group'] === $g['slug'])) continue; ?><a href="#<?= e($g['slug']) ?>"><?= e($g['name']) ?></a><?php endforeach; ?></nav>
</div></section>
<div class="wrap sec-tight">
  <?php foreach ($groups as $g):
      $html = component_team(['group' => $g['slug']]);
      if (str_contains($html, 'class="empty"')) continue; ?>
    <section class="reg-sec" id="<?= e($g['slug']) ?>"><h2><?= e($g['name']) ?></h2><?= $html ?></section>
  <?php endforeach; ?>
</div>
