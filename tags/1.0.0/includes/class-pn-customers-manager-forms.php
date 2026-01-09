<?php
/**
 * Fired from activate() function.
 *
 * This class defines all post types necessary to run during the plugin's life cycle.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Forms {
  /**
   * Plaform forms.
   *
   * @since    1.0.0
   */

  /**
   * Get the current value of a field based on its type and storage
   * 
   * @param string $field_id The field ID
   * @param string $pn_customers_manager_type The type of field (user, post, option)
   * @param int $pn_customers_manager_id The ID of the user/post/option
   * @param int $pn_customers_manager_meta_array Whether the field is part of a meta array
   * @param int $pn_customers_manager_array_index The index in the meta array
   * @param array $pn_customers_manager_input The input array containing field configuration
   * @return mixed The current value of the field
   */
  private static function PN_CUSTOMERS_MANAGER_get_field_value($field_id, $pn_customers_manager_type, $pn_customers_manager_id = 0, $pn_customers_manager_meta_array = 0, $pn_customers_manager_array_index = 0, $pn_customers_manager_input = []) {
    $current_value = '';

    if ($pn_customers_manager_meta_array) {
      switch ($pn_customers_manager_type) {
        case 'user':
          $meta = get_user_meta($pn_customers_manager_id, $field_id, true);
          if (is_array($meta) && isset($meta[$pn_customers_manager_array_index])) {
            $current_value = $meta[$pn_customers_manager_array_index];
          }
          break;
        case 'post':
          $meta = get_post_meta($pn_customers_manager_id, $field_id, true);
          if (is_array($meta) && isset($meta[$pn_customers_manager_array_index])) {
            $current_value = $meta[$pn_customers_manager_array_index];
          }
          break;
        case 'option':
          $option = get_option($field_id);
          if (is_array($option) && isset($option[$pn_customers_manager_array_index])) {
            $current_value = $option[$pn_customers_manager_array_index];
          }
          break;
      }
    } else {
      switch ($pn_customers_manager_type) {
        case 'user':
          $current_value = get_user_meta($pn_customers_manager_id, $field_id, true);
          break;
        case 'post':
          $current_value = get_post_meta($pn_customers_manager_id, $field_id, true);
          break;
        case 'option':
          $current_value = get_option($field_id);
          break;
      }
    }

    // If no value is found and there's a default value in the input config, use it
    // BUT NOT for checkboxes in multiple fields, as empty string and 'off' are valid states (unchecked)
    if (empty($current_value) && !empty($pn_customers_manager_input['value'])) {
      // For checkboxes in multiple fields, don't override empty values or 'off' with default
      if (!($pn_customers_manager_meta_array && isset($pn_customers_manager_input['type']) && $pn_customers_manager_input['type'] === 'checkbox')) {
        $current_value = $pn_customers_manager_input['value'];
      }
    }
    
    // For checkboxes in multiple fields, normalize 'off' to empty string for display
    if ($pn_customers_manager_meta_array && isset($pn_customers_manager_input['type']) && $pn_customers_manager_input['type'] === 'checkbox' && $current_value === 'off') {
      $current_value = '';
    }

    return $current_value;
  }

  public static function PN_CUSTOMERS_MANAGER_input_builder($pn_customers_manager_input, $pn_customers_manager_type, $pn_customers_manager_id = 0, $disabled = 0, $pn_customers_manager_meta_array = 0, $pn_customers_manager_array_index = 0) {
    // Get the current value using the new function
    $pn_customers_manager_value = self::PN_CUSTOMERS_MANAGER_get_field_value($pn_customers_manager_input['id'], $pn_customers_manager_type, $pn_customers_manager_id, $pn_customers_manager_meta_array, $pn_customers_manager_array_index, $pn_customers_manager_input);

    $pn_customers_manager_parent_block = (!empty($pn_customers_manager_input['parent']) ? 'data-pn-customers-manager-parent="' . $pn_customers_manager_input['parent'] . '"' : '') . ' ' . (!empty($pn_customers_manager_input['parent_option']) ? 'data-pn-customers-manager-parent-option="' . $pn_customers_manager_input['parent_option'] . '"' : '');

    switch ($pn_customers_manager_input['input']) {
      case 'input':        
        switch ($pn_customers_manager_input['type']) {
          case 'file':
            ?>
              <?php if (empty($pn_customers_manager_value)): ?>
                <p class="pn-customers-manager-m-10"><?php esc_html_e('No file found', 'pn-customers-manager'); ?></p>
              <?php else: ?>
                <p class="pn-customers-manager-m-10">
                  <a href="<?php echo esc_url(get_post_meta($pn_customers_manager_id, $pn_customers_manager_input['id'], true)['url']); ?>" target="_blank"><?php echo esc_html(basename(get_post_meta($pn_customers_manager_id, $pn_customers_manager_input['id'], true)['url'])); ?></a>
                </p>
              <?php endif ?>
            <?php
            break;
          case 'checkbox':
            ?>
              <label class="pn-customers-manager-switch">
                <input id="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" class="<?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?> pn-customers-manager-checkbox pn-customers-manager-checkbox-switch pn-customers-manager-field" type="<?php echo esc_attr($pn_customers_manager_input['type']); ?>" <?php echo $pn_customers_manager_value == 'on' ? 'checked="checked"' : ''; ?> <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?> <?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] ? 'multiple' : ''); ?> <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>>
                <span class="pn-customers-manager-slider pn-customers-manager-round"></span>
              </label>
            <?php
            break;
          case 'radio':
            ?>
              <div class="pn-customers-manager-input-radio-wrapper">
                <?php if (!empty($pn_customers_manager_input['radio_options'])): ?>
                  <?php foreach ($pn_customers_manager_input['radio_options'] as $radio_option): ?>
                    <div class="pn-customers-manager-input-radio-item">
                      <label for="<?php echo esc_attr($radio_option['id']); ?>">
                        <?php echo wp_kses_post(wp_specialchars_decode($radio_option['label'])); ?>
                        
                        <input type="<?php echo esc_attr($pn_customers_manager_input['type']); ?>"
                          id="<?php echo esc_attr($radio_option['id']); ?>"
                          name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>"
                          value="<?php echo esc_attr($radio_option['value']); ?>"
                          <?php echo $pn_customers_manager_value == $radio_option['value'] ? 'checked="checked"' : ''; ?>
                          <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == 'true') ? 'required' : ''); ?>>

                        <div class="pn-customers-manager-radio-control"></div>
                      </label>
                    </div>
                  <?php endforeach ?>
                <?php endif ?>
              </div>
            <?php
            break;
          case 'range':
            ?>
              <div class="pn-customers-manager-input-range-wrapper">
                <div class="pn-customers-manager-width-100-percent">
                  <?php if (!empty($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_label_min'])): ?>
                    <p class="pn-customers-manager-input-range-label-min"><?php echo esc_html($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_label_min']); ?></p>
                  <?php endif ?>

                  <?php if (!empty($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_label_max'])): ?>
                    <p class="pn-customers-manager-input-range-label-max"><?php echo esc_html($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_label_max']); ?></p>
                  <?php endif ?>
                </div>

                <input type="<?php echo esc_attr($pn_customers_manager_input['type']); ?>" id="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" class="pn-customers-manager-input-range <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>" <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo (isset($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_max']) ? 'max=' . esc_attr($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_max']) : ''); ?> <?php echo (isset($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_min']) ? 'min=' . esc_attr($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_min']) : ''); ?> <?php echo (((array_key_exists('step', $pn_customers_manager_input) && $pn_customers_manager_input['step'] != '')) ? 'step="' . esc_attr($pn_customers_manager_input['step']) . '"' : ''); ?> <?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] ? 'multiple' : ''); ?> value="<?php echo (!empty($pn_customers_manager_input['button_text']) ? esc_html($pn_customers_manager_input['button_text']) : esc_html($pn_customers_manager_value)); ?>"/>
                <h3 class="pn-customers-manager-input-range-output"></h3>
              </div>
            <?php
            break;
          case 'stars':
            $pn_customers_manager_stars = !empty($pn_customers_manager_input['stars_number']) ? $pn_customers_manager_input['stars_number'] : 5;
            ?>
              <div class="pn-customers-manager-input-stars-wrapper">
                <div class="pn-customers-manager-width-100-percent">
                  <?php if (!empty($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_label_min'])): ?>
                    <p class="pn-customers-manager-input-stars-label-min"><?php echo esc_html($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_label_min']); ?></p>
                  <?php endif ?>

                  <?php if (!empty($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_label_max'])): ?>
                    <p class="pn-customers-manager-input-stars-label-max"><?php echo esc_html($pn_customers_manager_input['PN_CUSTOMERS_MANAGER_label_max']); ?></p>
                  <?php endif ?>
                </div>

                <div class="pn-customers-manager-input-stars pn-customers-manager-text-align-center pn-customers-manager-pt-20">
                  <?php foreach (range(1, $pn_customers_manager_stars) as $index => $star): ?>
                    <i class="material-icons-outlined pn-customers-manager-input-star">
                      <?php echo ($index < intval($pn_customers_manager_value)) ? 'star' : 'star_outlined'; ?>
                    </i>
                  <?php endforeach ?>
                </div>

                <input type="number" <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?> <?php echo ((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') ? 'disabled' : ''); ?> id="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" class="pn-customers-manager-input-hidden-stars <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>" min="1" max="<?php echo esc_attr($pn_customers_manager_stars) ?>" value="<?php echo esc_attr($pn_customers_manager_value); ?>">
              </div>
            <?php
            break;
          case 'submit':
            $post_id_value = '';
            if (!empty($pn_customers_manager_input['post_id'])) {
              $post_id_value = $pn_customers_manager_input['post_id'];
            } elseif (!empty($pn_customers_manager_id) && $pn_customers_manager_type === 'post') {
              $post_id_value = $pn_customers_manager_id;
            } elseif (!empty(get_the_ID())) {
              $post_id_value = get_the_ID();
            }
            $post_type_value = !empty($pn_customers_manager_input['post_type']) ? $pn_customers_manager_input['post_type'] : '';
            ?>
              <div class="pn-customers-manager-text-align-right">
                <input type="submit" value="<?php echo esc_attr($pn_customers_manager_input['value']); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" class="pn-customers-manager-btn" data-pn-customers-manager-type="<?php echo esc_attr($pn_customers_manager_type); ?>" data-pn-customers-manager-subtype="<?php echo ((array_key_exists('subtype', $pn_customers_manager_input)) ? esc_attr($pn_customers_manager_input['subtype']) : ''); ?>" data-pn-customers-manager-user-id="<?php echo esc_attr($pn_customers_manager_id); ?>" data-pn-customers-manager-post-id="<?php echo esc_attr($post_id_value); ?>" <?php echo !empty($post_type_value) ? 'data-pn-customers-manager-post-type="' . esc_attr($post_type_value) . '"' : ''; ?>/><?php esc_html(PN_CUSTOMERS_MANAGER_Data::PN_CUSTOMERS_MANAGER_loader()); ?>
              </div>
            <?php
            break;
          case 'hidden':
            ?>
              <input type="hidden" id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" value="<?php echo esc_attr($pn_customers_manager_value); ?>" <?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] == 'true' ? 'multiple' : ''); ?>>
            <?php
            break;
          case 'nonce':
            ?>
              <input type="hidden" id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" value="<?php echo esc_attr(wp_create_nonce('pn-customers-manager-nonce')); ?>">
            <?php
            break;
          case 'password':
            ?>
              <div class="pn-customers-manager-password-checker">
                <div class="pn-customers-manager-password-input pn-customers-manager-position-relative">
                  <input id="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] == 'true') ? '[]' : ''); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] == 'true') ? '[]' : ''); ?>" <?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] == 'true' ? 'multiple' : ''); ?> class="pn-customers-manager-field pn-customers-manager-password-strength <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>" type="<?php echo esc_attr($pn_customers_manager_input['type']); ?>" <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == 'true') ? 'required' : ''); ?> <?php echo ((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') ? 'disabled' : ''); ?> value="<?php echo (!empty($pn_customers_manager_input['button_text']) ? esc_html($pn_customers_manager_input['button_text']) : esc_attr($pn_customers_manager_value)); ?>" placeholder="<?php echo (array_key_exists('placeholder', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['placeholder']) : ''); ?>" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>/>

                  <a href="#" class="pn-customers-manager-show-pass pn-customers-manager-cursor-pointer pn-customers-manager-display-none-soft">
                    <i class="material-icons-outlined pn-customers-manager-font-size-20">visibility</i>
                  </a>
                </div>

                <div id="pn-customers-manager-popover-pass" class="pn-customers-manager-display-none-soft">
                  <div class="pn-customers-manager-progress-bar-wrapper">
                    <div class="pn-customers-manager-password-strength-bar"></div>
                  </div>

                  <h3 class="pn-customers-manager-mt-20"><?php esc_html_e('Password strength checker', 'pn-customers-manager'); ?> <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-close-icon pn-customers-manager-mt-30">close</i></h3>
                  <ul class="pn-customers-manager-list-style-none">
                    <li class="low-upper-case">
                      <i class="material-icons-outlined pn-customers-manager-font-size-20 pn-customers-manager-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Lowercase & Uppercase', 'pn-customers-manager'); ?></span>
                    </li>
                    <li class="one-number">
                      <i class="material-icons-outlined pn-customers-manager-font-size-20 pn-customers-manager-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Number (0-9)', 'pn-customers-manager'); ?></span>
                    </li>
                    <li class="one-special-char">
                      <i class="material-icons-outlined pn-customers-manager-font-size-20 pn-customers-manager-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Special Character (!@#$%^&*)', 'pn-customers-manager'); ?></span>
                    </li>
                    <li class="eight-character">
                      <i class="material-icons-outlined pn-customers-manager-font-size-20 pn-customers-manager-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Atleast 8 Character', 'pn-customers-manager'); ?></span>
                    </li>
                  </ul>
                </div>
              </div>
            <?php
            break;
          case 'color':
            ?>
              <input id="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" <?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] ? 'multiple' : ''); ?> class="pn-customers-manager-field <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>" type="<?php echo esc_attr($pn_customers_manager_input['type']); ?>" <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> value="<?php echo (!empty($pn_customers_manager_value) ? esc_attr($pn_customers_manager_value) : '#000000'); ?>" placeholder="<?php echo (array_key_exists('placeholder', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['placeholder']) : ''); ?>" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>/>
            <?php
            break;
          default:
            ?>
              <input 
                <?php /* ID and name attributes */ ?>
                id="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" 
                name="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>"
                
                <?php /* Type and styling */ ?>
                class="pn-customers-manager-field <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>" 
                type="<?php echo esc_attr($pn_customers_manager_input['type']); ?>"
                
                <?php /* State attributes */ ?>
                <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?>
                <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
                <?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] ? 'multiple' : ''); ?>
                
                <?php /* Validation and limits */ ?>
                <?php echo (((array_key_exists('step', $pn_customers_manager_input) && $pn_customers_manager_input['step'] != '')) ? 'step="' . esc_attr($pn_customers_manager_input['step']) . '"' : ''); ?>
                <?php echo (isset($pn_customers_manager_input['max']) ? 'max="' . esc_attr($pn_customers_manager_input['max']) . '"' : ''); ?>
                <?php echo (isset($pn_customers_manager_input['min']) ? 'min="' . esc_attr($pn_customers_manager_input['min']) . '"' : ''); ?>
                <?php echo (isset($pn_customers_manager_input['maxlength']) ? 'maxlength="' . esc_attr($pn_customers_manager_input['maxlength']) . '"' : ''); ?>
                <?php echo (isset($pn_customers_manager_input['pattern']) ? 'pattern="' . esc_attr($pn_customers_manager_input['pattern']) . '"' : ''); ?>
                
                <?php /* Content attributes */ ?>
                value="<?php echo (!empty($pn_customers_manager_input['button_text']) ? esc_html($pn_customers_manager_input['button_text']) : esc_html($pn_customers_manager_value)); ?>"
                placeholder="<?php echo (array_key_exists('placeholder', $pn_customers_manager_input) ? esc_html($pn_customers_manager_input['placeholder']) : ''); ?>"
                
                <?php /* Custom data attributes */ ?>
                <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>
              />
            <?php
            break;
        }
        break;
      case 'select':
        if (!empty($pn_customers_manager_input['options']) && is_array($pn_customers_manager_input['options'])) {
          ?>
          <select 
            id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" 
            name="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" 
            class="pn-customers-manager-field <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>"
            <?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? 'multiple' : ''; ?>
            <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?>
            <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
            <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>
          >
            <?php if (array_key_exists('placeholder', $pn_customers_manager_input) && !empty($pn_customers_manager_input['placeholder'])): ?>
              <option value=""><?php echo esc_html($pn_customers_manager_input['placeholder']); ?></option>
            <?php endif; ?>
            
            <?php 
            $selected_values = array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] ? 
              (is_array($pn_customers_manager_value) ? $pn_customers_manager_value : array()) : 
              array($pn_customers_manager_value);
            
            foreach ($pn_customers_manager_input['options'] as $value => $label): 
              $is_selected = in_array($value, $selected_values);
            ?>
              <option 
                value="<?php echo esc_attr($value); ?>"
                <?php echo $is_selected ? 'selected="selected"' : ''; ?>
              >
                <?php echo esc_html($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php
        }
        break;
      case 'textarea':
        ?>
          <textarea id="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']) . ((array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? '[]' : ''); ?>" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?> class="pn-customers-manager-field <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>" <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple'] ? 'multiple' : ''); ?> placeholder="<?php echo (array_key_exists('placeholder', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['placeholder']) : ''); ?>"><?php echo esc_html($pn_customers_manager_value); ?></textarea>
        <?php
        break;
      case 'image':
        ?>
          <div class="pn-customers-manager-field pn-customers-manager-images-block" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?> data-pn-customers-manager-multiple="<?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? 'true' : 'false'; ?>">
            <?php if (!empty($pn_customers_manager_value)): ?>
              <div class="pn-customers-manager-images">
                <?php foreach (explode(',', $pn_customers_manager_value) as $pn_customers_manager_image): ?>
                  <?php echo wp_get_attachment_image($pn_customers_manager_image, 'medium'); ?>
                <?php endforeach ?>
              </div>

              <div class="pn-customers-manager-text-align-center pn-customers-manager-position-relative"><a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-image-btn"><?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? esc_html(__('Edit images', 'pn-customers-manager')) : esc_html(__('Edit image', 'pn-customers-manager')); ?></a></div>
            <?php else: ?>
              <div class="pn-customers-manager-images"></div>

              <div class="pn-customers-manager-text-align-center pn-customers-manager-position-relative"><a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-image-btn"><?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? esc_html(__('Add images', 'pn-customers-manager')) : esc_html(__('Add image', 'pn-customers-manager')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" class="pn-customers-manager-display-none pn-customers-manager-image-input" type="text" value="<?php echo esc_attr($pn_customers_manager_value); ?>"/>
          </div>
        <?php
        break;
      case 'video':
        ?>
        <div class="pn-customers-manager-field pn-customers-manager-videos-block" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>>
            <?php if (!empty($pn_customers_manager_value)): ?>
              <div class="pn-customers-manager-videos">
                <?php foreach (explode(',', $pn_customers_manager_value) as $pn_customers_manager_video): ?>
                  <div class="pn-customers-manager-video pn-customers-manager-tooltip" title="<?php echo esc_html(get_the_title($pn_customers_manager_video)); ?>"><i class="dashicons dashicons-media-video"></i></div>
                <?php endforeach ?>
              </div>

              <div class="pn-customers-manager-text-align-center pn-customers-manager-position-relative"><a href="#" class="pn-customers-manager-btn pn-customers-manager-video-btn"><?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? esc_html(__('Edit videos', 'pn-customers-manager')) : esc_html(__('Edit video', 'pn-customers-manager')); ?></a></div>
            <?php else: ?>
              <div class="pn-customers-manager-videos"></div>

              <div class="pn-customers-manager-text-align-center pn-customers-manager-position-relative"><a href="#" class="pn-customers-manager-btn pn-customers-manager-video-btn"><?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? esc_html(__('Add videos', 'pn-customers-manager')) : esc_html(__('Add video', 'pn-customers-manager')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" class="pn-customers-manager-display-none pn-customers-manager-video-input" type="text" value="<?php echo esc_attr($pn_customers_manager_value); ?>"/>
          </div>
        <?php
        break;
      case 'audio':
        ?>
          <div class="pn-customers-manager-field pn-customers-manager-audios-block" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>>
            <?php if (!empty($pn_customers_manager_value)): ?>
              <div class="pn-customers-manager-audios">
                <?php foreach (explode(',', $pn_customers_manager_value) as $pn_customers_manager_audio): ?>
                  <div class="pn-customers-manager-audio pn-customers-manager-tooltip" title="<?php echo esc_html(get_the_title($pn_customers_manager_audio)); ?>"><i class="dashicons dashicons-media-audio"></i></div>
                <?php endforeach ?>
              </div>

              <div class="pn-customers-manager-text-align-center pn-customers-manager-position-relative"><a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-audio-btn"><?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? esc_html(__('Edit audios', 'pn-customers-manager')) : esc_html(__('Edit audio', 'pn-customers-manager')); ?></a></div>
            <?php else: ?>
              <div class="pn-customers-manager-audios"></div>

              <div class="pn-customers-manager-text-align-center pn-customers-manager-position-relative"><a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-audio-btn"><?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? esc_html(__('Add audios', 'pn-customers-manager')) : esc_html(__('Add audio', 'pn-customers-manager')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" class="pn-customers-manager-display-none pn-customers-manager-audio-input" type="text" value="<?php echo esc_attr($pn_customers_manager_value); ?>"/>
          </div>
        <?php
        break;
      case 'file':
        ?>
          <div class="pn-customers-manager-field pn-customers-manager-files-block" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>>
            <?php if (!empty($pn_customers_manager_value)): ?>
              <div class="pn-customers-manager-files pn-customers-manager-text-align-center">
                <?php foreach (explode(',', $pn_customers_manager_value) as $pn_customers_manager_file): ?>
                  <embed src="<?php echo esc_url(wp_get_attachment_url($pn_customers_manager_file)); ?>" type="application/pdf" class="pn-customers-manager-embed-file"/>
                <?php endforeach ?>
              </div>

              <div class="pn-customers-manager-text-align-center pn-customers-manager-position-relative"><a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-file-btn"><?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? esc_html(__('Edit files', 'pn-customers-manager')) : esc_html(__('Edit file', 'pn-customers-manager')); ?></a></div>
            <?php else: ?>
              <div class="pn-customers-manager-files"></div>

              <div class="pn-customers-manager-text-align-center pn-customers-manager-position-relative"><a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-mini pn-customers-manager-file-btn"><?php echo (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) ? esc_html(__('Add files', 'pn-customers-manager')) : esc_html(__('Add file', 'pn-customers-manager')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" class="pn-customers-manager-display-none pn-customers-manager-file-input pn-customers-manager-btn-mini" type="text" value="<?php echo esc_attr($pn_customers_manager_value); ?>"/>
          </div>
        <?php
        break;
      case 'editor':
        ?>
          <div class="pn-customers-manager-field" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>>
            <textarea id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-wysiwyg"><?php echo ((empty($pn_customers_manager_value)) ? (array_key_exists('placeholder', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['placeholder']) : '') : esc_html($pn_customers_manager_value)); ?></textarea>
          </div>
        <?php
        break;
      case 'html':
        ?>
          <div class="pn-customers-manager-field" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>>
            <?php echo !empty($pn_customers_manager_input['html_content']) ? wp_kses(do_shortcode($pn_customers_manager_input['html_content']), PN_CUSTOMERS_MANAGER_KSES) : ''; ?>
          </div>
        <?php
        break;
      case 'html_multi':
        switch ($pn_customers_manager_type) {
          case 'user':
            $html_multi_fields_length = !empty(get_user_meta($pn_customers_manager_id, $pn_customers_manager_input['html_multi_fields'][0]['id'], true)) ? count(get_user_meta($pn_customers_manager_id, $pn_customers_manager_input['html_multi_fields'][0]['id'], true)) : 0;
            break;
          case 'post':
            $html_multi_fields_length = !empty(get_post_meta($pn_customers_manager_id, $pn_customers_manager_input['html_multi_fields'][0]['id'], true)) ? count(get_post_meta($pn_customers_manager_id, $pn_customers_manager_input['html_multi_fields'][0]['id'], true)) : 0;
            break;
          case 'option':
            $html_multi_fields_length = !empty(get_option($pn_customers_manager_input['html_multi_fields'][0]['id'])) ? count(get_option($pn_customers_manager_input['html_multi_fields'][0]['id'])) : 0;
        }

        ?>
          <div class="pn-customers-manager-field pn-customers-manager-html-multi-wrapper pn-customers-manager-mb-50" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>>
            <?php if ($html_multi_fields_length): ?>
              <?php foreach (range(0, ($html_multi_fields_length - 1)) as $length_index): ?>
                <div class="pn-customers-manager-html-multi-group pn-customers-manager-display-table pn-customers-manager-width-100-percent pn-customers-manager-mb-30">
                  <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-90-percent">
                    <?php foreach ($pn_customers_manager_input['html_multi_fields'] as $index => $html_multi_field): ?>
                      <?php if (isset($html_multi_field['label']) && !empty($html_multi_field['label'])): ?>
                        <label><?php echo esc_html($html_multi_field['label']); ?></label>
                      <?php endif; ?>

                      <?php self::PN_CUSTOMERS_MANAGER_input_builder($html_multi_field, $pn_customers_manager_type, $pn_customers_manager_id, false, true, $length_index); ?>
                    <?php endforeach ?>
                  </div>
                  <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-10-percent pn-customers-manager-text-align-center">
                    <i class="material-icons-outlined pn-customers-manager-cursor-move pn-customers-manager-multi-sorting pn-customers-manager-vertical-align-super pn-customers-manager-tooltip" title="<?php esc_html_e('Order element', 'pn-customers-manager'); ?>">drag_handle</i>
                  </div>

                  <div class="pn-customers-manager-text-align-right">
                    <a href="#" class="pn-customers-manager-html-multi-remove-btn"><i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-tooltip" title="<?php esc_html_e('Remove element', 'pn-customers-manager'); ?>">remove</i></a>
                  </div>
                </div>
              <?php endforeach ?>
            <?php else: ?>
              <div class="pn-customers-manager-html-multi-group pn-customers-manager-mb-50">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-90-percent">
                  <?php foreach ($pn_customers_manager_input['html_multi_fields'] as $html_multi_field): ?>
                    <?php self::PN_CUSTOMERS_MANAGER_input_builder($html_multi_field, $pn_customers_manager_type); ?>
                  <?php endforeach ?>
                </div>
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-10-percent pn-customers-manager-text-align-center">
                  <i class="material-icons-outlined pn-customers-manager-cursor-move pn-customers-manager-multi-sorting pn-customers-manager-vertical-align-super pn-customers-manager-tooltip" title="<?php esc_html_e('Order element', 'pn-customers-manager'); ?>">drag_handle</i>
                </div>

                <div class="pn-customers-manager-text-align-right">
                  <a href="#" class="pn-customers-manager-html-multi-remove-btn pn-customers-manager-tooltip" title="<?php esc_html_e('Remove element', 'pn-customers-manager'); ?>"><i class="material-icons-outlined pn-customers-manager-cursor-pointer">remove</i></a>
                </div>
              </div>
            <?php endif ?>

            <div class="pn-customers-manager-text-align-right">
              <a href="#" class="pn-customers-manager-html-multi-add-btn pn-customers-manager-tooltip" title="<?php esc_html_e('Add element', 'pn-customers-manager'); ?>"><i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-40">add</i></a>
            </div>
          </div>
        <?php
        break;
      case 'audio_recorder':
        // Enqueue CSS and JS files for audio recorder
        wp_enqueue_style('pn-customers-manager-audio-recorder', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-audio-recorder.css', array(), '1.0.0');
        wp_enqueue_script('pn-customers-manager-audio-recorder', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-audio-recorder.js', array('jquery'), '1.0.0', true);
        
        // Localize script with AJAX data
        wp_localize_script('pn-customers-manager-audio-recorder', 'PN_CUSTOMERS_MANAGER_audio_recorder_vars', array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('PN_CUSTOMERS_MANAGER_audio_nonce'),
        ));
        
        ?>
          <div class="pn-customers-manager-audio-recorder-status pn-customers-manager-display-none-soft">
            <p class="pn-customers-manager-recording-status"><?php esc_html_e('Ready to record', 'pn-customers-manager'); ?></p>
          </div>
          
          <div class="pn-customers-manager-audio-recorder-wrapper">
            <div class="pn-customers-manager-audio-recorder-controls">
              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-50-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center pn-customers-manager-mb-20">
                  <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-primary pn-customers-manager-start-recording" <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined pn-customers-manager-vertical-align-middle">mic</i>
                    <?php esc_html_e('Start recording', 'pn-customers-manager'); ?>
                  </button>
                </div>

                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-50-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center pn-customers-manager-mb-20">
                  <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-secondary pn-customers-manager-stop-recording" style="display: none;" <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined pn-customers-manager-vertical-align-middle">stop</i>
                    <?php esc_html_e('Stop recording', 'pn-customers-manager'); ?>
                  </button>
                </div>
              </div>

              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-50-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center pn-customers-manager-mb-20">
                  <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-secondary pn-customers-manager-play-audio" style="display: none;" <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined pn-customers-manager-vertical-align-middle">play_arrow</i>
                    <?php esc_html_e('Play audio', 'pn-customers-manager'); ?>
                  </button>
                </div>

                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-50-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center pn-customers-manager-mb-20">
                  <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-secondary pn-customers-manager-stop-audio" style="display: none;" <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined pn-customers-manager-vertical-align-middle">stop</i>
                    <?php esc_html_e('Stop audio', 'pn-customers-manager'); ?>
                  </button>
                </div>
              </div>
            </div>

            <div class="pn-customers-manager-audio-recorder-visualizer" style="display: none;">
              <canvas class="pn-customers-manager-audio-canvas" width="300" height="60"></canvas>
            </div>

            <div class="pn-customers-manager-audio-recorder-timer" style="display: none;">
              <span class="pn-customers-manager-recording-time">00:00</span>
            </div>

            <div class="pn-customers-manager-audio-transcription-controls pn-customers-manager-display-none-soft pn-customers-manager-display-table pn-customers-manager-width-100-percent pn-customers-manager-mb-20">
              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-50-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center">
                <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-primary pn-customers-manager-transcribe-audio" <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                  <i class="material-icons-outlined pn-customers-manager-vertical-align-middle">translate</i>
                  <?php esc_html_e('Transcribe Audio', 'pn-customers-manager'); ?>
                </button>
              </div>

              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-50-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center">
                <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-secondary pn-customers-manager-clear-transcription" <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                  <i class="material-icons-outlined pn-customers-manager-vertical-align-middle">clear</i>
                  <?php esc_html_e('Clear', 'pn-customers-manager'); ?>
                </button>
              </div>
            </div>

            <div class="pn-customers-manager-audio-transcription-loading">
              <?php echo esc_html(PN_CUSTOMERS_MANAGER_Data::PN_CUSTOMERS_MANAGER_loader()); ?>
            </div>

            <div class="pn-customers-manager-audio-transcription-result">
              <textarea 
                id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" 
                name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" 
                class="pn-customers-manager-field pn-customers-manager-transcription-textarea <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>" 
                placeholder="<?php echo (array_key_exists('placeholder', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['placeholder']) : esc_attr__('Transcribed text will appear here...', 'pn-customers-manager')); ?>"
                <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?>
                <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
                <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>
                rows="4"
                style="width: 100%; margin-top: 10px;"
              ><?php echo esc_textarea($pn_customers_manager_value); ?></textarea>
            </div>

            <div class="pn-customers-manager-audio-transcription-error pn-customers-manager-display-none-soft">
              <p class="pn-customers-manager-error-message"></p>
            </div>

            <div class="pn-customers-manager-audio-transcription-success pn-customers-manager-display-none-soft">
              <p class="pn-customers-manager-success-message"></p>
            </div>

            <!-- Hidden input to store audio data -->
            <input type="hidden" 
                  id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>_audio_data" 
                  name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>_audio_data" 
                  value="" />
          </div>
        <?php
        break;
      case 'tags':
        // Get current tags value
        $current_tags = self::PN_CUSTOMERS_MANAGER_get_PN_CUSTOMERS_MANAGER_value($pn_customers_manager_type, $pn_customers_manager_id, $pn_customers_manager_input);
        $tags_array = is_array($current_tags) ? $current_tags : [];
        $tags_string = implode(', ', $tags_array);
        ?>
        <div class="pn-customers-manager-tags-wrapper" <?php echo wp_kses_post($pn_customers_manager_parent_block); ?>>
          <input type="text" 
            id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" 
            name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>" 
            class="pn-customers-manager-field pn-customers-manager-tags-input <?php echo array_key_exists('class', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['class']) : ''; ?>" 
            value="<?php echo esc_attr($tags_string); ?>" 
            placeholder="<?php echo (array_key_exists('placeholder', $pn_customers_manager_input) ? esc_attr($pn_customers_manager_input['placeholder']) : ''); ?>"
            <?php echo ((array_key_exists('required', $pn_customers_manager_input) && $pn_customers_manager_input['required'] == true) ? 'required' : ''); ?>
            <?php echo (((array_key_exists('disabled', $pn_customers_manager_input) && $pn_customers_manager_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> />
          
          <div class="pn-customers-manager-tags-suggestions" style="display: none;">
            <div class="pn-customers-manager-tags-suggestions-list"></div>
          </div>
          
          <div class="pn-customers-manager-tags-display">
            <?php if (!empty($tags_array)): ?>
              <?php foreach ($tags_array as $tag): ?>
                <span class="pn-customers-manager-tag">
                  <?php echo esc_html($tag); ?>
                  <i class="material-icons-outlined pn-customers-manager-tag-remove">close</i>
                </span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <input type="hidden" 
            id="<?php echo esc_attr($pn_customers_manager_input['id']); ?>_tags_array" 
            name="<?php echo esc_attr($pn_customers_manager_input['id']); ?>_tags_array" 
            value="<?php echo esc_attr(json_encode($tags_array)); ?>" />
        </div>
        <?php
        break;
    }
  }

  public static function pn_customers_manager_input_wrapper_builder($input_array, $type, $pn_customers_manager_id = 0, $disabled = 0, $cm_pn_format = 'half'){
    ?>
      <?php if (array_key_exists('section', $input_array) && !empty($input_array['section'])): ?>      
        <?php if ($input_array['section'] == 'start'): ?>
          <div class="pn-customers-manager-toggle-wrapper pn-customers-manager-section-wrapper pn-customers-manager-position-relative pn-customers-manager-mb-30 <?php echo array_key_exists('class', $input_array) ? esc_attr($input_array['class']) : ''; ?>" id="<?php echo array_key_exists('id', $input_array) ? esc_attr($input_array['id']) : ''; ?>">
            <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
              <i class="material-icons-outlined pn-customers-manager-section-helper pn-customers-manager-color-main-0 pn-customers-manager-tooltip" title="<?php echo wp_kses_post($input_array['description']); ?>">help</i>
            <?php endif ?>

            <a href="#" class="pn-customers-manager-toggle pn-customers-manager-width-100-percent pn-customers-manager-text-decoration-none">
              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent pn-customers-manager-mb-20">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-90-percent">
                  <label class="pn-customers-manager-cursor-pointer pn-customers-manager-mb-20 pn-customers-manager-color-main-0"><?php echo wp_kses_post($input_array['label']); ?></label>
                </div>
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-10-percent pn-customers-manager-text-align-right">
                  <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-color-main-0">add</i>
                </div>
              </div>
            </a>

            <div class="pn-customers-manager-content pn-customers-manager-pl-10 pn-customers-manager-toggle-content pn-customers-manager-mb-20 pn-customers-manager-display-none-soft">
        <?php elseif ($input_array['section'] == 'end'): ?>
            </div>
          </div>
        <?php endif ?>
      <?php else: ?>
        <div class="pn-customers-manager-input-wrapper <?php echo esc_attr($input_array['id']); ?> <?php echo !empty($input_array['tabs']) ? 'pn-customers-manager-input-tabbed' : ''; ?> pn-customers-manager-input-field-<?php echo esc_attr($input_array['input']); ?> <?php echo (!empty($input_array['required']) && $input_array['required'] == true) ? 'pn-customers-manager-input-field-required' : ''; ?> <?php echo ($disabled) ? 'pn-customers-manager-input-field-disabled' : ''; ?> pn-customers-manager-mb-30">
          <?php if (array_key_exists('label', $input_array) && !empty($input_array['label'])): ?>
            <div class="pn-customers-manager-display-inline-table <?php echo (($cm_pn_format == 'half' && !(array_key_exists('type', $input_array) && $input_array['type'] == 'submit')) ? 'pn-customers-manager-width-40-percent' : 'pn-customers-manager-width-100-percent'); ?> pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-vertical-align-top">
              <div class="pn-customers-manager-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'pn-customers-manager-pl-30' : ''; ?>">
                <label class="pn-customers-manager-vertical-align-middle pn-customers-manager-display-block <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? 'pn-customers-manager-toggle' : ''; ?>" for="<?php echo esc_attr($input_array['id']); ?>"><?php echo wp_kses($input_array['label'], PN_CUSTOMERS_MANAGER_KSES); ?> <?php echo (array_key_exists('required', $input_array) && !empty($input_array['required']) && $input_array['required'] == true) ? '<span class="pn-customers-manager-tooltip" title="' . esc_html(__('Required field', 'pn-customers-manager')) . '">*</span>' : ''; ?><?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? '<i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-float-right">add</i>' : ''; ?></label>

                <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
                  <div class="pn-customers-manager-toggle-content pn-customers-manager-display-none-soft">
                    <small><?php echo wp_kses_post(wp_specialchars_decode($input_array['description'])); ?></small>
                  </div>
                <?php endif ?>
              </div>
            </div>
          <?php endif ?>

          <div class="pn-customers-manager-display-inline-table <?php echo ((array_key_exists('label', $input_array) && empty($input_array['label'])) ? 'pn-customers-manager-width-100-percent' : (($cm_pn_format == 'half' && !(array_key_exists('type', $input_array) && $input_array['type'] == 'submit')) ? 'pn-customers-manager-width-60-percent' : 'pn-customers-manager-width-100-percent')); ?> pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-vertical-align-top">
            <div class="pn-customers-manager-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'pn-customers-manager-pl-30' : ''; ?>">
              <div class="pn-customers-manager-input-field"><?php self::PN_CUSTOMERS_MANAGER_input_builder($input_array, $type, $pn_customers_manager_id, $disabled); ?></div>
            </div>
          </div>
        </div>
      <?php endif ?>
    <?php
  }

  /**
   * Display wrapper for field values with format control
   * 
   * @param array $input_array The input array containing field configuration
   * @param string $type The type of field (user, post, option)
   * @param int $pn_customers_manager_id The ID of the user/post/option
   * @param int $pn_customers_manager_meta_array Whether the field is part of a meta array
   * @param int $pn_customers_manager_array_index The index in the meta array
   * @param string $cm_pn_format The display format ('half' or 'full')
   * @return string Formatted HTML output
   */
  public static function PN_CUSTOMERS_MANAGER_input_display_wrapper($input_array, $type, $pn_customers_manager_id = 0, $pn_customers_manager_meta_array = 0, $pn_customers_manager_array_index = 0, $cm_pn_format = 'half') {
    ob_start();
    ?>
    <?php if (array_key_exists('section', $input_array) && !empty($input_array['section'])): ?>      
      <?php if ($input_array['section'] == 'start'): ?>
        <div class="pn-customers-manager-toggle-wrapper pn-customers-manager-section-wrapper pn-customers-manager-position-relative pn-customers-manager-mb-30 <?php echo array_key_exists('class', $input_array) ? esc_attr($input_array['class']) : ''; ?>" id="<?php echo array_key_exists('id', $input_array) ? esc_attr($input_array['id']) : ''; ?>">
          <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
            <i class="material-icons-outlined pn-customers-manager-section-helper pn-customers-manager-color-main-0 pn-customers-manager-tooltip" title="<?php echo wp_kses_post($input_array['description']); ?>">help</i>
          <?php endif ?>

          <a href="#" class="pn-customers-manager-toggle pn-customers-manager-width-100-percent pn-customers-manager-text-decoration-none">
            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent pn-customers-manager-mb-20">
              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-90-percent">
                <label class="pn-customers-manager-cursor-pointer pn-customers-manager-mb-20 pn-customers-manager-color-main-0"><?php echo wp_kses($input_array['label'], PN_CUSTOMERS_MANAGER_KSES); ?></label>
              </div>
              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-10-percent pn-customers-manager-text-align-right">
                <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-color-main-0">add</i>
              </div>
            </div>
          </a>

          <div class="pn-customers-manager-content pn-customers-manager-pl-10 pn-customers-manager-toggle-content pn-customers-manager-mb-20 pn-customers-manager-display-none-soft">
      <?php elseif ($input_array['section'] == 'end'): ?>
          </div>
        </div>
      <?php endif ?>
    <?php else: ?>
      <div class="pn-customers-manager-input-wrapper <?php echo esc_attr($input_array['id']); ?> pn-customers-manager-input-display-<?php echo esc_attr($input_array['input']); ?> <?php echo (!empty($input_array['required']) && $input_array['required'] == true) ? 'pn-customers-manager-input-field-required' : ''; ?> pn-customers-manager-mb-30">
        <?php if (array_key_exists('label', $input_array) && !empty($input_array['label'])): ?>
          <div class="pn-customers-manager-display-inline-table <?php echo ($cm_pn_format == 'half' ? 'pn-customers-manager-width-40-percent' : 'pn-customers-manager-width-100-percent'); ?> pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-vertical-align-top">
            <div class="pn-customers-manager-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'pn-customers-manager-pl-30' : ''; ?>">
              <label class="pn-customers-manager-vertical-align-middle pn-customers-manager-display-block <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? 'pn-customers-manager-toggle' : ''; ?>" for="<?php echo esc_attr($input_array['id']); ?>">
                <?php echo wp_kses($input_array['label'], PN_CUSTOMERS_MANAGER_KSES); ?>
                <?php echo (array_key_exists('required', $input_array) && !empty($input_array['required']) && $input_array['required'] == true) ? '<span class="pn-customers-manager-tooltip" title="' . esc_html(__('Required field', 'pn-customers-manager')) . '">*</span>' : ''; ?>
                <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? '<i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-float-right">add</i>' : ''; ?>
              </label>

              <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
                <div class="pn-customers-manager-toggle-content pn-customers-manager-display-none-soft">
                  <small><?php echo wp_kses_post(wp_specialchars_decode($input_array['description'])); ?></small>
                </div>
              <?php endif ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="pn-customers-manager-display-inline-table <?php echo ((array_key_exists('label', $input_array) && empty($input_array['label'])) ? 'pn-customers-manager-width-100-percent' : ($cm_pn_format == 'half' ? 'pn-customers-manager-width-60-percent' : 'pn-customers-manager-width-100-percent')); ?> pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-vertical-align-top">
          <div class="pn-customers-manager-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'pn-customers-manager-pl-30' : ''; ?>">
            <div class="pn-customers-manager-input-field">
              <?php self::PN_CUSTOMERS_MANAGER_input_display($input_array, $type, $pn_customers_manager_id, $pn_customers_manager_meta_array, $pn_customers_manager_array_index); ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
  }

  /**
   * Display formatted values of PN_CUSTOMERS_MANAGER_input_builder fields in frontend
   * 
   * @param array $pn_customers_manager_input The input array containing field configuration
   * @param string $pn_customers_manager_type The type of field (user, post, option)
   * @param int $pn_customers_manager_id The ID of the user/post/option
   * @param int $pn_customers_manager_meta_array Whether the field is part of a meta array
   * @param int $pn_customers_manager_array_index The index in the meta array
   * @return string Formatted HTML output of field values
   */
  public static function PN_CUSTOMERS_MANAGER_input_display($pn_customers_manager_input, $pn_customers_manager_type, $pn_customers_manager_id = 0, $pn_customers_manager_meta_array = 0, $pn_customers_manager_array_index = 0) {
    // Get the current value using the new function
    $current_value = self::PN_CUSTOMERS_MANAGER_get_field_value($pn_customers_manager_input['id'], $pn_customers_manager_type, $pn_customers_manager_id, $pn_customers_manager_meta_array, $pn_customers_manager_array_index, $pn_customers_manager_input);

    // Start the field value display
    ?>
      <div class="pn-customers-manager-field-value">
        <?php
        switch ($pn_customers_manager_input['input']) {
          case 'input':
            switch ($pn_customers_manager_input['type']) {
              case 'hidden':
                break;
              case 'nonce':
                break;
              case 'file':
                if (!empty($current_value)) {
                  $file_url = wp_get_attachment_url($current_value);
                  ?>
                    <div class="pn-customers-manager-file-display">
                      <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="pn-customers-manager-file-link">
                        <?php echo esc_html(basename($file_url)); ?>
                      </a>
                    </div>
                  <?php
                } else {
                  echo '<span class="pn-customers-manager-no-file">' . esc_html__('No file uploaded', 'pn-customers-manager') . '</span>';
                }
                break;

              case 'checkbox':
                ?>
                  <div class="pn-customers-manager-checkbox-display">
                    <span class="pn-customers-manager-checkbox-status <?php echo $current_value === 'on' ? 'checked' : 'unchecked'; ?>">
                      <?php echo $current_value === 'on' ? esc_html__('Yes', 'pn-customers-manager') : esc_html__('No', 'pn-customers-manager'); ?>
                    </span>
                  </div>
                <?php
                break;

              case 'radio':
                if (!empty($pn_customers_manager_input['radio_options'])) {
                  foreach ($pn_customers_manager_input['radio_options'] as $option) {
                    if ($current_value === $option['value']) {
                      ?>
                        <span class="pn-customers-manager-radio-selected"><?php echo esc_html($option['label']); ?></span>
                      <?php
                    }
                  }
                }
                break;

              case 'color':
                ?>
                  <div class="pn-customers-manager-color-display">
                    <span class="pn-customers-manager-color-preview" style="background-color: <?php echo esc_attr($current_value); ?>"></span>
                    <span class="pn-customers-manager-color-value"><?php echo esc_html($current_value); ?></span>
                  </div>
                <?php
                break;

              default:
                ?>
                  <span class="pn-customers-manager-text-value"><?php echo esc_html($current_value); ?></span>
                <?php
                break;
            }
            break;

          case 'select':
            if (!empty($pn_customers_manager_input['options']) && is_array($pn_customers_manager_input['options'])) {
              if (array_key_exists('multiple', $pn_customers_manager_input) && $pn_customers_manager_input['multiple']) {
                // Handle multiple select
                $selected_values = is_array($current_value) ? $current_value : array();
                if (!empty($selected_values)) {
                  ?>
                  <div class="pn-customers-manager-select-values pn-customers-manager-select-values-column">
                    <?php foreach ($selected_values as $value): ?>
                      <?php if (isset($pn_customers_manager_input['options'][$value])): ?>
                        <div class="pn-customers-manager-select-value-item"><?php echo esc_html($pn_customers_manager_input['options'][$value]); ?></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                  <?php
                }
              } else {
                // Handle single select
                $current_value = is_scalar($current_value) ? (string)$current_value : '';
                if (isset($pn_customers_manager_input['options'][$current_value])) {
                  ?>
                  <span class="pn-customers-manager-select-value"><?php echo esc_html($pn_customers_manager_input['options'][$current_value]); ?></span>
                  <?php
                }
              }
            }
            break;

          case 'textarea':
            ?>
              <div class="pn-customers-manager-textarea-value"><?php echo wp_kses_post(nl2br($current_value)); ?></div>
            <?php
            break;
          case 'image':
            if (!empty($current_value)) {
              $image_ids = is_array($current_value) ? $current_value : explode(',', $current_value);
              ?>
                <div class="pn-customers-manager-image-gallery">
                  <?php foreach ($image_ids as $image_id): ?>
                    <div class="pn-customers-manager-image-item">
                      <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php
            } else {
              ?>
                <span class="pn-customers-manager-no-image"><?php esc_html_e('No images uploaded', 'pn-customers-manager'); ?></span>
              <?php
            }
            break;
          case 'editor':
            ?>
              <div class="pn-customers-manager-editor-content"><?php echo wp_kses_post($current_value); ?></div>
            <?php
            break;
          case 'html':
            if (!empty($pn_customers_manager_input['html_content'])) {
              ?>
                <div class="pn-customers-manager-html-content"><?php echo wp_kses_post(do_shortcode($pn_customers_manager_input['html_content'])); ?></div>
              <?php
            }
            break;
          case 'html_multi':
            switch ($pn_customers_manager_type) {
              case 'user':
                $html_multi_fields_length = !empty(get_user_meta($pn_customers_manager_id, $pn_customers_manager_input['html_multi_fields'][0]['id'], true)) ? count(get_user_meta($pn_customers_manager_id, $pn_customers_manager_input['html_multi_fields'][0]['id'], true)) : 0;
                break;
              case 'post':
                $html_multi_fields_length = !empty(get_post_meta($pn_customers_manager_id, $pn_customers_manager_input['html_multi_fields'][0]['id'], true)) ? count(get_post_meta($pn_customers_manager_id, $pn_customers_manager_input['html_multi_fields'][0]['id'], true)) : 0;
                break;
              case 'option':
                $html_multi_fields_length = !empty(get_option($pn_customers_manager_input['html_multi_fields'][0]['id'])) ? count(get_option($pn_customers_manager_input['html_multi_fields'][0]['id'])) : 0;
            }

            ?>
              <div class="pn-customers-manager-html-multi-content">
                <?php if ($html_multi_fields_length): ?>
                  <?php foreach (range(0, ($html_multi_fields_length - 1)) as $length_index): ?>
                    <div class="pn-customers-manager-html-multi-group pn-customers-manager-display-table pn-customers-manager-width-100-percent pn-customers-manager-mb-30">
                      <?php foreach ($pn_customers_manager_input['html_multi_fields'] as $index => $html_multi_field): ?>
                          <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-60-percent">
                            <label><?php echo esc_html($html_multi_field['label']); ?></label>
                          </div>

                          <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-40-percent">
                            <?php self::PN_CUSTOMERS_MANAGER_input_display($html_multi_field, $pn_customers_manager_type, $pn_customers_manager_id, 1, $length_index); ?>
                          </div>
                      <?php endforeach ?>
                    </div>
                  <?php endforeach ?>
                <?php endif; ?>
              </div>
            <?php
            break;
        }
        ?>
      </div>
    <?php
  }

  public static function pn_customers_manager_sanitizer($value, $node = '', $type = '', $field_config = []) {
    // Use the new validation system
    $result = PN_CUSTOMERS_MANAGER_Validation::PN_CUSTOMERS_MANAGER_validate_and_sanitize($value, $node, $type, $field_config);
    
    // If validation failed, return empty value and log the error
    if (is_wp_error($result)) {
        return '';
    }
    
    return $result;
  }
}