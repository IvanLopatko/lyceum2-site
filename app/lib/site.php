<?php
/**
 * Те, що раніше було «зашите» в шаблони, а тепер редагується в адмінці:
 * головна сторінка (content/home.json), вступні тексти розділів, посилання в шапці й підвалі,
 * алгоритм дій під час тривоги й карта (content/settings.json).
 * Тут — значення за замовчуванням: вони діють, доки в адмінці нічого не змінено.
 */

declare(strict_types=1);

if (!defined('ROOT')) {
    http_response_code(404);
    exit;
}

const TILE_TONES = ['wide' => 'велика синя', 'sun' => 'сонячна', 'sail' => 'червона', 'blue' => 'блакитна', 'green' => 'зелена'];

/** Малюнки для плиток «Швидко знайти»: файли з assets/img/tiles/. */
function tile_arts(): array
{
    $out = [];
    foreach (glob(ROOT . '/assets/img/tiles/*.svg') ?: [] as $f) {
        $out[] = basename($f, '.svg');
    }
    return $out;
}

function home_defaults(): array
{
    return [
        'hero' => [
            'eyebrow' => 'Біляївка · Одеський район · з ' . setting('site.founded', '1897') . ' року',
            'title' => 'Школа, яку впізнають за [вітрилами]',
            'lead' => 'Комунальний ліцей з інклюзивним навчанням і трьома корпусами. Тут вчаться думати, дружити й не боятися ставити питання.',
            'buttons' => [['label' => 'Вступ до ліцею', 'url' => '/batkam-i-uchniam/vstup/'], ['label' => 'Розклад і дзвінки', 'url' => '/navchannia/rozklad/']],
            'chips' => ['3 корпуси', 'інклюзивне навчання'],
        ],
        'headings' => ['quick' => 'Швидко знайти', 'news' => 'Новини ліцею', 'numbers' => 'Ліцей у цифрах', 'team' => 'Адміністрація', 'clubs' => 'Гуртки і клуби'],
        'tiles' => [
            ['art' => 'rozklad', 'url' => '/navchannia/rozklad/', 'title' => 'Розклад уроків і дзвінків', 'text' => 'Дві зміни, дії під час тривоги', 'tone' => 'wide'],
            ['art' => 'vstup', 'url' => '/batkam-i-uchniam/vstup/', 'title' => 'Вступ до ліцею', 'text' => 'Документи до першого класу', 'tone' => 'sun'],
            ['art' => 'harchuvannia', 'url' => '/batkam-i-uchniam/harchuvannia/', 'title' => 'Харчування', 'text' => 'Шкільна їдальня, хто відповідає', 'tone' => 'sail'],
            ['art' => 'bezpeka', 'url' => '/pidtrymka-i-bezpeka/bezpeka-i-ukryttia/', 'title' => 'Безпека і укриття', 'text' => 'Дії під час тривоги, дві зміни', 'tone' => 'blue'],
            ['art' => 'psyholog', 'url' => '/pidtrymka-i-bezpeka/psyhologichna-sluzhba/', 'title' => 'Психологічна служба', 'text' => 'Підтримка учнів і батьків', 'tone' => 'green'],
            ['art' => 'inkliuziia', 'url' => '/pidtrymka-i-bezpeka/inkliuzyvne-navchannia/', 'title' => 'Інклюзивне навчання', 'text' => 'Команда супроводу, асистенти', 'tone' => 'green'],
            ['art' => 'profilna', 'url' => '/navchannia/profilna-shkola/', 'title' => 'Профільна школа', 'text' => 'Академічний ліцей з 2027 року', 'tone' => 'sun'],
            ['art' => 'dokumenty', 'url' => '/dokumenty/', 'title' => 'Документи', 'text' => 'Реєстр за статтею 30', 'tone' => 'sail'],
            ['art' => 'gurtky', 'url' => '/gurtky/', 'title' => 'Гуртки і клуби', 'text' => 'Об’єднання учнів ліцею', 'tone' => 'blue'],
        ],
        'promo' => [
            'show' => true,
            'eyebrow' => 'Старша профільна школа',
            'title' => 'Академічний ліцей з 1 вересня 2027 року',
            'text' => 'Що зміниться для нинішніх дев’ятикласників, як обрати профіль і де поставити запитання.',
            'button' => 'Усе про профільну школу',
            'url' => '/navchannia/profilna-shkola/',
        ],
        'docs' => [
            'title' => 'Прозорість і документи',
            'text' => 'Статут, освітні програми, звіти й кошторис — у відкритому доступі за статтею 30 Закону «Про освіту». Біля кожного документа — дата оприлюднення.',
        ],
        'cta' => [
            'title' => 'Маєте питання?',
            'text' => 'Зателефонуйте до приймальні або напишіть. Якщо потрібен офіційний документ — подайте запит на публічну інформацію: ліцей відповідає протягом п’яти робочих днів.',
            'buttons' => [['label' => 'Контакти', 'url' => '/kontakty/'], ['label' => 'Запит на інформацію', 'url' => '/publichna-informacia/']],
        ],
    ];
}

