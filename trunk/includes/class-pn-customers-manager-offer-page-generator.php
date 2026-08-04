<?php
/**
 * Offer Page Generator - AI-powered page generation using marketing data.
 *
 * Creates professional marketing pages with Gutenberg blocks based on
 * marketing settings and the Relatia HTML template style.
 *
 * @link       padresenlanube.com/
 * @since      1.1.60
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Offer_Page_Generator {

	use PN_CM_AI_Chat_Common;

	/**
	 * Page types configuration.
	 *
	 * @var array
	 */
	private static $page_types = [
		'business_offer' => [
			'label'       => 'Business Offer',
			'option_key'  => 'pn_cm_business_offer_pages',
			'description' => 'Complete offer page with problems, solution, transformation, pricing, and social proof.',
		],
		'sales_presentation' => [
			'label'       => 'Sales Presentation',
			'option_key'  => 'pn_cm_sales_presentation_pages',
			'description' => 'Professional presentation page focused on services and results.',
		],
	];

	/* ================================================================
	 * TRAIT ABSTRACT METHODS IMPLEMENTATION
	 * ================================================================ */

	protected static function platform_prefix() {
		return 'offer_page_gen_';
	}

	protected static function conversations_table_suffix() {
		return 'pn_cm_offer_page_generations';
	}

	protected static function platform_display_name() {
		return 'Offer Page Generator';
	}

	protected static function brand_color() {
		return '#b84700';
	}

	protected static function email_type() {
		return 'pn_cm_offer_page_generated';
	}

	protected static function log_channel() {
		return 'offer-page-generator';
	}

	protected static function debug_log_option() {
		return 'pn_customers_manager_offer_page_gen_debug_log';
	}

	protected static function node_subtype() {
		return 'offer_page_ai';
	}

	protected static function get_formatting_rules() {
		return '';
	}

	protected static function get_image_rules($include_images) {
		return '';
	}

	protected static function supports_native_images() {
		return false;
	}

	protected static function get_identifier_field() {
		return 'generation_id';
	}

	protected static function get_identifier_label() {
		return 'Generation';
	}

	protected static function get_identifier_value($conversation) {
		return isset($conversation->generation_id) ? $conversation->generation_id : '';
	}

	/* ================================================================
	 * ADMIN PAGES REGISTRATION
	 * ================================================================ */

	/**
	 * Register admin menu pages.
	 */
	public static function register_admin_pages() {
		add_submenu_page(
			'pn-customers-manager-menu',
			__('Business Offer Generator', 'pn-customers-manager'),
			__('Business Offer', 'pn-customers-manager'),
			'edit_posts',
			'pn-cm-business-offer-generator',
			[__CLASS__, 'render_business_offer_page']
		);

		add_submenu_page(
			'pn-customers-manager-menu',
			__('Sales Presentation Generator', 'pn-customers-manager'),
			__('Sales Presentation', 'pn-customers-manager'),
			'edit_posts',
			'pn-cm-sales-presentation-generator',
			[__CLASS__, 'render_sales_presentation_page']
		);
	}

	/**
	 * Render Business Offer Generator page.
	 */
	public static function render_business_offer_page() {
		self::render_generator_page('business_offer');
	}

	/**
	 * Render Sales Presentation Generator page.
	 */
	public static function render_sales_presentation_page() {
		self::render_generator_page('sales_presentation');
	}

	/**
	 * Render generator page.
	 *
	 * @param string $page_type Type of page (business_offer or sales_presentation).
	 */
	private static function render_generator_page($page_type) {
		$config = self::$page_types[$page_type];
		$marketing_data = self::get_marketing_data();
		$created_pages = self::get_created_pages($page_type);

		?>
		<div class="wrap pn-cm-offer-page-generator">
			<h1><?php echo esc_html(sprintf(__('%s Generator', 'pn-customers-manager'), $config['label'])); ?></h1>
			<p class="description"><?php echo esc_html($config['description']); ?></p>

			<?php if (!empty($created_pages)): ?>
			<!-- Created Pages List -->
			<div class="pn-cm-created-pages-section">
				<h2><?php esc_html_e('Created Pages', 'pn-customers-manager'); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e('Title', 'pn-customers-manager'); ?></th>
							<th><?php esc_html_e('Status', 'pn-customers-manager'); ?></th>
							<th><?php esc_html_e('Created', 'pn-customers-manager'); ?></th>
							<th><?php esc_html_e('Actions', 'pn-customers-manager'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($created_pages as $page_id): ?>
							<?php
							$post = get_post($page_id);
							if (!$post) continue;
							?>
							<tr>
								<td><strong><?php echo esc_html($post->post_title); ?></strong></td>
								<td>
									<?php
									$status_labels = [
										'publish' => __('Published', 'pn-customers-manager'),
										'draft'   => __('Draft', 'pn-customers-manager'),
										'pending' => __('Pending', 'pn-customers-manager'),
									];
									echo esc_html($status_labels[$post->post_status] ?? $post->post_status);
									?>
								</td>
								<td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($post->post_date))); ?></td>
								<td>
									<a href="<?php echo esc_url(get_edit_post_link($page_id)); ?>" class="button button-small">
										<?php esc_html_e('Edit', 'pn-customers-manager'); ?>
									</a>
									<a href="<?php echo esc_url(get_permalink($page_id)); ?>" class="button button-small" target="_blank">
										<?php esc_html_e('View', 'pn-customers-manager'); ?>
									</a>
									<button type="button" class="button button-small button-link-delete pn-cm-delete-page" data-page-id="<?php echo esc_attr($page_id); ?>" data-page-type="<?php echo esc_attr($page_type); ?>">
										<?php esc_html_e('Delete', 'pn-customers-manager'); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>

			<!-- Generator Form -->
			<div class="pn-cm-generator-form-section">
				<h2><?php esc_html_e('Generate New Page', 'pn-customers-manager'); ?></h2>
				<p class="description">
					<?php esc_html_e('Review and edit the marketing data below. This information will be used to generate the page with AI.', 'pn-customers-manager'); ?>
				</p>

				<form id="pn-cm-offer-page-form" method="post">
					<?php wp_nonce_field('pn_cm_generate_offer_page', 'pn_cm_nonce'); ?>
					<input type="hidden" name="page_type" value="<?php echo esc_attr($page_type); ?>">

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="pn_cm_page_title"><?php esc_html_e('Page Title', 'pn-customers-manager'); ?></label>
							</th>
							<td>
								<input type="text" id="pn_cm_page_title" name="page_title" class="regular-text" required>
								<p class="description"><?php esc_html_e('Enter a descriptive title for this specific page.', 'pn-customers-manager'); ?></p>
							</td>
						</tr>

						<?php
						// Render all marketing fields
						self::render_marketing_fields($marketing_data);
						?>

						<tr>
							<th scope="row">
								<label for="pn_cm_additional_comments"><?php esc_html_e('Additional Comments', 'pn-customers-manager'); ?></label>
							</th>
							<td>
								<textarea id="pn_cm_additional_comments" name="additional_comments" rows="5" class="large-text"></textarea>
								<p class="description"><?php esc_html_e('Any additional instructions or context for the AI to consider when generating the page.', 'pn-customers-manager'); ?></p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary button-large" id="pn-cm-generate-btn">
							<span class="pn-cm-btn-text"><?php esc_html_e('Generate Page with AI', 'pn-customers-manager'); ?></span>
							<span class="pn-cm-btn-spinner spinner" style="display:none;float:none;"></span>
						</button>
					</p>
				</form>
			</div>

			<div id="pn-cm-generation-result" style="display:none;">
				<div class="notice notice-success">
					<p>
						<strong><?php esc_html_e('Success!', 'pn-customers-manager'); ?></strong>
						<span class="pn-cm-result-message"></span>
					</p>
					<p>
						<a href="#" class="button button-primary pn-cm-edit-page-link" target="_blank">
							<?php esc_html_e('Edit Page', 'pn-customers-manager'); ?>
						</a>
						<a href="#" class="button pn-cm-view-page-link" target="_blank">
							<?php esc_html_e('View Page', 'pn-customers-manager'); ?>
						</a>
					</p>
				</div>
			</div>

			<div id="pn-cm-generation-error" style="display:none;">
				<div class="notice notice-error">
					<p class="pn-cm-error-message"></p>
				</div>
			</div>
		</div>

		<style>
			.pn-cm-offer-page-generator {
				max-width: 1200px;
			}
			.pn-cm-created-pages-section {
				background: #fff;
				padding: 20px;
				margin: 20px 0;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}
			.pn-cm-generator-form-section {
				background: #fff;
				padding: 20px;
				margin: 20px 0;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}
			#pn-cm-generate-btn.loading .pn-cm-btn-text {
				display: none;
			}
			#pn-cm-generate-btn.loading .pn-cm-btn-spinner {
				display: inline-block;
				visibility: visible;
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Form submission
			$('#pn-cm-offer-page-form').on('submit', function(e) {
				e.preventDefault();

				var $form = $(this);
				var $btn = $('#pn-cm-generate-btn');
				var formData = $form.serialize();

				// Disable button and show loading
				$btn.addClass('loading').prop('disabled', true);
				$('#pn-cm-generation-result, #pn-cm-generation-error').hide();

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: formData + '&action=pn_cm_generate_offer_page',
					success: function(response) {
						if (response.success) {
							$('#pn-cm-generation-result').show();
							$('.pn-cm-result-message').text(response.data.message);
							$('.pn-cm-edit-page-link').attr('href', response.data.edit_url);
							$('.pn-cm-view-page-link').attr('href', response.data.view_url);

							// Reload page after 2 seconds to show new page in list
							setTimeout(function() {
								location.reload();
							}, 2000);
						} else {
							$('#pn-cm-generation-error').show();
							$('.pn-cm-error-message').text(response.data.message || '<?php esc_html_e('An error occurred. Please try again.', 'pn-customers-manager'); ?>');
						}
					},
					error: function() {
						$('#pn-cm-generation-error').show();
						$('.pn-cm-error-message').text('<?php esc_html_e('Communication error. Please check your connection and try again.', 'pn-customers-manager'); ?>');
					},
					complete: function() {
						$btn.removeClass('loading').prop('disabled', false);
					}
				});
			});

			// Delete page
			$('.pn-cm-delete-page').on('click', function() {
				if (!confirm('<?php esc_html_e('Are you sure you want to delete this page?', 'pn-customers-manager'); ?>')) {
					return;
				}

				var $btn = $(this);
				var pageId = $btn.data('page-id');
				var pageType = $btn.data('page-type');

				$btn.prop('disabled', true);

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'pn_cm_delete_offer_page',
						nonce: '<?php echo wp_create_nonce('pn_cm_delete_offer_page'); ?>',
						page_id: pageId,
						page_type: pageType
					},
					success: function(response) {
						if (response.success) {
							location.reload();
						} else {
							alert(response.data.message || '<?php esc_html_e('Failed to delete page.', 'pn-customers-manager'); ?>');
							$btn.prop('disabled', false);
						}
					},
					error: function() {
						alert('<?php esc_html_e('Communication error.', 'pn-customers-manager'); ?>');
						$btn.prop('disabled', false);
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render marketing fields in the form.
	 *
	 * @param array $marketing_data Pre-filled marketing data.
	 */
	private static function render_marketing_fields($marketing_data) {
		$fields = [
			'business_name' => [
				'label'       => __('Business Name', 'pn-customers-manager'),
				'type'        => 'text',
				'description' => '',
			],
			'tagline' => [
				'label'       => __('Tagline', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 2,
				'description' => __('Short catchphrase that describes your business (1 line).', 'pn-customers-manager'),
			],
			'description' => [
				'label'       => __('Business Description', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 4,
				'description' => __('What your business does, what problem it solves (2-3 paragraphs).', 'pn-customers-manager'),
			],
			'target_description' => [
				'label'       => __('Target Audience', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 3,
				'description' => __('Who is your ideal customer? Describe their characteristics, problems, and needs.', 'pn-customers-manager'),
			],
			'problems' => [
				'label'       => __('Customer Problems', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 4,
				'description' => __('List the main problems your customers face (one per line).', 'pn-customers-manager'),
			],
			'solution_headline' => [
				'label'       => __('Solution Headline', 'pn-customers-manager'),
				'type'        => 'text',
				'description' => __('Brief headline describing your solution.', 'pn-customers-manager'),
			],
			'solution_description' => [
				'label'       => __('Solution Description', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 4,
				'description' => __('Detailed explanation of how you solve the problems.', 'pn-customers-manager'),
			],
			'transformation' => [
				'label'       => __('Transformation Statement', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 3,
				'description' => __('Describe the transformation: from current state to desired state.', 'pn-customers-manager'),
			],
			'process_steps' => [
				'label'       => __('Process Steps', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 4,
				'description' => __('Your methodology or process (list each step on a new line).', 'pn-customers-manager'),
			],
			'services' => [
				'label'       => __('Services/Products', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 4,
				'description' => __('List your services/products with prices (format: Service Name | Price | Description).', 'pn-customers-manager'),
			],
			'testimonials' => [
				'label'       => __('Testimonials', 'pn-customers-manager'),
				'type'        => 'textarea',
				'rows'        => 4,
				'description' => __('Customer testimonials (format: "Quote" - Name, Company).', 'pn-customers-manager'),
			],
			'cta_primary_text' => [
				'label'       => __('Primary CTA Text', 'pn-customers-manager'),
				'type'        => 'text',
				'description' => __('Text for the main call-to-action button.', 'pn-customers-manager'),
			],
			'cta_primary_url' => [
				'label'       => __('Primary CTA URL', 'pn-customers-manager'),
				'type'        => 'text',
				'description' => __('URL for the main call-to-action button.', 'pn-customers-manager'),
			],
		];

		foreach ($fields as $field_key => $field_config) {
			$field_name = 'marketing_' . $field_key;
			$field_value = $marketing_data[$field_key] ?? '';
			?>
			<tr>
				<th scope="row">
					<label for="pn_cm_<?php echo esc_attr($field_name); ?>">
						<?php echo esc_html($field_config['label']); ?>
					</label>
				</th>
				<td>
					<?php if ($field_config['type'] === 'textarea'): ?>
						<textarea
							id="pn_cm_<?php echo esc_attr($field_name); ?>"
							name="<?php echo esc_attr($field_name); ?>"
							rows="<?php echo esc_attr($field_config['rows'] ?? 3); ?>"
							class="large-text"
						><?php echo esc_textarea($field_value); ?></textarea>
					<?php else: ?>
						<input
							type="text"
							id="pn_cm_<?php echo esc_attr($field_name); ?>"
							name="<?php echo esc_attr($field_name); ?>"
							value="<?php echo esc_attr($field_value); ?>"
							class="regular-text"
						>
					<?php endif; ?>
					<?php if (!empty($field_config['description'])): ?>
						<p class="description"><?php echo esc_html($field_config['description']); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Get marketing data from options.
	 *
	 * @return array Marketing data.
	 */
	private static function get_marketing_data() {
		$prefix = 'pn_customers_manager_marketing_';
		$fields = [
			'business_name',
			'tagline',
			'description',
			'target_description',
			'solution_headline',
			'solution_description',
			'transformation',
			'process_steps',
			'services',
			'testimonials',
			'cta_primary_text',
			'cta_primary_url',
		];

		$data = [];
		foreach ($fields as $field) {
			$data[$field] = get_option($prefix . $field, '');
		}

		// Problems is an html_multi field, get as array and convert to text
		$problems_array = get_option($prefix . 'problems_text', []);
		if (is_array($problems_array) && !empty($problems_array)) {
			$data['problems'] = implode("\n", array_filter($problems_array));
		} else {
			$data['problems'] = '';
		}

		return $data;
	}

	/**
	 * Get created pages for a specific type.
	 *
	 * @param string $page_type Type of page.
	 * @return array Array of page IDs.
	 */
	private static function get_created_pages($page_type) {
		$config = self::$page_types[$page_type] ?? null;
		if (!$config) {
			return [];
		}

		$pages = get_option($config['option_key'], []);
		if (!is_array($pages)) {
			return [];
		}

		// Filter out deleted pages
		return array_filter($pages, function($page_id) {
			return get_post($page_id) !== null;
		});
	}

	/**
	 * Add page to tracking.
	 *
	 * @param string $page_type Type of page.
	 * @param int    $page_id Page ID.
	 */
	private static function track_created_page($page_type, $page_id) {
		$config = self::$page_types[$page_type] ?? null;
		if (!$config) {
			return;
		}

		$pages = get_option($config['option_key'], []);
		if (!is_array($pages)) {
			$pages = [];
		}

		if (!in_array($page_id, $pages)) {
			$pages[] = $page_id;
			update_option($config['option_key'], $pages);
		}
	}

	/**
	 * Remove page from tracking.
	 *
	 * @param string $page_type Type of page.
	 * @param int    $page_id Page ID.
	 */
	private static function untrack_page($page_type, $page_id) {
		$config = self::$page_types[$page_type] ?? null;
		if (!$config) {
			return;
		}

		$pages = get_option($config['option_key'], []);
		if (!is_array($pages)) {
			return;
		}

		$pages = array_filter($pages, function($id) use ($page_id) {
			return $id != $page_id;
		});

		update_option($config['option_key'], array_values($pages));
	}

	/* ================================================================
	 * AJAX HANDLERS
	 * ================================================================ */

	/**
	 * Handle AJAX request to generate offer page.
	 */
	public static function handle_generate_offer_page() {
		check_ajax_referer('pn_cm_generate_offer_page', 'pn_cm_nonce');

		if (!current_user_can('edit_posts')) {
			wp_send_json_error([
				'message' => __('Insufficient permissions.', 'pn-customers-manager'),
			]);
		}

		// Get form data
		$page_type = sanitize_text_field($_POST['page_type'] ?? '');
		$page_title = sanitize_text_field($_POST['page_title'] ?? '');
		$additional_comments = sanitize_textarea_field($_POST['additional_comments'] ?? '');

		// Get marketing data from form
		$marketing_data = [];
		$marketing_fields = [
			'business_name', 'tagline', 'description', 'target_description',
			'problems', 'solution_headline', 'solution_description', 'transformation',
			'process_steps', 'services', 'testimonials', 'cta_primary_text', 'cta_primary_url',
		];

		foreach ($marketing_fields as $field) {
			$key = 'marketing_' . $field;
			$marketing_data[$field] = isset($_POST[$key]) ? sanitize_textarea_field($_POST[$key]) : '';
		}

		// Validate
		if (empty($page_title)) {
			wp_send_json_error([
				'message' => __('Page title is required.', 'pn-customers-manager'),
			]);
		}

		// Generate with AI
		$generator = new self();
		$gutenberg_blocks = $generator->generate_gutenberg_content($marketing_data, $additional_comments, $page_type);

		if (!$gutenberg_blocks) {
			wp_send_json_error([
				'message' => __('AI generation failed. Please check your OpenAI API key in settings.', 'pn-customers-manager'),
			]);
		}

		// Create page
		$page_id = wp_insert_post([
			'post_title'   => $page_title,
			'post_content' => $gutenberg_blocks,
			'post_status'  => 'draft',
			'post_type'    => 'page',
			'post_author'  => get_current_user_id(),
			'meta_input'   => [
				'_pn_cm_page_type'       => $page_type,
				'_pn_cm_generated_by_ai' => 'yes',
				'_pn_cm_generation_date' => current_time('mysql'),
			],
		]);

		if (!$page_id || is_wp_error($page_id)) {
			wp_send_json_error([
				'message' => __('Failed to create page. Please try again.', 'pn-customers-manager'),
			]);
		}

		// Track page
		self::track_created_page($page_type, $page_id);

		wp_send_json_success([
			'message'  => __('Page generated successfully!', 'pn-customers-manager'),
			'page_id'  => $page_id,
			'edit_url' => admin_url('post.php?post=' . $page_id . '&action=edit'),
			'view_url' => get_permalink($page_id),
		]);
	}

	/**
	 * Handle AJAX request to delete offer page.
	 */
	public static function handle_delete_offer_page() {
		check_ajax_referer('pn_cm_delete_offer_page', 'nonce');

		if (!current_user_can('delete_posts')) {
			wp_send_json_error([
				'message' => __('Insufficient permissions.', 'pn-customers-manager'),
			]);
		}

		$page_id = absint($_POST['page_id'] ?? 0);
		$page_type = sanitize_text_field($_POST['page_type'] ?? '');

		if (!$page_id) {
			wp_send_json_error([
				'message' => __('Invalid page ID.', 'pn-customers-manager'),
			]);
		}

		// Delete page
		$result = wp_delete_post($page_id, true);

		if (!$result) {
			wp_send_json_error([
				'message' => __('Failed to delete page.', 'pn-customers-manager'),
			]);
		}

		// Remove from tracking
		self::untrack_page($page_type, $page_id);

		wp_send_json_success([
			'message' => __('Page deleted successfully.', 'pn-customers-manager'),
		]);
	}

	/* ================================================================
	 * AI GENERATION
	 * ================================================================ */

	/**
	 * Generate Gutenberg content using AI and the Relatia template.
	 *
	 * @param array  $marketing_data Marketing data from form.
	 * @param string $additional_comments Additional user comments.
	 * @param string $page_type Type of page.
	 * @return string|false Generated Gutenberg blocks or false on failure.
	 */
	private function generate_gutenberg_content($marketing_data, $additional_comments, $page_type) {
		// Read the Relatia template
		$template_path = dirname(dirname(__FILE__)) . '/templates/relatia-offer-template.html';

		// Read the actual template file
		$template_content = '';
		if (file_exists($template_path)) {
			$template_content = file_get_contents($template_path);

			// Truncate if too long (keep first 15000 chars for context window)
			if (strlen($template_content) > 15000) {
				$template_content = substr($template_content, 0, 15000) . "\n\n[... template continues with similar structure ...]";
			}

			$template_reference = "EJEMPLO COMPLETO DE ESTRUCTURA DE BLOQUES GUTENBERG (Relatia):\n\n" . $template_content;
		} else {
			// Fallback if template file doesn't exist
			$template_reference = "EJEMPLO DE ESTRUCTURA DE BLOQUES GUTENBERG (Relatia):

La página debe seguir esta estructura profesional con bloques Gutenberg:

1. HERO SECTION (cabecera principal):
   - Grupo con padding y fondo degradado
   - Título H1 centrado con el nombre del negocio y propuesta de valor destacada
   - Párrafo descriptivo centrado
   - Botón CTA principal centrado

2. RESUMEN DEL EMPRENDIMIENTO (caja destacada con borde):
   - Grupo con borde de color, border-radius y fondo
   - Título H2 con el nombre del negocio
   - Columnas con:
     * ¿Qué? (descripción del servicio)
     * ¿A quién? (público objetivo)
   - ¿Para qué? (objetivo/transformación)
   - Transformación (texto en cursiva destacado)
   - Precio (lista con niveles de servicio)

3. PASOS DEL PROCESO (secciones colapsables):
   - Título de sección centrado
   - Múltiples bloques <details> con:
     * Summary con título del paso
     * Contenido detallado con subsecciones
     * Grupos de color para diferentes categorías
     * Listas y párrafos explicativos

Usa estilos inline con colores, spacing, border-radius, y tipografía personalizada.
Los bloques deben estar en formato HTML comentado de Gutenberg: <!-- wp:block-type {\"attributes\"} -->content<!-- /wp:block-type -->";
		}

		// Build AI prompt
		$prompt = $this->build_ai_prompt($marketing_data, $additional_comments, $template_reference, $page_type);

		// Call OpenAI
		$messages = [
			['role' => 'system', 'content' => $this->build_system_prompt()],
			['role' => 'user', 'content' => $prompt],
		];

		error_log('PN_CM_Offer_Generator: Calling OpenAI with model gpt-4o');
		error_log('PN_CM_Offer_Generator: System prompt length: ' . strlen($this->build_system_prompt()));
		error_log('PN_CM_Offer_Generator: User prompt length: ' . strlen($prompt));

		// Call OpenAI with detailed logging
		$response = $this->call_openai_with_logging($messages, 'gpt-4o', 0.7);

		error_log('PN_CM_Offer_Generator: OpenAI returned: ' . ($response === false ? 'FALSE' : 'response length ' . strlen($response)));

		if ($response === false) {
			error_log('PN_CM_Offer_Generator: call_openai returned false (admin)');
			return false;
		}

		error_log('PN_CM_Offer_Generator: OpenAI response received (admin), length: ' . strlen($response));

		// Extract and clean the response
		$cleaned = $this->clean_gutenberg_response($response);

		if (empty($cleaned)) {
			error_log('PN_CM_Offer_Generator: clean_gutenberg_response returned empty (admin)');
			// Return the original response if cleaning failed
			return $response;
		}

		return $cleaned;
	}

	/**
	 * Generate Gutenberg content with custom colors from settings.
	 *
	 * @param array  $marketing_data Marketing data from form.
	 * @param string $additional_comments Additional user comments.
	 * @param string $page_type Type of page.
	 * @param array  $colors Array of custom colors from settings.
	 * @return string|false Generated Gutenberg blocks or false on failure.
	 */
	public function generate_gutenberg_with_colors($marketing_data, $additional_comments, $page_type, $colors) {
		// Read the Relatia template
		$template_path = dirname(dirname(__FILE__)) . '/templates/relatia-offer-template.html';

		// Read the actual template file
		$template_content = '';
		if (file_exists($template_path)) {
			$template_content = file_get_contents($template_path);

			// Truncate if too long
			if (strlen($template_content) > 15000) {
				$template_content = substr($template_content, 0, 15000) . "\n\n[... template continues with similar structure ...]";
			}

			// Replace default colors with custom colors in the template reference
			$template_content = str_replace('#b84700', $colors['main'], $template_content);
			$template_content = str_replace('#F5E6D0', $colors['main_alt'], $template_content);
			$template_content = str_replace('#2563EB', $colors['main_blue'], $template_content);
			$template_content = str_replace('#6B6B80', $colors['main_grey'], $template_content);

			$template_reference = "EJEMPLO COMPLETO DE ESTRUCTURA DE BLOQUES GUTENBERG (Relatia) CON TUS COLORES PERSONALIZADOS:\n\n" . $template_content;
			$template_reference .= "\n\nCOLORES PERSONALIZADOS A USAR:\n";
			$template_reference .= "- Color principal (naranja): {$colors['main']}\n";
			$template_reference .= "- Color alternativo (beige): {$colors['main_alt']}\n";
			$template_reference .= "- Color azul: {$colors['main_blue']}\n";
			$template_reference .= "- Color gris: {$colors['main_grey']}\n";
		} else {
			// Fallback
			$template_reference = "USA ESTOS COLORES EN LA PÁGINA:\n";
			$template_reference .= "- Color principal: {$colors['main']}\n";
			$template_reference .= "- Color alternativo: {$colors['main_alt']}\n";
			$template_reference .= "- Color azul: {$colors['main_blue']}\n";
			$template_reference .= "- Color gris: {$colors['main_grey']}\n\n";
			$template_reference .= "Estructura: Hero section, Resumen del emprendimiento, Pasos del proceso colapsables.";
		}

		// Build AI prompt
		$prompt = $this->build_ai_prompt($marketing_data, $additional_comments, $template_reference, $page_type);

		// Call OpenAI
		$messages = [
			['role' => 'system', 'content' => $this->build_system_prompt_with_colors($colors)],
			['role' => 'user', 'content' => $prompt],
		];

		error_log('PN_CM_Offer_Generator: Calling OpenAI with model gpt-4o and custom colors');
		error_log('PN_CM_Offer_Generator: Colors: ' . json_encode($colors));
		error_log('PN_CM_Offer_Generator: System prompt length: ' . strlen($this->build_system_prompt_with_colors($colors)));
		error_log('PN_CM_Offer_Generator: User prompt length: ' . strlen($prompt));

		// Call OpenAI with detailed logging
		$response = $this->call_openai_with_logging($messages, 'gpt-4o', 0.7);

		error_log('PN_CM_Offer_Generator: OpenAI returned: ' . ($response === false ? 'FALSE' : 'response length ' . strlen($response)));

		if ($response === false) {
			error_log('PN_CM_Offer_Generator: call_openai returned false');
			return false;
		}

		error_log('PN_CM_Offer_Generator: OpenAI response received, length: ' . strlen($response));

		// Extract and clean the response
		$cleaned = $this->clean_gutenberg_response($response);

		if (empty($cleaned)) {
			error_log('PN_CM_Offer_Generator: clean_gutenberg_response returned empty');
			// Return the original response if cleaning failed
			return $response;
		}

		return $cleaned;
	}

	/**
	 * Build system prompt for AI.
	 *
	 * @return string System prompt.
	 */
	private function build_system_prompt() {
		return "Eres un experto en crear páginas de ventas profesionales usando bloques Gutenberg de WordPress siguiendo metodologías de marketing genuino (Isra Bravo) y ventas consultivas.

Tu tarea es generar el código HTML completo de bloques Gutenberg siguiendo EXACTAMENTE el estilo y estructura del ejemplo de Relatia que te proporcionaré.

IMPORTANTE - REGLAS ESTRICTAS:
- Devuelve SOLO el código HTML de los bloques Gutenberg, sin explicaciones ni texto adicional
- Usa el formato EXACTO de comentarios de Gutenberg: <!-- wp:block-type {\"attributes\"} -->
- REPLICA la estructura del template: mismo orden de secciones, misma jerarquía, mismos tipos de bloques
- MANTÉN los estilos inline del ejemplo: colores específicos, spacing, border-radius, tipografía
- Usa los mismos bloques que el ejemplo: group, heading, paragraph, columns, list, buttons, details, spacer, separator
- Los colores deben coincidir con el estilo del template (tonos tierra, naranjas, verdes)
- Estructura obligatoria:
  1. Hero section (group con gradient background)
  2. Resumen del emprendimiento (group con borde naranja #b84700)
  3. Título \"Pasos del proceso\"
  4. Múltiples bloques details colapsables (PASO 1, PASO 2, etc.)
  5. Cada paso con subsecciones y grupos de colores diferentes
- NO inventes estilos nuevos, COPIA los del template
- NO cambies la paleta de colores del ejemplo
- El resultado debe verse visualmente similar al template pero con el contenido del negocio específico";
	}

	/**
	 * Build system prompt with custom colors for AI.
	 *
	 * @param array $colors Custom colors from settings.
	 * @return string System prompt.
	 */
	private function build_system_prompt_with_colors($colors) {
		return "Eres un experto en crear páginas de ventas profesionales usando bloques Gutenberg de WordPress siguiendo metodologías de marketing genuino (Isra Bravo) y ventas consultivas.

Tu tarea es generar el código HTML completo de bloques Gutenberg siguiendo EXACTAMENTE el estilo y estructura del ejemplo de Relatia que te proporcionaré, pero USANDO LOS COLORES PERSONALIZADOS especificados.

COLORES PERSONALIZADOS OBLIGATORIOS:
- Color principal (para bordes, acentos, botones): {$colors['main']}
- Color alternativo (para fondos suaves): {$colors['main_alt']}
- Color azul (para elementos secundarios): {$colors['main_blue']}
- Color gris (para textos secundarios): {$colors['main_grey']}

IMPORTANTE - REGLAS ESTRICTAS:
- Devuelve SOLO el código HTML de los bloques Gutenberg, sin explicaciones ni texto adicional
- Usa el formato EXACTO de comentarios de Gutenberg: <!-- wp:block-type {\"attributes\"} -->
- REPLICA la estructura del template: mismo orden de secciones, misma jerarquía, mismos tipos de bloques
- REEMPLAZA todos los colores del template con los colores personalizados proporcionados arriba
- Mantén los estilos inline del ejemplo: spacing, border-radius, tipografía
- Usa los mismos bloques que el ejemplo: group, heading, paragraph, columns, list, buttons, details, spacer, separator
- Estructura obligatoria:
  1. Hero section (group con gradient background)
  2. Resumen del emprendimiento (group con borde del color principal)
  3. Título \"Pasos del proceso\"
  4. Múltiples bloques details colapsables (PASO 1, PASO 2, etc.)
  5. Cada paso con subsecciones y grupos de colores diferentes
- NO inventes estilos nuevos, COPIA los del template
- USA los colores personalizados proporcionados, NO los colores originales del template
- El resultado debe verse visualmente similar al template pero con los colores del usuario";
	}

	/**
	 * Build user prompt for AI generation.
	 *
	 * @param array  $marketing_data Marketing data.
	 * @param string $additional_comments Additional comments.
	 * @param string $template_reference Template reference.
	 * @param string $page_type Page type.
	 * @return string User prompt.
	 */
	private function build_ai_prompt($marketing_data, $additional_comments, $template_reference, $page_type) {
		$data_summary = "DATOS DEL NEGOCIO A USAR:\n\n";
		foreach ($marketing_data as $key => $value) {
			if (!empty($value)) {
				$label = ucfirst(str_replace('_', ' ', $key));
				$data_summary .= "**{$label}:**\n{$value}\n\n";
			}
		}

		if (!empty($additional_comments)) {
			$data_summary .= "**COMENTARIOS ADICIONALES DEL USUARIO:**\n{$additional_comments}\n\n";
		}

		// Sales Presentation uses a simpler, shorter structure
		if ($page_type === 'sales_presentation') {
			return $this->build_sales_presentation_prompt($data_summary, $marketing_data);
		}

		// Business Offer uses the full complex structure
		$type_label = 'Business Offer (Oferta Genuina)';

		return "---INICIO DEL TEMPLATE DE REFERENCIA (Relatia)---

{$template_reference}

---FIN DEL TEMPLATE DE REFERENCIA---

{$data_summary}

TAREA:
Genera una página completa de {$type_label} con bloques Gutenberg REPLICANDO EXACTAMENTE la estructura y estilo del template de Relatia que te mostré arriba.

ESTRUCTURA OBLIGATORIA (sigue el template):
1. Hero section con:
   - Grupo con padding 80px y gradient background
   - Etiqueta superior con letra tracking
   - H1 con clamp font-size y marca destacada
   - Párrafo descriptivo
   - Botón CTA centrado

2. Spacer de 60px

3. Resumen del emprendimiento:
   - Grupo con borde naranja #b84700, border-radius 16px, fondo #F5E6D0
   - Título H2 con nombre del negocio
   - Columnas con ¿Qué? y ¿A quién?
   - ¿Para qué?
   - Transformación (cursiva)
   - Precio (lista)

4. Spacer de 60px

5. Título \"Pasos del proceso\" (centrado, uppercase, color gris)

6. Spacer de 40px

7. Múltiples bloques <details> colapsables:
   - PASO 1: Público Objetivo (con arquetipos de usuario en grupos de colores)
   - PASO 2: Grupos de Problemas (grupos con bordes de colores diferentes)
   - PASO 3: Estructura de la Oferta (con transformación, método/fases, verticales)
   - PASO 4+: Más pasos según sea necesario

IMPORTANTE:
- USA los datos del negocio proporcionados para rellenar el contenido
- MANTÉN la estructura exacta del template
- COPIA los estilos CSS inline del ejemplo
- USA los mismos colores y tipografía
- Devuelve SOLO el código HTML de los bloques Gutenberg
- NO añadas explicaciones antes ni después del código";
	}

	/**
	 * Build simplified prompt for Sales Presentation pages.
	 *
	 * @param string $data_summary Data summary.
	 * @param array  $marketing_data Marketing data.
	 * @return string Simplified prompt.
	 */
	private function build_sales_presentation_prompt($data_summary, $marketing_data) {
		return "{$data_summary}

TAREA:
Genera una página profesional de Presentación de Ventas con bloques Gutenberg. Estructura SIMPLIFICADA para generación rápida.

ESTRUCTURA OBLIGATORIA (más simple que Business Offer):

1. Hero Section:
   - Grupo con gradient background (color principal + color alternativo)
   - H1 con el nombre del negocio
   - Párrafo con el tagline
   - Botón CTA principal

2. Spacer (40px)

3. Sobre Nosotros:
   - H2 \"Sobre Nosotros\"
   - Párrafo con la descripción del negocio
   - Incluir párrafos cortos y concisos

4. Spacer (40px)

5. Nuestros Servicios:
   - H2 \"Nuestros Servicios\"
   - Columnas con tarjetas de servicios
   - Cada servicio: nombre, descripción breve, precio
   - Usar grupos con bordes de color principal

6. Spacer (40px)

7. Testimonios (SI HAY DATOS):
   - H2 \"Lo Que Dicen Nuestros Clientes\"
   - Grupos con testimonios
   - Cada uno: quote + autor

8. Spacer (40px)

9. CTA Final:
   - H2 \"¿Listo para Empezar?\"
   - Párrafo breve invitando a la acción
   - Botón CTA principal

ESTILOS A USAR:
- Colores: usa el color principal para bordes y botones, color alternativo para fondos
- Padding: 60px en hero, 40px en secciones
- Border-radius: 12px en grupos
- Font-size: clamp para títulos (mobile-responsive)

IMPORTANTE:
- Mantén el contenido CONCISO - máximo 2-3 párrafos por sección
- NO uses bloques collapsibles details - todo visible directamente
- Estructura simple y directa
- Devuelve SOLO el código HTML de los bloques Gutenberg
- NO añadas explicaciones";
	}

	/**
	 * Call OpenAI with detailed logging (overrides trait method for debugging).
	 *
	 * @param array  $messages Messages array.
	 * @param string $model Model name.
	 * @param float  $temperature Temperature.
	 * @return string|false Response content or false on failure.
	 */
	private function call_openai_with_logging($messages, $model = 'gpt-4o', $temperature = 0.7) {
		$provider = self::resolve_provider_for_model($model);
		$api_key  = get_option($provider['key_option'], '');

		if (empty($api_key)) {
			error_log('PN_CM_Offer_Generator: API key is empty for provider ' . $provider['label']);
			return false;
		}

		error_log('PN_CM_Offer_Generator: Making API call to ' . $provider['label'] . '...');

		$body = [
			'model'       => $model,
			'messages'    => $messages,
			'max_tokens'  => 16000,
		];

		// DeepSeek R1 does not support temperature
		if ($model !== 'deepseek-reasoner') {
			$body['temperature'] = $temperature;
		}

		$response = wp_remote_post($provider['url'], [
			'timeout' => 60,
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
			'body' => wp_json_encode($body),
		]);

		if (is_wp_error($response)) {
			error_log('PN_CM_Offer_Generator: WP_Error: ' . $response->get_error_message());
			return false;
		}

		$http_code = wp_remote_retrieve_response_code($response);
		$body_raw  = wp_remote_retrieve_body($response);
		$body_data = json_decode($body_raw, true);

		error_log('PN_CM_Offer_Generator: HTTP Code: ' . $http_code);
		error_log('PN_CM_Offer_Generator: Response body length: ' . strlen($body_raw));

		if ($http_code !== 200) {
			error_log('PN_CM_Offer_Generator: HTTP Error ' . $http_code);
			if (isset($body_data['error'])) {
				error_log('PN_CM_Offer_Generator: Error: ' . json_encode($body_data['error']));
			}
			return false;
		}

		if (isset($body_data['choices'][0]['message']['content'])) {
			$content = $body_data['choices'][0]['message']['content'];
			error_log('PN_CM_Offer_Generator: Success! Content length: ' . strlen($content));
			return $content;
		}

		if (isset($body_data['error'])) {
			error_log('PN_CM_Offer_Generator: API Error: ' . json_encode($body_data['error']));
		} else {
			error_log('PN_CM_Offer_Generator: Unexpected response structure: ' . substr($body_raw, 0, 500));
		}

		return false;
	}

	/**
	 * Clean and validate Gutenberg response from AI.
	 *
	 * @param string $response AI response.
	 * @return string Cleaned Gutenberg blocks.
	 */
	private function clean_gutenberg_response($response) {
		if (empty($response)) {
			error_log('PN_CM_Offer_Generator: Empty response from OpenAI');
			return false;
		}

		// Remove markdown code blocks if present
		$response = preg_replace('/```html\s*|\s*```/s', '', $response);
		$response = preg_replace('/```\s*/s', '', $response);
		$response = trim($response);

		// Log the first 500 chars for debugging
		error_log('PN_CM_Offer_Generator: Response preview: ' . substr($response, 0, 500));

		// Try to find Gutenberg blocks
		if (strpos($response, '<!-- wp:') === false) {
			error_log('PN_CM_Offer_Generator: No Gutenberg blocks found in response');
			// If no Gutenberg blocks found, return the response anyway
			// It might be valid HTML that can be used
			return $response;
		}

		// Extract from first Gutenberg block
		$pos = strpos($response, '<!-- wp:');
		if ($pos !== false && $pos > 0) {
			$response = substr($response, $pos);
		}

		error_log('PN_CM_Offer_Generator: Successfully cleaned response, length: ' . strlen($response));
		return $response;
	}
}
