<?php
/**
 * Activity Log (Audit Trail) system.
 *
 * Records every significant change made through the application
 * and provides an admin dashboard section for querying history.
 *
 * @link       padresenlanube.com/
 * @since      1.2.125
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

class PN_CUSTOMERS_MANAGER_Activity_Log {

	/**
	 * Log an activity entry.
	 *
	 * @param string $action_type  create|update|delete|settings|status_change|import|export
	 * @param string $object_type  organization|budget|invoice|funnel|mockup_flow|setting|contact_message|form_submission
	 * @param int    $object_id    Post ID, message ID, etc.
	 * @param string $object_title Human-readable label.
	 * @param string $description  Full description of the change.
	 */
	public static function log($action_type, $object_type, $object_id, $object_title, $description) {
		global $wpdb;

		$user = wp_get_current_user();
		$user_id   = $user->ID;
		$user_name = $user->exists() ? $user->display_name : __('System', 'pn-customers-manager');

		$ip = '';
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
		}

		$wpdb->insert(
			$wpdb->prefix . 'pn_cm_activity_log',
			[
				'user_id'      => $user_id,
				'user_name'    => $user_name,
				'action_type'  => sanitize_key($action_type),
				'object_type'  => sanitize_key($object_type),
				'object_id'    => absint($object_id),
				'object_title' => sanitize_text_field($object_title),
				'description'  => sanitize_textarea_field($description),
				'ip_address'   => $ip,
				'created_at'   => current_time('mysql'),
			],
			['%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
		);
	}

	/**
	 * Retrieve log entries with filtering, search, and pagination.
	 *
	 * @param array $args {
	 *   @type string $action_type Filter by action type.
	 *   @type string $object_type Filter by object type.
	 *   @type string $search      Search in object_title, description, user_name.
	 *   @type string $order       ASC or DESC (default DESC).
	 *   @type int    $per_page    Results per page (default 30).
	 *   @type int    $page        Current page (default 1).
	 * }
	 * @return array {items, total, pages, has_more}
	 */
	public static function get_logs($args = []) {
		global $wpdb;

		$defaults = [
			'action_type' => '',
			'object_type' => '',
			'search'      => '',
			'order'       => 'DESC',
			'per_page'    => 30,
			'page'        => 1,
		];
		$args = wp_parse_args($args, $defaults);

		$table  = $wpdb->prefix . 'pn_cm_activity_log';
		$where  = [];
		$values = [];

		if (!empty($args['action_type'])) {
			$where[]  = 'action_type = %s';
			$values[] = sanitize_key($args['action_type']);
		}

		if (!empty($args['object_type'])) {
			$where[]  = 'object_type = %s';
			$values[] = sanitize_key($args['object_type']);
		}

		if (!empty($args['search'])) {
			$like     = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
			$where[]  = '(object_title LIKE %s OR description LIKE %s OR user_name LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = '';
		if (!empty($where)) {
			$where_sql = 'WHERE ' . implode(' AND ', $where);
		}

		$order    = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
		$per_page = max(1, intval($args['per_page']));
		$page     = max(1, intval($args['page']));
		$offset   = ($page - 1) * $per_page;

		// Count
		if (!empty($values)) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table and $where_sql are safely constructed; user values use placeholders.
			$count = (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} {$where_sql}",
				$values
			));
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is safely built from $wpdb->prefix.
			$count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
		}

		// Fetch rows
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table, $where_sql, and $order are safely constructed; user values use placeholders.
		$query = "SELECT * FROM {$table} {$where_sql} ORDER BY created_at {$order}, id {$order} LIMIT %d OFFSET %d";
		$query_values = array_merge($values, [$per_page, $offset]);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is built above with interpolated safe values and %d placeholders; prepared on this line.
		$items = $wpdb->get_results($wpdb->prepare($query, $query_values));

		$pages    = (int) ceil($count / $per_page);
		$has_more = $page < $pages;

		return [
			'items'    => $items ? $items : [],
			'total'    => $count,
			'pages'    => $pages,
			'has_more' => $has_more,
		];
	}

	/**
	 * Render the full activity log section HTML for the stats page.
	 *
	 * @return string
	 */
	public static function render_section() {
		$action_types = self::get_action_types();
		$object_types = self::get_object_types();

		ob_start();
		?>
		<div class="pn-cm-al-section">
			<div class="pn-cm-al-header">
				<h2>
					<i class="material-icons-outlined">history</i>
					<?php esc_html_e('Activity Log', 'pn-customers-manager'); ?>
					<span class="pn-cm-al-total" id="pn-cm-al-total"></span>
				</h2>
				<button type="button" id="pn-cm-al-sort-toggle" class="pn-cm-al-load-more" title="<?php esc_attr_e('Toggle sort order', 'pn-customers-manager'); ?>">
					<i class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">swap_vert</i>
					<?php esc_html_e('Sort', 'pn-customers-manager'); ?>
				</button>
			</div>

			<div class="pn-cm-al-filters">
				<input type="text" id="pn-cm-al-search" placeholder="<?php esc_attr_e('Search...', 'pn-customers-manager'); ?>" />
				<select id="pn-cm-al-action-type">
					<option value=""><?php esc_html_e('All actions', 'pn-customers-manager'); ?></option>
					<?php foreach ($action_types as $key => $label) : ?>
						<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
				<select id="pn-cm-al-object-type">
					<option value=""><?php esc_html_e('All types', 'pn-customers-manager'); ?></option>
					<?php foreach ($object_types as $key => $label) : ?>
						<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="pn-cm-al-entries" id="pn-cm-al-entries"></div>

			<button type="button" id="pn-cm-al-load-more" class="pn-cm-al-load-more" style="display:none;">
				<?php esc_html_e('Load more', 'pn-customers-manager'); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render HTML for an array of log entries.
	 *
	 * @param array $items Array of row objects.
	 * @return string
	 */
	public static function render_entries($items) {
		if (empty($items)) {
			return '<div class="pn-cm-al-empty">' . esc_html__('No activity found.', 'pn-customers-manager') . '</div>';
		}

		$action_types = self::get_action_types();
		$icons = [
			'create'        => 'add_circle',
			'update'        => 'edit',
			'delete'        => 'delete',
			'settings'      => 'settings',
			'status_change' => 'swap_horiz',
			'import'        => 'file_download',
			'export'        => 'file_upload',
		];

		$html = '';
		foreach ($items as $item) {
			$icon_class = 'pn-cm-al-icon-' . esc_attr($item->action_type);
			$icon_name  = isset($icons[$item->action_type]) ? $icons[$item->action_type] : 'info';
			$badge_label = isset($action_types[$item->action_type]) ? $action_types[$item->action_type] : ucfirst($item->action_type);
			$time_ago   = human_time_diff(strtotime($item->created_at), current_time('timestamp')) . ' ' . __('ago', 'pn-customers-manager');

			$html .= '<div class="pn-cm-al-entry">';
			$html .= '<div class="pn-cm-al-entry-icon ' . $icon_class . '"><i class="material-icons-outlined">' . esc_html($icon_name) . '</i></div>';
			$html .= '<div class="pn-cm-al-entry-body">';
			$html .= '<div class="pn-cm-al-entry-desc">';
			$html .= '<span class="pn-cm-al-badge ' . $icon_class . '">' . esc_html($badge_label) . '</span> ';
			if (!empty($item->object_title)) {
				$html .= '<strong>' . esc_html($item->object_title) . '</strong> &mdash; ';
			}
			$html .= esc_html($item->description);
			$html .= '</div>';
			$html .= '<div class="pn-cm-al-entry-meta">';
			$html .= esc_html($item->user_name) . ' &middot; ' . esc_html($time_ago);
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * AJAX handler: fetch activity log entries.
	 */
	public static function ajax_get_logs() {
		$search      = !empty($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
		$action_type = !empty($_POST['action_type']) ? sanitize_key($_POST['action_type']) : '';
		$object_type = !empty($_POST['object_type']) ? sanitize_key($_POST['object_type']) : '';
		$order       = !empty($_POST['order']) ? sanitize_text_field(wp_unslash($_POST['order'])) : 'DESC';
		$page        = !empty($_POST['page']) ? absint($_POST['page']) : 1;

		$result = self::get_logs([
			'action_type' => $action_type,
			'object_type' => $object_type,
			'search'      => $search,
			'order'       => $order,
			'page'        => $page,
		]);

		echo wp_json_encode([
			'error_key' => '',
			'html'      => self::render_entries($result['items']),
			'total'     => $result['total'],
			'pages'     => $result['pages'],
			'page'      => $page,
			'has_more'  => $result['has_more'],
		]);
	}

	/**
	 * Return the list of action types.
	 *
	 * @return array
	 */
	public static function get_action_types() {
		return [
			'create'        => __('Created', 'pn-customers-manager'),
			'update'        => __('Updated', 'pn-customers-manager'),
			'delete'        => __('Deleted', 'pn-customers-manager'),
			'settings'      => __('Settings', 'pn-customers-manager'),
			'status_change' => __('Status change', 'pn-customers-manager'),
			'import'        => __('Import', 'pn-customers-manager'),
			'export'        => __('Export', 'pn-customers-manager'),
		];
	}

	/**
	 * Return the list of object types.
	 *
	 * @return array
	 */
	public static function get_object_types() {
		return [
			'organization'    => __('Organization', 'pn-customers-manager'),
			'budget'          => __('Budget', 'pn-customers-manager'),
			'invoice'         => __('Invoice', 'pn-customers-manager'),
			'funnel'          => __('Funnel', 'pn-customers-manager'),
			'mockup_flow'     => __('Mockup Flow', 'pn-customers-manager'),
			'setting'         => __('Setting', 'pn-customers-manager'),
			'contact_message' => __('Contact Message', 'pn-customers-manager'),
			'form_submission' => __('Form Submission', 'pn-customers-manager'),
		];
	}

	/**
	 * Map CPT post_type slug to activity log object_type.
	 *
	 * @param string $post_type WordPress post type slug.
	 * @return string
	 */
	public static function cpt_to_object_type($post_type) {
		$map = [
			'pn_cm_organization' => 'organization',
			'pn_cm_budget'       => 'budget',
			'pn_cm_invoice'      => 'invoice',
			'pn_cm_funnel'       => 'funnel',
			'pn_cm_mockup_flow'  => 'mockup_flow',
		];
		return isset($map[$post_type]) ? $map[$post_type] : '';
	}

	/**
	 * Hook callback for save_post on plugin CPTs.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 */
	public static function on_save_post($post_id, $post, $update) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (wp_is_post_revision($post_id)) {
			return;
		}

		$object_type = self::cpt_to_object_type($post->post_type);
		if (empty($object_type)) {
			return;
		}

		$action = $update ? 'update' : 'create';
		$label  = $update ? __('Updated', 'pn-customers-manager') : __('Created', 'pn-customers-manager');
		$cpt_name = isset(PN_CUSTOMERS_MANAGER_CPTS[$post->post_type]) ? PN_CUSTOMERS_MANAGER_CPTS[$post->post_type] : $post->post_type;

		self::log(
			$action,
			$object_type,
			$post_id,
			$post->post_title,
			sprintf('%s %s #%d', $label, $cpt_name, $post_id)
		);
	}

	/**
	 * Hook callback for trashed_post.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function on_trash_post($post_id) {
		$post = get_post($post_id);
		if (!$post) {
			return;
		}

		$object_type = self::cpt_to_object_type($post->post_type);
		if (empty($object_type)) {
			return;
		}

		$cpt_name = isset(PN_CUSTOMERS_MANAGER_CPTS[$post->post_type]) ? PN_CUSTOMERS_MANAGER_CPTS[$post->post_type] : $post->post_type;

		self::log(
			'delete',
			$object_type,
			$post_id,
			$post->post_title,
			sprintf('%s %s #%d', __('Trashed', 'pn-customers-manager'), $cpt_name, $post_id)
		);
	}
}
