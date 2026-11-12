<?php
/**
 * Zoho CRM Connector.
 *
 * Handles bidirectional sync between the plugin's Organizations/Contacts
 * and Zoho CRM contacts via the Zoho REST API with OAuth 2.0.
 *
 * @since      1.2.48
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */
class PN_CUSTOMERS_MANAGER_Zoho {

  /**
   * Default accounts URL (can change based on data center).
   */
  const ACCOUNTS_URL = 'https://accounts.zoho.com';

  // ── OAuth 2.0 ──────────────────────────────────────────────────────

  /**
   * Build the OAuth authorization URL for Zoho.
   *
   * @return string
   */
  public static function get_oauth_url() {
    $client_id    = get_option( 'pn_customers_manager_zoho_client_id', '' );
    $redirect_uri = admin_url( 'admin.php?page=pn_customers_manager_options&pn_cm_zoho_oauth=1' );

    $params = [
      'scope'         => 'ZohoCRM.modules.ALL,ZohoCRM.settings.ALL',
      'client_id'     => $client_id,
      'response_type' => 'code',
      'access_type'   => 'offline',
      'redirect_uri'  => $redirect_uri,
      'prompt'        => 'consent',
    ];

    return self::ACCOUNTS_URL . '/oauth/v2/auth?' . http_build_query( $params );
  }

  /**
   * Check if the current request is a Zoho OAuth callback and handle it.
   */
  public static function maybe_handle_oauth_callback() {
    if ( ! isset( $_GET['pn_cm_zoho_oauth'] ) || ! isset( $_GET['code'] ) ) {
      return;
    }
    self::handle_oauth_callback();
  }

