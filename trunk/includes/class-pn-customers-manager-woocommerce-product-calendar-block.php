<?php
/**
 * WooCommerce Product Calendar Gutenberg Block & Shortcode.
 *
 * Displays an interactive calendar and product excerpts sorted by proximity in time
 * for products that have Product Calendar enabled.
 *
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce_Product_Calendar_Block
{
	private static $instance = null;

	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct()
	{
		add_action('init', [$this, 'register_block']);
		add_shortcode('pn-customers-manager-product-calendar', [$this, 'render_shortcode']);
	}

	/**
	 * Register the Gutenberg block and localize product data.
	 */
	public function register_block()
	{
		if (!function_exists('register_block_type')) {
			return;
		}

		if (!class_exists('WooCommerce')) {
			return;
		}

		// Register Material Icons stylesheet
		if (!wp_style_is('wph-material-icons-outlined', 'registered')) {
			wp_register_style(
				'wph-material-icons-outlined',
				PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css',
				[],
				defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0'
			);
		}

		// Register Tooltips script
		if (!wp_script_is('pn-customers-manager-tooltips', 'registered')) {
			wp_register_script(
				'pn-customers-manager-tooltips',
				PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-tooltips.js',
				['jquery'],
				defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0',
				true
			);
		}

		// Register block editor script
		wp_register_script(
			'pn-customers-manager-product-calendar-block',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-product-calendar.js',
			['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0',
			true
		);

		// Register frontend script
		wp_register_script(
			'pn-customers-manager-product-calendar-frontend',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-product-calendar-frontend.js',
			['jquery', 'pn-customers-manager-tooltips'],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0',
			true
		);

		// Register CSS
		wp_register_style(
			'pn-customers-manager-calendar-booking-style',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-cm-calendar-booking.css',
			['wph-material-icons-outlined'],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0'
		);

		// Fetch ONLY products that have Product Calendar enabled (_pn_cm_woo_calendar_enabled = 'yes')
		$product_posts = get_posts([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => '_pn_cm_woo_calendar_enabled',
					'value'   => 'yes',
					'compare' => '=',
				],
			],
		]);

		$calendar_products = [];
		foreach ($product_posts as $p) {
			$weeks = get_post_meta($p->ID, '_pn_cm_woo_calendar_weeks', true);
			$calendar_products[] = [
				'id'    => $p->ID,
				'title' => $p->post_title,
				'weeks' => is_array($weeks) ? $weeks : [],
			];
		}

		wp_localize_script('pn-customers-manager-product-calendar-block', 'pnCmProductCalendarBlock', [
			'products' => $calendar_products,
			'i18n'     => [
				'title'           => __('Calendario de producto', 'pn-customers-manager'),
				'selectProducts'  => __('Seleccionar productos a mostrar', 'pn-customers-manager'),
				'noProductsFound' => __('No hay productos con Calendario de producto activado.', 'pn-customers-manager'),
				'heading'         => __('Título del bloque', 'pn-customers-manager'),
				'buttonText'      => __('Texto del botón CTA', 'pn-customers-manager'),
				'defaultBtnText'  => __('Comprar producto', 'pn-customers-manager'),
				'selectAll'       => __('Seleccionar todos', 'pn-customers-manager'),
				'deselectAll'     => __('Deseleccionar todos', 'pn-customers-manager'),
			]
		]);

		register_block_type('pn-customers-manager/product-calendar', [
			'editor_script'   => 'pn-customers-manager-product-calendar-block',
			'style'           => 'pn-customers-manager-calendar-booking-style',
			'script'          => 'pn-customers-manager-product-calendar-frontend',
			'render_callback' => [$this, 'render_block'],
			'attributes'      => [
				'selectedProducts' => [
					'type'    => 'array',
					'default' => null,
				],
				'heading' => [
					'type'    => 'string',
					'default' => '',
				],
				'buttonText' => [
					'type'    => 'string',
					'default' => 'Comprar producto',
				],
			],
		]);
	}

	/**
	 * Shortcode handler wrapper.
	 */
	public function render_shortcode($atts)
	{
		$atts = shortcode_atts([
			'products'    => '',
			'heading'     => '',
			'button_text' => 'Comprar producto',
		], $atts, 'pn-customers-manager-product-calendar');

		$selected_products = null;
		if (!empty($atts['products'])) {
			$selected_products = array_map('intval', explode(',', $atts['products']));
		}

		return $this->render_block([
			'selectedProducts' => $selected_products,
			'heading'          => $atts['heading'],
			'buttonText'       => $atts['button_text'],
		]);
	}

	/**
	 * Render callback for the block and shortcode.
	 */
	public function render_block($attributes)
	{
		if (!class_exists('WooCommerce')) {
			return '';
		}

		wp_enqueue_style('wph-material-icons-outlined');
		wp_enqueue_style('pn-customers-manager-calendar-booking-style');
		wp_enqueue_script('pn-customers-manager-tooltips');
		wp_enqueue_script('pn-customers-manager-product-calendar-frontend');

		$heading     = !empty($attributes['heading']) ? sanitize_text_field($attributes['heading']) : '';
		$button_text = !empty($attributes['buttonText']) ? sanitize_text_field($attributes['buttonText']) : __('Comprar producto', 'pn-customers-manager');

		// Resolve selected products
		if (!array_key_exists('selectedProducts', $attributes) || is_null($attributes['selectedProducts'])) {
			// Default (e.g. shortcode without products parameter): fetch all enabled calendar products
			$selected_ids = get_posts([
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'     => '_pn_cm_woo_calendar_enabled',
						'value'   => 'yes',
						'compare' => '=',
					],
				],
			]);
		} else {
			// Gutenberg block or explicit shortcode selection: use exact array of selected IDs
			$selected_ids = array_map('intval', (array) $attributes['selectedProducts']);
		}

		if (empty($selected_ids)) {
			return '';
		}

		$today = gmdate('Y-m-d');
		$products_data = [];
		$all_dates_map = []; // 'YYYY-MM-DD' => [ 'pids' => [123, 456], 'titles' => ['Title 1', 'Title 2'], 'tooltip' => 'Title 1, Title 2' ]

		foreach ($selected_ids as $pid) {
			if (get_post_status($pid) !== 'publish') {
				continue;
			}
			if (get_post_meta($pid, '_pn_cm_woo_calendar_enabled', true) !== 'yes') {
				continue;
			}

			$title = get_the_title($pid);
			$weeks = get_post_meta($pid, '_pn_cm_woo_calendar_weeks', true);
			$product_dates = [];
			if (is_array($weeks)) {
				foreach ($weeks as $w) {
					if (!empty($w['dates']) && is_array($w['dates'])) {
						foreach ($w['dates'] as $d) {
							$d_str = sanitize_text_field($d);
							$product_dates[] = $d_str;

							if (!isset($all_dates_map[$d_str])) {
								$all_dates_map[$d_str] = [
									'pids'   => [],
									'titles' => [],
								];
							}
							if (!in_array($pid, $all_dates_map[$d_str]['pids'], true)) {
								$all_dates_map[$d_str]['pids'][] = $pid;
								$all_dates_map[$d_str]['titles'][] = $title;
							}
						}
					}
				}
			}

			$product_dates = array_unique($product_dates);
			sort($product_dates);

			// Calculate earliest upcoming date (>= today), or earliest date overall if all in past
			$upcoming_dates = array_filter($product_dates, function ($d) use ($today) {
				return $d >= $today;
			});

			if (!empty($upcoming_dates)) {
				$earliest_date = reset($upcoming_dates);
			} elseif (!empty($product_dates)) {
				$earliest_date = reset($product_dates);
			} else {
				$earliest_date = '9999-12-31';
			}

			// Format readable date badge
			$date_badge = '';
			if (!empty($product_dates)) {
				$min_d = reset($product_dates);
				$max_d = end($product_dates);
				if ($min_d === $max_d) {
					$date_badge = date_i18n(get_option('date_format'), strtotime($min_d));
				} else {
					$date_badge = date_i18n('j M', strtotime($min_d)) . ' - ' . date_i18n('j M Y', strtotime($max_d));
				}
			}

			$wc_product = wc_get_product($pid);
			$thumb_url  = get_the_post_thumbnail_url($pid, 'medium');

			// Get excerpt
			$excerpt = get_the_excerpt($pid);
			if (empty($excerpt) && $wc_product) {
				$excerpt = $wc_product->get_short_description();
			}

			$products_data[] = [
				'id'            => $pid,
				'title'         => $title,
				'permalink'     => get_permalink($pid),
				'excerpt'       => $excerpt,
				'thumb_url'     => $thumb_url,
				'price_html'    => $wc_product ? $wc_product->get_price_html() : '',
				'earliest_date' => $earliest_date,
				'dates'         => $product_dates,
				'date_badge'    => $date_badge,
			];
		}

		if (empty($products_data)) {
			return '';
		}

		// Prepare tooltips text for each date
		foreach ($all_dates_map as $d_str => &$info) {
			$info['tooltip'] = implode(' • ', $info['titles']);
		}
		unset($info);

		// Sort products chronologically by earliest date (closest in time first)
		usort($products_data, function ($a, $b) {
			return strcmp($a['earliest_date'], $b['earliest_date']);
		});

		// Determine initial month to display on calendar
		$initial_year  = (int) gmdate('Y');
		$initial_month = (int) gmdate('n');

		if (!empty($all_dates_map)) {
			$sorted_active_dates = array_keys($all_dates_map);
			sort($sorted_active_dates);
			$upcoming_active_dates = array_filter($sorted_active_dates, function ($d) use ($today) {
				return $d >= $today;
			});
			$target_date = !empty($upcoming_active_dates) ? reset($upcoming_active_dates) : reset($sorted_active_dates);
			if ($target_date) {
				$parts = explode('-', $target_date);
				if (count($parts) === 3) {
					$initial_year  = (int) $parts[0];
					$initial_month = (int) $parts[1];
				}
			}
		}

		$months_es = [
			1 => __('Enero', 'pn-customers-manager'),
			2 => __('Febrero', 'pn-customers-manager'),
			3 => __('Marzo', 'pn-customers-manager'),
			4 => __('Abril', 'pn-customers-manager'),
			5 => __('Mayo', 'pn-customers-manager'),
			6 => __('Junio', 'pn-customers-manager'),
			7 => __('Julio', 'pn-customers-manager'),
			8 => __('Agosto', 'pn-customers-manager'),
			9 => __('Septiembre', 'pn-customers-manager'),
			10 => __('Octubre', 'pn-customers-manager'),
			11 => __('Noviembre', 'pn-customers-manager'),
			12 => __('Diciembre', 'pn-customers-manager'),
		];

		$weekdays_es = [
			__('Lu', 'pn-customers-manager'),
			__('Ma', 'pn-customers-manager'),
			__('Mi', 'pn-customers-manager'),
			__('Ju', 'pn-customers-manager'),
			__('Vi', 'pn-customers-manager'),
			__('Sá', 'pn-customers-manager'),
			__('Do', 'pn-customers-manager'),
		];

		$unique_id = 'pn-cm-product-cal-' . wp_rand(1000, 9999);
		$dates_json = wp_json_encode($all_dates_map);
		$months_json = wp_json_encode($months_es);
		$weekdays_json = wp_json_encode($weekdays_es);

		$main_btn_color = get_option('pn_customers_manager_color_main', '#0000aa');

		ob_start();
		?>
		<div class="pn-cm-product-calendar-block-wrap" id="<?php echo esc_attr($unique_id); ?>"
		     data-year="<?php echo esc_attr($initial_year); ?>"
		     data-month="<?php echo esc_attr($initial_month); ?>"
		     data-dates="<?php echo esc_attr($dates_json); ?>"
		     data-months="<?php echo esc_attr($months_json); ?>"
		     data-weekdays="<?php echo esc_attr($weekdays_json); ?>">

			<?php if (!empty($heading)): ?>
				<h3 class="pn-cm-product-calendar-heading"><?php echo esc_html($heading); ?></h3>
			<?php endif; ?>

			<!-- Calendario de 1 mes centrado con navegación de Material Icons -->
			<div class="pn-cm-calendar-months-nav-wrap">
				<div class="pn-cm-calendar-months">
					<div class="pn-cm-calendar-month">
						<div class="pn-cm-calendar-month-header">
							<button type="button" class="pn-cm-calendar-nav-btn pn-cm-calendar-prev-month" aria-label="<?php esc_attr_e('Previous month', 'pn-customers-manager'); ?>">
								<i class="material-icons-outlined">chevron_left</i>
							</button>
							<div class="pn-cm-calendar-month-title">
								<?php echo esc_html(($months_es[$initial_month] ?? '') . ' ' . $initial_year); ?>
							</div>
							<button type="button" class="pn-cm-calendar-nav-btn pn-cm-calendar-next-month" aria-label="<?php esc_attr_e('Next month', 'pn-customers-manager'); ?>">
								<i class="material-icons-outlined">chevron_right</i>
							</button>
						</div>
						<div class="pn-cm-calendar-month-grid">
							<?php foreach ($weekdays_es as $wd): ?>
								<div class="pn-cm-calendar-weekday"><?php echo esc_html($wd); ?></div>
							<?php endforeach; ?>

							<?php
							// Initial month grid generation
							$first_day = new DateTime(sprintf('%04d-%02d-01', $initial_year, $initial_month));
							$start_dow = ((int) $first_day->format('N') - 1); // 0=Mon, 6=Sun
							$days_in_month = (int) $first_day->format('t');

							for ($e = 0; $e < $start_dow; $e++) {
								echo '<div class="pn-cm-calendar-day pn-cm-calendar-day--empty"></div>';
							}

							for ($d = 1; $d <= $days_in_month; $d++) {
								$date_str = sprintf('%04d-%02d-%02d', $initial_year, $initial_month, $d);
								$is_active = isset($all_dates_map[$date_str]);
								if ($is_active) {
									$info = $all_dates_map[$date_str];
									$pids_str = implode(',', $info['pids']);
									$tooltip_str = esc_attr($info['tooltip']);
									$cls = 'pn-cm-calendar-day pn-cm-calendar-day--available pn-customers-manager-tooltip';
									echo '<div class="' . esc_attr($cls) . '" data-date="' . esc_attr($date_str) . '" data-product-ids="' . esc_attr($pids_str) . '" data-pn-customers-manager-tooltip="' . esc_attr($tooltip_str) . '">';
								} else {
									echo '<div class="pn-cm-calendar-day pn-cm-calendar-day--inactive" data-date="' . esc_attr($date_str) . '">';
								}
								echo '<span class="pn-cm-calendar-day-num">' . esc_html($d) . '</span>';
								echo '</div>';
							}
							?>
						</div>
					</div>
				</div>
			</div>

			<!-- Lista de extractos de productos ordenados por cercanía en el tiempo -->
			<div class="pn-cm-product-calendar-excerpts">
				<?php foreach ($products_data as $p): ?>
					<div class="pn-cm-product-excerpt-card" data-product-id="<?php echo esc_attr($p['id']); ?>">
						<?php if (!empty($p['thumb_url'])): ?>
							<div class="pn-cm-product-excerpt-thumb">
								<a href="<?php echo esc_url($p['permalink']); ?>">
									<img src="<?php echo esc_url($p['thumb_url']); ?>" alt="<?php echo esc_attr($p['title']); ?>" />
								</a>
							</div>
						<?php endif; ?>
						<div class="pn-cm-product-excerpt-content">
							<h4 class="pn-cm-product-excerpt-title">
								<a href="<?php echo esc_url($p['permalink']); ?>"><?php echo esc_html($p['title']); ?></a>
							</h4>

							<?php if (!empty($p['date_badge'])): ?>
								<div class="pn-cm-product-excerpt-dates">
									<i class="material-icons-outlined">calendar_today</i>
									<span><?php echo esc_html($p['date_badge']); ?></span>
								</div>
							<?php endif; ?>

							<?php if (!empty($p['excerpt'])): ?>
								<div class="pn-cm-product-excerpt-text">
									<?php echo wp_kses_post(wpautop($p['excerpt'])); ?>
								</div>
							<?php endif; ?>

							<div class="pn-cm-product-excerpt-cta">
								<?php if (!empty($p['price_html'])): ?>
									<span class="pn-cm-product-excerpt-price"><?php echo wp_kses_post($p['price_html']); ?></span>
								<?php endif; ?>

								<a href="<?php echo esc_url($p['permalink']); ?>" class="pn-customers-manager-btn pn-cm-product-cta-btn" style="background-color: <?php echo esc_attr($main_btn_color); ?>;">
									<span><?php echo esc_html($button_text); ?></span>
									<i class="material-icons-outlined" style="font-size: 16px; margin-left: 4px; vertical-align: middle;">arrow_forward</i>
								</a>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
