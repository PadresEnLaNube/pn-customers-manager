<?php
/**
 * Provide a common footer area view for the plugin
 *
 * This file is used to markup the common footer facing aspects of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    CRMPN
 * @subpackage CRMPN/common/templates
 */

  if (!defined('ABSPATH')) exit; // Exit if accessed directly

  // Ensure the global variable exists
  if (!isset($GLOBALS['crmpn_data'])) {
    $GLOBALS['crmpn_data'] = array(
      'user_id' => get_current_user_id(),
      'post_id' => is_admin() ? (!empty($GLOBALS['_REQUEST']['post']) ? $GLOBALS['_REQUEST']['post'] : 0) : get_the_ID()
    );
  }

  $crmpn_data = $GLOBALS['crmpn_data'];
?>

<div id="crmpn-main-message" class="crmpn-main-message crmpn-display-none-soft crmpn-z-index-top" style="display:none;" data-user-id="<?php echo esc_attr($crmpn_data['user_id']); ?>" data-post-id="<?php echo esc_attr($crmpn_data['post_id']); ?>">
  <span id="crmpn-main-message-span"></span><i class="material-icons-outlined crmpn-vertical-align-bottom crmpn-ml-20 crmpn-cursor-pointer crmpn-color-white crmpn-close-icon">close</i>

  <div id="crmpn-bar-wrapper">
  	<div id="crmpn-bar"></div>
  </div>
</div>
