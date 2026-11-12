<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<header class="head"><h1>Гуртки</h1><a class="btn" href="<?= e(url('/admin/gurtky/novyi/')) ?>">+ Гурток</a></header>
<ul class="rows"><?php foreach (clubs() as $c): ?><li><a href="<?= e(url('/admin/gurtky/' . $c['id'] . '/')) ?>"><span><?= e($c['title']) ?><?= !empty($c['demo']) ? ' <em class="pill">зразок</em>' : '' ?></span><small><?= e(trim($c['day'] . ' ' . $c['time'])) ?></small></a></li><?php endforeach; ?></ul>
