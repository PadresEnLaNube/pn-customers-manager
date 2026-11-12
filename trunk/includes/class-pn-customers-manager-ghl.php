<?php
/**
 * Go High Level CRM Connector.
 *
 * Handles bidirectional sync between the plugin's Organizations/Contacts
 * and Go High Level contacts via the GHL REST API.
 *
 * @since      1.2.47
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 */
class PN_CUSTOMERS_MANAGER_GHL {

  const API_BASE = 'https://services.leadconnectorhq.com';

  /**
   * Perform an authenticated request to the GHL API.
   *
   * @param string $method   HTTP method (GET, POST, PUT, DELETE).
   * @param string $endpoint API endpoint path (e.g. /contacts/).
   * @param array  $body     Optional request body.
   * @return array|WP_Error  Decoded JSON response or WP_Error.
   */
  public static function api_request( $method, $endpoint, $body = null ) {
    $api_key = get_option( 'pn_customers_manager_ghl_api_key', '' );

    if ( empty( $api_key ) ) {
      return new WP_Error( 'ghl_no_key', __( 'Go High Level API Key is not configured.', 'pn-customers-manager' ) );
    }

    $args = [
      'method'  => strtoupper( $method ),
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json',
        'Version'       => '2021-07-28',
      ],
      'timeout' => 30,
    ];

    if ( $body !== null ) {
      $args['body'] = wp_json_encode( $body );
    }

    $url      = self::API_BASE . $endpoint;
    $response = wp_remote_request( $url, $args );

    if ( is_wp_error( $response ) ) {
      return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $code < 200 || $code >= 300 ) {
      $message = isset( $data['message'] ) ? $data['message'] : ( isset( $data['msg'] ) ? $data['msg'] : wp_remote_retrieve_body( $response ) );
      // translators: %1$d is the HTTP status code, %2$s is the error message from the GHL API.
      return new WP_Error( 'ghl_api_error', sprintf( __( 'GHL API error (%1$d): %2$s', 'pn-customers-manager' ), $code, $message ) );
    }

