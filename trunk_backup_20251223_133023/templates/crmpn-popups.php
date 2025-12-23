<?php
/**
 * Provide common popups for the plugin
 *
 * This file is used to markup the common popups of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    customers_manager_pn
 * @subpackage customers_manager_pn/common/templates
 */

  if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>
<div class="customers-manager-pn-popup-overlay customers-manager-pn-display-none-soft"></div>
<div class="customers-manager-pn-menu-more-overlay customers-manager-pn-display-none-soft"></div>

<?php foreach (CUSTOMERS_MANAGER_PN_CPTS as $cpt => $cpt_name) : ?>
  <div id="customers-manager-pn-popup-<?php echo esc_attr($cpt); ?>-add" class="customers-manager-pn-popup customers-manager-pn-popup-size-medium customers-manager-pn-display-none-soft" data-customers-manager-pn-popup-disable-esc="true" data-customers-manager-pn-popup-disable-overlay-close="true">
    <?php CUSTOMERS_MANAGER_PN_Data::customers_manager_pn_popup_loader(); ?>
  </div>

  <div id="customers-manager-pn-popup-<?php echo esc_attr($cpt); ?>-check" class="customers-manager-pn-popup customers-manager-pn-popup-size-medium customers-manager-pn-display-none-soft">
    <?php CUSTOMERS_MANAGER_PN_Data::customers_manager_pn_popup_loader(); ?>
  </div>

  <div id="customers-manager-pn-popup-<?php echo esc_attr($cpt); ?>-view" class="customers-manager-pn-popup customers-manager-pn-popup-size-medium customers-manager-pn-display-none-soft">
    <?php CUSTOMERS_MANAGER_PN_Data::customers_manager_pn_popup_loader(); ?>
  </div>

  <div id="customers-manager-pn-popup-<?php echo esc_attr($cpt); ?>-edit" class="customers-manager-pn-popup customers-manager-pn-popup-size-large customers-manager-pn-display-none-soft" data-customers-manager-pn-popup-disable-esc="true" data-customers-manager-pn-popup-disable-overlay-close="true">
    <?php CUSTOMERS_MANAGER_PN_Data::customers_manager_pn_popup_loader(); ?>
  </div>

  <div id="customers-manager-pn-popup-<?php echo esc_attr($cpt); ?>-remove" class="customers-manager-pn-popup customers-manager-pn-popup-size-medium customers-manager-pn-display-none-soft">
    <div class="customers-manager-pn-popup-content">
      <div class="customers-manager-pn-p-30">
        <h3 class="customers-manager-pn-text-align-center"><?php echo esc_html($cpt_name); ?> <?php esc_html_e('removal', 'customers-manager-pn'); ?></h3>
        <p class="customers-manager-pn-text-align-center"><?php echo esc_html($cpt_name); ?> <?php esc_html_e('will be completely deleted. This process cannot be reversed and cannot be recovered.', 'customers-manager-pn'); ?></p>

        <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
          <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-text-align-center">
            <a href="#" class="customers-manager-pn-popup-close customers-manager-pn-text-decoration-none customers-manager-pn-font-size-small"><?php esc_html_e('Cancel', 'customers-manager-pn'); ?></a>
          </div>
          <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-text-align-center">
            <a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-<?php echo esc_attr($cpt); ?>-remove" data-customers-manager-pn-post-type="customers_manager_pn_<?php echo esc_attr($cpt); ?>"><?php esc_html_e('Remove', 'customers-manager-pn'); ?> <?php echo esc_html($cpt_name); ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<div id="customers-manager-pn-popup-customers_manager_pn_contact-add" class="customers-manager-pn-popup customers-manager-pn-popup-size-medium customers-manager-pn-display-none-soft">
  <?php CUSTOMERS_MANAGER_PN_Data::customers_manager_pn_popup_loader(); ?>
</div>