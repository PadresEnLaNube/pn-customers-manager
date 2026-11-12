<?php
/**
 * Social Media Manager.
 *
 * Manages social media links display in Linktree style.
 *
 * @link       padresenlanube.com/
 * @since      1.1.151
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Social_Media
{

	/**
	 * Managed pages for social media.
	 *
	 * @var array
	 */
	private static $social_media_pages = [
		'pn_customers_manager_social_media_page' => [
			'shortcode' => 'pn-customers-manager-social-media',
			'block' => 'pn-customers-manager/social-media',
			'label' => 'Social Media',
		],
	];

	/**
	 * Get social media managed pages configuration.
	 *
	 * @return array
	 */
	public static function get_social_media_pages()
	{
		return self::$social_media_pages;
	}

	/**
	 * Get the settings section options array for the social media section.
	 *
	 * @return array
	 */
	public static function get_settings_section()
	{
		$options = [];

		// ═══════════════════════════════════════════════════════════════
		// SUBSECTION: General Settings
		// ═══════════════════════════════════════════════════════════════
		$options['pn_customers_manager_social_media_general_subsection_start'] = [
			'id' => 'pn_customers_manager_social_media_general_subsection_start',
			'section' => 'start',
			'label' => __('General Settings', 'pn-customers-manager'),
			'description' => __('General configuration for your Linktree-style page.', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_title'] = [
			'id' => 'pn_customers_manager_social_media_title',
			'input' => 'input',
			'type' => 'text',
			'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label' => __('Title', 'pn-customers-manager'),
			'placeholder' => __('Follow us', 'pn-customers-manager'),
			'value' => __('Follow us', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_subtitle'] = [
			'id' => 'pn_customers_manager_social_media_subtitle',
			'input' => 'textarea',
			'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label' => __('Subtitle', 'pn-customers-manager'),
			'placeholder' => __('Connect with us', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_profile_image'] = [
			'id' => 'pn_customers_manager_social_media_profile_image',
			'input' => 'image',
			'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label' => __('Profile Image', 'pn-customers-manager'),
			'description' => __('Optional profile image displayed at the top.', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_enable_share'] = [
			'id' => 'pn_customers_manager_social_media_enable_share',
			'input' => 'input',
			'type' => 'checkbox',
			'value' => 'on',
			'label' => __('Enable Share Button', 'pn-customers-manager'),
			'description' => __('Show a share button that opens a popup with sharing options.', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_fetch_images'] = [
			'id' => 'pn_customers_manager_social_media_fetch_images',
			'input' => 'input',
			'type' => 'checkbox',
			'value' => 'on',
			'label' => __('Fetch Link Images', 'pn-customers-manager'),
			'description' => __('Try to load images from Open Graph meta tags of linked pages.', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_general_subsection_end'] = [
			'id' => 'pn_customers_manager_social_media_general_subsection_end',
			'section' => 'end',
		];

		// ═══════════════════════════════════════════════════════════════
		// SUBSECTION: Custom Links
		// ═══════════════════════════════════════════════════════════════
		$options['pn_customers_manager_social_media_links_subsection_start'] = [
			'id' => 'pn_customers_manager_social_media_links_subsection_start',
			'section' => 'start',
			'label' => __('Custom Links', 'pn-customers-manager'),
			'description' => __('Add custom links that will appear as large buttons. Format: One link per line as JSON: {"title":"My Link","url":"https://...","description":"Optional"}', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_custom_links'] = [
			'id' => 'pn_customers_manager_social_media_custom_links',
			'input' => 'html_multi',
			'label' => __('Custom Links', 'pn-customers-manager'),
			'description' => __('Add custom links that will appear as large buttons at the top of your social media page.', 'pn-customers-manager'),
			'html_multi_fields' => [
				[
					'id' => 'pn_customers_manager_social_media_custom_link_title',
					'input' => 'input',
					'type' => 'text',
					'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label' => __('Title', 'pn-customers-manager'),
					'placeholder' => __('Link title', 'pn-customers-manager'),
				],
				[
					'id' => 'pn_customers_manager_social_media_custom_link_url',
					'input' => 'input',
					'type' => 'url',
					'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label' => __('URL', 'pn-customers-manager'),
					'placeholder' => 'https://',
				],
				[
					'id' => 'pn_customers_manager_social_media_custom_link_icon',
					'input' => 'input',
					'type' => 'text',
					'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label' => __('Icon (optional)', 'pn-customers-manager'),
					'placeholder' => 'link.svg',
					'description' => __('SVG filename from assets/media/socials/white/', 'pn-customers-manager'),
				],
				[
					'id' => 'pn_customers_manager_social_media_custom_link_description',
					'input' => 'textarea',
					'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
					'label' => __('Description (optional)', 'pn-customers-manager'),
					'placeholder' => __('Optional description', 'pn-customers-manager'),
				],
			],
		];

		$options['pn_customers_manager_social_media_links_subsection_end'] = [
			'id' => 'pn_customers_manager_social_media_links_subsection_end',
			'section' => 'end',
		];

		// ═══════════════════════════════════════════════════════════════
		// SUBSECTION: Social Networks
		// ═══════════════════════════════════════════════════════════════
		$options['pn_customers_manager_social_media_networks_subsection_start'] = [
			'id' => 'pn_customers_manager_social_media_networks_subsection_start',
			'section' => 'start',
			'label' => __('Social Networks', 'pn-customers-manager'),
			'description' => __('Social media links that will appear as circular icons at the bottom.', 'pn-customers-manager'),
		];

		// Social networks
		$social_networks = [
			'facebook' => ['icon' => 'facebook', 'label' => 'Facebook'],
			'instagram' => ['icon' => 'camera_alt', 'label' => 'Instagram'],
			'twitter' => ['icon' => 'flutter_dash', 'label' => 'Twitter / X'],
			'linkedin' => ['icon' => 'work', 'label' => 'LinkedIn'],
			'youtube' => ['icon' => 'play_circle', 'label' => 'YouTube'],
			'tiktok' => ['icon' => 'music_note', 'label' => 'TikTok'],
			'pinterest' => ['icon' => 'push_pin', 'label' => 'Pinterest'],
			'whatsapp' => ['icon' => 'chat', 'label' => 'WhatsApp'],
			'telegram' => ['icon' => 'send', 'label' => 'Telegram'],
			'email' => ['icon' => 'email', 'label' => 'Email'],
			'website' => ['icon' => 'language', 'label' => __('Website', 'pn-customers-manager')],
		];

		foreach ($social_networks as $network => $data) {
			$options['pn_customers_manager_social_media_' . $network] = [
				'id' => 'pn_customers_manager_social_media_' . $network,
				'input' => 'input',
				'type' => 'url',
				'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
				'label' => $data['label'],
				'placeholder' => 'https://',
			];
		}

		$options['pn_customers_manager_social_media_networks_subsection_end'] = [
			'id' => 'pn_customers_manager_social_media_networks_subsection_end',
			'section' => 'end',
		];

		// WooCommerce Products subsection
		$options['pn_customers_manager_social_media_products_subsection_start'] = [
			'id' => 'pn_customers_manager_social_media_products_subsection_start',
			'section' => 'start',
			'label' => __('WooCommerce Products', 'pn-customers-manager'),
			'description' => __('Display products from your WooCommerce store with the same style as custom links.', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_enable_products'] = [
			'id' => 'pn_customers_manager_social_media_enable_products',
			'input' => 'input',
			'type' => 'checkbox',
			'value' => 'on',
			'label' => __('Enable Products Display', 'pn-customers-manager'),
			'description' => __('Show products from a selected category.', 'pn-customers-manager'),
		];

		// Get WooCommerce product categories
		$product_categories = ['' => __('-- Select a category --', 'pn-customers-manager')];
		if (class_exists('WooCommerce')) {
			$terms = get_terms([
				'taxonomy' => 'product_cat',
				'hide_empty' => false,
			]);
			if (!is_wp_error($terms) && !empty($terms)) {
				foreach ($terms as $term) {
					$product_categories[$term->term_id] = $term->name;
				}
			}
		}

		$options['pn_customers_manager_social_media_product_category'] = [
			'id' => 'pn_customers_manager_social_media_product_category',
			'input' => 'select',
			'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label' => __('Product Category', 'pn-customers-manager'),
			'description' => __('Select which product category to display.', 'pn-customers-manager'),
			'options' => $product_categories,
		];

		$options['pn_customers_manager_social_media_products_limit'] = [
			'id' => 'pn_customers_manager_social_media_products_limit',
			'input' => 'input',
			'type' => 'number',
			'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label' => __('Products Limit', 'pn-customers-manager'),
			'placeholder' => '5',
			'description' => __('Maximum number of products to display.', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_products_subsection_end'] = [
			'id' => 'pn_customers_manager_social_media_products_subsection_end',
			'section' => 'end',
		];

		// Colors subsection
		$options['pn_customers_manager_social_media_colors_subsection_start'] = [
			'id' => 'pn_customers_manager_social_media_colors_subsection_start',
			'section' => 'start',
			'label' => __('Button Colors', 'pn-customers-manager'),
			'description' => __('Customize the colors for the buttons on your social media page.', 'pn-customers-manager'),
		];

		$options['pn_customers_manager_social_media_button_color'] = [
			'id' => 'pn_customers_manager_social_media_button_color',
			'input' => 'input',
			'type' => 'color',
			'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label' => __('Button Background Color', 'pn-customers-manager'),
			'value' => '#ffffff',
			'placeholder' => '#ffffff',
		];

		$options['pn_customers_manager_social_media_button_text_color'] = [
			'id' => 'pn_customers_manager_social_media_button_text_color',
			'input' => 'input',
			'type' => 'color',
			'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'label' => __('Button Text Color', 'pn-customers-manager'),
			'value' => '#1a1a1a',
			'placeholder' => '#1a1a1a',
		];

		$options['pn_customers_manager_social_media_colors_subsection_end'] = [
			'id' => 'pn_customers_manager_social_media_colors_subsection_end',
			'section' => 'end',
		];

		$options['pn_customers_manager_social_media_subsection_end'] = [
			'id' => 'pn_customers_manager_social_media_subsection_end',
			'section' => 'end',
		];

		return $options;
	}

	/**
	 * Get SVG icon content.
	 *
	 * @param string $icon_filename SVG filename (e.g., 'facebook.svg').
	 * @param string $color Color for the icon (default: 'white', can be 'black' or hex color).
	 * @return string SVG content or empty string if not found.
	 */
	/**
	 * Get allowed HTML tags and attributes for SVG output sanitization.
	 *
	 * @return array Allowed HTML tags for wp_kses.
	 */
	private static function get_svg_kses_allowed()
	{
		return array(
			'svg'    => array(
				'xmlns'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'fill'        => true,
				'class'       => true,
				'style'       => true,
				'id'          => true,
				'version'     => true,
				'x'           => true,
				'y'           => true,
				'xml:space'   => true,
				'aria-hidden' => true,
				'role'        => true,
			),
			'path'   => array(
				'd'         => true,
				'fill'      => true,
				'style'     => true,
				'class'     => true,
				'id'        => true,
				'transform' => true,
			),
			'g'      => array(
				'fill'      => true,
				'style'     => true,
				'class'     => true,
				'id'        => true,
				'transform' => true,
			),
			'circle' => array(
				'cx'    => true,
				'cy'    => true,
				'r'     => true,
				'fill'  => true,
				'style' => true,
				'class' => true,
			),
			'rect'   => array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
				'fill'   => true,
				'style'  => true,
				'class'  => true,
			),
			'polygon' => array(
				'points' => true,
				'fill'   => true,
				'style'  => true,
				'class'  => true,
			),
			'polyline' => array(
				'points' => true,
				'fill'   => true,
				'style'  => true,
				'class'  => true,
			),
			'line'   => array(
				'x1'    => true,
				'y1'    => true,
				'x2'    => true,
				'y2'    => true,
				'style' => true,
				'class' => true,
			),
			'ellipse' => array(
				'cx'    => true,
				'cy'    => true,
				'rx'    => true,
				'ry'    => true,
				'fill'  => true,
				'style' => true,
				'class' => true,
			),
			'defs'   => array(),
			'style'  => array(
				'type' => true,
			),
			'title'  => array(),
		);
	}

	private static function get_svg_icon($icon_filename, $color = 'white')
	{
		if (empty($icon_filename)) {
			return '';
		}

		// Ensure .svg extension
		if (!str_ends_with($icon_filename, '.svg')) {
			$icon_filename .= '.svg';
		}

		$icon_path = PN_CUSTOMERS_MANAGER_DIR . 'assets/media/socials/white/' . $icon_filename;

		if (file_exists($icon_path)) {
			$svg_content = file_get_contents($icon_path);

			// Change color if needed
			if ($color !== 'white') {
				$fill_color = $color === 'black' ? '#000000' : $color;
				$svg_content = str_replace('fill:#ffffff', 'fill:' . $fill_color, $svg_content);
				$svg_content = str_replace('style="fill:#ffffff"', 'style="fill:' . $fill_color . '"', $svg_content);
			}

			return $svg_content;
		}

		return '';
	}

	/**
	 * Fetch Open Graph image from URL.
	 *
	 * @param string $url URL to fetch image from.
	 * @return string|null Image URL or null if not found.
	 */
	private static function fetch_og_image($url)
	{
		if (empty($url)) {
			return null;
		}

		$transient_key = 'pn_cm_og_img_' . md5($url);
		$cached = get_transient($transient_key);

		if ($cached !== false) {
			return $cached === 'none' ? null : $cached;
		}

		$response = wp_remote_get($url, ['timeout' => 5, 'sslverify' => false]);

		if (is_wp_error($response)) {
			set_transient($transient_key, 'none', DAY_IN_SECONDS);
			return null;
		}

		$html = wp_remote_retrieve_body($response);

		// Try to find og:image meta tag
		if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](https?:\/\/[^"\']+)["\']/i', $html, $matches)) {
			$image_url = $matches[1];
			set_transient($transient_key, $image_url, WEEK_IN_SECONDS);
			return $image_url;
		}

		// Try to find twitter:image meta tag
		if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\'](https?:\/\/[^"\']+)["\']/i', $html, $matches)) {
			$image_url = $matches[1];
			set_transient($transient_key, $image_url, WEEK_IN_SECONDS);
			return $image_url;
		}

		set_transient($transient_key, 'none', DAY_IN_SECONDS);
		return null;
	}

	/**
	 * Render social media block/shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_social_media($atts = [])
	{
		$atts = shortcode_atts([
			'title' => '',
			'subtitle' => '',
			'profile_image' => '',
			'style' => 'default', // default, compact, minimal
			'columns' => '1',       // 1, 2
			'show_icons' => '1',
			'button_style' => 'solid',   // solid, outline, minimal
			'bg_color' => '',
			'button_color' => '',
			'button_text_color' => '',
		], $atts);

		// Encolar scripts necesarios para el popup
		if (!wp_script_is('pn-customers-manager-popups', 'enqueued')) {
			wp_enqueue_script('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', ['jquery'], defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0', true);
		}
		if (!wp_style_is('pn-customers-manager-popups', 'enqueued')) {
			wp_enqueue_style('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', [], defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0');
		}

		// Get settings
		$title = !empty($atts['title']) ? $atts['title'] : get_option('pn_customers_manager_social_media_title', '');

		// Forzar valor por defecto si está vacío
		if (empty($title) || trim($title) === '') {
			$title = __('Follow us', 'pn-customers-manager');
		}
		$subtitle = !empty($atts['subtitle']) ? $atts['subtitle'] : get_option('pn_customers_manager_social_media_subtitle', '');
		$profile_image = !empty($atts['profile_image']) ? $atts['profile_image'] : get_option('pn_customers_manager_social_media_profile_image', '');

		// Fixed background color for Linktree style (light gray)
		$bg_color = '#f8f9fa';

		// Get button color settings
		$button_color = !empty($atts['button_color']) ? $atts['button_color'] : get_option('pn_customers_manager_social_media_button_color', '#ffffff');
		$button_text_color = !empty($atts['button_text_color']) ? $atts['button_text_color'] : get_option('pn_customers_manager_social_media_button_text_color', '#1a1a1a');

		// Get share and image settings
		$enable_share = get_option('pn_customers_manager_social_media_enable_share', '') === 'on';
		$fetch_images = get_option('pn_customers_manager_social_media_fetch_images', '') === 'on';

		// Get social networks
		$social_networks = [
			'facebook' => ['icon' => 'facebook.svg', 'label' => 'Facebook'],
			'instagram' => ['icon' => 'instagram.svg', 'label' => 'Instagram'],
			'twitter' => ['icon' => 'twitterx.svg', 'label' => 'Twitter / X'],
			'linkedin' => ['icon' => 'linkedin.svg', 'label' => 'LinkedIn'],
			'youtube' => ['icon' => 'youtube.svg', 'label' => 'YouTube'],
			'tiktok' => ['icon' => 'tiktok.svg', 'label' => 'TikTok'],
			'pinterest' => ['icon' => 'pinterest.svg', 'label' => 'Pinterest'],
			'whatsapp' => ['icon' => 'whatsapp.svg', 'label' => 'WhatsApp'],
			'telegram' => ['icon' => 'telegram-app.svg', 'label' => 'Telegram'],
			'email' => ['icon' => 'email.svg', 'label' => 'Email'],
			'website' => ['icon' => 'website.svg', 'label' => __('Website', 'pn-customers-manager')],
		];

		// Separate custom links, products, and social networks
		$custom_links = [];
		$product_links = [];
		$social_links = [];

		// Parse custom links from html_multi format
		$custom_link_titles = get_option('pn_customers_manager_social_media_custom_link_title', []);
		$custom_link_urls = get_option('pn_customers_manager_social_media_custom_link_url', []);
		$custom_link_icons = get_option('pn_customers_manager_social_media_custom_link_icon', []);
		$custom_link_descriptions = get_option('pn_customers_manager_social_media_custom_link_description', []);

		if (is_array($custom_link_titles) && !empty($custom_link_titles)) {
			foreach ($custom_link_titles as $i => $link_title) {
				$url = isset($custom_link_urls[$i]) ? $custom_link_urls[$i] : '';

				if (!empty($url)) {
					$image = null;
					if ($fetch_images) {
						$image = self::fetch_og_image($url);
					}

					$custom_links[] = [
						'network' => 'custom_' . $i,
						'url' => $url,
						'icon' => !empty($custom_link_icons[$i]) ? $custom_link_icons[$i] : 'link.svg',
						'label' => !empty($link_title) ? $link_title : $url,
						'description' => !empty($custom_link_descriptions[$i]) ? $custom_link_descriptions[$i] : '',
						'image' => $image,
						'type' => 'custom',
					];
				}
			}
		}

		// Get WooCommerce products if enabled
		$enable_products = get_option('pn_customers_manager_social_media_enable_products', '') === 'on';
		if ($enable_products && class_exists('WooCommerce')) {
			$product_category = get_option('pn_customers_manager_social_media_product_category', '');
			$products_limit = get_option('pn_customers_manager_social_media_products_limit', 5);

			if (!empty($product_category)) {
				$args = [
					'post_type' => 'product',
					'posts_per_page' => intval($products_limit),
					'tax_query' => [
						[
							'taxonomy' => 'product_cat',
							'field' => 'term_id',
							'terms' => intval($product_category),
						],
					],
				];

				$products = get_posts($args);
				foreach ($products as $product) {
					$product_obj = wc_get_product($product->ID);
					$image = null;
					if ($fetch_images) {
						$image_id = $product_obj->get_image_id();
						if ($image_id) {
							$image = wp_get_attachment_url($image_id);
						}
					}

					$product_links[] = [
						'network' => 'product_' . $product->ID,
						'url' => get_permalink($product->ID),
						'icon' => 'link.svg',
						'label' => $product->post_title,
						'description' => $product_obj->get_price_html(),
						'image' => $image,
						'type' => 'product',
					];
				}
			}
		}

		// Add social networks - These go at the bottom as icons
		foreach ($social_networks as $network => $data) {
			$url = get_option('pn_customers_manager_social_media_' . $network, '');
			if (!empty($url)) {
				$social_links[] = [
					'network' => $network,
					'url' => $url,
					'icon' => $data['icon'],
					'label' => $data['label'],
					'type' => 'social',
				];
			}
		}

		if (empty($custom_links) && empty($product_links) && empty($social_links)) {
			return '<p>' . esc_html__('No links configured.', 'pn-customers-manager') . '</p>';
		}

		// Build CSS classes
		$wrapper_class = 'pn-cm-social-media';
		$wrapper_class .= ' pn-cm-social-media--' . esc_attr($atts['style']);
		$wrapper_class .= ' pn-cm-social-media--cols-' . esc_attr($atts['columns']);
		$wrapper_class .= ' pn-cm-social-media--btn-' . esc_attr($atts['button_style']);

		$unique_id = 'pn-cm-sm-' . uniqid();

		ob_start();
		?>
		<div class="<?php echo esc_attr($wrapper_class); ?>" id="<?php echo esc_attr($unique_id); ?>"
			style="<?php echo !empty($bg_color) ? 'background-color:' . esc_attr($bg_color) . ';' : ''; ?>">
			<?php if (!empty($profile_image)): ?>
				<div class="pn-cm-social-media__profile">
					<?php echo wp_get_attachment_image(absint($profile_image), 'thumbnail', false, ['class' => 'pn-cm-social-media__profile-img']); ?>
				</div>
			<?php endif; ?>

			<h2 class="pn-cm-social-media__title">
				<?php
				if (!empty($title)) {
					echo esc_html($title);
				} else {
					echo esc_html__('Follow us', 'pn-customers-manager');
				}
				?>
			</h2>

			<?php if (!empty($subtitle)): ?>
				<p class="pn-cm-social-media__subtitle"><?php echo esc_html($subtitle); ?></p>
			<?php endif; ?>

			<!-- Enlaces Personalizados (Botones grandes) -->
			<?php if (!empty($custom_links)): ?>
				<div class="pn-cm-social-media__custom-links">
					<?php foreach ($custom_links as $index => $link): ?>
						<?php
						$link_id = $unique_id . '-custom-' . $index;
						?>
						<div class="pn-cm-social-media__link-wrapper">
							<a href="<?php echo esc_url($link['url']); ?>" id="<?php echo esc_attr($link_id); ?>"
								class="pn-cm-social-media__link pn-cm-social-media__link--custom" target="_blank"
								rel="noopener noreferrer" data-url="<?php echo esc_attr($link['url']); ?>"
								data-title="<?php echo esc_attr($link['label']); ?>">

								<?php if (!empty($link['image'])): ?>
									<div class="pn-cm-social-media__link-image"
										style="background-image: url('<?php echo esc_url($link['image']); ?>')"></div>
								<?php endif; ?>

								<div class="pn-cm-social-media__link-content">
									<div class="pn-cm-social-media__link-main">
										<?php if ($atts['show_icons'] === '1' || $atts['show_icons'] === true): ?>
											<div class="pn-cm-social-media__icon">
												<?php echo wp_kses(self::get_svg_icon($link['icon']), self::get_svg_kses_allowed()); ?>
											</div>
										<?php endif; ?>
										<span class="pn-cm-social-media__label"><?php echo esc_html($link['label']); ?></span>
									</div>

									<?php if (!empty($link['description'])): ?>
										<div class="pn-cm-social-media__description"><?php echo esc_html($link['description']); ?></div>
									<?php endif; ?>
								</div>

								<?php if ($enable_share): ?>
									<button type="button" class="pn-cm-social-media__share-btn"
										onclick="pnCmOpenSharePopup(event, '<?php echo esc_js($link['url']); ?>', '<?php echo esc_js($link['label']); ?>', '<?php echo esc_js($unique_id); ?>')"
										title="<?php esc_attr_e('Share', 'pn-customers-manager'); ?>">
										<div class="pn-cm-social-media__share-icon">
											<?php echo wp_kses(self::get_svg_icon('share.svg', '#444444'), self::get_svg_kses_allowed()); ?>
										</div>
									</button>
								<?php endif; ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Enlaces de Productos (Botones grandes) -->
			<?php if (!empty($product_links)): ?>
				<div class="pn-cm-social-media__custom-links">
					<?php foreach ($product_links as $index => $link): ?>
						<?php
						$link_id = $unique_id . '-product-' . $index;
						?>
						<div class="pn-cm-social-media__link-wrapper">
							<a href="<?php echo esc_url($link['url']); ?>" id="<?php echo esc_attr($link_id); ?>"
								class="pn-cm-social-media__link pn-cm-social-media__link--custom pn-cm-social-media__link--product"
								target="_blank" rel="noopener noreferrer" data-url="<?php echo esc_attr($link['url']); ?>"
								data-title="<?php echo esc_attr($link['label']); ?>">

								<?php if (!empty($link['image'])): ?>
									<div class="pn-cm-social-media__link-image"
										style="background-image: url('<?php echo esc_url($link['image']); ?>')"></div>
								<?php endif; ?>

								<div class="pn-cm-social-media__link-content">
									<div class="pn-cm-social-media__link-main">
										<?php if ($atts['show_icons'] === '1' || $atts['show_icons'] === true): ?>
											<div class="pn-cm-social-media__icon">
												<?php echo wp_kses(self::get_svg_icon($link['icon']), self::get_svg_kses_allowed()); ?>
											</div>
										<?php endif; ?>
										<span class="pn-cm-social-media__label"><?php echo esc_html($link['label']); ?></span>
									</div>

									<?php if (!empty($link['description'])): ?>
										<div class="pn-cm-social-media__description"><?php echo wp_kses_post($link['description']); ?></div>
									<?php endif; ?>
								</div>

								<?php if ($enable_share): ?>
									<button type="button" class="pn-cm-social-media__share-btn"
										onclick="pnCmOpenSharePopup(event, '<?php echo esc_js($link['url']); ?>', '<?php echo esc_js($link['label']); ?>', '<?php echo esc_js($unique_id); ?>')"
										title="<?php esc_attr_e('Share', 'pn-customers-manager'); ?>">
										<div class="pn-cm-social-media__share-icon">
											<?php echo wp_kses(self::get_svg_icon('share.svg', '#444444'), self::get_svg_kses_allowed()); ?>
										</div>
									</button>
								<?php endif; ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Redes Sociales (Iconos circulares pequeños) -->
			<?php if (!empty($social_links)): ?>
				<div class="pn-cm-social-media__social-icons">
					<?php foreach ($social_links as $index => $link): ?>
						<?php
						$url = $link['url'];
						// Special handling for email
						if ($link['network'] === 'email' && !str_starts_with($url, 'mailto:')) {
							$url = 'mailto:' . $url;
						}
						$link_id = $unique_id . '-social-' . $index;
						?>
						<a href="<?php echo esc_url($url); ?>" id="<?php echo esc_attr($link_id); ?>"
							class="pn-cm-social-media__social-icon pn-cm-social-media__social-icon--<?php echo esc_attr($link['network']); ?>"
							target="<?php echo $link['network'] === 'email' ? '_self' : '_blank'; ?>" rel="noopener noreferrer"
							title="<?php echo esc_attr($link['label']); ?>">
							<div class="pn-cm-social-media__social-icon-svg">
								<?php echo wp_kses(self::get_svg_icon($link['icon'], '#444444'), self::get_svg_kses_allowed()); ?>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Popup para Compartir -->
		<?php if ($enable_share): ?>
			<!-- Overlay del popup -->
			<div
				class="pn-customers-manager-popup-overlay pn-cm-share-overlay-<?php echo esc_attr($unique_id); ?> pn-customers-manager-display-none-soft">
			</div>

			<div id="pn-cm-share-popup-<?php echo esc_attr($unique_id); ?>"
				class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
				<div class="pn-customers-manager-popup-content">
					<div class="pn-customers-manager-p-30">
						<div class="pn-customers-manager-popup-header">
							<h3><?php esc_html_e('Compartir', 'pn-customers-manager'); ?></h3>
						</div>
						<div class="pn-cm-share-popup__body">
							<button type="button" class="pn-cm-share-option"
								onclick="pnCmShareVia('facebook', '<?php echo esc_js($unique_id); ?>')">
								<div class="pn-cm-share-icon"><?php echo wp_kses(self::get_svg_icon('facebook.svg', '#444444'), self::get_svg_kses_allowed()); ?></div>
								<span>Facebook</span>
							</button>
							<button type="button" class="pn-cm-share-option"
								onclick="pnCmShareVia('twitter', '<?php echo esc_js($unique_id); ?>')">
								<div class="pn-cm-share-icon"><?php echo wp_kses(self::get_svg_icon('twitterx.svg', '#444444'), self::get_svg_kses_allowed()); ?></div>
								<span>Twitter</span>
							</button>
							<button type="button" class="pn-cm-share-option"
								onclick="pnCmShareVia('linkedin', '<?php echo esc_js($unique_id); ?>')">
								<div class="pn-cm-share-icon"><?php echo wp_kses(self::get_svg_icon('linkedin.svg', '#444444'), self::get_svg_kses_allowed()); ?></div>
								<span>LinkedIn</span>
							</button>
							<button type="button" class="pn-cm-share-option"
								onclick="pnCmShareVia('whatsapp', '<?php echo esc_js($unique_id); ?>')">
								<div class="pn-cm-share-icon"><?php echo wp_kses(self::get_svg_icon('whatsapp.svg', '#444444'), self::get_svg_kses_allowed()); ?></div>
								<span>WhatsApp</span>
							</button>
							<button type="button" class="pn-cm-share-option"
								onclick="pnCmShareVia('telegram', '<?php echo esc_js($unique_id); ?>')">
								<div class="pn-cm-share-icon"><?php echo wp_kses(self::get_svg_icon('telegram-app.svg', '#444444'), self::get_svg_kses_allowed()); ?></div>
								<span>Telegram</span>
							</button>
							<button type="button" class="pn-cm-share-option"
								onclick="pnCmShareVia('email', '<?php echo esc_js($unique_id); ?>')">
								<div class="pn-cm-share-icon"><?php echo wp_kses(self::get_svg_icon('email.svg', '#444444'), self::get_svg_kses_allowed()); ?></div>
								<span>Email</span>
							</button>
							<button type="button" class="pn-cm-share-option"
								onclick="pnCmCopyLink('<?php echo esc_js($unique_id); ?>')">
								<div class="pn-cm-share-icon"><?php echo wp_kses(self::get_svg_icon('link.svg', '#444444'), self::get_svg_kses_allowed()); ?></div>
								<span><?php esc_html_e('Copiar enlace', 'pn-customers-manager'); ?></span>
							</button>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<style>
			#<?php echo esc_attr($unique_id); ?> {
				max-width: 600px;
				margin: 0 auto;
				padding: 40px 20px;
				text-align: center;
				border-radius: 12px;
				box-sizing: border-box;
				width: 100%;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__profile {
				margin-bottom: 20px;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__profile-img {
				width: 120px;
				height: 120px;
				border-radius: 50%;
				object-fit: cover;
				box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__title {
				font-size: 28px !important;
				font-weight: 600 !important;
				margin: 20px 0 10px !important;
				color: #1a1a1a !important;
				display: block !important;
				visibility: visible !important;
				opacity: 1 !important;
				text-align: center !important;
				width: 100% !important;
				line-height: 1.3 !important;
				position: relative !important;
				z-index: 1 !important;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__subtitle {
				font-size: 16px;
				color: #666;
				margin: 0 0 30px;
				word-wrap: break-word;
				overflow-wrap: break-word;
				max-width: 100%;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__custom-links {
				display: flex;
				flex-direction: column;
				gap: 12px;
				margin-bottom: 30px;
			}

			/* Diseño de columnas para enlaces personalizados */
			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--cols-2 .pn-cm-social-media__custom-links {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 12px;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__link-wrapper {
				position: relative;
				width: 100%;
				box-sizing: border-box;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__link {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 12px;
				padding: 16px 24px;
				border-radius: 12px;
				text-decoration: none;
				font-size: 16px;
				font-weight: 500;
				transition: all 0.3s ease;
				position: relative;
				overflow: hidden;
				width: 100%;
				text-align: center;
				box-sizing: border-box;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__link-image {
				width: 50px;
				height: 50px;
				border-radius: 8px;
				background-size: cover;
				background-position: center;
				flex-shrink: 0;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__link-content {
				flex: 1;
				display: flex;
				flex-direction: column;
				gap: 4px;
				align-items: center;
				justify-content: center;
				max-width: 100%;
				overflow: hidden;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__link-main {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 10px;
				max-width: 100%;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__description {
				font-size: 12px;
				opacity: 0.8;
				margin-top: 2px;
				word-wrap: break-word;
				overflow-wrap: break-word;
				max-width: 100%;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__share-btn {
				position: absolute;
				right: 12px;
				top: 50%;
				transform: translateY(-50%);
				background: #ffffff;
				border: 1px solid rgba(0, 0, 0, 0.08);
				box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
				border-radius: 50%;
				width: 48px;
				height: 48px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				cursor: pointer;
				transition: all 0.3s ease;
				z-index: 2;
				padding: 0;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__share-btn:hover {
				background: #ffffff;
				border-color: rgba(0, 0, 0, 0.12);
				box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
				transform: translateY(-50%) scale(1.1);
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__share-icon {
				width: 24px;
				height: 24px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__share-icon svg {
				width: 100%;
				height: 100%;
				display: block;
			}

			/* Estilo de botón sólido - estilo Linktree */
			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--btn-solid .pn-cm-social-media__link {
				background: #ffffff;
				color: #1a1a1a;
				border: 1px solid rgba(0, 0, 0, 0.1);
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
			}

			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--btn-solid .pn-cm-social-media__link:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
				border-color: rgba(0, 0, 0, 0.15);
			}

			/* Estilo de botón con borde */
			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--btn-outline .pn-cm-social-media__link {
				background: #fff;
				color:
					<?php echo esc_attr($button_color); ?>
				;
				border: 2px solid
					<?php echo esc_attr($button_color); ?>
				;
			}

			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--btn-outline .pn-cm-social-media__link:hover {
				background:
					<?php echo esc_attr($button_color); ?>
				;
				color:
					<?php echo esc_attr($button_text_color); ?>
				;
			}

			/* Estilo de botón minimalista */
			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--btn-minimal .pn-cm-social-media__link {
				background: #f8f9fa;
				color: #333;
				border: 1px solid #e0e0e0;
			}

			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--btn-minimal .pn-cm-social-media__link:hover {
				background: #fff;
				border-color:
					<?php echo esc_attr($button_color); ?>
				;
				color:
					<?php echo esc_attr($button_color); ?>
				;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__icon {
				width: 24px;
				height: 24px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				flex-shrink: 0;
				vertical-align: middle;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__icon svg {
				width: 100%;
				height: 100%;
				display: block;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__label {
				font-weight: 500;
				line-height: 1.4;
				vertical-align: middle;
				word-wrap: break-word;
				overflow-wrap: break-word;
				max-width: 100%;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__share-icon {
				width: 18px;
				height: 18px;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__share-icon svg {
				width: 100%;
				height: 100%;
			}

			/* Estilo compacto */
			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--compact {
				padding: 20px;
			}

			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--compact .pn-cm-social-media__title {
				font-size: 22px;
			}

			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--compact .pn-cm-social-media__link {
				padding: 12px 20px;
				font-size: 14px;
			}

			/* Estilo minimal */
			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--minimal .pn-cm-social-media__link {
				padding: 10px 16px;
				font-size: 14px;
			}

			#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--minimal .pn-cm-social-media__icon {
				font-size: 20px;
			}

			/* Contenido del Popup para Compartir */
			.pn-cm-share-overlay-<?php echo esc_attr($unique_id); ?> {
				position: fixed !important;
				top: 0 !important;
				left: 0 !important;
				width: 100vw !important;
				height: 100vh !important;
				z-index: 99998 !important;
				display: none !important;
				background-color: rgba(0, 0, 0, 0.7) !important;
			}

			.pn-cm-share-overlay-<?php echo esc_attr($unique_id); ?>.pn-customers-manager-popup-overlay-active {
				display: block !important;
			}

			#pn-cm-share-popup-<?php echo esc_attr($unique_id); ?> {
				z-index: 99999;
			}

			#pn-cm-share-popup-<?php echo esc_attr($unique_id); ?> .pn-customers-manager-popup-content {
				position: relative;
				z-index: 100000;
			}

			#pn-cm-share-popup-<?php echo esc_attr($unique_id); ?> .pn-customers-manager-p-30 {
				padding: 30px;
			}

			.pn-cm-share-popup__body {
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 12px;
			}

			.pn-cm-share-option {
				display: inline-flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				gap: 8px;
				padding: 16px;
				background: #f8f9fa;
				border: 1px solid #e0e0e0;
				border-radius: 12px;
				cursor: pointer;
				transition: all 0.2s ease;
				font-size: 14px;
				font-weight: 500;
				text-align: center;
			}

			.pn-cm-share-option:hover {
				background: #e9ecef;
				transform: translateY(-2px);
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
			}

			.pn-cm-share-icon {
				width: 32px;
				height: 32px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
			}

			.pn-cm-share-icon svg {
				width: 100%;
				height: 100%;
				display: block;
			}

			.pn-cm-share-option span {
				line-height: 1.2;
				color: #333;
			}

			/* Iconos de redes sociales (circulares, horizontales) */
			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__social-icons {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 16px;
				flex-wrap: wrap;
				padding: 20px 0;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__social-icon {
				width: 64px;
				height: 64px;
				border-radius: 50%;
				background: #ffffff;
				border: 1px solid rgba(0, 0, 0, 0.08);
				box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
				display: inline-flex;
				align-items: center;
				justify-content: center;
				text-decoration: none;
				transition: all 0.3s ease;
				padding: 0;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__social-icon:hover {
				background: #ffffff;
				border-color: rgba(0, 0, 0, 0.12);
				box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
				transform: scale(1.1);
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__social-icon-svg {
				width: 40px;
				height: 40px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
			}

			#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__social-icon-svg svg {
				width: 100%;
				height: 100%;
				display: block;
			}

			/* Diseño responsive */
			@media (max-width: 640px) {
				#<?php echo esc_attr($unique_id); ?>.pn-cm-social-media--cols-2 .pn-cm-social-media__custom-links {
					grid-template-columns: 1fr;
				}

				#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__social-icons {
					gap: 12px;
				}

				#<?php echo esc_attr($unique_id); ?> .pn-cm-social-media__social-icon {
					width: 56px;
					height: 56px;
				}
			}
		</style>

		<?php if ($enable_share): ?>
			<script>
				(function () {
					if (typeof window.pnCmShareData === 'undefined') {
						window.pnCmShareData = {};
					}

					window.pnCmOpenSharePopup = function (event, url, title, uniqueId) {
						event.preventDefault();
						event.stopPropagation();
						window.pnCmShareData[uniqueId] = { url: url, title: title };
						// Mostrar overlay específico
						jQuery('.pn-cm-share-overlay-' + uniqueId).removeClass('pn-customers-manager-display-none-soft').addClass('pn-customers-manager-popup-overlay-active');
						// Abrir popup
						pn_customers_manager_Popups.open('pn-cm-share-popup-' + uniqueId);
					};

					window.pnCmShareVia = function (platform, uniqueId) {
						var data = window.pnCmShareData[uniqueId];
						if (!data) return;

						var url = encodeURIComponent(data.url);
						var title = encodeURIComponent(data.title);
						var shareUrl = '';

						switch (platform) {
							case 'facebook':
								shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
								break;
							case 'twitter':
								shareUrl = 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title;
								break;
							case 'linkedin':
								shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + url;
								break;
							case 'whatsapp':
								shareUrl = 'https://wa.me/?text=' + title + ' ' + url;
								break;
							case 'telegram':
								shareUrl = 'https://t.me/share/url?url=' + url + '&text=' + title;
								break;
							case 'email':
								shareUrl = 'mailto:?subject=' + title + '&body=' + url;
								break;
						}

						if (shareUrl) {
							window.open(shareUrl, '_blank', 'width=600,height=400');
							jQuery('.pn-cm-share-overlay-' + uniqueId).removeClass('pn-customers-manager-popup-overlay-active').addClass('pn-customers-manager-display-none-soft');
							pn_customers_manager_Popups.close();
						}
					};

					window.pnCmCopyLink = function (uniqueId) {
						var data = window.pnCmShareData[uniqueId];
						if (!data) return;

						navigator.clipboard.writeText(data.url).then(function () {
							var btn = event.target.closest('.pn-cm-share-option');
							var originalText = btn.querySelector('span').textContent;
							btn.querySelector('span').textContent = '<?php esc_html_e('¡Copiado!', 'pn-customers-manager'); ?>';
							setTimeout(function () {
								btn.querySelector('span').textContent = originalText;
								jQuery('.pn-cm-share-overlay-' + uniqueId).removeClass('pn-customers-manager-popup-overlay-active').addClass('pn-customers-manager-display-none-soft');
								pn_customers_manager_Popups.close();
							}, 1500);
						});
					};
				})();
			</script>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Register Gutenberg block.
	 */
	public static function register_block()
	{
		if (!function_exists('register_block_type')) {
			return;
		}

		wp_register_script(
			'pn-customers-manager-social-media-block',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-social-media.js',
			['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0',
			true
		);

		register_block_type('pn-customers-manager/social-media', [
			'editor_script' => 'pn-customers-manager-social-media-block',
			'render_callback' => [__CLASS__, 'render_block'],
			'attributes' => [
				'title' => ['type' => 'string', 'default' => ''],
				'subtitle' => ['type' => 'string', 'default' => ''],
				'profileImage' => ['type' => 'string', 'default' => ''],
				'style' => ['type' => 'string', 'default' => 'default'],
				'columns' => ['type' => 'string', 'default' => '1'],
				'showIcons' => ['type' => 'boolean', 'default' => true],
				'buttonStyle' => ['type' => 'string', 'default' => 'solid'],
				'bgColor' => ['type' => 'string', 'default' => ''],
				'buttonColor' => ['type' => 'string', 'default' => ''],
				'buttonTextColor' => ['type' => 'string', 'default' => ''],
			],
		]);
	}

	/**
	 * Server-side render callback for the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_block($attributes)
	{
		$map = [
			'title' => 'title',
			'subtitle' => 'subtitle',
			'profileImage' => 'profile_image',
			'style' => 'style',
			'columns' => 'columns',
			'showIcons' => 'show_icons',
			'buttonStyle' => 'button_style',
			'bgColor' => 'bg_color',
			'buttonColor' => 'button_color',
			'buttonTextColor' => 'button_text_color',
		];

		$parts = [];
		foreach ($map as $block_key => $sc_key) {
			if (!isset($attributes[$block_key])) {
				continue;
			}
			$val = $attributes[$block_key];
			if (is_bool($val)) {
				$val = $val ? '1' : '0';
			}
			if ($val === '' || $val === '0') {
				continue;
			}
			$parts[] = $sc_key . '="' . esc_attr($val) . '"';
		}

		$shortcode = '[pn-customers-manager-social-media ' . implode(' ', $parts) . ']';
		return do_shortcode($shortcode);
	}
}
