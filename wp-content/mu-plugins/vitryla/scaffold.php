<?php
/**
 * Одноразове створення каркаса сайту: 7 розділів, сторінки, меню.
 * Сторінки створюються порожніми, але вже під’єднаними до реєстру
 * документів — лишається лише завантажити самі документи.
 *
 * Запускається один раз. Щоб повторити: видалити опцію vitryla_scaffolded.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Дерево сайту: розділ => [назва, [slug => [назва, підказка, (необов’язково) вміст]]].
 */
function vitryla_tree(): array {
	return array(
		'pro-licej' => array( 'Про ліцей', array(
			'istoriia'              => array( 'Історія ліцею', 'Перенести текст і 15 фото з чинного сайту, доповнити періодом після 2022 року.' ),
			'struktura-ta-organy'   => array( 'Структура та органи управління', 'Схема, склад ради ліцею, піклувальної ради, учнівського самоврядування.' ),
			'korpusy-i-baza'        => array( 'Три корпуси і матеріальна база', 'Адреси, призначення, кабінети, спортзал, їдальня, бібліотека.' ),
			'ustanovchi-dokumenty'  => array( 'Установчі документи', 'Статут, ліцензії, виписки.', '[vit_documents section="ustanovchi"]' ),
			'vakansii'              => array( 'Вакансії', 'Порядок конкурсу й актуальний перелік станом на дату.' ),
		) ),

		'navchannia' => array( 'Навчання', array(
			'osvitni-programy'           => array( 'Освітні програми', 'Освітня програма на 2026/2027 і перелік освітніх компонентів. Вимога статті 30.', '[vit_documents section="osvitni"]' ),
			'pochatkova-shkola'          => array( 'Початкова школа · 1–4 класи', 'Хто вчить, режим дня, адаптація першокласників, окремий корпус.' ),
			'bazova-shkola'              => array( 'Базова школа · 5–9 класи', 'Предмети, кабінетна система, оцінювання, вибір після 9 класу.' ),
			'profilna-shkola'            => array( 'Профільна школа: академічний ліцей з 2027 року', 'Перехід до старшої профільної школи: що зміниться, план, відповіді.' ),
			'rozklad'                    => array( 'Розклад і дзвінки', 'Розклад уроків по класах, дзвінки, режим трьох корпусів, закріплення кабінетів.' ),
			'struktura-navchalnoho-roku' => array( 'Структура навчального року', 'Семестри, канікули, дати початку й завершення.' ),
			'systema-otsiniuvannia'      => array( 'Система оцінювання', 'Критерії, формувальне оцінювання, академічна доброчесність. Оновити під наказ МОН №722.' ),
			'rezultaty-i-monitorynh'     => array( 'Результати і моніторинг', 'Моніторинг якості освіти та середній бал. Вимога статті 30.', '[vit_documents section="monitorynh"]' ),
			'proforiientatsiia-nmt'      => array( 'Профорієнтація і НМТ', 'Терміни реєстрації, підготовка, зустрічі із закладами вищої освіти.' ),
		) ),

		'batkam-i-uchniam' => array( 'Батькам і учням', array(
			'vstup'                    => array( 'Вступ до ліцею', 'Правила прийому, перелік документів, як подати заяву.', '[vit_documents section="pryjom"]' ),
			'terytoriia-obsluhovuvannia' => array( 'Територія обслуговування', 'Чинне рішення міської ради.' ),
			'harchuvannia'             => array( 'Харчування', 'Режим, вартість, пільгові категорії, меню на тиждень, відповідальний.' ),
			'pravyla-povedinky'        => array( 'Правила поведінки здобувачів освіти', 'Документ плюс короткий переказ людською мовою.' ),
			'pravyla-dostupu'          => array( 'Правила доступу і перебування', 'Нова вимога: наказ МОН №243 від 11.02.2026.', '[vit_documents section="bezpeka"]' ),
			'indyvidualna-forma'       => array( 'Індивідуальна форма навчання', 'Екстернат, сімейна форма, педагогічний патронаж.' ),
			'spivpratsia-z-batkamy'    => array( 'Співпраця з батьками', 'Збори, рада батьків, як звернутися до адміністрації.' ),
			'nakazy'                   => array( 'Накази та оголошення', 'Накази, що стосуються родин: зарахування, переведення.' ),
			'pidviz'                   => array( 'Підвіз учнів', 'Маршрут шкільного автобуса, розклад, супровід.' ),
		) ),

		'pidtrymka-i-bezpeka' => array( 'Підтримка і безпека', array(
			'bezpeka-i-ukryttia'     => array( 'Безпека і укриття', 'Де укриття, місткість, алгоритм переходу, навчання під час тривоги.' ),
			'psyhologichna-sluzhba'  => array( 'Психологічна служба', 'Графік психолога, як звернутися, матеріали для учнів і батьків.' ),
			'inkliuzyvne-navchannia' => array( 'Інклюзивне навчання', 'Команда супроводу, асистенти вчителя. Оновити під постанову КМУ №129 від 05.02.2026.' ),
			'dostupnist-oop'         => array( 'Доступність для осіб з ООП', 'Пандуси, санвузли, доступні кабінети, супровід.', '[vit_documents section="dostupnist"]' ),
			'protydiia-bulinhu'      => array( 'Протидія булінгу', 'Три обов’язкові документи: план заходів, порядок подання заяв, порядок реагування.', '[vit_documents section="bulinh"]' ),
			'ohorona-pratsi'         => array( 'Охорона праці та БЖД', 'Інструкції для учнів і працівників.' ),
			'kiberbezpeka'           => array( 'Кібербезпека', 'Безпека в інтернеті для учнів і батьків.' ),
			'medychne-obsluhovuvannia' => array( 'Медичне обслуговування', 'Медична сестра, кабінет, графік.' ),
		) ),

		'prozorist' => array( 'Прозорість', array(
			'finansovi-dokumenty'    => array( 'Фінансові документи', 'Кошторис і квартальні звіти. Строк — 35 днів після кварталу.', '[vit_documents section="finansy"]' ),
			'blagodijna-dopomoha'    => array( 'Благодійна допомога та закупівлі', 'Перелік товарів і послуг із зазначенням вартості.', '[vit_documents section="blagodijna"]' ),
			'plany-i-zvity'          => array( 'Плани і звіти', 'Річний звіт директора і план роботи.', '[vit_documents section="zvity"]' ),
			'strategiia-rozvytku'    => array( 'Стратегія розвитку', 'Чинна закінчилася у 2025 році — потрібна нова.', '[vit_documents section="strategiya"]' ),
			'samootsiniuvannia'      => array( 'Самооцінювання діяльності', 'Результати внутрішнього самооцінювання за напрямами.' ),
			'litsenzovanyj-obsiah'   => array( 'Ліцензований обсяг і кількість учнів', 'Дві цифри й дата, на яку вони чинні.' ),
			'mova-osvitnoho-protsesu'=> array( 'Мова освітнього процесу', 'Витяг зі Статуту.' ),
			'dodatkovi-posluhy'      => array( 'Додаткові освітні послуги', 'Перелік і вартість або відповідь, що не надаються.' ),
			'zapobihannia-koruptsii' => array( 'Запобігання корупції', 'Чинна антикорупційна програма.', '[vit_documents section="koruptsiya"]' ),
			'shcho-opryliudniuiemo'  => array( 'Що ми оприлюднюємо і коли', 'Пам’ятка: який документ, де лежить, як часто оновлюється.' ),
		) ),

		'pedagoham' => array( 'Педагогам', array(
			'normatyvna-baza'             => array( 'Нормативно-правова база', 'Документи на 2026/2027: оцінювання, інклюзія, поділ класів, харчування.', '[vit_documents section="normatyvna"]' ),
			'pidvyshchennia-kvalifikatsii'=> array( 'Підвищення кваліфікації', 'Перспективний план і порядок визнання результатів.' ),
			'atestatsiia'                 => array( 'Атестація', 'Порядок, графік, результати. Наказ МОН №445 зі змінами №582.', '[vit_documents section="kadry"]' ),
			'metodychna-robota'           => array( 'Методична робота', 'Методоб’єднання, напрацювання, обмін досвідом.' ),
			'rada-litseiu'                => array( 'Рада ліцею', 'Склад, повноваження, план роботи, протоколи.' ),
		) ),

		'zhyttia-litseiu' => array( 'Життя ліцею', array(
			'nashi-dosiahnennia'        => array( 'Наші досягнення', 'Олімпіади, конкурси, змагання, проєкти, робота з обдарованими.' ),
			'nashi-vypusknyky'          => array( 'Наші випускники', 'Ким стали, де навчаються, зустрічі.' ),
			'uchnivske-samovriaduvannia'=> array( 'Учнівське самоврядування', 'Склад, що роблять, як долучитися.' ),
			'natsionalna-identychnist'  => array( 'Національна ідентичність', 'Заходи національно-патріотичного спрямування, вшанування пам’яті.' ),
		) ),
	);
}

