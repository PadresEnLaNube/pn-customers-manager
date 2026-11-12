<?php
/**
 * Call to Action injection into post content.
 *
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */

class PN_CUSTOMERS_MANAGER_CTA
{
	/**
	 * Filter callback for 'the_content'. Injects CTA HTML based on settings.
	 *
	 * @param string $content The post content.
	 * @return string Modified content with CTA injected.
	 */
	public static function add_to_content($content)
	{
		// Only apply on singular front-end views (not admin, feeds, REST, etc.)
		if (is_admin() || !is_singular() || !in_the_loop() || !is_main_query()) {
			return $content;
		}

		// Check if CTA is enabled
		if (get_option('pn_customers_manager_cta_enabled') !== 'on') {
			return $content;
		}

		// Check if current post type is in the selected CPTs
		$cta_cpts = get_option('pn_customers_manager_cta_cpts', []);
		if (!is_array($cta_cpts)) {
			$cta_cpts = [];
		}
		$current_post_type = get_post_type();
		if (empty($cta_cpts) || !in_array($current_post_type, $cta_cpts, true)) {
			return $content;
		}

		// Get CTA content (language-specific with fallback to default)
		$cta_content = '';
		if (function_exists('pll_current_language')) {
			$lang = pll_current_language('slug');
			if ($lang) {
				$cta_content = get_option('pn_customers_manager_cta_content_' . $lang, '');
			}
		}
		if (empty(trim($cta_content))) {
			$cta_content = get_option('pn_customers_manager_cta_content', '');
		}
		if (empty(trim($cta_content))) {
			return $content;
		}

		// Wrap CTA in a container div
		$cta_html = '<div class="pn-customers-manager-cta" style="margin: 2em 0;">' . $cta_content . '</div>';

		// Get position
		$position = get_option('pn_customers_manager_cta_position', 'after');

		switch ($position) {
			case 'before':
				return $cta_html . $content;

			case 'inside':
				return self::insert_inside_content($content, $cta_html);

			case 'after':
			default:
				return $content . $cta_html;
		}
	}

	/**
	 * Insert CTA inside the content after a percentage of paragraphs.
	 *
	 * @param string $content  The post content.
	 * @param string $cta_html The CTA HTML to inject.
	 * @return string Modified content.
	 */
	private static function insert_inside_content($content, $cta_html)
	{
		$percentage = (int) get_option('pn_customers_manager_cta_position_percentage', 50);
		$percentage = max(0, min(100, $percentage));

		// Split content by closing </p> tags, keeping the delimiters
		$parts = preg_split('/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

		if ($parts === false || count($parts) < 2) {
			// No paragraphs found, append at the end
			return $content . $cta_html;
		}

		// Reconstruct complete paragraphs (text + </p>)
		$paragraphs = [];
		for ($i = 0; $i < count($parts) - 1; $i += 2) {
			$paragraphs[] = $parts[$i] . (isset($parts[$i + 1]) ? $parts[$i + 1] : '');
		}
		// Any trailing content after the last </p>
		$trailing = '';
		if (count($parts) % 2 !== 0) {
			$trailing = end($parts);
		}

		$total = count($paragraphs);
		if ($total === 0) {
			return $content . $cta_html;
		}

		// Calculate insertion point
		$insert_after = (int) ceil($total * $percentage / 100);
		$insert_after = max(1, min($total, $insert_after));

		// Build result
		$result = '';
		for ($i = 0; $i < $total; $i++) {
			$result .= $paragraphs[$i];
			if ($i + 1 === $insert_after) {
				$result .= $cta_html;
			}
		}
		$result .= $trailing;

		return $result;
	}
}
