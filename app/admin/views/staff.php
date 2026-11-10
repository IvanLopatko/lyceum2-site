<header class="head"><h1>Кадри</h1><a class="btn" href="<?= e(url('/admin/kadry/novyi/')) ?>">+ Людина</a></header>
<?php foreach (taxonomies()['staff'] as $g): $people = array_filter(staff(), static fn($p) => $p['group'] === $g['slug']); if (!$people) continue; ?>
  <h2 class="sub"><?= e($g['name']) ?> <small class="muted"><?= count($people) ?></small></h2>
  <ul class="rows">
    <?php foreach ($people as $p): ?>
      <li><a href="<?= e(url('/admin/kadry/' . $p['id'] . '/')) ?>"><?php if ($p['photo']): ?><img src="<?= e(upload_url($p['photo'])) ?>" alt="" width="42" height="42" class="round" loading="lazy"><?php else: ?><span class="thumb round"></span><?php endif; ?><span><?= e($p['name']) ?></span><small><?= e($p['position']) ?></small></a></li>
    <?php endforeach; ?>
  </ul>
<?php endforeach; ?>