  /**
   * Handle the OAuth callback: exchange code for tokens.
   */
  public static function handle_oauth_callback() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( esc_html__( 'You do not have permission to access this page.', 'pn-customers-manager' ) );
    }

    $code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

    if ( empty( $code ) ) {
      $error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : 'unknown';
      // translators: %s is the OAuth error code or description returned by Zoho.
      wp_die( esc_html( sprintf( __( 'Zoho OAuth error: %s', 'pn-customers-manager' ), $error ) ) );
    }

    $client_id     = get_option( 'pn_customers_manager_zoho_client_id', '' );
    $client_secret = get_option( 'pn_customers_manager_zoho_client_secret', '' );
    $redirect_uri  = admin_url( 'admin.php?page=pn_customers_manager_options&pn_cm_zoho_oauth=1' );

    $response = wp_remote_post( self::ACCOUNTS_URL . '/oauth/v2/token', [
      'body' => [
        'grant_type'    => 'authorization_code',
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
        'code'          => $code,
      ],
      'timeout' => 30,
    ] );

    if ( is_wp_error( $response ) ) {
      wp_die( esc_html( $response->get_error_message() ) );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! empty( $data['error'] ) ) {
      // translators: %s is the error returned by Zoho when exchanging the authorization code for a token.
      wp_die( esc_html( sprintf( __( 'Zoho token error: %s', 'pn-customers-manager' ), $data['error'] ) ) );
    }

    if ( ! empty( $data['access_token'] ) ) {
      update_option( 'pn_customers_manager_zoho_access_token', $data['access_token'] );
    }
    if ( ! empty( $data['refresh_token'] ) ) {
      update_option( 'pn_customers_manager_zoho_refresh_token', $data['refresh_token'] );
    }
    if ( ! empty( $data['api_domain'] ) ) {
      update_option( 'pn_customers_manager_zoho_api_domain', $data['api_domain'] );
    }
    if ( ! empty( $data['expires_in'] ) ) {
      update_option( 'pn_customers_manager_zoho_token_expires', time() + intval( $data['expires_in'] ) );
    }

    // Redirect back to settings page.
    wp_safe_redirect( admin_url( 'admin.php?page=pn_customers_manager_options&zoho_connected=1' ) );
    exit;
  }

  /**
   * Refresh the access token using the stored refresh token.
   *
   * @return string|WP_Error New access token or error.
   */
  public static function refresh_access_token() {
    $client_id     = get_option( 'pn_customers_manager_zoho_client_id', '' );
    $client_secret = get_option( 'pn_customers_manager_zoho_client_secret', '' );
    $refresh_token = get_option( 'pn_customers_manager_zoho_refresh_token', '' );

    if ( empty( $refresh_token ) ) {
      return new WP_Error( 'zoho_no_refresh', __( 'Zoho refresh token is not available. Please reconnect.', 'pn-customers-manager' ) );
    }

    $response = wp_remote_post( self::ACCOUNTS_URL . '/oauth/v2/token', [
      'body' => [
        'grant_type'    => 'refresh_token',
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $refresh_token,
      ],
      'timeout' => 30,
    ] );

    if ( is_wp_error( $response ) ) {
      return $response;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! empty( $data['error'] ) ) {
      // translators: %s is the error returned by Zoho when refreshing the access token.
      return new WP_Error( 'zoho_refresh_error', sprintf( __( 'Zoho token refresh error: %s', 'pn-customers-manager' ), $data['error'] ) );
    }

    if ( ! empty( $data['access_token'] ) ) {
      update_option( 'pn_customers_manager_zoho_access_token', $data['access_token'] );
      if ( ! empty( $data['expires_in'] ) ) {
        update_option( 'pn_customers_manager_zoho_token_expires', time() + intval( $data['expires_in'] ) );
      }
      return $data['access_token'];
    }

    return new WP_Error( 'zoho_no_token', __( 'No access token in refresh response.', 'pn-customers-manager' ) );
  }

  // ── API requests ───────────────────────────────────────────────────

  /**
   * Perform an authenticated request to the Zoho CRM API.
   *
   * @param string $method   HTTP method (GET, POST, PUT, DELETE).
   * @param string $endpoint API endpoint path (e.g. /crm/v2/Contacts).
   * @param array  $body     Optional request body.
   * @return array|WP_Error  Decoded JSON response or WP_Error.
   */
  public static function api_request( $method, $endpoint, $body = null ) {
    $access_token = get_option( 'pn_customers_manager_zoho_access_token', '' );
    $api_domain   = get_option( 'pn_customers_manager_zoho_api_domain', 'https://www.zohoapis.com' );

    if ( empty( $access_token ) ) {
      return new WP_Error( 'zoho_no_token', __( 'Zoho access token is not available. Please connect Zoho first.', 'pn-customers-manager' ) );
    }

    // Check if token needs refresh.
    $expires = intval( get_option( 'pn_customers_manager_zoho_token_expires', 0 ) );
    if ( $expires > 0 && time() >= ( $expires - 60 ) ) {
      $refreshed = self::refresh_access_token();
      if ( is_wp_error( $refreshed ) ) {
        return $refreshed;
      }
      $access_token = $refreshed;
    }

    $args = [
      'method'  => strtoupper( $method ),
      'headers' => [
        'Authorization' => 'Zoho-oauthtoken ' . $access_token,
        'Content-Type'  => 'application/json',
      ],
      'timeout' => 30,
    ];

    if ( $body !== null ) {
      $args['body'] = wp_json_encode( $body );
    }

    $url      = $api_domain . $endpoint;
    $response = wp_remote_request( $url, $args );

    if ( is_wp_error( $response ) ) {
      return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    // Auto-refresh on 401.
    if ( $code === 401 ) {
      $refreshed = self::refresh_access_token();
      if ( is_wp_error( $refreshed ) ) {
        return $refreshed;
      }

      $args['headers']['Authorization'] = 'Zoho-oauthtoken ' . $refreshed;
      $response = wp_remote_request( $url, $args );

      if ( is_wp_error( $response ) ) {
        return $response;
      }

      $code = wp_remote_retrieve_response_code( $response );
      $data = json_decode( wp_remote_retrieve_body( $response ), true );
    }

    if ( $code < 200 || $code >= 300 ) {
      $message = isset( $data['message'] ) ? $data['message'] : wp_remote_retrieve_body( $response );
      // translators: %1$d is the HTTP status code, %2$s is the error message from the Zoho API.
      return new WP_Error( 'zoho_api_error', sprintf( __( 'Zoho API error (%1$d): %2$s', 'pn-customers-manager' ), $code, $message ) );
    }

    return $data;
  }

  /**
   * Test the Zoho CRM connection.
   *
   * @return array Result array.
   */
  public static function test_connection() {
    $result = self::api_request( 'GET', '/crm/v2/org' );

    if ( is_wp_error( $result ) ) {
      return [
        'error_key'     => 'zoho_test_failed',
        'error_content' => $result->get_error_message(),
      ];
    }

    $org_name = isset( $result['org'][0]['company_name'] ) ? $result['org'][0]['company_name'] : '?';

    return [
      'error_key' => '',
      // translators: %s is the organization name returned by the Zoho CRM API.
      'message'   => sprintf( __( 'Zoho connection successful. Organization: %s', 'pn-customers-manager' ), $org_name ),
    ];
  }

  /**
   * Check if Zoho is connected (has refresh token).
   *
   * @return bool
   */
  public static function is_connected() {
    return ! empty( get_option( 'pn_customers_manager_zoho_refresh_token', '' ) );
  }

  /**
   * Disconnect Zoho (remove tokens).
   */
  public static function disconnect() {
    delete_option( 'pn_customers_manager_zoho_access_token' );
    delete_option( 'pn_customers_manager_zoho_refresh_token' );
    delete_option( 'pn_customers_manager_zoho_api_domain' );
    delete_option( 'pn_customers_manager_zoho_token_expires' );
  }

  // ── Field mapping ──────────────────────────────────────────────────

  /**
   * Map organization post meta to a Zoho CRM contact payload.
   */
  public static function map_org_to_zoho( $org_id ) {
    $payload = [
      'Full_Name'      => get_the_title( $org_id ),
      'Company'        => get_post_meta( $org_id, 'pn_cm_organization_legal_name', true ) ?: get_the_title( $org_id ),
      'Email'          => get_post_meta( $org_id, 'pn_cm_organization_email', true ),
      'Phone'          => get_post_meta( $org_id, 'pn_cm_organization_phone', true ),
      'Mailing_Street' => get_post_meta( $org_id, 'pn_cm_organization_address', true ),
      'Mailing_City'   => get_post_meta( $org_id, 'pn_cm_organization_city', true ),
      'Mailing_State'  => get_post_meta( $org_id, 'pn_cm_organization_region', true ),
      'Mailing_Zip'    => get_post_meta( $org_id, 'pn_cm_organization_postal_code', true ),
      'Mailing_Country' => get_post_meta( $org_id, 'pn_cm_organization_country', true ),
    ];

    // Split Full_Name into First/Last for Zoho.
    $title = get_the_title( $org_id );
    $parts = explode( ' ', $title, 2 );
    $payload['First_Name'] = $parts[0];
    $payload['Last_Name']  = isset( $parts[1] ) ? $parts[1] : $parts[0];

    return array_filter( $payload, function( $v ) { return $v !== '' && $v !== null; } );
  }

  /**
   * Map WP user data to a Zoho CRM contact payload.
   */
  public static function map_user_to_zoho( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) {
      return [];
    }

    $phone = get_user_meta( $user_id, 'phone', true );
    if ( empty( $phone ) ) {
      $phone = get_user_meta( $user_id, 'billing_phone', true );
    }

    $payload = [
      'First_Name' => $user->first_name,
      'Last_Name'  => $user->last_name ?: $user->first_name,
      'Email'      => $user->user_email,
      'Phone'      => $phone,
    ];

    return array_filter( $payload, function( $v ) { return $v !== '' && $v !== null; } );
  }

  /**
   * Update organization post meta from Zoho contact data.
   */
  public static function map_zoho_to_org( $zoho_contact, $org_id ) {
    $mapping = [
      'Email'           => 'pn_cm_organization_email',
      'Phone'           => 'pn_cm_organization_phone',
      'Mailing_Street'  => 'pn_cm_organization_address',
      'Mailing_City'    => 'pn_cm_organization_city',
      'Mailing_State'   => 'pn_cm_organization_region',
      'Mailing_Zip'     => 'pn_cm_organization_postal_code',
      'Mailing_Country' => 'pn_cm_organization_country',
    ];

    foreach ( $mapping as $zoho_key => $meta_key ) {
      if ( ! empty( $zoho_contact[ $zoho_key ] ) ) {
        update_post_meta( $org_id, $meta_key, sanitize_text_field( $zoho_contact[ $zoho_key ] ) );
      }
    }

    if ( ! empty( $zoho_contact['Company'] ) ) {
      update_post_meta( $org_id, 'pn_cm_organization_legal_name', sanitize_text_field( $zoho_contact['Company'] ) );
    }

    // Update title from Full_Name.
    $name = self::get_zoho_full_name( $zoho_contact );
    if ( ! empty( $name ) ) {
      wp_update_post( [
        'ID'         => $org_id,
        'post_title' => sanitize_text_field( $name ),
      ] );
    }

    if ( ! empty( $zoho_contact['id'] ) ) {
      update_post_meta( $org_id, 'pn_cm_organization_zoho_id', sanitize_text_field( $zoho_contact['id'] ) );
    }
  }

  /**
   * Update WP user data from Zoho contact data.
   */
  public static function map_zoho_to_user( $zoho_contact, $user_id ) {
    $update_data = [ 'ID' => $user_id ];

    if ( ! empty( $zoho_contact['First_Name'] ) ) {
      $update_data['first_name'] = sanitize_text_field( $zoho_contact['First_Name'] );
    }
    if ( ! empty( $zoho_contact['Last_Name'] ) ) {
      $update_data['last_name'] = sanitize_text_field( $zoho_contact['Last_Name'] );
    }
    if ( ! empty( $zoho_contact['Email'] ) ) {
      $update_data['user_email'] = sanitize_email( $zoho_contact['Email'] );
    }

    wp_update_user( $update_data );

    if ( ! empty( $zoho_contact['Phone'] ) ) {
      update_user_meta( $user_id, 'phone', sanitize_text_field( $zoho_contact['Phone'] ) );
    }

    if ( ! empty( $zoho_contact['id'] ) ) {
      update_user_meta( $user_id, 'pn_cm_zoho_contact_id', sanitize_text_field( $zoho_contact['id'] ) );
    }

    // Track last profile update for template inactivity checks.
    update_user_meta( $user_id, 'pn_cm_profile_updated', current_time( 'mysql' ) );
  }

  /**
   * Get full name from Zoho contact data.
   */
  private static function get_zoho_full_name( $zoho_contact ) {
    if ( ! empty( $zoho_contact['Full_Name'] ) ) {
      return $zoho_contact['Full_Name'];
    }
    $parts = array_filter( [
      isset( $zoho_contact['First_Name'] ) ? $zoho_contact['First_Name'] : '',
      isset( $zoho_contact['Last_Name'] ) ? $zoho_contact['Last_Name'] : '',
    ] );
    return implode( ' ', $parts );
  }

  // ── Push to Zoho ───────────────────────────────────────────────────

  /**
   * Push a single organization to Zoho CRM.
   */
  public static function push_organization( $org_id ) {
    $payload = self::map_org_to_zoho( $org_id );
    $zoho_id = get_post_meta( $org_id, 'pn_cm_organization_zoho_id', true );

    $body = [ 'data' => [ $payload ] ];

    if ( ! empty( $zoho_id ) ) {
      $payload['id'] = $zoho_id;
      $body = [ 'data' => [ $payload ] ];
      $result = self::api_request( 'PUT', '/crm/v2/Contacts', $body );
    } else {
      $result = self::api_request( 'POST', '/crm/v2/Contacts', $body );
    }

    if ( is_wp_error( $result ) ) {
      return $result;
    }

    // Store the Zoho contact ID.
    if ( isset( $result['data'][0]['details']['id'] ) ) {
      update_post_meta( $org_id, 'pn_cm_organization_zoho_id', sanitize_text_field( $result['data'][0]['details']['id'] ) );
    }

    return $result;
  }

  /**
   * Push a single WP user to Zoho CRM.
   */
  public static function push_contact( $user_id ) {
    $payload = self::map_user_to_zoho( $user_id );
    $zoho_id = get_user_meta( $user_id, 'pn_cm_zoho_contact_id', true );

    $body = [ 'data' => [ $payload ] ];

    if ( ! empty( $zoho_id ) ) {
      $payload['id'] = $zoho_id;
      $body = [ 'data' => [ $payload ] ];
      $result = self::api_request( 'PUT', '/crm/v2/Contacts', $body );
    } else {
      $result = self::api_request( 'POST', '/crm/v2/Contacts', $body );
    }

    if ( is_wp_error( $result ) ) {
      return $result;
    }

    if ( isset( $result['data'][0]['details']['id'] ) ) {
      update_user_meta( $user_id, 'pn_cm_zoho_contact_id', sanitize_text_field( $result['data'][0]['details']['id'] ) );
    }

    return $result;
  }

  /**
   * Push all organizations and contacts to Zoho.
   */
  public static function push_all() {
    $summary = [
      'orgs_pushed'     => 0,
      'orgs_errors'     => 0,
      'contacts_pushed' => 0,
      'contacts_errors' => 0,
    ];

    $orgs = get_posts( [
      'post_type'      => 'pn_cm_organization',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
      'fields'         => 'ids',
    ] );

    foreach ( $orgs as $org_id ) {
      $result = self::push_organization( $org_id );
      if ( is_wp_error( $result ) ) {
        $summary['orgs_errors']++;
      } else {
        $summary['orgs_pushed']++;
      }

      $linked_users = get_post_meta( $org_id, 'pn_cm_organization_contact', false );
      if ( ! empty( $linked_users ) ) {
        foreach ( $linked_users as $user_id ) {
          $user_id = intval( $user_id );
          if ( $user_id <= 0 ) continue;

          $contact_result = self::push_contact( $user_id );
          if ( is_wp_error( $contact_result ) ) {
            $summary['contacts_errors']++;
          } else {
            $summary['contacts_pushed']++;
          }
        }
      }
    }

    return $summary;
  }

  // ── Pull from Zoho ─────────────────────────────────────────────────

  /**
   * Pull all contacts from Zoho CRM.
   */
  /**
   * Pull one page of contacts from Zoho CRM.
   *
   * @param int $page     Page number (1-based).
   * @param int $per_page Contacts per page.
   * @return array { summary: array, page: int, has_more: bool }
   */
  public static function pull_contacts_page( $page = 1, $per_page = 200 ) {
    $summary = [
      'orgs_created'     => 0,
      'orgs_updated'     => 0,
      'contacts_created' => 0,
      'contacts_updated' => 0,
      'errors'           => 0,
    ];

    $result = self::api_request( 'GET', '/crm/v2/Contacts?page=' . $page . '&per_page=' . $per_page );

    if ( is_wp_error( $result ) ) {
      self::log( 'Pull API error: ' . $result->get_error_message() );
      $summary['errors']++;
      $summary['last_error'] = $result->get_error_message();
      return [ 'summary' => $summary, 'page' => $page, 'has_more' => false ];
    }

    $contacts = isset( $result['data'] ) ? $result['data'] : [];

    if ( empty( $contacts ) ) {
      return [ 'summary' => $summary, 'page' => $page, 'has_more' => false ];
    }

    foreach ( $contacts as $zoho_contact ) {
      $processed = self::process_pulled_contact( $zoho_contact );
      if ( is_wp_error( $processed ) ) {
        $summary['errors']++;
        $summary['last_error'] = $processed->get_error_message();
      } else {
        $summary[ $processed ]++;
        self::set_client_meta_after_save( $zoho_contact, 'zoho', $processed );
      }
    }

    $has_more = isset( $result['info']['more_records'] ) && $result['info']['more_records'];

    return [ 'summary' => $summary, 'page' => $page, 'has_more' => $has_more ];
  }

  /**
   * Pull all contacts from Zoho CRM (all pages at once).
   */
  public static function pull_contacts() {
    $summary = [
      'orgs_created'     => 0,
      'orgs_updated'     => 0,
      'contacts_created' => 0,
      'contacts_updated' => 0,
      'errors'           => 0,
    ];

    $page = 1;

    do {
      $page_result = self::pull_contacts_page( $page );
      foreach ( [ 'orgs_created', 'orgs_updated', 'contacts_created', 'contacts_updated', 'errors' ] as $key ) {
        $summary[ $key ] += $page_result['summary'][ $key ];
      }
      if ( ! empty( $page_result['summary']['last_error'] ) ) {
        $summary['last_error'] = $page_result['summary']['last_error'];
      }
      $page++;
    } while ( $page_result['has_more'] );

    return $summary;
  }

  /**
   * Pull a single contact from Zoho by ID.
   */
  public static function pull_single( $zoho_id ) {
    $result = self::api_request( 'GET', '/crm/v2/Contacts/' . urlencode( $zoho_id ) );

    if ( is_wp_error( $result ) ) {
      return $result;
    }

    $contact = isset( $result['data'][0] ) ? $result['data'][0] : [];
    if ( empty( $contact ) ) {
      return new WP_Error( 'zoho_no_data', __( 'No contact data returned.', 'pn-customers-manager' ) );
    }

    return self::process_pulled_contact( $contact );
  }

  /**
   * Process a single pulled Zoho contact.
   */
  private static function process_pulled_contact( $zoho_contact ) {
    $zoho_id = isset( $zoho_contact['id'] ) ? $zoho_contact['id'] : '';

    if ( empty( $zoho_id ) ) {
      return new WP_Error( 'zoho_no_id', 'Contact has no ID.' );
    }

    // Check existing organization.
    $existing_org = self::find_org_by_zoho_id( $zoho_id );
    if ( $existing_org ) {
      self::map_zoho_to_org( $zoho_contact, $existing_org );
      return 'orgs_updated';
    }

    // Check existing user.
    $existing_user = self::find_user_by_zoho_id( $zoho_id );
    if ( $existing_user ) {
      self::map_zoho_to_user( $zoho_contact, $existing_user );
      return 'contacts_updated';
    }

    // Before creating anything, check if an organization already exists for
    // this phone number (may have been created by WhatsApp AI sync without
    // a Zoho ID). This prevents duplicate organizations.
    $contact_phone = isset( $zoho_contact['Phone'] ) ? $zoho_contact['Phone'] : '';
    if ( ! empty( $contact_phone ) ) {
      $existing_org_by_phone = self::find_org_by_phone( $contact_phone );
      if ( $existing_org_by_phone ) {
        self::map_zoho_to_org( $zoho_contact, $existing_org_by_phone );
        self::log( 'Linked existing org #' . $existing_org_by_phone . ' (found by phone ' . $contact_phone . ') to Zoho contact ' . $zoho_id );
        return 'orgs_updated';
      }
    }

    // Also check by email if an organization exists with that email.
    $contact_email_check = isset( $zoho_contact['Email'] ) ? $zoho_contact['Email'] : '';
    if ( ! empty( $contact_email_check ) ) {
      $existing_org_by_email = self::find_org_by_email( $contact_email_check );
      if ( $existing_org_by_email ) {
        self::map_zoho_to_org( $zoho_contact, $existing_org_by_email );
        self::log( 'Linked existing org #' . $existing_org_by_email . ' (found by email ' . $contact_email_check . ') to Zoho contact ' . $zoho_id );
        return 'orgs_updated';
      }
    }

    // Check if a user already exists with this phone number.
    if ( ! empty( $contact_phone ) ) {
      $existing_user_by_phone = self::find_user_by_phone( $contact_phone );
      if ( $existing_user_by_phone ) {
        self::map_zoho_to_user( $zoho_contact, $existing_user_by_phone );
        self::log( 'Linked existing user #' . $existing_user_by_phone . ' (found by phone ' . $contact_phone . ') to Zoho contact ' . $zoho_id );
        return 'contacts_updated';
      }
    }

    // Check if a user already exists with this email.
    if ( ! empty( $contact_email_check ) && email_exists( $contact_email_check ) ) {
      $user_id = get_user_by( 'email', $contact_email_check )->ID;
      self::map_zoho_to_user( $zoho_contact, $user_id );
      self::log( 'Linked existing user #' . $user_id . ' (found by email ' . $contact_email_check . ') to Zoho contact ' . $zoho_id );
      return 'contacts_updated';
    }

    // New contact — decide org vs user.
    $has_company = ! empty( $zoho_contact['Company'] );

    if ( $has_company ) {
      $org_id = wp_insert_post( [
        'post_type'   => 'pn_cm_organization',
        'post_title'  => sanitize_text_field( $zoho_contact['Company'] ),
        'post_status' => 'publish',
      ] );

      if ( is_wp_error( $org_id ) ) {
        return $org_id;
      }

      self::map_zoho_to_org( $zoho_contact, $org_id );
      return 'orgs_created';
    } else {
      $email      = isset( $zoho_contact['Email'] ) ? sanitize_email( $zoho_contact['Email'] ) : '';
      $first_name = isset( $zoho_contact['First_Name'] ) ? sanitize_text_field( $zoho_contact['First_Name'] ) : '';
      $last_name  = isset( $zoho_contact['Last_Name'] ) ? sanitize_text_field( $zoho_contact['Last_Name'] ) : '';
      $login      = ! empty( $email ) ? $email : 'zoho_' . $zoho_id;
      $password   = wp_generate_password( 24 );

      $user_id = PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_insert(
        $login, $password, $email, $first_name, $last_name
      );

      if ( ! $user_id || is_wp_error( $user_id ) ) {
        return new WP_Error( 'zoho_user_create_failed', 'Failed to create user for Zoho contact ' . $zoho_id );
      }

      self::map_zoho_to_user( $zoho_contact, $user_id );
      return 'contacts_created';
    }
  }

  /**
   * Find an organization by phone number, stripping formatting characters.
   *
   * @param string $phone Phone number to search for.
   * @return int|false Organization post ID, or false if not found.
   */
  private static function find_org_by_phone( $phone ) {
    $phone_clean = preg_replace( '/[^\d]/', '', $phone );
    if ( empty( $phone_clean ) ) {
      return false;
    }

    global $wpdb;
    $org_id = $wpdb->get_var( $wpdb->prepare(
      "SELECT p.ID FROM {$wpdb->posts} p
       INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
       WHERE p.post_type = 'pn_cm_organization'
         AND p.post_status = 'publish'
         AND pm.meta_key = 'pn_cm_organization_phone'
         AND REPLACE(REPLACE(REPLACE(REPLACE(pm.meta_value, '+', ''), ' ', ''), '-', ''), '.', '') LIKE %s
       ORDER BY p.ID DESC
       LIMIT 1",
      '%' . $wpdb->esc_like( $phone_clean ) . '%'
    ) );

    return $org_id ? (int) $org_id : false;
  }

  /**
   * Find an organization by email address.
   *
   * @param string $email Email address to search for.
   * @return int|false Organization post ID, or false if not found.
   */
  private static function find_org_by_email( $email ) {
    if ( empty( $email ) ) {
      return false;
    }

    $orgs = get_posts( [
      'post_type'      => 'pn_cm_organization',
      'posts_per_page' => 1,
      'post_status'    => 'publish',
      'meta_key'       => 'pn_cm_organization_email',
      'meta_value'     => sanitize_email( $email ),
      'fields'         => 'ids',
    ] );

    return ! empty( $orgs ) ? $orgs[0] : false;
  }

  // ── Lookup helpers ─────────────────────────────────────────────────

  private static function find_org_by_zoho_id( $zoho_id ) {
    $posts = get_posts( [
      'post_type'      => 'pn_cm_organization',
      'posts_per_page' => 1,
      'post_status'    => 'any',
      'meta_key'       => 'pn_cm_organization_zoho_id',
      'meta_value'     => $zoho_id,
      'fields'         => 'ids',
    ] );

    return ! empty( $posts ) ? $posts[0] : false;
  }

  private static function find_user_by_zoho_id( $zoho_id ) {
    $users = get_users( [
      'meta_key'   => 'pn_cm_zoho_contact_id',
      'meta_value' => $zoho_id,
      'number'     => 1,
      'fields'     => 'ID',
    ] );

    return ! empty( $users ) ? intval( $users[0] ) : false;
  }

  /**
   * Find a WP user by phone number, stripping formatting characters.
   *
   * @param string $phone Phone number to search for.
   * @return int|false User ID, or false if not found.
   */
  private static function find_user_by_phone( $phone ) {
    $phone_clean = preg_replace( '/[^\d]/', '', $phone );
    if ( empty( $phone_clean ) ) {
      return false;
    }

    global $wpdb;
    $user_id = $wpdb->get_var( $wpdb->prepare(
      "SELECT u.user_id FROM {$wpdb->usermeta} u
       WHERE u.meta_key = 'phone'
       AND REPLACE(REPLACE(REPLACE(REPLACE(u.meta_value, '+', ''), ' ', ''), '-', ''), '.', '') LIKE %s
       LIMIT 1",
      '%' . $wpdb->esc_like( $phone_clean ) . '%'
    ) );

    if ( $user_id ) {
      return (int) $user_id;
    }

    // Also check billing_phone.
    $user_id = $wpdb->get_var( $wpdb->prepare(
      "SELECT u.user_id FROM {$wpdb->usermeta} u
       WHERE u.meta_key = 'billing_phone'
       AND REPLACE(REPLACE(REPLACE(REPLACE(u.meta_value, '+', ''), ' ', ''), '-', ''), '.', '') LIKE %s
       LIMIT 1",
      '%' . $wpdb->esc_like( $phone_clean ) . '%'
    ) );

    return $user_id ? (int) $user_id : false;
  }

  // ── REST API webhook ───────────────────────────────────────────────

  /**
   * Register REST API routes for Zoho webhooks.
   */
  public static function register_routes() {
    register_rest_route( 'pn-cm/v1', '/zoho/webhook', [
      'methods'             => 'POST',
      'callback'            => [ __CLASS__, 'handle_webhook' ],
      'permission_callback' => '__return_true',
    ] );
  }

  /**
   * Handle incoming Zoho webhook requests.
   */
  public static function handle_webhook( $request ) {
    // Process any overdue scheduled WhatsApp templates on every incoming webhook.
    if ( class_exists( 'PN_CUSTOMERS_MANAGER_WhatsApp_AI' ) ) {
      PN_CUSTOMERS_MANAGER_WhatsApp_AI::process_pending_template_events();
    }

    $body = $request->get_json_params();

    if ( empty( $body ) ) {
      $body = $request->get_body_params();
    }

    self::log( 'Webhook received: ' . wp_json_encode( array_keys( $body ) ) );
    self::log( 'Webhook full payload: ' . wp_json_encode( $body ) );

    // ── Deal stage update ───────────────────────────────────────────
    $deal_stage_result = self::handle_deal_stage_update( $body );
    if ( $deal_stage_result !== false ) {
      return new WP_REST_Response( $deal_stage_result, 200 );
    }

    // Normalize contact data.
    $contact = $body;
    if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
      $contact = $body['data'];
    }

    // Build name if not present.
    if ( empty( $contact['Full_Name'] ) ) {
      $parts = array_filter( [
        isset( $contact['First_Name'] ) ? $contact['First_Name'] : '',
        isset( $contact['Last_Name'] ) ? $contact['Last_Name'] : '',
      ] );
      if ( $parts ) {
        $contact['Full_Name'] = implode( ' ', $parts );
      }
    }

    if ( empty( $contact['id'] ) && empty( $contact['Email'] ) ) {
      self::log( 'Webhook ignored: no contact id or email.' );
      return new WP_REST_Response( [ 'status' => 'ignored', 'reason' => 'no contact data' ], 200 );
    }

    if ( empty( $contact['id'] ) ) {
      $contact['id'] = sanitize_email( $contact['Email'] );
    }

    // Find and execute funnel trigger nodes.
    $trigger_nodes = self::find_zoho_trigger_nodes();
    $saved_to_wp   = false;

    foreach ( $trigger_nodes as $trigger ) {
      $settings = isset( $trigger['config'] ) ? $trigger['config'] : [];

      if ( ! $saved_to_wp && ! empty( $settings['zoho_save_to_wp'] ) && $settings['zoho_save_to_wp'] !== 'off' ) {
        $result = self::process_pulled_contact( $contact );

        if ( is_wp_error( $result ) ) {
          self::log( 'Webhook process error: ' . $result->get_error_message() );
        } else {
          self::log( 'Webhook processed contact: ' . $result );
          self::set_client_meta_after_save( $contact, 'zoho', $result );
        }

        $saved_to_wp = true;
      }

      self::execute_node_action( $trigger, $contact );

      // Follow connection arrows to downstream nodes (WhatsApp AI, Email, etc.).
      self::execute_downstream_nodes( $trigger['id'], $trigger['_funnel_id'], $contact );
    }

    if ( empty( $trigger_nodes ) ) {
      self::log( 'Webhook: no trigger nodes found, skipping local save.' );
    }

    return new WP_REST_Response( [ 'status' => 'ok' ], 200 );
  }

  /**
   * Get the webhook URL for Zoho integration.
   */
  public static function get_webhook_url() {
    return rest_url( 'pn-cm/v1/zoho/webhook' );
  }

  /**
   * Find all funnel canvas nodes with subtype zoho_new_contact.
   */
  public static function find_zoho_trigger_nodes() {
    $funnels = get_posts( [
      'post_type'      => 'pn_cm_funnel',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
      'fields'         => 'ids',
    ] );

    $nodes = [];

    foreach ( $funnels as $funnel_id ) {
      $canvas_json = get_post_meta( $funnel_id, 'pn_cm_funnel_canvas', true );
      if ( empty( $canvas_json ) ) {
        continue;
      }

      $canvas = json_decode( $canvas_json, true );
      if ( empty( $canvas['nodes'] ) || ! is_array( $canvas['nodes'] ) ) {
        continue;
      }

      foreach ( $canvas['nodes'] as $node ) {
        if ( isset( $node['subtype'] ) && $node['subtype'] === 'zoho_new_contact' ) {
          $node['_funnel_id'] = $funnel_id;
          $nodes[] = $node;
        }
      }
    }

    return $nodes;
  }

  /**
   * Execute the configured action for a Zoho trigger node.
   */
  private static function execute_node_action( $node_config, $contact_data ) {
    $settings = isset( $node_config['config'] ) ? $node_config['config'] : [];

    $contact_name  = self::get_zoho_full_name( $contact_data );
    $contact_email = isset( $contact_data['Email'] ) ? $contact_data['Email'] : '';
    $contact_phone = isset( $contact_data['Phone'] ) ? $contact_data['Phone'] : '';
    $company       = isset( $contact_data['Company'] ) ? $contact_data['Company'] : '';

    // Only send notification email if the checkbox is enabled.
    $send_email = ! empty( $settings['zoho_send_email'] ) && $settings['zoho_send_email'] === 'on';
    if ( ! $send_email ) {
      return;
    }

    $to = isset( $settings['zoho_notify_email'] ) ? sanitize_email( $settings['zoho_notify_email'] ) : get_option( 'admin_email' );
    if ( empty( $to ) ) {
      $to = get_option( 'admin_email' );
    }

    $subject_template = isset( $settings['zoho_notify_subject'] ) && ! empty( $settings['zoho_notify_subject'] )
      ? $settings['zoho_notify_subject']
      : __( 'New Zoho Contact: {name}', 'pn-customers-manager' );

    $subject = str_replace( '{name}', $contact_name, $subject_template );

    $body = sprintf(
      // translators: %1$s is the contact name, %2$s is the company name, %3$s is the email address, %4$s is the phone number.
      __( 'A new contact has been received from Zoho CRM:

Name: %1$s
Company: %2$s
Email: %3$s
Phone: %4$s', 'pn-customers-manager' ),
      $contact_name, $company, $contact_email, $contact_phone
    );

    if ( class_exists( 'MAILPN_Mailing' ) ) {
      $mailing = new MAILPN_Mailing();
      $mailing->mailpn_sender( [
        'mailpn_user_to' => $to,
        'mailpn_subject' => $subject,
        'mailpn_type'    => 'pn_cm_zoho_notification',
      ], nl2br( $body ) );
      self::log( 'MAILPN notification sent to ' . $to . ' for Zoho contact: ' . $contact_name );
    } else {
      wp_mail( $to, $subject, $body );
      self::log( 'Email notification sent to ' . $to . ' for Zoho contact: ' . $contact_name );
    }
  }

  /**
   * Set client meta after a Zoho contact is saved locally.
   */
  public static function set_client_meta_after_save( $contact, $source, $result_type ) {
    $zoho_id = isset( $contact['id'] ) ? $contact['id'] : '';
    if ( empty( $zoho_id ) ) {
      return;
    }

    $default = class_exists( 'PN_CUSTOMERS_MANAGER_Clients' )
      ? PN_CUSTOMERS_MANAGER_Clients::get_default_stage()
      : 'New';

    if ( in_array( $result_type, [ 'orgs_created', 'orgs_updated' ], true ) ) {
      $org_id = self::find_org_by_zoho_id( $zoho_id );
      if ( $org_id ) {
        if ( empty( get_post_meta( $org_id, 'pn_cm_organization_client_stage', true ) ) ) {
          update_post_meta( $org_id, 'pn_cm_organization_client_stage', $default );
        }
        update_post_meta( $org_id, 'pn_cm_client_source', $source );
      }
    } elseif ( in_array( $result_type, [ 'contacts_created', 'contacts_updated' ], true ) ) {
      $user_id = self::find_user_by_zoho_id( $zoho_id );
      if ( $user_id ) {
        if ( empty( get_user_meta( $user_id, 'pn_cm_client_stage', true ) ) ) {
          update_user_meta( $user_id, 'pn_cm_client_stage', $default );
        }
        update_user_meta( $user_id, 'pn_cm_client_source', $source );
      }
    }
  }

  // ── Pipeline execution ───────────────────────────────────────────

  /**
   * Follow connection arrows from a trigger node and execute downstream nodes.
   *
   * @param string $trigger_node_id The trigger node ID in the canvas.
   * @param int    $funnel_id       The funnel post ID.
   * @param array  $contact_data    Normalized contact data.
   */
  private static function execute_downstream_nodes( $trigger_node_id, $funnel_id, $contact_data ) {
    $canvas_json = get_post_meta( $funnel_id, 'pn_cm_funnel_canvas', true );
    if ( empty( $canvas_json ) ) {
      return;
    }

    $canvas = json_decode( $canvas_json, true );
    if ( empty( $canvas['connections'] ) || empty( $canvas['nodes'] ) ) {
      return;
    }

    $nodes_by_id = [];
    foreach ( $canvas['nodes'] as $n ) {
      $nodes_by_id[ $n['id'] ] = $n;
    }

    foreach ( $canvas['connections'] as $conn ) {
      if ( $conn['from'] !== $trigger_node_id ) {
        continue;
      }

      $target = isset( $nodes_by_id[ $conn['to'] ] ) ? $nodes_by_id[ $conn['to'] ] : null;
      if ( ! $target ) {
        continue;
      }

      $subtype = isset( $target['subtype'] ) ? $target['subtype'] : '';
      $config  = isset( $target['config'] ) ? $target['config'] : [];

      self::execute_single_node( $subtype, $config, $target['id'], $funnel_id, $contact_data );
    }
  }

  /**
   * Execute a single downstream node based on its subtype.
   *
   * @param string $subtype      Node subtype.
   * @param array  $config       Node configuration.
   * @param string $node_id      The node ID.
   * @param int    $funnel_id    The funnel post ID.
   * @param array  $contact_data Normalized contact data.
   */
  private static function execute_single_node( $subtype, $config, $node_id, $funnel_id, $contact_data ) {
    $name  = isset( $contact_data['name'] ) ? $contact_data['name'] : '';
    $email = isset( $contact_data['email'] ) ? $contact_data['email'] : '';
    $phone = isset( $contact_data['phone'] ) ? $contact_data['phone'] : '';

    switch ( $subtype ) {
      case 'whatsapp_ai':
        if ( empty( $phone ) ) {
          self::log( 'Downstream node ' . $node_id . ' (whatsapp_ai): skipped, no phone number.' );
          break;
        }
        if ( ! class_exists( 'PN_CUSTOMERS_MANAGER_WhatsApp_AI' ) ) {
          self::log( 'Downstream node ' . $node_id . ' (whatsapp_ai): skipped, WhatsApp AI class not available.' );
          break;
        }
        self::log( 'Executing whatsapp_ai node ' . $node_id . ' for phone=' . $phone );
        PN_CUSTOMERS_MANAGER_WhatsApp_AI::create_conversation_for_node( $phone, $name, $funnel_id, $node_id );
        break;

      case 'send_email':
      case 'email_sequence':
        if ( empty( $email ) ) {
          self::log( 'Downstream node ' . $node_id . ' (' . $subtype . '): skipped, no email address.' );
          break;
        }

        $send_mode = ! empty( $config['es_send_mode'] ) ? $config['es_send_mode'] : 'manual';

        if ( $send_mode === 'mailpn_template' && class_exists( 'MAILPN_Mailing' ) ) {
          if ( $subtype === 'send_email' ) {
            $template_id = ! empty( $config['es_mailpn_template'] ) ? absint( $config['es_mailpn_template'] ) : 0;
            if ( $template_id ) {
              ( new MAILPN_Mailing() )->mailpn_queue_add( $template_id, $email );
              self::log( 'MAILPN template ' . $template_id . ' queued for ' . $email . ' (node ' . $node_id . ')' );
            }
          } else {
            $template_ids = ! empty( $config['es_mailpn_template_id'] ) ? $config['es_mailpn_template_id'] : [];
            if ( ! is_array( $template_ids ) ) {
              $template_ids = [ $template_ids ];
            }
            $mailing = new MAILPN_Mailing();
            foreach ( $template_ids as $tid ) {
              $tid = absint( $tid );
              if ( $tid ) {
                $mailing->mailpn_queue_add( $tid, $email );
                self::log( 'MAILPN template ' . $tid . ' queued for ' . $email . ' (node ' . $node_id . ')' );
              }
            }
          }
        } else {
          if ( $subtype === 'send_email' ) {
            $subject = ! empty( $config['es_subject'] ) ? $config['es_subject'] : __( 'Welcome', 'pn-customers-manager' );
            $body    = ! empty( $config['es_body'] ) ? $config['es_body'] : '';
            self::send_pipeline_email( $email, $subject, $body, $config, $contact_data );
            self::log( 'Email sent to ' . $email . ' (node ' . $node_id . ')' );
          } else {
            $subjects = ! empty( $config['es_email_subject'] ) ? $config['es_email_subject'] : [];
            $bodies   = ! empty( $config['es_email_body'] ) ? $config['es_email_body'] : [];
            if ( ! is_array( $subjects ) ) {
              $subjects = [ $subjects ];
            }
            if ( ! is_array( $bodies ) ) {
              $bodies = [ $bodies ];
            }
            foreach ( $subjects as $i => $subj ) {
              $b = isset( $bodies[ $i ] ) ? $bodies[ $i ] : '';
              self::send_pipeline_email( $email, $subj, $b, $config, $contact_data );
              self::log( 'Sequence email ' . ( $i + 1 ) . ' sent to ' . $email . ' (node ' . $node_id . ')' );
            }
          }
        }
        break;

      default:
        self::log( 'Downstream node ' . $node_id . ' subtype=' . $subtype . ' — no handler, skipping.' );
        break;
    }
  }

  /**
   * Send a pipeline email with placeholder replacement and MAILPN/wp_mail fallback.
   *
   * @param string $to           Recipient email.
   * @param string $subject      Email subject (may contain placeholders).
   * @param string $body         Email body HTML (may contain placeholders).
   * @param array  $config       Node configuration.
   * @param array  $contact_data Normalized contact data.
   */
  private static function send_pipeline_email( $to, $subject, $body, $config, $contact_data ) {
    $name  = isset( $contact_data['name'] ) ? $contact_data['name'] : '';
    $email = isset( $contact_data['email'] ) ? $contact_data['email'] : '';
    $phone = isset( $contact_data['phone'] ) ? $contact_data['phone'] : '';

    $replacements = [ '{name}' => $name, '{email}' => $email, '{phone}' => $phone ];
    $subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
    $body    = str_replace( array_keys( $replacements ), array_values( $replacements ), $body );

    $from_name  = ! empty( $config['es_from_name'] ) ? $config['es_from_name'] : get_bloginfo( 'name' );
    $from_email = ! empty( $config['es_from_email'] ) ? $config['es_from_email'] : get_option( 'admin_email' );

    if ( class_exists( 'MAILPN_Mailing' ) ) {
      $mailing = new MAILPN_Mailing();
      $mailing->mailpn_sender( [
        'mailpn_user_to' => $to,
        'mailpn_subject' => $subject,
        'mailpn_type'    => 'pn_cm_funnel_pipeline',
      ], $body );
    } else {
      $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
      ];
      wp_mail( $to, $subject, $body, $headers );
    }
  }

  // ── Funnel status sync ─────────────────────────────────────────────

  /**
   * Handle deal stage update from a Zoho webhook.
   *
   * Detects deal/stage data in the payload and updates the local
   * organization's funnel status accordingly.
   *
   * @param array $body Webhook payload.
   * @return array|false Response array if handled, false if not a deal stage event.
   */
  private static function handle_deal_stage_update( $body ) {
    if ( get_option( 'pn_customers_manager_zoho_funnel_sync', '' ) !== 'on' ) {
      return false;
    }

    // Zoho deal webhooks: detect by module=Deals or Stage field presence.
    $data = $body;
    if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
      $data = $body['data'];
    }

    $module   = isset( $body['module'] ) ? $body['module'] : '';
    $stage    = isset( $data['Stage'] ) ? $data['Stage'] : '';
    $deal_id  = isset( $data['id'] ) ? $data['id'] : '';
    $zoho_contact_id = isset( $data['Contact_Name']['id'] ) ? $data['Contact_Name']['id'] : '';

    // Also check for flat contact_id field.
    if ( empty( $zoho_contact_id ) && isset( $data['contact_id'] ) ) {
      $zoho_contact_id = $data['contact_id'];
    }

    if ( empty( $stage ) || ( empty( $deal_id ) && $module !== 'Deals' ) ) {
      return false;
    }

    self::log( 'Deal stage update: dealId=' . $deal_id . ', stage=' . $stage . ', contactId=' . $zoho_contact_id );

    // Find local organization by Zoho contact ID.
    $org_id = false;
    if ( ! empty( $zoho_contact_id ) ) {
      $org_id = self::find_org_by_zoho_id( $zoho_contact_id );
    }

    if ( ! $org_id ) {
      self::log( 'Deal stage update: no local org found for Zoho contact ' . $zoho_contact_id );
      return false;
    }

    // Map to local funnel state.
    if ( ! class_exists( 'PN_CUSTOMERS_MANAGER_Settings' ) ) {
      return false;
    }
    $local_state_id = PN_CUSTOMERS_MANAGER_Settings::resolve_funnel_state_id( '', $stage );

    if ( empty( $local_state_id ) ) {
      self::log( 'Deal stage update: no matching local funnel state for stage "' . $stage . '"' );
      return [ 'status' => 'ignored', 'reason' => 'no matching funnel state' ];
    }

    // Set sync lock.
    set_transient( 'pn_cm_funnel_sync_lock_' . $org_id, 1, 30 );

    // Update local funnel status.
    update_post_meta( $org_id, 'pn_cm_organization_funnel_status', $local_state_id );

    // Store deal ID.
    if ( ! empty( $deal_id ) ) {
      update_post_meta( $org_id, 'pn_cm_organization_zoho_deal_id', sanitize_text_field( $deal_id ) );
    }

    self::log( 'Deal stage update: org #' . $org_id . ' funnel status set to "' . $local_state_id . '" (stage: ' . $stage . ')' );

    return [ 'status' => 'ok', 'action' => 'funnel_status_updated', 'org_id' => $org_id, 'state' => $local_state_id ];
  }

  /**
   * PUSH: React to local funnel status changes and update Zoho deal.
   *
   * Hooked to updated_post_meta and added_post_meta.
   *
   * @param int    $meta_id    Meta ID.
   * @param int    $object_id  Post ID.
   * @param string $meta_key   Meta key.
   * @param mixed  $meta_value Meta value.
   */
  public static function on_funnel_status_changed( $meta_id, $object_id, $meta_key, $meta_value ) {
    if ( $meta_key !== 'pn_cm_organization_funnel_status' ) {
      return;
    }
    if ( get_post_type( $object_id ) !== 'pn_cm_organization' ) {
      return;
    }
    if ( get_option( 'pn_customers_manager_zoho_funnel_sync', '' ) !== 'on' ) {
      return;
    }

    // Check sync lock.
    if ( get_transient( 'pn_cm_funnel_sync_lock_' . $object_id ) ) {
      return;
    }

    $deal_id = get_post_meta( $object_id, 'pn_cm_organization_zoho_deal_id', true );
    if ( empty( $deal_id ) ) {
      return;
    }

    // Map local state name to Zoho stage name.
    $stage_name = self::map_local_state_to_zoho_stage( $meta_value );
    if ( empty( $stage_name ) ) {
      self::log( 'Push funnel: no Zoho stage match for local state "' . $meta_value . '" (org #' . $object_id . ')' );
      return;
    }

    // Update Zoho deal.
    $result = self::api_request( 'PUT', '/crm/v2/Deals', [
      'data' => [ [ 'id' => $deal_id, 'Stage' => $stage_name ] ],
    ] );

    if ( is_wp_error( $result ) ) {
      self::log( 'Push funnel error (org #' . $object_id . '): ' . $result->get_error_message() );
    } else {
      self::log( 'Push funnel: org #' . $object_id . ' → Zoho deal ' . $deal_id . ' stage set to "' . $stage_name . '"' );
    }
  }

  /**
   * Map a local funnel state ID to a Zoho deal stage name.
   *
   * Uses the local state name directly, since Zoho deal stages are
   * matched by their display_value/name.
   *
   * @param string $local_state_id Local funnel state ID.
   * @return string Zoho stage name, or empty string.
   */
  private static function map_local_state_to_zoho_stage( $local_state_id ) {
    if ( ! class_exists( 'PN_CUSTOMERS_MANAGER_Settings' ) ) {
      return '';
    }

    $states = PN_CUSTOMERS_MANAGER_Settings::pn_customers_manager_get_funnel_states_config();
    foreach ( $states as $state ) {
      if ( $state['id'] === $local_state_id ) {
        return $state['name'];
      }
    }

    return '';
  }

  // ── AJAX handlers ──────────────────────────────────────────────────

  /**
   * Log a Zoho message to a dedicated log file (zoho.log).
   *
   * Always writes to wp-content/uploads/pn-customers-manager-logs/zoho.log
   * regardless of WP_DEBUG, matching the GHL logging pattern.
   */
  private static function log( $message ) {
    $upload_dir = wp_upload_dir();
    $log_dir    = $upload_dir['basedir'] . '/pn-customers-manager-logs';

    if ( ! file_exists( $log_dir ) ) {
      wp_mkdir_p( $log_dir );
      file_put_contents( $log_dir . '/.htaccess', 'deny from all' );
    }

    $log_file = $log_dir . '/zoho.log';
    $time     = current_time( 'Y-m-d H:i:s' );
    $line     = '[' . $time . '] ' . $message . "\n";

    // Rotate if file exceeds 5 MB.
    if ( file_exists( $log_file ) && filesize( $log_file ) > 5242880 ) {
      $contents = file_get_contents( $log_file );
      file_put_contents( $log_file, substr( $contents, -2621440 ) );
    }

    file_put_contents( $log_file, $line, FILE_APPEND | LOCK_EX );
  }

  /**
   * AJAX: Test Zoho connection.
   */
  public static function ajax_test() {
    try {
      echo wp_json_encode( self::test_connection() );
    } catch ( \Throwable $e ) {
      self::log( 'ajax_test exception: ' . $e->getMessage() );
      echo wp_json_encode( [
        'error_key'     => 'zoho_exception',
        'error_content' => $e->getMessage(),
      ] );
    }
    exit;
  }

  /**
   * AJAX: Push all to Zoho.
   */
  public static function ajax_push_all() {
    try {
      $direction = get_option( 'pn_customers_manager_zoho_sync_direction', 'both' );

      if ( $direction === 'pull' ) {
        echo wp_json_encode( [
          'error_key'     => 'zoho_direction',
          'error_content' => __( 'Push is disabled. Sync direction is set to Pull only.', 'pn-customers-manager' ),
        ] );
        exit;
      }

      $summary = self::push_all();

      echo wp_json_encode( [
        'error_key' => '',
        'message'   => sprintf(
          // translators: %1$d is organizations pushed, %2$d is organization errors, %3$d is contacts pushed, %4$d is contact errors.
          __( 'Push complete. Organizations: %1$d pushed, %2$d errors. Contacts: %3$d pushed, %4$d errors.', 'pn-customers-manager' ),
          $summary['orgs_pushed'], $summary['orgs_errors'],
          $summary['contacts_pushed'], $summary['contacts_errors']
        ),
      ] );
    } catch ( \Throwable $e ) {
      self::log( 'ajax_push_all exception: ' . $e->getMessage() );
      echo wp_json_encode( [
        'error_key'     => 'zoho_exception',
        'error_content' => $e->getMessage(),
      ] );
    }
    exit;
  }

  /**
   * AJAX: Pull all from Zoho.
   */
  public static function ajax_pull_all() {
    try {
      $direction = get_option( 'pn_customers_manager_zoho_sync_direction', 'both' );

      if ( $direction === 'push' ) {
        echo wp_json_encode( [
          'error_key'     => 'zoho_direction',
          'error_content' => __( 'Pull is disabled. Sync direction is set to Push only.', 'pn-customers-manager' ),
        ] );
        exit;
      }

      $page = isset( $_POST['pull_page'] ) ? absint( $_POST['pull_page'] ) : 1;
      if ( $page < 1 ) {
        $page = 1;
      }

      self::log( 'Pull batch started (page=' . $page . ')' );
      $page_result = self::pull_contacts_page( $page );
      $summary     = $page_result['summary'];
      self::log( 'Pull batch finished: ' . wp_json_encode( $summary ) );

      echo wp_json_encode( [
        'error_key' => '',
        'summary'   => $summary,
        'page'      => $page,
        'has_more'  => $page_result['has_more'],
      ] );
    } catch ( \Throwable $e ) {
      self::log( 'ajax_pull_all exception: ' . $e->getMessage() );
      echo wp_json_encode( [
        'error_key'     => 'zoho_exception',
        'error_content' => $e->getMessage(),
      ] );
    }
    exit;
  }

  // ── Deal stages sync ────────────────────────────────────────────────

  /**
   * Fetch deal/pipeline stages from Zoho CRM.
   *
   * Tries /crm/v2/settings/deal_stages first; falls back to
   * /crm/v2/settings/stages?module=Deals for older Zoho setups.
   *
   * @return array|WP_Error Array of stages.
   */
  public static function get_deal_stages() {
    // Try the pipeline endpoint first (newer Zoho setups).
    $result = self::api_request( 'GET', '/crm/v2/settings/pipeline?module=Deals' );
    if ( ! is_wp_error( $result ) && ! empty( $result['pipeline'] ) ) {
      $output = [];
      foreach ( $result['pipeline'] as $pipeline ) {
        $stages = [];
        if ( ! empty( $pipeline['maps'] ) && is_array( $pipeline['maps'] ) ) {
          foreach ( $pipeline['maps'] as $map ) {
            $stages[] = [
              'id'   => isset( $map['id'] ) ? (string) $map['id'] : sanitize_title( $map['display_value'] ),
              'name' => isset( $map['display_value'] ) ? $map['display_value'] : ( isset( $map['actual_value'] ) ? $map['actual_value'] : '' ),
            ];
          }
        }
        $output[] = [
          'name'   => isset( $pipeline['display_value'] ) ? $pipeline['display_value'] : '',
          'stages' => $stages,
        ];
      }
      if ( ! empty( $output ) ) {
        return $output;
      }
    }

    // Fallback: /crm/v2/settings/stages endpoint.
    $result2 = self::api_request( 'GET', '/crm/v2/settings/stages?module=Deals' );
    if ( is_wp_error( $result2 ) ) {
      return $result2;
    }

    $stages_data = isset( $result2['stages'] ) ? $result2['stages'] : ( isset( $result2['stage'] ) ? $result2['stage'] : [] );
    if ( empty( $stages_data ) ) {
      return new WP_Error( 'zoho_no_stages', __( 'No deal stages found in Zoho CRM.', 'pn-customers-manager' ) );
    }

    $stages = [];
    foreach ( $stages_data as $s ) {
      $stages[] = [
        'id'   => isset( $s['id'] ) ? (string) $s['id'] : sanitize_title( $s['display_value'] ?? $s['name'] ?? '' ),
        'name' => isset( $s['display_value'] ) ? $s['display_value'] : ( isset( $s['name'] ) ? $s['name'] : '' ),
      ];
    }

    return [ [ 'name' => 'Deals', 'stages' => $stages ] ];
  }

  /**
   * AJAX: Fetch deal stages from Zoho and return as funnel states.
   */
  public static function ajax_get_deal_stages() {
    $pipelines = self::get_deal_stages();
    if ( is_wp_error( $pipelines ) ) {
      echo wp_json_encode( [
        'error_key'     => 'zoho_error',
        'error_content' => $pipelines->get_error_message(),
      ] );
      return;
    }

    $colors = [ '#6c757d', '#0d6efd', '#6f42c1', '#fd7e14', '#e83e8c', '#198754', '#dc3545', '#20c997', '#0dcaf0', '#ffc107' ];
    $states = [];
    $idx    = 0;
    foreach ( $pipelines as $pipeline ) {
      foreach ( $pipeline['stages'] as $stage ) {
        $states[] = [
          'id'          => $stage['id'],
          'name'        => $stage['name'],
          'color'       => $colors[ $idx % count( $colors ) ],
          'description' => ! empty( $pipeline['name'] ) ? $pipeline['name'] : '',
          'enabled'     => true,
        ];
        $idx++;
      }
    }

    echo wp_json_encode( [
      'error_key' => '',
      'states'    => $states,
      'pipelines' => $pipelines,
    ] );
  }
}
