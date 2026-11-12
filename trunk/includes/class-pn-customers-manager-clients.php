<?php
/**
 * CRM Clients Dashboard.
 *
 * Provides a dashboard page for managing clients with grid/list views,
 * stage filtering, and integration with GHL/Zoho sources.
 *
 * @since      1.2.48
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */
class PN_CUSTOMERS_MANAGER_Clients {

  /**
   * Default client stages.
   */
  const DEFAULT_STAGES = '["New","Contacted","Qualified","Proposal","Negotiation","Won","Lost"]';

  /**
   * Default stage colors for backwards compatibility.
   */
  const DEFAULT_STAGE_COLORS = [ '#6c757d', '#0d6efd', '#6f42c1', '#fd7e14', '#e83e8c', '#198754', '#dc3545' ];

  /**
   * Get full stages configuration (name, color, description).
   *
   * @return array
   */
  public static function get_stages_config() {
    // Primary source: funnel states config (shared with GHL/Zoho sync).
    if ( class_exists( 'PN_CUSTOMERS_MANAGER_Settings' ) ) {
      $funnel = PN_CUSTOMERS_MANAGER_Settings::pn_customers_manager_get_funnel_states_config();
      if ( ! empty( $funnel ) && is_array( $funnel ) ) {
        $result = [];
        foreach ( $funnel as $state ) {
          if ( empty( $state['enabled'] ) ) {
            continue;
          }
          $result[] = [
            'id'          => isset( $state['id'] ) ? $state['id'] : sanitize_title( $state['name'] ),
            'name'        => $state['name'],
            'color'       => ! empty( $state['color'] ) ? $state['color'] : '#6c757d',
            'description' => isset( $state['description'] ) ? $state['description'] : '',
          ];
        }
        if ( ! empty( $result ) ) {
          return $result;
        }
      }
    }

    // Fallback: client stages config option.
    $config = json_decode( get_option( 'pn_customers_manager_client_stages_config', '' ), true );
    if ( ! empty( $config ) && is_array( $config ) ) {
      return $config;
    }

    // Backwards compatibility: derive from old plain stages option.
    $stages  = json_decode( get_option( 'pn_customers_manager_client_stages', self::DEFAULT_STAGES ), true );
    if ( ! is_array( $stages ) || empty( $stages ) ) {
      $stages = json_decode( self::DEFAULT_STAGES, true );
    }
    $defaults = self::DEFAULT_STAGE_COLORS;
    $result   = [];
    foreach ( $stages as $i => $name ) {
      $result[] = [
        'name'        => $name,
        'color'       => $defaults[ $i % count( $defaults ) ],
        'description' => '',
      ];
    }
    return $result;
  }

  /**
   * Get the default stage name for new contacts.
   *
   * @return string
   */
  public static function get_default_stage() {
    $default = get_option( 'pn_customers_manager_default_client_stage', '' );
    if ( ! empty( $default ) ) {
      return $default;
    }
    $stages = self::get_stages();
    return ! empty( $stages ) ? $stages[0] : 'New';
  }

  /**
   * Get configured stage names.
   *
   * @return array
   */
  public static function get_stages() {
    return array_map( function( $s ) { return $s['name']; }, self::get_stages_config() );
  }

  /**
   * Render the stage pipeline widget.
   *
   * @param string $current_stage Current stage name.
   * @param array  $stages_config Full stages config array.
   * @param string $client_type   'user' or 'organization'.
   * @param int    $client_id     Client ID.
   * @param string $size          'small' or 'large'.
   * @return string HTML.
   */
  public static function render_stage_pipeline( $current_stage, $stages_config, $client_type, $client_id, $size = 'small' ) {
    $size_class = $size === 'large' ? ' pn-cm-clients-stage-pipeline-large' : '';
    $html  = '<div class="pn-cm-clients-stage-pipeline' . $size_class . '" data-client-type="' . esc_attr( $client_type ) . '" data-client-id="' . esc_attr( $client_id ) . '">';
    $found = false;
    foreach ( $stages_config as $i => $stage ) {
      $is_active = ! $found;
      $is_current = ( $stage['name'] === $current_stage );
      if ( $is_current ) {
        $found = true;
      }
      if ( $i > 0 ) {
        $html .= '<span class="pn-cm-clients-stage-connector' . ( $is_active ? ' active' : '' ) . '" style="--stage-color:' . esc_attr( $stage['color'] ) . ';"></span>';
      }
      $tooltip = esc_attr( $stage['name'] );
      if ( ! empty( $stage['description'] ) ) {
        $tooltip .= ': ' . esc_attr( $stage['description'] );
      }
      $classes = 'pn-cm-clients-stage-dot pn-customers-manager-tooltip';
      if ( $is_active ) {
        $classes .= ' active';
      }
      if ( $is_current ) {
        $classes .= ' current';
      }
      $html .= '<span class="' . $classes . '" data-pn-customers-manager-tooltip="' . $tooltip . '" data-stage="' . esc_attr( $stage['name'] ) . '" style="--stage-color:' . esc_attr( $stage['color'] ) . ';">';
      if ( $is_current && $size === 'small' ) {
        $html .= '<span class="pn-cm-clients-stage-current-ring"></span>';
      }
      $html .= '</span>';
      if ( $is_current && $size === 'large' ) {
        $html .= '<span class="pn-cm-clients-stage-current-label" style="--stage-color:' . esc_attr( $stage['color'] ) . ';">' . esc_html( $stage['name'] ) . '</span>';
      }
    }
    $html .= '</div>';
    return $html;
  }

  /**
   * Get user display name, prioritizing first name + last name over display_name.
   *
   * @param WP_User $user User object.
   * @return string Display name.
   */
  private static function get_user_display_name( $user ) {
    $first_name = get_user_meta( $user->ID, 'first_name', true );
    $last_name  = get_user_meta( $user->ID, 'last_name', true );

    // Priority 1: first_name + last_name
    if ( ! empty( $first_name ) || ! empty( $last_name ) ) {
      return trim( $first_name . ' ' . $last_name );
    }

    // Priority 2: display_name if it's not an email
    if ( ! empty( $user->display_name ) && ! is_email( $user->display_name ) ) {
      return $user->display_name;
    }

    // Priority 3: user_login if it's not an email
    if ( ! empty( $user->user_login ) && ! is_email( $user->user_login ) ) {
      return $user->user_login;
    }

    // Fallback: display_name (even if it's an email)
    return $user->display_name;
  }

  /**
   * Get clients from both users and organizations.
   *
   * @param array $args Query arguments.
   * @return array
   */
  public static function get_clients( $args = [] ) {
    $defaults = [
      'stage'    => '',
      'source'   => '',
      'search'   => '',
      'per_page' => 20,
      'page'     => 1,
      'orderby'  => 'name',
      'order'    => 'ASC',
    ];
    $args = wp_parse_args( $args, $defaults );

    $clients = [];

    // Query users with pn_cm_client_stage.
    $user_args = [
      'meta_key'     => 'pn_cm_client_stage',
      'meta_compare' => 'EXISTS',
      'number'       => -1,
    ];

    if ( ! empty( $args['stage'] ) ) {
      $user_args['meta_query'] = [
        [
          'key'   => 'pn_cm_client_stage',
          'value' => $args['stage'],
        ],
      ];
    }

    if ( ! empty( $args['search'] ) ) {
      $user_args['search']         = '*' . $args['search'] . '*';
      $user_args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
    }

    $users = get_users( $user_args );

    foreach ( $users as $user ) {
      $source = get_user_meta( $user->ID, 'pn_cm_client_source', true ) ?: 'manual';
      if ( ! empty( $args['source'] ) && $source !== $args['source'] ) {
        continue;
      }
      $phone   = get_user_meta( $user->ID, 'phone', true ) ?: get_user_meta( $user->ID, 'billing_phone', true );
      $country = get_user_meta( $user->ID, 'billing_country', true );
      if ( empty( $country ) && ! empty( $phone ) ) {
        $country = self::country_from_phone( $phone );
      }
      $clients[] = [
        'type'    => 'user',
        'id'      => $user->ID,
        'name'    => self::get_user_display_name( $user ),
        'email'   => $user->user_email,
        'phone'   => $phone,
        'company' => get_user_meta( $user->ID, 'billing_company', true ),
        'stage'   => get_user_meta( $user->ID, 'pn_cm_client_stage', true ),
        'source'  => $source,
        'date'    => $user->user_registered,
        'avatar'  => get_avatar_url( $user->ID, [ 'size' => 80 ] ),
        'country' => $country,
      ];
    }

    // Query organizations with pn_cm_organization_client_stage.
    $org_args = [
      'post_type'      => 'pn_cm_organization',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
      'meta_key'       => 'pn_cm_organization_client_stage',
      'meta_compare'   => 'EXISTS',
    ];

    if ( ! empty( $args['stage'] ) ) {
      $org_args['meta_query'] = [
        [
          'key'   => 'pn_cm_organization_client_stage',
          'value' => $args['stage'],
        ],
      ];
    }

    if ( ! empty( $args['search'] ) ) {
      $org_args['s'] = $args['search'];
    }

    $orgs = get_posts( $org_args );

    foreach ( $orgs as $org ) {
      $source = get_post_meta( $org->ID, 'pn_cm_client_source', true ) ?: 'manual';
      if ( ! empty( $args['source'] ) && $source !== $args['source'] ) {
        continue;
      }
      $phone   = get_post_meta( $org->ID, 'pn_cm_organization_phone', true );
      $country = get_post_meta( $org->ID, 'pn_cm_organization_country', true );
      if ( empty( $country ) && ! empty( $phone ) ) {
        $country = self::country_from_phone( $phone );
      }
      $clients[] = [
        'type'    => 'organization',
        'id'      => $org->ID,
        'name'    => $org->post_title,
        'email'   => get_post_meta( $org->ID, 'pn_cm_organization_email', true ),
        'phone'   => $phone,
        'company' => get_post_meta( $org->ID, 'pn_cm_organization_legal_name', true ),
        'stage'   => get_post_meta( $org->ID, 'pn_cm_organization_client_stage', true ),
        'source'  => $source,
        'date'    => $org->post_date_gmt,
        'avatar'  => '',
        'country' => $country,
      ];
    }

    // Deduplicate: when a user and an organization share the same phone number,
    // keep only the organization (which has WhatsApp conversations linked).
    // This prevents duplicate cards when GHL creates a user and WhatsApp AI
    // creates an organization for the same contact.
    $org_phones = [];
    foreach ( $clients as $client ) {
      if ( $client['type'] === 'organization' && ! empty( $client['phone'] ) ) {
        $clean = preg_replace( '/[^\d]/', '', $client['phone'] );
        if ( ! empty( $clean ) ) {
          $org_phones[ $clean ] = true;
        }
      }
    }
    if ( ! empty( $org_phones ) ) {
      $clients = array_values( array_filter( $clients, function( $client ) use ( $org_phones ) {
        if ( $client['type'] !== 'user' || empty( $client['phone'] ) ) {
          return true;
        }
        $clean = preg_replace( '/[^\d]/', '', $client['phone'] );
        return empty( $clean ) || ! isset( $org_phones[ $clean ] );
      } ) );
    }

    // Sort.
    $allowed_orderby = [ 'name', 'date', 'email', 'company', 'source' ];
    $field = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'name';
    usort( $clients, function( $a, $b ) use ( $field, $args ) {
      if ( $field === 'date' ) {
        $cmp = strcmp( $a['date'], $b['date'] );
      } else {
        $cmp = strcasecmp( $a[ $field ], $b[ $field ] );
      }
      return $args['order'] === 'DESC' ? -$cmp : $cmp;
    } );

    // Paginate.
    $total = count( $clients );
    $offset = ( $args['page'] - 1 ) * $args['per_page'];
    $paged_clients = array_slice( $clients, $offset, $args['per_page'] );

    return [
      'clients'     => $paged_clients,
      'total'       => $total,
      'total_pages' => ceil( $total / $args['per_page'] ),
      'page'        => $args['page'],
      'per_page'    => $args['per_page'],
    ];
  }

  /**
   * Get stage counts.
   *
   * @return array Stage name => count.
   */
  public static function get_stage_counts() {
    $stages = self::get_stages();
    $counts = [];

    // Collect all organization phone numbers (digits only) to deduplicate
    // users that also have an organization with the same phone.
    global $wpdb;
    $org_phones_raw = $wpdb->get_col(
      "SELECT REPLACE(REPLACE(REPLACE(REPLACE(pm.meta_value, '+', ''), ' ', ''), '-', ''), '.', '')
       FROM {$wpdb->postmeta} pm
       INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
       INNER JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = 'pn_cm_organization_client_stage'
       WHERE p.post_type = 'pn_cm_organization'
         AND p.post_status = 'publish'
         AND pm.meta_key = 'pn_cm_organization_phone'
         AND pm.meta_value != ''"
    );
    $org_phones_set = array_flip( array_filter( $org_phones_raw ) );

    foreach ( $stages as $stage ) {
      $org_count = count( get_posts( [
        'post_type'      => 'pn_cm_organization',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'meta_key'       => 'pn_cm_organization_client_stage',
        'meta_value'     => $stage,
      ] ) );

      // Count users, excluding those whose phone matches an org phone.
      $users = get_users( [
        'meta_key'   => 'pn_cm_client_stage',
        'meta_value' => $stage,
        'fields'     => 'ID',
        'number'     => -1,
      ] );

      $user_count = 0;
      if ( ! empty( $org_phones_set ) ) {
        foreach ( $users as $uid ) {
          $phone = get_user_meta( $uid, 'phone', true );
          if ( empty( $phone ) ) {
            $phone = get_user_meta( $uid, 'billing_phone', true );
          }
          $clean = preg_replace( '/[^\d]/', '', $phone );
          if ( empty( $clean ) || ! isset( $org_phones_set[ $clean ] ) ) {
            $user_count++;
          }
        }
      } else {
        $user_count = count( $users );
      }

      $counts[ $stage ] = $user_count + $org_count;
    }

    return $counts;
  }