/**
 * Службові сторінки поза розділами.
 */
function vitryla_service_pages(): array {
	return array(
		'karta-sajtu'          => array( 'Карта сайту', 'Перелік усіх розділів і сторінок.' ),
		'dostupnist'           => array( 'Доступність', 'Як користуватися сайтом людям з порушеннями зору, заява про доступність.' ),
		'publichna-informacia' => array( 'Публічна інформація', 'Порядок подання запиту, форма, відповідальна особа, порядок оскарження.' ),
		'kontakty'             => array( 'Контакти', 'Три корпуси, телефони, графік прийому громадян, карта.' ),
	);
}

add_action( 'admin_init', 'vitryla_scaffold_once' );

/**
 * Створює каркас один раз.
 */
function vitryla_scaffold_once(): void {
	if ( get_option( 'vitryla_scaffolded' ) ) {
		return;
	}

	$make = function ( string $slug, string $title, string $brief = '', int $parent = 0, string $content = '' ): int {
		$existing = function_exists( 'vitryla_page_by_slug' ) ? vitryla_page_by_slug( $slug ) : get_page_by_path( $slug );
		if ( $existing ) {
			return $existing->ID;
		}

		$body = '';
		if ( $brief ) {
			$body .= "<!-- wp:paragraph {\"className\":\"vit-brief\",\"textColor\":\"ink-3\"} -->\n"
				. '<p class="vit-brief has-ink-3-color has-text-color"><em>Що тут має бути: ' . esc_html( $brief ) . '</em></p>'
				. "\n<!-- /wp:paragraph -->\n\n";
		}
		if ( $content ) {
			$body .= "<!-- wp:shortcode -->\n" . $content . "\n<!-- /wp:shortcode -->\n";
		}

		return (int) wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_parent'  => $parent,
			'post_content' => $body,
		) );
	};

	// Головна.
	$home = $make( 'holovna', 'Головна' );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );

	// Новини — сторінка записів.
	$news = $make( 'novyny', 'Новини' );
	update_option( 'page_for_posts', $news );

	// Розділи та підсторінки.
	$section_ids = array();
	foreach ( vitryla_tree() as $slug => [$title, $children] ) {
		$pid                    = $make( $slug, $title, 'Обкладинка розділу: два-три речення про те, що тут знайти, і плитки підсторінок.' );
		$section_ids[ $slug ]   = $pid;
		foreach ( $children as $cslug => $c ) {
			$make( $cslug, $c[0], $c[1], $pid, $c[2] ?? '' );
		}
	}

	foreach ( vitryla_service_pages() as $slug => [$title, $brief] ) {
		$make( $slug, $title, $brief );
	}

	vitryla_build_navigation( $section_ids, $news );

	update_option( 'vitryla_scaffolded', 1 );
	flush_rewrite_rules();
}

/**
 * Створює запис навігації для блокової теми.
 *
 * @param array<string,int> $sections Розділи.
 * @param int               $news     Сторінка новин.
 */
function vitryla_build_navigation( array $sections, int $news ): void {
	$existing = get_posts( array( 'post_type' => 'wp_navigation', 'posts_per_page' => 1 ) );
	$items    = '';

	foreach ( $sections as $slug => $id ) {
		$items .= sprintf(
			'<!-- wp:navigation-link {"label":"%s","type":"page","id":%d,"url":"%s","kind":"post-type"} /-->' . "\n",
			esc_attr( get_the_title( $id ) ),
			$id,
			esc_url( (string) get_permalink( $id ) )
		);
	}
	// «Новини» свідомо не в головному меню, а в службовому рядку:
	// у меню — не більше семи розділів (висновок аналізу 12 шкільних сайтів).
	unset( $news );

	if ( $existing ) {
		wp_update_post( array( 'ID' => $existing[0]->ID, 'post_content' => $items ) );
		return;
	}

	wp_insert_post( array(
		'post_type'    => 'wp_navigation',
		'post_status'  => 'publish',
		'post_title'   => 'Головне меню',
		'post_content' => $items,
	) );
}
