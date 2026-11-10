<?php
/**
 * Інтерфейсні компоненти сторінок.
 *
 * Хлібні крихти, лід, позначка шаблонного тексту, плитки підсторінок,
 * повʼязані сторінки, реєстр документів за розділами, команда, гуртки,
 * контакти. Усе — короткими кодами, щоб працювало в будь-якому шаблоні
 * і переживало зміну теми.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

/* ═════════ Лід сторінки і позначка «шаблонний текст» ═════════ */

add_action( 'init', function (): void {
	add_post_type_support( 'page', 'excerpt' );
	register_post_meta( 'page', 'vit_template', array(
		'type'          => 'boolean',
		'single'        => true,
		'show_in_rest'  => true,
		'auth_callback' => fn() => current_user_can( 'edit_pages' ),
	) );
}, 12 );

add_action( 'add_meta_boxes_page', function (): void {
	add_meta_box( 'vitryla_tpl', 'Стан тексту', 'vitryla_tpl_box', 'page', 'side', 'high' );
} );

/**
 * Метабокс «Текст шаблонний».
 *
 * @param WP_Post $post Сторінка.
 */
function vitryla_tpl_box( WP_Post $post ): void {
	wp_nonce_field( 'vitryla_tpl', 'vitryla_tpl_nonce' );
	$on = (bool) get_post_meta( $post->ID, 'vit_template', true );
	printf(
		'<label><input type="checkbox" name="vit_template" value="1" %s> Текст шаблонний — замінити на фактичний</label>
		<p class="description">Зніміть позначку, коли сторінку наповнено справжніми даними. Тоді зникне плашка «Зразок наповнення».</p>',
		checked( $on, true, false )
	);
}

add_action( 'save_post_page', function ( int $id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['vitryla_tpl_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vitryla_tpl_nonce'] ) ), 'vitryla_tpl' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_page', $id ) ) {
		return;
	}
	update_post_meta( $id, 'vit_template', isset( $_POST['vit_template'] ) ? 1 : 0 );
} );

/**
 * Скільки сторінок ще містять шаблонний текст.
 */
function vitryla_template_count(): int {
	global $wpdb;
	return (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
		 JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = 'vit_template' AND pm.meta_value = '1' AND p.post_type = 'page'"
	);
}

add_filter( 'manage_page_posts_columns', function ( array $c ): array {
	return array_slice( $c, 0, 2, true ) + array( 'vit_text' => 'Текст' ) + array_slice( $c, 2, null, true );
} );

add_action( 'manage_page_posts_custom_column', function ( string $col, int $id ): void {
	if ( 'vit_text' !== $col ) {
		return;
	}
	$content = (string) get_post_field( 'post_content', $id );
	if ( get_post_meta( $id, 'vit_template', true ) ) {
		echo '<span class="vit-badge vit-due">шаблон</span>';
	} elseif ( str_contains( $content, 'vit-brief' ) ) {
		echo '<span class="vit-badge vit-nodate">чекає</span>';
	} else {
		echo '<span class="vit-badge vit-fresh">фактичний</span>';
	}
}, 10, 2 );

add_action( 'admin_notices', function (): void {
	$n = vitryla_template_count();
	if ( ! $n ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p><strong>Сторінок із шаблонним текстом: %d.</strong> Він показує, як виглядатиме наповнена сторінка, але цифри, дати й імена в ньому треба замінити на фактичні. Перелік — у колонці «Текст» на <a href="%s">сторінці «Сторінки»</a>.</p></div>',
		$n,
		esc_url( admin_url( 'edit.php?post_type=page' ) )
	);
} );

/* ═════════ Хлібні крихти ═════════ */

