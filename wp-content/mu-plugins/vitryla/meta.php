<?php
/**
 * Поля записів. Без ACF — нативні register_post_meta плюс прості метабокси.
 * Менше плагінів = менше оновлень і менше дір у безпеці.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Схема полів. Типи: text, date, textarea, attachment, select, bool, post.
 */
function vitryla_fields(): array {
	return array(
		'vit_document' => array(
			'vit_number'    => array( 'label' => 'Номер документа', 'type' => 'text', 'hint' => 'Наприклад: наказ №243 від 11.02.2026' ),
			'vit_file'      => array( 'label' => 'Файл документа', 'type' => 'attachment', 'hint' => 'PDF або DOCX. Вбудований файл краще за голе посилання.' ),
			'vit_approved'  => array( 'label' => 'Дата затвердження', 'type' => 'date', 'hint' => 'Від неї рахується десятиденний строк оприлюднення.' ),
			'vit_published' => array( 'label' => 'Дата оприлюднення', 'type' => 'date', 'hint' => 'Коли документ фактично з’явився на сайті.' ),
			'vit_valid'     => array(
				'label'   => 'Стан',
				'type'    => 'select',
				'options' => array( 'current' => 'Чинний', 'archive' => 'Архівний' ),
				'default' => 'current',
			),
		),

		'vit_staff' => array(
			'vit_position'       => array( 'label' => 'Посада', 'type' => 'text' ),
			'vit_subjects'       => array( 'label' => 'Предмети', 'type' => 'text', 'hint' => 'Через кому' ),
			'vit_classes'        => array( 'label' => 'Класи', 'type' => 'text' ),
			'vit_category'       => array( 'label' => 'Кваліфікаційна категорія', 'type' => 'text' ),
			'vit_ped_title'      => array( 'label' => 'Педагогічне звання', 'type' => 'text' ),
			'vit_homeroom'       => array( 'label' => 'Класне керівництво', 'type' => 'text' ),
			'vit_photo_consent'  => array( 'label' => 'Є згода на публікацію фото', 'type' => 'bool' ),
		),

		'vit_club' => array(
			'vit_leader'  => array( 'label' => 'Керівник', 'type' => 'post', 'post_type' => 'vit_staff' ),
			'vit_classes' => array( 'label' => 'Для яких класів', 'type' => 'text' ),
			'vit_day'     => array( 'label' => 'День', 'type' => 'text', 'hint' => 'Наприклад: вівторок, четвер' ),
			'vit_time'    => array( 'label' => 'Час', 'type' => 'text', 'hint' => 'Наприклад: 14:30–15:30' ),
			'vit_room'    => array( 'label' => 'Кабінет', 'type' => 'text' ),
		),
	);
}

/**
 * Реєстрація полів — щоб вони були в REST і доступні блокам.
 */
add_action( 'init', function (): void {
	foreach ( vitryla_fields() as $post_type => $fields ) {
		foreach ( $fields as $key => $f ) {
			$type = in_array( $f['type'], array( 'attachment', 'post' ), true ) ? 'integer'
				: ( 'bool' === $f['type'] ? 'boolean' : 'string' );

			register_post_meta( $post_type, $key, array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'integer' === $type ? 'absint' : ( 'boolean' === $type ? 'rest_sanitize_boolean' : 'sanitize_text_field' ),
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			) );
		}
	}
}, 11 );

/**
 * Метабокси.
 */
add_action( 'add_meta_boxes', function (): void {
	foreach ( array_keys( vitryla_fields() ) as $pt ) {
		add_meta_box( 'vitryla_fields', 'Дані запису', 'vitryla_render_metabox', $pt, 'normal', 'high' );
	}
} );

/**
 * Малює метабокс.
 *
 * @param WP_Post $post Запис.
 */
