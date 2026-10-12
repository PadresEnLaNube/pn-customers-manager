<?php
/**
 * Mockup Flow Viewer.
 *
 * Provides a Figma-style flow viewer for mockup screens with connections,
 * labels, comments and mobile/desktop versions.
 *
 * @link       padresenlanube.com/
 * @since      1.2.53
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

class PN_CUSTOMERS_MANAGER_Mockup_Flow
{
	/**
	 * Register the Mockup Flow custom post type.
	 */
	public static function register_post_type()
	{
		$slug = get_option('pn_customers_manager_mockup_flow_slug', 'mockup-flow');

		register_post_type('pn_cm_mockup_flow', [
			'labels' => [
				'name'               => __('Mockup Flows', 'pn-customers-manager'),
				'singular_name'      => __('Mockup Flow', 'pn-customers-manager'),
				'add_new'            => __('Add New', 'pn-customers-manager'),
				'add_new_item'       => __('Add New Mockup Flow', 'pn-customers-manager'),
				'edit_item'          => __('Edit Mockup Flow', 'pn-customers-manager'),
				'new_item'           => __('New Mockup Flow', 'pn-customers-manager'),
				'view_item'          => __('View Mockup Flow', 'pn-customers-manager'),
				'search_items'       => __('Search Mockup Flows', 'pn-customers-manager'),
				'not_found'          => __('No mockup flows found', 'pn-customers-manager'),
				'not_found_in_trash' => __('No mockup flows found in Trash', 'pn-customers-manager'),
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'show_in_rest' => true,
			'supports'     => ['title'],
			'has_archive'  => false,
			'rewrite'      => ['slug' => $slug],
			'capabilities' => PN_CUSTOMERS_MANAGER_ROLE_PN_CM_MOCKUP_FLOW_CAPABILITIES,
		]);
	}

	/**
	 * Add meta box to the Mockup Flow edit screen.
	 */
	public static function add_meta_box()
	{
		add_meta_box(
			'pn_cm_mockup_flow_canvas',
			__('Mockup Flow Canvas', 'pn-customers-manager'),
			[__CLASS__, 'render_meta_box'],
			'pn_cm_mockup_flow',
			'normal',
			'high'
		);
	}

	/**
	 * Render the admin meta box with canvas editor.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_meta_box($post)
	{
		wp_enqueue_media();

		// Mockup Flow assets
		wp_enqueue_style('pn-customers-manager-mockup-flow', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-mockup-flow.css', [], PN_CUSTOMERS_MANAGER_VERSION);
		wp_enqueue_script('pn-customers-manager-mockup-flow-admin', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-mockup-flow.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);

		$canvas_data = get_post_meta($post->ID, 'pn_cm_mockup_flow_canvas_data', true);
		if (empty($canvas_data)) {
			$canvas_data = wp_json_encode(['mockups' => [], 'connections' => [], 'labels' => []]);
		}
		// Re-encode with unescaped unicode so accented characters render correctly
		$canvas_data = self::normalize_canvas_json($canvas_data);

		$image_urls = self::get_image_urls_map($canvas_data);

		wp_localize_script('pn-customers-manager-mockup-flow-admin', 'pnCmMockupFlow', [
			'ajaxUrl'       => admin_url('admin-ajax.php'),
			'nonce'         => wp_create_nonce('pn-customers-manager-nonce'),
			'mockupFlowId'  => $post->ID,
			'canvasData'    => $canvas_data,
			'imageUrls'     => $image_urls,
			'isAdmin'       => true,
			'i18n'          => [
				'addMockup'       => __('Add Mockup', 'pn-customers-manager'),
				'addLabel'        => __('Add Label', 'pn-customers-manager'),
				'connect'         => __('Connect', 'pn-customers-manager'),
				'save'            => __('Save', 'pn-customers-manager'),
				'delete'          => __('Delete', 'pn-customers-manager'),
				'title'           => __('Title', 'pn-customers-manager'),
				'description'     => __('Description', 'pn-customers-manager'),
				'order'           => __('Order', 'pn-customers-manager'),
				'desktopImage'    => __('Desktop Image', 'pn-customers-manager'),
				'mobileImage'     => __('Mobile Image', 'pn-customers-manager'),
				'selectImage'     => __('Select Image', 'pn-customers-manager'),
				'removeImage'     => __('Remove', 'pn-customers-manager'),
				'label'           => __('Label', 'pn-customers-manager'),
				'fontSize'        => __('Font Size', 'pn-customers-manager'),
				'color'           => __('Color', 'pn-customers-manager'),
				'connectionLabel' => __('Connection Label', 'pn-customers-manager'),
				'editMockup'      => __('Edit Mockup', 'pn-customers-manager'),
				'properties'      => __('Properties', 'pn-customers-manager'),
				'noSelection'     => __('Select a mockup, label or connection to edit its properties.', 'pn-customers-manager'),
				'saved'           => __('Canvas saved successfully.', 'pn-customers-manager'),
				'saveError'       => __('Error saving canvas.', 'pn-customers-manager'),
				'confirmDelete'   => __('Are you sure you want to delete this element?', 'pn-customers-manager'),
				'confirmRemoveImage' => __('Are you sure you want to remove this image?', 'pn-customers-manager'),
				'clickSource'     => __('Click on the source mockup', 'pn-customers-manager'),
				'clickTarget'     => __('Now click on the target mockup', 'pn-customers-manager'),
				'cancelConnect'   => __('Cancel', 'pn-customers-manager'),
				'text'            => __('Text', 'pn-customers-manager'),
				'newMockup'       => __('New Mockup', 'pn-customers-manager'),
				'newLabel'        => __('New Label', 'pn-customers-manager'),
				'exportJson'      => __('Export JSON', 'pn-customers-manager'),
				'importJson'      => __('Import JSON', 'pn-customers-manager'),
				'importConfirm'   => __('This will replace all current mockups, connections and labels. Are you sure?', 'pn-customers-manager'),
				'importError'     => __('Invalid JSON file.', 'pn-customers-manager'),
				'importSuccess'   => __('Mockup flow imported successfully. Remember to save.', 'pn-customers-manager'),
				'importingImages' => __('Importing images…', 'pn-customers-manager'),
			],
		]);

		?>
		<div class="pn-cm-mf-admin-wrap">
			<div class="pn-cm-mf-toolbar">
				<button type="button" class="button pn-cm-mf-btn-add-mockup" title="<?php esc_attr_e('Add Mockup', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-format-image"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Add Mockup', 'pn-customers-manager'); ?></span>
				</button>
				<button type="button" class="button pn-cm-mf-btn-add-label" title="<?php esc_attr_e('Add Label', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-text"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Add Label', 'pn-customers-manager'); ?></span>
				</button>
				<button type="button" class="button pn-cm-mf-btn-connect" title="<?php esc_attr_e('Connect', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-arrow-right-alt"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Connect', 'pn-customers-manager'); ?></span>
				</button>
				<span class="pn-cm-mf-connect-status" style="display:none;"></span>
				<button type="button" class="button pn-cm-mf-btn-cancel-connect" style="display:none;">
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Cancel', 'pn-customers-manager'); ?></span>
				</button>
				<div class="pn-cm-mf-toolbar-spacer"></div>
				<div class="pn-cm-mf-project-name-wrap">
					<i class="material-icons-outlined pn-cm-mf-project-name-icon">folder_open</i>
					<input type="text" class="pn-cm-mf-project-name-input" placeholder="<?php esc_attr_e('Project name', 'pn-customers-manager'); ?>" />
				</div>
				<span class="pn-cm-mf-zoom-display">100%</span>
				<button type="button" class="button pn-cm-mf-btn-export" title="<?php esc_attr_e('Export JSON', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-download"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Export', 'pn-customers-manager'); ?></span>
				</button>
				<button type="button" class="button pn-cm-mf-btn-import" title="<?php esc_attr_e('Import JSON', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-upload"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Import', 'pn-customers-manager'); ?></span>
				</button>
				<input type="file" class="pn-cm-mf-import-file" accept=".json" style="display:none;" />
				<button type="button" class="button pn-cm-mf-btn-fullscreen" title="<?php esc_attr_e('Fullscreen', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-fullscreen-alt"></span>
				</button>
				<button type="button" class="button button-primary pn-cm-mf-btn-save" title="<?php esc_attr_e('Save', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-saved"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Save', 'pn-customers-manager'); ?></span>
				</button>
			</div>
			<div class="pn-cm-mf-body">
				<div class="pn-cm-mf-canvas-wrap">
					<div class="pn-cm-mf-zoom-controls">
						<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-out" title="<?php esc_attr_e('Zoom out', 'pn-customers-manager'); ?>">
							<span class="dashicons dashicons-minus"></span>
						</button>
						<span class="pn-cm-mf-zoom-controls-level">100%</span>
						<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-in" title="<?php esc_attr_e('Zoom in', 'pn-customers-manager'); ?>">
							<span class="dashicons dashicons-plus-alt2"></span>
						</button>
						<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-reset" title="<?php esc_attr_e('Reset zoom', 'pn-customers-manager'); ?>">
							<span class="dashicons dashicons-image-rotate"></span>
						</button>
					</div>
					<div class="pn-cm-mf-zoom-layer">
						<svg class="pn-cm-mf-canvas-svg">
							<defs>
								<marker id="pn-cm-mf-arrowhead" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto">
									<polygon points="0 0, 10 3.5, 0 7" fill="#666" />
								</marker>
							</defs>
						</svg>
						<div class="pn-cm-mf-canvas"></div>
					</div>
				</div>
				<div class="pn-cm-mf-sidebar">
					<h3><?php esc_html_e('Properties', 'pn-customers-manager'); ?></h3>
					<div class="pn-cm-mf-sidebar-content">
						<p class="pn-cm-mf-no-selection"><?php esc_html_e('Select a mockup, label or connection to edit its properties.', 'pn-customers-manager'); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save post handler (noop – save is done via AJAX).
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_post($post_id)
	{
		// Canvas data is saved via AJAX, nothing to do here.
	}

	/**
	 * Register the Gutenberg block.
	 */
	public static function register_block()
	{
		if (!function_exists('register_block_type')) {
			return;
		}

		if (get_option('pn_customers_manager_mockup_flow_enabled') !== 'on') {
			return;
		}

		wp_register_script(
			'pn-customers-manager-mockup-flow-block',
			PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-mockup-flow.js',
			['wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'],
			PN_CUSTOMERS_MANAGER_VERSION,
			true
		);

		// Provide list of mockup flows for the block editor
		$flows = get_posts([
			'post_type'      => 'pn_cm_mockup_flow',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		]);

		$flow_options = [];
		foreach ($flows as $f) {
			$flow_options[] = ['id' => $f->ID, 'title' => $f->post_title];
		}

		wp_localize_script('pn-customers-manager-mockup-flow-block', 'pnCmMockupFlowBlock', [
			'flows' => $flow_options,
		]);

		register_block_type('pn-customers-manager/mockup-flow', [
			'editor_script'   => 'pn-customers-manager-mockup-flow-block',
			'render_callback' => [__CLASS__, 'render_block'],
			'attributes'      => [
				'mockupFlowId' => ['type' => 'number', 'default' => 0],
			],
		]);
	}

	/**
	 * Block render callback.
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML.
	 */
	public static function render_block($attributes)
	{
		$id = !empty($attributes['mockupFlowId']) ? absint($attributes['mockupFlowId']) : 0;
		if (!$id) {
			return '<!-- pn-cm-mf: block rendered without mockupFlowId -->';
		}
		return self::render_public_view($id);
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML.
	 */
	public static function shortcode_handler($atts)
	{
		$atts = shortcode_atts(['id' => 0], $atts, 'pn-customers-manager-mockup-flow');
		$id = absint($atts['id']);

		// Accept ID via GET parameter when shortcode has no id attribute
		if (!$id && !empty($_GET['mockup_flow_id'])) {
			$id = absint($_GET['mockup_flow_id']);
		}

		if (!$id) {
			return '<!-- pn-cm-mf: shortcode called without id attribute. Use [pn-customers-manager-mockup-flow id="123"] or pass ?mockup_flow_id=123 -->';
		}
		return self::render_public_view($id);
	}

	/**
	 * Render the public view for a Mockup Flow.
	 *
	 * @param int $id Post ID.
	 * @return string HTML.
	 */
	public static function render_public_view($id)
	{
		$post = get_post($id);
		if (!$post || $post->post_type !== 'pn_cm_mockup_flow') {
			return '<!-- pn-cm-mf: post #' . intval($id) . ' not found or wrong type (got: ' . ($post ? esc_html($post->post_type) : 'null') . ') -->';
		}

		$enabled = get_option('pn_customers_manager_mockup_flow_enabled');
		if ($enabled !== 'on') {
			return '<!-- pn-cm-mf: feature disabled (option value: ' . esc_html(var_export($enabled, true)) . '). Go to Settings > Marketing > Mockup Flow and enable it. -->';
		}

		$is_admin_editor = current_user_can('manage_options');

		// Popup assets
		wp_enqueue_style('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', [], PN_CUSTOMERS_MANAGER_VERSION);
		wp_enqueue_script('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);

		// Mockup Flow assets
		wp_enqueue_style('pn-customers-manager-mockup-flow', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-mockup-flow.css', [], PN_CUSTOMERS_MANAGER_VERSION);
		wp_enqueue_script('pn-customers-manager-mockup-flow-public', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-mockup-flow.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);

		// Dashicons for zoom control buttons
		wp_enqueue_style('dashicons');

		// Admin editors need wp.media for the image picker
		if ($is_admin_editor) {
			wp_enqueue_media();
		}

		$canvas_data = get_post_meta($id, 'pn_cm_mockup_flow_canvas_data', true);
		if (empty($canvas_data)) {
			$canvas_data = wp_json_encode(['mockups' => [], 'connections' => [], 'labels' => []]);
		}
		// Re-encode with unescaped unicode so accented characters render correctly
		$canvas_data = self::normalize_canvas_json($canvas_data);

		$image_urls = self::get_image_urls_map($canvas_data);

		$unique_id = 'pn-cm-mf-public-' . $id;

		// Build i18n: public strings always included
		$i18n = [
			'all'             => __('All', 'pn-customers-manager'),
			'mobile'          => __('Mobile', 'pn-customers-manager'),
			'desktop'         => __('Desktop', 'pn-customers-manager'),
			'close'           => __('Close', 'pn-customers-manager'),
			'previous'        => __('Previous', 'pn-customers-manager'),
			'next'            => __('Next', 'pn-customers-manager'),
			'comments'        => __('Comments', 'pn-customers-manager'),
			'addComment'      => __('Add comment', 'pn-customers-manager'),
			'send'            => __('Send', 'pn-customers-manager'),
			'resolve'         => __('Resolve', 'pn-customers-manager'),
			'reopen'          => __('Reopen', 'pn-customers-manager'),
			'resolved'        => __('Resolved', 'pn-customers-manager'),
			'deleteComment'   => __('Delete', 'pn-customers-manager'),
			'noComments'      => __('No comments yet.', 'pn-customers-manager'),
			'loginToComment'  => __('Log in to add comments.', 'pn-customers-manager'),
			'fullscreen'      => __('Fullscreen', 'pn-customers-manager'),
			'exitFullscreen'  => __('Exit fullscreen', 'pn-customers-manager'),
			'clickToZoom'     => __('Click to enlarge', 'pn-customers-manager'),
		];

		// Add admin editor strings when admin is viewing
		if ($is_admin_editor) {
			$i18n = array_merge($i18n, [
				'addMockup'       => __('Add Mockup', 'pn-customers-manager'),
				'addLabel'        => __('Add Label', 'pn-customers-manager'),
				'connect'         => __('Connect', 'pn-customers-manager'),
				'save'            => __('Save', 'pn-customers-manager'),
				'delete'          => __('Delete', 'pn-customers-manager'),
				'title'           => __('Title', 'pn-customers-manager'),
				'description'     => __('Description', 'pn-customers-manager'),
				'order'           => __('Order', 'pn-customers-manager'),
				'desktopImage'    => __('Desktop Image', 'pn-customers-manager'),
				'mobileImage'     => __('Mobile Image', 'pn-customers-manager'),
				'selectImage'     => __('Select Image', 'pn-customers-manager'),
				'removeImage'     => __('Remove', 'pn-customers-manager'),
				'label'           => __('Label', 'pn-customers-manager'),
				'fontSize'        => __('Font Size', 'pn-customers-manager'),
				'color'           => __('Color', 'pn-customers-manager'),
				'connectionLabel' => __('Connection Label', 'pn-customers-manager'),
				'editMockup'      => __('Edit Mockup', 'pn-customers-manager'),
				'properties'      => __('Properties', 'pn-customers-manager'),
				'noSelection'     => __('Select a mockup, label or connection to edit its properties.', 'pn-customers-manager'),
				'saved'           => __('Canvas saved successfully.', 'pn-customers-manager'),
				'saveError'       => __('Error saving canvas.', 'pn-customers-manager'),
				'confirmDelete'   => __('Are you sure you want to delete this element?', 'pn-customers-manager'),
				'confirmRemoveImage' => __('Are you sure you want to remove this image?', 'pn-customers-manager'),
				'clickSource'     => __('Click on the source mockup', 'pn-customers-manager'),
				'clickTarget'     => __('Now click on the target mockup', 'pn-customers-manager'),
				'cancelConnect'   => __('Cancel', 'pn-customers-manager'),
				'text'            => __('Text', 'pn-customers-manager'),
				'newMockup'       => __('New Mockup', 'pn-customers-manager'),
				'newLabel'        => __('New Label', 'pn-customers-manager'),
				'exportJson'      => __('Export JSON', 'pn-customers-manager'),
				'importJson'      => __('Import JSON', 'pn-customers-manager'),
				'importConfirm'   => __('This will replace all current mockups, connections and labels. Are you sure?', 'pn-customers-manager'),
				'importError'     => __('Invalid JSON file.', 'pn-customers-manager'),
				'importSuccess'   => __('Mockup flow imported successfully. Remember to save.', 'pn-customers-manager'),
				'importingImages' => __('Importing images…', 'pn-customers-manager'),
			]);
		}

		wp_localize_script('pn-customers-manager-mockup-flow-public', 'pnCmMockupFlow', [
			'ajaxUrl'       => admin_url('admin-ajax.php'),
			'nonce'         => wp_create_nonce('pn-customers-manager-nonce'),
			'mockupFlowId'  => $id,
			'canvasData'    => $canvas_data,
			'imageUrls'     => $image_urls,
			'isAdmin'       => $is_admin_editor,
			'canComment'    => is_user_logged_in(),
			'isAdminUser'   => $is_admin_editor,
			'i18n'          => $i18n,
		]);

		ob_start();
		?>
		<!-- pn-cm-mf: rendering public view for post #<?php echo intval($id); ?> "<?php echo esc_html($post->post_title); ?>" | mockups: <?php $d = json_decode($canvas_data, true); echo is_array($d) && !empty($d['mockups']) ? count($d['mockups']) : 0; ?> | images: <?php echo count($image_urls); ?> | admin: <?php echo $is_admin_editor ? 'yes' : 'no'; ?> -->
		<?php if ($is_admin_editor) : ?>
		<div id="<?php echo esc_attr($unique_id); ?>" class="pn-cm-mf-admin-wrap pn-cm-mf-public-wrap" data-mockup-flow-id="<?php echo esc_attr($id); ?>">
			<div class="pn-cm-mf-toolbar">
				<button type="button" class="button pn-cm-mf-btn-add-mockup" title="<?php esc_attr_e('Add Mockup', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-format-image"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Add Mockup', 'pn-customers-manager'); ?></span>
				</button>
				<button type="button" class="button pn-cm-mf-btn-add-label" title="<?php esc_attr_e('Add Label', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-text"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Add Label', 'pn-customers-manager'); ?></span>
				</button>
				<button type="button" class="button pn-cm-mf-btn-connect" title="<?php esc_attr_e('Connect', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-arrow-right-alt"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Connect', 'pn-customers-manager'); ?></span>
				</button>
				<span class="pn-cm-mf-connect-status" style="display:none;"></span>
				<button type="button" class="button pn-cm-mf-btn-cancel-connect" style="display:none;">
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Cancel', 'pn-customers-manager'); ?></span>
				</button>
				<div class="pn-cm-mf-toolbar-spacer"></div>
				<div class="pn-cm-mf-project-name-wrap">
					<i class="material-icons-outlined pn-cm-mf-project-name-icon">folder_open</i>
					<input type="text" class="pn-cm-mf-project-name-input" placeholder="<?php esc_attr_e('Project name', 'pn-customers-manager'); ?>" />
				</div>
				<span class="pn-cm-mf-zoom-display">100%</span>
				<button type="button" class="button pn-cm-mf-btn-export" title="<?php esc_attr_e('Export JSON', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-download"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Export', 'pn-customers-manager'); ?></span>
				</button>
				<button type="button" class="button pn-cm-mf-btn-import" title="<?php esc_attr_e('Import JSON', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-upload"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Import', 'pn-customers-manager'); ?></span>
				</button>
				<input type="file" class="pn-cm-mf-import-file" accept=".json" style="display:none;" />
				<button type="button" class="button pn-cm-mf-btn-fullscreen" title="<?php esc_attr_e('Fullscreen', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-fullscreen-alt"></span>
				</button>
				<button type="button" class="button button-primary pn-cm-mf-btn-save" title="<?php esc_attr_e('Save', 'pn-customers-manager'); ?>">
					<span class="dashicons dashicons-saved"></span>
					<span class="pn-cm-mf-btn-text"><?php esc_html_e('Save', 'pn-customers-manager'); ?></span>
				</button>
			</div>
			<div class="pn-cm-mf-body">
				<div class="pn-cm-mf-canvas-wrap">
					<div class="pn-cm-mf-zoom-controls">
						<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-out" title="<?php esc_attr_e('Zoom out', 'pn-customers-manager'); ?>">
							<span class="dashicons dashicons-minus"></span>
						</button>
						<span class="pn-cm-mf-zoom-controls-level">100%</span>
						<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-in" title="<?php esc_attr_e('Zoom in', 'pn-customers-manager'); ?>">
							<span class="dashicons dashicons-plus-alt2"></span>
						</button>
						<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-reset" title="<?php esc_attr_e('Reset zoom', 'pn-customers-manager'); ?>">
							<span class="dashicons dashicons-image-rotate"></span>
						</button>
					</div>
					<div class="pn-cm-mf-zoom-layer">
						<svg class="pn-cm-mf-canvas-svg">
							<defs>
								<marker id="pn-cm-mf-arrowhead" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto">
									<polygon points="0 0, 10 3.5, 0 7" fill="#666" />
								</marker>
							</defs>
						</svg>
						<div class="pn-cm-mf-canvas"></div>
					</div>
				</div>
				<div class="pn-cm-mf-sidebar">
					<h3><?php esc_html_e('Properties', 'pn-customers-manager'); ?></h3>
					<div class="pn-cm-mf-sidebar-content">
						<p class="pn-cm-mf-no-selection"><?php esc_html_e('Select a mockup, label or connection to edit its properties.', 'pn-customers-manager'); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php else : ?>
		<div id="<?php echo esc_attr($unique_id); ?>" class="pn-cm-mf-public-wrap" data-mockup-flow-id="<?php echo esc_attr($id); ?>">
			<div class="pn-cm-mf-filter-bar">
				<div class="pn-cm-mf-filter-bar-left">
					<button type="button" class="pn-cm-mf-filter-btn active pn-customers-manager-tooltip" data-filter="all" data-pn-customers-manager-tooltip="<?php esc_attr_e('All', 'pn-customers-manager'); ?>"><i class="material-icons-outlined">apps</i></button>
					<button type="button" class="pn-cm-mf-filter-btn pn-customers-manager-tooltip" data-filter="desktop" data-pn-customers-manager-tooltip="<?php esc_attr_e('Desktop', 'pn-customers-manager'); ?>"><i class="material-icons-outlined">desktop_windows</i></button>
					<button type="button" class="pn-cm-mf-filter-btn pn-customers-manager-tooltip" data-filter="mobile" data-pn-customers-manager-tooltip="<?php esc_attr_e('Mobile', 'pn-customers-manager'); ?>"><i class="material-icons-outlined">smartphone</i></button>
				</div>
				<div class="pn-cm-mf-filter-bar-right">
					<button type="button" class="pn-cm-mf-filter-btn pn-cm-mf-download-all-btn pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e('Download all mockups', 'pn-customers-manager'); ?>"><i class="material-icons-outlined">cloud_download</i></button>
				</div>
			</div>
			<div class="pn-cm-mf-public-canvas-wrap">
				<div class="pn-cm-mf-zoom-controls">
					<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-out" title="<?php esc_attr_e('Zoom out', 'pn-customers-manager'); ?>">
						<span class="dashicons dashicons-minus"></span>
					</button>
					<span class="pn-cm-mf-zoom-controls-level">100%</span>
					<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-in" title="<?php esc_attr_e('Zoom in', 'pn-customers-manager'); ?>">
						<span class="dashicons dashicons-plus-alt2"></span>
					</button>
					<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-zoom-reset" title="<?php esc_attr_e('Reset zoom', 'pn-customers-manager'); ?>">
						<span class="dashicons dashicons-image-rotate"></span>
					</button>
					<button type="button" class="pn-cm-mf-zoom-btn pn-cm-mf-btn-fullscreen" title="<?php esc_attr_e('Fullscreen', 'pn-customers-manager'); ?>">
						<span class="dashicons dashicons-fullscreen-alt"></span>
					</button>
				</div>
				<div class="pn-cm-mf-zoom-layer">
					<svg class="pn-cm-mf-canvas-svg">
						<defs>
							<marker id="pn-cm-mf-arrowhead-public" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto">
								<polygon points="0 0, 10 3.5, 0 7" fill="#666" />
							</marker>
						</defs>
					</svg>
					<div class="pn-cm-mf-canvas pn-cm-mf-canvas-public"></div>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Parse canvas JSON and build a map of attachment IDs to image URLs.
	 *
	 * @param string $canvas_json JSON string.
	 * @return array Associative array keyed by attachment ID.
	 */
	public static function get_image_urls_map($canvas_json)
	{
		$data = json_decode($canvas_json, true);
		if (!is_array($data)) {
			return [];
		}

		$ids = [];
		if (!empty($data['mockups'])) {
			foreach ($data['mockups'] as $m) {
				if (!empty($m['desktop_image_id'])) {
					$ids[] = absint($m['desktop_image_id']);
				}
				if (!empty($m['mobile_image_id'])) {
					$ids[] = absint($m['mobile_image_id']);
				}
			}
		}

		$ids = array_unique($ids);
		$map = [];

		// Load thumbnail for canvas cards; include full URL string for export
		// portability (the browser only downloads an image when rendered in <img>).
		// Medium/full are loaded on demand via AJAX when the popup opens.
		foreach ($ids as $att_id) {
			$thumbnail = wp_get_attachment_image_url($att_id, 'thumbnail');
			$full      = wp_get_attachment_image_url($att_id, 'full');

			if ($thumbnail || $full) {
				$map[$att_id] = [
					'thumbnail' => $thumbnail ?: '',
					'full'      => $full ?: '',
				];
			}
		}

		return $map;
	}

	/**
	 * AJAX handler: return full and medium image URLs for given attachment IDs.
	 */
	public static function ajax_get_image_urls()
	{
		$raw_ids = !empty($_POST['attachment_ids']) ? wp_unslash($_POST['attachment_ids']) : [];

		if (!is_array($raw_ids)) {
			$raw_ids = [$raw_ids];
		}

		$ids = array_map('absint', $raw_ids);
		$ids = array_filter($ids);

		if (empty($ids)) {
			echo wp_json_encode(['error_key' => '', 'urls' => new \stdClass()]);
			exit;
		}

		$map = [];
		foreach ($ids as $att_id) {
			$thumbnail = wp_get_attachment_image_url($att_id, 'thumbnail');
			$medium    = wp_get_attachment_image_url($att_id, 'medium');
			$full      = wp_get_attachment_image_url($att_id, 'full');

			// Get the true original (non-scaled) image URL.
			// wp_get_original_image_path() returns the absolute path to the
			// original upload before WordPress created the -scaled copy.
			$original_url = '';
			if (function_exists('wp_get_original_image_path')) {
				$original_path = wp_get_original_image_path($att_id);
				if ($original_path && file_exists($original_path)) {
					$upload_dir   = wp_get_upload_dir();
					$original_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $original_path);
				}
			}

			if ($thumbnail || $medium || $full) {
				$map[$att_id] = [
					'thumbnail' => $thumbnail ?: '',
					'medium'    => $medium ?: '',
					'full'      => $full ?: '',
					'original'  => ($original_url && $original_url !== $full) ? $original_url : '',
				];
			}
		}

		echo wp_json_encode(['error_key' => '', 'urls' => !empty($map) ? $map : new \stdClass()]);
		exit;
	}

	/**
	 * AJAX handler: generate and stream a ZIP with all mockup images at maximum resolution.
	 * Files are named {order}-{title}.{ext} using the original (non-scaled) image when available.
	 */
	public static function ajax_download_all_images()
	{
		$post_id = !empty($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		if (!$post_id) {
			wp_die(__('Invalid request.', 'pn-customers-manager'));
		}

		$post = get_post($post_id);
		if (!$post || $post->post_type !== 'pn_cm_mockup_flow') {
			wp_die(__('Mockup Flow not found.', 'pn-customers-manager'));
		}

		$canvas_json = get_post_meta($post_id, 'pn_cm_mockup_flow_canvas_data', true);
		$data = json_decode($canvas_json, true);
		if (!is_array($data) || empty($data['mockups'])) {
			wp_die(__('No mockups found.', 'pn-customers-manager'));
		}

		// Sort by order
		$mockups = $data['mockups'];
		usort($mockups, function ($a, $b) {
			return ($a['order'] ?? 0) - ($b['order'] ?? 0);
		});

		// Collect files to include
		$files = [];
		$order = 1;
		foreach ($mockups as $m) {
			$title = !empty($m['title']) ? sanitize_file_name($m['title']) : 'mockup';
			$prefix = str_pad($order, 2, '0', STR_PAD_LEFT) . '-' . $title;

			// Desktop image
			if (!empty($m['desktop_image_id'])) {
				$path = self::get_best_image_path(absint($m['desktop_image_id']));
				if ($path) {
					$ext = pathinfo($path, PATHINFO_EXTENSION);
					$files[] = ['path' => $path, 'name' => $prefix . '-desktop.' . $ext];
				}
			}

			// Mobile image
			if (!empty($m['mobile_image_id'])) {
				$path = self::get_best_image_path(absint($m['mobile_image_id']));
				if ($path) {
					$ext = pathinfo($path, PATHINFO_EXTENSION);
					$files[] = ['path' => $path, 'name' => $prefix . '-mobile.' . $ext];
				}
			}

			$order++;
		}

		if (empty($files)) {
			wp_die(__('No images to download.', 'pn-customers-manager'));
		}

		// ZIP filename: use projectName from canvas data, fallback to post title
		$project_name = !empty($data['projectName']) ? $data['projectName'] : ($post->post_title ?: 'mockup-flow');
		$zip_filename = sanitize_file_name($project_name) . '.zip';
		$tmp_file = wp_tempnam($zip_filename);

		$zip = new \ZipArchive();
		if ($zip->open($tmp_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			wp_die(__('Could not create ZIP file.', 'pn-customers-manager'));
		}

		foreach ($files as $f) {
			$zip->addFile($f['path'], $f['name']);
		}
		$zip->close();

		// Clean ALL buffered output from WordPress to prevent ZIP corruption
		while (ob_get_level()) {
			ob_end_clean();
		}

		$file_size = filesize($tmp_file);

		// Stream the ZIP
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
		header('Content-Length: ' . $file_size);
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');

		if ($file_size > 0) {
			readfile($tmp_file);
		}
		unlink($tmp_file);
		exit;
	}

	/**
	 * Get the best (highest resolution) local file path for an attachment.
	 * Prefers the original (non-scaled) image, falls back to full.
	 *
	 * @param int $att_id Attachment ID.
	 * @return string|false Absolute file path or false.
	 */
	private static function get_best_image_path($att_id)
	{
		// Try original (non-scaled)
		if (function_exists('wp_get_original_image_path')) {
			$original = wp_get_original_image_path($att_id);
			if ($original && file_exists($original)) {
				return $original;
			}
		}
		// Fallback to attached file (may be -scaled)
		$path = get_attached_file($att_id);
		if ($path && file_exists($path)) {
			return $path;
		}
		return false;
	}

	/**
	 * AJAX handler: import images from external URLs.
	 *
	 * Receives an object mapping old attachment IDs to their full URLs,
	 * downloads each image into the local Media Library via media_sideload_image,
	 * and returns a mapping of old IDs to new local attachment IDs.
	 */
	public static function ajax_import_images()
	{
		if (!current_user_can('upload_files')) {
			echo wp_json_encode(['error_key' => 'pn_cm_mf_import_error', 'error_content' => esc_html__('You do not have permission to upload files.', 'pn-customers-manager')]);
			exit;
		}

		$raw = !empty($_POST['image_map']) ? wp_unslash($_POST['image_map']) : '';
		$image_map = json_decode($raw, true);

		if (!is_array($image_map) || empty($image_map)) {
			echo wp_json_encode(['error_key' => '', 'id_map' => new \stdClass()]);
			exit;
		}

		// Ensure media_sideload_image is available
		if (!function_exists('media_sideload_image')) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$mockup_flow_id = !empty($_POST['mockup_flow_id']) ? absint($_POST['mockup_flow_id']) : 0;

		$id_map = [];
		$errors = [];

		foreach ($image_map as $old_id => $url) {
			$old_id = absint($old_id);
			$url    = esc_url_raw($url);

			if (!$old_id || empty($url)) {
				continue;
			}

			// Check if this attachment ID already exists locally with a valid file
			$local_url = wp_get_attachment_url($old_id);
			if ($local_url) {
				$id_map[$old_id] = $old_id;
				continue;
			}

			// Download the image into the media library
			$new_id = media_sideload_image($url, $mockup_flow_id, '', 'id');

			if (is_wp_error($new_id)) {
				$errors[] = sprintf('%d: %s', $old_id, $new_id->get_error_message());
				continue;
			}

			$id_map[$old_id] = $new_id;
		}

		$response = ['error_key' => '', 'id_map' => !empty($id_map) ? $id_map : new \stdClass()];

		if (!empty($errors)) {
			$response['warnings'] = $errors;
		}

		echo wp_json_encode($response);
		exit;
	}

	/**
	 * Re-encode canvas JSON with unescaped unicode so that accented
	 * characters stored as \uXXXX sequences render correctly when
	 * the string is passed through wp_localize_script.
	 *
	 * @param string $json JSON string.
	 * @return string JSON string with unescaped unicode.
	 */
	private static function normalize_canvas_json($json)
	{
		$decoded = json_decode($json, true);
		if (!is_array($decoded)) {
			return $json;
		}
		return wp_json_encode($decoded, JSON_UNESCAPED_UNICODE);
	}

	// ── Front-end list / CRUD ──────────────────────────────────

	/**
	 * List wrapper shortcode callback.
	 *
	 * @return string HTML.
	 */
	/**
	 * Get the public URL for viewing a specific mockup flow.
	 *
	 * @param int $flow_id Post ID.
	 * @return string URL or empty string.
	 */
	public static function get_public_url($flow_id)
	{
		$page_id = get_option('pn_customers_manager_page_mockup_flow', 0);
		if (empty($page_id)) {
			return '';
		}
		$page_url = get_permalink($page_id);
		if (!$page_url) {
			return '';
		}
		return add_query_arg('mockup_flow_id', $flow_id, $page_url);
	}

	public static function list_wrapper()
	{
		if (!is_user_logged_in()) {
			return do_shortcode('[pn-customers-manager-call-to-action'
				. ' pn_customers_manager_call_to_action_icon="admin_panel_settings"'
				. ' pn_customers_manager_call_to_action_title="' . esc_attr__('Access restricted', 'pn-customers-manager') . '"'
				. ' pn_customers_manager_call_to_action_content="' . esc_attr__('You do not have permission to access this section.', 'pn-customers-manager') . '"'
				. ' pn_customers_manager_call_to_action_button_link="#"'
				. ' pn_customers_manager_call_to_action_button_text="' . esc_attr__('Log in', 'pn-customers-manager') . '"'
				. ' pn_customers_manager_call_to_action_button_class="userspn-profile-popup-btn"'
				. ' pn_customers_manager_call_to_action_button_data_key="data-userspn-action"'
				. ' pn_customers_manager_call_to_action_button_data_value="login"'
				. ']');
		}

		if (!current_user_can('manage_options')) {
			return do_shortcode('[pn-customers-manager-call-to-action'
				. ' pn_customers_manager_call_to_action_icon="admin_panel_settings"'
				. ' pn_customers_manager_call_to_action_title="' . esc_attr__('Access restricted', 'pn-customers-manager') . '"'
				. ' pn_customers_manager_call_to_action_content="' . esc_attr__('You do not have permission to access this section.', 'pn-customers-manager') . '"'
				. ']');
		}

		if (!is_admin()) {
			wp_enqueue_style('pn-customers-manager-mockup-flow', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-mockup-flow.css', [], PN_CUSTOMERS_MANAGER_VERSION);
		}

		ob_start();
		?>
		<div class="pn-customers-manager-cpt-list pn-customers-manager-pn_cm_mockup_flow-list pn-customers-manager-mb-50 pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mb-100">
			<div class="pn-customers-manager-cpt-search-container pn-customers-manager-mb-20 pn-customers-manager-text-align-right">
				<div class="pn-customers-manager-cpt-search-wrapper">
					<input type="text" class="pn-customers-manager-cpt-search-input pn-customers-manager-input pn-customers-manager-display-none" placeholder="<?php esc_attr_e('Filter...', 'pn-customers-manager'); ?>" />
					<i class="material-icons-outlined pn-customers-manager-cpt-search-toggle pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Search Mockup Flows', 'pn-customers-manager'); ?>">search</i>

					<a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_mockup_flow-add" data-pn-customers-manager-ajax-type="pn_cm_mockup_flow_new">
						<i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Add new Mockup Flow', 'pn-customers-manager'); ?>">add</i>
					</a>
				</div>
			</div>

			<div class="pn-customers-manager-cpt-list-wrapper pn-customers-manager-pn_cm_mockup_flow-list-wrapper">
				<?php echo wp_kses(self::mockup_flow_list(), PN_CUSTOMERS_MANAGER_KSES); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the mockup flow list table.
	 *
	 * @return string HTML.
	 */
	public static function mockup_flow_list()
	{
		$flows = get_posts([
			'fields'         => 'ids',
			'numberposts'    => -1,
			'post_type'      => 'pn_cm_mockup_flow',
			'post_status'    => 'any',
			'orderby'        => 'date',
			'order'          => 'DESC',
		]);

		ob_start();
		if (!empty($flows)):
		?>
			<div class="pn-cm-mf-list-table-wrap">
		<table class="pn-cm-mf-list-table">
				<thead>
					<tr>
						<th><?php esc_html_e('Title', 'pn-customers-manager'); ?></th>
						<th><?php esc_html_e('Mockups', 'pn-customers-manager'); ?></th>
						<th><?php esc_html_e('Date', 'pn-customers-manager'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($flows as $flow_id):
						$flow_title = get_the_title($flow_id);
						$canvas_data = get_post_meta($flow_id, 'pn_cm_mockup_flow_canvas_data', true);
						$data = !empty($canvas_data) ? json_decode($canvas_data, true) : [];
						$mockup_count = is_array($data) && !empty($data['mockups']) ? count($data['mockups']) : 0;
						$flow_date = get_the_date('', $flow_id);
						$public_url = self::get_public_url($flow_id);
					?>
						<tr class="pn-customers-manager-pn_cm_mockup_flow-list-item" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>">
							<td data-label="<?php esc_attr_e('Title', 'pn-customers-manager'); ?>">
								<a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_mockup_flow-view" data-pn-customers-manager-ajax-type="pn_cm_mockup_flow_view" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>">
									<strong><?php echo esc_html($flow_title); ?></strong>
								</a>
								<?php if (!empty($public_url)): ?>
									<a href="<?php echo esc_url($public_url); ?>" target="_blank" class="pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" title="<?php esc_attr_e('Open canvas', 'pn-customers-manager'); ?>">
										<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-18 pn-customers-manager-ml-5" style="color:#999;">open_in_new</i>
									</a>
								<?php endif; ?>
							</td>
							<td data-label="<?php esc_attr_e('Mockups', 'pn-customers-manager'); ?>">
								<?php echo esc_html($mockup_count); ?>
							</td>
							<td data-label="<?php esc_attr_e('Date', 'pn-customers-manager'); ?>">
								<?php echo esc_html($flow_date); ?>
							</td>
							<td>
								<div class="pn-customers-manager-position-relative">
									<i class="material-icons-outlined pn-customers-manager-menu-more-btn pn-customers-manager-cursor-pointer pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30">more_vert</i>
									<div class="pn-customers-manager-menu-more pn-customers-manager-z-index-99 pn-customers-manager-display-none-soft">
										<ul class="pn-customers-manager-list-style-none">
											<?php if (!empty($public_url)): ?>
											<li>
												<a href="<?php echo esc_url($public_url); ?>" target="_blank" class="pn-customers-manager-text-decoration-none">
													<div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
															<p><?php esc_html_e('Open Canvas', 'pn-customers-manager'); ?></p>
														</div>
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
															<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">open_in_new</i>
														</div>
													</div>
												</a>
											</li>
											<?php endif; ?>
											<li>
												<a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_mockup_flow-view" data-pn-customers-manager-ajax-type="pn_cm_mockup_flow_view" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>">
													<div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
															<p><?php esc_html_e('View', 'pn-customers-manager'); ?></p>
														</div>
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
															<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">visibility</i>
														</div>
													</div>
												</a>
											</li>
											<li>
												<a href="<?php echo esc_url(get_edit_post_link($flow_id)); ?>" class="pn-customers-manager-text-decoration-none">
													<div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
															<p><?php esc_html_e('Edit Canvas', 'pn-customers-manager'); ?></p>
														</div>
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
															<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">edit</i>
														</div>
													</div>
												</a>
											</li>
											<li>
												<a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_mockup_flow-edit" data-pn-customers-manager-ajax-type="pn_cm_mockup_flow_edit" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>">
													<div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
															<p><?php esc_html_e('Edit Details', 'pn-customers-manager'); ?></p>
														</div>
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
															<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">settings</i>
														</div>
													</div>
												</a>
											</li>
											<li>
												<a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_mockup_flow-remove" data-pn-customers-manager-ajax-type="pn_cm_mockup_flow_remove_confirm" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>">
													<div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
															<p><?php esc_html_e('Remove', 'pn-customers-manager'); ?></p>
														</div>
														<div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
															<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">delete</i>
														</div>
													</div>
												</a>
											</li>
										</ul>
									</div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php else: ?>
			<p class="pn-customers-manager-text-align-center pn-customers-manager-mt-30"><?php esc_html_e('No mockup flows found.', 'pn-customers-manager'); ?></p>
		<?php endif;
		return ob_get_clean();
	}

	/**
	 * New mockup flow form (popup content).
	 *
	 * @return string HTML.
	 */
	public static function mockup_flow_new()
	{
		if (!is_user_logged_in()) {
			wp_die(esc_html__('You must be logged in.', 'pn-customers-manager'), esc_html__('Access Denied', 'pn-customers-manager'), ['response' => 403]);
		}

		ob_start();
		?>
		<div class="pn_cm_mockup_flow-new pn-customers-manager-p-30">
			<h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Add new Mockup Flow', 'pn-customers-manager'); ?></h4>

			<form action="" method="post" class="pn-customers-manager-form">
				<?php
				$fields = self::get_fields();
				foreach ($fields as $field) {
					echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post'), PN_CUSTOMERS_MANAGER_KSES);
				}
				?>

				<div class="pn-customers-manager-text-align-right">
					<input class="pn-customers-manager-btn" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_new" data-pn-customers-manager-post-type="pn_cm_mockup_flow" type="submit" value="<?php esc_attr_e('Create Mockup Flow', 'pn-customers-manager'); ?>"/>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * View mockup flow (popup content).
	 *
	 * @param int $flow_id Post ID.
	 * @return string HTML.
	 */
	public static function mockup_flow_view($flow_id)
	{
		$post = get_post($flow_id);
		if (!$post || $post->post_type !== 'pn_cm_mockup_flow') {
			return '<p>' . esc_html__('Mockup Flow not found.', 'pn-customers-manager') . '</p>';
		}

		ob_start();
		?>
		<div class="pn_cm_mockup_flow-view pn-customers-manager-p-30" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>">
			<h4 class="pn-customers-manager-text-align-center pn-customers-manager-mb-20">
				<?php echo esc_html(get_the_title($flow_id)); ?>
			</h4>

			<?php
			$fields = self::get_fields($flow_id);
			foreach ($fields as $field) {
				echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_display_wrapper($field, 'post', $flow_id), PN_CUSTOMERS_MANAGER_KSES);
			}
			?>

			<div class="pn-customers-manager-text-align-right pn-customers-manager-mt-20">
				<?php
				$view_public_url = self::get_public_url($flow_id);
				if (!empty($view_public_url)):
				?>
				<a href="<?php echo esc_url($view_public_url); ?>" class="pn-customers-manager-btn pn-customers-manager-btn-mini" target="_blank">
					<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-18">open_in_new</i>
					<?php esc_html_e('Open Canvas', 'pn-customers-manager'); ?>
				</a>
				<?php endif; ?>
				<a href="<?php echo esc_url(get_edit_post_link($flow_id)); ?>" class="pn-customers-manager-btn pn-customers-manager-btn-mini" target="_blank">
					<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-18">edit</i>
					<?php esc_html_e('Edit Canvas', 'pn-customers-manager'); ?>
				</a>
				<a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-popup-open-ajax" data-pn-customers-manager-popup-id="pn-customers-manager-popup-pn_cm_mockup_flow-edit" data-pn-customers-manager-ajax-type="pn_cm_mockup_flow_edit" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>">
					<?php esc_html_e('Edit Details', 'pn-customers-manager'); ?>
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Edit mockup flow form (popup content).
	 *
	 * @param int $flow_id Post ID.
	 * @return string HTML.
	 */
	public static function mockup_flow_edit($flow_id)
	{
		$post = get_post($flow_id);
		if (!$post || $post->post_type !== 'pn_cm_mockup_flow') {
			return '<p>' . esc_html__('Mockup Flow not found.', 'pn-customers-manager') . '</p>';
		}

		ob_start();
		?>
		<div class="pn_cm_mockup_flow-edit pn-customers-manager-p-30" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>">
			<h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Edit Mockup Flow', 'pn-customers-manager'); ?></h4>

			<form action="" method="post" class="pn-customers-manager-form">
				<?php
				$fields = self::get_fields($flow_id);
				foreach ($fields as $field) {
					echo wp_kses(PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($field, 'post', $flow_id), PN_CUSTOMERS_MANAGER_KSES);
				}
				?>

				<div class="pn-customers-manager-text-align-right">
					<input class="pn-customers-manager-btn" type="submit" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_edit" data-pn-customers-manager-post-type="pn_cm_mockup_flow" data-pn-customers-manager-post-id="<?php echo esc_attr($flow_id); ?>" value="<?php esc_attr_e('Save Changes', 'pn-customers-manager'); ?>"/>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get form fields for mockup flow create/edit.
	 *
	 * @param int $flow_id Optional post ID for populating values.
	 * @return array Field definitions.
	 */
	public static function get_fields($flow_id = 0)
	{
		$title_value = $flow_id ? get_the_title($flow_id) : '';
		$description_value = $flow_id ? get_post_meta($flow_id, 'pn_cm_mockup_flow_description', true) : '';

		$fields = [];

		$fields['pn_cm_mockup_flow_title'] = [
			'id'          => 'pn_cm_mockup_flow_title',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'input'       => 'input',
			'type'        => 'text',
			'required'    => true,
			'value'       => $title_value,
			'label'       => __('Title', 'pn-customers-manager'),
			'placeholder' => __('Mockup Flow title', 'pn-customers-manager'),
		];

		$fields['pn_customers_manager_ajax_nonce'] = [
			'id'    => 'pn_customers_manager_ajax_nonce',
			'input' => 'input',
			'type'  => 'nonce',
		];

		$fields['pn_cm_mockup_flow_description'] = [
			'id'          => 'pn_cm_mockup_flow_description',
			'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
			'input'       => 'textarea',
			'required'    => false,
			'value'       => $description_value,
			'label'       => __('Description', 'pn-customers-manager'),
			'placeholder' => __('Description (optional)', 'pn-customers-manager'),
		];

		return $fields;
	}

	/**
	 * Remove confirmation popup content.
	 *
	 * @param int $flow_id Post ID.
	 * @return string HTML.
	 */
	public static function mockup_flow_remove_confirm($flow_id)
	{
		$post = get_post($flow_id);
		if (!$post || $post->post_type !== 'pn_cm_mockup_flow') {
			return '<p>' . esc_html__('Mockup Flow not found.', 'pn-customers-manager') . '</p>';
		}

		ob_start();
		?>
		<div class="pn_cm_mockup_flow-remove-confirm pn-customers-manager-p-30 pn-customers-manager-text-align-center">
			<i class="material-icons-outlined pn-customers-manager-font-size-60 pn-customers-manager-mb-20" style="color:#e53935;">delete_forever</i>
			<h4 class="pn-customers-manager-mb-10"><?php esc_html_e('Remove Mockup Flow', 'pn-customers-manager'); ?></h4>
			<p class="pn-customers-manager-mb-20"><?php echo esc_html(sprintf(
				/* translators: %s: mockup flow title */
				__('Are you sure you want to remove "%s"? This action cannot be undone.', 'pn-customers-manager'),
				get_the_title($flow_id)
			)); ?></p>
			<a href="#" class="pn-customers-manager-btn pn-customers-manager-pn_cm_mockup_flow-remove" data-pn_cm_mockup_flow-id="<?php echo esc_attr($flow_id); ?>" style="background:#e53935;border-color:#e53935;">
				<?php esc_html_e('Yes, remove it', 'pn-customers-manager'); ?>
			</a>
		</div>
		<script>
		(function($){
			$(document).off('click.pnCmMfRemove').on('click.pnCmMfRemove', '.pn-customers-manager-pn_cm_mockup_flow-remove', function(e){
				e.preventDefault();
				var flowId = $(this).data('pn_cm_mockup_flow-id');
				var $listContainer = $('.pn-customers-manager-pn_cm_mockup_flow-list-wrapper');
				$listContainer.css('opacity', '0.5');
				if(typeof pn_customers_manager_Popups !== 'undefined') pn_customers_manager_Popups.close();

				$.post(pn_customers_manager_ajax.ajax_url, {
					action: 'pn_customers_manager_ajax',
					pn_customers_manager_ajax_nonce: pn_customers_manager_action.pn_customers_manager_get_nonce,
					pn_customers_manager_ajax_type: 'pn_cm_mockup_flow_remove',
					pn_cm_mockup_flow_id: flowId
				}, function(response){
					var res = typeof response === 'string' ? JSON.parse(response) : response;
					if(res.error_key === '' && res.html){
						$listContainer.html(res.html).css('opacity', '1');
					} else {
						$listContainer.css('opacity', '1');
					}
				}).fail(function(){ $listContainer.css('opacity', '1'); });
			});
		})(jQuery);
		</script>
		<?php
		return ob_get_clean();
	}

	// ── AJAX handlers ───────────────────────────────────────────

	/**
	 * Save canvas data via AJAX.
	 */
	public static function ajax_save_canvas()
	{
		$mockup_flow_id = !empty($_POST['mockup_flow_id']) ? absint($_POST['mockup_flow_id']) : 0;
		$canvas_data    = !empty($_POST['canvas_data']) ? wp_unslash($_POST['canvas_data']) : '';

		if (!$mockup_flow_id || empty($canvas_data)) {
			echo wp_json_encode(['error_key' => 'pn_cm_mf_save_error', 'error_content' => esc_html__('Invalid data.', 'pn-customers-manager')]);
			exit;
		}

		$post = get_post($mockup_flow_id);
		if (!$post || $post->post_type !== 'pn_cm_mockup_flow') {
			echo wp_json_encode(['error_key' => 'pn_cm_mf_save_error', 'error_content' => esc_html__('Mockup Flow not found.', 'pn-customers-manager')]);
			exit;
		}

		// Validate JSON
		$decoded = json_decode($canvas_data, true);
		if (!is_array($decoded)) {
			echo wp_json_encode(['error_key' => 'pn_cm_mf_save_error', 'error_content' => esc_html__('Invalid JSON data.', 'pn-customers-manager')]);
			exit;
		}

		update_post_meta($mockup_flow_id, 'pn_cm_mockup_flow_canvas_data', wp_json_encode($decoded, JSON_UNESCAPED_UNICODE));

		echo wp_json_encode(['error_key' => '']);
		exit;
	}

	/**
	 * Add a comment to a mockup.
	 */
	public static function ajax_add_comment()
	{
		global $wpdb;

		$mockup_flow_id = !empty($_POST['mockup_flow_id']) ? absint($_POST['mockup_flow_id']) : 0;
		$mockup_id      = !empty($_POST['mockup_id']) ? sanitize_text_field(wp_unslash($_POST['mockup_id'])) : '';
		$comment_text   = !empty($_POST['comment_text']) ? sanitize_textarea_field(wp_unslash($_POST['comment_text'])) : '';

		if (!$mockup_flow_id || empty($mockup_id) || empty($comment_text)) {
			echo wp_json_encode(['error_key' => 'pn_cm_mf_comment_error', 'error_content' => esc_html__('Missing required fields.', 'pn-customers-manager')]);
			exit;
		}

		$current_user = wp_get_current_user();
		$user_name = $current_user->display_name ?: $current_user->user_login;

		$table = $wpdb->prefix . 'pn_cm_mockup_comments';

		$wpdb->insert($table, [
			'mockup_flow_id' => $mockup_flow_id,
			'mockup_id'      => $mockup_id,
			'user_id'        => get_current_user_id(),
			'user_name'      => $user_name,
			'comment_text'   => $comment_text,
			'is_resolved'    => 0,
			'created_at'     => current_time('mysql'),
		], ['%d', '%s', '%d', '%s', '%s', '%d', '%s']);

		echo wp_json_encode([
			'error_key' => '',
			'comment'   => [
				'id'           => $wpdb->insert_id,
				'user_name'    => $user_name,
				'comment_text' => $comment_text,
				'is_resolved'  => 0,
				'created_at'   => current_time('mysql'),
			],
		]);
		exit;
	}

	/**
	 * Get comments for a mockup.
	 */
	public static function ajax_get_comments()
	{
		global $wpdb;

		$mockup_flow_id = !empty($_POST['mockup_flow_id']) ? absint($_POST['mockup_flow_id']) : 0;
		$mockup_id      = !empty($_POST['mockup_id']) ? sanitize_text_field(wp_unslash($_POST['mockup_id'])) : '';

		if (!$mockup_flow_id || empty($mockup_id)) {
			echo wp_json_encode(['error_key' => 'pn_cm_mf_comment_error', 'error_content' => esc_html__('Missing required fields.', 'pn-customers-manager')]);
			exit;
		}

		$table = $wpdb->prefix . 'pn_cm_mockup_comments';

		$comments = $wpdb->get_results($wpdb->prepare(
			"SELECT id, user_name, comment_text, is_resolved, created_at FROM {$table} WHERE mockup_flow_id = %d AND mockup_id = %s ORDER BY created_at ASC",
			$mockup_flow_id,
			$mockup_id
		), ARRAY_A);

		echo wp_json_encode(['error_key' => '', 'comments' => $comments ?: []]);
		exit;
	}

	/**
	 * Toggle resolved status on a comment.
	 */
	public static function ajax_resolve_comment()
	{
		global $wpdb;

		$comment_id = !empty($_POST['comment_id']) ? absint($_POST['comment_id']) : 0;

		if (!$comment_id) {
			echo wp_json_encode(['error_key' => 'pn_cm_mf_comment_error']);
			exit;
		}

		$table = $wpdb->prefix . 'pn_cm_mockup_comments';

		$current = $wpdb->get_var($wpdb->prepare("SELECT is_resolved FROM {$table} WHERE id = %d", $comment_id));
		$new_val = $current ? 0 : 1;

		$wpdb->update($table, ['is_resolved' => $new_val], ['id' => $comment_id], ['%d'], ['%d']);

		echo wp_json_encode(['error_key' => '', 'is_resolved' => $new_val]);
		exit;
	}

	/**
	 * Delete a comment.
	 */
	public static function ajax_delete_comment()
	{
		global $wpdb;

		$comment_id = !empty($_POST['comment_id']) ? absint($_POST['comment_id']) : 0;

		if (!$comment_id) {
			echo wp_json_encode(['error_key' => 'pn_cm_mf_comment_error']);
			exit;
		}

		$table = $wpdb->prefix . 'pn_cm_mockup_comments';
		$wpdb->delete($table, ['id' => $comment_id], ['%d']);

		echo wp_json_encode(['error_key' => '']);
		exit;
	}
}