add_shortcode( 'vit_crumbs', function (): string {
	$items = array( array( home_url( '/' ), 'Головна' ) );
	$here  = '';
	$news  = (int) get_option( 'page_for_posts' );

	if ( is_page() ) {
		$id = get_queried_object_id();
		foreach ( array_reverse( get_post_ancestors( $id ) ) as $a ) {
			$items[] = array( get_permalink( $a ), get_the_title( $a ) );
		}
		$here = get_the_title( $id );
	} elseif ( is_singular( 'post' ) ) {
		if ( $news ) {
			$items[] = array( get_permalink( $news ), 'Новини' );
		}
		$here = get_the_title( get_queried_object_id() );
	} elseif ( is_singular( array( 'vit_document', 'vit_club', 'vit_staff' ) ) ) {
		$pt      = get_post_type();
		$items[] = array( get_post_type_archive_link( $pt ), post_type_archive_title( '', false ) ?: get_post_type_object( $pt )->labels->name );
		$here    = get_the_title( get_queried_object_id() );
	} elseif ( is_home() ) {
		$here = 'Новини';
	} elseif ( is_category() ) {
		if ( $news ) {
			$items[] = array( get_permalink( $news ), 'Новини' );
		}
		$here = single_cat_title( '', false );
	} elseif ( is_post_type_archive() ) {
		$here = post_type_archive_title( '', false );
	} elseif ( is_search() ) {
		$here = 'Пошук';
	} else {
		return '';
	}

	$out = '<nav class="vit-crumbs" aria-label="Навігаційний ланцюжок"><ol>';
	foreach ( $items as [ $u, $t ] ) {
		$out .= '<li><a href="' . esc_url( (string) $u ) . '">' . esc_html( (string) $t ) . '</a></li>';
	}
	return $out . '<li aria-current="page">' . esc_html( $here ) . '</li></ol></nav>';
} );

/* ═════════ Лід і плашка шаблону ═════════ */

add_shortcode( 'vit_lead', function (): string {
	$id = get_queried_object_id();
	$ex = $id ? (string) get_post_field( 'post_excerpt', $id ) : '';
	return $ex ? '<p class="vit-lead">' . esc_html( $ex ) . '</p>' : '';
} );

add_shortcode( 'vit_template_badge', function (): string {
	$id = get_queried_object_id();
	if ( ! $id || ! get_post_meta( $id, 'vit_template', true ) ) {
		return '';
	}
	return '<p class="vit-tpl-badge"><span aria-hidden="true"></span>Зразок наповнення — дані буде уточнено</p>';
} );

/* ═════════ Плитки підсторінок ═════════ */

/**
 * Значок для сторінки за її слагом.
 */
function vitryla_icon_for( string $slug ): string {
	$map = array(
		'istoriia' => 'book', 'struktura-ta-organy' => 'users', 'korpusy-i-baza' => 'home',
		'ustanovchi-dokumenty' => 'doc', 'vakansii' => 'hand',
		'osvitni-programy' => 'book', 'pochatkova-shkola' => 'sun', 'bazova-shkola' => 'compass',
		'profilna-shkola' => 'award', 'rozklad' => 'clock', 'struktura-navchalnoho-roku' => 'calendar',
		'systema-otsiniuvannia' => 'chart', 'rezultaty-i-monitorynh' => 'chart', 'proforiientatsiia-nmt' => 'flag',
		'vstup' => 'key', 'terytoriia-obsluhovuvannia' => 'pin', 'harchuvannia' => 'food',
		'pravyla-povedinky' => 'check', 'pravyla-dostupu' => 'lock', 'indyvidualna-forma' => 'home',
		'spivpratsia-z-batkamy' => 'chat', 'nakazy' => 'doc',
		'bezpeka-i-ukryttia' => 'shield', 'psyhologichna-sluzhba' => 'heart', 'inkliuzyvne-navchannia' => 'users',
		'dostupnist-oop' => 'eye', 'protydiia-bulinhu' => 'hand', 'ohorona-pratsi' => 'alert', 'kiberbezpeka' => 'laptop',
		'finansovi-dokumenty' => 'chart', 'blagodijna-dopomoha' => 'gift', 'plany-i-zvity' => 'doc',
		'strategiia-rozvytku' => 'compass', 'samootsiniuvannia' => 'check', 'litsenzovanyj-obsiah' => 'users',
		'mova-osvitnoho-protsesu' => 'globe', 'dodatkovi-posluhy' => 'star', 'zapobihannia-koruptsii' => 'scale',
		'shcho-opryliudniuiemo' => 'eye',
		'normatyvna-baza' => 'scale', 'pidvyshchennia-kvalifikatsii' => 'award', 'atestatsiia' => 'check',
		'metodychna-robota' => 'book', 'rada-litseiu' => 'users',
		'nashi-dosiahnennia' => 'award', 'nashi-vypusknyky' => 'flag', 'uchnivske-samovriaduvannia' => 'chat',
		'natsionalna-identychnist' => 'flag',
		'pidviz' => 'bus', 'medychne-obsluhovuvannia' => 'heart',
	);
	return $map[ $slug ] ?? 'doc';
}

