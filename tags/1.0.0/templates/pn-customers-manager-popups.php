<?php
/**
 * Provide common popups for the plugin
 *
 * This file is used to markup the common popups of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/common/templates
 */

  if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>
<div class="pn-customers-manager-popup-overlay pn-customers-manager-display-none-soft"></div>
<div class="pn-customers-manager-menu-more-overlay pn-customers-manager-display-none-soft"></div>

<?php foreach (PN_CUSTOMERS_MANAGER_CPTS as $pn_customers_manager_cpt => $pn_customers_manager_cpt_name) : ?>
  <div id="pn-customers-manager-popup-<?php echo esc_attr($pn_customers_manager_cpt); ?>-add" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-disable-esc="true" data-pn-customers-manager-popup-disable-overlay-close="true">
    <?php PN_CUSTOMERS_MANAGER_Data::PN_CUSTOMERS_MANAGER_popup_loader(); ?>
  </div>

  <div id="pn-customers-manager-popup-<?php echo esc_attr($pn_customers_manager_cpt); ?>-check" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
    <?php PN_CUSTOMERS_MANAGER_Data::PN_CUSTOMERS_MANAGER_popup_loader(); ?>
  </div>

  <div id="pn-customers-manager-popup-<?php echo esc_attr($pn_customers_manager_cpt); ?>-view" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
    <?php PN_CUSTOMERS_MANAGER_Data::PN_CUSTOMERS_MANAGER_popup_loader(); ?>
  </div>

  <div id="pn-customers-manager-popup-<?php echo esc_attr($pn_customers_manager_cpt); ?>-edit" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" data-pn-customers-manager-popup-disable-esc="true" data-pn-customers-manager-popup-disable-overlay-close="true">
    <?php PN_CUSTOMERS_MANAGER_Data::PN_CUSTOMERS_MANAGER_popup_loader(); ?>
  </div>

  <div id="pn-customers-manager-popup-<?php echo esc_attr($pn_customers_manager_cpt); ?>-remove" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
    <div class="pn-customers-manager-popup-content">
      <div class="pn-customers-manager-p-30">
        <h3 class="pn-customers-manager-text-align-center"><?php echo esc_html($pn_customers_manager_cpt_name); ?> <?php esc_html_e('removal', 'pn-customers-manager'); ?></h3>
        <p class="pn-customers-manager-text-align-center"><?php echo esc_html($pn_customers_manager_cpt_name); ?> <?php esc_html_e('will be completely deleted. This process cannot be reversed and cannot be recovered.', 'pn-customers-manager'); ?></p>

        <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
          <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-50-percent pn-customers-manager-text-align-center">
            <a href="#" class="pn-customers-manager-popup-cancel pn-customers-manager-text-decoration-none pn-customers-manager-font-size-small"><?php esc_html_e('Cancel', 'pn-customers-manager'); ?></a>
          </div>
          <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-50-percent pn-customers-manager-text-align-center">
            <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-<?php echo esc_attr($pn_customers_manager_cpt); ?>-remove" data-pn-customers-manager-post-type="PN_CUSTOMERS_MANAGER_<?php echo esc_attr($pn_customers_manager_cpt); ?>"><?php esc_html_e('Remove', 'pn-customers-manager'); ?> <?php echo esc_html($pn_customers_manager_cpt_name); ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<div id="pn-customers-manager-popup-PN_CUSTOMERS_MANAGER_contact-add" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">
  <?php PN_CUSTOMERS_MANAGER_Data::PN_CUSTOMERS_MANAGER_popup_loader(); ?>
</div>