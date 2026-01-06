<?php
/**
 * Provide a common footer area view for the plugin
 *
 * This file is used to markup the common footer facing aspects of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/common/templates
 */

  if (!defined('ABSPATH')) exit; // Exit if accessed directly

  // Ensure the global variable exists
  if (!isset($GLOBALS['PN_CUSTOMERS_MANAGER_data'])) {
    $GLOBALS['PN_CUSTOMERS_MANAGER_data'] = array(
      'user_id' => get_current_user_id(),
      'post_id' => is_admin() ? (!empty($GLOBALS['_REQUEST']['post']) ? $GLOBALS['_REQUEST']['post'] : 0) : get_the_ID()
    );
  }

  $pn_customers_manager_data = $GLOBALS['PN_CUSTOMERS_MANAGER_data'];
?>

<div id="pn-customers-manager-main-message" class="pn-customers-manager-main-message pn-customers-manager-display-none-soft pn-customers-manager-z-index-top" style="display:none;" data-user-id="<?php echo esc_attr($pn_customers_manager_data['user_id']); ?>" data-post-id="<?php echo esc_attr($pn_customers_manager_data['post_id']); ?>">
  <span id="pn-customers-manager-main-message-span"></span><i class="material-icons-outlined pn-customers-manager-vertical-align-bottom pn-customers-manager-ml-20 pn-customers-manager-cursor-pointer pn-customers-manager-color-white pn-customers-manager-close-icon">close</i>

  <div id="pn-customers-manager-bar-wrapper">
  	<div id="pn-customers-manager-bar"></div>
  </div>
</div>
