<?php
/**
 * Короткі коди для динамічних блоків.
 * Свідомо не власні блоки: ті потребують збірки через npm, а короткі коди
 * вставляються будь-ким і переживають зміну теми.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

/* ═════════ Реєстр документів ═════════
   [vit_documents section="finansy" limit="-1" show_state="1"]           */

add_shortcode( 'vit_documents', function ( $atts ): string {
	$a = shortcode_atts( array(
		'section'    => '',
		'limit'      => -1,
		'show_state' => '1',
		'archive'    => '0',
	), $atts, 'vit_documents' );

	$args = array(
		'post_type'      => 'vit_document',
		'posts_per_page' => (int) $a['limit'],
		'orderby'        => 'meta_value',
		'meta_key'       => 'vit_approved',
		'order'          => 'DESC',
	);

	if ( $a['section'] ) {
		$args['tax_query'] = array( array(
			'taxonomy' => 'vit_doc_section',
			'field'    => 'slug',
			'terms'    => array_map( 'trim', explode( ',', $a['section'] ) ),
		) );
	}

	if ( '1' !== $a['archive'] ) {
		$args['meta_query'] = array(
			'relation' => 'OR',
			array( 'key' => 'vit_valid', 'value' => 'archive', 'compare' => '!=' ),
			array( 'key' => 'vit_valid', 'compare' => 'NOT EXISTS' ),
		);
	}

	$docs = get_posts( $args );

	if ( ! $docs ) {
		return '<p class="vit-empty">У цьому розділі документів поки немає.</p>';
	}

	$out = '<div class="vit-docs">';
	foreach ( $docs as $d ) {
		$file  = (int) get_post_meta( $d->ID, 'vit_file', true );
		$url   = $file ? wp_get_attachment_url( $file ) : get_permalink( $d );
		$state = vitryla_doc_state( $d->ID );
		$pub   = (string) get_post_meta( $d->ID, 'vit_published', true );

		$meta = $pub
			? 'оприлюднено ' . mysql2date( 'd.m.Y', $pub )
			: ( '1' === $a['show_state'] ? $state['label'] : '' );

		$out .= sprintf(
			'<a class="vit-doc" href="%s"%s>
				<span class="ic" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/></svg>
				</span>
				<span class="ttl">%s</span>
				<span class="meta %s">%s</span>
			</a>',
			esc_url( (string) $url ),
			$file ? ' target="_blank" rel="noopener"' : '',
			esc_html( $d->post_title ),
			esc_attr( $state['class'] ),
			esc_html( $meta )
		);
	}
	return $out . '</div>';
} );

/* ═════════ Сітка педагогів ═════════
   [vit_staff group="administratsiya"]                                   */

add_shortcode( 'vit_staff', function ( $atts ): string {
	$a = shortcode_atts( array( 'group' => '', 'limit' => -1 ), $atts, 'vit_staff' );

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

	$people = get_posts( $args );
	if ( ! $people ) {
		return '<p class="vit-empty">Список працівників ще не заповнено.</p>';
	}

	$out = '<div class="vit-staff">';
	foreach ( $people as $p ) {
		$pos  = (string) get_post_meta( $p->ID, 'vit_position', true );
		$subj = (string) get_post_meta( $p->ID, 'vit_subjects', true );
		if ( has_post_thumbnail( $p->ID ) ) {
			$img = get_the_post_thumbnail( $p->ID, 'medium', array( 'loading' => 'lazy', 'alt' => esc_attr( $p->post_title ) ) );
		} else {
			// Замість сірої заглушки — ініціали на кольорі з палітри емблеми.
			$parts    = preg_split( '/\s+/u', trim( $p->post_title ) );
			$initials = mb_substr( $parts[0] ?? '', 0, 1 ) . mb_substr( $parts[1] ?? '', 0, 1 );
			$tints    = array( 'is-blue', 'is-sun', 'is-sail', 'is-green' );
			$img      = sprintf(
				'<span class="vit-ava %s" aria-hidden="true">%s</span>',
				esc_attr( $tints[ $p->ID % 4 ] ),
				esc_html( mb_strtoupper( $initials ) )
			);
		}

		$out .= sprintf(
			'<div class="p">%s<b>%s</b>%s%s</div>',
			$img,
			esc_html( $p->post_title ),
			$pos ? '<span>' . esc_html( $pos ) . '</span>' : '',
			$subj ? '<span>' . esc_html( $subj ) . '</span>' : ''
		);
	}
	return $out . '</div>';
} );

/* ═════════ Гуртки ═════════ */

