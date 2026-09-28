<?php
/**
 * WooCommerce CTA Gutenberg Block.
 *
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */

class PN_CUSTOMERS_MANAGER_Woo_CTA
{
	/**
	 * Register the block type and editor script.
	 */
	public static function register_block()
	{
		if (!function_exists('register_block_type')) {
			return;
		}

		if (get_option('pn_customers_manager_woocommerce_cta_block_enabled') !== 'on') {
			return;
		}

		if (!class_exists('WooCommerce')) {
			return;
		}

		wp_register_script(
			'pn-customers-manager-woo-cta-block',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-woo-cta.js',
			['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0',
			true
		);

		wp_register_style(
			'pn-customers-manager-woo-cta-style',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css',
			[],
			defined('PN_CUSTOMERS_MANAGER_VERSION') ? PN_CUSTOMERS_MANAGER_VERSION : '1.0.0'
		);

		// Localize data for the block editor
		$products = [];
		$product_posts = get_posts([
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		]);
		foreach ($product_posts as $p) {
			$products[] = ['id' => $p->ID, 'title' => $p->post_title];
		}

		$cat_terms = get_terms([
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		]);
		$categories_list = [];
		if (!is_wp_error($cat_terms)) {
			foreach ($cat_terms as $term) {
				$categories_list[] = ['id' => $term->term_id, 'name' => $term->name];
			}
		}

		$tag_terms = get_terms([
			'taxonomy'   => 'product_tag',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		]);
		$tags_list = [];
		if (!is_wp_error($tag_terms)) {
			foreach ($tag_terms as $term) {
				$tags_list[] = ['id' => $term->term_id, 'name' => $term->name];
			}
		}

		wp_localize_script('pn-customers-manager-woo-cta-block', 'pnCmWooCtaBlock', [
			'colors' => [
				'bg_color_main'  => get_option('pn_customers_manager_bg_color_main', '#0000aa'),
				'color_main'     => get_option('pn_customers_manager_color_main', '#0000aa'),
				'color_main_alt' => get_option('pn_customers_manager_color_main_alt', '#232323'),
			],
			'shopUrl'    => wc_get_page_permalink('shop'),
			'products'   => $products,
			'categories' => $categories_list,
			'tags'       => $tags_list,
		]);

		register_block_type('pn-customers-manager/woo-cta', [
			'editor_script'   => 'pn-customers-manager-woo-cta-block',
			'style'           => 'pn-customers-manager-woo-cta-style',
			'render_callback' => [__CLASS__, 'render_block'],
			'attributes'      => [
				'ctaType'         => ['type' => 'string',  'default' => 'product'],
				'heading'         => ['type' => 'string',  'default' => ''],
				'description'     => ['type' => 'string',  'default' => ''],
				'buttonText'      => ['type' => 'string',  'default' => 'Ver producto'],
				'targetId'        => ['type' => 'number',  'default' => 0],
				'targetBlank'     => ['type' => 'boolean', 'default' => false],
				'bgColor'         => ['type' => 'string',  'default' => ''],
				'textColor'       => ['type' => 'string',  'default' => ''],
				'buttonColor'     => ['type' => 'string',  'default' => ''],
				'buttonTextColor' => ['type' => 'string',  'default' => ''],
				'align'           => ['type' => 'string',  'default' => 'center'],
				'style'           => ['type' => 'string',  'default' => 'card'],
				'showImage'       => ['type' => 'boolean', 'default' => true],
			],
		]);
	}

	/**
	 * Server-side render callback for the WooCommerce CTA block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public static function render_block($attributes)
	{
		if (!class_exists('WooCommerce')) {
			return '';
		}

		$cta_type    = $attributes['ctaType'] ?? 'product';
		$target_id   = (int) ($attributes['targetId'] ?? 0);
		$heading     = $attributes['heading'] ?? '';
		$description = $attributes['description'] ?? '';
		$button_text = $attributes['buttonText'] ?? 'Ver producto';
		$target_blank = !empty($attributes['targetBlank']);
		$style       = $attributes['style'] ?? 'card';
		$align       = $attributes['align'] ?? 'center';
		$show_image  = $attributes['showImage'] ?? true;

		// Resolve colors
		$bg_color    = !empty($attributes['bgColor']) ? $attributes['bgColor'] : get_option('pn_customers_manager_bg_color_main', '#0000aa');
		$text_color  = !empty($attributes['textColor']) ? $attributes['textColor'] : '#ffffff';
		$btn_bg      = !empty($attributes['buttonColor']) ? $attributes['buttonColor'] : get_option('pn_customers_manager_color_main_alt', '#232323');
		$btn_text    = !empty($attributes['buttonTextColor']) ? $attributes['buttonTextColor'] : '#ffffff';

		// Resolve target URL and image
		$url       = '';
		$image_url = '';
		$alt_text  = '';

		switch ($cta_type) {
			case 'product':
				if ($target_id > 0) {
					$url = get_permalink($target_id);
					if ($show_image) {
						$thumb_id = get_post_thumbnail_id($target_id);
						if ($thumb_id) {
							$image_url = wp_get_attachment_image_url($thumb_id, 'medium');
							$alt_text  = get_post_meta($thumb_id, '_wp_attachment_image_alt', true) ?: get_the_title($target_id);
						}
					}
					if (empty($heading)) {
						$heading = get_the_title($target_id);
					}
				}
				break;

			case 'category':
				if ($target_id > 0) {
					$term = get_term($target_id, 'product_cat');
					if ($term && !is_wp_error($term)) {
						$url = get_term_link($term, 'product_cat');
						if (is_wp_error($url)) {
							$url = '';
						}
						if ($show_image) {
							$thumb_id = get_term_meta($target_id, 'thumbnail_id', true);
							if ($thumb_id) {
								$image_url = wp_get_attachment_image_url($thumb_id, 'medium');
								$alt_text  = $term->name;
							}
						}
						if (empty($heading)) {
							$heading = $term->name;
						}
					}
				}
				break;

			case 'tag':
				if ($target_id > 0) {
					$term = get_term($target_id, 'product_tag');
					if ($term && !is_wp_error($term)) {
						$url = get_term_link($term, 'product_tag');
						if (is_wp_error($url)) {
							$url = '';
						}
						if (empty($heading)) {
							$heading = $term->name;
						}
					}
				}
				break;

			case 'shop':
				$url = wc_get_page_permalink('shop');
				if (empty($heading)) {
					$heading = __('Visit our shop', 'pn-customers-manager');
				}
				break;
		}

		if (empty($url)) {
			return '';
		}

		$target_attr = $target_blank ? ' target="_blank" rel="noopener noreferrer"' : '';

		$css_vars = sprintf(
			'--pn-cm-woo-cta-bg:%s;--pn-cm-woo-cta-text:%s;--pn-cm-woo-cta-btn-bg:%s;--pn-cm-woo-cta-btn-text:%s;',
			esc_attr($bg_color),
			esc_attr($text_color),
			esc_attr($btn_bg),
			esc_attr($btn_text)
		);

		$classes = 'pn-cm-woo-cta pn-cm-woo-cta--' . esc_attr($style) . ' pn-cm-woo-cta--align-' . esc_attr($align);

		$html = '<div class="' . $classes . '" style="' . $css_vars . '">';

		// Image
		if ($show_image && !empty($image_url)) {
			$html .= '<div class="pn-cm-woo-cta__image">';
			$html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '">';
			$html .= '</div>';
		}

		// Content
		$html .= '<div class="pn-cm-woo-cta__content">';
		if (!empty($heading)) {
			$html .= '<h3 class="pn-cm-woo-cta__heading">' . esc_html($heading) . '</h3>';
		}
		if (!empty($description)) {
			$html .= '<p class="pn-cm-woo-cta__description">' . esc_html($description) . '</p>';
		}
		$html .= '<a class="pn-cm-woo-cta__button" href="' . esc_url($url) . '"' . $target_attr . '>' . esc_html($button_text) . '</a>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}
}
