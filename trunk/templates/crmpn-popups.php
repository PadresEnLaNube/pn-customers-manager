<?php
/**
 * Provide common popups for the plugin
 *
 * This file is used to markup the common popups of the plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 *
 * @package    crmpn
 * @subpackage crmpn/common/templates
 */

  if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>
<div class="crmpn-popup-overlay crmpn-display-none-soft"></div>
<div class="crmpn-menu-more-overlay crmpn-display-none-soft"></div>

<?php foreach (CRMPN_CPTS as $cpt => $cpt_name) : ?>
  <div id="crmpn-popup-<?php echo esc_attr($cpt); ?>-add" class="crmpn-popup crmpn-popup-size-medium crmpn-display-none-soft" data-crmpn-popup-disable-esc="true" data-crmpn-popup-disable-overlay-close="true">
    <?php CRMPN_Data::crmpn_popup_loader(); ?>
  </div>

  <div id="crmpn-popup-<?php echo esc_attr($cpt); ?>-check" class="crmpn-popup crmpn-popup-size-medium crmpn-display-none-soft">
    <?php CRMPN_Data::crmpn_popup_loader(); ?>
  </div>

  <div id="crmpn-popup-<?php echo esc_attr($cpt); ?>-view" class="crmpn-popup crmpn-popup-size-medium crmpn-display-none-soft">
    <?php CRMPN_Data::crmpn_popup_loader(); ?>
  </div>

  <div id="crmpn-popup-<?php echo esc_attr($cpt); ?>-edit" class="crmpn-popup crmpn-popup-size-large crmpn-display-none-soft" data-crmpn-popup-disable-esc="true" data-crmpn-popup-disable-overlay-close="true">
    <?php CRMPN_Data::crmpn_popup_loader(); ?>
  </div>

  <div id="crmpn-popup-<?php echo esc_attr($cpt); ?>-remove" class="crmpn-popup crmpn-popup-size-medium crmpn-display-none-soft">
    <div class="crmpn-popup-content">
      <div class="crmpn-p-30">
        <h3 class="crmpn-text-align-center"><?php echo esc_html($cpt_name); ?> <?php esc_html_e('removal', 'crmpn'); ?></h3>
        <p class="crmpn-text-align-center"><?php echo esc_html($cpt_name); ?> <?php esc_html_e('will be completely deleted. This process cannot be reversed and cannot be recovered.', 'crmpn'); ?></p>

        <div class="crmpn-display-table crmpn-width-100-percent">
          <div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-text-align-center">
            <a href="#" class="crmpn-popup-close crmpn-text-decoration-none crmpn-font-size-small"><?php esc_html_e('Cancel', 'crmpn'); ?></a>
          </div>
          <div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-text-align-center">
            <a href="#" class="crmpn-btn crmpn-btn-mini crmpn-<?php echo esc_attr($cpt); ?>-remove" data-crmpn-post-type="crmpn_<?php echo esc_attr($cpt); ?>"><?php esc_html_e('Remove', 'crmpn'); ?> <?php echo esc_html($cpt_name); ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<div id="crmpn-popup-crmpn_contact-add" class="crmpn-popup crmpn-popup-size-medium crmpn-display-none-soft">
  <?php CRMPN_Data::crmpn_popup_loader(); ?>
</div>