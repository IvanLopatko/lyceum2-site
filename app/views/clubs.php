<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<section class="page-head"><div class="wrap">
  <nav class="crumbs" aria-label="Ви тут"><a href="<?= e(url('/')) ?>">Головна</a><a href="<?= e(url('/zhyttia-litseiu/')) ?>">Життя ліцею</a><span aria-current="page">Гуртки і клуби</span></nav>
  <h1>Гуртки і клуби</h1>
  <p class="lead"><?= e(site_text('clubs_lead')) ?></p>
</div></section>
<div class="wrap sec-tight"><?= component_club_list() ?></div>