add_shortcode( 'vit_children', function (): string {
	$id   = get_queried_object_id();
	$kids = $id ? get_pages( array( 'parent' => $id, 'sort_column' => 'menu_order,post_title' ) ) : array();
	if ( ! $kids ) {
		return '';
	}

	$tones = array( 'blue', 'sun', 'green', 'sail' );
	$out   = '<div class="vit-children">';
	foreach ( $kids as $i => $k ) {
		$out .= sprintf(
			'<a class="vit-child is-%1$s vit-glow" href="%2$s">
				<span class="vit-ic vit-ico-%3$s" aria-hidden="true"></span>
				<span class="vit-child-t">%4$s</span>
				<span class="vit-child-d">%5$s</span>
				<span class="vit-child-go" aria-hidden="true">→</span>
			</a>',
			esc_attr( $tones[ $i % 4 ] ),
			esc_url( (string) get_permalink( $k ) ),
			esc_attr( vitryla_icon_for( $k->post_name ) ),
			esc_html( $k->post_title ),
			esc_html( $k->post_excerpt )
		);
	}
	return $out . '</div>';
} );

/* ═════════ Інші сторінки розділу ═════════ */

add_shortcode( 'vit_related', function (): string {
	if ( ! is_page() ) {
		return '';
	}
	$id     = get_queried_object_id();
	$parent = (int) wp_get_post_parent_id( $id );
	if ( ! $parent ) {
		return '';
	}
	$sibs = get_pages( array( 'parent' => $parent, 'sort_column' => 'menu_order,post_title', 'exclude' => array( $id ) ) );

	$out = '<aside class="vit-related" aria-label="Інші сторінки розділу"><p class="vit-related-h">Ще в розділі «' . esc_html( get_the_title( $parent ) ) . '»</p><ul>';
	foreach ( $sibs as $s ) {
		$out .= '<li><a href="' . esc_url( (string) get_permalink( $s ) ) . '">' . esc_html( $s->post_title ) . '</a></li>';
	}
	return $out . '</ul><a class="vit-related-back" href="' . esc_url( (string) get_permalink( $parent ) ) . '">← До розділу</a></aside>';
} );

/* ═════════ Чипи під заголовком головної ═════════ */

add_shortcode( 'vit_hero_chips', function (): string {
	$o     = (array) get_option( 'vitryla_numbers', array() );
	$chips = array();
	if ( ! empty( $o['pupils'] ) ) {
		$chips[] = $o['pupils'] . ' ' . ( function_exists( 'vitryla_plural' ) ? vitryla_plural( (int) $o['pupils'], 'учень', 'учні', 'учнів' ) : 'учнів' );
	}
	if ( ! empty( $o['staff'] ) ) {
		$chips[] = $o['staff'] . ' ' . ( function_exists( 'vitryla_plural' ) ? vitryla_plural( (int) $o['staff'], 'педагог', 'педагоги', 'педагогів' ) : 'педагогів' );
	}
	$chips[] = '3 корпуси';
	$chips[] = 'інклюзивне навчання';

	return '<ul class="vit-chips">' . implode( '', array_map( fn( $c ) => '<li>' . esc_html( $c ) . '</li>', $chips ) ) . '</ul>';
} );

/* ═════════ Реєстр документів за розділами ═════════ */