  /**
   * Render the admin dashboard page.
   */
  public static function render_page() {
    if ( ! current_user_can( 'pn_cm_manage_crm' ) && ! current_user_can( 'manage_options' ) ) {
      wp_die( esc_html__( 'You do not have permission to access this page.', 'pn-customers-manager' ) );
    }

    $stages        = self::get_stages();
    $stages_config = self::get_stages_config();
    $stage_counts  = self::get_stage_counts();
    $nonce         = wp_create_nonce( 'pn-customers-manager-nonce' );
    $ajax_url     = admin_url( 'admin-ajax.php' );

    wp_enqueue_style( 'pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_style( 'pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_style( 'pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-tooltips.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_style( 'pn-customers-manager-clients', PN_CUSTOMERS_MANAGER_URL . 'assets/css/admin/pn-customers-manager-clients.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_script( 'pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', [ 'jquery' ], PN_CUSTOMERS_MANAGER_VERSION, true );
    wp_enqueue_script( 'pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-tooltips.js', [ 'jquery' ], PN_CUSTOMERS_MANAGER_VERSION, true );
    wp_enqueue_script( 'pn-customers-manager-clients', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-clients.js', [ 'jquery', 'pn-customers-manager-popups', 'pn-customers-manager-tooltips' ], PN_CUSTOMERS_MANAGER_VERSION, true );
    wp_localize_script( 'pn-customers-manager-clients', 'pnCmClients', [
      'ajaxUrl'      => $ajax_url,
      'nonce'        => $nonce,
      'stages'       => $stages,
      'i18n'    => [
        'loading'      => __( 'Loading...', 'pn-customers-manager' ),
        'error'        => __( 'Error', 'pn-customers-manager' ),
        'name'         => __( 'Name', 'pn-customers-manager' ),
        'email'        => __( 'Email', 'pn-customers-manager' ),
        'phone'        => __( 'Phone', 'pn-customers-manager' ),
        'stage'        => __( 'Stage', 'pn-customers-manager' ),
        'save'         => __( 'Save', 'pn-customers-manager' ),
        'cancel'       => __( 'Cancel', 'pn-customers-manager' ),
        'active'       => __( 'Active', 'pn-customers-manager' ),
        'completed'    => __( 'Completed', 'pn-customers-manager' ),
        'orderAsc'     => __( 'Ascending', 'pn-customers-manager' ),
        'orderDesc'    => __( 'Descending', 'pn-customers-manager' ),
        'install'      => __( 'Install', 'pn-customers-manager' ),
        'installing'   => __( 'Installing...', 'pn-customers-manager' ),
        'activate'     => __( 'Activate', 'pn-customers-manager' ),
        'activating'   => __( 'Activating...', 'pn-customers-manager' ),
        'installError' => __( 'Error installing plugin.', 'pn-customers-manager' ),
        'activateError' => __( 'Error activating plugin.', 'pn-customers-manager' ),
        'duplicates'       => __( 'Duplicates', 'pn-customers-manager' ),
        'merge'            => __( 'Merge', 'pn-customers-manager' ),
        'mergeAll'         => __( 'Fix all', 'pn-customers-manager' ),
        'merging'          => __( 'Merging...', 'pn-customers-manager' ),
        'merged'           => __( 'Merged successfully.', 'pn-customers-manager' ),
        'primary'          => __( 'Primary', 'pn-customers-manager' ),
        'confirmMerge'     => __( 'Merge this group? Duplicate records will be deleted.', 'pn-customers-manager' ),
        'confirmMergeAll'  => __( 'Merge ALL duplicate groups? This cannot be undone.', 'pn-customers-manager' ),
        'noDuplicates'     => __( 'No duplicates found.', 'pn-customers-manager' ),
      ],
      'showFlags'         => true,
    ] );

    ?>
    <div class="wrap pn-cm-clients-wrap">
      <div class="pn-cm-clients-topbar">
        <div class="pn-cm-clients-topbar-left">
          <button type="button" id="pn-cm-clients-sort-toggle" class="pn-cm-clients-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e( 'Sort', 'pn-customers-manager' ); ?>">
            <span class="material-icons-outlined">sort</span>
          </button>
          <div id="pn-cm-clients-sort-panel" class="pn-cm-clients-dropdown-panel" style="display:none;">
            <div class="pn-cm-clients-panel-header">
              <span><?php esc_html_e( 'Sort by', 'pn-customers-manager' ); ?></span>
            </div>
            <div class="pn-cm-clients-panel-body">
              <select id="pn-cm-clients-orderby" class="pn-cm-clients-panel-select">
                <option value="date" selected><?php esc_html_e( 'Registration date', 'pn-customers-manager' ); ?></option>
                <option value="name"><?php esc_html_e( 'Name', 'pn-customers-manager' ); ?></option>
                <option value="email"><?php esc_html_e( 'Email', 'pn-customers-manager' ); ?></option>
                <option value="company"><?php esc_html_e( 'Company', 'pn-customers-manager' ); ?></option>
                <option value="source"><?php esc_html_e( 'Source', 'pn-customers-manager' ); ?></option>
              </select>
              <button type="button" id="pn-cm-clients-order-toggle" class="pn-cm-clients-order-toggle pn-customers-manager-tooltip" data-order="DESC" title="<?php esc_attr_e( 'Descending', 'pn-customers-manager' ); ?>">
                <span class="material-icons-outlined">arrow_downward</span>
                <span class="pn-cm-clients-order-label"><?php esc_html_e( 'Descending', 'pn-customers-manager' ); ?></span>
              </button>
            </div>
          </div>

          <button type="button" id="pn-cm-clients-filter-toggle" class="pn-cm-clients-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e( 'Filter', 'pn-customers-manager' ); ?>">
            <span class="material-icons-outlined">filter_list</span>
          </button>
          <div id="pn-cm-clients-filter-panel" class="pn-cm-clients-dropdown-panel" style="display:none;">
            <div class="pn-cm-clients-panel-header">
              <span><?php esc_html_e( 'Filter clients', 'pn-customers-manager' ); ?></span>
            </div>
            <div class="pn-cm-clients-panel-body">
              <input type="text" id="pn-cm-clients-search" class="pn-cm-clients-panel-input" placeholder="<?php esc_attr_e( 'Search by name, email...', 'pn-customers-manager' ); ?>" />
              <label class="pn-cm-clients-panel-label"><?php esc_html_e( 'Source', 'pn-customers-manager' ); ?></label>
              <select id="pn-cm-clients-source-filter" class="pn-cm-clients-panel-select">
                <option value=""><?php esc_html_e( 'All sources', 'pn-customers-manager' ); ?></option>
                <option value="manual"><?php esc_html_e( 'Manual', 'pn-customers-manager' ); ?></option>
                <option value="ghl">GHL</option>
                <option value="zoho">Zoho</option>
              </select>
            </div>
          </div>

          <button type="button" id="pn-cm-clients-add" class="pn-cm-clients-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e( 'Add client', 'pn-customers-manager' ); ?>">
            <span class="material-icons-outlined">person_add</span>
          </button>
          <button type="button" id="pn-cm-clients-dup-toggle" class="pn-cm-clients-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e( 'Duplicates', 'pn-customers-manager' ); ?>" style="display:none;">
            <span class="material-icons-outlined">people</span>
            <span class="pn-cm-dup-badge" id="pn-cm-dup-badge">0</span>
          </button>
        </div>
        <div class="pn-cm-clients-topbar-right">
          <select id="pn-cm-clients-per-page" class="pn-cm-clients-per-page-select">
            <option value="10">10</option>
            <option value="20" selected>20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="pn-cm-clients-toolbar-label"><?php esc_html_e( 'per page', 'pn-customers-manager' ); ?></span>

          <div class="pn-cm-clients-view-toggle">
            <button type="button" class="pn-cm-clients-view-btn active pn-customers-manager-tooltip" data-view="grid" title="<?php esc_attr_e( 'Grid view', 'pn-customers-manager' ); ?>">
              <span class="material-icons-outlined">grid_view</span>
            </button>
            <button type="button" class="pn-cm-clients-view-btn pn-customers-manager-tooltip" data-view="list" title="<?php esc_attr_e( 'List view', 'pn-customers-manager' ); ?>">
              <span class="material-icons-outlined">view_list</span>
            </button>
          </div>
        </div>
      </div>

      <div class="pn-customers-manager-tabs pn-cm-clients-stages-bar">
        <button type="button" class="pn-customers-manager-tab-links pn-cm-clients-stage-tab active" data-stage="">
          <?php esc_html_e( 'All', 'pn-customers-manager' ); ?>
          <span class="pn-cm-clients-stage-count"><?php echo esc_html( array_sum( $stage_counts ) ); ?></span>
        </button>
        <?php foreach ( $stages_config as $sc ) :
          $sc_name  = $sc['name'];
          $sc_color = $sc['color'];
          $sc_desc  = isset( $sc['description'] ) ? $sc['description'] : '';
          $sc_count = isset( $stage_counts[ $sc_name ] ) ? $stage_counts[ $sc_name ] : 0;
        ?>
          <button type="button" class="pn-customers-manager-tab-links pn-cm-clients-stage-tab<?php echo ! empty( $sc_desc ) ? ' pn-customers-manager-tooltip' : ''; ?>" data-stage="<?php echo esc_attr( $sc_name ); ?>" style="--stage-color:<?php echo esc_attr( $sc_color ); ?>;"<?php if ( ! empty( $sc_desc ) ) : ?> data-pn-customers-manager-tooltip="<?php echo esc_attr( $sc_desc ); ?>"<?php endif; ?>>
            <span class="pn-cm-clients-stage-indicator" style="background:<?php echo esc_attr( $sc_color ); ?>;"></span>
            <?php echo esc_html( $sc_name ); ?>
            <span class="pn-cm-clients-stage-count"><?php echo esc_html( $sc_count ); ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <div id="pn-cm-clients-content" class="pn-cm-clients-content pn-cm-clients-grid">
        <!-- Loaded via AJAX -->
      </div>

      <div id="pn-cm-clients-pagination" class="pn-cm-clients-pagination"></div>
    </div>

    <div class="pn-customers-manager-popup-overlay pn-customers-manager-display-none-soft" style="display:none;"></div>
    <div id="pn-cm-clients-detail-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" style="display:none;">
      <div class="pn-customers-manager-popup-content">
        <div id="pn-cm-clients-detail-body"></div>
      </div>
    </div>

    <div id="pn-cm-clients-edit-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" style="display:none;">
      <div class="pn-customers-manager-popup-content">
        <div id="pn-cm-clients-edit-body"></div>
      </div>
    </div>

    <div id="pn-cm-clients-dup-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" style="display:none;">
      <div class="pn-customers-manager-popup-content">
        <div id="pn-cm-clients-dup-body"></div>
      </div>
    </div>
    <?php
  }

  /**
   * AJAX: Get clients list.
   */
  public static function ajax_get_clients() {
    $stage    = isset( $_POST['stage'] ) ? sanitize_text_field( wp_unslash( $_POST['stage'] ) ) : '';
    $source   = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
    $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
    $page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
    $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
    $orderby  = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date';
    $order    = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'ASC';
    $view       = isset( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : 'grid';
    $show_flags = isset( $_POST['show_flags'] ) && $_POST['show_flags'] === '1';

    if ( $per_page < 1 ) {
      $per_page = 20;
    }

    $result = self::get_clients( [
      'stage'    => $stage,
      'source'   => $source,
      'search'   => $search,
      'page'     => $page,
      'per_page' => $per_page,
      'orderby'  => $orderby,
      'order'    => $order,
    ] );

    $stages        = self::get_stages();
    $stages_config = self::get_stages_config();

    ob_start();
    if ( empty( $result['clients'] ) ) {
      echo '<div class="pn-cm-clients-empty"><span class="material-icons-outlined">person_off</span><p>' . esc_html__( 'No clients found.', 'pn-customers-manager' ) . '</p></div>';
    } elseif ( $view === 'list' ) {
      self::render_list_view( $result['clients'], $stages, $show_flags, $stages_config );
    } else {
      self::render_grid_view( $result['clients'], $stages, $show_flags, $stages_config );
    }
    $html = ob_get_clean();

    $pagination_html = self::render_pagination_html(
      $result['total'],
      $result['total_pages'],
      $result['page'],
      $result['per_page']
    );

    // Build flag debug info.
    $flag_debug = [];
    if ( $show_flags && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
      foreach ( $result['clients'] as $c ) {
        $lc        = strtolower( trim( $c['country'] ?? '' ) );
        $flag_file = $lc ? PN_CUSTOMERS_MANAGER_DIR . 'assets/media/flags/' . $lc . '.svg' : '';
        $phone_country = ! empty( $c['phone'] ) ? self::country_from_phone( $c['phone'] ) : '';
        $flag_debug[] = [
          'id'              => $c['id'],
          'type'            => $c['type'],
          'name'            => $c['name'],
          'phone'           => $c['phone'] ?? '',
          'country_raw'     => $c['country'] ?? '',
          'country_lower'   => $lc,
          'phone_country'   => $phone_country,
          'country_source'  => $lc ? ( $phone_country === strtoupper( $lc ) && empty( $c['type'] === 'organization' ? get_post_meta( $c['id'], 'pn_cm_organization_country', true ) : get_user_meta( $c['id'], 'billing_country', true ) ) ? 'phone' : 'meta' ) : 'none',
          'svg_exists'      => $flag_file ? file_exists( $flag_file ) : false,
        ];
      }
    }

    $response = [
      'error_key'       => '',
      'html'            => $html,
      'pagination_html' => $pagination_html,
      'total'           => $result['total'],
      'pages'           => $result['total_pages'],
      'page'            => $result['page'],
      'per_page'        => $result['per_page'],
    ];

    if ( ! empty( $flag_debug ) ) {
      $response['flag_debug'] = $flag_debug;
    }

    echo wp_json_encode( $response );
    exit;
  }

  /**
   * AJAX: Update client stage.
   */
  public static function ajax_update_stage() {
    $client_type = isset( $_POST['client_type'] ) ? sanitize_text_field( wp_unslash( $_POST['client_type'] ) ) : '';
    $client_id   = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
    $new_stage   = isset( $_POST['new_stage'] ) ? sanitize_text_field( wp_unslash( $_POST['new_stage'] ) ) : '';

    if ( empty( $client_id ) || empty( $new_stage ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Missing required data.', 'pn-customers-manager' ) ] );
      exit;
    }

    if ( $client_type === 'organization' ) {
      update_post_meta( $client_id, 'pn_cm_organization_client_stage', $new_stage );
    } else {
      update_user_meta( $client_id, 'pn_cm_client_stage', $new_stage );
    }

    echo wp_json_encode( [ 'error_key' => '', 'message' => __( 'Stage updated.', 'pn-customers-manager' ) ] );
    exit;
  }

  /**
   * AJAX: Add a client manually.
   */
  public static function ajax_add_client() {
    $first_name     = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    $last_name      = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    $email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    $company        = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
    $stage          = isset( $_POST['stage'] ) ? sanitize_text_field( wp_unslash( $_POST['stage'] ) ) : '';
    $create_wp_user = isset( $_POST['create_wp_user'] ) && $_POST['create_wp_user'] === '1';
    $username       = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
    $password       = isset( $_POST['password'] ) ? $_POST['password'] : '';

    // Validation.
    if ( empty( $first_name ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_first_name', 'error_content' => __( 'First name is required.', 'pn-customers-manager' ) ] );
      exit;
    }
    if ( empty( $email ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_email', 'error_content' => __( 'Email is required.', 'pn-customers-manager' ) ] );
      exit;
    }
    if ( $create_wp_user && empty( $username ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_username', 'error_content' => __( 'Username is required to create a WordPress user.', 'pn-customers-manager' ) ] );
      exit;
    }
    if ( $create_wp_user && empty( $password ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_password', 'error_content' => __( 'Password is required to create a WordPress user.', 'pn-customers-manager' ) ] );
      exit;
    }

    // Check if email already exists.
    if ( email_exists( $email ) && $create_wp_user ) {
      echo wp_json_encode( [ 'error_key' => 'email_exists', 'error_content' => __( 'A user with this email already exists.', 'pn-customers-manager' ) ] );
      exit;
    }

    // Check if username already exists.
    if ( $create_wp_user && username_exists( $username ) ) {
      echo wp_json_encode( [ 'error_key' => 'username_exists', 'error_content' => __( 'Username already exists. Please choose a different one.', 'pn-customers-manager' ) ] );
      exit;
    }

    if ( empty( $stage ) ) {
      $stage = self::get_default_stage();
    }

    $user_id        = 0;
    $wp_user_created = false;

    // Create WordPress user if requested.
    if ( $create_wp_user ) {
      $user_id = wp_create_user( $username, $password, $email );
      if ( is_wp_error( $user_id ) ) {
        echo wp_json_encode( [ 'error_key' => 'wp_user_create_failed', 'error_content' => $user_id->get_error_message() ] );
        exit;
      }
      // Update user meta.
      wp_update_user( [
        'ID'         => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
      ] );
      $wp_user_created = true;
    }

    // Build full name.
    $full_name = trim( $first_name . ' ' . $last_name );
    if ( empty( $full_name ) ) {
      $full_name = $first_name;
    }

    // Create organization post type.
    $org_title = ! empty( $company ) ? $company : $full_name;
    $org_id    = wp_insert_post( [
      'post_type'   => 'pn_cm_organization',
      'post_title'  => $org_title,
      'post_status' => 'publish',
    ] );

    if ( is_wp_error( $org_id ) ) {
      // If org creation failed and we created a WP user, delete it.
      if ( $wp_user_created && $user_id ) {
        wp_delete_user( $user_id );
      }
      echo wp_json_encode( [ 'error_key' => 'org_create_failed', 'error_content' => $org_id->get_error_message() ] );
      exit;
    }

    // Store organization metadata.
    update_post_meta( $org_id, 'pn_cm_organization_client_stage', $stage );
    update_post_meta( $org_id, 'pn_cm_client_source', 'manual' );
    update_post_meta( $org_id, 'pn_cm_organization_email', $email );
    update_post_meta( $org_id, 'pn_cm_organization_phone', $phone );
    update_post_meta( $org_id, 'pn_cm_organization_first_name', $first_name );
    update_post_meta( $org_id, 'pn_cm_organization_last_name', $last_name );

    // Link organization to WP user if created.
    if ( $wp_user_created && $user_id ) {
      update_post_meta( $org_id, 'pn_cm_organization_user_id', $user_id );
      update_user_meta( $user_id, 'pn_cm_organization_id', $org_id );
    }

    // Initiate WhatsApp conversation with template if configured.
    $wa_template_sent = false;
    if ( ! empty( $phone ) && class_exists( 'PN_CUSTOMERS_MANAGER_WhatsApp_AI' ) ) {
      $wa_template_sent = PN_CUSTOMERS_MANAGER_WhatsApp_AI::initiate_conversation_with_template( $phone, $full_name );
    }

    echo wp_json_encode( [
      'error_key'        => '',
      'message'          => __( 'Client added successfully.', 'pn-customers-manager' ),
      'id'               => $org_id,
      'user_id'          => $user_id,
      'wp_user_created'  => $wp_user_created,
      'wa_template_sent' => $wa_template_sent,
    ] );
    exit;
  }

  /**
   * Render grid view HTML.
   */
  private static function render_grid_view( $clients, $stages, $show_flags = false, $stages_config = null ) {
    if ( null === $stages_config ) {
      $stages_config = self::get_stages_config();
    }
    foreach ( $clients as $client ) {
      $initial   = strtoupper( mb_substr( $client['name'], 0, 1 ) );
      $flag_html = $show_flags && ! empty( $client['country'] ) ? self::country_flag_html( $client['country'] ) : '';
      $date_str  = ! empty( $client['date'] ) ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $client['date'] ) ) : '';

      ?>
      <div class="pn-cm-clients-card" data-client-type="<?php echo esc_attr( $client['type'] ); ?>" data-client-id="<?php echo esc_attr( $client['id'] ); ?>">
        <?php if ( $flag_html ) : ?>
          <div class="pn-cm-clients-card-flag"><?php echo wp_kses_post( $flag_html ); ?></div>
        <?php endif; ?>
        <div class="pn-cm-clients-card-header">
          <div class="pn-cm-clients-avatar">
            <?php if ( ! empty( $client['avatar'] ) ) : ?>
              <img src="<?php echo esc_url( $client['avatar'] ); ?>" alt="" />
            <?php else : ?>
              <span class="pn-cm-clients-initial"><?php echo esc_html( $initial ); ?></span>
            <?php endif; ?>
          </div>
          <div class="pn-cm-clients-card-info">
            <div class="pn-cm-clients-card-name"><?php echo esc_html( $client['name'] ); ?></div>
            <?php if ( ! empty( $client['company'] ) ) : ?>
              <div class="pn-cm-clients-card-company"><?php echo esc_html( $client['company'] ); ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="pn-cm-clients-card-body">
          <?php if ( ! empty( $client['email'] ) ) : ?>
            <div class="pn-cm-clients-card-field">
              <span class="material-icons-outlined pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Email', 'pn-customers-manager' ); ?>">email</span>
              <span class="pn-cm-clients-card-field-text"><?php echo esc_html( $client['email'] ); ?></span>
            </div>
          <?php endif; ?>
          <?php if ( ! empty( $client['phone'] ) ) : ?>
            <div class="pn-cm-clients-card-field">
              <span class="material-icons-outlined pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Phone', 'pn-customers-manager' ); ?>">phone</span>
              <span class="pn-cm-clients-card-field-text"><?php echo esc_html( $client['phone'] ); ?></span>
            </div>
          <?php endif; ?>
          <?php if ( $date_str ) : ?>
            <div class="pn-cm-clients-card-date-inline">
              <span class="material-icons-outlined">calendar_today</span>
              <?php echo esc_html( $date_str ); ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="pn-cm-clients-card-footer">
          <?php echo wp_kses_post( self::render_stage_pipeline( $client['stage'], $stages_config, $client['type'], $client['id'] ) ); ?>
          <button type="button" class="pn-cm-clients-edit-btn pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>" data-client-type="<?php echo esc_attr( $client['type'] ); ?>" data-client-id="<?php echo esc_attr( $client['id'] ); ?>">
            <span class="material-icons-outlined">edit</span>
          </button>
        </div>
      </div>
      <?php
    }
  }

  /**
   * Render list view HTML.
   */
  private static function render_list_view( $clients, $stages, $show_flags = false, $stages_config = null ) {
    if ( null === $stages_config ) {
      $stages_config = self::get_stages_config();
    }
    ?>
    <table class="pn-cm-clients-table">
      <thead>
        <tr>
          <th><?php esc_html_e( 'Name', 'pn-customers-manager' ); ?></th>
          <th><?php esc_html_e( 'Email', 'pn-customers-manager' ); ?></th>
          <th><?php esc_html_e( 'Phone', 'pn-customers-manager' ); ?></th>
          <th><?php esc_html_e( 'Company', 'pn-customers-manager' ); ?></th>
          <th><?php esc_html_e( 'Stage', 'pn-customers-manager' ); ?></th>
          <th><?php esc_html_e( 'Source', 'pn-customers-manager' ); ?></th>
          <th><?php esc_html_e( 'Date', 'pn-customers-manager' ); ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $clients as $client ) :
          $source_label = self::get_source_label( $client['source'] );
        ?>
          <tr data-client-type="<?php echo esc_attr( $client['type'] ); ?>" data-client-id="<?php echo esc_attr( $client['id'] ); ?>">
            <?php $flag_html = $show_flags && ! empty( $client['country'] ) ? self::country_flag_html( $client['country'] ) : ''; ?>
            <td class="pn-cm-clients-cell-name"><?php if ( $flag_html ) : echo wp_kses_post( $flag_html ) . ' '; endif; ?><?php echo esc_html( $client['name'] ); ?></td>
            <td><?php echo esc_html( $client['email'] ); ?></td>
            <td><?php echo esc_html( $client['phone'] ); ?></td>
            <td><?php echo esc_html( $client['company'] ); ?></td>
            <td>
              <?php echo wp_kses_post( self::render_stage_pipeline( $client['stage'], $stages_config, $client['type'], $client['id'] ) ); ?>
            </td>
            <td><?php echo esc_html( $source_label ); ?></td>
            <td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $client['date'] ) ) ); ?></td>
            <td><button type="button" class="pn-cm-clients-edit-btn pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>" data-client-type="<?php echo esc_attr( $client['type'] ); ?>" data-client-id="<?php echo esc_attr( $client['id'] ); ?>"><span class="material-icons-outlined">edit</span></button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php
  }

  /**
   * Get country names array (ISO 3166-1 alpha-2 codes).
   *
   * @return array Associative array of country codes to English names.
   */
  private static function get_country_names() {
    return array(
      'AD' => 'Andorra',
      'AE' => 'United Arab Emirates',
      'AF' => 'Afghanistan',
      'AG' => 'Antigua and Barbuda',
      'AI' => 'Anguilla',
      'AL' => 'Albania',
      'AM' => 'Armenia',
      'AO' => 'Angola',
      'AQ' => 'Antarctica',
      'AR' => 'Argentina',
      'AS' => 'American Samoa',
      'AT' => 'Austria',
      'AU' => 'Australia',
      'AW' => 'Aruba',
      'AX' => 'Åland Islands',
      'AZ' => 'Azerbaijan',
      'BA' => 'Bosnia and Herzegovina',
      'BB' => 'Barbados',
      'BD' => 'Bangladesh',
      'BE' => 'Belgium',
      'BF' => 'Burkina Faso',
      'BG' => 'Bulgaria',
      'BH' => 'Bahrain',
      'BI' => 'Burundi',
      'BJ' => 'Benin',
      'BL' => 'Saint Barthélemy',
      'BM' => 'Bermuda',
      'BN' => 'Brunei',
      'BO' => 'Bolivia',
      'BQ' => 'Caribbean Netherlands',
      'BR' => 'Brazil',
      'BS' => 'Bahamas',
      'BT' => 'Bhutan',
      'BV' => 'Bouvet Island',
      'BW' => 'Botswana',
      'BY' => 'Belarus',
      'BZ' => 'Belize',
      'CA' => 'Canada',
      'CC' => 'Cocos (Keeling) Islands',
      'CD' => 'Congo (DRC)',
      'CF' => 'Central African Republic',
      'CG' => 'Congo (Republic)',
      'CH' => 'Switzerland',
      'CI' => 'Côte d\'Ivoire',
      'CK' => 'Cook Islands',
      'CL' => 'Chile',
      'CM' => 'Cameroon',
      'CN' => 'China',
      'CO' => 'Colombia',
      'CR' => 'Costa Rica',
      'CU' => 'Cuba',
      'CV' => 'Cape Verde',
      'CW' => 'Curaçao',
      'CX' => 'Christmas Island',
      'CY' => 'Cyprus',
      'CZ' => 'Czech Republic',
      'DE' => 'Germany',
      'DJ' => 'Djibouti',
      'DK' => 'Denmark',
      'DM' => 'Dominica',
      'DO' => 'Dominican Republic',
      'DZ' => 'Algeria',
      'EC' => 'Ecuador',
      'EE' => 'Estonia',
      'EG' => 'Egypt',
      'EH' => 'Western Sahara',
      'ER' => 'Eritrea',
      'ES' => 'Spain',
      'ET' => 'Ethiopia',
      'FI' => 'Finland',
      'FJ' => 'Fiji',
      'FK' => 'Falkland Islands',
      'FM' => 'Micronesia',
      'FO' => 'Faroe Islands',
      'FR' => 'France',
      'GA' => 'Gabon',
      'GB' => 'United Kingdom',
      'GD' => 'Grenada',
      'GE' => 'Georgia',
      'GF' => 'French Guiana',
      'GG' => 'Guernsey',
      'GH' => 'Ghana',
      'GI' => 'Gibraltar',
      'GL' => 'Greenland',
      'GM' => 'Gambia',
      'GN' => 'Guinea',
      'GP' => 'Guadeloupe',
      'GQ' => 'Equatorial Guinea',
      'GR' => 'Greece',
      'GS' => 'South Georgia',
      'GT' => 'Guatemala',
      'GU' => 'Guam',
      'GW' => 'Guinea-Bissau',
      'GY' => 'Guyana',
      'HK' => 'Hong Kong',
      'HM' => 'Heard Island',
      'HN' => 'Honduras',
      'HR' => 'Croatia',
      'HT' => 'Haiti',
      'HU' => 'Hungary',
      'ID' => 'Indonesia',
      'IE' => 'Ireland',
      'IL' => 'Israel',
      'IM' => 'Isle of Man',
      'IN' => 'India',
      'IO' => 'British Indian Ocean Territory',
      'IQ' => 'Iraq',
      'IR' => 'Iran',
      'IS' => 'Iceland',
      'IT' => 'Italy',
      'JE' => 'Jersey',
      'JM' => 'Jamaica',
      'JO' => 'Jordan',
      'JP' => 'Japan',
      'KE' => 'Kenya',
      'KG' => 'Kyrgyzstan',
      'KH' => 'Cambodia',
      'KI' => 'Kiribati',
      'KM' => 'Comoros',
      'KN' => 'Saint Kitts and Nevis',
      'KP' => 'North Korea',
      'KR' => 'South Korea',
      'KW' => 'Kuwait',
      'KY' => 'Cayman Islands',
      'KZ' => 'Kazakhstan',
      'LA' => 'Laos',
      'LB' => 'Lebanon',
      'LC' => 'Saint Lucia',
      'LI' => 'Liechtenstein',
      'LK' => 'Sri Lanka',
      'LR' => 'Liberia',
      'LS' => 'Lesotho',
      'LT' => 'Lithuania',
      'LU' => 'Luxembourg',
      'LV' => 'Latvia',
      'LY' => 'Libya',
      'MA' => 'Morocco',
      'MC' => 'Monaco',
      'MD' => 'Moldova',
      'ME' => 'Montenegro',
      'MF' => 'Saint Martin',
      'MG' => 'Madagascar',
      'MH' => 'Marshall Islands',
      'MK' => 'North Macedonia',
      'ML' => 'Mali',
      'MM' => 'Myanmar',
      'MN' => 'Mongolia',
      'MO' => 'Macao',
      'MP' => 'Northern Mariana Islands',
      'MQ' => 'Martinique',
      'MR' => 'Mauritania',
      'MS' => 'Montserrat',
      'MT' => 'Malta',
      'MU' => 'Mauritius',
      'MV' => 'Maldives',
      'MW' => 'Malawi',
      'MX' => 'Mexico',
      'MY' => 'Malaysia',
      'MZ' => 'Mozambique',
      'NA' => 'Namibia',
      'NC' => 'New Caledonia',
      'NE' => 'Niger',
      'NF' => 'Norfolk Island',
      'NG' => 'Nigeria',
      'NI' => 'Nicaragua',
      'NL' => 'Netherlands',
      'NO' => 'Norway',
      'NP' => 'Nepal',
      'NR' => 'Nauru',
      'NU' => 'Niue',
      'NZ' => 'New Zealand',
      'OM' => 'Oman',
      'PA' => 'Panama',
      'PE' => 'Peru',
      'PF' => 'French Polynesia',
      'PG' => 'Papua New Guinea',
      'PH' => 'Philippines',
      'PK' => 'Pakistan',
      'PL' => 'Poland',
      'PM' => 'Saint Pierre and Miquelon',
      'PN' => 'Pitcairn Islands',
      'PR' => 'Puerto Rico',
      'PS' => 'Palestine',
      'PT' => 'Portugal',
      'PW' => 'Palau',
      'PY' => 'Paraguay',
      'QA' => 'Qatar',
      'RE' => 'Réunion',
      'RO' => 'Romania',
      'RS' => 'Serbia',
      'RU' => 'Russia',
      'RW' => 'Rwanda',
      'SA' => 'Saudi Arabia',
      'SB' => 'Solomon Islands',
      'SC' => 'Seychelles',
      'SD' => 'Sudan',
      'SE' => 'Sweden',
      'SG' => 'Singapore',
      'SH' => 'Saint Helena',
      'SI' => 'Slovenia',
      'SJ' => 'Svalbard and Jan Mayen',
      'SK' => 'Slovakia',
      'SL' => 'Sierra Leone',
      'SM' => 'San Marino',
      'SN' => 'Senegal',
      'SO' => 'Somalia',
      'SR' => 'Suriname',
      'SS' => 'South Sudan',
      'ST' => 'São Tomé and Príncipe',
      'SV' => 'El Salvador',
      'SX' => 'Sint Maarten',
      'SY' => 'Syria',
      'SZ' => 'Eswatini',
      'TC' => 'Turks and Caicos Islands',
      'TD' => 'Chad',
      'TF' => 'French Southern Territories',
      'TG' => 'Togo',
      'TH' => 'Thailand',
      'TJ' => 'Tajikistan',
      'TK' => 'Tokelau',
      'TL' => 'Timor-Leste',
      'TM' => 'Turkmenistan',
      'TN' => 'Tunisia',
      'TO' => 'Tonga',
      'TR' => 'Turkey',
      'TT' => 'Trinidad and Tobago',
      'TV' => 'Tuvalu',
      'TW' => 'Taiwan',
      'TZ' => 'Tanzania',
      'UA' => 'Ukraine',
      'UG' => 'Uganda',
      'UM' => 'U.S. Minor Outlying Islands',
      'US' => 'United States',
      'UY' => 'Uruguay',
      'UZ' => 'Uzbekistan',
      'VA' => 'Vatican City',
      'VC' => 'Saint Vincent and the Grenadines',
      'VE' => 'Venezuela',
      'VG' => 'British Virgin Islands',
      'VI' => 'U.S. Virgin Islands',
      'VN' => 'Vietnam',
      'VU' => 'Vanuatu',
      'WF' => 'Wallis and Futuna',
      'WS' => 'Samoa',
      'XK' => 'Kosovo',
      'YE' => 'Yemen',
      'YT' => 'Mayotte',
      'ZA' => 'South Africa',
      'ZM' => 'Zambia',
      'ZW' => 'Zimbabwe',
    );
  }

  /**
   * Generate flag HTML for an ISO 3166-1 alpha-2 country code.
   *
   * Uses local circular SVG flags from assets/media/flags/.
   *
   * @param string $code Two-letter country code.
   * @return string Flag HTML or empty string.
   */
  private static function country_flag_html( $code ) {
    $code = strtoupper( trim( $code ) );
    if ( strlen( $code ) !== 2 || ! ctype_alpha( $code ) ) {
      return '';
    }
    $lc           = strtolower( $code );
    $flag_file    = PN_CUSTOMERS_MANAGER_DIR . 'assets/media/flags/' . $lc . '.svg';
    $flag_url     = PN_CUSTOMERS_MANAGER_URL . 'assets/media/flags/' . $lc . '.svg';
    $country_names = self::get_country_names();
    $country_name = isset( $country_names[ $code ] ) ? $country_names[ $code ] : $code;

    if ( file_exists( $flag_file ) ) {
      $html = '<img src="' . esc_url( $flag_url ) . '" alt="' . esc_attr( $code ) . '" title="' . esc_attr( $country_name ) . '" />';
    } else {
      // Fallback to flagcdn.com if local SVG not found.
      $html = '<img src="https://flagcdn.com/24x18/' . esc_attr( $lc ) . '.png"'
            . ' srcset="https://flagcdn.com/48x36/' . esc_attr( $lc ) . '.png 2x"'
            . ' alt="' . esc_attr( $code ) . '" title="' . esc_attr( $country_name ) . '" />';
    }
    return '<span class="pn-cm-clients-flag">' . $html . '</span>';
  }

  /**
   * Resolve a country code from a phone number prefix.
   *
   * Strips leading +/00 and matches the longest prefix first.
   *
   * @param string $phone Phone number.
   * @return string Two-letter country code or empty string.
   */
  private static function country_from_phone( $phone ) {
    $phone = trim( $phone );
    if ( empty( $phone ) ) {
      return '';
    }

    // Normalize: remove spaces, dashes, parentheses.
    $digits = preg_replace( '/[^0-9+]/', '', $phone );

    // Strip leading + or 00.
    if ( strpos( $digits, '+' ) === 0 ) {
      $digits = substr( $digits, 1 );
    } elseif ( strpos( $digits, '00' ) === 0 ) {
      $digits = substr( $digits, 2 );
    }

    if ( empty( $digits ) ) {
      return '';
    }

    // Phone prefix → ISO 3166-1 alpha-2 mapping (longest prefix first).
    // When multiple countries share a prefix the most common is listed.
    $prefixes = [
      '1684'  => 'AS', '1264'  => 'AI', '1268'  => 'AG', '1242'  => 'BS',
      '1246'  => 'BB', '1441'  => 'BM', '1284'  => 'VG', '1345'  => 'KY',
      '1767'  => 'DM', '1809'  => 'DO', '1829'  => 'DO', '1849'  => 'DO',
      '1473'  => 'GD', '1671'  => 'GU', '1876'  => 'JM', '1664'  => 'MS',
      '1787'  => 'PR', '1939'  => 'PR', '1869'  => 'KN', '1758'  => 'LC',
      '1784'  => 'VC', '1868'  => 'TT', '1649'  => 'TC', '1340'  => 'VI',
      '1'     => 'US',
      '7'     => 'RU',
      '20'    => 'EG',   '27'    => 'ZA',
      '30'    => 'GR',   '31'    => 'NL',   '32'    => 'BE',   '33'    => 'FR',
      '34'    => 'ES',   '36'    => 'HU',   '39'    => 'IT',
      '40'    => 'RO',   '41'    => 'CH',   '43'    => 'AT',   '44'    => 'GB',
      '45'    => 'DK',   '46'    => 'SE',   '47'    => 'NO',   '48'    => 'PL',
      '49'    => 'DE',
      '51'    => 'PE',   '52'    => 'MX',   '53'    => 'CU',   '54'    => 'AR',
      '55'    => 'BR',   '56'    => 'CL',   '57'    => 'CO',   '58'    => 'VE',
      '60'    => 'MY',   '61'    => 'AU',   '62'    => 'ID',   '63'    => 'PH',
      '64'    => 'NZ',   '65'    => 'SG',   '66'    => 'TH',
      '81'    => 'JP',   '82'    => 'KR',   '84'    => 'VN',   '86'    => 'CN',
      '90'    => 'TR',   '91'    => 'IN',   '92'    => 'PK',   '93'    => 'AF',
      '94'    => 'LK',   '95'    => 'MM',   '98'    => 'IR',
      '212'   => 'MA',   '213'   => 'DZ',   '216'   => 'TN',   '218'   => 'LY',
      '220'   => 'GM',   '221'   => 'SN',   '222'   => 'MR',   '223'   => 'ML',
      '224'   => 'GN',   '225'   => 'CI',   '226'   => 'BF',   '227'   => 'NE',
      '228'   => 'TG',   '229'   => 'BJ',   '230'   => 'MU',   '231'   => 'LR',
      '232'   => 'SL',   '233'   => 'GH',   '234'   => 'NG',   '235'   => 'TD',
      '236'   => 'CF',   '237'   => 'CM',   '238'   => 'CV',   '239'   => 'ST',
      '240'   => 'GQ',   '241'   => 'GA',   '242'   => 'CG',   '243'   => 'CD',
      '244'   => 'AO',   '245'   => 'GW',   '246'   => 'IO',   '247'   => 'AC',
      '248'   => 'SC',   '249'   => 'SD',   '250'   => 'RW',   '251'   => 'ET',
      '252'   => 'SO',   '253'   => 'DJ',   '254'   => 'KE',   '255'   => 'TZ',
      '256'   => 'UG',   '257'   => 'BI',   '258'   => 'MZ',   '260'   => 'ZM',
      '261'   => 'MG',   '262'   => 'RE',   '263'   => 'ZW',   '264'   => 'NA',
      '265'   => 'MW',   '266'   => 'LS',   '267'   => 'BW',   '268'   => 'SZ',
      '269'   => 'KM',
      '290'   => 'SH',   '291'   => 'ER',   '297'   => 'AW',   '298'   => 'FO',
      '299'   => 'GL',
      '350'   => 'GI',   '351'   => 'PT',   '352'   => 'LU',   '353'   => 'IE',
      '354'   => 'IS',   '355'   => 'AL',   '356'   => 'MT',   '357'   => 'CY',
      '358'   => 'FI',   '359'   => 'BG',
      '370'   => 'LT',   '371'   => 'LV',   '372'   => 'EE',   '373'   => 'MD',
      '374'   => 'AM',   '375'   => 'BY',   '376'   => 'AD',   '377'   => 'MC',
      '378'   => 'SM',   '380'   => 'UA',   '381'   => 'RS',   '382'   => 'ME',
      '383'   => 'XK',   '385'   => 'HR',   '386'   => 'SI',   '387'   => 'BA',
      '389'   => 'MK',
      '420'   => 'CZ',   '421'   => 'SK',
      '501'   => 'BZ',   '502'   => 'GT',   '503'   => 'SV',   '504'   => 'HN',
      '505'   => 'NI',   '506'   => 'CR',   '507'   => 'PA',   '509'   => 'HT',
      '590'   => 'GP',   '591'   => 'BO',   '592'   => 'GY',   '593'   => 'EC',
      '594'   => 'GF',   '595'   => 'PY',   '596'   => 'MQ',   '597'   => 'SR',
      '598'   => 'UY',   '599'   => 'CW',
      '670'   => 'TL',   '672'   => 'NF',   '673'   => 'BN',   '674'   => 'NR',
      '675'   => 'PG',   '676'   => 'TO',   '677'   => 'SB',   '678'   => 'VU',
      '679'   => 'FJ',   '680'   => 'PW',   '681'   => 'WF',   '682'   => 'CK',
      '683'   => 'NU',   '685'   => 'WS',   '686'   => 'KI',   '687'   => 'NC',
      '688'   => 'TV',   '689'   => 'PF',   '690'   => 'TK',   '691'   => 'FM',
      '692'   => 'MH',
      '850'   => 'KP',   '852'   => 'HK',   '853'   => 'MO',   '855'   => 'KH',
      '856'   => 'LA',
      '880'   => 'BD',   '886'   => 'TW',
      '960'   => 'MV',   '961'   => 'LB',   '962'   => 'JO',   '963'   => 'SY',
      '964'   => 'IQ',   '965'   => 'KW',   '966'   => 'SA',   '967'   => 'YE',
      '968'   => 'OM',   '970'   => 'PS',   '971'   => 'AE',   '972'   => 'IL',
      '973'   => 'BH',   '974'   => 'QA',   '975'   => 'BT',   '976'   => 'MN',
      '977'   => 'NP',
      '992'   => 'TJ',   '993'   => 'TM',   '994'   => 'AZ',   '995'   => 'GE',
      '996'   => 'KG',   '998'   => 'UZ',
    ];

    // Try longest prefix first (4 → 3 → 2 → 1 digits).
    for ( $len = min( 4, strlen( $digits ) ); $len >= 1; $len-- ) {
      $prefix = substr( $digits, 0, $len );
      if ( isset( $prefixes[ $prefix ] ) ) {
        return $prefixes[ $prefix ];
      }
    }

    return '';
  }

  /**
   * Get source display label.
   */
  private static function get_source_label( $source ) {
    $labels = [
      'ghl'    => 'GHL',
      'zoho'   => 'Zoho',
      'manual' => __( 'Manual', 'pn-customers-manager' ),
    ];
    return isset( $labels[ $source ] ) ? $labels[ $source ] : ucfirst( $source );
  }

  /**
   * Get source icon name.
   */
  private static function get_source_icon( $source ) {
    $icons = [
      'ghl'    => 'sync',
      'zoho'   => 'sync',
      'manual' => 'person',
    ];
    return isset( $icons[ $source ] ) ? $icons[ $source ] : 'person';
  }

  // ── Render helpers for server-side HTML ────────────────────────

  /**
   * Render a detail popup field row.
   *
   * @param string $icon   Material icon name.
   * @param string $label  Field label.
   * @param string $value  Field value.
   * @param string $type   'text', 'email', 'phone', or 'link'.
   * @return string HTML.
   */
  private static function render_detail_field( $icon, $label, $value, $type = 'text' ) {
    if ( empty( $value ) ) {
      return '';
    }
    $display = esc_html( $value );
    if ( $type === 'email' ) {
      $display = '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
    } elseif ( $type === 'phone' ) {
      $display = '<a href="tel:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
    } elseif ( $type === 'link' ) {
      $url = ( strpos( $value, 'http' ) === 0 ) ? $value : 'https://' . $value;
      $display = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $value ) . '</a>';
    }
    return '<div class="pn-cm-clients-popup-field">'
      . '<span class="material-icons-outlined pn-cm-clients-popup-field-icon">' . esc_html( $icon ) . '</span>'
      . '<div><span class="pn-cm-clients-popup-field-label">' . esc_html( $label ) . '</span>'
      . '<span class="pn-cm-clients-popup-field-value">' . $display . '</span></div>'
      . '</div>';
  }

  /**
   * Render a single conversation item for the detail popup.
   */
  private static function render_conversation_item_html( $conv ) {
    $platform_icon  = $conv['platform'] === 'whatsapp' ? 'chat' : 'photo_camera';
    $platform_class = $conv['platform'] === 'whatsapp' ? 'platform-whatsapp' : 'platform-instagram';
    $status_class   = 'status-' . ( $conv['status'] ?: 'active' );
    $updated        = ! empty( $conv['updated_at'] ) ? substr( $conv['updated_at'], 0, 10 ) : '';

    return '<div class="pn-cm-clients-conv-item" data-conv-id="' . esc_attr( $conv['id'] ) . '" data-conv-platform="' . esc_attr( $conv['platform'] ) . '">'
      . '<div class="pn-cm-clients-conv-header">'
      . '<div class="pn-cm-clients-conv-platform ' . esc_attr( $platform_class ) . '"><span class="material-icons-outlined">' . esc_html( $platform_icon ) . '</span></div>'
      . '<div class="pn-cm-clients-conv-info">'
      . '<div class="pn-cm-clients-conv-name">' . esc_html( $conv['contact_name'] ?: $conv['phone'] ) . '</div>'
      . '<div class="pn-cm-clients-conv-detail">' . esc_html( $conv['phone'] ) . ' &middot; ' . (int) $conv['message_count'] . ' ' . esc_html__( 'messages', 'pn-customers-manager' ) . '</div>'
      . '</div>'
      . '<div class="pn-cm-clients-conv-meta">'
      . '<span class="pn-cm-clients-conv-date">' . esc_html( $updated ) . '</span>'
      . '<span class="pn-cm-clients-conv-status ' . esc_attr( $status_class ) . '">' . esc_html( $conv['status'] ?: 'active' ) . '</span>'
      . '</div>'
      . '<span class="material-icons-outlined pn-cm-clients-conv-toggle">expand_more</span>'
      . '</div>'
      . '<div class="pn-cm-clients-conv-thread" style="display:none;"></div>'
      . '</div>';
  }

  /**
   * Render a single task item for the detail popup.
   */
  public static function render_task_item_html( $task ) {
    $status_class = $task['status'] === 'completed' ? 'status-completed' : 'status-active';
    $status_label = $task['status'] === 'completed' ? __( 'Completed', 'pn-customers-manager' ) : __( 'Active', 'pn-customers-manager' );
    $check_icon   = $task['status'] === 'completed' ? 'check_circle' : 'radio_button_unchecked';

    $html  = '<div class="pn-cm-clients-task-item">';
    $html .= '<button type="button" class="pn-cm-clients-task-complete-btn" data-task-id="' . esc_attr( $task['id'] ) . '" title="' . esc_attr__( 'Complete', 'pn-customers-manager' ) . '"><span class="material-icons-outlined">' . esc_html( $check_icon ) . '</span></button>';
    $html .= '<div class="pn-cm-clients-task-info">';
    $html .= '<div class="pn-cm-clients-task-title">' . esc_html( $task['title'] ) . '</div>';
    $html .= '<div class="pn-cm-clients-task-detail">';
    if ( ! empty( $task['date'] ) ) {
      $html .= '<span class="material-icons-outlined">calendar_today</span> ' . esc_html( $task['date'] );
    }
    if ( ! empty( $task['time'] ) ) {
      $html .= ' ' . esc_html( $task['time'] );
    }
    $html .= '</div></div>';
    $html .= '<span class="pn-cm-clients-task-status ' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</span>';
    $html .= '</div>';
    return $html;
  }

  /**
   * Render a single appointment item for the detail popup.
   */
  public static function render_appointment_item_html( $appt ) {
    $status_map = [
      'pending'   => [ 'label' => __( 'Pending', 'pn-customers-manager' ),   'cls' => 'status-pending' ],
      'confirmed' => [ 'label' => __( 'Confirmed', 'pn-customers-manager' ), 'cls' => 'status-confirmed' ],
      'cancelled' => [ 'label' => __( 'Cancelled', 'pn-customers-manager' ), 'cls' => 'status-cancelled' ],
    ];
    $st         = isset( $status_map[ $appt['status'] ] ) ? $status_map[ $appt['status'] ] : $status_map['pending'];
    $time_range = ( $appt['start_time'] ?: '' ) . ( ! empty( $appt['end_time'] ) ? ' - ' . $appt['end_time'] : '' );

    $html  = '<div class="pn-cm-clients-appt-item">';
    $html .= '<div class="pn-cm-clients-appt-info">';
    $html .= '<div class="pn-cm-clients-appt-title">' . esc_html( $appt['title'] ) . '</div>';
    $html .= '<div class="pn-cm-clients-appt-detail">';
    $html .= '<span class="material-icons-outlined">calendar_today</span> ' . esc_html( $appt['date'] ?: '' );
    if ( $time_range ) {
      $html .= ' &middot; <span class="material-icons-outlined">schedule</span> ' . esc_html( $time_range );
    }
    if ( ! empty( $appt['duration'] ) ) {
      $html .= ' (' . esc_html( $appt['duration'] ) . ' min)';
    }
    $html .= '</div></div>';
    $html .= '<span class="pn-cm-clients-appt-status ' . esc_attr( $st['cls'] ) . '">' . esc_html( $st['label'] ) . '</span>';
    $html .= '</div>';
    return $html;
  }

  /**
   * Render plugin action button (install/activate) for PN Tasks Manager.
   */
  private static function render_plugin_action_button( $status ) {
    if ( ! current_user_can( 'install_plugins' ) ) {
      return '';
    }
    if ( $status === 'not_installed' ) {
      return '<button type="button" class="pn-cm-clients-btn-primary pn-cm-clients-plugin-install" data-slug="pn-tasks-manager"><span class="material-icons-outlined">download</span> ' . esc_html__( 'Install', 'pn-customers-manager' ) . '</button>';
    }
    if ( $status === 'installed' ) {
      return '<button type="button" class="pn-cm-clients-btn-primary pn-cm-clients-plugin-activate" data-slug="pn-tasks-manager"><span class="material-icons-outlined">power_settings_new</span> ' . esc_html__( 'Activate', 'pn-customers-manager' ) . '</button>';
    }
    return '';
  }

  /**
   * Render the full detail popup body HTML for a client.
   *
   * @param array $client Client detail data array.
   * @return string HTML.
   */
  public static function render_detail_html( $client ) {
    $initial       = strtoupper( mb_substr( $client['name'], 0, 1 ) );
    $source_label  = self::get_source_label( $client['source'] );
    $source_icon   = $client['source'] === 'manual' ? 'person' : 'sync';
    $stages_config = $client['stagesConfig'];

    // Location string.
    $location_parts = [];
    if ( ! empty( $client['address'] ) )     $location_parts[] = $client['address'];
    if ( ! empty( $client['postal_code'] ) ) $location_parts[] = $client['postal_code'];
    if ( ! empty( $client['city'] ) )        $location_parts[] = $client['city'];
    if ( ! empty( $client['region'] ) )      $location_parts[] = $client['region'];
    if ( ! empty( $client['country'] ) )     $location_parts[] = $client['country'];
    $location_str = implode( ', ', $location_parts );

    $conversations    = $client['conversations'];
    $tasks            = $client['tasks'];
    $appointments     = $client['appointments'];
    $has_tasks_plugin = $client['hasTasksPlugin'];

    ob_start();
    ?>
    <div class="pn-cm-clients-popup-header">
      <div class="pn-cm-clients-popup-avatar-wrap">
        <?php if ( ! empty( $client['avatar'] ) ) : ?>
          <img src="<?php echo esc_url( $client['avatar'] ); ?>" class="pn-cm-clients-popup-avatar-img" alt="" />
        <?php else : ?>
          <span class="pn-cm-clients-popup-avatar-initial"><?php echo esc_html( $initial ); ?></span>
        <?php endif; ?>
      </div>
      <h2 class="pn-cm-clients-popup-name"><?php echo esc_html( $client['name'] ); ?></h2>
      <?php if ( ! empty( $client['company'] ) ) : ?>
        <p class="pn-cm-clients-popup-company"><?php echo esc_html( $client['company'] ); ?></p>
      <?php endif; ?>
      <div class="pn-cm-clients-popup-badges">
        <span class="pn-cm-clients-popup-source-badge pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Source', 'pn-customers-manager' ); ?>">
          <span class="material-icons-outlined"><?php echo esc_html( $source_icon ); ?></span> <?php echo esc_html( $source_label ); ?>
        </span>
        <span class="pn-cm-clients-popup-date-badge pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Registration date', 'pn-customers-manager' ); ?>">
          <span class="material-icons-outlined">calendar_today</span> <?php echo esc_html( ! empty( $client['date'] ) ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $client['date'] ) ) : '' ); ?>
        </span>
      </div>
    </div>

    <div class="pn-customers-manager-tabs-wrapper pn-cm-clients-tabs-wrapper">
      <div class="pn-customers-manager-tabs">
        <button type="button" class="pn-customers-manager-tab-links active" data-pn-customers-manager-id="pn-cm-client-tab-info"><?php esc_html_e( 'Info', 'pn-customers-manager' ); ?></button>
        <button type="button" class="pn-customers-manager-tab-links" data-pn-customers-manager-id="pn-cm-client-tab-conversations"><?php esc_html_e( 'Conversations', 'pn-customers-manager' ); ?><?php if ( count( $conversations ) ) : ?> <span class="pn-cm-clients-popup-tab-count"><?php echo count( $conversations ); ?></span><?php endif; ?></button>
        <button type="button" class="pn-customers-manager-tab-links" data-pn-customers-manager-id="pn-cm-client-tab-tasks"><?php esc_html_e( 'Tasks', 'pn-customers-manager' ); ?><?php if ( count( $tasks ) ) : ?> <span class="pn-cm-clients-popup-tab-count"><?php echo count( $tasks ); ?></span><?php endif; ?></button>
        <button type="button" class="pn-customers-manager-tab-links" data-pn-customers-manager-id="pn-cm-client-tab-appointments"><?php esc_html_e( 'Appointments', 'pn-customers-manager' ); ?><?php if ( count( $appointments ) ) : ?> <span class="pn-cm-clients-popup-tab-count"><?php echo count( $appointments ); ?></span><?php endif; ?></button>
      </div>

      <!-- Tab: Info -->
      <div id="pn-cm-client-tab-info" class="pn-customers-manager-tab-content">
        <div class="pn-cm-clients-popup-section">
          <h3 class="pn-cm-clients-popup-section-title"><span class="material-icons-outlined">flag</span> <?php esc_html_e( 'Stage', 'pn-customers-manager' ); ?></h3>
          <?php echo wp_kses_post( self::render_stage_pipeline( $client['stage'], $stages_config, $client['type'], $client['id'], 'large' ) ); ?>
        </div>

        <div class="pn-cm-clients-popup-section">
          <h3 class="pn-cm-clients-popup-section-title"><span class="material-icons-outlined">person</span> <?php esc_html_e( 'Personal data', 'pn-customers-manager' ); ?></h3>
          <div class="pn-cm-clients-popup-fields">
            <?php
              echo wp_kses_post( self::render_detail_field( 'email', __( 'Email', 'pn-customers-manager' ), $client['email'], 'email' ) );
              echo wp_kses_post( self::render_detail_field( 'phone', __( 'Phone', 'pn-customers-manager' ), $client['phone'], 'phone' ) );
              if ( ! empty( $client['website'] ) )  echo wp_kses_post( self::render_detail_field( 'language', __( 'Website', 'pn-customers-manager' ), $client['website'], 'link' ) );
              if ( ! empty( $client['linkedin'] ) ) echo wp_kses_post( self::render_detail_field( 'work', 'LinkedIn', $client['linkedin'], 'link' ) );
              if ( ! empty( $client['cif'] ) )      echo wp_kses_post( self::render_detail_field( 'badge', __( 'Tax ID', 'pn-customers-manager' ), $client['cif'] ) );
              if ( $client['type'] === 'user' && ! empty( $client['ai_paused'] ) )
                echo wp_kses_post( self::render_detail_field( 'pause_circle', __( 'AI Status', 'pn-customers-manager' ), __( 'Paused', 'pn-customers-manager' ) ) );
            ?>
          </div>
        </div>

        <?php if ( $location_str ) : ?>
          <div class="pn-cm-clients-popup-section">
            <h3 class="pn-cm-clients-popup-section-title"><span class="material-icons-outlined">location_on</span> <?php esc_html_e( 'Location', 'pn-customers-manager' ); ?></h3>
            <div class="pn-cm-clients-popup-fields">
              <div class="pn-cm-clients-popup-field"><span class="pn-cm-clients-popup-field-value"><?php echo esc_html( $location_str ); ?></span></div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ( ! empty( $client['industry'] ) || ! empty( $client['segment'] ) ) : ?>
          <div class="pn-cm-clients-popup-section">
            <h3 class="pn-cm-clients-popup-section-title"><span class="material-icons-outlined">info</span> <?php esc_html_e( 'Details', 'pn-customers-manager' ); ?></h3>
            <div class="pn-cm-clients-popup-fields">
              <?php
                if ( ! empty( $client['industry'] ) ) echo wp_kses_post( self::render_detail_field( 'business', __( 'Industry', 'pn-customers-manager' ), $client['industry'] ) );
                if ( ! empty( $client['segment'] ) )  echo wp_kses_post( self::render_detail_field( 'category', __( 'Segment', 'pn-customers-manager' ), $client['segment'] ) );
              ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Tab: Conversations -->
      <div id="pn-cm-client-tab-conversations" class="pn-customers-manager-tab-content pn-customers-manager-display-none">
        <?php if ( empty( $conversations ) ) : ?>
          <div class="pn-cm-clients-tab-empty"><span class="material-icons-outlined">chat_bubble_outline</span><p><?php esc_html_e( 'No conversations found.', 'pn-customers-manager' ); ?></p></div>
        <?php else : ?>
          <?php foreach ( $conversations as $conv ) : ?>
            <?php echo wp_kses_post( self::render_conversation_item_html( $conv ) ); ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Tab: Tasks -->
      <div id="pn-cm-client-tab-tasks" class="pn-customers-manager-tab-content pn-customers-manager-display-none">
        <?php if ( ! $has_tasks_plugin ) : ?>
          <div class="pn-cm-clients-tab-empty">
            <span class="material-icons-outlined">info</span>
            <p><?php esc_html_e( 'PN Tasks Manager plugin is required for this feature.', 'pn-customers-manager' ); ?></p>
            <?php echo wp_kses_post( self::render_plugin_action_button( $client['tasksPluginStatus'] ) ); ?>
          </div>
        <?php else : ?>
          <div class="pn-cm-clients-tasks-list">
            <?php if ( empty( $tasks ) ) : ?>
              <div class="pn-cm-clients-tab-empty"><span class="material-icons-outlined">task_alt</span><p><?php esc_html_e( 'No tasks found.', 'pn-customers-manager' ); ?></p></div>
            <?php else : ?>
              <?php foreach ( $tasks as $task ) : ?>
                <?php echo wp_kses_post( self::render_task_item_html( $task ) ); ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <button type="button" class="pn-cm-clients-add-task-btn pn-cm-clients-btn-primary"><span class="material-icons-outlined">add</span> <?php esc_html_e( 'Add task', 'pn-customers-manager' ); ?></button>
          <div class="pn-cm-clients-add-task-form pn-cm-clients-add-form">
            <div class="pn-cm-clients-add-form-grid">
              <div class="pn-cm-clients-add-form-field pn-cm-clients-add-form-field-full"><label><?php esc_html_e( 'Title', 'pn-customers-manager' ); ?></label><input type="text" name="task_title" /></div>
              <div class="pn-cm-clients-add-form-field"><label><?php esc_html_e( 'Date', 'pn-customers-manager' ); ?></label><input type="date" name="task_date" /></div>
              <div class="pn-cm-clients-add-form-field"><label><?php esc_html_e( 'Time', 'pn-customers-manager' ); ?></label><input type="time" name="task_time" /></div>
              <div class="pn-cm-clients-add-form-field pn-cm-clients-add-form-field-full"><label><?php esc_html_e( 'Description', 'pn-customers-manager' ); ?></label><textarea name="task_description" rows="2"></textarea></div>
            </div>
            <div class="pn-cm-clients-add-form-actions"><button type="button" class="pn-cm-clients-save-task pn-cm-clients-btn-primary"><?php esc_html_e( 'Save', 'pn-customers-manager' ); ?></button></div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Tab: Appointments -->
      <div id="pn-cm-client-tab-appointments" class="pn-customers-manager-tab-content pn-customers-manager-display-none">
        <?php if ( ! $has_tasks_plugin ) : ?>
          <div class="pn-cm-clients-tab-empty">
            <span class="material-icons-outlined">info</span>
            <p><?php esc_html_e( 'PN Tasks Manager plugin is required for this feature.', 'pn-customers-manager' ); ?></p>
            <?php echo wp_kses_post( self::render_plugin_action_button( $client['tasksPluginStatus'] ) ); ?>
          </div>
        <?php else : ?>
          <div class="pn-cm-clients-appts-list">
            <?php if ( empty( $appointments ) ) : ?>
              <div class="pn-cm-clients-tab-empty"><span class="material-icons-outlined">event</span><p><?php esc_html_e( 'No appointments found.', 'pn-customers-manager' ); ?></p></div>
            <?php else : ?>
              <?php foreach ( $appointments as $appt ) : ?>
                <?php echo wp_kses_post( self::render_appointment_item_html( $appt ) ); ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <button type="button" class="pn-cm-clients-add-appt-btn pn-cm-clients-btn-primary"><span class="material-icons-outlined">add</span> <?php esc_html_e( 'Add appointment', 'pn-customers-manager' ); ?></button>
          <div class="pn-cm-clients-add-appt-form pn-cm-clients-add-form">
            <div class="pn-cm-clients-add-form-grid">
              <div class="pn-cm-clients-add-form-field"><label><?php esc_html_e( 'Date', 'pn-customers-manager' ); ?></label><input type="date" name="appointment_date" /></div>
              <div class="pn-cm-clients-add-form-field"><label><?php esc_html_e( 'Start time', 'pn-customers-manager' ); ?></label><input type="time" name="appointment_start_time" /></div>
              <div class="pn-cm-clients-add-form-field"><label><?php esc_html_e( 'End time', 'pn-customers-manager' ); ?></label><input type="time" name="appointment_end_time" /></div>
            </div>
            <div class="pn-cm-clients-add-form-actions"><button type="button" class="pn-cm-clients-save-appt pn-cm-clients-btn-primary"><?php esc_html_e( 'Save', 'pn-customers-manager' ); ?></button></div>
          </div>
        <?php endif; ?>
      </div>

      <div class="pn-customers-manager-navigator-previous"></div>
      <div class="pn-customers-manager-navigator-next"></div>
    </div>

    <div class="pn-cm-clients-popup-footer">
      <a href="<?php echo esc_url( $client['edit_url'] ); ?>" class="pn-cm-clients-btn-primary"><span class="material-icons-outlined">edit</span> <?php esc_html_e( 'Edit', 'pn-customers-manager' ); ?></a>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Render pagination HTML.
   *
   * @param int $total    Total number of items.
   * @param int $pages    Total pages.
   * @param int $page     Current page.
   * @param int $per_page Items per page.
   * @return string HTML.
   */
  public static function render_pagination_html( $total, $pages, $page, $per_page ) {
    if ( $pages <= 1 ) {
      return '';
    }
    $from = ( ( $page - 1 ) * $per_page ) + 1;
    $to   = min( $page * $per_page, $total );

    $html  = '<div class="pn-cm-clients-pagination-inner">';
    $html .= '<span class="pn-cm-clients-pagination-info">'
           . esc_html__( 'Showing', 'pn-customers-manager' ) . ' ' . $from . '-' . $to . ' '
           . esc_html__( 'of', 'pn-customers-manager' ) . ' ' . $total
           . '</span>';

    $html .= '<div class="pn-cm-clients-pagination-buttons">';

    // Prev.
    $html .= '<button type="button" class="pn-cm-clients-page-btn" data-page="' . ( $page - 1 ) . '"'
           . ( $page <= 1 ? ' disabled' : '' )
           . ' title="' . esc_attr__( 'Previous', 'pn-customers-manager' ) . '">'
           . '<span class="material-icons-outlined">chevron_left</span></button>';

    $start_page = max( 1, $page - 2 );
    $end_page   = min( $pages, $page + 2 );

    if ( $start_page > 1 ) {
      $html .= '<button type="button" class="pn-cm-clients-page-btn" data-page="1">1</button>';
      if ( $start_page > 2 ) {
        $html .= '<span class="pn-cm-clients-pagination-dots">&hellip;</span>';
      }
    }

    for ( $p = $start_page; $p <= $end_page; $p++ ) {
      $html .= '<button type="button" class="pn-cm-clients-page-btn' . ( $p === $page ? ' active' : '' ) . '" data-page="' . $p . '">' . $p . '</button>';
    }

    if ( $end_page < $pages ) {
      if ( $end_page < $pages - 1 ) {
        $html .= '<span class="pn-cm-clients-pagination-dots">&hellip;</span>';
      }
      $html .= '<button type="button" class="pn-cm-clients-page-btn" data-page="' . $pages . '">' . $pages . '</button>';
    }

    // Next.
    $html .= '<button type="button" class="pn-cm-clients-page-btn" data-page="' . ( $page + 1 ) . '"'
           . ( $page >= $pages ? ' disabled' : '' )
           . ' title="' . esc_attr__( 'Next', 'pn-customers-manager' ) . '">'
           . '<span class="material-icons-outlined">chevron_right</span></button>';

    $html .= '</div></div>';
    return $html;
  }

  /**
   * Render conversation messages thread HTML.
   *
   * @param array  $messages Array of message objects.
   * @param string $platform 'whatsapp' or 'instagram'.
   * @return string HTML.
   */
  public static function render_messages_html( $messages, $platform ) {
    if ( empty( $messages ) ) {
      return '<div class="pn-cm-clients-conv-empty">' . esc_html__( 'No messages', 'pn-customers-manager' ) . '</div>';
    }
    $html = '<div class="pn-cm-clients-conv-messages">';
    foreach ( $messages as $m ) {
      $role = isset( $m['role'] ) ? $m['role'] : 'user';
      $time = ! empty( $m['timestamp'] ) ? substr( $m['timestamp'], 11, 5 ) : '';
      $html .= '<div class="pn-cm-clients-msg pn-cm-clients-msg-' . esc_attr( $role ) . '">';
      $html .= '<div class="pn-cm-clients-msg-bubble">';
      if ( ! empty( $m['quoted_text'] ) ) {
        $html .= '<div class="pn-cm-clients-msg-quote">' . esc_html( mb_substr( $m['quoted_text'], 0, 300 ) ) . '</div>';
      }
      $html .= '<div class="pn-cm-clients-msg-text">' . nl2br( esc_html( $m['content'] ?? '' ) ) . '</div>';
      $html .= '<div class="pn-cm-clients-msg-time">' . esc_html( $time ) . '</div>';
      $html .= '</div></div>';
    }
    $html .= '</div>';
    return $html;
  }

  /**
   * AJAX: Get detailed client info for the popup (returns rendered HTML).
   */
  public static function ajax_get_detail() {
    $client_type = isset( $_POST['client_type'] ) ? sanitize_text_field( wp_unslash( $_POST['client_type'] ) ) : '';
    $client_id   = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;

    if ( empty( $client_id ) || empty( $client_type ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Missing required data.', 'pn-customers-manager' ) ] );
      exit;
    }

    $stages = self::get_stages();
    $detail = [];

    if ( $client_type === 'organization' ) {
      $org = get_post( $client_id );
      if ( ! $org || $org->post_type !== 'pn_cm_organization' ) {
        echo wp_json_encode( [ 'error_key' => 'not_found', 'error_content' => __( 'Client not found.', 'pn-customers-manager' ) ] );
        exit;
      }

      $detail = [
        'type'       => 'organization',
        'id'         => $client_id,
        'name'       => $org->post_title,
        'email'      => get_post_meta( $client_id, 'pn_cm_organization_email', true ),
        'phone'      => get_post_meta( $client_id, 'pn_cm_organization_phone', true ),
        'company'    => get_post_meta( $client_id, 'pn_cm_organization_legal_name', true ),
        'stage'      => get_post_meta( $client_id, 'pn_cm_organization_client_stage', true ),
        'source'     => get_post_meta( $client_id, 'pn_cm_client_source', true ) ?: 'manual',
        'date'       => $org->post_date,
        'avatar'     => '',
        'website'    => get_post_meta( $client_id, 'pn_cm_organization_website', true ),
        'address'    => get_post_meta( $client_id, 'pn_cm_organization_address', true ),
        'city'       => get_post_meta( $client_id, 'pn_cm_organization_city', true ),
        'region'     => get_post_meta( $client_id, 'pn_cm_organization_region', true ),
        'postal_code' => get_post_meta( $client_id, 'pn_cm_organization_postal_code', true ),
        'country'    => get_post_meta( $client_id, 'pn_cm_organization_country', true ),
        'cif'        => get_post_meta( $client_id, 'pn_cm_organization_cif', true ),
        'linkedin'   => get_post_meta( $client_id, 'pn_cm_organization_linkedin', true ),
        'industry'   => get_post_meta( $client_id, 'pn_cm_organization_industry', true ),
        'segment'    => get_post_meta( $client_id, 'pn_cm_organization_segment', true ),
        'edit_url'   => admin_url( 'post.php?post=' . $client_id . '&action=edit' ),
      ];
    } else {
      $user = get_userdata( $client_id );
      if ( ! $user ) {
        echo wp_json_encode( [ 'error_key' => 'not_found', 'error_content' => __( 'Client not found.', 'pn-customers-manager' ) ] );
        exit;
      }

      $phone = get_user_meta( $client_id, 'phone', true ) ?: get_user_meta( $client_id, 'billing_phone', true );

      $detail = [
        'type'       => 'user',
        'id'         => $client_id,
        'name'       => self::get_user_display_name( $user ),
        'first_name' => get_user_meta( $client_id, 'first_name', true ),
        'last_name'  => get_user_meta( $client_id, 'last_name', true ),
        'email'      => $user->user_email,
        'phone'      => $phone,
        'company'    => get_user_meta( $client_id, 'billing_company', true ),
        'stage'      => get_user_meta( $client_id, 'pn_cm_client_stage', true ),
        'ai_paused'  => get_user_meta( $client_id, 'pn_cm_ai_paused', true ),
        'source'     => get_user_meta( $client_id, 'pn_cm_client_source', true ) ?: 'manual',
        'date'       => $user->user_registered,
        'avatar'     => get_avatar_url( $client_id, [ 'size' => 120 ] ),
        'website'    => $user->user_url,
        'address'    => get_user_meta( $client_id, 'billing_address_1', true ),
        'city'       => get_user_meta( $client_id, 'billing_city', true ),
        'region'     => get_user_meta( $client_id, 'billing_state', true ),
        'postal_code' => get_user_meta( $client_id, 'billing_postcode', true ),
        'country'    => get_user_meta( $client_id, 'billing_country', true ),
        'cif'        => '',
        'linkedin'   => '',
        'industry'   => '',
        'segment'    => '',
        'edit_url'   => admin_url( 'user-edit.php?user_id=' . $client_id ),
      ];
    }

    $detail['stages']       = $stages;
    $detail['stagesConfig'] = self::get_stages_config();

    // Tabs data.
    $detail['conversations'] = self::get_client_conversations( $detail['phone'], $detail['email'] );
    $detail['tasks']         = self::get_client_tasks( $client_type, $client_id );
    $detail['appointments']  = self::get_client_appointments( $client_type, $client_id, $detail['email'] );
    $detail['hasTasksPlugin'] = class_exists( 'PN_TASKS_MANAGER_Appointment_Manager' );

    // Determine PN Tasks Manager plugin status for install/activate button.
    if ( ! $detail['hasTasksPlugin'] ) {
      if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      $installed = get_plugins();
      if ( isset( $installed['pn-tasks-manager/pn-tasks-manager.php'] ) ) {
        $detail['tasksPluginStatus'] = 'installed';
      } else {
        $detail['tasksPluginStatus'] = 'not_installed';
      }
    } else {
      $detail['tasksPluginStatus'] = 'active';
    }

    $html = self::render_detail_html( $detail );

    echo wp_json_encode( [
      'error_key'   => '',
      'html'        => $html,
      'client_type' => $client_type,
      'client_id'   => $client_id,
    ] );
    exit;
  }

  /**
   * Get conversations linked to a client by phone number.
   *
   * @param string $phone Client phone.
   * @param string $email Client email.
   * @return array
   */
  public static function get_client_conversations( $phone, $email ) {
    global $wpdb;
    $conversations = [];

    if ( empty( $phone ) ) {
      return $conversations;
    }

    // Normalize phone: strip +, spaces, dashes, parentheses — keep only digits.
    $phone_clean = preg_replace( '/[^\d]/', '', $phone );

    if ( empty( $phone_clean ) ) {
      return $conversations;
    }

    // WhatsApp conversations.
    $wa_table = $wpdb->prefix . 'pn_cm_whatsapp_conversations';
    $wa_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wa_table ) );
    if ( $wa_exists ) {
      // Match: either the stored phone contains the client digits or vice-versa.
      $like_param = '%' . $wpdb->esc_like( $phone_clean ) . '%';
      // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery -- $wa_table is safely built from $wpdb->prefix. LIKE wildcards are server-side pattern matching on the phone_number column, not user input.
      $wa_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, phone_number, contact_name, messages, status, created_at, updated_at
         FROM {$wa_table}
         WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE %s
            OR %s LIKE CONCAT('%%', REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '%%')
         ORDER BY updated_at DESC LIMIT 20",
        $like_param,
        $phone_clean
      ) );
      foreach ( $wa_rows as $row ) {
        $msgs = json_decode( $row->messages, true );
        $conversations[] = [
          'id'            => (int) $row->id,
          'platform'      => 'whatsapp',
          'phone'         => $row->phone_number,
          'contact_name'  => $row->contact_name,
          'status'        => $row->status,
          'message_count' => is_array( $msgs ) ? count( $msgs ) : 0,
          'created_at'    => $row->created_at,
          'updated_at'    => $row->updated_at,
        ];
      }
    }

    // Instagram conversations — match by contact_name if it contains the client name.
    // Instagram uses ig_user_id (not phone), so we can only match by name.
    $ig_table = $wpdb->prefix . 'pn_cm_instagram_conversations';
    $ig_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $ig_table ) );
    if ( $ig_exists && ! empty( $email ) ) {
      // Try matching by contact name that contains the email prefix (best effort).
      $email_prefix = explode( '@', $email )[0];
      if ( strlen( $email_prefix ) >= 3 ) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $ig_table is safely built from $wpdb->prefix.
        $ig_rows = $wpdb->get_results( $wpdb->prepare(
          "SELECT id, ig_user_id, contact_name, messages, status, created_at, updated_at
           FROM {$ig_table}
           WHERE contact_name LIKE %s
           ORDER BY updated_at DESC LIMIT 20",
          '%' . $wpdb->esc_like( $email_prefix ) . '%'
        ) );
        foreach ( $ig_rows as $row ) {
          $msgs = json_decode( $row->messages, true );
          $conversations[] = [
            'id'            => (int) $row->id,
            'platform'      => 'instagram',
            'phone'         => $row->ig_user_id,
            'contact_name'  => $row->contact_name,
            'status'        => $row->status,
            'message_count' => is_array( $msgs ) ? count( $msgs ) : 0,
            'created_at'    => $row->created_at,
            'updated_at'    => $row->updated_at,
          ];
        }
      }
    }

    // Sort by updated_at descending.
    usort( $conversations, function( $a, $b ) {
      return strcmp( $b['updated_at'], $a['updated_at'] );
    } );

    return array_slice( $conversations, 0, 20 );
  }

  /**
   * Get tasks linked to a client.
   *
   * @param string $client_type 'user' or 'organization'.
   * @param int    $client_id   Client ID.
   * @return array
   */
  public static function get_client_tasks( $client_type, $client_id ) {
    if ( ! post_type_exists( 'pn_tasks_task' ) ) {
      return [];
    }

    $linked_key = $client_type . '_' . $client_id;
    $posts = get_posts( [
      'post_type'      => 'pn_tasks_task',
      'posts_per_page' => 50,
      'post_status'    => [ 'publish', 'draft' ],
      'meta_key'       => 'pn_cm_linked_client',
      'meta_value'     => $linked_key,
      'orderby'        => 'meta_value',
      'order'          => 'DESC',
    ] );

    $tasks = [];
    foreach ( $posts as $post ) {
      $tasks[] = [
        'id'              => $post->ID,
        'title'           => $post->post_title,
        'date'            => get_post_meta( $post->ID, 'pn_tasks_manager_task_date', true ),
        'time'            => get_post_meta( $post->ID, 'pn_tasks_manager_task_time', true ),
        'status'          => $post->post_status === 'publish' ? 'active' : 'completed',
        'estimated_hours' => get_post_meta( $post->ID, 'pn_tasks_manager_task_estimated_hours', true ),
        'edit_url'        => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
      ];
    }

    return $tasks;
  }

  /**
   * Get appointments linked to a client.
   *
   * @param string $client_type 'user' or 'organization'.
   * @param int    $client_id   Client ID.
   * @param string $email       Client email.
   * @return array
   */
  public static function get_client_appointments( $client_type, $client_id, $email ) {
    if ( ! post_type_exists( 'pn_tasks_appointment' ) ) {
      return [];
    }

    $linked_key = $client_type . '_' . $client_id;

    // Build meta query: linked client OR matching email.
    $meta_query = [ 'relation' => 'OR' ];
    $meta_query[] = [
      'key'   => 'pn_cm_linked_client',
      'value' => $linked_key,
    ];
    if ( ! empty( $email ) ) {
      $meta_query[] = [
        'key'   => 'pn_tasks_manager_appointment_user_email',
        'value' => $email,
      ];
    }

    $posts = get_posts( [
      'post_type'      => 'pn_tasks_appointment',
      'posts_per_page' => 50,
      'post_status'    => 'publish',
      'meta_query'     => $meta_query,
    ] );

    $appointments = [];
    foreach ( $posts as $post ) {
      $start = get_post_meta( $post->ID, 'pn_tasks_manager_appointment_start_time', true );
      $end   = get_post_meta( $post->ID, 'pn_tasks_manager_appointment_end_time', true );
      $appointments[] = [
        'id'         => $post->ID,
        'title'      => $post->post_title,
        'date'       => get_post_meta( $post->ID, 'pn_tasks_manager_appointment_date', true ),
        'start_time' => $start,
        'end_time'   => $end,
        'duration'   => get_post_meta( $post->ID, 'pn_tasks_manager_appointment_duration', true ),
        'status'     => get_post_meta( $post->ID, 'pn_tasks_manager_appointment_status', true ) ?: 'pending',
        'edit_url'   => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
      ];
    }

    // Sort by date descending.
    usort( $appointments, function( $a, $b ) {
      $cmp = strcmp( $b['date'], $a['date'] );
      if ( $cmp === 0 ) {
        return strcmp( $b['start_time'], $a['start_time'] );
      }
      return $cmp;
    } );

    return $appointments;
  }

  /**
   * AJAX: Add a task linked to a client.
   */
  /**
   * AJAX: Get edit form HTML for a client.
   */
  public static function ajax_get_edit_form() {
    $client_type = isset( $_POST['client_type'] ) ? sanitize_text_field( wp_unslash( $_POST['client_type'] ) ) : '';
    $client_id   = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;

    if ( empty( $client_id ) || empty( $client_type ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Missing required data.', 'pn-customers-manager' ) ] );
      exit;
    }

    $stages = self::get_stages();

    if ( $client_type === 'organization' ) {
      $org = get_post( $client_id );
      if ( ! $org || $org->post_type !== 'pn_cm_organization' ) {
        echo wp_json_encode( [ 'error_key' => 'not_found', 'error_content' => __( 'Client not found.', 'pn-customers-manager' ) ] );
        exit;
      }
      $fields = [
        'name'        => $org->post_title,
        'email'       => get_post_meta( $client_id, 'pn_cm_organization_email', true ),
        'phone'       => get_post_meta( $client_id, 'pn_cm_organization_phone', true ),
        'company'     => get_post_meta( $client_id, 'pn_cm_organization_legal_name', true ),
        'stage'       => get_post_meta( $client_id, 'pn_cm_organization_client_stage', true ),
        'website'     => get_post_meta( $client_id, 'pn_cm_organization_website', true ),
        'address'     => get_post_meta( $client_id, 'pn_cm_organization_address', true ),
        'city'        => get_post_meta( $client_id, 'pn_cm_organization_city', true ),
        'region'      => get_post_meta( $client_id, 'pn_cm_organization_region', true ),
        'postal_code' => get_post_meta( $client_id, 'pn_cm_organization_postal_code', true ),
        'country'     => get_post_meta( $client_id, 'pn_cm_organization_country', true ),
        'cif'         => get_post_meta( $client_id, 'pn_cm_organization_cif', true ),
        'linkedin'    => get_post_meta( $client_id, 'pn_cm_organization_linkedin', true ),
      ];
    } else {
      $user = get_userdata( $client_id );
      if ( ! $user ) {
        echo wp_json_encode( [ 'error_key' => 'not_found', 'error_content' => __( 'Client not found.', 'pn-customers-manager' ) ] );
        exit;
      }
      $fields = [
        'name'        => self::get_user_display_name( $user ),
        'first_name'  => get_user_meta( $client_id, 'first_name', true ),
        'last_name'   => get_user_meta( $client_id, 'last_name', true ),
        'email'       => $user->user_email,
        'phone'       => get_user_meta( $client_id, 'phone', true ) ?: get_user_meta( $client_id, 'billing_phone', true ),
        'company'     => get_user_meta( $client_id, 'billing_company', true ),
        'stage'       => get_user_meta( $client_id, 'pn_cm_client_stage', true ),
        'ai_paused'   => get_user_meta( $client_id, 'pn_cm_ai_paused', true ),
        'website'     => $user->user_url,
        'address'     => get_user_meta( $client_id, 'billing_address_1', true ),
        'city'        => get_user_meta( $client_id, 'billing_city', true ),
        'region'      => get_user_meta( $client_id, 'billing_state', true ),
        'postal_code' => get_user_meta( $client_id, 'billing_postcode', true ),
        'country'     => get_user_meta( $client_id, 'billing_country', true ),
        'cif'         => '',
        'linkedin'    => '',
      ];
    }

    ob_start();
    ?>
    <div class="pn-cm-clients-edit-form-wrap" data-client-type="<?php echo esc_attr( $client_type ); ?>" data-client-id="<?php echo esc_attr( $client_id ); ?>">
      <h2 class="pn-cm-clients-edit-title"><span class="material-icons-outlined">edit</span> <?php echo esc_html( $fields['name'] ); ?></h2>

      <?php if ( $client_type === 'user' ) : ?>
        <div class="pn-cm-clients-edit-row">
          <div class="pn-cm-clients-edit-field">
            <label><?php esc_html_e( 'First name', 'pn-customers-manager' ); ?></label>
            <input type="text" name="first_name" value="<?php echo esc_attr( $fields['first_name'] ?? '' ); ?>" />
          </div>
          <div class="pn-cm-clients-edit-field">
            <label><?php esc_html_e( 'Last name', 'pn-customers-manager' ); ?></label>
            <input type="text" name="last_name" value="<?php echo esc_attr( $fields['last_name'] ?? '' ); ?>" />
          </div>
        </div>
      <?php else : ?>
        <div class="pn-cm-clients-edit-row">
          <div class="pn-cm-clients-edit-field pn-cm-clients-edit-field-full">
            <label><?php esc_html_e( 'Name', 'pn-customers-manager' ); ?></label>
            <input type="text" name="name" value="<?php echo esc_attr( $fields['name'] ); ?>" />
          </div>
        </div>
      <?php endif; ?>

      <div class="pn-cm-clients-edit-row">
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'Email', 'pn-customers-manager' ); ?></label>
          <input type="email" name="email" value="<?php echo esc_attr( $fields['email'] ); ?>" />
        </div>
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'Phone', 'pn-customers-manager' ); ?></label>
          <input type="text" name="phone" value="<?php echo esc_attr( $fields['phone'] ); ?>" />
        </div>
      </div>

      <div class="pn-cm-clients-edit-row">
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'Company', 'pn-customers-manager' ); ?></label>
          <input type="text" name="company" value="<?php echo esc_attr( $fields['company'] ); ?>" />
        </div>
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'Stage', 'pn-customers-manager' ); ?></label>
          <select name="stage">
            <?php foreach ( $stages as $stage ) : ?>
              <option value="<?php echo esc_attr( $stage ); ?>" <?php selected( $fields['stage'], $stage ); ?>><?php echo esc_html( $stage ); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <?php if ( $client_type === 'user' ) : ?>
        <div class="pn-cm-clients-edit-row">
          <div class="pn-cm-clients-edit-field">
            <label><?php esc_html_e( 'AI Paused', 'pn-customers-manager' ); ?></label>
            <label class="pn-customers-manager-switch">
              <input type="checkbox" name="ai_paused" value="on" <?php checked( $fields['ai_paused'] ?? '', 'on' ); ?> />
              <span class="pn-customers-manager-slider pn-customers-manager-round"></span>
            </label>
          </div>
          <div class="pn-cm-clients-edit-field"></div>
        </div>
      <?php endif; ?>

      <div class="pn-cm-clients-edit-row">
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'Website', 'pn-customers-manager' ); ?></label>
          <input type="url" name="website" value="<?php echo esc_attr( $fields['website'] ); ?>" />
        </div>
        <?php if ( $client_type === 'organization' ) : ?>
          <div class="pn-cm-clients-edit-field">
            <label><?php esc_html_e( 'LinkedIn', 'pn-customers-manager' ); ?></label>
            <input type="url" name="linkedin" value="<?php echo esc_attr( $fields['linkedin'] ); ?>" />
          </div>
        <?php else : ?>
          <div class="pn-cm-clients-edit-field"></div>
        <?php endif; ?>
      </div>

      <?php if ( $client_type === 'organization' ) : ?>
        <div class="pn-cm-clients-edit-row">
          <div class="pn-cm-clients-edit-field">
            <label><?php esc_html_e( 'Tax ID', 'pn-customers-manager' ); ?></label>
            <input type="text" name="cif" value="<?php echo esc_attr( $fields['cif'] ); ?>" />
          </div>
          <div class="pn-cm-clients-edit-field"></div>
        </div>
      <?php endif; ?>

      <h3 class="pn-cm-clients-edit-section-title"><span class="material-icons-outlined">location_on</span> <?php esc_html_e( 'Address', 'pn-customers-manager' ); ?></h3>

      <div class="pn-cm-clients-edit-row">
        <div class="pn-cm-clients-edit-field pn-cm-clients-edit-field-full">
          <label><?php esc_html_e( 'Street address', 'pn-customers-manager' ); ?></label>
          <input type="text" name="address" value="<?php echo esc_attr( $fields['address'] ); ?>" />
        </div>
      </div>

      <div class="pn-cm-clients-edit-row">
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'City', 'pn-customers-manager' ); ?></label>
          <input type="text" name="city" value="<?php echo esc_attr( $fields['city'] ); ?>" />
        </div>
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'Region / State', 'pn-customers-manager' ); ?></label>
          <input type="text" name="region" value="<?php echo esc_attr( $fields['region'] ); ?>" />
        </div>
      </div>

      <div class="pn-cm-clients-edit-row">
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'Postal code', 'pn-customers-manager' ); ?></label>
          <input type="text" name="postal_code" value="<?php echo esc_attr( $fields['postal_code'] ); ?>" />
        </div>
        <div class="pn-cm-clients-edit-field">
          <label><?php esc_html_e( 'Country (ISO code)', 'pn-customers-manager' ); ?></label>
          <input type="text" name="country" value="<?php echo esc_attr( $fields['country'] ); ?>" maxlength="2" style="text-transform:uppercase;" />
        </div>
      </div>

      <div class="pn-cm-clients-edit-actions">
        <button type="button" class="pn-cm-clients-btn-primary pn-cm-clients-edit-save"><?php esc_html_e( 'Save', 'pn-customers-manager' ); ?></button>
        <button type="button" class="pn-cm-clients-edit-cancel"><?php esc_html_e( 'Cancel', 'pn-customers-manager' ); ?></button>
      </div>
    </div>
    <?php
    $html = ob_get_clean();

    echo wp_json_encode( [ 'error_key' => '', 'html' => $html ] );
    exit;
  }

  /**
   * AJAX: Save client edits.
   */
  public static function ajax_save_client() {
    $client_type = isset( $_POST['client_type'] ) ? sanitize_text_field( wp_unslash( $_POST['client_type'] ) ) : '';
    $client_id   = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;

    if ( empty( $client_id ) || empty( $client_type ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Missing required data.', 'pn-customers-manager' ) ] );
      exit;
    }

    $s = function( $key ) {
      return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
    };

    if ( $client_type === 'organization' ) {
      $org = get_post( $client_id );
      if ( ! $org || $org->post_type !== 'pn_cm_organization' ) {
        echo wp_json_encode( [ 'error_key' => 'not_found', 'error_content' => __( 'Client not found.', 'pn-customers-manager' ) ] );
        exit;
      }

      $name = $s( 'name' );
      if ( $name ) {
        wp_update_post( [ 'ID' => $client_id, 'post_title' => $name ] );
      }

      $meta_map = [
        'email'       => 'pn_cm_organization_email',
        'phone'       => 'pn_cm_organization_phone',
        'company'     => 'pn_cm_organization_legal_name',
        'stage'       => 'pn_cm_organization_client_stage',
        'website'     => 'pn_cm_organization_website',
        'address'     => 'pn_cm_organization_address',
        'city'        => 'pn_cm_organization_city',
        'region'      => 'pn_cm_organization_region',
        'postal_code' => 'pn_cm_organization_postal_code',
        'country'     => 'pn_cm_organization_country',
        'cif'         => 'pn_cm_organization_cif',
        'linkedin'    => 'pn_cm_organization_linkedin',
      ];

      foreach ( $meta_map as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
          update_post_meta( $client_id, $meta_key, $s( $field ) );
        }
      }
    } else {
      $user = get_userdata( $client_id );
      if ( ! $user ) {
        echo wp_json_encode( [ 'error_key' => 'not_found', 'error_content' => __( 'Client not found.', 'pn-customers-manager' ) ] );
        exit;
      }

      $user_data = [ 'ID' => $client_id ];
      $first = $s( 'first_name' );
      $last  = $s( 'last_name' );
      if ( $first !== '' || $last !== '' ) {
        $user_data['first_name']   = $first;
        $user_data['last_name']    = $last;
        $user_data['display_name'] = trim( $first . ' ' . $last );
      }
      $email = $s( 'email' );
      if ( $email && is_email( $email ) ) {
        $user_data['user_email'] = $email;
      }
      $website = $s( 'website' );
      $user_data['user_url'] = $website;

      wp_update_user( $user_data );

      $phone = $s( 'phone' );
      update_user_meta( $client_id, 'phone', $phone );
      update_user_meta( $client_id, 'billing_phone', $phone );

      $company = $s( 'company' );
      update_user_meta( $client_id, 'billing_company', $company );

      $stage = $s( 'stage' );
      if ( $stage ) {
        update_user_meta( $client_id, 'pn_cm_client_stage', $stage );
      }

      $ai_paused = $s( 'ai_paused' );
      update_user_meta( $client_id, 'pn_cm_ai_paused', $ai_paused === 'on' ? 'on' : '' );

      $billing_map = [
        'address'     => 'billing_address_1',
        'city'        => 'billing_city',
        'region'      => 'billing_state',
        'postal_code' => 'billing_postcode',
        'country'     => 'billing_country',
      ];

      foreach ( $billing_map as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
          update_user_meta( $client_id, $meta_key, $s( $field ) );
        }
      }
    }

    echo wp_json_encode( [ 'error_key' => '', 'message' => __( 'Client saved.', 'pn-customers-manager' ) ] );
    exit;
  }

  public static function ajax_add_task() {
    $client_type = isset( $_POST['client_type'] ) ? sanitize_text_field( wp_unslash( $_POST['client_type'] ) ) : '';
    $client_id   = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
    $title       = isset( $_POST['task_title'] ) ? sanitize_text_field( wp_unslash( $_POST['task_title'] ) ) : '';
    $date        = isset( $_POST['task_date'] ) ? sanitize_text_field( wp_unslash( $_POST['task_date'] ) ) : '';
    $time        = isset( $_POST['task_time'] ) ? sanitize_text_field( wp_unslash( $_POST['task_time'] ) ) : '';
    $description = isset( $_POST['task_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['task_description'] ) ) : '';

    if ( empty( $title ) || empty( $client_id ) || empty( $client_type ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Title is required.', 'pn-customers-manager' ) ] );
      exit;
    }

    if ( ! post_type_exists( 'pn_tasks_task' ) ) {
      echo wp_json_encode( [ 'error_key' => 'no_plugin', 'error_content' => __( 'PN Tasks Manager is not active.', 'pn-customers-manager' ) ] );
      exit;
    }

    $post_id = wp_insert_post( [
      'post_type'    => 'pn_tasks_task',
      'post_title'   => $title,
      'post_content' => $description,
      'post_status'  => 'publish',
    ] );

    if ( is_wp_error( $post_id ) ) {
      echo wp_json_encode( [ 'error_key' => 'create_failed', 'error_content' => $post_id->get_error_message() ] );
      exit;
    }

    $linked_key = $client_type . '_' . $client_id;
    update_post_meta( $post_id, 'pn_cm_linked_client', $linked_key );
    if ( ! empty( $date ) ) {
      update_post_meta( $post_id, 'pn_tasks_manager_task_date', $date );
    }
    if ( ! empty( $time ) ) {
      update_post_meta( $post_id, 'pn_tasks_manager_task_time', $time );
    }

    $task = [
      'id'     => $post_id,
      'title'  => $title,
      'date'   => $date,
      'time'   => $time,
      'status' => 'active',
      'edit_url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
    ];

    echo wp_json_encode( [
      'error_key' => '',
      'task_html' => self::render_task_item_html( $task ),
    ] );
    exit;
  }

  /**
   * AJAX: Add an appointment linked to a client.
   */
  public static function ajax_add_appointment() {
    $client_type = isset( $_POST['client_type'] ) ? sanitize_text_field( wp_unslash( $_POST['client_type'] ) ) : '';
    $client_id   = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
    $date        = isset( $_POST['appointment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['appointment_date'] ) ) : '';
    $start_time  = isset( $_POST['appointment_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['appointment_start_time'] ) ) : '';
    $end_time    = isset( $_POST['appointment_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['appointment_end_time'] ) ) : '';

    if ( empty( $date ) || empty( $start_time ) || empty( $end_time ) || empty( $client_id ) || empty( $client_type ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Date and times are required.', 'pn-customers-manager' ) ] );
      exit;
    }

    if ( ! class_exists( 'PN_TASKS_MANAGER_Appointment_Manager' ) ) {
      echo wp_json_encode( [ 'error_key' => 'no_plugin', 'error_content' => __( 'PN Tasks Manager is not active.', 'pn-customers-manager' ) ] );
      exit;
    }

    // Get client info for auto-fill.
    $user_name  = '';
    $user_email = '';
    $user_phone = '';

    if ( $client_type === 'organization' ) {
      $org = get_post( $client_id );
      $user_name  = $org ? $org->post_title : '';
      $user_email = get_post_meta( $client_id, 'pn_cm_organization_email', true );
      $user_phone = get_post_meta( $client_id, 'pn_cm_organization_phone', true );
    } else {
      $user = get_userdata( $client_id );
      if ( $user ) {
        $user_name  = self::get_user_display_name( $user );
        $user_email = $user->user_email;
        $user_phone = get_user_meta( $client_id, 'phone', true ) ?: get_user_meta( $client_id, 'billing_phone', true );
      }
    }

    $appointment_id = PN_TASKS_MANAGER_Appointment_Manager::pn_tasks_manager_create_appointment( [
      'date'       => $date,
      'start_time' => $start_time,
      'end_time'   => $end_time,
      'user_name'  => $user_name,
      'user_email' => $user_email,
      'user_phone' => $user_phone,
      'description' => '',
    ] );

    if ( is_wp_error( $appointment_id ) ) {
      echo wp_json_encode( [ 'error_key' => 'create_failed', 'error_content' => $appointment_id->get_error_message() ] );
      exit;
    }

    // Link to client.
    $linked_key = $client_type . '_' . $client_id;
    update_post_meta( $appointment_id, 'pn_cm_linked_client', $linked_key );

    $appt_post = get_post( $appointment_id );
    $appt = [
      'id'         => $appointment_id,
      'title'      => $appt_post ? $appt_post->post_title : '',
      'date'       => $date,
      'start_time' => $start_time,
      'end_time'   => $end_time,
      'duration'   => get_post_meta( $appointment_id, 'pn_tasks_manager_appointment_duration', true ),
      'status'     => get_post_meta( $appointment_id, 'pn_tasks_manager_appointment_status', true ) ?: 'confirmed',
      'edit_url'   => admin_url( 'post.php?post=' . $appointment_id . '&action=edit' ),
    ];

    echo wp_json_encode( [
      'error_key'        => '',
      'appointment_html' => self::render_appointment_item_html( $appt ),
    ] );
    exit;
  }

  /**
   * AJAX: Complete (toggle) a task.
   */
  public static function ajax_complete_task() {
    $task_id = isset( $_POST['task_id'] ) ? absint( $_POST['task_id'] ) : 0;

    if ( empty( $task_id ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Missing task ID.', 'pn-customers-manager' ) ] );
      exit;
    }

    $post = get_post( $task_id );
    if ( ! $post || $post->post_type !== 'pn_tasks_task' ) {
      echo wp_json_encode( [ 'error_key' => 'not_found', 'error_content' => __( 'Task not found.', 'pn-customers-manager' ) ] );
      exit;
    }

    $new_status = $post->post_status === 'publish' ? 'draft' : 'publish';
    wp_update_post( [ 'ID' => $task_id, 'post_status' => $new_status ] );

    echo wp_json_encode( [
      'error_key' => '',
      'status'    => $new_status === 'publish' ? 'active' : 'completed',
    ] );
    exit;
  }

  /**
   * AJAX: Get conversation messages as rendered HTML.
   */
  public static function ajax_get_conversation_messages() {
    global $wpdb;
    $conv_id  = isset( $_POST['conv_id'] ) ? absint( $_POST['conv_id'] ) : 0;
    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( empty( $conv_id ) || empty( $platform ) ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Missing required data.', 'pn-customers-manager' ) ] );
      exit;
    }

    $table = $platform === 'instagram'
      ? $wpdb->prefix . 'pn_cm_instagram_conversations'
      : $wpdb->prefix . 'pn_cm_whatsapp_conversations';

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT messages FROM {$table} WHERE id = %d", $conv_id ) );
    if ( ! $row ) {
      echo wp_json_encode( [ 'error_key' => 'not_found', 'error_content' => __( 'Conversation not found.', 'pn-customers-manager' ) ] );
      exit;
    }

    $messages = json_decode( $row->messages, true );
    if ( ! is_array( $messages ) ) {
      $messages = [];
    }

    echo wp_json_encode( [
      'error_key' => '',
      'html'      => self::render_messages_html( $messages, $platform ),
    ] );
    exit;
  }

  // ── Gutenberg Block ──────────────────────────────────────────────

  /**
   * Register the Gutenberg block.
   */
  public static function register_block() {
    if ( ! function_exists( 'register_block_type' ) ) {
      return;
    }

    wp_register_script(
      'pn-cm-clients-block',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-clients.js',
      [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );

    register_block_type( 'pn-customers-manager/clients-list', [
      'editor_script'   => 'pn-cm-clients-block',
      'render_callback' => [ __CLASS__, 'render_clients_block' ],
      'attributes'      => [
        'showSearch'      => [ 'type' => 'boolean', 'default' => true ],
        'showStageFilter' => [ 'type' => 'boolean', 'default' => true ],
        'viewMode'        => [ 'type' => 'string', 'default' => 'grid' ],
        'perPage'         => [ 'type' => 'number', 'default' => 20 ],
        'showFlags'       => [ 'type' => 'boolean', 'default' => true ],
      ],
    ] );
  }

  /**
   * Shortcode handler.
   */
  public static function shortcode_handler( $atts ) {
    $atts = shortcode_atts( [
      'view'    => 'grid',
      'search'  => 'true',
      'filter'  => 'true',
      'perpage' => 20,
      'flags'   => 'true',
    ], $atts );

    return self::render_clients_block( [
      'showSearch'      => $atts['search'] === 'true',
      'showStageFilter' => $atts['filter'] === 'true',
      'viewMode'        => $atts['view'],
      'perPage'         => intval( $atts['perpage'] ),
      'showFlags'       => $atts['flags'] === 'true',
    ] );
  }

  /**
   * Server-side render for the Gutenberg block / shortcode.
   */
  public static function render_clients_block( $attributes ) {
    if ( ! is_user_logged_in() || ( ! current_user_can( 'pn_cm_manage_crm' ) && ! current_user_can( 'manage_options' ) ) ) {
      return '<p>' . esc_html__( 'You do not have permission to view this content.', 'pn-customers-manager' ) . '</p>';
    }

    $show_search = isset( $attributes['showSearch'] ) ? $attributes['showSearch'] : true;
    $show_filter = isset( $attributes['showStageFilter'] ) ? $attributes['showStageFilter'] : true;
    $view_mode   = isset( $attributes['viewMode'] ) ? $attributes['viewMode'] : 'grid';
    $per_page    = isset( $attributes['perPage'] ) ? intval( $attributes['perPage'] ) : 20;
    $show_flags  = isset( $attributes['showFlags'] ) ? $attributes['showFlags'] : true;

    $stages        = self::get_stages();
    $stages_config = self::get_stages_config();
    $stage_counts  = self::get_stage_counts();
    $result        = self::get_clients( [ 'per_page' => $per_page ] );
    $nonce         = wp_create_nonce( 'pn-customers-manager-nonce' );

    wp_enqueue_style( 'pn-customers-manager', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_style( 'pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_style( 'pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-tooltips.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_style( 'pn-customers-manager-clients', PN_CUSTOMERS_MANAGER_URL . 'assets/css/admin/pn-customers-manager-clients.css', [], PN_CUSTOMERS_MANAGER_VERSION );
    wp_enqueue_script( 'pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', [ 'jquery' ], PN_CUSTOMERS_MANAGER_VERSION, true );
    wp_enqueue_script( 'pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-tooltips.js', [ 'jquery' ], PN_CUSTOMERS_MANAGER_VERSION, true );
    wp_enqueue_script( 'pn-customers-manager-clients', PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-clients.js', [ 'jquery', 'pn-customers-manager-popups', 'pn-customers-manager-tooltips' ], PN_CUSTOMERS_MANAGER_VERSION, true );
    wp_localize_script( 'pn-customers-manager-clients', 'pnCmClients', [
      'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
      'nonce'        => $nonce,
      'stages'       => $stages,
      'i18n'    => [
        'loading'      => __( 'Loading...', 'pn-customers-manager' ),
        'error'        => __( 'Error', 'pn-customers-manager' ),
        'name'         => __( 'Name', 'pn-customers-manager' ),
        'email'        => __( 'Email', 'pn-customers-manager' ),
        'phone'        => __( 'Phone', 'pn-customers-manager' ),
        'stage'        => __( 'Stage', 'pn-customers-manager' ),
        'save'         => __( 'Save', 'pn-customers-manager' ),
        'cancel'       => __( 'Cancel', 'pn-customers-manager' ),
        'active'       => __( 'Active', 'pn-customers-manager' ),
        'completed'    => __( 'Completed', 'pn-customers-manager' ),
        'orderAsc'     => __( 'Ascending', 'pn-customers-manager' ),
        'orderDesc'    => __( 'Descending', 'pn-customers-manager' ),
        'install'      => __( 'Install', 'pn-customers-manager' ),
        'installing'   => __( 'Installing...', 'pn-customers-manager' ),
        'activate'     => __( 'Activate', 'pn-customers-manager' ),
        'activating'   => __( 'Activating...', 'pn-customers-manager' ),
        'installError' => __( 'Error installing plugin.', 'pn-customers-manager' ),
        'activateError' => __( 'Error activating plugin.', 'pn-customers-manager' ),
        'duplicates'       => __( 'Duplicates', 'pn-customers-manager' ),
        'merge'            => __( 'Merge', 'pn-customers-manager' ),
        'mergeAll'         => __( 'Fix all', 'pn-customers-manager' ),
        'merging'          => __( 'Merging...', 'pn-customers-manager' ),
        'merged'           => __( 'Merged successfully.', 'pn-customers-manager' ),
        'primary'          => __( 'Primary', 'pn-customers-manager' ),
        'confirmMerge'     => __( 'Merge this group? Duplicate records will be deleted.', 'pn-customers-manager' ),
        'confirmMergeAll'  => __( 'Merge ALL duplicate groups? This cannot be undone.', 'pn-customers-manager' ),
        'noDuplicates'     => __( 'No duplicates found.', 'pn-customers-manager' ),
      ],
      'showFlags'         => $show_flags,
    ] );

    ob_start();
    ?>
    <div class="pn-cm-clients-wrap pn-cm-clients-block" data-show-flags="<?php echo $show_flags ? '1' : '0'; ?>">
      <?php if ( $show_filter ) : ?>
        <div class="pn-customers-manager-tabs pn-cm-clients-stages-bar">
          <button type="button" class="pn-customers-manager-tab-links pn-cm-clients-stage-tab active" data-stage="">
            <?php esc_html_e( 'All', 'pn-customers-manager' ); ?>
            <span class="pn-cm-clients-stage-count"><?php echo esc_html( array_sum( $stage_counts ) ); ?></span>
          </button>
          <?php foreach ( $stages_config as $sc ) :
            $sc_name  = $sc['name'];
            $sc_color = $sc['color'];
            $sc_desc  = isset( $sc['description'] ) ? $sc['description'] : '';
            $sc_count = isset( $stage_counts[ $sc_name ] ) ? $stage_counts[ $sc_name ] : 0;
          ?>
            <button type="button" class="pn-customers-manager-tab-links pn-cm-clients-stage-tab<?php echo ! empty( $sc_desc ) ? ' pn-customers-manager-tooltip' : ''; ?>" data-stage="<?php echo esc_attr( $sc_name ); ?>" style="--stage-color:<?php echo esc_attr( $sc_color ); ?>;"<?php if ( ! empty( $sc_desc ) ) : ?> data-pn-customers-manager-tooltip="<?php echo esc_attr( $sc_desc ); ?>"<?php endif; ?>>
              <span class="pn-cm-clients-stage-indicator" style="background:<?php echo esc_attr( $sc_color ); ?>;"></span>
              <?php echo esc_html( $sc_name ); ?>
              <span class="pn-cm-clients-stage-count"><?php echo esc_html( $sc_count ); ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="pn-cm-clients-topbar">
        <div class="pn-cm-clients-topbar-left">
          <button type="button" id="pn-cm-clients-sort-toggle" class="pn-cm-clients-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e( 'Sort', 'pn-customers-manager' ); ?>">
            <span class="material-icons-outlined">sort</span>
          </button>
          <div id="pn-cm-clients-sort-panel" class="pn-cm-clients-dropdown-panel" style="display:none;">
            <div class="pn-cm-clients-panel-header">
              <span><?php esc_html_e( 'Sort by', 'pn-customers-manager' ); ?></span>
            </div>
            <div class="pn-cm-clients-panel-body">
              <select id="pn-cm-clients-orderby" class="pn-cm-clients-panel-select">
                <option value="date" selected><?php esc_html_e( 'Registration date', 'pn-customers-manager' ); ?></option>
                <option value="name"><?php esc_html_e( 'Name', 'pn-customers-manager' ); ?></option>
                <option value="email"><?php esc_html_e( 'Email', 'pn-customers-manager' ); ?></option>
                <option value="company"><?php esc_html_e( 'Company', 'pn-customers-manager' ); ?></option>
                <option value="source"><?php esc_html_e( 'Source', 'pn-customers-manager' ); ?></option>
              </select>
              <button type="button" id="pn-cm-clients-order-toggle" class="pn-cm-clients-order-toggle pn-customers-manager-tooltip" data-order="DESC" title="<?php esc_attr_e( 'Descending', 'pn-customers-manager' ); ?>">
                <span class="material-icons-outlined">arrow_downward</span>
                <span class="pn-cm-clients-order-label"><?php esc_html_e( 'Descending', 'pn-customers-manager' ); ?></span>
              </button>
            </div>
          </div>

          <?php if ( $show_search || $show_filter ) : ?>
          <button type="button" id="pn-cm-clients-filter-toggle" class="pn-cm-clients-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e( 'Filter', 'pn-customers-manager' ); ?>">
            <span class="material-icons-outlined">filter_list</span>
          </button>
          <div id="pn-cm-clients-filter-panel" class="pn-cm-clients-dropdown-panel" style="display:none;">
            <div class="pn-cm-clients-panel-header">
              <span><?php esc_html_e( 'Filter clients', 'pn-customers-manager' ); ?></span>
            </div>
            <div class="pn-cm-clients-panel-body">
              <?php if ( $show_search ) : ?>
              <input type="text" id="pn-cm-clients-search" class="pn-cm-clients-panel-input" placeholder="<?php esc_attr_e( 'Search by name, email...', 'pn-customers-manager' ); ?>" />
              <?php endif; ?>
              <?php if ( $show_filter ) : ?>
              <label class="pn-cm-clients-panel-label"><?php esc_html_e( 'Source', 'pn-customers-manager' ); ?></label>
              <select id="pn-cm-clients-source-filter" class="pn-cm-clients-panel-select">
                <option value=""><?php esc_html_e( 'All sources', 'pn-customers-manager' ); ?></option>
                <option value="manual"><?php esc_html_e( 'Manual', 'pn-customers-manager' ); ?></option>
                <option value="ghl">GHL</option>
                <option value="zoho">Zoho</option>
              </select>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <button type="button" id="pn-cm-clients-dup-toggle" class="pn-cm-clients-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e( 'Duplicates', 'pn-customers-manager' ); ?>" style="display:none;">
            <span class="material-icons-outlined">people</span>
            <span class="pn-cm-dup-badge" id="pn-cm-dup-badge">0</span>
          </button>
        </div>
        <div class="pn-cm-clients-topbar-right">
          <select id="pn-cm-clients-per-page" class="pn-cm-clients-per-page-select">
            <option value="10"<?php selected( $per_page, 10 ); ?>>10</option>
            <option value="20"<?php selected( $per_page, 20 ); ?>>20</option>
            <option value="50"<?php selected( $per_page, 50 ); ?>>50</option>
            <option value="100"<?php selected( $per_page, 100 ); ?>>100</option>
          </select>
          <span class="pn-cm-clients-toolbar-label"><?php esc_html_e( 'per page', 'pn-customers-manager' ); ?></span>

          <div class="pn-cm-clients-view-toggle">
            <button type="button" class="pn-cm-clients-view-btn<?php echo $view_mode !== 'list' ? ' active' : ''; ?> pn-customers-manager-tooltip" data-view="grid" title="<?php esc_attr_e( 'Grid view', 'pn-customers-manager' ); ?>">
              <span class="material-icons-outlined">grid_view</span>
            </button>
            <button type="button" class="pn-cm-clients-view-btn<?php echo $view_mode === 'list' ? ' active' : ''; ?> pn-customers-manager-tooltip" data-view="list" title="<?php esc_attr_e( 'List view', 'pn-customers-manager' ); ?>">
              <span class="material-icons-outlined">view_list</span>
            </button>
          </div>
        </div>
      </div>

      <div id="pn-cm-clients-content" class="pn-cm-clients-content <?php echo $view_mode === 'list' ? 'pn-cm-clients-list' : 'pn-cm-clients-grid'; ?>">
        <!-- Loaded via AJAX -->
      </div>

      <div id="pn-cm-clients-pagination" class="pn-cm-clients-pagination"></div>
    </div>

    <div class="pn-customers-manager-popup-overlay pn-customers-manager-display-none-soft" style="display:none;"></div>
    <div id="pn-cm-clients-detail-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" style="display:none;">
      <div class="pn-customers-manager-popup-content">
        <div id="pn-cm-clients-detail-body"></div>
      </div>
    </div>

    <div id="pn-cm-clients-edit-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" style="display:none;">
      <div class="pn-customers-manager-popup-content">
        <div id="pn-cm-clients-edit-body"></div>
      </div>
    </div>

    <div id="pn-cm-clients-dup-popup" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" style="display:none;">
      <div class="pn-customers-manager-popup-content">
        <div id="pn-cm-clients-dup-body"></div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /* ──────────────────────────────────────────────────────────────────
   * Duplicate detection & merge
   * ────────────────────────────────────────────────────────────────── */

  /**
   * Normalize a phone number to digits only for comparison.
   *
   * @param string $phone Raw phone.
   * @return string Digits only (empty string if nothing useful).
   */
  public static function normalize_phone( $phone ) {
    $digits = preg_replace( '/[^0-9]/', '', $phone );
    return strlen( $digits ) >= 6 ? $digits : '';
  }

  /**
   * Find duplicate groups among clients using union-find on email & phone.
   *
   * @return array Array of groups. Each group is an array of client arrays (same shape as get_clients output).
   */
  public static function get_duplicate_groups() {
    // Fetch ALL clients without pagination.
    $all = self::get_clients( [ 'per_page' => 999999, 'page' => 1 ] );
    $clients = $all['clients'];

    $n = count( $clients );
    if ( $n < 2 ) {
      return [];
    }

    // Union-find arrays.
    $parent = range( 0, $n - 1 );
    $rank   = array_fill( 0, $n, 0 );

    $find = function ( $i ) use ( &$parent, &$find ) {
      if ( $parent[ $i ] !== $i ) {
        $parent[ $i ] = $find( $parent[ $i ] );
      }
      return $parent[ $i ];
    };

    $union = function ( $a, $b ) use ( &$parent, &$rank, $find ) {
      $ra = $find( $a );
      $rb = $find( $b );
      if ( $ra === $rb ) return;
      if ( $rank[ $ra ] < $rank[ $rb ] ) { $parent[ $ra ] = $rb; }
      elseif ( $rank[ $ra ] > $rank[ $rb ] ) { $parent[ $rb ] = $ra; }
      else { $parent[ $rb ] = $ra; $rank[ $ra ]++; }
    };

    // Build index maps.
    $email_map = [];
    $phone_map = [];

    for ( $i = 0; $i < $n; $i++ ) {
      $em = strtolower( trim( $clients[ $i ]['email'] ?? '' ) );
      if ( $em !== '' ) {
        if ( isset( $email_map[ $em ] ) ) {
          $union( $email_map[ $em ], $i );
        } else {
          $email_map[ $em ] = $i;
        }
      }

      $ph = self::normalize_phone( $clients[ $i ]['phone'] ?? '' );
      if ( $ph !== '' ) {
        if ( isset( $phone_map[ $ph ] ) ) {
          $union( $phone_map[ $ph ], $i );
        } else {
          $phone_map[ $ph ] = $i;
        }
        // Also match by last 9 digits to handle country prefix differences
        // e.g. +34612345678 vs 612345678.
        if ( strlen( $ph ) >= 9 ) {
          $ph_suffix = substr( $ph, -9 );
          if ( isset( $phone_map[ 's_' . $ph_suffix ] ) ) {
            $union( $phone_map[ 's_' . $ph_suffix ], $i );
          } else {
            $phone_map[ 's_' . $ph_suffix ] = $i;
          }
        }
      }
    }

    // Collect groups.
    $groups_map = [];
    for ( $i = 0; $i < $n; $i++ ) {
      $root = $find( $i );
      $groups_map[ $root ][] = $clients[ $i ];
    }

    // Only keep groups with 2+ members, sorted by earliest date.
    $groups = [];
    foreach ( $groups_map as $members ) {
      if ( count( $members ) < 2 ) continue;
      usort( $members, function ( $a, $b ) {
        return strcmp( $a['date'], $b['date'] );
      } );
      $groups[] = $members;
    }

    return $groups;
  }

  /**
   * Choose the primary (keeper) from a duplicate group.
   * Prefers users over organizations, then most-filled fields, then oldest.
   *
   * @param array $group Array of client arrays.
   * @return int Index within $group of the primary.
   */
  public static function choose_primary( $group ) {
    $best = 0;
    $best_score = -1;

    foreach ( $group as $i => $c ) {
      $score = 0;
      // Prefer users.
      if ( $c['type'] === 'user' ) $score += 1000;
      // Count non-empty fields.
      foreach ( [ 'name', 'email', 'phone', 'company', 'stage', 'source' ] as $f ) {
        if ( ! empty( $c[ $f ] ) ) $score++;
      }
      // Tie-break: older is better (lower date string = smaller).
      if ( $score > $best_score || ( $score === $best_score && strcmp( $c['date'], $group[ $best ]['date'] ) < 0 ) ) {
        $best_score = $score;
        $best       = $i;
      }
    }

    return $best;
  }

  /**
   * Merge a duplicate group: copy missing data to primary, delete others.
   *
   * @param array $group Array of client arrays.
   * @return array [ 'primary' => client, 'deleted' => int count ]
   */
  public static function merge_group( $group ) {
    $pi      = self::choose_primary( $group );
    $primary = $group[ $pi ];
    $deleted = 0;

    // Org-to-user meta mapping.
    $org_to_user = [
      'pn_cm_organization_phone'      => 'phone',
      'pn_cm_organization_email'      => false, // user email is on the user object.
      'pn_cm_organization_legal_name' => 'billing_company',
      'pn_cm_organization_address'    => 'billing_address_1',
      'pn_cm_organization_city'       => 'billing_city',
      'pn_cm_organization_region'     => 'billing_state',
      'pn_cm_organization_postal_code'=> 'billing_postcode',
      'pn_cm_organization_country'    => 'billing_country',
      'pn_cm_organization_website'    => 'url',
      'pn_cm_organization_first_name' => 'first_name',
      'pn_cm_organization_last_name'  => 'last_name',
    ];

    // Org meta map (for org-to-org merges).
    $org_meta_keys = [
      'pn_cm_organization_email',
      'pn_cm_organization_phone',
      'pn_cm_organization_legal_name',
      'pn_cm_organization_address',
      'pn_cm_organization_city',
      'pn_cm_organization_region',
      'pn_cm_organization_postal_code',
      'pn_cm_organization_country',
      'pn_cm_organization_website',
      'pn_cm_organization_cif',
      'pn_cm_organization_linkedin',
      'pn_cm_organization_first_name',
      'pn_cm_organization_last_name',
    ];

    // User meta keys to copy.
    $user_meta_keys = [
      'phone', 'billing_phone', 'first_name', 'last_name',
      'billing_company', 'billing_address_1', 'billing_city',
      'billing_state', 'billing_postcode', 'billing_country',
    ];

    foreach ( $group as $i => $dup ) {
      if ( $i === $pi ) continue;

      // Copy fields from duplicate to primary.
      if ( $primary['type'] === 'user' && $dup['type'] === 'user' ) {
        // User-to-user: copy empty user meta.
        foreach ( $user_meta_keys as $mk ) {
          $existing = get_user_meta( $primary['id'], $mk, true );
          if ( empty( $existing ) ) {
            $val = get_user_meta( $dup['id'], $mk, true );
            if ( ! empty( $val ) ) {
              update_user_meta( $primary['id'], $mk, $val );
            }
          }
        }
      } elseif ( $primary['type'] === 'user' && $dup['type'] === 'organization' ) {
        // Org-to-user: map org postmeta to user meta.
        foreach ( $org_to_user as $org_key => $user_key ) {
          if ( $user_key === false ) continue;
          $existing = get_user_meta( $primary['id'], $user_key, true );
          if ( empty( $existing ) ) {
            $val = get_post_meta( $dup['id'], $org_key, true );
            if ( ! empty( $val ) ) {
              update_user_meta( $primary['id'], $user_key, $val );
            }
          }
        }
      } elseif ( $primary['type'] === 'organization' && $dup['type'] === 'organization' ) {
        // Org-to-org: copy empty postmeta.
        foreach ( $org_meta_keys as $mk ) {
          $existing = get_post_meta( $primary['id'], $mk, true );
          if ( empty( $existing ) ) {
            $val = get_post_meta( $dup['id'], $mk, true );
            if ( ! empty( $val ) ) {
              update_post_meta( $primary['id'], $mk, $val );
            }
          }
        }
      } elseif ( $primary['type'] === 'organization' && $dup['type'] === 'user' ) {
        // User-to-org: map user meta to org postmeta.
        foreach ( $org_to_user as $org_key => $user_key ) {
          if ( $user_key === false ) continue;
          $existing = get_post_meta( $primary['id'], $org_key, true );
          if ( empty( $existing ) ) {
            $val = get_user_meta( $dup['id'], $user_key, true );
            if ( ! empty( $val ) ) {
              update_post_meta( $primary['id'], $org_key, $val );
            }
          }
        }
      }

      // Delete the duplicate.
      if ( $dup['type'] === 'user' ) {
        // Safety: never delete admin users.
        $user_obj = get_userdata( $dup['id'] );
        if ( $user_obj && ! in_array( 'administrator', $user_obj->roles, true ) ) {
          wp_delete_user( $dup['id'] );
          $deleted++;
        }
      } else {
        wp_delete_post( $dup['id'], true );
        $deleted++;
      }
    }

    // Activity log.
    if ( class_exists( 'PN_CUSTOMERS_MANAGER_Activity_Log' ) ) {
      PN_CUSTOMERS_MANAGER_Activity_Log::log(
        'delete',
        'client',
        $primary['id'],
        $primary['name'],
        sprintf(
          /* translators: %1$d: number of duplicates deleted, %2$s: primary client name */
          __( 'Merged %1$d duplicate(s) into "%2$s".', 'pn-customers-manager' ),
          $deleted,
          $primary['name']
        )
      );
    }

    return [ 'primary' => $primary, 'deleted' => $deleted ];
  }

  /**
   * AJAX: Return duplicate groups HTML + count.
   */
  public static function ajax_get_duplicates() {
    $groups = self::get_duplicate_groups();
    $count  = count( $groups );

    ob_start();
    if ( $count === 0 ) {
      echo '<div class="pn-cm-clients-empty"><span class="material-icons-outlined">check_circle</span><p>' . esc_html__( 'No duplicates found.', 'pn-customers-manager' ) . '</p></div>';
    } else {
      echo '<div class="pn-cm-dup-header">';
      echo '<h3>' . esc_html( sprintf(
        /* translators: %d: number of duplicate groups */
        __( '%d duplicate group(s) found', 'pn-customers-manager' ),
        $count
      ) ) . '</h3>';
      echo '<button type="button" id="pn-cm-dup-merge-all" class="pn-cm-clients-btn-primary"><span class="material-icons-outlined">merge_type</span> ' . esc_html__( 'Fix all', 'pn-customers-manager' ) . '</button>';
      echo '</div>';

      foreach ( $groups as $gi => $group ) {
        $primary_idx = self::choose_primary( $group );
        echo '<div class="pn-cm-dup-group" data-group-index="' . esc_attr( $gi ) . '">';
        echo '<div class="pn-cm-dup-group-header">';
        echo '<span class="pn-cm-dup-group-title">' . esc_html( sprintf(
          /* translators: %d: group number */
          __( 'Group %d', 'pn-customers-manager' ),
          $gi + 1
        ) ) . '</span>';
        echo '<button type="button" class="pn-cm-dup-merge-btn pn-cm-clients-btn-primary" data-group-index="' . esc_attr( $gi ) . '"><span class="material-icons-outlined">merge_type</span> ' . esc_html__( 'Merge', 'pn-customers-manager' ) . '</button>';
        echo '</div>';

        foreach ( $group as $ci => $client ) {
          $is_primary = ( $ci === $primary_idx );
          $initial    = strtoupper( mb_substr( $client['name'], 0, 1 ) );
          $type_label = $client['type'] === 'user' ? __( 'User', 'pn-customers-manager' ) : __( 'Organization', 'pn-customers-manager' );
          $date_str   = ! empty( $client['date'] ) ? wp_date( get_option( 'date_format' ), strtotime( $client['date'] ) ) : '';

          echo '<div class="pn-cm-dup-client' . ( $is_primary ? ' pn-cm-dup-client-primary' : '' ) . '">';
          echo '<div class="pn-cm-dup-client-avatar">';
          if ( ! empty( $client['avatar'] ) ) {
            echo '<img src="' . esc_url( $client['avatar'] ) . '" alt="" />';
          } else {
            echo '<span class="pn-cm-dup-client-initial">' . esc_html( $initial ) . '</span>';
          }
          echo '</div>';
          echo '<div class="pn-cm-dup-client-info">';
          echo '<div class="pn-cm-dup-client-name">' . esc_html( $client['name'] );
          if ( $is_primary ) {
            echo ' <span class="pn-cm-dup-primary-badge">' . esc_html__( 'Primary', 'pn-customers-manager' ) . '</span>';
          }
          echo '</div>';
          echo '<div class="pn-cm-dup-client-meta">';
          if ( ! empty( $client['email'] ) ) {
            echo '<span>' . esc_html( $client['email'] ) . '</span>';
          }
          if ( ! empty( $client['phone'] ) ) {
            echo '<span>' . esc_html( $client['phone'] ) . '</span>';
          }
          echo '</div>';
          echo '</div>';
          echo '<div class="pn-cm-dup-client-type">' . esc_html( $type_label ) . '</div>';
          if ( $date_str ) {
            echo '<div class="pn-cm-dup-client-date">' . esc_html( $date_str ) . '</div>';
          }
          echo '</div>';
        }
        echo '</div>';
      }
    }
    $html = ob_get_clean();

    echo wp_json_encode( [
      'error_key' => '',
      'html'      => $html,
      'count'     => $count,
    ] );
    exit;
  }

  /**
   * AJAX: Merge duplicate group(s).
   */
  public static function ajax_merge_duplicates() {
    $group_index = isset( $_POST['group_index'] ) ? sanitize_text_field( wp_unslash( $_POST['group_index'] ) ) : '';

    if ( $group_index === '' ) {
      echo wp_json_encode( [ 'error_key' => 'missing_data', 'error_content' => __( 'Missing group index.', 'pn-customers-manager' ) ] );
      exit;
    }

    $total_deleted = 0;

    if ( $group_index === 'all' ) {
      // Merge all groups. Re-fetch each time to avoid stale data.
      $safety = 0;
      while ( $safety < 200 ) {
        $groups = self::get_duplicate_groups();
        if ( empty( $groups ) ) break;
        $result = self::merge_group( $groups[0] );
        $total_deleted += $result['deleted'];
        $safety++;
      }
    } else {
      $idx    = absint( $group_index );
      $groups = self::get_duplicate_groups();
      if ( ! isset( $groups[ $idx ] ) ) {
        echo wp_json_encode( [ 'error_key' => 'invalid_group', 'error_content' => __( 'Group not found. It may have been already merged.', 'pn-customers-manager' ) ] );
        exit;
      }
      $result = self::merge_group( $groups[ $idx ] );
      $total_deleted = $result['deleted'];
    }

    echo wp_json_encode( [
      'error_key' => '',
      'deleted'   => $total_deleted,
      'message'   => sprintf(
        /* translators: %d: number of records merged */
        __( '%d record(s) merged.', 'pn-customers-manager' ),
        $total_deleted
      ),
    ] );
    exit;
  }
}
