<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$sec = (string) ($_GET['s'] ?? '');
$list = documents_in($sec, true);
?>
<header class="head"><h1>Документи</h1><a class="btn" href="<?= e(url('/admin/dokumenty/novyi/')) ?>">+ Документ</a></header>
<p class="muted">Реєстр за статтею 30. Поставте дату затвердження — сайт сам порахує 10 робочих днів і покаже стан.</p>
<form class="filters" method="get"><select name="s"><option value="">Усі розділи</option><?php foreach (taxonomies()['documents'] as $t): ?><option value="<?= e($t['slug']) ?>"<?= $sec === $t['slug'] ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select><noscript><button class="btn btn-ghost">Показати</button></noscript></form>
<ul class="rows">
  <?php foreach ($list as $d): $st = doc_state($d); ?>
    <li><a href="<?= e(url('/admin/dokumenty/' . $d['id'] . '/')) ?>"><span><?= e($d['title']) ?><?= !empty($d['demo']) ? ' <em class="pill">зразок</em>' : '' ?><?= $d['file'] ? '' : ' <em class="pill is-late">без файлу</em>' ?></span><span class="pill is-<?= e($st['class']) ?>"><?= e($st['label']) ?></span><small><?= e(implode(', ', array_map(static fn($s) => tax_name('documents', $s), $d['sections']))) ?></small></a></li>
  <?php endforeach; ?>
</ul>
