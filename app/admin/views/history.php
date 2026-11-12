<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/**
 * Історія змін: попередні версії сторінок, новин і списків, повернення видаленого.
 * @var string $rel файл, версії якого показуємо ('' — загальний список) @var bool $isAdmin
 */
$lists = ['documents.json' => 'Реєстр документів', 'staff.json' => 'Кадровий склад', 'clubs.json' => 'Гуртки'];
if ($isAdmin) {
    $lists['settings.json'] = 'Налаштування сайту';
}

/** Людська назва файлу: «Сторінка „Контакти“», «Новина „…“». */
$label = static function (string $rel, ?array $data = null) use ($lists): string {
    if (isset($lists[$rel])) {
        return $lists[$rel];
    }
    if ($data === null) {
        $data = store_read($rel, null);
        if (!is_array($data)) {
            $v = store_versions($rel);
            $data = $v ? store_version($rel, $v[0]['v']) : null;
        }
    }
    $t = is_array($data) ? (string) ($data['title'] ?? '') : '';
    return (str_starts_with($rel, 'news/') ? 'Новина' : 'Сторінка') . ' «' . excerpt($t !== '' ? $t : basename($rel, '.json'), 70) . '»';
};

/** Що всередині версії — одним рядком. */
$summary = static function (string $rel, mixed $d): string {
    if (!is_array($d)) {
        return 'файл пошкоджено';
    }
    if (str_starts_with($rel, 'pages/')) {
        return 'блоків: ' . count($d['blocks'] ?? []) . (!empty($d['template']) ? ' · зразок' : '');
    }
    if (str_starts_with($rel, 'news/')) {
        return 'абзаців: ' . count($d['text'] ?? []) . ' · фото: ' . count(news_photos($d)) . (!empty($d['draft']) ? ' · чернетка' : '');
    }
    return $rel === 'settings.json' ? 'налаштування' : 'записів: ' . count($d);
};
$when = static fn(int $t): string => ua_date(date('Y-m-d', $t)) . ', ' . date('H:i', $t);

if ($rel !== ''):
    $versions = store_versions($rel);
    $current = store_read($rel, null);
    $exists = is_array($current);
?>
<header class="head"><div><p class="crumb"><a href="<?= e(url('/admin/istoriia/')) ?>">Історія змін</a></p><h1><?= e($label($rel)) ?></h1></div></header>
<?php if ($exists): ?>
  <p class="muted">Зараз на сайті: <?= e($summary($rel, $current)) ?>. Нижче — попередні версії, найновіша зверху. Відновлення нічого не губить: поточна версія теж потрапить у цей список.</p>
<?php else: ?>
  <p class="flash is-warn">Цей запис видалено. Щоб повернути його на сайт, відновіть останню версію.</p>
<?php endif; ?>
<?php if (!$versions): ?><p class="muted">Попередніх версій немає.</p><?php endif; ?>
<ul class="rows">
  <?php foreach ($versions as $v): $d = store_version($rel, $v['v']); ?>
    <li><div class="row-static"><span><?= e($when($v['time'])) ?> <small class="muted"><?= e($summary($rel, $d)) ?><?php if (is_array($d) && isset($d['title'])): ?> · «<?= e(excerpt((string) $d['title'], 60)) ?>»<?php endif; ?></small></span>
      <form method="post" data-confirm="Відновити версію від <?= e($when($v['time'])) ?>? Поточна версія збережеться в історії."><?= csrf_field() ?><input type="hidden" name="do" value="restore"><input type="hidden" name="f" value="<?= e($rel) ?>"><input type="hidden" name="v" value="<?= e($v['v']) ?>"><button class="btn btn-ghost" type="submit">Відновити</button></form></div></li>
  <?php endforeach; ?>
</ul>
<?php
    return;
endif;

$index = store_history_index();
$deleted = [];
$recent = [];
foreach ($index as $f => $n) {
    if (!preg_match('~^(pages/[a-z0-9-]+|news/\d+)\.json$~', $f)) {
        continue;
    }
    $v = store_versions($f);
    if (!$v) {
        continue;
    }
    if (!is_file(store_path($f))) {
        $deleted[$f] = $v[0]['time'];
    } else {
        $recent[$f] = $v[0]['time'];
    }
}
arsort($deleted);
arsort($recent);
$recent = array_slice($recent, 0, 30, true);
$link = static fn(string $f): string => url('/admin/istoriia/?f=' . rawurlencode($f));
?>
<header class="head"><h1>Історія змін</h1></header>
<p class="muted">Кожне збереження лишає копію попередньої версії (останні 30 для кожного запису). Тут можна повернути видалене або відкотити невдалу правку. Видалені файли (фото, PDF) зберігаються 30 днів.</p>

<section class="card">
  <h2>Видалене</h2>
  <?php if (!$deleted): ?><p class="muted">Нічого не видалено.</p><?php else: ?>
  <ul class="rows">
    <?php foreach ($deleted as $f => $t): ?>
      <li><a href="<?= e($link($f)) ?>"><span><?= e($label($f)) ?></span><small>видалено <?= e($when($t)) ?></small></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Списки</h2>
  <p class="muted">Документи, кадри й гуртки зберігаються списком: відновлення повертає весь список до стану на обрану мить (так можна повернути видалений документ чи прибрані зразки).</p>
  <ul class="rows">
    <?php foreach ($lists as $f => $name): $n = $index[$f] ?? 0; ?>
      <li><?php if ($n): ?><a href="<?= e($link($f)) ?>"><span><?= e($name) ?></span><small>версій: <?= $n ?></small></a><?php else: ?><div class="row-static"><span><?= e($name) ?></span><small class="muted">змін ще не було</small></div><?php endif; ?></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="card">
  <h2>Нещодавно змінені сторінки й новини</h2>
  <?php if (!$recent): ?><p class="muted">Змін ще не було.</p><?php else: ?>
  <ul class="rows">
    <?php foreach ($recent as $f => $t): ?>
      <li><a href="<?= e($link($f)) ?>"><span><?= e($label($f)) ?></span><small>попередня версія від <?= e($when($t)) ?> · версій: <?= (int) $index[$f] ?></small></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
