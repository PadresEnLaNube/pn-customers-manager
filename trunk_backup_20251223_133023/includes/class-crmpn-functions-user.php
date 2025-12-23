<?php
/**
 * Define the users management functionality.
 *
 * Loads and defines the users management files for this plugin so that it is ready for user creation, edition or removal.
 *  
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    customers_manager_pn
 * @subpackage customers_manager_pn/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Functions_User {
  public static function customers_manager_pn_user_is_admin($user_id) {
    // CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_is_admin($user_id)
    return is_user_logged_in() && user_can($user_id, 'manage_options');
  }

  public static function customers_manager_pn_user_get_name($user_id) {
    // CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_get_name($user_id)
    if (!empty($user_id)) {
      $user_info = get_userdata($user_id);

      if (!empty($user_info->first_name) && !empty($user_info->last_name)) {
        return $user_info->first_name . ' ' . $user_info->last_name;
      }elseif (!empty($user_info->first_name)) {
        return $user_info->first_name;
      }else if (!empty($user_info->last_name)) {
        return $user_info->last_name;
      }else if (!empty($user_info->user_nicename)){
        return $user_info->user_nicename;
      }else if (!empty($user_info->user_login)){
        return $user_info->user_login;
      } else {
        return $user_info->user_email;
      }
    }
  }

  public static function customers_manager_pn_user_get_age($user_id) {
    // CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_get_age($user_id)
    $timestamp = get_user_meta($user_id, 'customers_manager_pn_child_birthdate', true);

    if (!empty($timestamp) && is_string($timestamp)) {
      $timestamp = strtotime($timestamp);

      $year = gmdate('Y', $timestamp);
      $age = gmdate('Y') - $year;

      if(strtotime('+' . $age . ' years', $timestamp) > time()) {
        $age--;
      }

      return $age;
    }

    return false;
  }

  public static function customers_manager_pn_user_insert($customers_manager_pn_user_login, $customers_manager_pn_user_password, $customers_manager_pn_user_email = '', $customers_manager_pn_first_name = '', $customers_manager_pn_last_name = '', $customers_manager_pn_display_name = '', $customers_manager_pn_user_nicename = '', $customers_manager_pn_user_nickname = '', $customers_manager_pn_user_description = '', $customers_manager_pn_user_role = [], $customers_manager_pn_array_usermeta = [/*['customers_manager_pn_key' => 'customers_manager_pn_value'], */]) {
    /* $this->insert_user($customers_manager_pn_user_login, $customers_manager_pn_user_password, $customers_manager_pn_user_email = '', $customers_manager_pn_first_name = '', $customers_manager_pn_last_name = '', $customers_manager_pn_display_name = '', $customers_manager_pn_user_nicename = '', $customers_manager_pn_user_nickname = '', $customers_manager_pn_user_description = '', $customers_manager_pn_user_role = [], $customers_manager_pn_array_usermeta = [['customers_manager_pn_key' => 'customers_manager_pn_value'], ],); */

    $customers_manager_pn_user_array = [
      'first_name' => $customers_manager_pn_first_name,
      'last_name' => $customers_manager_pn_last_name,
      'display_name' => $customers_manager_pn_display_name,
      'user_nicename' => $customers_manager_pn_user_nicename,
      'nickname' => $customers_manager_pn_user_nickname,
      'description' => $customers_manager_pn_user_description,
    ];

    if (!empty($customers_manager_pn_user_email)) {
      if (!email_exists($customers_manager_pn_user_email)) {
        if (username_exists($customers_manager_pn_user_login)) {
          $user_id = wp_create_user($customers_manager_pn_user_email, $customers_manager_pn_user_password, $customers_manager_pn_user_email);
        } else {
          $user_id = wp_create_user($customers_manager_pn_user_login, $customers_manager_pn_user_password, $customers_manager_pn_user_email);
        }
      } else {
        $user_id = get_user_by('email', $customers_manager_pn_user_email)->ID;
      }
    } else {
      if (!username_exists($customers_manager_pn_user_login)) {
        $user_id = wp_create_user($customers_manager_pn_user_login, $customers_manager_pn_user_password);
      } else {
        $user_id = get_user_by('login', $customers_manager_pn_user_login)->ID;
      }
    }

    if ($user_id && !is_wp_error($user_id)) {
      wp_update_user(array_merge(['ID' => $user_id], $customers_manager_pn_user_array));
    } else {
      return false;
    }

    $user = new WP_User($user_id);
    if (!empty($customers_manager_pn_user_role)) {
      foreach ($customers_manager_pn_user_role as $new_role) {
        $user->add_role($new_role);
      }
    }

    if (!empty($customers_manager_pn_array_usermeta)) {
      foreach ($customers_manager_pn_array_usermeta as $customers_manager_pn_usermeta) {
        foreach ($customers_manager_pn_usermeta as $meta_key => $meta_value) {
          if ((!empty($meta_value) || !empty(get_user_meta($user_id, $meta_key, true))) && !is_null($meta_value)) {
            update_user_meta($user_id, $meta_key, $meta_value);
          }
        }
      }
    }

    return $user_id;
  }

  public function customers_manager_pn_user_wp_login($login) {
    $user = get_user_by('login', $login);
    $user_id = $user->ID;
    $current_login_time = get_user_meta($user_id, 'customers_manager_pn_user_current_login', true);

    if(!empty($current_login_time)){
      update_user_meta($user_id, 'customers_manager_pn_user_last_login', $current_login_time);
      update_user_meta($user_id, 'customers_manager_pn_user_current_login', current_time('timestamp'));
    }else {
      update_user_meta($user_id, 'customers_manager_pn_user_current_login', current_time('timestamp'));
      update_user_meta($user_id, 'customers_manager_pn_user_last_login', current_time('timestamp'));
    }
  }

  /**
   * Check if current user can view a specific post based on ownership and capabilities
   *
   * @since    1.0.6
   * @param    int       $post_id    Post ID to check
   * @param    string    $post_type  Post type (customers_manager_pn_asset or customers_manager_pn_liability)
   * @return   bool                  True if user can view, false otherwise
   */
  public static function customers_manager_pn_user_can_view_post($post_id, $post_type) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
      return false;
    }

    $current_user_id = get_current_user_id();
    $post = get_post($post_id);

    // If post doesn't exist, deny access
    if (!$post || $post->post_type !== $post_type) {
      return false;
    }

    // Administrators can view everything
    if (is_user_logged_in() && user_can($current_user_id, 'manage_options')) {
      return true;
    }

    // Check if user is the owner of the post
    if ($post->post_author == $current_user_id) {
      return true;
    }

    // Check specific capabilities for the post type
    $capability = 'read_' . $post_type;
    if (current_user_can($capability)) {
      return true;
    }

    // Check if user has any of the specific capabilities for this post type
    $capabilities = constant('CUSTOMERS_MANAGER_PN_ROLE_' . strtoupper($post_type) . '_CAPABILITIES');
    if ($capabilities) {
      foreach ($capabilities as $cap) {
        if (current_user_can($cap)) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Filter posts to only show those the user can view
   *
   * @since    1.0.6
   * @param    array     $posts      Array of post IDs
   * @param    string    $post_type  Post type (customers_manager_pn_asset or customers_manager_pn_liability)
   * @return   array                 Filtered array of post IDs
   */
  public static function customers_manager_pn_filter_user_posts($posts, $post_type) {
    if (!is_user_logged_in()) {
      return [];
    }

    $current_user_id = get_current_user_id();
    $filtered_posts = [];

    // Administrators can view everything
    if (current_user_can('administrator')) {
      return $posts;
    }

    foreach ($posts as $post_id) {
      if (self::customers_manager_pn_user_can_view_post($post_id, $post_type)) {
        $filtered_posts[] = $post_id;
      }
    }

    return $filtered_posts;
  }
}