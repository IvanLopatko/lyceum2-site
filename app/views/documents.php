<?php
$sections = array_values(array_filter(taxonomies()['documents'], static fn(array $t): bool => documents_in($t['slug']) !== []));
?>
<section class="page-head"><div class="wrap">
  <nav class="crumbs" aria-label="Ви тут"><a href="<?= e(url('/')) ?>">Головна</a><span aria-current="page">Документи</span></nav>
  <h1>Документи</h1>
  <p class="lead">Реєстр документів ліцею за статтею 30 Закону «Про освіту». Біля кожного — дата оприлюднення або стан: документ має з’явитися на сайті протягом 10 робочих днів після затвердження.</p>
  <nav class="chips chips-nav" aria-label="Розділи реєстру"><?php foreach ($sections as $s): ?><a href="#<?= e($s['slug']) ?>"><?= e($s['name']) ?></a><?php endforeach; ?></nav>
</div></section>
<div class="wrap sec-tight registry">
  <?php foreach ($sections as $s): ?>
    <section class="reg-sec" id="<?= e($s['slug']) ?>"><h2><?= e($s['name']) ?></h2><?= component_documents(['section' => $s['slug']]) ?></section>
  <?php endforeach; ?>
  <p class="note">Не знайшли потрібного документа? <a href="<?= e(url('/publichna-informacia/')) ?>">Подайте запит на публічну інформацію</a> — ліцей відповість протягом п’яти робочих днів.</p>
</div>