add_shortcode( 'vit_registry', function (): string {
	$terms = get_terms( array( 'taxonomy' => 'vit_doc_section', 'hide_empty' => true ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return '<p class="vit-empty">Документів поки немає.</p>';
	}

	$out = '<nav class="vit-reg-nav" aria-label="Розділи документів"><ul>';
	foreach ( $terms as $t ) {
		$out .= '<li><a href="#r-' . esc_attr( $t->slug ) . '">' . esc_html( $t->name ) . ' <span>' . (int) $t->count . '</span></a></li>';
	}
	$out .= '</ul></nav>';

	foreach ( $terms as $t ) {
		$out .= '<section class="vit-reg-sec" id="r-' . esc_attr( $t->slug ) . '"><h2>' . esc_html( $t->name ) . '</h2>'
			. do_shortcode( '[vit_documents section="' . esc_attr( $t->slug ) . '"]' ) . '</section>';
	}
	return $out;
} );

/* ═════════ Команда ═════════
   [vit_team group="administratsiya" subjects="почат" limit="4"]          */

add_shortcode( 'vit_team', function ( $atts ): string {
	$a = shortcode_atts( array( 'group' => '', 'subjects' => '', 'limit' => -1 ), $atts, 'vit_team' );

	$args = array(
		'post_type'      => 'vit_staff',
		'posts_per_page' => (int) $a['limit'],
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	);
	if ( $a['group'] ) {
		$args['tax_query'] = array( array(
			'taxonomy' => 'vit_staff_group',
			'field'    => 'slug',
			'terms'    => array_map( 'trim', explode( ',', $a['group'] ) ),
		) );
	}
	if ( $a['subjects'] ) {
		$args['meta_query'] = array( array( 'key' => 'vit_subjects', 'value' => $a['subjects'], 'compare' => 'LIKE' ) );
	}

	$people = get_posts( $args );
	if ( ! $people ) {
		return '<p class="vit-empty">Список ще не заповнено.</p>';
	}

	$tones = array( 'blue', 'sun', 'sail', 'green' );
	$out   = '<ul class="vit-team">';
	foreach ( $people as $p ) {
		$pos = (string) get_post_meta( $p->ID, 'vit_position', true );
		$sub = (string) get_post_meta( $p->ID, 'vit_subjects', true );

		if ( has_post_thumbnail( $p ) ) {
			$ph = get_the_post_thumbnail( $p, 'medium', array( 'loading' => 'lazy', 'alt' => esc_attr( $p->post_title ) ) );
		} else {
			$parts = preg_split( '/\s+/u', trim( $p->post_title ) );
			$ini   = mb_strtoupper( mb_substr( $parts[0] ?? '', 0, 1 ) . mb_substr( $parts[1] ?? '', 0, 1 ) );
			$ph    = '<span class="vit-team-ava is-' . $tones[ $p->ID % 4 ] . '" aria-hidden="true">' . esc_html( $ini ) . '</span>';
		}

		$out .= '<li class="vit-team-p vit-glow">' . $ph
			. '<span class="vit-team-n">' . esc_html( $p->post_title ) . '</span>'
			. ( $pos ? '<span class="vit-team-r">' . esc_html( $pos ) . '</span>' : '' )
			. ( $sub ? '<span class="vit-team-s">' . esc_html( $sub ) . '</span>' : '' )
			. '</li>';
	}
	return $out . '</ul>';
} );

/* ═════════ Гуртки — картки ═════════ */

add_shortcode( 'vit_club_list', function ( $atts ): string {
	$a     = shortcode_atts( array( 'limit' => -1 ), $atts, 'vit_club_list' );
	$clubs = get_posts( array( 'post_type' => 'vit_club', 'posts_per_page' => (int) $a['limit'], 'orderby' => 'title', 'order' => 'ASC' ) );
	if ( ! $clubs ) {
		return '<p class="vit-empty">Перелік гуртків ще не заповнено.</p>';
	}

	$by_word = array( 'Курінь' => 'flag', 'журнал' => 'chat', 'Євро' => 'globe', 'дослід' => 'flask', 'Спорт' => 'ball', 'музи' => 'music', 'теат' => 'star' );
	$tones   = array( 'blue', 'sun', 'green', 'sail' );
	$out     = '<div class="vit-clubs">';

	foreach ( $clubs as $i => $c ) {
		$icon = 'star';
		foreach ( $by_word as $w => $ic ) {
			if ( mb_stripos( $c->post_title, $w ) !== false ) {
				$icon = $ic;
				break;
			}
		}
		$when = trim( get_post_meta( $c->ID, 'vit_day', true ) . ' · ' . get_post_meta( $c->ID, 'vit_time', true ), ' ·' );
		$meta = array_filter( array( (string) get_post_meta( $c->ID, 'vit_classes', true ), $when, (string) get_post_meta( $c->ID, 'vit_room', true ) ) );

		$out .= sprintf(
			'<a class="vit-club is-%1$s vit-glow" href="%2$s">
				<span class="vit-ic vit-ico-%3$s" aria-hidden="true"></span>
				<span class="vit-club-t">%4$s</span>
				<span class="vit-club-d">%5$s</span>
				<span class="vit-club-m">%6$s</span>
			</a>',
			esc_attr( $tones[ $i % 4 ] ),
			esc_url( (string) get_permalink( $c ) ),
			esc_attr( $icon ),
			esc_html( $c->post_title ),
			esc_html( wp_trim_words( wp_strip_all_tags( $c->post_content ), 18 ) ),
			esc_html( implode( ' · ', $meta ) )
		);
	}
	return $out . '</div>';
} );

/* ═════════ Інші новини під записом ═════════ */

add_shortcode( 'vit_related_news', function (): string {
	$id    = get_queried_object_id();
	$posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => array( $id ) ) );
	if ( ! $posts ) {
		return '';
	}
	$out = '<aside class="vit-more-news"><h2>Інші новини</h2><ul>';
	foreach ( $posts as $p ) {
		$cats = get_the_category( $p->ID );
		$out .= sprintf(
			'<li class="vit-glow"><a href="%s"><span class="vit-mn-c">%s</span><span class="vit-mn-t">%s</span><span class="vit-mn-d">%s</span></a></li>',
			esc_url( (string) get_permalink( $p ) ),
			esc_html( $cats ? $cats[0]->name : '' ),
			esc_html( $p->post_title ),
			esc_html( get_the_date( 'j F Y', $p ) )
		);
	}
	return $out . '</ul></aside>';
} );