add_shortcode( 'vit_clubs', function ( $atts ): string {
	$clubs = get_posts( array( 'post_type' => 'vit_club', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	if ( ! $clubs ) {
		return '<p class="vit-empty">Перелік гуртків ще не заповнено.</p>';
	}

	$out = '<div class="vit-docs">';
	foreach ( $clubs as $c ) {
		$leader = (int) get_post_meta( $c->ID, 'vit_leader', true );
		$bits   = array_filter( array(
			(string) get_post_meta( $c->ID, 'vit_classes', true ),
			trim( get_post_meta( $c->ID, 'vit_day', true ) . ' ' . get_post_meta( $c->ID, 'vit_time', true ) ),
			$leader ? get_the_title( $leader ) : '',
		) );

		$out .= sprintf(
			'<a class="vit-doc" href="%s"><span class="ttl">%s</span><span class="meta">%s</span></a>',
			esc_url( (string) get_permalink( $c ) ),
			esc_html( $c->post_title ),
			esc_html( implode( ' · ', $bits ) )
		);
	}
	return $out . '</div>';
} );

/* ═════════ Смуга «Сьогодні в ліцеї» ═════════ */

add_shortcode( 'vit_today', function (): string {
	$o = get_option( 'vitryla_today', array() );

	$cells = array(
		array( 'is-blue',  'clock',  'Навчання',   $o['format'] ?? '', $o['format_note'] ?? '' ),
		array( 'is-sail',  'shield', 'Тривога',    $o['alarm'] ?? '', $o['alarm_note'] ?? '' ),
		array( 'is-green', 'food',   'Харчування', $o['food'] ?? '', $o['food_note'] ?? '' ),
		array( 'is-sun',   'mail',   'Приймальня', $o['office'] ?? '', $o['office_note'] ?? '' ),
	);

	$icons = array(
		'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
		'food'   => '<path d="M4 12h16a8 8 0 0 1-16 0z"/><path d="M8 8c0-2 1-3 1-5M12 8c0-2 1-3 1-5M16 8c0-2 1-3 1-5"/>',
		'mail'   => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M4 7l8 6 8-6"/>',
	);

	$out = '<div class="vit-today">';
	foreach ( $cells as [ $cls, $icon, $label, $value, $note ] ) {
		$out .= sprintf(
			'<div class="cell %s"><span class="ic" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg></span>
			<span><span class="k">%s</span><span class="v">%s%s</span></span></div>',
			esc_attr( $cls ),
			$icons[ $icon ],
			esc_html( $label ),
			$value ? esc_html( $value ) : '<em style="font-weight:400;color:#8E97B5">не заповнено</em>',
			$note ? '<small>' . esc_html( $note ) . '</small>' : ''
		);
	}
	return $out . '</div>';
} );

/* ═════════ Ліцей у цифрах ═════════ */

add_shortcode( 'vit_numbers', function (): string {
	$o  = get_option( 'vitryla_numbers', array() );
	$pl = static function ( $n, string $one, string $few, string $many ): string {
		$n = (int) preg_replace( '/\D/', '', (string) $n );
		return function_exists( 'vitryla_plural' ) ? vitryla_plural( $n, $one, $few, $many ) : $many;
	};

	$items = array(
		array( $o['founded'] ?? '1897', 'рік заснування', 'церковно-приходська школа' ),
		array( $o['pupils'] ?? '', $pl( $o['pupils'] ?? '', 'здобувач освіти', 'здобувачі освіти', 'здобувачів освіти' ), $o['pupils_date'] ?? '' ),
		array( $o['classes'] ?? '', $pl( $o['classes'] ?? '', 'клас', 'класи', 'класів' ), $o['classes_date'] ?? '' ),
		array( $o['staff'] ?? '', $pl( $o['staff'] ?? '', 'педагог', 'педагоги', 'педагогів' ), $o['staff_date'] ?? '' ),
		array( $o['shelters'] ?? '', $pl( $o['shelters'] ?? '', 'обладнане укриття', 'обладнані укриття', 'обладнаних укриттів' ), $o['shelters_note'] ?? '' ),
	);

	$out = '<div class="vit-bento vit-numbers">';
	foreach ( $items as [ $n, $label, $note ] ) {
		$out .= sprintf(
			'<div class="tile"><b style="font-size:2.4rem;margin:0">%s</b><span>%s</span>%s</div>',
			$n ? esc_html( $n ) : '—',
			esc_html( $label ),
			$note ? '<span style="font-size:.78rem;opacity:.7">' . esc_html( $note ) . '</span>' : ''
		);
	}
	return $out . '</div>';
} );

/* ═════════ Карта сайту ═════════ */

add_shortcode( 'vit_sitemap', function (): string {
	$sections = get_pages( array( 'parent' => 0, 'sort_column' => 'menu_order,post_title' ) );
	$out      = '<div class="vit-sitemap">';

	foreach ( $sections as $sec ) {
		if ( in_array( $sec->post_name, array( 'holovna', 'karta-sajtu', 'privacy-policy', 'sample-page' ), true ) ) {
			continue;
		}
		$kids = get_pages( array( 'child_of' => $sec->ID, 'sort_column' => 'menu_order,post_title' ) );

		$out .= sprintf(
			'<div class="sec"><h2><a href="%s">%s</a></h2>',
			esc_url( (string) get_permalink( $sec ) ),
			esc_html( $sec->post_title )
		);
		if ( $kids ) {
			$out .= '<ul>';
			foreach ( $kids as $k ) {
				$out .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( (string) get_permalink( $k ) ), esc_html( $k->post_title ) );
			}
			$out .= '</ul>';
		}
		$out .= '</div>';
	}

	// Розділи, що генеруються з записів, а не зі сторінок.
	$out .= '<div class="sec"><h2>Окремі розділи</h2><ul>';
	foreach ( array( 'vit_document' => 'Документи', 'vit_staff' => 'Кадровий склад', 'vit_club' => 'Гуртки і клуби' ) as $pt => $label ) {
		$out .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( (string) get_post_type_archive_link( $pt ) ), esc_html( $label ) );
	}
	$news = (int) get_option( 'page_for_posts' );
	if ( $news ) {
		$out .= sprintf( '<li><a href="%s">Новини</a></li>', esc_url( (string) get_permalink( $news ) ) );
	}
	$out .= '</ul></div>';

	return $out . '</div>';
} );

