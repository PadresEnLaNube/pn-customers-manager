<?php
/**
 * Provide a common footer area view for the plugin
 *
 * This file is used to markup the common footer facing aspects of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/common/templates
 */

  if (!defined('ABSPATH')) exit; // Exit if accessed directly

  // Ensure the global variable exists
  if (!isset($GLOBALS['customers_manager_pn_data'])) {
    $GLOBALS['customers_manager_pn_data'] = array(
      'user_id' => get_current_user_id(),
      'post_id' => is_admin() ? (!empty($GLOBALS['_REQUEST']['post']) ? $GLOBALS['_REQUEST']['post'] : 0) : get_the_ID()
    );
  }

  $customers_manager_pn_data = $GLOBALS['customers_manager_pn_data'];
?>

<div id="customers-manager-pn-main-message" class="customers-manager-pn-main-message customers-manager-pn-display-none-soft customers-manager-pn-z-index-top" style="display:none;" data-user-id="<?php echo esc_attr($customers_manager_pn_data['user_id']); ?>" data-post-id="<?php echo esc_attr($customers_manager_pn_data['post_id']); ?>">
  <span id="customers-manager-pn-main-message-span"></span><i class="material-icons-outlined customers-manager-pn-vertical-align-bottom customers-manager-pn-ml-20 customers-manager-pn-cursor-pointer customers-manager-pn-color-white customers-manager-pn-close-icon">close</i>

  <div id="customers-manager-pn-bar-wrapper">
  	<div id="customers-manager-pn-bar"></div>
  </div>
</div>
