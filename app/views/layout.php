<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
/** @var string $body @var string $title */
$siteName = setting('site.name', 'Біляївський ліцей №2');
$fullTitle = ($title ?? '') !== '' ? $title . ' — ' . $siteName : $siteName . ' — ' . setting('site.motto');
$desc = trim(strip_tags((string) ($description ?? '')));
if ($desc === '') {
    $desc = 'Офіційний сайт Біляївського ліцею №2 Біляївської міської ради Одеського району Одеської області.';
}
$ogImage = '/' . ltrim(!empty($image) ? (string) $image : 'uploads/emblema.png', '/');
// Поки на сайті є зразкові дані й увімкнено смугу «Зразок сайту», пошуковикам його не показуємо.
$hideFromSearch = !empty($noindex) || (($status ?? 200) !== 200) || (setting('site.demo_bar', true) && has_demo());
$path = current_path();
$c = setting('contacts', []);
$showDemo = setting('site.demo_bar', true) && has_demo();
?><!doctype html>
<html lang="uk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e(excerpt($desc, 160)) ?>">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e(excerpt($desc, 160)) ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="<?= e(abs_url($ogImage)) ?>">
<meta property="og:url" content="<?= e(abs_url($path)) ?>">
<link rel="canonical" href="<?= e(abs_url($path)) ?>">
<?php if ($hideFromSearch): ?><meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<meta name="theme-color" content="#1C6FE0">
<link rel="icon" href="<?= e(url('/uploads/emblema.png')) ?>">
<link rel="preload" href="<?= e(url('/assets/fonts/onest-cyrillic.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('/assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/icons.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
<script><?= A11Y_BOOT ?></script>
</head>
<body<?= !empty($is_home) ? ' class="is-home"' : '' ?>>
<a class="skip" href="#main">Перейти до вмісту</a>
<div class="stripe" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
<?php if ($showDemo): ?>
<div class="demo-bar" role="note"><div class="wrap"><span>Зразок сайту: частина текстів і цифр показує, як виглядатиме наповнений сайт, і буде замінена на фактичні.</span><button type="button" class="demo-x" aria-label="Сховати повідомлення">×</button></div></div>
<?php endif; ?>

<header class="site-header">
  <div class="utility">
    <div class="wrap utility-in">
      <nav class="utility-links" aria-label="Швидкі посилання">
        <a href="<?= e(url('/novyny/')) ?>">Новини</a>
        <a href="<?= e(url('/navchannia/rozklad/')) ?>">Розклад і дзвінки</a>
        <a href="<?= e(url('/batkam-i-uchniam/harchuvannia/')) ?>">Харчування</a>
        <a href="<?= e(url('/dokumenty/')) ?>">Документи</a>
        <a href="<?= e(url('/kontakty/')) ?>">Контакти</a>
      </nav>
      <form class="search" role="search" action="<?= e(url('/poshuk/')) ?>" method="get">
        <label class="sr" for="q-top">Пошук по сайту</label>
        <input id="q-top" type="search" name="q" placeholder="Пошук по сайту" autocomplete="off">
        <button type="submit" aria-label="Шукати"><span class="ico i-search" aria-hidden="true"></span></button>
      </form>
    </div>
  </div>
  <div class="wrap header-in">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?> — на головну">
      <img src="<?= e(url('/uploads/emblema.png')) ?>" alt="" width="52" height="52">
      <span><b><?= e($siteName) ?></b><small>«<?= e(setting('site.motto')) ?>»</small></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav"><span></span><span class="sr">Меню</span></button>
    <nav id="nav" class="nav" aria-label="Головне меню">
      <ul>
        <?php foreach (nav_sections() as $s):
            $p = page_path($s['slug']); ?>
          <li><a href="<?= e(url($p)) ?>"<?= str_starts_with($path, $p) ? ' aria-current="page"' : '' ?>><?= e($s['title']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <form class="search nav-search" role="search" action="<?= e(url('/poshuk/')) ?>" method="get">
        <label class="sr" for="q-nav">Пошук по сайту</label>
        <input id="q-nav" type="search" name="q" placeholder="Пошук по сайту" autocomplete="off">
        <button type="submit" aria-label="Шукати"><span class="ico i-search" aria-hidden="true"></span></button>
      </form>
    </nav>
  </div>
</header>

<main id="main" tabindex="-1">
<?= $body ?>
</main>

<footer class="site-footer">
  <div class="stripe" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
  <div class="wrap footer-in">
    <div class="footer-brand">
      <img src="<?= e(url('/uploads/emblema.png')) ?>" alt="" width="64" height="64">
      <p><b><?= e($siteName) ?></b><br><?= e(setting('site.full_name')) ?></p>
    </div>
    <div>
      <h2>Зв’язок</h2>
      <ul>
        <li><?= e($c['phone'] ?: 'Телефон уточнюється') ?></li>
        <li><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></li>
        <li><?= e($c['hours']) ?></li>
        <?php if (!empty($c['facebook'])): ?><li><a href="<?= e(safe_url((string) $c['facebook'])) ?>" target="_blank" rel="noopener">Спільнота у Facebook</a></li><?php endif; ?>
      </ul>
    </div>
    <div>
      <h2>Корпуси</h2>
      <ul>
        <?php foreach ($c['buildings'] ?? [] as $b): ?>
          <li><b><?= e($b['name']) ?></b><br><?= e($b['address']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h2>Корисне</h2>
      <ul>
        <li><a href="<?= e(url('/publichna-informacia/')) ?>">Публічна інформація</a></li>
        <li><a href="<?= e(url('/prozorist/shcho-opryliudniuiemo/')) ?>">Що ми оприлюднюємо</a></li>
        <li><a href="<?= e(url('/dostupnist/')) ?>">Доступність</a></li>
        <li><a href="<?= e(url('/karta-sajtu/')) ?>">Карта сайту</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap footer-bottom"><span>© <?= date('Y') ?> <?= e($siteName) ?></span><span>Матеріали з офіційних документів і спільноти ліцею</span></div>
</footer>

<button class="a11y-toggle" type="button" aria-expanded="false" aria-controls="a11y"><span class="ico i-eye" aria-hidden="true"></span><span class="sr">Налаштування доступності</span></button>
<div class="a11y" id="a11y" hidden>
  <h2>Доступність</h2>
  <div class="a11y-row"><span>Розмір тексту</span><div><button type="button" data-a11y="size" data-v="">А</button><button type="button" data-a11y="size" data-v="1">А+</button><button type="button" data-a11y="size" data-v="2">А++</button></div></div>
  <div class="a11y-row"><span>Міжрядковий інтервал</span><div><button type="button" data-a11y="space" data-v="">Звичайний</button><button type="button" data-a11y="space" data-v="1">Просторий</button></div></div>
  <div class="a11y-row"><span>Контраст</span><div><button type="button" data-a11y="contrast" data-v="">Звичайний</button><button type="button" data-a11y="contrast" data-v="1">Високий</button></div></div>
</div>
<button class="to-top" type="button" aria-label="Нагору"><span class="ico i-up" aria-hidden="true"></span></button>
<script src="<?= e(asset('/assets/js/site.js')) ?>" defer></script>
</body>
</html>