/** Вміст головної: збережене в адмінці поверх значень за замовчуванням. Списки (плитки, кнопки) беруться цілком. */
function home_data(): array
{
    $d = home_defaults();
    $s = store_read('home.json', []);
    foreach ($d as $k => $def) {
        if (!isset($s[$k]) || !is_array($s[$k])) {
            continue;
        }
        $d[$k] = array_is_list($def) ? $s[$k] : array_merge($def, $s[$k]);
    }
    return $d;
}

/** Заголовок з виділеним словом: «Школа за [вітрилами]» → HTML зі <span class="hl">. */
function hl_title(string $t): string
{
    return preg_replace('~\[([^\]]+)\]~u', '<span class="hl">$1</span>', e($t)) ?? e($t);
}

const TEXT_DEFAULTS = [
    'news_lead' => 'Події, оголошення й досягнення ліцею. Дописи переносимо зі спільноти ліцею у Facebook.',
    'documents_lead' => 'Реєстр документів ліцею за статтею 30 Закону «Про освіту». Біля кожного — дата оприлюднення або стан: документ має з’явитися на сайті протягом 10 робочих днів після затвердження.',
    'staff_lead' => 'Адміністрація, педагоги, асистенти вчителя й фахівці супроводу Біляївського ліцею №2.',
    'clubs_lead' => 'Гуртки, клуби й об’єднання учнів ліцею.',
    'notfound_title' => 'Такої сторінки немає',
    'notfound_lead' => 'Можливо, її перенесли під час оновлення сайту. Скористайтеся пошуком або картою сайту.',
    'footer_note' => 'Матеріали з офіційних документів і спільноти ліцею',
];

const TEXT_LABELS = [
    'news_lead' => 'Новини: текст під заголовком',
    'documents_lead' => 'Документи: текст під заголовком',
    'staff_lead' => 'Кадровий склад: текст під заголовком',
    'clubs_lead' => 'Гуртки і клуби: текст під заголовком',
    'notfound_title' => 'Сторінка «не знайдено»: заголовок',
    'notfound_lead' => 'Сторінка «не знайдено»: пояснення',
    'footer_note' => 'Підвал сайту: рядок унизу',
];

function site_text(string $key): string
{
    $v = trim((string) setting('texts.' . $key, ''));
    return $v !== '' ? $v : (TEXT_DEFAULTS[$key] ?? '');
}

const LINK_DEFAULTS = [
    'utility' => [
        ['label' => 'Новини', 'url' => '/novyny/'], ['label' => 'Розклад і дзвінки', 'url' => '/navchannia/rozklad/'],
        ['label' => 'Харчування', 'url' => '/batkam-i-uchniam/harchuvannia/'], ['label' => 'Документи', 'url' => '/dokumenty/'], ['label' => 'Контакти', 'url' => '/kontakty/'],
    ],
    'footer' => [
        ['label' => 'Публічна інформація', 'url' => '/publichna-informacia/'], ['label' => 'Що ми оприлюднюємо', 'url' => '/prozorist/shcho-opryliudniuiemo/'],
        ['label' => 'Доступність', 'url' => '/dostupnist/'], ['label' => 'Карта сайту', 'url' => '/karta-sajtu/'],
    ],
];

/** Посилання верхнього рядка («utility») або колонки «Корисне» в підвалі («footer»). */
function site_links(string $group): array
{
    $v = setting('links.' . $group, null);
    return is_array($v) && $v ? $v : (LINK_DEFAULTS[$group] ?? []);
}

const ALARM_DEFAULTS = [
    ['name' => 'Перша зміна', 'home' => '07:30', 'ctrl' => '09:00',
        'text_home' => 'Якщо тривога триває до 07:30 — учні залишаються вдома й не вирушають до ліцею.',
        'text_ctrl' => 'Якщо тривога триває до 09:00 — навчання першої зміни переходить у дистанційний формат.'],
    ['name' => 'Друга зміна', 'home' => '11:30', 'ctrl' => '13:00',
        'text_home' => 'Якщо тривога триває до 11:30 — учні залишаються вдома. Початок занять другої зміни — о 12:00, якщо адміністрація не повідомила інше.',
        'text_ctrl' => 'Якщо тривога триває до 13:00 — навчання другої зміни переходить у дистанційний формат.'],
];

/** Рядки блоку «Тривога: дві зміни». */
function alarm_rows(): array
{
    $v = setting('alarm', null);
    return is_array($v) && $v ? $v : ALARM_DEFAULTS;
}

/** Точка на карті (головний корпус). */
function map_point(): array
{
    $m = (array) setting('map', []);
    $lat = (float) ($m['lat'] ?? 0);
    $lon = (float) ($m['lon'] ?? 0);
    if ($lat < 44 || $lat > 53 || $lon < 22 || $lon > 41) { // поза Україною — беремо адресу головного корпусу
        $lat = 46.487948;
        $lon = 30.200875;
    }
    return ['lat' => $lat, 'lon' => $lon, 'title' => trim((string) ($m['title'] ?? '')) ?: 'Карта: головний корпус ліцею'];
}
