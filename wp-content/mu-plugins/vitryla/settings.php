<?php
/**
 * Сторінка налаштувань «Ліцей»: дані, які часто змінюються і не є
 * окремими записами — контакти, смуга «Сьогодні», цифри на головній.
 *
 * Усі поля свідомо лишаються порожніми, доки не надійдуть відповіді
 * від адміністрації. На сайті замість порожнього значення показується
 * «не заповнено», а не вигадана цифра.
 *
 * @package vitryla
 */

defined( 'ABSPATH' ) || exit;

const VITRYLA_SETTINGS = array(

	'vitryla_contacts' => array(
		'title'  => 'Контакти',
		'fields' => array(
			'phone'        => array( 'Телефон приймальні', 'Публікується у підвалі на всіх сторінках' ),
			'phone_extra'  => array( 'Інші телефони', 'Черговий, медсестра — через кому' ),
			'office_hours' => array( 'Години роботи приймальні', '' ),
			'reception'    => array( 'Графік особистого прийому громадян', 'Вимога Закону «Про звернення громадян»' ),
			'public_info'  => array( 'Відповідальний за доступ до публічної інформації', '' ),
		),
	),

	'vitryla_today' => array(
		'title'  => 'Смуга «Сьогодні в ліцеї»',
		'fields' => array(
			'format'      => array( 'Формат навчання', 'Наприклад: очно, 5 днів' ),
			'format_note' => array( '↳ уточнення', 'Наприклад: перший урок о 8:30' ),
			'alarm'       => array( 'Під час тривоги', 'Наприклад: переходимо в укриття' ),
			'alarm_note'  => array( '↳ уточнення', '' ),
			'food'        => array( 'Харчування', '' ),
			'food_note'   => array( '↳ уточнення', '' ),
			'office'      => array( 'Приймальня', '' ),
			'office_note' => array( '↳ уточнення', '' ),
		),
	),

	'vitryla_numbers' => array(
		'title'  => 'Ліцей у цифрах',
		'fields' => array(
			'founded'       => array( 'Рік заснування', '' ),
			'pupils'        => array( 'Кількість учнів', '' ),
			'pupils_date'   => array( '↳ станом на', 'Дата поруч із цифрою — головна вимога до цього блоку' ),
			'classes'       => array( 'Кількість класів', '' ),
			'classes_date'  => array( '↳ станом на', '' ),
			'staff'         => array( 'Кількість педагогів', '' ),
			'staff_date'    => array( '↳ станом на', '' ),
			'shelters'      => array( 'Обладнаних укриттів', '' ),
			'shelters_note' => array( '↳ уточнення', 'Наприклад: на 400 осіб' ),
		),
	),
);

add_action( 'admin_menu', function (): void {
	add_menu_page( 'Дані ліцею', 'Дані ліцею', 'manage_options', 'vitryla', 'vitryla_settings_page', 'dashicons-admin-site-alt3', 20 );
} );

add_action( 'admin_init', function (): void {
	foreach ( array_keys( VITRYLA_SETTINGS ) as $group ) {
		register_setting( 'vitryla', $group, array(
			'type'              => 'array',
			'sanitize_callback' => function ( $v ) {
				return is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : array();
			},
			'default'           => array(),
		) );
	}
} );

/**
 * Малює сторінку налаштувань.
 */
function vitryla_settings_page(): void {
	?>
	<div class="wrap">
		<h1>Дані ліцею</h1>
		<p style="max-width:70ch">Те, що змінюється кілька разів на рік і показується одразу на багатьох сторінках.
		Порожнє поле — це не помилка: на сайті замість нього з’явиться позначка «не заповнено», і це чесніше, ніж застаріла цифра.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'vitryla' ); ?>

			<?php foreach ( VITRYLA_SETTINGS as $group => $cfg ) :
				$vals = (array) get_option( $group, array() ); ?>
				<h2><?php echo esc_html( $cfg['title'] ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $cfg['fields'] as $key => [$label, $hint] ) : ?>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( "$group-$key" ); ?>"><?php echo esc_html( $label ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text"
									id="<?php echo esc_attr( "$group-$key" ); ?>"
									name="<?php echo esc_attr( "{$group}[{$key}]" ); ?>"
									value="<?php echo esc_attr( $vals[ $key ] ?? '' ); ?>">
								<?php if ( $hint ) : ?><p class="description"><?php echo esc_html( $hint ); ?></p><?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>


			<?php submit_button( 'Зберегти' ); ?>
		</form>

		<?php $demo = function_exists( 'vitryla_demo_count' ) ? vitryla_demo_count() : 0; ?>
		<?php if ( isset( $_GET['purged'] ) ) : ?>
			<div class="notice notice-success"><p>Прибрано демонстраційних записів: <?php echo (int) $_GET['purged']; ?>.</p></div>
		<?php endif; ?>

		<h2>Демонстраційне наповнення</h2>
		<?php if ( $demo ) : ?>
			<p>Зараз на сайті <strong><?php echo (int) $demo; ?></strong> демонстраційних записів — новини, педагоги, гуртки й документи з вигаданими даними.
			Вони потрібні лише для того, щоб побачити, як виглядатиме наповнений сайт.</p>
			<p><strong>Прибрати їх обов’язково до того, як сайт стане доступним людям.</strong> Справжні матеріали це не зачепить —
			видаляються лише записи з позначкою демонстраційних.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				onsubmit="return confirm('Видалити всі демонстраційні записи? Дію не можна скасувати.');">
				<input type="hidden" name="action" value="vitryla_purge_demo">
				<?php wp_nonce_field( 'vitryla_purge_demo' ); ?>
				<?php submit_button( 'Прибрати демонстраційне наповнення', 'delete' ); ?>
			</form>
		<?php else : ?>
			<p>Демонстраційних записів немає — на сайті лише справжні матеріали.</p>
		<?php endif; ?>
	</div>
	<?php
}
