<?php
$n = setting('numbers', []);
$news = news_all();
$lead = $news[0] ?? null;
$rest = array_slice($news, 1, 4);
$plan = profile_plan();
$nowItems = [];
foreach ($plan['months'] as $m) {
    if ($m['month'] === date('Y-m')) {
        $nowItems = $m['items'];
    }
}
$days = days_to($plan['start'] ?? '2027-09-01');
$pupils = (int) preg_replace('~\D~', '', (string) ($n['pupils'] ?? '0'));
$classes = (int) preg_replace('~\D~', '', (string) ($n['classes'] ?? '0'));
$tiles = [
    ['rozklad', '/navchannia/rozklad/', 'Розклад уроків і дзвінків', 'Уроки по класах, дзвінки, структура навчального року', 'wide'],
    ['vstup', '/batkam-i-uchniam/vstup/', 'Вступ до ліцею', 'Документи, правила прийому, територія', 'sun'],
    ['harchuvannia', '/batkam-i-uchniam/harchuvannia/', 'Харчування', 'Меню, режим, пільги', 'sail'],
    ['bezpeka', '/pidtrymka-i-bezpeka/bezpeka-i-ukryttia/', 'Безпека і укриття', 'Дії під час тривоги, дві зміни', 'blue'],
    ['psyholog', '/pidtrymka-i-bezpeka/psyhologichna-sluzhba/', 'Психологічна служба', 'Підтримка учнів і батьків', 'green'],
    ['inkliuziia', '/pidtrymka-i-bezpeka/inkliuzyvne-navchannia/', 'Інклюзивне навчання', 'Команда супроводу, асистенти', 'green'],
    ['profilna', '/navchannia/profilna-shkola/', 'Профільна школа', 'Академічний ліцей з 2027 року', 'sun'],
    ['dokumenty', '/dokumenty/', 'Документи', 'Реєстр за статтею 30', 'sail'],
    ['gurtky', '/gurtky/', 'Гуртки і клуби', 'Пласт, спорт, творчість', 'blue'],
];
?>
<section class="hero">
  <div class="wrap hero-in">
    <div class="hero-txt">
      <p class="eyebrow">Біляївка · Одеський район · з <?= e(setting('site.founded', '1897')) ?> року</p>
      <h1>Школа, яку впізнають за <span class="grad-text">вітрилами</span></h1>
      <p class="lead">Комунальний ліцей з інклюзивним навчанням і трьома корпусами. Тут вчаться думати, дружити й не боятися ставити питання.</p>
      <div class="actions">
        <a class="btn" href="<?= e(url('/batkam-i-uchniam/vstup/')) ?>">Вступ до ліцею</a>
        <a class="btn btn-ghost" href="<?= e(url('/navchannia/rozklad/')) ?>">Розклад і дзвінки</a>
      </div>
      <ul class="chips" aria-label="Коротко про ліцей">
        <?php if ($pupils): ?><li><?= $pupils ?> <?= plural($pupils, 'учень', 'учні', 'учнів') ?></li><?php endif; ?>
        <?php if ($classes): ?><li><?= $classes ?> <?= plural($classes, 'клас', 'класи', 'класів') ?></li><?php endif; ?>
        <li>3 корпуси</li>
        <li>інклюзивне навчання</li>
      </ul>
    </div>
    <div class="hero-art" aria-hidden="true">
      <span class="hero-sun"></span>
      <img src="<?= e(url('/uploads/emblema.png')) ?>" alt="" width="240" height="240">
      <span class="hero-motto">«<?= e(setting('site.motto')) ?>»</span>
      <span class="hero-wave"></span>
    </div>
  </div>
  <div class="wrap"><?= component_today() ?></div>
</section>

