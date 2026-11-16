<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<section class="page-head"><div class="wrap">
  <h1><?= e(site_text('notfound_title')) ?></h1>
  <p class="lead"><?= e(site_text('notfound_lead')) ?></p>
  <div class="actions"><a class="btn" href="<?= e(url('/')) ?>">На головну</a><a class="btn btn-ghost" href="<?= e(url('/karta-sajtu/')) ?>">Карта сайту</a></div>
</div></section>
