<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$all = pages();
$top = array_filter($all, static fn($p) => ($p['parent'] ?? '') === '' && !in_array($p['slug'], SPECIAL_PAGES, true));
uasort($top, static fn($a, $b) => [$a['order'] ?? 0, $a['title']] <=> [$b['order'] ?? 0, $b['title']]);
$row = static function (array $p, bool $child = false): string {
    return '<li' . ($child ? ' class="is-child"' : '') . '><a href="' . e(url('/admin/storinky/' . $p['slug'] . '/')) . '"><span>' . e($p['title']) . '</span>'
        . (!empty($p['template']) ? '<em class="pill is-due">зразок</em>' : '<em class="pill is-ok">фактичний</em>')
        . '<small>' . e(page_path($p['slug'])) . '</small></a></li>';
};
?>
<header class="head"><h1>Сторінки</h1><a class="btn" href="<?= e(url('/admin/storinky/nova/')) ?>">+ Сторінка</a></header>
<p class="muted">Новини, документи, кадри й гуртки редагуються в окремих розділах — сторінки підтягують їх автоматично.</p>
<ul class="rows tree">
  <?php foreach ($top as $p): ?>
    <?= $row($p) ?>
    <?php foreach (page_children($p['slug']) as $k): ?><?= $row($k, true) ?><?php endforeach; ?>
  <?php endforeach; ?>
</ul>