    return $data;
  }

  /**
   * Test the GHL connection by fetching a single contact.
   *
   * @return array Result array with error_key and message.
   */
  public static function test_connection() {
    $location_id = get_option( 'pn_customers_manager_ghl_location_id', '' );

    if ( empty( $location_id ) ) {
      return [
        'error_key'     => 'ghl_no_location',
        'error_content' => __( 'Location ID is not configured.', 'pn-customers-manager' ),
      ];
    }

    $result = self::api_request( 'GET', '/contacts/?locationId=' . urlencode( $location_id ) . '&limit=1' );

    if ( is_wp_error( $result ) ) {
      return [
        'error_key'     => 'ghl_test_failed',
        'error_content' => $result->get_error_message(),
      ];
    }

    $total = isset( $result['meta']['total'] ) ? intval( $result['meta']['total'] ) : ( isset( $result['total'] ) ? intval( $result['total'] ) : '?' );

    return [
      'error_key' => '',
      // translators: %s is the number of contacts found in the GHL account.
      'message'   => sprintf( __( 'Connection successful. %s contacts found in GHL.', 'pn-customers-manager' ), $total ),
    ];
  }

  // ── Notes ─────────────────────────────────────────────────────────

  /**
   * Create a note on a GHL contact.
   *
   * @param string $ghl_contact_id GHL contact ID.
   * @param string $body           Note body text.
   * @return bool True on success, false on failure.
   */
  public static function create_note( $ghl_contact_id, $body ) {
    if ( empty( $ghl_contact_id ) ) {
      return false;
    }
    $result = self::api_request( 'POST', '/contacts/' . $ghl_contact_id . '/notes', [
      'body' => $body,
    ] );
    return ! is_wp_error( $result );
  }

  /**
   * Find a GHL contact ID by phone number.
   *
   * Searches organizations (post meta pn_cm_organization_phone) and
   * users (user meta phone) for a matching phone number, then returns
   * the corresponding GHL contact ID.
   *
   * @param string $phone Phone number to search for.
   * @return string GHL contact ID or empty string.
   */
  public static function find_ghl_contact_id_by_phone( $phone ) {
    $phone_clean = preg_replace( '/[^\d]/', '', $phone );
    if ( empty( $phone_clean ) ) {
      return '';
    }

    global $wpdb;

    // Check organizations.
    $org_id = $wpdb->get_var( $wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta}
       WHERE meta_key = 'pn_cm_organization_phone'
       AND REPLACE(REPLACE(REPLACE(meta_value, '+', ''), ' ', ''), '-', '') LIKE %s
       LIMIT 1",
      '%' . $wpdb->esc_like( $phone_clean ) . '%'
    ) );
    if ( $org_id ) {
      $ghl_id = get_post_meta( $org_id, 'pn_cm_organization_ghl_id', true );
      if ( ! empty( $ghl_id ) ) {
        return $ghl_id;
      }
    }

    // Check users.
    $user_id = $wpdb->get_var( $wpdb->prepare(
      "SELECT user_id FROM {$wpdb->usermeta}
       WHERE meta_key = 'phone'
       AND REPLACE(REPLACE(REPLACE(meta_value, '+', ''), ' ', ''), '-', '') LIKE %s
       LIMIT 1",
      '%' . $wpdb->esc_like( $phone_clean ) . '%'
    ) );
    if ( $user_id ) {
      $ghl_id = get_user_meta( $user_id, 'pn_cm_ghl_contact_id', true );
      if ( ! empty( $ghl_id ) ) {
        return $ghl_id;
      }
    }

    return '';
  }

  // ── Conversations ────────────────────────────────────────────────

  /**
   * Create a conversation in GHL for a contact.
   *
   * @param string $ghl_contact_id GHL contact ID.
   * @return string Conversation ID on success, empty string on failure.
   */
  public static function create_conversation( $ghl_contact_id ) {
    $location_id = get_option( 'pn_customers_manager_ghl_location_id', '' );
    if ( empty( $ghl_contact_id ) || empty( $location_id ) ) {
      return '';
    }

    $result = self::api_request( 'POST', '/conversations/', [
      'locationId' => $location_id,
      'contactId'  => $ghl_contact_id,
    ] );

    if ( is_wp_error( $result ) ) {
      self::log( 'create_conversation error for contact ' . $ghl_contact_id . ': ' . $result->get_error_message() );
      return '';
    }

    $conversation_id = isset( $result['conversation']['id'] ) ? $result['conversation']['id'] : ( isset( $result['id'] ) ? $result['id'] : '' );
    self::log( 'create_conversation: contact=' . $ghl_contact_id . ', conversationId=' . $conversation_id );

    return $conversation_id;
  }

  /**
   * Add a message to a GHL conversation.
   *
   * @param string $ghl_contact_id GHL contact ID.
   * @param string $message        Message text.
   * @param string $direction      'inbound' (customer) or 'outbound' (AI response).
   * @return bool True on success, false on failure.
   */
  public static function add_conversation_message( $ghl_contact_id, $message, $direction = 'inbound' ) {
    if ( empty( $ghl_contact_id ) || empty( $message ) ) {
      return false;
    }

    $provider_id = get_option( 'pn_customers_manager_ghl_conversation_provider_id', '' );

    if ( $direction === 'inbound' ) {
      $result = self::api_request( 'POST', '/conversations/messages/inbound', [
        'type'                   => 'WhatsApp',
        'contactId'              => $ghl_contact_id,
        'conversationProviderId' => $provider_id,
        'message'                => $message,
      ] );
    } else {
      $result = self::api_request( 'POST', '/conversations/messages', [
        'type'      => 'WhatsApp',
        'contactId' => $ghl_contact_id,
        'message'   => $message,
      ] );
    }

    if ( is_wp_error( $result ) ) {
      self::log( 'add_conversation_message (' . $direction . ') error for contact ' . $ghl_contact_id . ': ' . $result->get_error_message() );
      return false;
    }

    self::log( 'add_conversation_message (' . $direction . '): contact=' . $ghl_contact_id . ' — ok' );
    return true;
  }

  // ── Field mapping ──────────────────────────────────────────────────

  /**
   * Map organization post meta to a GHL contact payload.
   */
  public static function map_org_to_ghl( $org_id ) {
    $location_id = get_option( 'pn_customers_manager_ghl_location_id', '' );

    $payload = [
      'locationId'  => $location_id,
      'name'        => get_the_title( $org_id ),
      'companyName' => get_post_meta( $org_id, 'pn_cm_organization_legal_name', true ) ?: get_the_title( $org_id ),
      'email'       => get_post_meta( $org_id, 'pn_cm_organization_email', true ),
      'phone'       => get_post_meta( $org_id, 'pn_cm_organization_phone', true ),
      'website'     => get_post_meta( $org_id, 'pn_cm_organization_website', true ),
      'address1'    => get_post_meta( $org_id, 'pn_cm_organization_address', true ),
      'city'        => get_post_meta( $org_id, 'pn_cm_organization_city', true ),
      'state'       => get_post_meta( $org_id, 'pn_cm_organization_region', true ),
      'postalCode'  => get_post_meta( $org_id, 'pn_cm_organization_postal_code', true ),
      'country'     => get_post_meta( $org_id, 'pn_cm_organization_country', true ),
    ];

    // Remove empty values to avoid overwriting GHL data with blanks.
    return array_filter( $payload, function( $v ) { return $v !== '' && $v !== null; } );
  }

  /**
   * Map WP user data to a GHL contact payload.
   */
  public static function map_user_to_ghl( $user_id ) {
    $user        = get_userdata( $user_id );
    $location_id = get_option( 'pn_customers_manager_ghl_location_id', '' );

    if ( ! $user ) {
      return [];
    }

    $phone = get_user_meta( $user_id, 'phone', true );
    if ( empty( $phone ) ) {
      $phone = get_user_meta( $user_id, 'billing_phone', true );
    }

    $payload = [
      'locationId' => $location_id,
      'firstName'  => $user->first_name,
      'lastName'   => $user->last_name,
      'email'      => $user->user_email,
      'phone'      => $phone,
    ];

    return array_filter( $payload, function( $v ) { return $v !== '' && $v !== null; } );
  }

  /**
   * Update organization post meta from GHL contact data.
   */
  public static function map_ghl_to_org( $ghl_contact, $org_id ) {
    $mapping = [
      'email'      => 'pn_cm_organization_email',
      'phone'      => 'pn_cm_organization_phone',
      'website'    => 'pn_cm_organization_website',
      'address1'   => 'pn_cm_organization_address',
      'city'       => 'pn_cm_organization_city',
      'state'      => 'pn_cm_organization_region',
      'postalCode' => 'pn_cm_organization_postal_code',
      'country'    => 'pn_cm_organization_country',
    ];

    foreach ( $mapping as $ghl_key => $meta_key ) {
      if ( ! empty( $ghl_contact[ $ghl_key ] ) ) {
        update_post_meta( $org_id, $meta_key, sanitize_text_field( $ghl_contact[ $ghl_key ] ) );
      }
    }

    if ( ! empty( $ghl_contact['companyName'] ) ) {
      update_post_meta( $org_id, 'pn_cm_organization_legal_name', sanitize_text_field( $ghl_contact['companyName'] ) );
    }

    // Update organization title from name field.
    if ( ! empty( $ghl_contact['name'] ) ) {
      wp_update_post( [
        'ID'         => $org_id,
        'post_title' => sanitize_text_field( $ghl_contact['name'] ),
      ] );
    }

    // Store GHL contact ID.
    if ( ! empty( $ghl_contact['id'] ) ) {
      update_post_meta( $org_id, 'pn_cm_organization_ghl_id', sanitize_text_field( $ghl_contact['id'] ) );
    }
  }

  /**
   * Update WP user data from GHL contact data.
   */
  public static function map_ghl_to_user( $ghl_contact, $user_id ) {
    $update_data = [ 'ID' => $user_id ];

    if ( ! empty( $ghl_contact['firstName'] ) ) {
      $update_data['first_name'] = sanitize_text_field( $ghl_contact['firstName'] );
    }
    if ( ! empty( $ghl_contact['lastName'] ) ) {
      $update_data['last_name'] = sanitize_text_field( $ghl_contact['lastName'] );
    }
    if ( ! empty( $ghl_contact['email'] ) ) {
      $update_data['user_email'] = sanitize_email( $ghl_contact['email'] );
    }

    // Build display_name from available name data.
    if ( ! empty( $ghl_contact['name'] ) ) {
      $update_data['display_name'] = sanitize_text_field( $ghl_contact['name'] );
    } elseif ( ! empty( $ghl_contact['firstName'] ) || ! empty( $ghl_contact['lastName'] ) ) {
      $first = isset( $ghl_contact['firstName'] ) ? $ghl_contact['firstName'] : '';
      $last  = isset( $ghl_contact['lastName'] ) ? $ghl_contact['lastName'] : '';
      $update_data['display_name'] = trim( $first . ' ' . $last );
    }

    wp_update_user( $update_data );

    if ( ! empty( $ghl_contact['phone'] ) ) {
      update_user_meta( $user_id, 'phone', sanitize_text_field( $ghl_contact['phone'] ) );
    }

    // Store GHL contact ID.
    if ( ! empty( $ghl_contact['id'] ) ) {
      update_user_meta( $user_id, 'pn_cm_ghl_contact_id', sanitize_text_field( $ghl_contact['id'] ) );
    }

    // Track last profile update for template inactivity checks.
    update_user_meta( $user_id, 'pn_cm_profile_updated', current_time( 'mysql' ) );
  }

  // ── Push to GHL ────────────────────────────────────────────────────

  /**
   * Push a single organization to GHL.
   *
   * @param int $org_id Organization post ID.
   * @return array|WP_Error GHL API response.
   */
  public static function push_organization( $org_id ) {
    $payload    = self::map_org_to_ghl( $org_id );
    $ghl_id     = get_post_meta( $org_id, 'pn_cm_organization_ghl_id', true );

    if ( ! empty( $ghl_id ) ) {
      // Update existing GHL contact.
      unset( $payload['locationId'] );
      $result = self::api_request( 'PUT', '/contacts/' . $ghl_id, $payload );
    } else {
      // Create new GHL contact.
      $result = self::api_request( 'POST', '/contacts/', $payload );
    }

    if ( is_wp_error( $result ) ) {
      return $result;
    }

    // Store the GHL contact ID.
    $contact_id = isset( $result['contact']['id'] ) ? $result['contact']['id'] : ( isset( $result['id'] ) ? $result['id'] : '' );
    if ( ! empty( $contact_id ) ) {
      update_post_meta( $org_id, 'pn_cm_organization_ghl_id', sanitize_text_field( $contact_id ) );
    }

    return $result;
  }

  /**
   * Push a single WP user contact to GHL.
   *
   * @param int $user_id WordPress user ID.
   * @return array|WP_Error GHL API response.
   */
  public static function push_contact( $user_id ) {
    $payload = self::map_user_to_ghl( $user_id );
    $ghl_id  = get_user_meta( $user_id, 'pn_cm_ghl_contact_id', true );

    if ( ! empty( $ghl_id ) ) {
      unset( $payload['locationId'] );
      $result = self::api_request( 'PUT', '/contacts/' . $ghl_id, $payload );
    } else {
      $result = self::api_request( 'POST', '/contacts/', $payload );
    }

    if ( is_wp_error( $result ) ) {
      return $result;
    }

    $contact_id = isset( $result['contact']['id'] ) ? $result['contact']['id'] : ( isset( $result['id'] ) ? $result['id'] : '' );
    if ( ! empty( $contact_id ) ) {
      update_user_meta( $user_id, 'pn_cm_ghl_contact_id', sanitize_text_field( $contact_id ) );
    }

    return $result;
  }

  /**
   * Push all organizations and their contacts to GHL.
   *
   * @return array Summary with counts.
   */
  public static function push_all() {
    $summary = [
      'orgs_pushed'     => 0,
      'orgs_errors'     => 0,
      'contacts_pushed' => 0,
      'contacts_errors' => 0,
    ];

    // Push all organizations.
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

      // Push contacts linked to this organization.
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

  // ── Pull from GHL ──────────────────────────────────────────────────

  /**
   * Pull all contacts from GHL and create/update local records.
   *
   * @return array Summary with counts.
   */
  /**
   * Pull one page of contacts from GHL.
   *
   * @param string $cursor  The startAfterId cursor (empty for first page).
   * @param int    $limit   Contacts per page.
   * @return array { summary: array, cursor: string, has_more: bool }
   */
  public static function pull_contacts_page( $cursor = '', $limit = 100 ) {
    $location_id = get_option( 'pn_customers_manager_ghl_location_id', '' );
    $summary     = [
      'orgs_created'     => 0,
      'orgs_updated'     => 0,
      'contacts_created' => 0,
      'contacts_updated' => 0,
      'errors'           => 0,
    ];

    $url = '/contacts/?locationId=' . urlencode( $location_id ) . '&limit=' . $limit;
    if ( ! empty( $cursor ) ) {
      $url .= '&startAfterId=' . urlencode( $cursor );
    }

    self::log( 'Pull page (cursor=' . $cursor . '): ' . $url );
    $result = self::api_request( 'GET', $url );

    if ( is_wp_error( $result ) ) {
      self::log( 'Pull API error: ' . $result->get_error_message() );
      $summary['errors']++;
      $summary['last_error'] = $result->get_error_message();
      return [ 'summary' => $summary, 'cursor' => '', 'has_more' => false ];
    }

    $contacts = isset( $result['contacts'] ) ? $result['contacts'] : [];
    self::log( 'Pull page: ' . count( $contacts ) . ' contacts received' );

    if ( empty( $contacts ) ) {
      return [ 'summary' => $summary, 'cursor' => '', 'has_more' => false ];
    }

    foreach ( $contacts as $ghl_contact ) {
      $contact_name = isset( $ghl_contact['name'] ) ? $ghl_contact['name'] : ( isset( $ghl_contact['email'] ) ? $ghl_contact['email'] : 'unknown' );
      $contact_id   = isset( $ghl_contact['id'] ) ? $ghl_contact['id'] : 'no-id';

      $processed = self::process_pulled_contact( $ghl_contact );
      if ( is_wp_error( $processed ) ) {
        self::log( 'Pull error for contact ' . $contact_id . ' (' . $contact_name . '): ' . $processed->get_error_message() );
        $summary['errors']++;
        $summary['last_error'] = $contact_name . ': ' . $processed->get_error_message();
      } else {
        $summary[ $processed ]++;
        self::set_client_meta_after_save( $ghl_contact, 'ghl', $processed );
      }
    }

    $last_contact = end( $contacts );
    $next_cursor  = isset( $last_contact['id'] ) ? $last_contact['id'] : '';
    $has_more     = isset( $result['meta']['nextPageUrl'] ) && ! empty( $result['meta']['nextPageUrl'] );

    return [ 'summary' => $summary, 'cursor' => $next_cursor, 'has_more' => $has_more ];
  }

  /**
   * Pull all contacts from GHL (all pages at once).
   *
   * @return array Summary with counts.
   */
  public static function pull_contacts() {
    $summary = [
      'orgs_created'     => 0,
      'orgs_updated'     => 0,
      'contacts_created' => 0,
      'contacts_updated' => 0,
      'errors'           => 0,
    ];

    $cursor = '';

    do {
      $page_result = self::pull_contacts_page( $cursor );
      foreach ( [ 'orgs_created', 'orgs_updated', 'contacts_created', 'contacts_updated', 'errors' ] as $key ) {
        $summary[ $key ] += $page_result['summary'][ $key ];
      }
      if ( ! empty( $page_result['summary']['last_error'] ) ) {
        $summary['last_error'] = $page_result['summary']['last_error'];
      }
      $cursor = $page_result['cursor'];
    } while ( $page_result['has_more'] );

    return $summary;
  }

  /**
   * Pull a single contact from GHL by their GHL contact ID.
   *
   * @param string $ghl_contact_id GHL contact ID.
   * @return array|WP_Error Result.
   */
  public static function pull_single( $ghl_contact_id ) {
    $result = self::api_request( 'GET', '/contacts/' . urlencode( $ghl_contact_id ) );

    if ( is_wp_error( $result ) ) {
      return $result;
    }

    $contact = isset( $result['contact'] ) ? $result['contact'] : $result;
    return self::process_pulled_contact( $contact );
  }

  /**
   * Process a single pulled GHL contact: match or create org/user.
   *
   * @param array $ghl_contact GHL contact data.
   * @return string|WP_Error  Key indicating action taken.
   */
  private static function process_pulled_contact( $ghl_contact ) {
    $ghl_id = isset( $ghl_contact['id'] ) ? $ghl_contact['id'] : '';

    if ( empty( $ghl_id ) ) {
      return new WP_Error( 'ghl_no_id', 'Contact has no ID.' );
    }

    // Check if this GHL contact already maps to a local organization.
    $existing_org = self::find_org_by_ghl_id( $ghl_id );
    if ( $existing_org ) {
      self::map_ghl_to_org( $ghl_contact, $existing_org );
      return 'orgs_updated';
    }

    // Check if this GHL contact already maps to a local user.
    $existing_user = self::find_user_by_ghl_id( $ghl_id );
    if ( $existing_user ) {
      self::map_ghl_to_user( $ghl_contact, $existing_user );
      return 'contacts_updated';
    }

    // Before creating anything, check if an organization already exists for
    // this phone number (may have been created by WhatsApp AI sync without
    // a GHL ID). This prevents duplicate organizations.
    $contact_phone = isset( $ghl_contact['phone'] ) ? $ghl_contact['phone'] : '';
    if ( ! empty( $contact_phone ) ) {
      $existing_org_by_phone = self::find_org_by_phone( $contact_phone );
      if ( $existing_org_by_phone ) {
        self::map_ghl_to_org( $ghl_contact, $existing_org_by_phone );
        self::log( 'Linked existing org #' . $existing_org_by_phone . ' (found by phone ' . $contact_phone . ') to GHL contact ' . $ghl_id );
        return 'orgs_updated';
      }
    }

    // Also check by email if an organization exists with that email.
    $contact_email_check = isset( $ghl_contact['email'] ) ? $ghl_contact['email'] : '';
    if ( ! empty( $contact_email_check ) ) {
      $existing_org_by_email = self::find_org_by_email( $contact_email_check );
      if ( $existing_org_by_email ) {
        self::map_ghl_to_org( $ghl_contact, $existing_org_by_email );
        self::log( 'Linked existing org #' . $existing_org_by_email . ' (found by email ' . $contact_email_check . ') to GHL contact ' . $ghl_id );
        return 'orgs_updated';
      }
    }

    // Check if a user already exists with this phone number.
    if ( ! empty( $contact_phone ) ) {
      $existing_user_by_phone = self::find_user_by_phone( $contact_phone );
      if ( $existing_user_by_phone ) {
        self::map_ghl_to_user( $ghl_contact, $existing_user_by_phone );
        self::log( 'Linked existing user #' . $existing_user_by_phone . ' (found by phone ' . $contact_phone . ') to GHL contact ' . $ghl_id );
        return 'contacts_updated';
      }
    }

    // Check if a user already exists with this email.
    if ( ! empty( $contact_email_check ) && email_exists( $contact_email_check ) ) {
      $user_id = get_user_by( 'email', $contact_email_check )->ID;
      self::map_ghl_to_user( $ghl_contact, $user_id );
      self::log( 'Linked existing user #' . $user_id . ' (found by email ' . $contact_email_check . ') to GHL contact ' . $ghl_id );
      return 'contacts_updated';
    }

    // No existing match — decide if this is an organization or individual.
    $has_company = ! empty( $ghl_contact['companyName'] );

    if ( $has_company ) {
      // Create new organization.
      $org_id = wp_insert_post( [
        'post_type'   => 'pn_cm_organization',
        'post_title'  => sanitize_text_field( $ghl_contact['companyName'] ),
        'post_status' => 'publish',
      ] );

      if ( is_wp_error( $org_id ) ) {
        return $org_id;
      }

      self::map_ghl_to_org( $ghl_contact, $org_id );
      return 'orgs_created';
    } else {
      // Create new user contact.
      $email      = isset( $ghl_contact['email'] ) ? sanitize_email( $ghl_contact['email'] ) : '';
      $first_name = isset( $ghl_contact['firstName'] ) ? sanitize_text_field( $ghl_contact['firstName'] ) : '';
      $last_name  = isset( $ghl_contact['lastName'] ) ? sanitize_text_field( $ghl_contact['lastName'] ) : '';
      $login      = ! empty( $email ) ? $email : 'ghl_' . $ghl_id;
      $password   = wp_generate_password( 24 );

      // Build display_name from available name data.
      if ( ! empty( $ghl_contact['name'] ) ) {
        $display_name = sanitize_text_field( $ghl_contact['name'] );
      } else {
        $display_name = trim( $first_name . ' ' . $last_name );
      }

      $user_id = PN_CUSTOMERS_MANAGER_Functions_User::pn_customers_manager_user_insert(
        $login,
        $password,
        $email,
        $first_name,
        $last_name,
        $display_name
      );

      if ( ! $user_id || is_wp_error( $user_id ) ) {
        return new WP_Error( 'ghl_user_create_failed', 'Failed to create user for GHL contact ' . $ghl_id );
      }

      self::map_ghl_to_user( $ghl_contact, $user_id );
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

  /**
   * Find an organization by its stored GHL contact ID.
   */
  private static function find_org_by_ghl_id( $ghl_id ) {
    $posts = get_posts( [
      'post_type'      => 'pn_cm_organization',
      'posts_per_page' => 1,
      'post_status'    => 'any',
      'meta_key'       => 'pn_cm_organization_ghl_id',
      'meta_value'     => $ghl_id,
      'fields'         => 'ids',
    ] );

    return ! empty( $posts ) ? $posts[0] : false;
  }

  /**
   * Find a WP user by their stored GHL contact ID.
   */
  private static function find_user_by_ghl_id( $ghl_id ) {
    $users = get_users( [
      'meta_key'   => 'pn_cm_ghl_contact_id',
      'meta_value' => $ghl_id,
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

  // ── REST API webhook ─────────────────────────────────────────────

  /**
   * Register REST API routes for GHL webhooks.
   */
  public static function register_routes() {
    register_rest_route( 'pn-cm/v1', '/ghl/webhook', [
      'methods'             => 'POST',
      'callback'            => [ __CLASS__, 'handle_webhook' ],
      'permission_callback' => '__return_true',
    ] );
  }

  /**
   * Handle incoming GHL webhook requests.
   *
   * @param WP_REST_Request $request
   * @return WP_REST_Response
   */
  public static function handle_webhook( $request ) {
    // Process any overdue scheduled WhatsApp templates on every incoming webhook.
    // This ensures templates fire even when WP Cron is unreliable.
    if ( class_exists( 'PN_CUSTOMERS_MANAGER_WhatsApp_AI' ) ) {
      PN_CUSTOMERS_MANAGER_WhatsApp_AI::process_pending_template_events();
    }

    $body = $request->get_json_params();

    if ( empty( $body ) ) {
      $body = $request->get_body_params();
    }

    // If still empty, try reading raw body.
    if ( empty( $body ) ) {
      $raw = $request->get_body();
      self::log( 'Webhook raw body: ' . substr( $raw, 0, 2000 ) );
      $body = json_decode( $raw, true );
      if ( ! is_array( $body ) ) {
        // Try form-urlencoded.
        parse_str( $raw, $body );
      }
    }

    self::log( 'Webhook received (keys): ' . wp_json_encode( array_keys( $body ) ) );
    self::log( 'Webhook full payload: ' . wp_json_encode( $body ) );
    $ct = $request->get_content_type();
    self::log( 'Webhook Content-Type: ' . ( is_array( $ct ) && isset( $ct['value'] ) ? $ct['value'] : 'unknown' ) );

    // ── Opportunity stage update ────────────────────────────────────
    // Detect opportunity/pipeline stage change events and handle funnel sync.
    $opp_stage_result = self::handle_opportunity_stage_update( $body );
    if ( $opp_stage_result !== false ) {
      return new WP_REST_Response( $opp_stage_result, 200 );
    }

    // ── Normalize contact data ──────────────────────────────────────
    // GHL workflow webhooks send contact fields FLAT at the root level
    // (first_name, email, phone, contact_id, etc.) while the nested
    // "contact" key contains only attribution/tracking data.
    // GHL API webhooks (contact.create, contact.update) send the actual
    // contact data inside $body['contact'] with camelCase keys.
    // We detect which format we received by checking if the nested
    // "contact" object looks like real contact data (has email/firstName/id).
    $contact = $body;
    if ( isset( $body['contact'] ) && is_array( $body['contact'] ) ) {
      $nested = $body['contact'];
      // Only use the nested object if it contains actual contact fields.
      $has_contact_fields = ! empty( $nested['email'] )
        || ! empty( $nested['firstName'] )
        || ! empty( $nested['id'] )
        || ! empty( $nested['first_name'] )
        || ! empty( $nested['contact_id'] );
      if ( $has_contact_fields ) {
        $contact = $nested;
      }
    }

    // Merge customData fields (GHL workflows often put user-defined
    // fields like userspn_source, first_name, email there as well).
    if ( isset( $body['customData'] ) && is_array( $body['customData'] ) ) {
      foreach ( $body['customData'] as $cd_key => $cd_val ) {
        if ( $cd_val !== '' && $cd_val !== null && empty( $contact[ $cd_key ] ) ) {
          $contact[ $cd_key ] = $cd_val;
        }
      }
    }

    // ── Normalize alternative field names ──────────────────────────
    // GHL workflows or custom webhooks may send snake_case keys instead
    // of the native camelCase (e.g. first_name instead of firstName).
    $field_aliases = [
      'first_name'    => 'firstName',
      'last_name'     => 'lastName',
      'contact_id'    => 'id',
      'company_name'  => 'companyName',
      'company'       => 'companyName',
      'postal_code'   => 'postalCode',
      'full_name'     => 'name',
      'fullName'      => 'name',
      'contact_source'=> 'source',
    ];

    foreach ( $field_aliases as $alt_key => $canonical_key ) {
      if ( ! empty( $contact[ $alt_key ] ) && empty( $contact[ $canonical_key ] ) ) {
        $contact[ $canonical_key ] = $contact[ $alt_key ];
      }
    }

    // Store custom source field if provided (userspn_source).
    $custom_source = '';
    if ( ! empty( $contact['userspn_source'] ) ) {
      $custom_source = sanitize_text_field( $contact['userspn_source'] );
    } elseif ( ! empty( $body['userspn_source'] ) ) {
      $custom_source = sanitize_text_field( $body['userspn_source'] );
    }

    self::log( 'Webhook normalized contact: id=' . ( isset( $contact['id'] ) ? $contact['id'] : '' )
      . ', email=' . ( isset( $contact['email'] ) ? $contact['email'] : '' )
      . ', firstName=' . ( isset( $contact['firstName'] ) ? $contact['firstName'] : '' )
      . ', lastName=' . ( isset( $contact['lastName'] ) ? $contact['lastName'] : '' )
      . ', phone=' . ( isset( $contact['phone'] ) ? $contact['phone'] : '' )
      . ', source=' . $custom_source
    );

    // Build a name field if not present.
    if ( empty( $contact['name'] ) ) {
      $parts = array_filter( [
        isset( $contact['firstName'] ) ? $contact['firstName'] : '',
        isset( $contact['lastName'] ) ? $contact['lastName'] : '',
      ] );
      if ( $parts ) {
        $contact['name'] = implode( ' ', $parts );
      }
    }

    // Must have at least an id or email to process.
    if ( empty( $contact['id'] ) && empty( $contact['email'] ) ) {
      self::log( 'Webhook ignored: no contact id or email. Available keys after normalization: ' . wp_json_encode( array_keys( $contact ) ) );
      return new WP_REST_Response( [ 'status' => 'ignored', 'reason' => 'no contact data' ], 200 );
    }

    // If there is no id but there is an email, use email as temporary id.
    if ( empty( $contact['id'] ) ) {
      $contact['id'] = sanitize_email( $contact['email'] );
    }

    self::log( 'Webhook processing contact: id=' . $contact['id'] . ', email=' . ( isset( $contact['email'] ) ? $contact['email'] : '' ) . ', name=' . ( isset( $contact['name'] ) ? $contact['name'] : '' ) );

    // Find and execute funnel trigger nodes.
    $trigger_nodes = self::find_ghl_trigger_nodes();
    $saved_to_wp   = false;

    foreach ( $trigger_nodes as $trigger ) {
      $settings = isset( $trigger['config'] ) ? $trigger['config'] : [];

      // Save contact locally if the node has ghl_save_to_wp enabled and not already saved.
      if ( ! $saved_to_wp && ! empty( $settings['ghl_save_to_wp'] ) && $settings['ghl_save_to_wp'] !== 'off' ) {
        $result = self::process_pulled_contact( $contact );

        if ( is_wp_error( $result ) ) {
          self::log( 'Webhook process error: ' . $result->get_error_message() );
        } else {
          self::log( 'Webhook processed contact: ' . $result );

          // Set client stage and source for the Clients dashboard.
          $source_label = ! empty( $custom_source ) ? $custom_source : 'ghl';
          self::set_client_meta_after_save( $contact, $source_label, $result );
        }

        $saved_to_wp = true;
      }

      self::execute_node_action( $trigger, $contact );

      // Follow connection arrows to downstream nodes (WhatsApp AI, Email, etc.).
      self::execute_downstream_nodes( $trigger['id'], $trigger['_funnel_id'], $contact );
    }

    // If no trigger nodes exist, save the contact directly as a fallback.
    if ( empty( $trigger_nodes ) ) {
      self::log( 'Webhook: no trigger nodes found, saving contact directly.' );
      $result = self::process_pulled_contact( $contact );

      if ( is_wp_error( $result ) ) {
        self::log( 'Webhook fallback save error: ' . $result->get_error_message() );
      } else {
        self::log( 'Webhook fallback saved contact: ' . $result );
        $source_label = ! empty( $custom_source ) ? $custom_source : 'ghl';
        self::set_client_meta_after_save( $contact, $source_label, $result );
        $saved_to_wp = true;
      }
    }

    return new WP_REST_Response( [
      'status'    => 'ok',
      'saved'     => $saved_to_wp,
      'contact_id'=> $contact['id'],
    ], 200 );
  }

  /**
   * Get the webhook URL for GHL integration.
   *
   * @return string
   */
  public static function get_webhook_url() {
    return rest_url( 'pn-cm/v1/ghl/webhook' );
  }

  /**
   * Find all funnel canvas nodes with subtype ghl_new_contact.
   *
   * @return array Array of node config arrays.
   */
  public static function find_ghl_trigger_nodes() {
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
        if ( isset( $node['subtype'] ) && $node['subtype'] === 'ghl_new_contact' ) {
          $node['_funnel_id'] = $funnel_id;
          $nodes[] = $node;
        }
      }
    }

    return $nodes;
  }

  /**
   * Execute the configured action for a GHL trigger node.
   *
   * @param array $node_config Node configuration from the canvas.
   * @param array $contact_data GHL contact data.
   */
  private static function execute_node_action( $node_config, $contact_data ) {
    $settings = isset( $node_config['config'] ) ? $node_config['config'] : [];

    $contact_name  = isset( $contact_data['name'] ) ? $contact_data['name'] : '';
    $contact_email = isset( $contact_data['email'] ) ? $contact_data['email'] : '';
    $contact_phone = isset( $contact_data['phone'] ) ? $contact_data['phone'] : '';
    $company       = isset( $contact_data['companyName'] ) ? $contact_data['companyName'] : '';

    // Only send notification email if the checkbox is enabled.
    $send_email = ! empty( $settings['ghl_send_email'] ) && $settings['ghl_send_email'] === 'on';
    if ( ! $send_email ) {
      return;
    }

    $to = isset( $settings['ghl_notify_email'] ) ? sanitize_email( $settings['ghl_notify_email'] ) : get_option( 'admin_email' );

    if ( empty( $to ) ) {
      $to = get_option( 'admin_email' );
    }

    $subject_template = isset( $settings['ghl_notify_subject'] ) && ! empty( $settings['ghl_notify_subject'] )
      ? $settings['ghl_notify_subject']
      : __( 'New GHL Contact: {name}', 'pn-customers-manager' );

    $subject = str_replace( '{name}', $contact_name, $subject_template );

    $body = sprintf(
      // translators: %1$s is the contact name, %2$s is the company name, %3$s is the email address, %4$s is the phone number.
      __( 'A new contact has been received from Go High Level:

Name: %1$s
Company: %2$s
Email: %3$s
Phone: %4$s', 'pn-customers-manager' ),
      $contact_name,
      $company,
      $contact_email,
      $contact_phone
    );

    if ( class_exists( 'MAILPN_Mailing' ) ) {
      $mailing = new MAILPN_Mailing();
      $mailing->mailpn_sender( [
        'mailpn_user_to' => $to,
        'mailpn_subject' => $subject,
        'mailpn_type'    => 'pn_cm_ghl_notification',
      ], nl2br( $body ) );
      self::log( 'MAILPN notification sent to ' . $to . ' for GHL contact: ' . $contact_name );
    } else {
      wp_mail( $to, $subject, $body );
      self::log( 'Email notification sent to ' . $to . ' for GHL contact: ' . $contact_name );
    }
  }

  /**
   * Set client stage and source meta after a contact is saved locally.
   *
   * @param array  $contact     The contact data.
   * @param string $source      The source identifier (e.g. 'ghl', 'zoho').
   * @param string $result_type The result key from process_pulled_contact().
   */
  public static function set_client_meta_after_save( $contact, $source, $result_type ) {
    $ghl_id = isset( $contact['id'] ) ? $contact['id'] : '';
    if ( empty( $ghl_id ) ) {
      return;
    }

    $default = class_exists( 'PN_CUSTOMERS_MANAGER_Clients' )
      ? PN_CUSTOMERS_MANAGER_Clients::get_default_stage()
      : 'New';

    if ( in_array( $result_type, [ 'orgs_created', 'orgs_updated' ], true ) ) {
      $org_id = self::find_org_by_ghl_id( $ghl_id );
      if ( $org_id ) {
        if ( empty( get_post_meta( $org_id, 'pn_cm_organization_client_stage', true ) ) ) {
          update_post_meta( $org_id, 'pn_cm_organization_client_stage', $default );
        }
        update_post_meta( $org_id, 'pn_cm_client_source', $source );
      }
    } elseif ( in_array( $result_type, [ 'contacts_created', 'contacts_updated' ], true ) ) {
      $user_id = self::find_user_by_ghl_id( $ghl_id );
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
   * @param array  $contact_data    Normalized contact data (name, email, phone, etc.).
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

    // Build node lookup.
    $nodes_by_id = [];
    foreach ( $canvas['nodes'] as $n ) {
      $nodes_by_id[ $n['id'] ] = $n;
    }

    // Find and execute downstream connections.
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
   * @param string $subtype      Node subtype (e.g. whatsapp_ai, email_sequence).
   * @param array  $config       Node configuration from the canvas.
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
   * Handle opportunity stage update from a GHL webhook.
   *
   * Detects opportunity data in the payload and updates the local
   * organization's funnel status accordingly.
   *
   * @param array $body Webhook payload.
   * @return array|false Response array if handled, false if not an opportunity event.
   */
  private static function handle_opportunity_stage_update( $body ) {
    if ( get_option( 'pn_customers_manager_ghl_funnel_sync', '' ) !== 'on' ) {
      return false;
    }

    // GHL opportunity webhooks include pipelineStageId and contactId.
    $stage_id   = isset( $body['pipelineStageId'] ) ? $body['pipelineStageId'] : '';
    $contact_id = isset( $body['contactId'] ) ? $body['contactId'] : '';

    if ( empty( $stage_id ) || empty( $contact_id ) ) {
      return false;
    }

    self::log( 'Opportunity stage update: contactId=' . $contact_id . ', stageId=' . $stage_id );

    // Find local organization.
    $org_id = self::find_org_by_ghl_id( $contact_id );
    if ( ! $org_id ) {
      self::log( 'Opportunity stage update: no local org found for GHL contact ' . $contact_id );
      return false;
    }

    // Resolve stage name from GHL pipelines.
    $stage_name = self::get_pipeline_stage_name( $stage_id );

    // Map to local funnel state.
    if ( ! class_exists( 'PN_CUSTOMERS_MANAGER_Settings' ) ) {
      return false;
    }
    $local_state_id = PN_CUSTOMERS_MANAGER_Settings::resolve_funnel_state_id( $stage_id, $stage_name );

    if ( empty( $local_state_id ) ) {
      self::log( 'Opportunity stage update: no matching local funnel state for stageId=' . $stage_id . ', name=' . $stage_name );
      return [ 'status' => 'ignored', 'reason' => 'no matching funnel state' ];
    }

    // Set sync lock to prevent push-back loop.
    set_transient( 'pn_cm_funnel_sync_lock_' . $org_id, 1, 30 );

    // Update local funnel status.
    update_post_meta( $org_id, 'pn_cm_organization_funnel_status', $local_state_id );

    // Store opportunity ID if present.
    $opp_id = isset( $body['id'] ) ? sanitize_text_field( $body['id'] ) : '';
    if ( ! empty( $opp_id ) ) {
      update_post_meta( $org_id, 'pn_cm_organization_ghl_opportunity_id', $opp_id );
    }

    self::log( 'Opportunity stage update: org #' . $org_id . ' funnel status set to "' . $local_state_id . '" (stage: ' . $stage_name . ')' );

    return [ 'status' => 'ok', 'action' => 'funnel_status_updated', 'org_id' => $org_id, 'state' => $local_state_id ];
  }

  /**
   * Get a pipeline stage name by its ID from GHL.
   *
   * Caches the full pipeline data in a transient for 1 hour.
   *
   * @param string $stage_id GHL pipeline stage ID.
   * @return string Stage name, or empty string if not found.
   */
  public static function get_pipeline_stage_name( $stage_id ) {
    $cache_key = 'pn_cm_ghl_pipeline_stages';
    $cached    = get_transient( $cache_key );

    if ( $cached === false ) {
      $pipelines = self::get_pipeline_stages();
      if ( is_wp_error( $pipelines ) ) {
        self::log( 'get_pipeline_stage_name: API error: ' . $pipelines->get_error_message() );
        return '';
      }
      $cached = $pipelines;
      set_transient( $cache_key, $cached, HOUR_IN_SECONDS );
    }

    foreach ( $cached as $pipeline ) {
      if ( ! empty( $pipeline['stages'] ) ) {
        foreach ( $pipeline['stages'] as $stage ) {
          if ( $stage['id'] === $stage_id ) {
            return $stage['name'];
          }
        }
      }
    }

    return '';
  }

  /**
   * PUSH: React to local funnel status changes and update GHL opportunity.
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
    if ( get_option( 'pn_customers_manager_ghl_funnel_sync', '' ) !== 'on' ) {
      return;
    }

    // Check sync lock to prevent infinite loop.
    if ( get_transient( 'pn_cm_funnel_sync_lock_' . $object_id ) ) {
      return;
    }

    $opp_id = get_post_meta( $object_id, 'pn_cm_organization_ghl_opportunity_id', true );
    if ( empty( $opp_id ) ) {
      return;
    }

    // Map local state to GHL pipeline stage ID.
    $target_stage_id = self::map_local_state_to_ghl_stage( $meta_value );
    if ( empty( $target_stage_id ) ) {
      self::log( 'Push funnel: no GHL stage match for local state "' . $meta_value . '" (org #' . $object_id . ')' );
      return;
    }

    // Update GHL opportunity.
    $result = self::api_request( 'PUT', '/opportunities/' . $opp_id, [
      'pipelineStageId' => $target_stage_id,
    ] );

    if ( is_wp_error( $result ) ) {
      self::log( 'Push funnel error (org #' . $object_id . '): ' . $result->get_error_message() );
    } else {
      self::log( 'Push funnel: org #' . $object_id . ' → GHL opportunity ' . $opp_id . ' stage set to ' . $target_stage_id );
    }
  }

  /**
   * Map a local funnel state ID to a GHL pipeline stage ID.
   *
   * Tries exact ID match first, then case-insensitive name match
   * against cached pipeline stages.
   *
   * @param string $local_state_id Local funnel state ID.
   * @return string GHL pipeline stage ID, or empty string.
   */
  private static function map_local_state_to_ghl_stage( $local_state_id ) {
    // Get local state name.
    $state_name = '';
    if ( class_exists( 'PN_CUSTOMERS_MANAGER_Settings' ) ) {
      $states = PN_CUSTOMERS_MANAGER_Settings::pn_customers_manager_get_funnel_states_config();
      foreach ( $states as $state ) {
        if ( $state['id'] === $local_state_id ) {
          $state_name = $state['name'];
          break;
        }
      }
    }

    // Get cached GHL pipeline stages.
    $cache_key = 'pn_cm_ghl_pipeline_stages';
    $pipelines = get_transient( $cache_key );
    if ( $pipelines === false ) {
      $pipelines = self::get_pipeline_stages();
      if ( is_wp_error( $pipelines ) ) {
        return '';
      }
      set_transient( $cache_key, $pipelines, HOUR_IN_SECONDS );
    }

    // 1. Try exact ID match.
    foreach ( $pipelines as $pipeline ) {
      if ( ! empty( $pipeline['stages'] ) ) {
        foreach ( $pipeline['stages'] as $stage ) {
          if ( $stage['id'] === $local_state_id ) {
            return $stage['id'];
          }
        }
      }
    }

    // 2. Try name match (case-insensitive).
    if ( ! empty( $state_name ) ) {
      $name_lower = strtolower( trim( $state_name ) );
      foreach ( $pipelines as $pipeline ) {
        if ( ! empty( $pipeline['stages'] ) ) {
          foreach ( $pipeline['stages'] as $stage ) {
            if ( strtolower( trim( $stage['name'] ) ) === $name_lower ) {
              return $stage['id'];
            }
          }
        }
      }
    }

    return '';
  }

  // ── AJAX handlers ──────────────────────────────────────────────────

  /**
   * Log a GHL message to a dedicated log file (ghl.log).
   *
   * Always writes to wp-content/uploads/pn-customers-manager-logs/ghl.log
   * regardless of WP_DEBUG, so webhook debugging is available in production.
   */
  private static function log( $message ) {
    $upload_dir = wp_upload_dir();
    $log_dir    = $upload_dir['basedir'] . '/pn-customers-manager-logs';

    if ( ! file_exists( $log_dir ) ) {
      wp_mkdir_p( $log_dir );
      file_put_contents( $log_dir . '/.htaccess', 'deny from all' );
    }

    $log_file = $log_dir . '/ghl.log';
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
   * AJAX: Test GHL connection.
   */
  public static function ajax_test() {
    try {
      echo wp_json_encode( self::test_connection() );
    } catch ( \Throwable $e ) {
      self::log( 'ajax_test exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
      echo wp_json_encode( [
        'error_key'     => 'ghl_exception',
        'error_content' => $e->getMessage() . ' (' . basename( $e->getFile() ) . ':' . $e->getLine() . ')',
      ] );
    }
    exit;
  }

  /**
   * AJAX: Push all organizations and contacts to GHL.
   */
  public static function ajax_push_all() {
    try {
      $direction = get_option( 'pn_customers_manager_ghl_sync_direction', 'both' );

      if ( $direction === 'pull' ) {
        echo wp_json_encode( [
          'error_key'     => 'ghl_direction',
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
          $summary['orgs_pushed'],
          $summary['orgs_errors'],
          $summary['contacts_pushed'],
          $summary['contacts_errors']
        ),
      ] );
    } catch ( \Throwable $e ) {
      self::log( 'ajax_push_all exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
      echo wp_json_encode( [
        'error_key'     => 'ghl_exception',
        'error_content' => $e->getMessage() . ' (' . basename( $e->getFile() ) . ':' . $e->getLine() . ')',
      ] );
    }
    exit;
  }

  /**
   * AJAX: Pull all contacts from GHL.
   */
  public static function ajax_pull_all() {
    try {
      $direction = get_option( 'pn_customers_manager_ghl_sync_direction', 'both' );

      if ( $direction === 'push' ) {
        echo wp_json_encode( [
          'error_key'     => 'ghl_direction',
          'error_content' => __( 'Pull is disabled. Sync direction is set to Push only.', 'pn-customers-manager' ),
        ] );
        exit;
      }

      $cursor = isset( $_POST['pull_cursor'] ) ? sanitize_text_field( wp_unslash( $_POST['pull_cursor'] ) ) : '';

      self::log( 'Pull batch started (cursor=' . $cursor . ')' );
      $page_result = self::pull_contacts_page( $cursor );
      $summary     = $page_result['summary'];
      self::log( 'Pull batch finished: ' . wp_json_encode( $summary ) );

      echo wp_json_encode( [
        'error_key' => '',
        'summary'   => $summary,
        'cursor'    => $page_result['cursor'],
        'has_more'  => $page_result['has_more'],
      ] );
    } catch ( \Throwable $e ) {
      self::log( 'ajax_pull_all exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() );
      echo wp_json_encode( [
        'error_key'     => 'ghl_exception',
        'error_content' => $e->getMessage() . ' (' . basename( $e->getFile() ) . ':' . $e->getLine() . ')',
      ] );
    }
    exit;
  }

  // ── Pipeline stages sync ────────────────────────────────────────────

  /**
   * Fetch all pipelines and their stages from GHL.
   *
   * @return array|WP_Error Array of pipelines, each with 'name' and 'stages' array.
   */
  public static function get_pipeline_stages() {
    $location_id = get_option( 'pn_customers_manager_ghl_location_id', '' );
    if ( empty( $location_id ) ) {
      return new WP_Error( 'ghl_no_location', __( 'Location ID is not configured.', 'pn-customers-manager' ) );
    }

    $result = self::api_request( 'GET', '/opportunities/pipelines?locationId=' . urlencode( $location_id ) );
    if ( is_wp_error( $result ) ) {
      return $result;
    }

    $pipelines = isset( $result['pipelines'] ) ? $result['pipelines'] : [];
    $output    = [];

    foreach ( $pipelines as $pipeline ) {
      $stages = [];
      if ( ! empty( $pipeline['stages'] ) && is_array( $pipeline['stages'] ) ) {
        foreach ( $pipeline['stages'] as $stage ) {
          $stages[] = [
            'id'   => isset( $stage['id'] ) ? $stage['id'] : sanitize_title( $stage['name'] ),
            'name' => isset( $stage['name'] ) ? $stage['name'] : '',
          ];
        }
      }
      $output[] = [
        'name'   => isset( $pipeline['name'] ) ? $pipeline['name'] : '',
        'stages' => $stages,
      ];
    }

    return $output;
  }

  /**
   * AJAX: Fetch pipeline stages from GHL and return as funnel states.
   */
  public static function ajax_get_pipeline_stages() {
    $pipelines = self::get_pipeline_stages();
    if ( is_wp_error( $pipelines ) ) {
      echo wp_json_encode( [
        'error_key'     => 'ghl_error',
        'error_content' => $pipelines->get_error_message(),
      ] );
      return;
    }

    // Flatten all stages from all pipelines with distinct colors.
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
