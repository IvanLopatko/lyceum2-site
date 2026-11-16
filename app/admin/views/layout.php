<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/** @var string $body */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$here = $GLOBALS['section'] ?? '';
$isAdminUser = is_admin_user($user ?? null);
$isOwnerUser = is_owner($user ?? null);
// групи меню: назва групи => розділи; для редактора лишаються тільки його розділи
$menu = [
    '' => ['' => ['Огляд', 'home']],
    'Вміст' => ['holovna' => ['Головна сторінка', 'sun'], 'storinky' => ['Сторінки', 'doc'], 'novyny' => ['Новини', 'chat'], 'dokumenty' => ['Документи', 'scale'],
        'kadry' => ['Кадри', 'users'], 'gurtky' => ['Гуртки', 'star']],
    'Сайт' => ['teksty' => ['Тексти й меню', 'book'], 'rubryky' => ['Рубрики й розділи', 'flag'], 'plan' => ['План профільної школи', 'compass'], 'nalashtuvannia' => ['Налаштування', 'key']],
    'Безпека' => ['istoriia' => ['Історія змін', 'clock'], 'zhurnal' => ['Журнал дій', 'eye'], 'kopii' => ['Резервні копії', 'shield'],
        'oblikovyi-zapys' => [$isOwnerUser ? 'Доступи й пароль' : 'Мій пароль', 'lock']],
];
if (!$isAdminUser) {
    foreach (['holovna', 'teksty', 'rubryky', 'plan', 'nalashtuvannia', 'zhurnal', 'kopii'] as $k) {
        foreach ($menu as $g => $items) {
            unset($menu[$g][$k]);
        }
    }
}
?><!doctype html>
<html lang="uk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Адмінка — <?= e(setting('site.name')) ?></title>
<link rel="icon" href="<?= e(asset('/uploads/emblema.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/icons.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin.css')) ?>">
</head>
<body class="<?= !empty($bare) ? 'is-bare' : '' ?>" data-base="<?= e(BASE) ?>">
<?php if (empty($bare)): ?>
<aside class="side">
  <a class="side-brand" href="<?= e(url('/admin/')) ?>"><img src="<?= e(asset('/uploads/emblema.png')) ?>" alt="" width="40" height="40"><span><b>Адмінка</b><small><?= e(setting('site.name')) ?></small></span></a>
  <nav aria-label="Розділи адмінки">
    <?php foreach ($menu as $group => $items): if (!$items) continue; ?>
      <?php if ($group !== ''): ?><p class="side-h"><?= e($group) ?></p><?php endif; ?>
      <?php foreach ($items as $k => [$label, $ic]): ?>
        <a href="<?= e(url('/admin/' . ($k ? $k . '/' : ''))) ?>"<?= $here === $k ? ' aria-current="page"' : '' ?>><span class="ico i-<?= $ic ?>" aria-hidden="true"></span><?= e($label) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Відкрити сайт ↗</a>
    <span><?= e($user['name'] ?? '') ?><br><small><?= e(role_label((string) ($user['role'] ?? 'editor'))) ?></small></span>
    <a href="<?= e(url('/admin/vyhid/')) ?>">Вийти</a>
  </div>
</aside>
<?php endif; ?>
<main class="main">
  <?php if ($flash): ?><div class="flash is-<?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
  <?= $body ?>
</main>
<script src="<?= e(asset('/assets/js/admin.js')) ?>" defer></script>
</body>
</html>
