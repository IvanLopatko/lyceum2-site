<?php
/**
 * Адмінка: колонки списків і віджет контролю строків.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

/* ── колонки списку документів ───────────────────────────── */

add_filter( 'manage_vit_document_posts_columns', function ( array $cols ): array {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['vit_state']     = 'Стан';
			$new['vit_approved']  = 'Затверджено';
			$new['vit_published'] = 'Оприлюднено';
		}
	}
	return $new;
} );

add_action( 'manage_vit_document_posts_custom_column', function ( string $col, int $post_id ): void {
	switch ( $col ) {
		case 'vit_state':
			$s = vitryla_doc_state( $post_id );
			printf(
				'<span class="vit-badge vit-%s">%s</span>%s',
				esc_attr( $s['key'] ),
				esc_html( $s['label'] ),
				$s['note'] ? '<br><small style="color:#777">' . esc_html( $s['note'] ) . '</small>' : ''
			);
			break;

		case 'vit_approved':
		case 'vit_published':
			$v = (string) get_post_meta( $post_id, $col, true );
			echo $v ? esc_html( mysql2date( 'd.m.Y', $v ) ) : '<span style="color:#c00">—</span>';
			break;
	}
}, 10, 2 );

add_filter( 'manage_edit-vit_document_sortable_columns', function ( array $c ): array {
	$c['vit_approved']  = 'vit_approved';
	$c['vit_published'] = 'vit_published';
	return $c;
} );

/* ── колонки списку педагогів ────────────────────────────── */

add_filter( 'manage_vit_staff_posts_columns', function ( array $cols ): array {
	$new = array( 'cb' => $cols['cb'], 'vit_photo' => 'Фото' );
	foreach ( $cols as $k => $v ) {
		if ( 'cb' !== $k ) {
			$new[ $k ] = $v;
		}
		if ( 'title' === $k ) {
			$new['vit_position'] = 'Посада';
			$new['vit_subjects'] = 'Предмети';
		}
	}
	return $new;
} );

add_action( 'manage_vit_staff_posts_custom_column', function ( string $col, int $post_id ): void {
	if ( 'vit_photo' === $col ) {
		echo has_post_thumbnail( $post_id )
			? get_the_post_thumbnail( $post_id, array( 48, 64 ), array( 'style' => 'border-radius:8px;object-fit:cover' ) )
			: '<span style="color:#c00">немає</span>';
		return;
	}
	if ( in_array( $col, array( 'vit_position', 'vit_subjects' ), true ) ) {
		echo esc_html( (string) get_post_meta( $post_id, $col, true ) ?: '—' );
	}
}, 10, 2 );

/* ── стилі позначок ──────────────────────────────────────── */

add_action( 'admin_head', function (): void {
	echo '<style>
		.vit-badge{display:inline-block;font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;white-space:nowrap}
		.vit-fresh{background:#DDF7E8;color:#0E8A4A}
		.vit-ok{background:#eef1f7;color:#56618A}
		.vit-due{background:#FFF5CC;color:#8A6400}
		.vit-overdue{background:#FFE4E0;color:#E8433A}
		.vit-nodate{background:#FFE4E0;color:#E8433A}
		.vit-archive{background:#f0f0f1;color:#777}
		#vitryla_watch .inside ul{margin:0}
		#vitryla_watch li{padding:7px 0;border-top:1px solid #f0f0f1}
		#vitryla_watch li:first-child{border-top:0}
		.vit-fin{padding:12px 14px;border-radius:8px;margin-bottom:14px}
		.vit-fin.ok{background:#DDF7E8}
		.vit-fin.warn{background:#FFF5CC}
		.vit-fin.bad{background:#FFE4E0}
	</style>';
} );

/* ── віджет на головній адмінки ──────────────────────────── */

add_action( 'wp_dashboard_setup', function (): void {
	wp_add_dashboard_widget( 'vitryla_watch', 'Прозорість: що потребує уваги', 'vitryla_dashboard_widget' );
} );

/**
 * Віджет контролю строків.
 */
function vitryla_dashboard_widget(): void {
	$fin = vitryla_finance_deadline();

	if ( $fin['published'] ) {
		$cls  = 'ok';
		$text = sprintf( 'Фінансову звітність за %s оприлюднено.', $fin['quarter'] );
	} elseif ( $fin['days_left'] < 0 ) {
		$cls  = 'bad';
		$text = sprintf(
			'Фінансову звітність за %s не оприлюднено, строк минув %s.',
			$fin['quarter'],
			$fin['deadline']
		);
	} else {
		$cls  = $fin['days_left'] <= 10 ? 'bad' : 'warn';
		$text = sprintf(
			'Фінансова звітність за %s — до %s, лишилось %d дн.',
			$fin['quarter'],
			$fin['deadline'],
			$fin['days_left']
		);
	}
	printf( '<div class="vit-fin %s"><strong>%s</strong><br><small>Закон «Про відкритість використання публічних коштів»: 35 днів після кварталу.</small></div>', esc_attr( $cls ), esc_html( $text ) );

	$docs = vitryla_docs_needing_attention();
	if ( ! $docs ) {
		echo '<p>За статтею 30 усе гаразд: прострочених документів немає.</p>';
		return;
	}

	echo '<ul>';
	foreach ( array_slice( $docs, 0, 12 ) as $d ) {
		$s = vitryla_doc_state( $d->ID );
		printf(
			'<li><span class="vit-badge vit-%s">%s</span> <a href="%s">%s</a></li>',
			esc_attr( $s['key'] ),
			esc_html( $s['label'] ),
			esc_url( (string) get_edit_post_link( $d->ID ) ),
			esc_html( $d->post_title ?: '(без назви)' )
		);
	}
	echo '</ul>';

	if ( count( $docs ) > 12 ) {
		printf( '<p><small>…і ще %d.</small></p>', count( $docs ) - 12 );
	}
	printf(
		'<p><a class="button" href="%s">Усі документи</a></p>',
		esc_url( admin_url( 'edit.php?post_type=vit_document' ) )
	);
}
