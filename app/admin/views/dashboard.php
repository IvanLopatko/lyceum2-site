<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$att = docs_attention();
$fin = finance_deadline();
$tpl = array_filter(pages(), static fn($p) => !empty($p['template']));
$demoDocs = count(array_filter(documents(), static fn($d) => !empty($d['demo'])));
$demoClubs = count(array_filter(clubs(), static fn($c) => !empty($c['demo'])));
$news = news_all(true);
$drafts = count(array_filter($news, static fn($n) => !empty($n['draft'])));
$hidden = setting('site.demo_bar', true) && has_demo();
// попередній вдалий вхід цього користувача (останній запис — нинішній сеанс)
$mine = array_values(array_filter(login_history(), static fn($r) => !empty($r['ok']) && ($r['login'] ?? '') === ($user['login'] ?? '')));
$prev = count($mine) >= 2 ? $mine[count($mine) - 2] : null;
?>
<?php if (!is_https() && !is_local_host()): ?>
<div class="flash is-err"><b>Увага: сайт відкрито без шифрування (http://).</b> Паролі й усе, що ви тут вводите, дорогою можуть перехопити. Відкривайте адмінку лише через https://. Якщо так сайт не відкривається — попросіть підтримку хостингу ввімкнути SSL-сертифікат (зазвичай це безкоштовно).</div>
<?php endif; ?>
<?php if (is_file(CONFIG . '/debug')): ?>
<div class="flash is-warn"><b>Увімкнено режим налагодження.</b> Відвідувачі можуть бачити технічні повідомлення про помилки. Коли він не потрібен, видаліть файл <code>config/debug</code> на сервері.</div>
<?php endif; ?>
<div class="flash is-err" id="exposure" hidden data-check="<?= e((string) json_encode([url('/config/README.md'), url('/content/settings.json'), url('/.git/HEAD')])) ?>"><b>Увага: службові теки сайту відкриті з інтернету.</b> Сервер не виконує правила з файлу <code>.htaccess</code>. Зверніться до підтримки хостингу: теки <code>app</code>, <code>content</code>, <code>config</code>, <code>tools</code> мають бути закриті. Приклад налаштування для nginx є у файлі README.</div>
<header class="head"><h1>Огляд</h1><a class="btn" href="<?= e(url('/admin/novyny/nova/')) ?>">+ Новина</a></header>
<?php if ($prev): ?><p class="muted small">Ваш попередній вхід: <?= e(ua_date(date('Y-m-d', (int) $prev['t'])) . ', ' . date('H:i', (int) $prev['t'])) ?>, з адреси <?= e((string) ($prev['ip'] ?? '')) ?>. Якщо це були не ви — <a href="<?= e(url('/admin/oblikovyi-zapys/')) ?>">змініть пароль</a>.</p><?php endif; ?>
<div class="grid-3">
  <a class="stat" href="<?= e(url('/admin/storinky/')) ?>"><b><?= count(pages()) ?></b><span>сторінок</span><small><?= count($tpl) ?> ще зі зразковим текстом</small></a>
  <a class="stat" href="<?= e(url('/admin/novyny/')) ?>"><b><?= count($news) ?></b><span>новин</span><small>остання — <?= $news ? e(ua_date($news[0]['date'])) : '—' ?><?= $drafts ? ' · чернеток: ' . $drafts : '' ?></small></a>
  <a class="stat" href="<?= e(url('/admin/dokumenty/')) ?>"><b><?= count(documents()) ?></b><span>документів</span><small><?= $demoDocs ?> зразкових</small></a>
</div>

<section class="card">
  <h2>Строки оприлюднення</h2>
  <p class="muted">Документ має з’явитися на сайті протягом 10 робочих днів після затвердження (ч. 4 ст. 30 Закону «Про освіту»).</p>
  <?php if (!$att): ?><p class="ok-line">Усі документи оприлюднено вчасно.</p><?php else: ?>
  <ul class="rows">
    <?php foreach ($att as $a): ?>
      <li><a href="<?= e(url('/admin/dokumenty/' . $a['doc']['id'] . '/')) ?>"><span><?= e($a['doc']['title']) ?><?= !empty($a['doc']['demo']) ? ' <em class="pill">зразок</em>' : '' ?></span><span class="pill is-<?= e($a['state']['class']) ?>"><?= e($a['state']['label']) ?></span></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <p class="muted small">Окремо від сайту: звіт про використання бюджетних коштів за <?= e($fin['quarter']) ?> подається на портал spending.gov.ua до <?= e($fin['deadline']) ?> (ст. 3 Закону «Про відкритість використання публічних коштів»).</p>
</section>

<?php if ($tpl): ?>
<section class="card">
  <h2>Сторінки зі зразковим текстом</h2>
  <p class="muted">Ці сторінки показують, як виглядатиме наповнений сайт. Замініть текст на фактичний і зніміть позначку «Зразок».</p>
  <ul class="chips"><?php foreach ($tpl as $p): ?><li><a href="<?= e(url('/admin/storinky/' . $p['slug'] . '/')) ?>"><?= e($p['title']) ?></a></li><?php endforeach; ?></ul>
</section>
<?php endif; ?>

<section class="card">
  <h2>Пошуковики</h2>
  <?php if ($hidden): ?><p>Зараз сайт <b>закритий від Google та інших пошуковиків</b>, бо на ньому ще є зразкові тексти чи записи і ввімкнено смугу «Зразок сайту». Коли все зразкове буде замінено або прибрано, сайт відкриється сам.</p>
  <?php else: ?><p class="ok-line">Сайт відкритий для пошуковиків. Карта сайту: <a href="<?= e(url('/sitemap.xml')) ?>" target="_blank" rel="noopener">sitemap.xml</a>.</p><?php endif; ?>
</section>

<?php if (($demoDocs + $demoClubs) && !empty($isAdmin)): ?>
<section class="card is-warn">
  <h2>Прибрати зразкові записи</h2>
  <p>Зразкових документів: <?= $demoDocs ?>, гуртків: <?= $demoClubs ?>. Перед відкриттям сайту для всіх їх треба прибрати — вони виглядають як справжні.</p>
  <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="purge">
    <label class="check"><input type="checkbox" name="confirm" value="yes"> Розумію: зразкові записи зникнуть із сайту (повернути їх можна буде в розділі «Історія змін»)</label>
    <button class="btn btn-danger" type="submit">Прибрати зразки</button>
  </form>
</section>
<?php endif; ?>
