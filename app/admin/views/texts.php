<?php if (!defined('ROOT')) { http_response_code(404); exit; } ?>
<?php
$util = array_pad(site_links('utility'), count(site_links('utility')) + 2, ['label' => '', 'url' => '']);
$foot = array_pad(site_links('footer'), count(site_links('footer')) + 2, ['label' => '', 'url' => '']);
$alarm = array_pad(alarm_rows(), max(2, count(alarm_rows())), ['name' => '', 'home' => '', 'ctrl' => '', 'text_home' => '', 'text_ctrl' => '']);
$map = map_point();
?>
<header class="head"><h1>Тексти й меню</h1></header>
<p class="muted">Вступні тексти розділів, посилання в шапці та підвалі, порядок дій під час тривоги і точка на карті. Головне меню сайту складається саме: у ньому розділи верхнього рівня, які мають підсторінки (їх порядок задається в «Сторінках»).</p>
<form method="post" class="edit">
  <?= csrf_field() ?>
  <h2 class="sub">Тексти розділів</h2>
  <div class="fields">
    <?php foreach (TEXT_LABELS as $k => $label): ?>
      <label class="wide"><?= e($label) ?><textarea name="text_<?= $k ?>" rows="2"><?= e(site_text($k)) ?></textarea></label>
    <?php endforeach; ?>
  </div>

  <h2 class="sub">Верхній рядок сайту</h2>
  <p class="muted small">Швидкі посилання над шапкою. Щоб прибрати посилання, зітріть його напис.</p>
  <div class="table-edit">
    <div class="tr th"><span>Напис</span><span>Куди веде (наприклад, /novyny/ або https://…)</span></div>
    <?php foreach ($util as $l): ?><div class="tr"><input name="u_label[]" value="<?= e($l['label']) ?>"><input name="u_url[]" value="<?= e($l['url']) ?>"></div><?php endforeach; ?>
  </div>

  <h2 class="sub">Підвал: колонка «Корисне»</h2>
  <div class="table-edit">
    <div class="tr th"><span>Напис</span><span>Куди веде</span></div>
    <?php foreach ($foot as $l): ?><div class="tr"><input name="f_label[]" value="<?= e($l['label']) ?>"><input name="f_url[]" value="<?= e($l['url']) ?>"></div><?php endforeach; ?>
  </div>

  <h2 class="sub">Під час повітряної тривоги</h2>
  <p class="muted small">Блок «Тривога: дві зміни» на сторінках «Розклад і дзвінки» та «Безпека і укриття». Змінюйте лише разом із наказом директора.</p>
  <?php foreach ($alarm as $r): ?>
    <div class="fields is-group">
      <label>Зміна<input name="al_name[]" value="<?= e($r['name']) ?>"></label>
      <label>Час, до якого діти лишаються вдома<input name="al_home[]" value="<?= e($r['home']) ?>"></label>
      <label>Контрольний час<input name="al_ctrl[]" value="<?= e($r['ctrl']) ?>"></label>
      <label class="wide">Пояснення до першого часу<textarea name="al_text_home[]" rows="2"><?= e($r['text_home']) ?></textarea></label>
      <label class="wide">Пояснення до контрольного часу<textarea name="al_text_ctrl[]" rows="2"><?= e($r['text_ctrl']) ?></textarea></label>
    </div>
  <?php endforeach; ?>

  <h2 class="sub">Карта</h2>
  <p class="muted small">Точка на карті сторінки «Контакти». Координати можна взяти на openstreetmap.org: клацніть правою кнопкою на будівлі → «Показати адресу».</p>
  <div class="fields">
    <label>Широта<input name="map_lat" value="<?= e(number_format($map['lat'], 6, '.', '')) ?>" inputmode="decimal"></label>
    <label>Довгота<input name="map_lon" value="<?= e(number_format($map['lon'], 6, '.', '')) ?>" inputmode="decimal"></label>
    <label class="wide">Підпис карти для програм читання з екрана<input name="map_title" value="<?= e($map['title']) ?>"></label>
  </div>
  <div class="savebar"><button class="btn" type="submit">Зберегти</button></div>
</form>
