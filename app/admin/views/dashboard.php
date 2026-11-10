<?php
$att = docs_attention();
$fin = finance_deadline();
$tpl = array_filter(pages(), static fn($p) => !empty($p['template']));
$demoDocs = count(array_filter(documents(), static fn($d) => !empty($d['demo'])));
$demoClubs = count(array_filter(clubs(), static fn($c) => !empty($c['demo'])));
$news = news_all();
?>
<header class="head"><h1>Огляд</h1><a class="btn" href="<?= e(url('/admin/novyny/nova/')) ?>">+ Новина</a></header>
<div class="grid-3">
  <a class="stat" href="<?= e(url('/admin/storinky/')) ?>"><b><?= count(pages()) ?></b><span>сторінок</span><small><?= count($tpl) ?> ще зі зразковим текстом</small></a>
  <a class="stat" href="<?= e(url('/admin/novyny/')) ?>"><b><?= count($news) ?></b><span>новин</span><small>остання — <?= $news ? e(ua_date($news[0]['date'])) : '—' ?></small></a>
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

<?php if ($demoDocs + $demoClubs): ?>
<section class="card is-warn">
  <h2>Прибрати зразкові записи</h2>
  <p>Зразкових документів: <?= $demoDocs ?>, гуртків: <?= $demoClubs ?>. Перед відкриттям сайту для всіх їх треба прибрати — вони виглядають як справжні.</p>
  <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="purge">
    <label class="check"><input type="checkbox" name="confirm" value="yes"> Розумію: зразкові записи зникнуть із сайту (копії лишаться в історії змін)</label>
    <button class="btn btn-danger" type="submit">Прибрати зразки</button>
  </form>
</section>
<?php endif; ?>
