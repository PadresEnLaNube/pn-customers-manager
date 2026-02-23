<?php
/**
 * Platform shortcodes.
 *
 * This class defines all shortcodes of the platform.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Shortcodes {
	/**
	 * Manage the shortcodes in the platform.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_test($atts) {
    $a = extract(shortcode_atts([
      'user_id' => 0,
      'post_id' => 0,
    ], $atts));

    
	}

  public function pn_customers_manager_call_to_action($atts) {
    // echo do_shortcode('[pn-customers-manager-call-to-action pn_customers_manager_call_to_action_icon="error_outline" pn_customers_manager_call_to_action_title="' . esc_html(__('Default title', 'pn-customers-manager')) . '" pn_customers_manager_call_to_action_content="' . esc_html(__('Default content', 'pn-customers-manager')) . '" pn_customers_manager_call_to_action_button_link="#" pn_customers_manager_call_to_action_button_text="' . esc_html(__('Button text', 'pn-customers-manager')) . '" pn_customers_manager_call_to_action_button_class="pn-customers-manager-class"]');
    $a = extract(shortcode_atts(array(
      'pn_customers_manager_call_to_action_class' => '',
      'pn_customers_manager_call_to_action_icon' => '',
      'pn_customers_manager_call_to_action_title' => '',
      'pn_customers_manager_call_to_action_content' => '',
      'pn_customers_manager_call_to_action_button_link' => '#',
      'pn_customers_manager_call_to_action_button_text' => '',
      'pn_customers_manager_call_to_action_button_class' => '',
      'pn_customers_manager_call_to_action_button_data_key' => '',
      'pn_customers_manager_call_to_action_button_data_value' => '',
      'pn_customers_manager_call_to_action_button_blank' => 0,
    ), $atts));

    ob_start();
    ?>
      <div class="pn-customers-manager-call-to-action pn-customers-manager-text-align-center pn-customers-manager-pt-30 pn-customers-manager-pb-50 <?php echo esc_attr($pn_customers_manager_call_to_action_class); ?>">
        <div class="pn-customers-manager-call-to-action-icon">
          <i class="material-icons-outlined pn-customers-manager-font-size-75 pn-customers-manager-color-main-0"><?php echo esc_html($pn_customers_manager_call_to_action_icon); ?></i>
        </div>

        <h4 class="pn-customers-manager-call-to-action-title pn-customers-manager-text-align-center pn-customers-manager-mt-10 pn-customers-manager-mb-20"><?php echo esc_html($pn_customers_manager_call_to_action_title); ?></h4>
        
        <?php if (!empty($pn_customers_manager_call_to_action_content)): ?>
          <p class="pn-customers-manager-text-align-center"><?php echo wp_kses_post($pn_customers_manager_call_to_action_content); ?></p>
        <?php endif ?>

        <?php if (!empty($pn_customers_manager_call_to_action_button_text)): ?>
          <div class="pn-customers-manager-text-align-center pn-customers-manager-mt-20">
            <a class="pn-customers-manager-btn pn-customers-manager-btn-transparent pn-customers-manager-margin-auto <?php echo esc_attr($pn_customers_manager_call_to_action_button_class); ?>" <?php echo ($pn_customers_manager_call_to_action_button_blank) ? 'target="_blank"' : ''; ?> href="<?php echo esc_url($pn_customers_manager_call_to_action_button_link); ?>" <?php echo (!empty($pn_customers_manager_call_to_action_button_data_key) && !empty($pn_customers_manager_call_to_action_button_data_value)) ? esc_attr($pn_customers_manager_call_to_action_button_data_key) . '="' . esc_attr($pn_customers_manager_call_to_action_button_data_value) . '"' : ''; ?>><?php echo esc_html($pn_customers_manager_call_to_action_button_text); ?></a>
          </div>
        <?php endif ?>
      </div>
    <?php 
    $pn_customers_manager_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $pn_customers_manager_return_string;
  }

  /**
   * Client onboarding form shortcode.
   *
   * @param array $atts
   * @return string
   */
  public function pn_customers_manager_client_form($atts = []) {
    return PN_CUSTOMERS_MANAGER_Client_Form::render_form($atts);
  }

  /**
   * Contact form shortcode.
   *
   * @param array $atts
   * @return string
   */
  public function pn_customers_manager_contact_form($atts = []) {
    return PN_CUSTOMERS_MANAGER_Contact_Form::render_form($atts);
  }

}