/* ═════════ Контакти ═════════ */

/**
 * Контактні дані з налаштувань із чесними замінниками.
 *
 * @return array<string,string>
 */
function vitryla_contacts(): array {
	$o = (array) get_option( 'vitryla_contacts', array() );
	return array(
		'phone'     => $o['phone'] ?? '' ?: 'уточнюється',
		'extra'     => $o['phone_extra'] ?? '',
		'hours'     => $o['office_hours'] ?? '' ?: 'уточнюється',
		'reception' => $o['reception'] ?? '' ?: 'уточнюється',
		'public'    => $o['public_info'] ?? '' ?: 'буде призначено',
		'email'     => 'shkola2bel@ukr.net',
	);
}

add_shortcode( 'vit_contacts', function (): string {
	$c     = vitryla_contacts();
	$cards = array(
		array( 'phone', 'blue', 'Телефон приймальні', $c['phone'] . ( $c['extra'] ? '<br><small>' . esc_html( $c['extra'] ) . '</small>' : '' ) ),
		array( 'mail', 'sun', 'Електронна пошта', '<a href="mailto:' . esc_attr( $c['email'] ) . '">' . esc_html( $c['email'] ) . '</a>' ),
		array( 'clock', 'green', 'Години роботи', esc_html( $c['hours'] ) ),
		array( 'users', 'sail', 'Особистий прийом громадян', esc_html( $c['reception'] ) ),
	);
	$out = '<div class="vit-contact-cards">';
	foreach ( $cards as [ $ic, $tone, $label, $val ] ) {
		$out .= '<div class="vit-contact is-' . $tone . ' vit-glow"><span class="vit-ic vit-ico-' . $ic . '" aria-hidden="true"></span><span class="vit-contact-l">' . esc_html( $label ) . '</span><span class="vit-contact-v">' . ( 'phone' === $ic ? esc_html( $c['phone'] ) . ( $c['extra'] ? '<br><small>' . esc_html( $c['extra'] ) . '</small>' : '' ) : $val ) . '</span></div>';
	}
	return $out . '</div>';
} );

add_shortcode( 'vit_footer_contacts', function (): string {
	$c = vitryla_contacts();
	return '<ul class="vit-fc">'
		. '<li><span class="vit-ic-s vit-ico-phone" aria-hidden="true"></span>' . esc_html( $c['phone'] ) . '</li>'
		. '<li><span class="vit-ic-s vit-ico-mail" aria-hidden="true"></span><a href="mailto:' . esc_attr( $c['email'] ) . '">' . esc_html( $c['email'] ) . '</a></li>'
		. '<li><span class="vit-ic-s vit-ico-clock" aria-hidden="true"></span>' . esc_html( $c['hours'] ) . '</li>'
		. '</ul>';
} );