/* ═════════ Картка документа (на сторінці документа) ═════════ */

add_shortcode( 'vit_doc_card', function (): string {
	$id = get_the_ID();
	if ( ! $id || 'vit_document' !== get_post_type( $id ) ) {
		return '';
	}

	$file      = (int) get_post_meta( $id, 'vit_file', true );
	$number    = (string) get_post_meta( $id, 'vit_number', true );
	$approved  = (string) get_post_meta( $id, 'vit_approved', true );
	$published = (string) get_post_meta( $id, 'vit_published', true );
	$state     = vitryla_doc_state( $id );

	$rows = array();
	if ( $number ) {
		$rows[] = array( 'Реквізити', esc_html( $number ) );
	}
	if ( $approved ) {
		$rows[] = array( 'Затверджено', esc_html( mysql2date( 'd.m.Y', $approved ) ) );
	}
	if ( $published ) {
		$rows[] = array( 'Оприлюднено', esc_html( mysql2date( 'd.m.Y', $published ) ) );
	}
	$rows[] = array( 'Стан', '<span class="vit-state ' . esc_attr( $state['class'] ) . '">' . esc_html( $state['label'] ) . '</span>' );

	$out = '<div class="vit-card"><dl>';
	foreach ( $rows as [$k, $v] ) {
		$out .= '<div><dt>' . esc_html( $k ) . '</dt><dd>' . $v . '</dd></div>';
	}
	$out .= '</dl>';

	if ( $file ) {
		$url  = wp_get_attachment_url( $file );
		$size = size_format( (int) filesize( (string) get_attached_file( $file ) ) );
		$ext  = strtoupper( pathinfo( (string) $url, PATHINFO_EXTENSION ) );
		$out .= sprintf(
			'<a class="vit-dl" href="%s" target="_blank" rel="noopener">Відкрити документ <span>%s · %s</span></a>',
			esc_url( (string) $url ),
			esc_html( $ext ),
			esc_html( $size )
		);
	} else {
		$out .= '<p class="vit-empty">Файл документа ще не завантажено.</p>';
	}

	return $out . '</div>';
} );

/* ═════════ Картка гуртка ═════════ */

add_shortcode( 'vit_club_card', function (): string {
	$id = get_the_ID();
	if ( ! $id || 'vit_club' !== get_post_type( $id ) ) {
		return '';
	}

	$leader = (int) get_post_meta( $id, 'vit_leader', true );
	$rows   = array(
		'Керівник' => $leader ? esc_html( get_the_title( $leader ) ) : '',
		'Для класів' => esc_html( (string) get_post_meta( $id, 'vit_classes', true ) ),
		'Коли'       => esc_html( trim( get_post_meta( $id, 'vit_day', true ) . ' ' . get_post_meta( $id, 'vit_time', true ) ) ),
		'Де'         => esc_html( (string) get_post_meta( $id, 'vit_room', true ) ),
	);
	$rows = array_filter( $rows );
	if ( ! $rows ) {
		return '';
	}

	$out = '<div class="vit-card"><dl>';
	foreach ( $rows as $k => $v ) {
		$out .= '<div><dt>' . esc_html( $k ) . '</dt><dd>' . $v . '</dd></div>';
	}
	return $out . '</dl></div>';
} );
