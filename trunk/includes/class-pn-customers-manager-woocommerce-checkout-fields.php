<?php
/**
 * WooCommerce Checkout Custom Fields System
 *
 * Allows adding custom fields to checkout page per product
 *
 * @link       padresenlanube.com/
 * @since      1.1.118
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce_Checkout_Fields
{
	private static $instance = null;

	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		// Admin: Tab de Campos de Checkout en editor de producto
		add_filter('woocommerce_product_data_tabs', [$this, 'product_data_tab']);
		add_action('woocommerce_product_data_panels', [$this, 'product_data_panel']);
		add_action('woocommerce_process_product_meta', [$this, 'save_product_meta']);
		add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

		// Frontend: Product Page
		add_action('woocommerce_before_add_to_cart_button', [$this, 'render_product_fields']);
		add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);
		add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);

		// Frontend: Checkout
		add_action('woocommerce_after_order_notes', [$this, 'render_checkout_fields'], 10, 1);
		add_action('wp_enqueue_scripts', [$this, 'frontend_scripts']);

		// Validación y guardado
		add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);
		add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_checkout_field_data'], 10, 4);

		// Mostrar en pedidos
		add_action('woocommerce_order_item_meta_end', [$this, 'display_order_item_meta'], 10, 4);
		add_filter('woocommerce_order_item_display_meta_key', [$this, 'format_meta_key'], 10, 3);
		add_filter('woocommerce_order_item_display_meta_value', [$this, 'format_meta_value'], 10, 3);

		// Mostrar en emails
		add_action('woocommerce_email_after_order_table', [$this, 'display_in_email'], 10, 4);

		// AJAX para gestión de campos
		add_action('wp_ajax_pn_cm_checkout_fields_get_field_types', [$this, 'ajax_get_field_types']);
	}

	/* ───────────────────────────────────────────
	 *  Admin: Product Tab
	 * ─────────────────────────────────────────── */

	/**
	 * Add product data tab
	 */
	public function product_data_tab($tabs)
	{
		// Check if checkout fields feature is enabled in settings
		$enabled = get_option('pn_customers_manager_woocommerce_checkout_fields_enabled', 'on');

		if ($enabled === 'on') {
			$tabs['pn_cm_woo_checkout_fields'] = [
				'label'    => __('Checkout Fields', 'pn-customers-manager'),
				'target'   => 'pn_cm_woo_checkout_fields_data',
				'class'    => ['show_if_simple', 'show_if_variable'],
				'priority' => 75, // Before Calendar (80)
			];
		}

		return $tabs;
	}

	/**
	 * Render product data panel
	 */
	public function product_data_panel()
	{
		global $post;
		$product_id = $post->ID;

		$enabled = get_post_meta($product_id, '_pn_cm_woo_checkout_fields_enabled', true);
		$fields = get_post_meta($product_id, '_pn_cm_woo_checkout_fields', true);
		if (!is_array($fields)) {
			$fields = [];
		}

		$show_in_product = get_post_meta($product_id, '_pn_cm_woo_checkout_fields_show_in_product', true);

		?>
		<div id="pn_cm_woo_checkout_fields_data" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php
				woocommerce_wp_checkbox([
					'id'          => '_pn_cm_woo_checkout_fields_enabled',
					'label'       => __('Enable custom fields', 'pn-customers-manager'),
					'description' => __('Add custom fields to checkout for this product', 'pn-customers-manager'),
					'value'       => $enabled === 'yes' ? 'yes' : 'no',
				]);

				woocommerce_wp_checkbox([
					'id'          => '_pn_cm_woo_checkout_fields_show_in_product',
					'label'       => __('Show fields in product page', 'pn-customers-manager'),
					'description' => __('Display fields in product page before add to cart button. If filled, data will be pre-filled in checkout.', 'pn-customers-manager'),
					'value'       => $show_in_product === 'yes' ? 'yes' : 'no',
				]);
				?>
			</div>

			<div class="pn-cm-checkout-fields-container" style="padding: 0 20px;">
				<h4><?php _e('Custom Fields', 'pn-customers-manager'); ?></h4>
				<p class="description">
					<?php _e('Define the fields that will be displayed at checkout when customers purchase this product.', 'pn-customers-manager'); ?>
				</p>

				<div id="pn-cm-checkout-fields-list">
					<?php
					if (!empty($fields)) {
						foreach ($fields as $index => $field) {
							$this->render_field_row($index, $field);
						}
					}
					?>
				</div>

				<button type="button" class="button pn-cm-add-checkout-field">
					<?php _e('+ Add Field', 'pn-customers-manager'); ?>
				</button>

				<input type="hidden" name="_pn_cm_woo_checkout_fields_nonce" value="<?php echo wp_create_nonce('pn_cm_checkout_fields'); ?>" />
			</div>

			<!-- Template para nuevo campo -->
			<script type="text/html" id="pn-cm-checkout-field-template">
				<?php $this->render_field_row('{{INDEX}}', []); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Render a single field row in admin
	 */
	private function render_field_row($index, $field)
	{
		$field = wp_parse_args($field, [
			'type'        => 'text',
			'label'       => '',
			'name'        => '',
			'placeholder' => '',
			'required'    => false,
			'options'     => '',
			'description' => '',
			'min'         => '',
			'max'         => '',
			'step'        => '',
			'multiple'    => false,
			'stars_number' => 5,
		]);

		$field_types = $this->get_available_field_types();
		?>
		<div class="pn-cm-checkout-field-row" data-index="<?php echo esc_attr($index); ?>">
			<div class="pn-cm-field-header">
				<span class="pn-cm-field-handle dashicons dashicons-menu"></span>
				<strong class="pn-cm-field-title">
					<?php echo esc_html($field['label'] ?: __('New Field', 'pn-customers-manager')); ?>
				</strong>
				<button type="button" class="button-link pn-cm-toggle-field">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</button>
				<button type="button" class="button-link pn-cm-remove-field" title="<?php _e('Delete', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>

			<div class="pn-cm-field-body" style="display: none;">
				<p>
					<label><?php _e('Field Type', 'pn-customers-manager'); ?></label>
					<select name="_pn_cm_checkout_fields[<?php echo $index; ?>][type]" class="pn-cm-field-type">
						<?php foreach ($field_types as $type => $label) : ?>
							<option value="<?php echo esc_attr($type); ?>" <?php selected($field['type'], $type); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label><?php _e('Label', 'pn-customers-manager'); ?></label>
					<input type="text"
						name="_pn_cm_checkout_fields[<?php echo $index; ?>][label]"
						value="<?php echo esc_attr($field['label']); ?>"
						class="widefat pn-cm-field-label-input"
						placeholder="<?php _e('E.g: Participant Name', 'pn-customers-manager'); ?>" />
				</p>

				<p>
					<label><?php _e('Internal Name (ID)', 'pn-customers-manager'); ?></label>
					<input type="text"
						name="_pn_cm_checkout_fields[<?php echo $index; ?>][name]"
						value="<?php echo esc_attr($field['name']); ?>"
						class="widefat"
						placeholder="<?php _e('E.g: participant_name', 'pn-customers-manager'); ?>" />
					<span class="description"><?php _e('Unique identifier (letters, numbers and underscores only)', 'pn-customers-manager'); ?></span>
				</p>

				<p>
					<label><?php _e('Placeholder', 'pn-customers-manager'); ?></label>
					<input type="text"
						name="_pn_cm_checkout_fields[<?php echo $index; ?>][placeholder]"
						value="<?php echo esc_attr($field['placeholder']); ?>"
						class="widefat"
						placeholder="<?php _e('Help text', 'pn-customers-manager'); ?>" />
				</p>

				<div class="pn-cm-field-options" style="<?php echo in_array($field['type'], ['select', 'radio']) ? '' : 'display:none;'; ?>">
					<label><?php _e('Options', 'pn-customers-manager'); ?></label>
					<div class="pn-cm-options-list" data-field-index="<?php echo $index; ?>">
						<?php
						$options = !empty($field['options']) ? array_filter(array_map('trim', explode("\n", $field['options']))) : [];
						if (!empty($options)) {
							foreach ($options as $opt_index => $option) {
								?>
								<div class="pn-cm-option-item">
									<span class="pn-cm-option-handle dashicons dashicons-menu"></span>
									<input type="text"
										name="_pn_cm_checkout_fields[<?php echo $index; ?>][options_array][]"
										value="<?php echo esc_attr($option); ?>"
										class="pn-cm-option-input"
										placeholder="<?php _e('Option value', 'pn-customers-manager'); ?>" />
									<button type="button" class="button pn-cm-remove-option" title="<?php _e('Remove option', 'pn-customers-manager'); ?>">
										<span class="dashicons dashicons-no"></span>
									</button>
								</div>
								<?php
							}
						}
						?>
					</div>
					<button type="button" class="button pn-cm-add-option">
						<span class="dashicons dashicons-plus"></span> <?php _e('Add Option', 'pn-customers-manager'); ?>
					</button>
					<!-- Hidden field to store serialized options -->
					<textarea name="_pn_cm_checkout_fields[<?php echo $index; ?>][options]" class="pn-cm-options-hidden" style="display:none;"><?php echo esc_textarea($field['options']); ?></textarea>
				</div>

				<div class="pn-cm-field-html-multi-config" style="<?php echo $field['type'] === 'html_multi' ? '' : 'display:none;'; ?>">
					<label><?php _e('Repeatable Fields Configuration', 'pn-customers-manager'); ?></label>
					<span class="description"><?php _e('Define the fields that will appear in each repeatable group', 'pn-customers-manager'); ?></span>
					<div class="pn-cm-html-multi-fields-list" data-field-index="<?php echo $index; ?>">
						<?php
						$html_multi_fields = !empty($field['html_multi_fields']) ? $field['html_multi_fields'] : [];
						if (!empty($html_multi_fields)) {
							foreach ($html_multi_fields as $hmf_index => $hmf) {
								?>
								<div class="pn-cm-html-multi-field-item">
									<div style="display: flex; gap: 10px; margin-bottom: 5px; align-items: center;">
										<input type="text"
											name="_pn_cm_checkout_fields[<?php echo $index; ?>][html_multi_fields][<?php echo $hmf_index; ?>][label]"
											value="<?php echo esc_attr($hmf['label'] ?? ''); ?>"
											placeholder="<?php _e('Field Label', 'pn-customers-manager'); ?>"
											style="flex: 2;" />
										<select name="_pn_cm_checkout_fields[<?php echo $index; ?>][html_multi_fields][<?php echo $hmf_index; ?>][type]"
											style="flex: 1;">
											<option value="text" <?php selected($hmf['type'] ?? 'text', 'text'); ?>><?php _e('Text', 'pn-customers-manager'); ?></option>
											<option value="email" <?php selected($hmf['type'] ?? '', 'email'); ?>><?php _e('Email', 'pn-customers-manager'); ?></option>
											<option value="tel" <?php selected($hmf['type'] ?? '', 'tel'); ?>><?php _e('Phone', 'pn-customers-manager'); ?></option>
											<option value="number" <?php selected($hmf['type'] ?? '', 'number'); ?>><?php _e('Number', 'pn-customers-manager'); ?></option>
											<option value="date" <?php selected($hmf['type'] ?? '', 'date'); ?>><?php _e('Date', 'pn-customers-manager'); ?></option>
											<option value="textarea" <?php selected($hmf['type'] ?? '', 'textarea'); ?>><?php _e('Textarea', 'pn-customers-manager'); ?></option>
										</select>
										<label style="margin: 0; display: flex; align-items: center; gap: 5px; white-space: nowrap;">
											<input type="checkbox"
												name="_pn_cm_checkout_fields[<?php echo $index; ?>][html_multi_fields][<?php echo $hmf_index; ?>][required]"
												value="1"
												<?php checked($hmf['required'] ?? false, true); ?> />
											<?php _e('Required', 'pn-customers-manager'); ?>
										</label>
										<button type="button" class="button pn-cm-remove-html-multi-field" title="<?php _e('Remove field', 'pn-customers-manager'); ?>">
											<span class="dashicons dashicons-no"></span>
										</button>
									</div>
								</div>
								<?php
							}
						}
						?>
					</div>
					<button type="button" class="button pn-cm-add-html-multi-field">
						<span class="dashicons dashicons-plus"></span> <?php _e('Add Field', 'pn-customers-manager'); ?>
					</button>
				</div>

				<div class="pn-cm-field-range-settings" style="<?php echo in_array($field['type'], ['number', 'range']) ? '' : 'display:none;'; ?>">
					<label><?php _e('Min / Max / Step', 'pn-customers-manager'); ?></label>
					<div style="display: flex; gap: 10px;">
						<input type="number"
							name="_pn_cm_checkout_fields[<?php echo $index; ?>][min]"
							value="<?php echo esc_attr($field['min']); ?>"
							placeholder="Min"
							style="flex: 1;" />
						<input type="number"
							name="_pn_cm_checkout_fields[<?php echo $index; ?>][max]"
							value="<?php echo esc_attr($field['max']); ?>"
							placeholder="Max"
							style="flex: 1;" />
						<input type="number"
							name="_pn_cm_checkout_fields[<?php echo $index; ?>][step]"
							value="<?php echo esc_attr($field['step']); ?>"
							placeholder="Step"
							style="flex: 1;" />
					</div>
				</div>

				<div class="pn-cm-field-stars-settings" style="<?php echo $field['type'] === 'stars' ? '' : 'display:none;'; ?>">
					<label><?php _e('Number of Stars', 'pn-customers-manager'); ?></label>
					<input type="number"
						name="_pn_cm_checkout_fields[<?php echo $index; ?>][stars_number]"
						value="<?php echo esc_attr($field['stars_number']); ?>"
						min="3"
						max="10"
						placeholder="5"
						class="widefat" />
				</div>

				<div class="pn-cm-field-multiple" style="<?php echo in_array($field['type'], ['image', 'video', 'audio', 'file']) ? '' : 'display:none;'; ?>">
					<label>
						<input type="checkbox"
							name="_pn_cm_checkout_fields[<?php echo $index; ?>][multiple]"
							value="1"
							<?php checked($field['multiple'], true); ?> />
						<?php _e('Allow Multiple Files', 'pn-customers-manager'); ?>
					</label>
				</div>

				<p>
					<label><?php _e('Description', 'pn-customers-manager'); ?></label>
					<textarea
						name="_pn_cm_checkout_fields[<?php echo $index; ?>][description]"
						class="widefat"
						rows="2"><?php echo esc_textarea($field['description']); ?></textarea>
				</p>

				<p>
					<label><?php _e('CSS Classes', 'pn-customers-manager'); ?></label>
					<input type="text"
						name="_pn_cm_checkout_fields[<?php echo $index; ?>][css_classes]"
						value="<?php echo esc_attr($field['css_classes'] ?? ''); ?>"
						class="widefat"
						placeholder="<?php _e('e.g: custom-class another-class', 'pn-customers-manager'); ?>" />
					<span class="description"><?php _e('Custom CSS classes separated by spaces', 'pn-customers-manager'); ?></span>
				</p>

				<p>
					<label>
						<input type="checkbox"
							name="_pn_cm_checkout_fields[<?php echo $index; ?>][required]"
							value="1"
							<?php checked($field['required'], true); ?> />
						<?php _e('Required Field', 'pn-customers-manager'); ?>
					</label>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Get available field types
	 */
	private function get_available_field_types()
	{
		return [
			// Basic input types
			'text'     => __('Text', 'pn-customers-manager'),
			'email'    => __('Email', 'pn-customers-manager'),
			'tel'      => __('Phone', 'pn-customers-manager'),
			'number'   => __('Number', 'pn-customers-manager'),
			'date'     => __('Date', 'pn-customers-manager'),
			'password' => __('Password', 'pn-customers-manager'),
			'color'    => __('Color Picker', 'pn-customers-manager'),
			'range'    => __('Range Slider', 'pn-customers-manager'),

			// Text areas and editors
			'textarea' => __('Textarea', 'pn-customers-manager'),
			'editor'   => __('WYSIWYG Editor', 'pn-customers-manager'),

			// Selection types
			'select'   => __('Select (dropdown)', 'pn-customers-manager'),
			'radio'    => __('Radio Options', 'pn-customers-manager'),
			'checkbox' => __('Checkbox', 'pn-customers-manager'),

			// Media types
			'image'    => __('Image Upload', 'pn-customers-manager'),
			'video'    => __('Video Upload', 'pn-customers-manager'),
			'audio'    => __('Audio Upload', 'pn-customers-manager'),
			'file'     => __('File Upload', 'pn-customers-manager'),

			// Advanced types
			'html_multi'     => __('Repeatable Fields', 'pn-customers-manager'),
			'audio_recorder' => __('Audio Recorder', 'pn-customers-manager'),
			'tags'           => __('Tags Input', 'pn-customers-manager'),
			'stars'          => __('Star Rating', 'pn-customers-manager'),
		];
	}

	/**
	 * Save product meta
	 */
	public function save_product_meta($post_id)
	{
		// Verify nonce
		if (!isset($_POST['_pn_cm_woo_checkout_fields_nonce']) ||
			!wp_verify_nonce($_POST['_pn_cm_woo_checkout_fields_nonce'], 'pn_cm_checkout_fields')) {
			return;
		}

		// Save enabled state
		$enabled = isset($_POST['_pn_cm_woo_checkout_fields_enabled']) ? 'yes' : 'no';
		update_post_meta($post_id, '_pn_cm_woo_checkout_fields_enabled', $enabled);

		// Save show in product state
		$show_in_product = isset($_POST['_pn_cm_woo_checkout_fields_show_in_product']) ? 'yes' : 'no';
		update_post_meta($post_id, '_pn_cm_woo_checkout_fields_show_in_product', $show_in_product);

		// Save fields
		$fields = isset($_POST['_pn_cm_checkout_fields']) ? $_POST['_pn_cm_checkout_fields'] : [];
		$sanitized_fields = [];

		foreach ($fields as $field) {
			// Process options - use the hidden field that contains serialized options
			$options = isset($field['options']) ? sanitize_textarea_field($field['options']) : '';

			// Process html_multi_fields
			$html_multi_fields = [];
			if (isset($field['html_multi_fields']) && is_array($field['html_multi_fields'])) {
				foreach ($field['html_multi_fields'] as $hmf) {
					if (!empty($hmf['label'])) {
						$html_multi_fields[] = [
							'label'    => sanitize_text_field($hmf['label']),
							'type'     => sanitize_text_field($hmf['type'] ?? 'text'),
							'required' => isset($hmf['required']) && $hmf['required'] == '1',
						];
					}
				}
			}

			$sanitized_fields[] = [
				'type'              => sanitize_text_field($field['type']),
				'label'             => sanitize_text_field($field['label']),
				'name'              => sanitize_key($field['name']),
				'placeholder'       => sanitize_text_field($field['placeholder'] ?? ''),
				'required'          => isset($field['required']) && $field['required'] == '1',
				'options'           => $options,
				'description'       => sanitize_textarea_field($field['description'] ?? ''),
				'css_classes'       => sanitize_text_field($field['css_classes'] ?? ''),
				'min'               => isset($field['min']) ? sanitize_text_field($field['min']) : '',
				'max'               => isset($field['max']) ? sanitize_text_field($field['max']) : '',
				'step'              => isset($field['step']) ? sanitize_text_field($field['step']) : '',
				'multiple'          => isset($field['multiple']) && $field['multiple'] == '1',
				'stars_number'      => isset($field['stars_number']) ? absint($field['stars_number']) : 5,
				'html_multi_fields' => $html_multi_fields,
			];
		}

		update_post_meta($post_id, '_pn_cm_woo_checkout_fields', $sanitized_fields);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function admin_scripts($hook)
	{
		if ('post.php' !== $hook && 'post-new.php' !== $hook) {
			return;
		}

		global $post;
		if (!$post || $post->post_type !== 'product') {
			return;
		}

		wp_enqueue_script(
			'pn-cm-checkout-fields-admin',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-checkout-fields-admin.js',
			['jquery', 'jquery-ui-sortable'],
			PN_CUSTOMERS_MANAGER_VERSION,
			true
		);

		wp_enqueue_style(
			'pn-cm-checkout-fields',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-cm-checkout-fields.css',
			[],
			PN_CUSTOMERS_MANAGER_VERSION
		);

		wp_localize_script('pn-cm-checkout-fields-admin', 'pnCmCheckoutFields', [
			'i18n' => [
				'newField' => __('New Field', 'pn-customers-manager'),
				'confirmRemove' => __('Are you sure you want to delete this field?', 'pn-customers-manager'),
			],
		]);
	}

	/* ───────────────────────────────────────────
	 *  Frontend: Product Page
	 * ─────────────────────────────────────────── */

	/**
	 * Render fields in product page
	 */
	public function render_product_fields()
	{
		global $product;

		if (!$product) {
			return;
		}

		// Check if feature is enabled
		$feature_enabled = get_option('pn_customers_manager_woocommerce_checkout_fields_enabled', 'on');
		if ($feature_enabled !== 'on') {
			return;
		}

		$product_id = $product->get_id();
		$enabled = get_post_meta($product_id, '_pn_cm_woo_checkout_fields_enabled', true);
		$show_in_product = get_post_meta($product_id, '_pn_cm_woo_checkout_fields_show_in_product', true);

		if ($enabled !== 'yes' || $show_in_product !== 'yes') {
			return;
		}

		$fields = get_post_meta($product_id, '_pn_cm_woo_checkout_fields', true);
		if (!is_array($fields) || empty($fields)) {
			return;
		}

		// Filter to only supported types
		$supported_fields = array_filter($fields, function($field) {
			return $this->is_supported_checkout_field($field['type']);
		});

		if (empty($supported_fields)) {
			return;
		}

		?>
		<div class="pn-cm-product-custom-fields">
			<h3><?php _e('Additional Information', 'pn-customers-manager'); ?></h3>
			<p class="description"><?php _e('Fill in the following information. This data will be saved with your order.', 'pn-customers-manager'); ?></p>

			<?php foreach ($supported_fields as $field_key => $field): ?>
				<?php if (empty($field['name'])) continue; ?>

				<?php
				$field_id = 'pn_cm_product_field_' . $field['name'];
				$field_name = 'pn_cm_product_fields[' . $field['name'] . ']';

				// Handle html_multi with custom rendering
				if ($field['type'] === 'html_multi') {
					$this->render_html_multi_field_product($field, $field_id);
					continue;
				}

				$args = [
					'type'        => $field['type'],
					'label'       => $field['label'],
					'placeholder' => $field['placeholder'],
					'required'    => $field['required'],
					'class'       => ['form-row-wide', 'pn-cm-product-field'],
					'label_class' => ['pn-cm-product-field-label'],
					'input_class' => ['pn-customers-manager-input'],
				];

				// For select, radio, checkbox: add options
				if (in_array($field['type'], ['select', 'radio', 'checkbox'])) {
					$options = array_filter(array_map('trim', explode("\n", $field['options'])));
					if (!empty($options)) {
						if ($field['type'] === 'select') {
							$args['options'] = ['' => __('Select...', 'pn-customers-manager')] + array_combine($options, $options);
							$args['input_class'][] = 'pn-customers-manager-select';
						} else {
							$args['options'] = array_combine($options, $options);
						}
					}
				}

				// For number/range: add min/max/step
				if (in_array($field['type'], ['number', 'range'])) {
					$custom_attrs = [];
					if (!empty($field['min'])) $custom_attrs['min'] = $field['min'];
					if (!empty($field['max'])) $custom_attrs['max'] = $field['max'];
					if (!empty($field['step'])) $custom_attrs['step'] = $field['step'];
					if (!empty($custom_attrs)) $args['custom_attributes'] = $custom_attrs;

					if ($field['type'] === 'range') {
						$args['input_class'][] = 'pn-customers-manager-input-range';
					}
				}

				// Description
				if (!empty($field['description'])) {
					$args['description'] = $field['description'];
				}

				// Add custom CSS classes
				if (!empty($field['css_classes'])) {
					$custom_classes = array_filter(array_map('trim', explode(' ', $field['css_classes'])));
					foreach ($custom_classes as $custom_class) {
						$args['input_class'][] = sanitize_html_class($custom_class);
					}
				}

				woocommerce_form_field($field_name, $args, '');
				?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render html_multi field in product page
	 */
	private function render_html_multi_field_product($field, $field_id)
	{
		if (empty($field['html_multi_fields'])) {
			return;
		}

		$required_class = $field['required'] ? 'validate-required' : '';
		$required_label = $field['required'] ? ' <abbr class="required" title="required">*</abbr>' : '';
		$field_name = 'pn_cm_product_fields[' . $field['name'] . ']';

		?>
		<div class="form-row form-row-wide <?php echo esc_attr($required_class); ?> pn-cm-html-multi-wrapper pn-cm-product-html-multi" data-field-name="<?php echo esc_attr($field['name']); ?>">
			<label><?php echo esc_html($field['label']) . $required_label; ?></label>

			<?php if (!empty($field['description'])): ?>
				<span class="description"><?php echo esc_html($field['description']); ?></span>
			<?php endif; ?>

			<div class="pn-cm-html-multi-groups-container">
				<!-- Initial group -->
				<div class="pn-cm-html-multi-group" data-index="0">
					<div class="pn-cm-html-multi-fields">
						<?php foreach ($field['html_multi_fields'] as $hmf_index => $hmf): ?>
							<?php
							$sub_field_name = $field_name . '[0][' . $hmf_index . ']';
							$sub_args = [
								'type'        => $hmf['type'],
								'label'       => $hmf['label'],
								'placeholder' => '',
								'required'    => !empty($hmf['required']),
								'class'       => ['pn-cm-html-multi-field'],
								'label_class' => ['pn-cm-html-multi-field-label'],
								'input_class' => ['pn-customers-manager-input'],
							];

							// Add specific class for select
							if ($hmf['type'] === 'select') {
								$sub_args['input_class'][] = 'pn-customers-manager-select';
							}
							?>
							<div class="pn-cm-html-multi-field-wrapper">
								<?php woocommerce_form_field($sub_field_name, $sub_args, ''); ?>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="pn-cm-html-multi-actions">
						<a href="#" class="pn-cm-remove-html-multi-group pn-customers-manager-tooltip" style="display: none;" title="<?php esc_attr_e('Remove element', 'pn-customers-manager'); ?>">
							<i class="material-icons-outlined pn-customers-manager-cursor-pointer">remove</i>
						</a>
					</div>
				</div>
			</div>

			<div style="text-align: right; margin-top: 10px;">
				<a href="#" class="pn-cm-add-html-multi-group pn-customers-manager-tooltip" data-field-name="<?php echo esc_attr($field['name']); ?>" title="<?php esc_attr_e('Add element', 'pn-customers-manager'); ?>">
					<i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-40">add</i>
				</a>
			</div>

			<!-- Template for new groups -->
			<script type="text/template" id="<?php echo esc_attr($field['name']); ?>-product-template">
				<div class="pn-cm-html-multi-group" data-index="{{INDEX}}">
					<div class="pn-cm-html-multi-fields">
						<?php foreach ($field['html_multi_fields'] as $hmf_index => $hmf): ?>
							<?php
							$sub_field_name = $field_name . '[{{INDEX}}][' . $hmf_index . ']';
							$sub_args = [
								'type'        => $hmf['type'],
								'label'       => $hmf['label'],
								'placeholder' => '',
								'required'    => !empty($hmf['required']),
								'class'       => ['pn-cm-html-multi-field'],
								'label_class' => ['pn-cm-html-multi-field-label'],
								'input_class' => ['pn-customers-manager-input'],
							];

							// Add specific class for select
							if ($hmf['type'] === 'select') {
								$sub_args['input_class'][] = 'pn-customers-manager-select';
							}
							?>
							<div class="pn-cm-html-multi-field-wrapper">
								<?php
								ob_start();
								woocommerce_form_field($sub_field_name, $sub_args, '');
								$field_html = ob_get_clean();
								echo $field_html;
								?>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="pn-cm-html-multi-actions">
						<a href="#" class="pn-cm-remove-html-multi-group pn-customers-manager-tooltip" title="<?php esc_attr_e('Remove element', 'pn-customers-manager'); ?>">
							<i class="material-icons-outlined pn-customers-manager-cursor-pointer">remove</i>
						</a>
					</div>
				</div>
			</script>
		</div>
		<?php
	}

	/**
	 * Add custom field data to cart item
	 */
	public function add_cart_item_data($cart_item_data, $product_id, $variation_id)
	{
		if (isset($_POST['pn_cm_product_fields'])) {
			$cart_item_data['pn_cm_product_fields'] = $_POST['pn_cm_product_fields'];
		}

		return $cart_item_data;
	}

	/**
	 * Display custom field data in cart
	 */
	public function display_cart_item_data($item_data, $cart_item)
	{
		if (isset($cart_item['pn_cm_product_fields'])) {
			$product_id = $cart_item['product_id'];
			$fields_config = get_post_meta($product_id, '_pn_cm_woo_checkout_fields', true);

			if (is_array($fields_config)) {
				foreach ($fields_config as $field) {
					if (isset($cart_item['pn_cm_product_fields'][$field['name']])) {
						$value = $cart_item['pn_cm_product_fields'][$field['name']];

						// Handle html_multi
						if ($field['type'] === 'html_multi' && is_array($value)) {
							$display_value = '';
							foreach ($value as $group_index => $group_data) {
								$display_value .= sprintf(__('Group %d', 'pn-customers-manager'), $group_index + 1) . ': ';
								$group_values = [];
								if (is_array($group_data)) {
									foreach ($field['html_multi_fields'] as $hmf_index => $hmf) {
										if (isset($group_data[$hmf_index])) {
											$group_values[] = $hmf['label'] . ': ' . $group_data[$hmf_index];
										}
									}
								}
								$display_value .= implode(', ', $group_values) . ' | ';
							}
							$value = rtrim($display_value, ' | ');
						}

						$item_data[] = [
							'key'   => $field['label'],
							'value' => is_array($value) ? implode(', ', $value) : $value,
						];
					}
				}
			}
		}

		return $item_data;
	}

	/* ───────────────────────────────────────────
	 *  Frontend: Checkout
	 * ─────────────────────────────────────────── */

	/**
	 * Get supported checkout field types
	 * Only field types that work with woocommerce_form_field or have custom rendering
	 */
	private function get_supported_checkout_field_types()
	{
		return [
			'text',
			'textarea',
			'email',
			'tel',
			'number',
			'date',
			'password',
			'color',
			'range',
			'select',
			'radio',
			'checkbox',
			'html_multi', // Custom rendering
		];
	}

	/**
	 * Check if a field type is supported in checkout
	 */
	private function is_supported_checkout_field($field_type)
	{
		return in_array($field_type, $this->get_supported_checkout_field_types());
	}

	/**
	 * Render checkout fields
	 */
	public function render_checkout_fields($checkout)
	{
		// Check if checkout fields feature is enabled in settings
		$feature_enabled = get_option('pn_customers_manager_woocommerce_checkout_fields_enabled', 'on');

		if ($feature_enabled !== 'on') {
			return;
		}

		// Obtener productos del carrito que tengan campos personalizados
		$products_with_fields = $this->get_cart_products_with_fields();

		if (empty($products_with_fields)) {
			return;
		}

		echo '<div class="pn-cm-checkout-custom-fields">';

		foreach ($products_with_fields as $product_data) {
			$product_id = $product_data['product_id'];
			$cart_item_key = $product_data['cart_item_key'];
			$product = wc_get_product($product_id);
			$fields = $product_data['fields'];
			$cart_item = $product_data['cart_item'];

			if (empty($fields)) {
				continue;
			}

			// Filter fields to only include supported types
			$supported_fields = array_filter($fields, function($field) {
				return $this->is_supported_checkout_field($field['type']);
			});

			if (empty($supported_fields)) {
				continue;
			}

			echo '<div class="pn-cm-checkout-product-fields" data-product-id="' . esc_attr($product_id) . '">';
			echo '<h3>' . sprintf(__('Additional information for: %s', 'pn-customers-manager'), esc_html($product->get_name())) . '</h3>';

			foreach ($supported_fields as $field_key => $field) {
				if (empty($field['name'])) {
					continue;
				}

				$field_id = 'pn_cm_field_' . $product_id . '_' . $field['name'];
				$field_name = 'pn_cm_checkout_field[' . $cart_item_key . '][' . $field['name'] . ']';

				// Handle html_multi with custom rendering
				if ($field['type'] === 'html_multi') {
					$this->render_html_multi_field($field, $field_id, $product_id, $checkout);
					continue;
				}

				$args = [
					'type'        => $field['type'],
					'label'       => $field['label'],
					'placeholder' => $field['placeholder'],
					'required'    => $field['required'],
					'class'       => ['form-row-wide'],
					'input_class' => ['pn-customers-manager-input'],
					'clear'       => true,
				];

				// Add type-specific plugin classes
				if ($field['type'] === 'select') {
					$args['input_class'][] = 'pn-customers-manager-select';
				} elseif ($field['type'] === 'range') {
					$args['input_class'][] = 'pn-customers-manager-input-range';
				}

				// Add custom CSS classes
				if (!empty($field['css_classes'])) {
					$custom_classes = array_filter(array_map('trim', explode(' ', $field['css_classes'])));
					foreach ($custom_classes as $custom_class) {
						$args['input_class'][] = sanitize_html_class($custom_class);
					}
				}

				// For select, radio, checkbox: add options
				if (in_array($field['type'], ['select', 'radio', 'checkbox'])) {
					$options = array_filter(array_map('trim', explode("\n", $field['options'])));
					if (!empty($options)) {
						if ($field['type'] === 'select') {
							$args['options'] = ['' => __('Select...', 'pn-customers-manager')] + array_combine($options, $options);
						} else {
							$args['options'] = array_combine($options, $options);
						}
					}
				}

				// For number/range: add min/max/step
				if (in_array($field['type'], ['number', 'range'])) {
					$custom_attrs = [];
					if (!empty($field['min'])) {
						$custom_attrs['min'] = $field['min'];
					}
					if (!empty($field['max'])) {
						$custom_attrs['max'] = $field['max'];
					}
					if (!empty($field['step'])) {
						$custom_attrs['step'] = $field['step'];
					}
					if (!empty($custom_attrs)) {
						$args['custom_attributes'] = $custom_attrs;
					}
				}

				// Description
				if (!empty($field['description'])) {
					$args['description'] = $field['description'];
				}

				// Get default value from cart item if exists
				$default_value = '';
				if (isset($cart_item['pn_cm_product_fields'][$field['name']])) {
					$default_value = $cart_item['pn_cm_product_fields'][$field['name']];
				}

				// Render field
				woocommerce_form_field($field_id, $args, $checkout->get_value($field_id) ?: $default_value);
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Render html_multi field type
	 */
	private function render_html_multi_field($field, $field_id, $product_id, $checkout)
	{
		if (empty($field['html_multi_fields'])) {
			return;
		}

		$required_class = $field['required'] ? 'validate-required' : '';
		$required_label = $field['required'] ? ' <abbr class="required" title="required">*</abbr>' : '';

		?>
		<div class="form-row form-row-wide <?php echo esc_attr($required_class); ?> pn-cm-html-multi-wrapper" data-field-id="<?php echo esc_attr($field_id); ?>">
			<label><?php echo esc_html($field['label']) . $required_label; ?></label>

			<?php if (!empty($field['description'])): ?>
				<span class="description"><?php echo esc_html($field['description']); ?></span>
			<?php endif; ?>

			<div class="pn-cm-html-multi-groups-container">
				<!-- Initial group -->
				<div class="pn-cm-html-multi-group" data-index="0">
					<div class="pn-cm-html-multi-fields">
						<?php foreach ($field['html_multi_fields'] as $hmf_index => $hmf): ?>
							<?php
							$sub_field_id = $field_id . '_' . $hmf_index . '_0';
							$sub_field_name = $field_id . '[0][' . $hmf_index . ']';

							$sub_args = [
								'type'        => $hmf['type'],
								'label'       => $hmf['label'],
								'placeholder' => '',
								'required'    => !empty($hmf['required']),
								'class'       => ['pn-cm-html-multi-field'],
								'input_class' => ['pn-customers-manager-input'],
								'label_class' => ['pn-cm-html-multi-field-label'],
							];
							?>
							<div class="pn-cm-html-multi-field-wrapper">
								<?php woocommerce_form_field($sub_field_name, $sub_args, ''); ?>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="pn-cm-html-multi-actions">
						<a href="#" class="pn-cm-remove-html-multi-group pn-customers-manager-tooltip" style="display: none;" title="<?php esc_attr_e('Remove element', 'pn-customers-manager'); ?>">
							<i class="material-icons-outlined pn-customers-manager-cursor-pointer">remove</i>
						</a>
					</div>
				</div>
			</div>

			<div style="text-align: right; margin-top: 10px;">
				<a href="#" class="pn-cm-add-html-multi-group pn-customers-manager-tooltip" data-field-id="<?php echo esc_attr($field_id); ?>" title="<?php esc_attr_e('Add element', 'pn-customers-manager'); ?>">
					<i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-40">add</i>
				</a>
			</div>

			<!-- Template for new groups -->
			<script type="text/template" id="<?php echo esc_attr($field_id); ?>-template">
				<div class="pn-cm-html-multi-group" data-index="{{INDEX}}">
					<div class="pn-cm-html-multi-fields">
						<?php foreach ($field['html_multi_fields'] as $hmf_index => $hmf): ?>
							<?php
							$sub_field_name = $field_id . '[{{INDEX}}][' . $hmf_index . ']';
							$sub_args = [
								'type'        => $hmf['type'],
								'label'       => $hmf['label'],
								'placeholder' => '',
								'required'    => !empty($hmf['required']),
								'class'       => ['pn-cm-html-multi-field'],
								'input_class' => ['pn-customers-manager-input'],
								'label_class' => ['pn-cm-html-multi-field-label'],
							];
							?>
							<div class="pn-cm-html-multi-field-wrapper">
								<?php
								// Capture the output to use in template
								ob_start();
								woocommerce_form_field($sub_field_name, $sub_args, '');
								$field_html = ob_get_clean();
								echo $field_html;
								?>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="pn-cm-html-multi-actions">
						<a href="#" class="pn-cm-remove-html-multi-group pn-customers-manager-tooltip" title="<?php esc_attr_e('Remove element', 'pn-customers-manager'); ?>">
							<i class="material-icons-outlined pn-customers-manager-cursor-pointer">remove</i>
						</a>
					</div>
				</div>
			</script>
		</div>
		<?php
	}

	/**
	 * Get cart products with custom checkout fields
	 */
	private function get_cart_products_with_fields()
	{
		$products = [];

		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			$product_id = $cart_item['product_id'];
			$enabled = get_post_meta($product_id, '_pn_cm_woo_checkout_fields_enabled', true);

			if ($enabled === 'yes') {
				$fields = get_post_meta($product_id, '_pn_cm_woo_checkout_fields', true);
				if (is_array($fields) && !empty($fields)) {
					$products[] = [
						'product_id' => $product_id,
						'cart_item_key' => $cart_item_key,
						'cart_item' => $cart_item,
						'fields' => $fields,
					];
				}
			}
		}

		return $products;
	}

	/**
	 * Validate checkout fields
	 */
	public function validate_checkout_fields()
	{
		// Check if checkout fields feature is enabled in settings
		$feature_enabled = get_option('pn_customers_manager_woocommerce_checkout_fields_enabled', 'on');

		if ($feature_enabled !== 'on') {
			return;
		}

		$products_with_fields = $this->get_cart_products_with_fields();

		foreach ($products_with_fields as $product_data) {
			$product_id = $product_data['product_id'];
			$cart_item_key = $product_data['cart_item_key'];
			$fields = $product_data['fields'];
			$product = wc_get_product($product_id);

			foreach ($fields as $field) {
				if (empty($field['name'])) {
					continue;
				}

				// Skip unsupported field types
				if (!$this->is_supported_checkout_field($field['type'])) {
					continue;
				}

				$field_id = 'pn_cm_field_' . $product_id . '_' . $field['name'];

				// Handle html_multi separately (comes as array)
				if ($field['type'] === 'html_multi') {
					$value = isset($_POST[$field_id]) ? $_POST[$field_id] : [];

					// Validate required fields - at least one group should be filled
					if ($field['required'] && (empty($value) || !is_array($value))) {
						wc_add_notice(
							sprintf(
								__('The field "%s" is required for %s', 'pn-customers-manager'),
								$field['label'],
								$product->get_name()
							),
							'error'
						);
					}

					// Validate required sub-fields within each group
					if (is_array($value) && !empty($field['html_multi_fields'])) {
						foreach ($value as $group_index => $group_data) {
							if (is_array($group_data)) {
								foreach ($field['html_multi_fields'] as $hmf_index => $hmf) {
									if (!empty($hmf['required'])) {
										$sub_value = isset($group_data[$hmf_index]) ? trim($group_data[$hmf_index]) : '';

										if (empty($sub_value)) {
											wc_add_notice(
												sprintf(
													__('"%s" is required in Group %d of %s', 'pn-customers-manager'),
													$hmf['label'],
													$group_index + 1,
													$field['label']
												),
												'error'
											);
										}

										// Validate email fields
										if ($hmf['type'] === 'email' && !empty($sub_value) && !is_email($sub_value)) {
											wc_add_notice(
												sprintf(
													__('"%s" in Group %d must be a valid email', 'pn-customers-manager'),
													$hmf['label'],
													$group_index + 1
												),
												'error'
											);
										}
									}
								}
							}
						}
					}
				} else {
					$value = isset($_POST[$field_id]) ? sanitize_text_field($_POST[$field_id]) : '';

					// Validate required fields
					if ($field['required'] && empty($value)) {
						wc_add_notice(
							sprintf(
								__('The field "%s" is required for %s', 'pn-customers-manager'),
								$field['label'],
								$product->get_name()
							),
							'error'
						);
					}

					// Validate email
					if ($field['type'] === 'email' && !empty($value) && !is_email($value)) {
						wc_add_notice(
							sprintf(
								__('The field "%s" must be a valid email', 'pn-customers-manager'),
								$field['label']
							),
							'error'
						);
					}
				}
			}
		}
	}

	/**
	 * Save checkout field data to order item meta
	 */
	public function save_checkout_field_data($item, $cart_item_key, $values, $order)
	{
		// Check if checkout fields feature is enabled in settings
		$feature_enabled = get_option('pn_customers_manager_woocommerce_checkout_fields_enabled', 'on');

		if ($feature_enabled !== 'on') {
			return;
		}

		$product_id = $values['product_id'];
		$enabled = get_post_meta($product_id, '_pn_cm_woo_checkout_fields_enabled', true);

		if ($enabled !== 'yes') {
			return;
		}

		$fields = get_post_meta($product_id, '_pn_cm_woo_checkout_fields', true);
		if (!is_array($fields) || empty($fields)) {
			return;
		}

		foreach ($fields as $field) {
			if (empty($field['name'])) {
				continue;
			}

			// Skip unsupported field types
			if (!$this->is_supported_checkout_field($field['type'])) {
				continue;
			}

			// Use the same field_id format as in render and validate
			$field_id = 'pn_cm_field_' . $product_id . '_' . $field['name'];

			// Handle html_multi separately (comes as array)
			if ($field['type'] === 'html_multi') {
				$value = isset($_POST[$field_id]) && is_array($_POST[$field_id]) ? $_POST[$field_id] : [];

				// Sanitize the multi-dimensional array
				$sanitized_value = [];
				foreach ($value as $group_index => $group_data) {
					if (is_array($group_data)) {
						foreach ($group_data as $sub_field_index => $sub_field_value) {
							$sanitized_value[$group_index][$sub_field_index] = sanitize_text_field($sub_field_value);
						}
					}
				}

				if (!empty($sanitized_value)) {
					// Save to order item meta
					$item->add_meta_data(
						'_pn_cm_checkout_field_' . $field['name'],
						$sanitized_value,
						true
					);

					// Also save field configuration for proper display
					$item->add_meta_data(
						'_pn_cm_checkout_field_config_' . $field['name'],
						$field,
						true
					);
				}
			} else {
				$value = isset($_POST[$field_id]) ? sanitize_text_field($_POST[$field_id]) : '';

				if (!empty($value) || $field['required']) {
					// Save to order item meta
					$item->add_meta_data(
						'_pn_cm_checkout_field_' . $field['name'],
						$value,
						true
					);

					// Also save field configuration for proper display
					$item->add_meta_data(
						'_pn_cm_checkout_field_config_' . $field['name'],
						$field,
						true
					);
				}
			}
		}
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function frontend_scripts()
	{
		// Load on checkout and product pages
		if (!is_checkout() && !is_product()) {
			return;
		}

		// Enqueue material icons for buttons
		if (!wp_style_is('wph-material-icons-outlined', 'enqueued')) {
			wp_enqueue_style(
				'wph-material-icons-outlined',
				PN_CUSTOMERS_MANAGER_URL . 'assets/css/material-icons-outlined.min.css',
				[],
				PN_CUSTOMERS_MANAGER_VERSION
			);
		}

		// Enqueue tooltips CSS
		if (!wp_style_is('pn-customers-manager-tooltips', 'enqueued')) {
			wp_enqueue_style(
				'pn-customers-manager-tooltips',
				PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-tooltips.css',
				[],
				PN_CUSTOMERS_MANAGER_VERSION
			);
		}

		// Enqueue main plugin CSS for input styles
		if (!wp_style_is('pn-customers-manager', 'enqueued')) {
			wp_enqueue_style(
				'pn-customers-manager',
				PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css',
				[],
				PN_CUSTOMERS_MANAGER_VERSION
			);
		}

		// Enqueue checkout fields specific CSS
		wp_enqueue_style(
			'pn-cm-checkout-fields',
			PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-cm-checkout-fields.css',
			['pn-customers-manager'],
			PN_CUSTOMERS_MANAGER_VERSION
		);

		// Enqueue tooltips JS
		if (!wp_script_is('pn-customers-manager-tooltips', 'enqueued')) {
			wp_enqueue_script(
				'pn-customers-manager-tooltips',
				PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-tooltips.js',
				['jquery'],
				PN_CUSTOMERS_MANAGER_VERSION,
				true
			);
		}

		// Enqueue checkout fields frontend JS
		wp_enqueue_script(
			'pn-cm-checkout-fields-frontend',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-cm-checkout-fields-frontend.js',
			['jquery'],
			PN_CUSTOMERS_MANAGER_VERSION,
			true
		);
	}

	/* ───────────────────────────────────────────
	 *  Display in Orders
	 * ─────────────────────────────────────────── */

	/**
	 * Display custom fields in order item meta
	 */
	public function display_order_item_meta($item_id, $item, $order, $plain_text)
	{
		$meta_data = $item->get_meta_data();

		foreach ($meta_data as $meta) {
			$key = $meta->key;

			// Only show our custom fields
			if (strpos($key, '_pn_cm_checkout_field_') === 0 && strpos($key, '_config_') === false) {
				$field_name = str_replace('_pn_cm_checkout_field_', '', $key);
				$config = $item->get_meta('_pn_cm_checkout_field_config_' . $field_name, true);

				if ($config && isset($config['label'])) {
					$label = $config['label'];
					$value = $meta->value;

					// Handle html_multi fields (array values)
					if ($config['type'] === 'html_multi' && is_array($value)) {
						if ($plain_text) {
							echo "\n" . $label . ':';
							foreach ($value as $group_index => $group_data) {
								echo "\n  Group " . ($group_index + 1) . ':';
								if (is_array($group_data) && !empty($config['html_multi_fields'])) {
									foreach ($config['html_multi_fields'] as $hmf_index => $hmf) {
										if (isset($group_data[$hmf_index])) {
											echo "\n    " . $hmf['label'] . ': ' . $group_data[$hmf_index];
										}
									}
								}
							}
						} else {
							echo '<div class="pn-cm-checkout-field-display pn-cm-html-multi-display">';
							echo '<strong>' . esc_html($label) . ':</strong>';
							echo '<div class="pn-cm-html-multi-groups">';
							foreach ($value as $group_index => $group_data) {
								echo '<div class="pn-cm-html-multi-group-display">';
								echo '<strong>' . sprintf(__('Group %d', 'pn-customers-manager'), $group_index + 1) . ':</strong>';
								echo '<ul>';
								if (is_array($group_data) && !empty($config['html_multi_fields'])) {
									foreach ($config['html_multi_fields'] as $hmf_index => $hmf) {
										if (isset($group_data[$hmf_index])) {
											echo '<li><strong>' . esc_html($hmf['label']) . ':</strong> ' . esc_html($group_data[$hmf_index]) . '</li>';
										}
									}
								}
								echo '</ul>';
								echo '</div>';
							}
							echo '</div>';
							echo '</div>';
						}
					} else {
						// Regular fields
						if ($plain_text) {
							echo "\n" . $label . ': ' . $value;
						} else {
							echo '<div class="pn-cm-checkout-field-display">';
							echo '<strong>' . esc_html($label) . ':</strong> ';
							echo esc_html($value);
							echo '</div>';
						}
					}
				}
			}
		}
	}

	/**
	 * Format meta key for display
	 */
	public function format_meta_key($display_key, $meta, $item)
	{
		if (strpos($display_key, '_pn_cm_checkout_field_') === 0 && strpos($display_key, '_config_') === false) {
			$field_name = str_replace('_pn_cm_checkout_field_', '', $display_key);
			$config = $item->get_meta('_pn_cm_checkout_field_config_' . $field_name, true);

			if ($config && isset($config['label'])) {
				return $config['label'];
			}
		}

		return $display_key;
	}

	/**
	 * Format meta value for display
	 */
	public function format_meta_value($display_value, $meta, $item)
	{
		if (strpos($meta->key, '_pn_cm_checkout_field_') === 0 && strpos($meta->key, '_config_') === false) {
			return esc_html($display_value);
		}

		return $display_value;
	}

	/**
	 * Display custom fields in emails
	 */
	public function display_in_email($order, $sent_to_admin, $plain_text, $email)
	{
		$items = $order->get_items();
		$has_custom_fields = false;

		// Check if there are custom fields
		foreach ($items as $item) {
			$meta_data = $item->get_meta_data();
			foreach ($meta_data as $meta) {
				if (strpos($meta->key, '_pn_cm_checkout_field_') === 0 && strpos($meta->key, '_config_') === false) {
					$has_custom_fields = true;
					break 2;
				}
			}
		}

		if (!$has_custom_fields) {
			return;
		}

		if ($plain_text) {
			echo "\n" . __('ADDITIONAL ORDER INFORMATION', 'pn-customers-manager') . "\n\n";
		} else {
			echo '<h2>' . __('Additional Order Information', 'pn-customers-manager') . '</h2>';
			echo '<div class="pn-cm-checkout-fields-email">';
		}

		foreach ($items as $item) {
			$product_name = $item->get_name();
			$meta_data = $item->get_meta_data();
			$item_has_fields = false;

			// Check if this item has fields
			foreach ($meta_data as $meta) {
				if (strpos($meta->key, '_pn_cm_checkout_field_') === 0 && strpos($meta->key, '_config_') === false) {
					$item_has_fields = true;
					break;
				}
			}

			if (!$item_has_fields) {
				continue;
			}

			if ($plain_text) {
				echo $product_name . ":\n";
			} else {
				echo '<h3>' . esc_html($product_name) . '</h3>';
				echo '<ul>';
			}

			foreach ($meta_data as $meta) {
				$key = $meta->key;

				if (strpos($key, '_pn_cm_checkout_field_') === 0 && strpos($key, '_config_') === false) {
					$field_name = str_replace('_pn_cm_checkout_field_', '', $key);
					$config = $item->get_meta('_pn_cm_checkout_field_config_' . $field_name, true);

					if ($config && isset($config['label'])) {
						$label = $config['label'];
						$value = $meta->value;

						if ($plain_text) {
							echo "  " . $label . ': ' . $value . "\n";
						} else {
							echo '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
						}
					}
				}
			}

			if (!$plain_text) {
				echo '</ul>';
			} else {
				echo "\n";
			}
		}

		if (!$plain_text) {
			echo '</div>';
		}
	}

	/* ───────────────────────────────────────────
	 *  AJAX Handlers
	 * ─────────────────────────────────────────── */

	/**
	 * AJAX: Get available field types
	 */
	public function ajax_get_field_types()
	{
		wp_send_json_success($this->get_available_field_types());
	}
}
