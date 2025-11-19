<?php
/**
 * Platform shortcodes.
 *
 * This class defines all shortcodes of the platform.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Shortcodes {
	/**
	 * Manage the shortcodes in the platform.
	 *
	 * @since    1.0.0
	 */
	public function crmpn_test($atts) {
    $a = extract(shortcode_atts([
      'user_id' => 0,
      'post_id' => 0,
    ], $atts));

    ob_start();
    ?>
      <div class="crmpn-shortcode-example">
      	Shortcode example
      	<p>User id: <?php echo intval($user_id); ?></p>
      	<p>Post id: <?php echo intval($post_id); ?></p>
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
	}

  public function crmpn_call_to_action($atts) {
    // echo do_shortcode('[crmpn-call-to-action crmpn_call_to_action_icon="error_outline" crmpn_call_to_action_title="' . esc_html(__('Default title', 'crmpn')) . '" crmpn_call_to_action_content="' . esc_html(__('Default content', 'crmpn')) . '" crmpn_call_to_action_button_link="#" crmpn_call_to_action_button_text="' . esc_html(__('Button text', 'crmpn')) . '" crmpn_call_to_action_button_class="crmpn-class"]');
    $a = extract(shortcode_atts(array(
      'crmpn_call_to_action_class' => '',
      'crmpn_call_to_action_icon' => '',
      'crmpn_call_to_action_title' => '',
      'crmpn_call_to_action_content' => '',
      'crmpn_call_to_action_button_link' => '#',
      'crmpn_call_to_action_button_text' => '',
      'crmpn_call_to_action_button_class' => '',
      'crmpn_call_to_action_button_data_key' => '',
      'crmpn_call_to_action_button_data_value' => '',
      'crmpn_call_to_action_button_blank' => 0,
    ), $atts));

    ob_start();
    ?>
      <div class="crmpn-call-to-action crmpn-text-align-center crmpn-pt-30 crmpn-pb-50 <?php echo esc_attr($crmpn_call_to_action_class); ?>">
        <div class="crmpn-call-to-action-icon">
          <i class="material-icons-outlined crmpn-font-size-75 crmpn-color-main-0"><?php echo esc_html($crmpn_call_to_action_icon); ?></i>
        </div>

        <h4 class="crmpn-call-to-action-title crmpn-text-align-center crmpn-mt-10 crmpn-mb-20"><?php echo esc_html($crmpn_call_to_action_title); ?></h4>
        
        <?php if (!empty($crmpn_call_to_action_content)): ?>
          <p class="crmpn-text-align-center"><?php echo wp_kses_post($crmpn_call_to_action_content); ?></p>
        <?php endif ?>

        <?php if (!empty($crmpn_call_to_action_button_text)): ?>
          <div class="crmpn-text-align-center crmpn-mt-20">
            <a class="crmpn-btn crmpn-btn-transparent crmpn-margin-auto <?php echo esc_attr($crmpn_call_to_action_button_class); ?>" <?php echo ($crmpn_call_to_action_button_blank) ? 'target="_blank"' : ''; ?> href="<?php echo esc_url($crmpn_call_to_action_button_link); ?>" <?php echo (!empty($crmpn_call_to_action_button_data_key) && !empty($crmpn_call_to_action_button_data_value)) ? esc_attr($crmpn_call_to_action_button_data_key) . '="' . esc_attr($crmpn_call_to_action_button_data_value) . '"' : ''; ?>><?php echo esc_html($crmpn_call_to_action_button_text); ?></a>
          </div>
        <?php endif ?>
      </div>
    <?php 
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }
}