<section class="sec" aria-labelledby="h-quick">
  <div class="wrap">
    <div class="sec-head"><h2 id="h-quick">Швидко знайти</h2></div>
    <ul class="bento">
      <?php foreach ($tiles as $t): ?>
        <li class="is-<?= e($t[4]) ?>"><a href="<?= e(url($t[1])) ?>"><img class="bento-art" src="<?= e(url('/assets/img/tiles/' . $t[0] . '.svg')) ?>" alt="" width="200" height="150"><b><?= e($t[2]) ?></b><span><?= e($t[3]) ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="sec sec-soft" aria-labelledby="h-news">
  <div class="wrap">
    <div class="sec-head"><h2 id="h-news">Новини ліцею</h2><a class="more" href="<?= e(url('/novyny/')) ?>">Усі новини</a></div>
    <?php if ($lead): ?>
    <div class="news-home">
      <a class="news-lead" href="<?= e(url(news_url($lead))) ?>">
        <?php if ($cv = news_cover($lead)): ?><img src="<?= e(upload_url($cv)) ?>" alt="" loading="lazy"><?php else: ?><span class="news-ph" aria-hidden="true"></span><?php endif; ?>
        <span class="news-body"><span class="tag is-<?= e($lead['categories'][0] ?? 'zhyttia') ?>"><?= e(tax_name('news', $lead['categories'][0] ?? 'zhyttia')) ?></span><b><?= e($lead['title']) ?></b><span class="news-ex"><?= e(excerpt(implode(' ', $lead['text']), 180)) ?></span><time datetime="<?= e(substr($lead['date'], 0, 10)) ?>"><?= e(ua_date($lead['date'])) ?></time></span>
      </a>
      <ul class="news-list">
        <?php foreach ($rest as $r): ?>
          <li><a href="<?= e(url(news_url($r))) ?>"><span class="tag is-<?= e($r['categories'][0] ?? 'zhyttia') ?>"><?= e(tax_name('news', $r['categories'][0] ?? 'zhyttia')) ?></span><b><?= e($r['title']) ?></b><time datetime="<?= e(substr($r['date'], 0, 10)) ?>"><?= e(ua_date($r['date'])) ?></time></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="sec" aria-labelledby="h-promo">
  <div class="wrap">
    <div class="promo">
      <div class="promo-txt">
        <p class="eyebrow">Старша профільна школа</p>
        <h2 id="h-promo">Академічний ліцей з 1 вересня 2027 року</h2>
        <p>Що зміниться для нинішніх дев’ятикласників, як обрати профіль і де поставити запитання.</p>
        <?php if ($nowItems): ?><p class="promo-now"><span><?= e(UA_MONTHS[(int) date('n')]) ?> за планом</span><?= e($nowItems[0]['what']) ?></p><?php endif; ?>
        <a class="btn btn-light" href="<?= e(url('/navchannia/profilna-shkola/')) ?>">Усе про профільну школу</a>
      </div>
      <div class="promo-cd" aria-hidden="true"><b data-count><?= $days ?></b><span><?= plural($days, 'день', 'дні', 'днів') ?> до старту</span></div>
    </div>
  </div>
</section>

<section class="sec" aria-labelledby="h-docs">
  <div class="wrap two-col">
    <div class="sec-head is-col">
      <h2 id="h-docs">Прозорість і документи</h2>
      <p>Статут, освітні програми, звіти й кошторис — у відкритому доступі за статтею 30 Закону «Про освіту». Біля кожного документа — дата оприлюднення.</p>
      <a class="more" href="<?= e(url('/dokumenty/')) ?>">Увесь реєстр документів</a>
    </div>
    <?= component_documents(['limit' => 6, 'real' => '1']) ?>
  </div>
</section>

<section class="sec sec-soft" aria-labelledby="h-about">
  <div class="wrap">
    <div class="sec-head"><h2 id="h-about">Ліцей у цифрах</h2></div>
    <?= component_numbers() ?>
    <div class="sec-head sec-head-sub"><h3>Адміністрація</h3><a class="more" href="<?= e(url('/kadrovyj-sklad/')) ?>">Увесь кадровий склад</a></div>
    <?= component_team(['group' => 'administratsiya', 'limit' => 5]) ?>
  </div>
</section>

<section class="sec" aria-labelledby="h-clubs">
  <div class="wrap">
    <div class="sec-head"><h2 id="h-clubs">Гуртки і клуби</h2><a class="more" href="<?= e(url('/gurtky/')) ?>">Усі гуртки</a></div>
    <?= component_club_list(['limit' => 3]) ?>
  </div>
</section>

<section class="sec" aria-labelledby="h-cta">
  <div class="wrap">
    <div class="cta">
      <div>
        <h2 id="h-cta">Маєте питання?</h2>
        <p>Зателефонуйте до приймальні або напишіть. Якщо потрібен офіційний документ — подайте запит на публічну інформацію: ліцей відповідає протягом п’яти робочих днів.</p>
      </div>
      <div class="actions">
        <a class="btn btn-light" href="<?= e(url('/kontakty/')) ?>">Контакти</a>
        <a class="btn btn-outline-light" href="<?= e(url('/publichna-informacia/')) ?>">Запит на інформацію</a>
      </div>
    </div>
  </div>
</section>