function vitryla_render_metabox( WP_Post $post ): void {
	$fields = vitryla_fields()[ $post->post_type ] ?? array();
	wp_nonce_field( 'vitryla_save_fields', 'vitryla_nonce' );

	echo '<style>
		.vit-f{display:grid;grid-template-columns:230px 1fr;gap:14px 18px;align-items:start;margin:14px 0}
		.vit-f label{font-weight:600;padding-top:6px}
		.vit-f .hint{display:block;color:#666;font-weight:400;font-size:12px;margin-top:3px}
		.vit-f input[type=text],.vit-f input[type=date],.vit-f select{width:100%;max-width:420px}
		.vit-file{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
	</style>';

	echo '<div class="vit-f">';
	foreach ( $fields as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		if ( '' === $val && isset( $f['default'] ) ) {
			$val = $f['default'];
		}

		printf(
			'<label for="%s">%s%s</label><div>',
			esc_attr( $key ),
			esc_html( $f['label'] ),
			isset( $f['hint'] ) ? '<span class="hint">' . esc_html( $f['hint'] ) . '</span>' : ''
		);

		switch ( $f['type'] ) {
			case 'date':
				printf( '<input type="date" id="%1$s" name="%1$s" value="%2$s">', esc_attr( $key ), esc_attr( $val ) );
				break;

			case 'bool':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> так</label>',
					esc_attr( $key ),
					checked( (bool) $val, true, false )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%1$s">', esc_attr( $key ) );
				foreach ( $f['options'] as $ov => $ol ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $ov ), selected( $val, $ov, false ), esc_html( $ol ) );
				}
				echo '</select>';
				break;

			case 'post':
				$items = get_posts( array(
					'post_type'      => $f['post_type'],
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				) );
				printf( '<select id="%1$s" name="%1$s"><option value="">— не вказано —</option>', esc_attr( $key ) );
				foreach ( $items as $it ) {
					printf( '<option value="%d" %s>%s</option>', $it->ID, selected( (int) $val, $it->ID, false ), esc_html( $it->post_title ) );
				}
				echo '</select>';
				break;

			case 'attachment':
				$url = $val ? wp_get_attachment_url( (int) $val ) : '';
				printf(
					'<div class="vit-file">
						<input type="hidden" id="%1$s" name="%1$s" value="%2$s">
						<button type="button" class="button vit-pick" data-target="%1$s">Обрати файл</button>
						<button type="button" class="button-link vit-clear" data-target="%1$s">прибрати</button>
						<span class="vit-name">%3$s</span>
					</div>',
					esc_attr( $key ),
					esc_attr( (string) $val ),
					$url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( basename( $url ) ) . '</a>' : '<em>файл не обрано</em>'
				);
				break;

			default:
				printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s">', esc_attr( $key ), esc_attr( $val ) );
		}

		echo '</div>';
	}
	echo '</div>';
}

/**
 * Медіабібліотека для поля «файл».
 */
add_action( 'admin_enqueue_scripts', function ( $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'jquery-core', <<<'JS'
jQuery(function($){
  $(document).on('click', '.vit-pick', function(){
    var target = $(this).data('target'), box = $(this).closest('.vit-file');
    var frame = wp.media({ title: 'Оберіть файл документа', multiple: false,
      button: { text: 'Використати' } });
    frame.on('select', function(){
      var a = frame.state().get('selection').first().toJSON();
      $('#' + target).val(a.id);
      box.find('.vit-name').html('<a href="' + a.url + '" target="_blank" rel="noopener">' + a.filename + '</a>');
    });
    frame.open();
  });
  $(document).on('click', '.vit-clear', function(){
    var box = $(this).closest('.vit-file');
    $('#' + $(this).data('target')).val('');
    box.find('.vit-name').html('<em>файл не обрано</em>');
  });
});
JS
	);
} );

/**
 * Збереження.
 *
 * @param int $post_id Запис.
 */
add_action( 'save_post', function ( int $post_id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['vitryla_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vitryla_nonce'] ) ), 'vitryla_save_fields' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = vitryla_fields()[ get_post_type( $post_id ) ] ?? array();
	foreach ( $fields as $key => $f ) {
		if ( 'bool' === $f['type'] ) {
			update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? 1 : 0 );
			continue;
		}
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		if ( in_array( $f['type'], array( 'attachment', 'post' ), true ) ) {
			$raw = $raw ? absint( $raw ) : '';
		}
		update_post_meta( $post_id, $key, $raw );
	}
}, 10, 1 );
