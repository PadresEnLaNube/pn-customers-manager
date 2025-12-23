<?php
/**
 * Fired from activate() function.
 *
 * This class defines all post types necessary to run during the plugin's life cycle.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Forms {
  /**
   * Plaform forms.
   *
   * @since    1.0.0
   */

  /**
   * Get the current value of a field based on its type and storage
   * 
   * @param string $field_id The field ID
   * @param string $customers_manager_pn_type The type of field (user, post, option)
   * @param int $customers_manager_pn_id The ID of the user/post/option
   * @param int $customers_manager_pn_meta_array Whether the field is part of a meta array
   * @param int $customers_manager_pn_array_index The index in the meta array
   * @param array $customers_manager_pn_input The input array containing field configuration
   * @return mixed The current value of the field
   */
  private static function customers_manager_pn_get_field_value($field_id, $customers_manager_pn_type, $customers_manager_pn_id = 0, $customers_manager_pn_meta_array = 0, $customers_manager_pn_array_index = 0, $customers_manager_pn_input = []) {
    $current_value = '';

    if ($customers_manager_pn_meta_array) {
      switch ($customers_manager_pn_type) {
        case 'user':
          $meta = get_user_meta($customers_manager_pn_id, $field_id, true);
          if (is_array($meta) && isset($meta[$customers_manager_pn_array_index])) {
            $current_value = $meta[$customers_manager_pn_array_index];
          }
          break;
        case 'post':
          $meta = get_post_meta($customers_manager_pn_id, $field_id, true);
          if (is_array($meta) && isset($meta[$customers_manager_pn_array_index])) {
            $current_value = $meta[$customers_manager_pn_array_index];
          }
          break;
        case 'option':
          $option = get_option($field_id);
          if (is_array($option) && isset($option[$customers_manager_pn_array_index])) {
            $current_value = $option[$customers_manager_pn_array_index];
          }
          break;
      }
    } else {
      switch ($customers_manager_pn_type) {
        case 'user':
          $current_value = get_user_meta($customers_manager_pn_id, $field_id, true);
          break;
        case 'post':
          $current_value = get_post_meta($customers_manager_pn_id, $field_id, true);
          break;
        case 'option':
          $current_value = get_option($field_id);
          break;
      }
    }

    // If no value is found and there's a default value in the input config, use it
    // BUT NOT for checkboxes in multiple fields, as empty string and 'off' are valid states (unchecked)
    if (empty($current_value) && !empty($customers_manager_pn_input['value'])) {
      // For checkboxes in multiple fields, don't override empty values or 'off' with default
      if (!($customers_manager_pn_meta_array && isset($customers_manager_pn_input['type']) && $customers_manager_pn_input['type'] === 'checkbox')) {
        $current_value = $customers_manager_pn_input['value'];
      }
    }
    
    // For checkboxes in multiple fields, normalize 'off' to empty string for display
    if ($customers_manager_pn_meta_array && isset($customers_manager_pn_input['type']) && $customers_manager_pn_input['type'] === 'checkbox' && $current_value === 'off') {
      $current_value = '';
    }

    return $current_value;
  }

  public static function customers_manager_pn_input_builder($customers_manager_pn_input, $customers_manager_pn_type, $customers_manager_pn_id = 0, $disabled = 0, $customers_manager_pn_meta_array = 0, $customers_manager_pn_array_index = 0) {
    // Get the current value using the new function
    $customers_manager_pn_value = self::customers_manager_pn_get_field_value($customers_manager_pn_input['id'], $customers_manager_pn_type, $customers_manager_pn_id, $customers_manager_pn_meta_array, $customers_manager_pn_array_index, $customers_manager_pn_input);

    $customers_manager_pn_parent_block = (!empty($customers_manager_pn_input['parent']) ? 'data-customers-manager-pn-parent="' . $customers_manager_pn_input['parent'] . '"' : '') . ' ' . (!empty($customers_manager_pn_input['parent_option']) ? 'data-customers-manager-pn-parent-option="' . $customers_manager_pn_input['parent_option'] . '"' : '');

    switch ($customers_manager_pn_input['input']) {
      case 'input':        
        switch ($customers_manager_pn_input['type']) {
          case 'file':
            ?>
              <?php if (empty($customers_manager_pn_value)): ?>
                <p class="customers-manager-pn-m-10"><?php esc_html_e('No file found', 'customers-manager-pn'); ?></p>
              <?php else: ?>
                <p class="customers-manager-pn-m-10">
                  <a href="<?php echo esc_url(get_post_meta($customers_manager_pn_id, $customers_manager_pn_input['id'], true)['url']); ?>" target="_blank"><?php echo esc_html(basename(get_post_meta($customers_manager_pn_id, $customers_manager_pn_input['id'], true)['url'])); ?></a>
                </p>
              <?php endif ?>
            <?php
            break;
          case 'checkbox':
            ?>
              <label class="customers-manager-pn-switch">
                <input id="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" class="<?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?> customers-manager-pn-checkbox customers-manager-pn-checkbox-switch customers-manager-pn-field" type="<?php echo esc_attr($customers_manager_pn_input['type']); ?>" <?php echo $customers_manager_pn_value == 'on' ? 'checked="checked"' : ''; ?> <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?> <?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] ? 'multiple' : ''); ?> <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>>
                <span class="customers-manager-pn-slider customers-manager-pn-round"></span>
              </label>
            <?php
            break;
          case 'radio':
            ?>
              <div class="customers-manager-pn-input-radio-wrapper">
                <?php if (!empty($customers_manager_pn_input['radio_options'])): ?>
                  <?php foreach ($customers_manager_pn_input['radio_options'] as $radio_option): ?>
                    <div class="customers-manager-pn-input-radio-item">
                      <label for="<?php echo esc_attr($radio_option['id']); ?>">
                        <?php echo wp_kses_post(wp_specialchars_decode($radio_option['label'])); ?>
                        
                        <input type="<?php echo esc_attr($customers_manager_pn_input['type']); ?>"
                          id="<?php echo esc_attr($radio_option['id']); ?>"
                          name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>"
                          value="<?php echo esc_attr($radio_option['value']); ?>"
                          <?php echo $customers_manager_pn_value == $radio_option['value'] ? 'checked="checked"' : ''; ?>
                          <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == 'true') ? 'required' : ''); ?>>

                        <div class="customers-manager-pn-radio-control"></div>
                      </label>
                    </div>
                  <?php endforeach ?>
                <?php endif ?>
              </div>
            <?php
            break;
          case 'range':
            ?>
              <div class="customers-manager-pn-input-range-wrapper">
                <div class="customers-manager-pn-width-100-percent">
                  <?php if (!empty($customers_manager_pn_input['customers_manager_pn_label_min'])): ?>
                    <p class="customers-manager-pn-input-range-label-min"><?php echo esc_html($customers_manager_pn_input['customers_manager_pn_label_min']); ?></p>
                  <?php endif ?>

                  <?php if (!empty($customers_manager_pn_input['customers_manager_pn_label_max'])): ?>
                    <p class="customers-manager-pn-input-range-label-max"><?php echo esc_html($customers_manager_pn_input['customers_manager_pn_label_max']); ?></p>
                  <?php endif ?>
                </div>

                <input type="<?php echo esc_attr($customers_manager_pn_input['type']); ?>" id="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" class="customers-manager-pn-input-range <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>" <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo (isset($customers_manager_pn_input['customers_manager_pn_max']) ? 'max=' . esc_attr($customers_manager_pn_input['customers_manager_pn_max']) : ''); ?> <?php echo (isset($customers_manager_pn_input['customers_manager_pn_min']) ? 'min=' . esc_attr($customers_manager_pn_input['customers_manager_pn_min']) : ''); ?> <?php echo (((array_key_exists('step', $customers_manager_pn_input) && $customers_manager_pn_input['step'] != '')) ? 'step="' . esc_attr($customers_manager_pn_input['step']) . '"' : ''); ?> <?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] ? 'multiple' : ''); ?> value="<?php echo (!empty($customers_manager_pn_input['button_text']) ? esc_html($customers_manager_pn_input['button_text']) : esc_html($customers_manager_pn_value)); ?>"/>
                <h3 class="customers-manager-pn-input-range-output"></h3>
              </div>
            <?php
            break;
          case 'stars':
            $customers_manager_pn_stars = !empty($customers_manager_pn_input['stars_number']) ? $customers_manager_pn_input['stars_number'] : 5;
            ?>
              <div class="customers-manager-pn-input-stars-wrapper">
                <div class="customers-manager-pn-width-100-percent">
                  <?php if (!empty($customers_manager_pn_input['customers_manager_pn_label_min'])): ?>
                    <p class="customers-manager-pn-input-stars-label-min"><?php echo esc_html($customers_manager_pn_input['customers_manager_pn_label_min']); ?></p>
                  <?php endif ?>

                  <?php if (!empty($customers_manager_pn_input['customers_manager_pn_label_max'])): ?>
                    <p class="customers-manager-pn-input-stars-label-max"><?php echo esc_html($customers_manager_pn_input['customers_manager_pn_label_max']); ?></p>
                  <?php endif ?>
                </div>

                <div class="customers-manager-pn-input-stars customers-manager-pn-text-align-center customers-manager-pn-pt-20">
                  <?php foreach (range(1, $customers_manager_pn_stars) as $index => $star): ?>
                    <i class="material-icons-outlined customers-manager-pn-input-star">
                      <?php echo ($index < intval($customers_manager_pn_value)) ? 'star' : 'star_outlined'; ?>
                    </i>
                  <?php endforeach ?>
                </div>

                <input type="number" <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?> <?php echo ((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') ? 'disabled' : ''); ?> id="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" class="customers-manager-pn-input-hidden-stars <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>" min="1" max="<?php echo esc_attr($customers_manager_pn_stars) ?>" value="<?php echo esc_attr($customers_manager_pn_value); ?>">
              </div>
            <?php
            break;
          case 'submit':
            $post_id_value = '';
            if (!empty($customers_manager_pn_input['post_id'])) {
              $post_id_value = esc_attr($customers_manager_pn_input['post_id']);
            } elseif (!empty($customers_manager_pn_id) && $customers_manager_pn_type === 'post') {
              $post_id_value = esc_attr($customers_manager_pn_id);
            } elseif (!empty(get_the_ID())) {
              $post_id_value = esc_attr(get_the_ID());
            }
            $post_type_value = !empty($customers_manager_pn_input['post_type']) ? esc_attr($customers_manager_pn_input['post_type']) : '';
            ?>
              <div class="customers-manager-pn-text-align-right">
                <input type="submit" value="<?php echo esc_attr($customers_manager_pn_input['value']); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" class="customers-manager-pn-btn" data-customers-manager-pn-type="<?php echo esc_attr($customers_manager_pn_type); ?>" data-customers-manager-pn-subtype="<?php echo ((array_key_exists('subtype', $customers_manager_pn_input)) ? esc_attr($customers_manager_pn_input['subtype']) : ''); ?>" data-customers-manager-pn-user-id="<?php echo esc_attr($customers_manager_pn_id); ?>" data-customers-manager-pn-post-id="<?php echo $post_id_value; ?>" <?php echo !empty($post_type_value) ? 'data-customers-manager-pn-post-type="' . $post_type_value . '"' : ''; ?>/><?php esc_html(CUSTOMERS_MANAGER_PN_Data::customers_manager_pn_loader()); ?>
              </div>
            <?php
            break;
          case 'hidden':
            ?>
              <input type="hidden" id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" value="<?php echo esc_attr($customers_manager_pn_value); ?>" <?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] == 'true' ? 'multiple' : ''); ?>>
            <?php
            break;
          case 'nonce':
            ?>
              <input type="hidden" id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" value="<?php echo esc_attr(wp_create_nonce('customers-manager-pn-nonce')); ?>">
            <?php
            break;
          case 'password':
            ?>
              <div class="customers-manager-pn-password-checker">
                <div class="customers-manager-pn-password-input customers-manager-pn-position-relative">
                  <input id="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] == 'true') ? '[]' : ''); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] == 'true') ? '[]' : ''); ?>" <?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] == 'true' ? 'multiple' : ''); ?> class="customers-manager-pn-field customers-manager-pn-password-strength <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>" type="<?php echo esc_attr($customers_manager_pn_input['type']); ?>" <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == 'true') ? 'required' : ''); ?> <?php echo ((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') ? 'disabled' : ''); ?> value="<?php echo (!empty($customers_manager_pn_input['button_text']) ? esc_html($customers_manager_pn_input['button_text']) : esc_attr($customers_manager_pn_value)); ?>" placeholder="<?php echo (array_key_exists('placeholder', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['placeholder']) : ''); ?>" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>/>

                  <a href="#" class="customers-manager-pn-show-pass customers-manager-pn-cursor-pointer customers-manager-pn-display-none-soft">
                    <i class="material-icons-outlined customers-manager-pn-font-size-20">visibility</i>
                  </a>
                </div>

                <div id="customers-manager-pn-popover-pass" class="customers-manager-pn-display-none-soft">
                  <div class="customers-manager-pn-progress-bar-wrapper">
                    <div class="customers-manager-pn-password-strength-bar"></div>
                  </div>

                  <h3 class="customers-manager-pn-mt-20"><?php esc_html_e('Password strength checker', 'customers-manager-pn'); ?> <i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-close-icon customers-manager-pn-mt-30">close</i></h3>
                  <ul class="customers-manager-pn-list-style-none">
                    <li class="low-upper-case">
                      <i class="material-icons-outlined customers-manager-pn-font-size-20 customers-manager-pn-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Lowercase & Uppercase', 'customers-manager-pn'); ?></span>
                    </li>
                    <li class="one-number">
                      <i class="material-icons-outlined customers-manager-pn-font-size-20 customers-manager-pn-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Number (0-9)', 'customers-manager-pn'); ?></span>
                    </li>
                    <li class="one-special-char">
                      <i class="material-icons-outlined customers-manager-pn-font-size-20 customers-manager-pn-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Special Character (!@#$%^&*)', 'customers-manager-pn'); ?></span>
                    </li>
                    <li class="eight-character">
                      <i class="material-icons-outlined customers-manager-pn-font-size-20 customers-manager-pn-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Atleast 8 Character', 'customers-manager-pn'); ?></span>
                    </li>
                  </ul>
                </div>
              </div>
            <?php
            break;
          case 'color':
            ?>
              <input id="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" <?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] ? 'multiple' : ''); ?> class="customers-manager-pn-field <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>" type="<?php echo esc_attr($customers_manager_pn_input['type']); ?>" <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> value="<?php echo (!empty($customers_manager_pn_value) ? esc_attr($customers_manager_pn_value) : '#000000'); ?>" placeholder="<?php echo (array_key_exists('placeholder', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['placeholder']) : ''); ?>" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>/>
            <?php
            break;
          default:
            ?>
              <input 
                <?php /* ID and name attributes */ ?>
                id="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" 
                name="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>"
                
                <?php /* Type and styling */ ?>
                class="customers-manager-pn-field <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>" 
                type="<?php echo esc_attr($customers_manager_pn_input['type']); ?>"
                
                <?php /* State attributes */ ?>
                <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?>
                <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
                <?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] ? 'multiple' : ''); ?>
                
                <?php /* Validation and limits */ ?>
                <?php echo (((array_key_exists('step', $customers_manager_pn_input) && $customers_manager_pn_input['step'] != '')) ? 'step="' . esc_attr($customers_manager_pn_input['step']) . '"' : ''); ?>
                <?php echo (isset($customers_manager_pn_input['max']) ? 'max="' . esc_attr($customers_manager_pn_input['max']) . '"' : ''); ?>
                <?php echo (isset($customers_manager_pn_input['min']) ? 'min="' . esc_attr($customers_manager_pn_input['min']) . '"' : ''); ?>
                <?php echo (isset($customers_manager_pn_input['maxlength']) ? 'maxlength="' . esc_attr($customers_manager_pn_input['maxlength']) . '"' : ''); ?>
                <?php echo (isset($customers_manager_pn_input['pattern']) ? 'pattern="' . esc_attr($customers_manager_pn_input['pattern']) . '"' : ''); ?>
                
                <?php /* Content attributes */ ?>
                value="<?php echo (!empty($customers_manager_pn_input['button_text']) ? esc_html($customers_manager_pn_input['button_text']) : esc_html($customers_manager_pn_value)); ?>"
                placeholder="<?php echo (array_key_exists('placeholder', $customers_manager_pn_input) ? esc_html($customers_manager_pn_input['placeholder']) : ''); ?>"
                
                <?php /* Custom data attributes */ ?>
                <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>
              />
            <?php
            break;
        }
        break;
      case 'select':
        if (!empty($customers_manager_pn_input['options']) && is_array($customers_manager_pn_input['options'])) {
          ?>
          <select 
            id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" 
            name="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" 
            class="customers-manager-pn-field <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>"
            <?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? 'multiple' : ''; ?>
            <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?>
            <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
            <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>
          >
            <?php if (array_key_exists('placeholder', $customers_manager_pn_input) && !empty($customers_manager_pn_input['placeholder'])): ?>
              <option value=""><?php echo esc_html($customers_manager_pn_input['placeholder']); ?></option>
            <?php endif; ?>
            
            <?php 
            $selected_values = array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] ? 
              (is_array($customers_manager_pn_value) ? $customers_manager_pn_value : array()) : 
              array($customers_manager_pn_value);
            
            foreach ($customers_manager_pn_input['options'] as $value => $label): 
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
          <textarea id="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']) . ((array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? '[]' : ''); ?>" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?> class="customers-manager-pn-field <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>" <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple'] ? 'multiple' : ''); ?> placeholder="<?php echo (array_key_exists('placeholder', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['placeholder']) : ''); ?>"><?php echo esc_html($customers_manager_pn_value); ?></textarea>
        <?php
        break;
      case 'image':
        ?>
          <div class="customers-manager-pn-field customers-manager-pn-images-block" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?> data-customers-manager-pn-multiple="<?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? 'true' : 'false'; ?>">
            <?php if (!empty($customers_manager_pn_value)): ?>
              <div class="customers-manager-pn-images">
                <?php foreach (explode(',', $customers_manager_pn_value) as $customers_manager_pn_image): ?>
                  <?php echo wp_get_attachment_image($customers_manager_pn_image, 'medium'); ?>
                <?php endforeach ?>
              </div>

              <div class="customers-manager-pn-text-align-center customers-manager-pn-position-relative"><a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-image-btn"><?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? esc_html(__('Edit images', 'customers-manager-pn')) : esc_html(__('Edit image', 'customers-manager-pn')); ?></a></div>
            <?php else: ?>
              <div class="customers-manager-pn-images"></div>

              <div class="customers-manager-pn-text-align-center customers-manager-pn-position-relative"><a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-image-btn"><?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? esc_html(__('Add images', 'customers-manager-pn')) : esc_html(__('Add image', 'customers-manager-pn')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" class="customers-manager-pn-display-none customers-manager-pn-image-input" type="text" value="<?php echo esc_attr($customers_manager_pn_value); ?>"/>
          </div>
        <?php
        break;
      case 'video':
        ?>
        <div class="customers-manager-pn-field customers-manager-pn-videos-block" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>>
            <?php if (!empty($customers_manager_pn_value)): ?>
              <div class="customers-manager-pn-videos">
                <?php foreach (explode(',', $customers_manager_pn_value) as $customers_manager_pn_video): ?>
                  <div class="customers-manager-pn-video customers-manager-pn-tooltip" title="<?php echo esc_html(get_the_title($customers_manager_pn_video)); ?>"><i class="dashicons dashicons-media-video"></i></div>
                <?php endforeach ?>
              </div>

              <div class="customers-manager-pn-text-align-center customers-manager-pn-position-relative"><a href="#" class="customers-manager-pn-btn customers-manager-pn-video-btn"><?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? esc_html(__('Edit videos', 'customers-manager-pn')) : esc_html(__('Edit video', 'customers-manager-pn')); ?></a></div>
            <?php else: ?>
              <div class="customers-manager-pn-videos"></div>

              <div class="customers-manager-pn-text-align-center customers-manager-pn-position-relative"><a href="#" class="customers-manager-pn-btn customers-manager-pn-video-btn"><?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? esc_html(__('Add videos', 'customers-manager-pn')) : esc_html(__('Add video', 'customers-manager-pn')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" class="customers-manager-pn-display-none customers-manager-pn-video-input" type="text" value="<?php echo esc_attr($customers_manager_pn_value); ?>"/>
          </div>
        <?php
        break;
      case 'audio':
        ?>
          <div class="customers-manager-pn-field customers-manager-pn-audios-block" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>>
            <?php if (!empty($customers_manager_pn_value)): ?>
              <div class="customers-manager-pn-audios">
                <?php foreach (explode(',', $customers_manager_pn_value) as $customers_manager_pn_audio): ?>
                  <div class="customers-manager-pn-audio customers-manager-pn-tooltip" title="<?php echo esc_html(get_the_title($customers_manager_pn_audio)); ?>"><i class="dashicons dashicons-media-audio"></i></div>
                <?php endforeach ?>
              </div>

              <div class="customers-manager-pn-text-align-center customers-manager-pn-position-relative"><a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-audio-btn"><?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? esc_html(__('Edit audios', 'customers-manager-pn')) : esc_html(__('Edit audio', 'customers-manager-pn')); ?></a></div>
            <?php else: ?>
              <div class="customers-manager-pn-audios"></div>

              <div class="customers-manager-pn-text-align-center customers-manager-pn-position-relative"><a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-audio-btn"><?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? esc_html(__('Add audios', 'customers-manager-pn')) : esc_html(__('Add audio', 'customers-manager-pn')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" class="customers-manager-pn-display-none customers-manager-pn-audio-input" type="text" value="<?php echo esc_attr($customers_manager_pn_value); ?>"/>
          </div>
        <?php
        break;
      case 'file':
        ?>
          <div class="customers-manager-pn-field customers-manager-pn-files-block" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>>
            <?php if (!empty($customers_manager_pn_value)): ?>
              <div class="customers-manager-pn-files customers-manager-pn-text-align-center">
                <?php foreach (explode(',', $customers_manager_pn_value) as $customers_manager_pn_file): ?>
                  <embed src="<?php echo esc_url(wp_get_attachment_url($customers_manager_pn_file)); ?>" type="application/pdf" class="customers-manager-pn-embed-file"/>
                <?php endforeach ?>
              </div>

              <div class="customers-manager-pn-text-align-center customers-manager-pn-position-relative"><a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-file-btn"><?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? esc_html(__('Edit files', 'customers-manager-pn')) : esc_html(__('Edit file', 'customers-manager-pn')); ?></a></div>
            <?php else: ?>
              <div class="customers-manager-pn-files"></div>

              <div class="customers-manager-pn-text-align-center customers-manager-pn-position-relative"><a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-btn-mini customers-manager-pn-file-btn"><?php echo (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) ? esc_html(__('Add files', 'customers-manager-pn')) : esc_html(__('Add file', 'customers-manager-pn')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" class="customers-manager-pn-display-none customers-manager-pn-file-input customers-manager-pn-btn-mini" type="text" value="<?php echo esc_attr($customers_manager_pn_value); ?>"/>
          </div>
        <?php
        break;
      case 'editor':
        ?>
          <div class="customers-manager-pn-field" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>>
            <textarea id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" class="customers-manager-pn-input customers-manager-pn-width-100-percent customers-manager-pn-wysiwyg"><?php echo ((empty($customers_manager_pn_value)) ? (array_key_exists('placeholder', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['placeholder']) : '') : esc_html($customers_manager_pn_value)); ?></textarea>
          </div>
        <?php
        break;
      case 'html':
        ?>
          <div class="customers-manager-pn-field" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>>
            <?php echo !empty($customers_manager_pn_input['html_content']) ? wp_kses(do_shortcode($customers_manager_pn_input['html_content']), CUSTOMERS_MANAGER_PN_KSES) : ''; ?>
          </div>
        <?php
        break;
      case 'html_multi':
        switch ($customers_manager_pn_type) {
          case 'user':
            $html_multi_fields_length = !empty(get_user_meta($customers_manager_pn_id, $customers_manager_pn_input['html_multi_fields'][0]['id'], true)) ? count(get_user_meta($customers_manager_pn_id, $customers_manager_pn_input['html_multi_fields'][0]['id'], true)) : 0;
            break;
          case 'post':
            $html_multi_fields_length = !empty(get_post_meta($customers_manager_pn_id, $customers_manager_pn_input['html_multi_fields'][0]['id'], true)) ? count(get_post_meta($customers_manager_pn_id, $customers_manager_pn_input['html_multi_fields'][0]['id'], true)) : 0;
            break;
          case 'option':
            $html_multi_fields_length = !empty(get_option($customers_manager_pn_input['html_multi_fields'][0]['id'])) ? count(get_option($customers_manager_pn_input['html_multi_fields'][0]['id'])) : 0;
        }

        ?>
          <div class="customers-manager-pn-field customers-manager-pn-html-multi-wrapper customers-manager-pn-mb-50" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>>
            <?php if ($html_multi_fields_length): ?>
              <?php foreach (range(0, ($html_multi_fields_length - 1)) as $length_index): ?>
                <div class="customers-manager-pn-html-multi-group customers-manager-pn-display-table customers-manager-pn-width-100-percent customers-manager-pn-mb-30">
                  <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-90-percent">
                    <?php foreach ($customers_manager_pn_input['html_multi_fields'] as $index => $html_multi_field): ?>
                      <?php if (isset($html_multi_field['label']) && !empty($html_multi_field['label'])): ?>
                        <label><?php echo esc_html($html_multi_field['label']); ?></label>
                      <?php endif; ?>

                      <?php self::customers_manager_pn_input_builder($html_multi_field, $customers_manager_pn_type, $customers_manager_pn_id, false, true, $length_index); ?>
                    <?php endforeach ?>
                  </div>
                  <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-10-percent customers-manager-pn-text-align-center">
                    <i class="material-icons-outlined customers-manager-pn-cursor-move customers-manager-pn-multi-sorting customers-manager-pn-vertical-align-super customers-manager-pn-tooltip" title="<?php esc_html_e('Order element', 'customers-manager-pn'); ?>">drag_handle</i>
                  </div>

                  <div class="customers-manager-pn-text-align-right">
                    <a href="#" class="customers-manager-pn-html-multi-remove-btn"><i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-tooltip" title="<?php esc_html_e('Remove element', 'customers-manager-pn'); ?>">remove</i></a>
                  </div>
                </div>
              <?php endforeach ?>
            <?php else: ?>
              <div class="customers-manager-pn-html-multi-group customers-manager-pn-mb-50">
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-90-percent">
                  <?php foreach ($customers_manager_pn_input['html_multi_fields'] as $html_multi_field): ?>
                    <?php self::customers_manager_pn_input_builder($html_multi_field, $customers_manager_pn_type); ?>
                  <?php endforeach ?>
                </div>
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-10-percent customers-manager-pn-text-align-center">
                  <i class="material-icons-outlined customers-manager-pn-cursor-move customers-manager-pn-multi-sorting customers-manager-pn-vertical-align-super customers-manager-pn-tooltip" title="<?php esc_html_e('Order element', 'customers-manager-pn'); ?>">drag_handle</i>
                </div>

                <div class="customers-manager-pn-text-align-right">
                  <a href="#" class="customers-manager-pn-html-multi-remove-btn customers-manager-pn-tooltip" title="<?php esc_html_e('Remove element', 'customers-manager-pn'); ?>"><i class="material-icons-outlined customers-manager-pn-cursor-pointer">remove</i></a>
                </div>
              </div>
            <?php endif ?>

            <div class="customers-manager-pn-text-align-right">
              <a href="#" class="customers-manager-pn-html-multi-add-btn customers-manager-pn-tooltip" title="<?php esc_html_e('Add element', 'customers-manager-pn'); ?>"><i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-font-size-40">add</i></a>
            </div>
          </div>
        <?php
        break;
      case 'audio_recorder':
        // Enqueue CSS and JS files for audio recorder
        wp_enqueue_style('customers-manager-pn-audio-recorder', CUSTOMERS_MANAGER_PN_URL . 'assets/css/customers-manager-pn-audio-recorder.css', array(), '1.0.0');
        wp_enqueue_script('customers-manager-pn-audio-recorder', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-audio-recorder.js', array('jquery'), '1.0.0', true);
        
        // Localize script with AJAX data
        wp_localize_script('customers-manager-pn-audio-recorder', 'customers_manager_pn_audio_recorder_vars', array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('customers_manager_pn_audio_nonce'),
        ));
        
        ?>
          <div class="customers-manager-pn-audio-recorder-status customers-manager-pn-display-none-soft">
            <p class="customers-manager-pn-recording-status"><?php esc_html_e('Ready to record', 'customers-manager-pn'); ?></p>
          </div>
          
          <div class="customers-manager-pn-audio-recorder-wrapper">
            <div class="customers-manager-pn-audio-recorder-controls">
              <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-center customers-manager-pn-mb-20">
                  <button type="button" class="customers-manager-pn-btn customers-manager-pn-btn-primary customers-manager-pn-start-recording" <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined customers-manager-pn-vertical-align-middle">mic</i>
                    <?php esc_html_e('Start recording', 'customers-manager-pn'); ?>
                  </button>
                </div>

                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-center customers-manager-pn-mb-20">
                  <button type="button" class="customers-manager-pn-btn customers-manager-pn-btn-secondary customers-manager-pn-stop-recording" style="display: none;" <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined customers-manager-pn-vertical-align-middle">stop</i>
                    <?php esc_html_e('Stop recording', 'customers-manager-pn'); ?>
                  </button>
                </div>
              </div>

              <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-center customers-manager-pn-mb-20">
                  <button type="button" class="customers-manager-pn-btn customers-manager-pn-btn-secondary customers-manager-pn-play-audio" style="display: none;" <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined customers-manager-pn-vertical-align-middle">play_arrow</i>
                    <?php esc_html_e('Play audio', 'customers-manager-pn'); ?>
                  </button>
                </div>

                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-center customers-manager-pn-mb-20">
                  <button type="button" class="customers-manager-pn-btn customers-manager-pn-btn-secondary customers-manager-pn-stop-audio" style="display: none;" <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined customers-manager-pn-vertical-align-middle">stop</i>
                    <?php esc_html_e('Stop audio', 'customers-manager-pn'); ?>
                  </button>
                </div>
              </div>
            </div>

            <div class="customers-manager-pn-audio-recorder-visualizer" style="display: none;">
              <canvas class="customers-manager-pn-audio-canvas" width="300" height="60"></canvas>
            </div>

            <div class="customers-manager-pn-audio-recorder-timer" style="display: none;">
              <span class="customers-manager-pn-recording-time">00:00</span>
            </div>

            <div class="customers-manager-pn-audio-transcription-controls customers-manager-pn-display-none-soft customers-manager-pn-display-table customers-manager-pn-width-100-percent customers-manager-pn-mb-20">
              <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-center">
                <button type="button" class="customers-manager-pn-btn customers-manager-pn-btn-primary customers-manager-pn-transcribe-audio" <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                  <i class="material-icons-outlined customers-manager-pn-vertical-align-middle">translate</i>
                  <?php esc_html_e('Transcribe Audio', 'customers-manager-pn'); ?>
                </button>
              </div>

              <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-50-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-center">
                <button type="button" class="customers-manager-pn-btn customers-manager-pn-btn-secondary customers-manager-pn-clear-transcription" <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                  <i class="material-icons-outlined customers-manager-pn-vertical-align-middle">clear</i>
                  <?php esc_html_e('Clear', 'customers-manager-pn'); ?>
                </button>
              </div>
            </div>

            <div class="customers-manager-pn-audio-transcription-loading">
              <?php echo esc_html(CUSTOMERS_MANAGER_PN_Data::customers_manager_pn_loader()); ?>
            </div>

            <div class="customers-manager-pn-audio-transcription-result">
              <textarea 
                id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" 
                name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" 
                class="customers-manager-pn-field customers-manager-pn-transcription-textarea <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>" 
                placeholder="<?php echo (array_key_exists('placeholder', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['placeholder']) : esc_attr__('Transcribed text will appear here...', 'customers-manager-pn')); ?>"
                <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?>
                <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
                <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>
                rows="4"
                style="width: 100%; margin-top: 10px;"
              ><?php echo esc_textarea($customers_manager_pn_value); ?></textarea>
            </div>

            <div class="customers-manager-pn-audio-transcription-error customers-manager-pn-display-none-soft">
              <p class="customers-manager-pn-error-message"></p>
            </div>

            <div class="customers-manager-pn-audio-transcription-success customers-manager-pn-display-none-soft">
              <p class="customers-manager-pn-success-message"></p>
            </div>

            <!-- Hidden input to store audio data -->
            <input type="hidden" 
                  id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>_audio_data" 
                  name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>_audio_data" 
                  value="" />
          </div>
        <?php
        break;
      case 'tags':
        // Get current tags value
        $current_tags = self::customers_manager_pn_get_customers_manager_pn_value($customers_manager_pn_type, $customers_manager_pn_id, $customers_manager_pn_input);
        $tags_array = is_array($current_tags) ? $current_tags : [];
        $tags_string = implode(', ', $tags_array);
        ?>
        <div class="customers-manager-pn-tags-wrapper" <?php echo wp_kses_post($customers_manager_pn_parent_block); ?>>
          <input type="text" 
            id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" 
            name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>" 
            class="customers-manager-pn-field customers-manager-pn-tags-input <?php echo array_key_exists('class', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['class']) : ''; ?>" 
            value="<?php echo esc_attr($tags_string); ?>" 
            placeholder="<?php echo (array_key_exists('placeholder', $customers_manager_pn_input) ? esc_attr($customers_manager_pn_input['placeholder']) : ''); ?>"
            <?php echo ((array_key_exists('required', $customers_manager_pn_input) && $customers_manager_pn_input['required'] == true) ? 'required' : ''); ?>
            <?php echo (((array_key_exists('disabled', $customers_manager_pn_input) && $customers_manager_pn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> />
          
          <div class="customers-manager-pn-tags-suggestions" style="display: none;">
            <div class="customers-manager-pn-tags-suggestions-list"></div>
          </div>
          
          <div class="customers-manager-pn-tags-display">
            <?php if (!empty($tags_array)): ?>
              <?php foreach ($tags_array as $tag): ?>
                <span class="customers-manager-pn-tag">
                  <?php echo esc_html($tag); ?>
                  <i class="material-icons-outlined customers-manager-pn-tag-remove">close</i>
                </span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <input type="hidden" 
            id="<?php echo esc_attr($customers_manager_pn_input['id']); ?>_tags_array" 
            name="<?php echo esc_attr($customers_manager_pn_input['id']); ?>_tags_array" 
            value="<?php echo esc_attr(json_encode($tags_array)); ?>" />
        </div>
        <?php
        break;
    }
  }

  public static function customers_manager_pn_input_wrapper_builder($input_array, $type, $customers_manager_pn_id = 0, $disabled = 0, $customers_manager_pn_format = 'half'){
    ?>
      <?php if (array_key_exists('section', $input_array) && !empty($input_array['section'])): ?>      
        <?php if ($input_array['section'] == 'start'): ?>
          <div class="customers-manager-pn-toggle-wrapper customers-manager-pn-section-wrapper customers-manager-pn-position-relative customers-manager-pn-mb-30 <?php echo array_key_exists('class', $input_array) ? esc_attr($input_array['class']) : ''; ?>" id="<?php echo array_key_exists('id', $input_array) ? esc_attr($input_array['id']) : ''; ?>">
            <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
              <i class="material-icons-outlined customers-manager-pn-section-helper customers-manager-pn-color-main-0 customers-manager-pn-tooltip" title="<?php echo wp_kses_post($input_array['description']); ?>">help</i>
            <?php endif ?>

            <a href="#" class="customers-manager-pn-toggle customers-manager-pn-width-100-percent customers-manager-pn-text-decoration-none">
              <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent customers-manager-pn-mb-20">
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-90-percent">
                  <label class="customers-manager-pn-cursor-pointer customers-manager-pn-mb-20 customers-manager-pn-color-main-0"><?php echo wp_kses_post($input_array['label']); ?></label>
                </div>
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-10-percent customers-manager-pn-text-align-right">
                  <i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-color-main-0">add</i>
                </div>
              </div>
            </a>

            <div class="customers-manager-pn-content customers-manager-pn-pl-10 customers-manager-pn-toggle-content customers-manager-pn-mb-20 customers-manager-pn-display-none-soft">
        <?php elseif ($input_array['section'] == 'end'): ?>
            </div>
          </div>
        <?php endif ?>
      <?php else: ?>
        <div class="customers-manager-pn-input-wrapper <?php echo esc_attr($input_array['id']); ?> <?php echo !empty($input_array['tabs']) ? 'customers-manager-pn-input-tabbed' : ''; ?> customers-manager-pn-input-field-<?php echo esc_attr($input_array['input']); ?> <?php echo (!empty($input_array['required']) && $input_array['required'] == true) ? 'customers-manager-pn-input-field-required' : ''; ?> <?php echo ($disabled) ? 'customers-manager-pn-input-field-disabled' : ''; ?> customers-manager-pn-mb-30">
          <?php if (array_key_exists('label', $input_array) && !empty($input_array['label'])): ?>
            <div class="customers-manager-pn-display-inline-table <?php echo (($customers_manager_pn_format == 'half' && !(array_key_exists('type', $input_array) && $input_array['type'] == 'submit')) ? 'customers-manager-pn-width-40-percent' : 'customers-manager-pn-width-100-percent'); ?> customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-vertical-align-top">
              <div class="customers-manager-pn-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'customers-manager-pn-pl-30' : ''; ?>">
                <label class="customers-manager-pn-vertical-align-middle customers-manager-pn-display-block <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? 'customers-manager-pn-toggle' : ''; ?>" for="<?php echo esc_attr($input_array['id']); ?>"><?php echo wp_kses($input_array['label'], CUSTOMERS_MANAGER_PN_KSES); ?> <?php echo (array_key_exists('required', $input_array) && !empty($input_array['required']) && $input_array['required'] == true) ? '<span class="customers-manager-pn-tooltip" title="' . esc_html(__('Required field', 'customers-manager-pn')) . '">*</span>' : ''; ?><?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? '<i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-float-right">add</i>' : ''; ?></label>

                <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
                  <div class="customers-manager-pn-toggle-content customers-manager-pn-display-none-soft">
                    <small><?php echo wp_kses_post(wp_specialchars_decode($input_array['description'])); ?></small>
                  </div>
                <?php endif ?>
              </div>
            </div>
          <?php endif ?>

          <div class="customers-manager-pn-display-inline-table <?php echo ((array_key_exists('label', $input_array) && empty($input_array['label'])) ? 'customers-manager-pn-width-100-percent' : (($customers_manager_pn_format == 'half' && !(array_key_exists('type', $input_array) && $input_array['type'] == 'submit')) ? 'customers-manager-pn-width-60-percent' : 'customers-manager-pn-width-100-percent')); ?> customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-vertical-align-top">
            <div class="customers-manager-pn-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'customers-manager-pn-pl-30' : ''; ?>">
              <div class="customers-manager-pn-input-field"><?php self::customers_manager_pn_input_builder($input_array, $type, $customers_manager_pn_id, $disabled); ?></div>
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
   * @param int $customers_manager_pn_id The ID of the user/post/option
   * @param int $customers_manager_pn_meta_array Whether the field is part of a meta array
   * @param int $customers_manager_pn_array_index The index in the meta array
   * @param string $customers_manager_pn_format The display format ('half' or 'full')
   * @return string Formatted HTML output
   */
  public static function customers_manager_pn_input_display_wrapper($input_array, $type, $customers_manager_pn_id = 0, $customers_manager_pn_meta_array = 0, $customers_manager_pn_array_index = 0, $customers_manager_pn_format = 'half') {
    ob_start();
    ?>
    <?php if (array_key_exists('section', $input_array) && !empty($input_array['section'])): ?>      
      <?php if ($input_array['section'] == 'start'): ?>
        <div class="customers-manager-pn-toggle-wrapper customers-manager-pn-section-wrapper customers-manager-pn-position-relative customers-manager-pn-mb-30 <?php echo array_key_exists('class', $input_array) ? esc_attr($input_array['class']) : ''; ?>" id="<?php echo array_key_exists('id', $input_array) ? esc_attr($input_array['id']) : ''; ?>">
          <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
            <i class="material-icons-outlined customers-manager-pn-section-helper customers-manager-pn-color-main-0 customers-manager-pn-tooltip" title="<?php echo wp_kses_post($input_array['description']); ?>">help</i>
          <?php endif ?>

          <a href="#" class="customers-manager-pn-toggle customers-manager-pn-width-100-percent customers-manager-pn-text-decoration-none">
            <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent customers-manager-pn-mb-20">
              <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-90-percent">
                <label class="customers-manager-pn-cursor-pointer customers-manager-pn-mb-20 customers-manager-pn-color-main-0"><?php echo wp_kses($input_array['label'], CUSTOMERS_MANAGER_PN_KSES); ?></label>
              </div>
              <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-10-percent customers-manager-pn-text-align-right">
                <i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-color-main-0">add</i>
              </div>
            </div>
          </a>

          <div class="customers-manager-pn-content customers-manager-pn-pl-10 customers-manager-pn-toggle-content customers-manager-pn-mb-20 customers-manager-pn-display-none-soft">
      <?php elseif ($input_array['section'] == 'end'): ?>
          </div>
        </div>
      <?php endif ?>
    <?php else: ?>
      <div class="customers-manager-pn-input-wrapper <?php echo esc_attr($input_array['id']); ?> customers-manager-pn-input-display-<?php echo esc_attr($input_array['input']); ?> <?php echo (!empty($input_array['required']) && $input_array['required'] == true) ? 'customers-manager-pn-input-field-required' : ''; ?> customers-manager-pn-mb-30">
        <?php if (array_key_exists('label', $input_array) && !empty($input_array['label'])): ?>
          <div class="customers-manager-pn-display-inline-table <?php echo ($customers_manager_pn_format == 'half' ? 'customers-manager-pn-width-40-percent' : 'customers-manager-pn-width-100-percent'); ?> customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-vertical-align-top">
            <div class="customers-manager-pn-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'customers-manager-pn-pl-30' : ''; ?>">
              <label class="customers-manager-pn-vertical-align-middle customers-manager-pn-display-block <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? 'customers-manager-pn-toggle' : ''; ?>" for="<?php echo esc_attr($input_array['id']); ?>">
                <?php echo wp_kses($input_array['label'], CUSTOMERS_MANAGER_PN_KSES); ?>
                <?php echo (array_key_exists('required', $input_array) && !empty($input_array['required']) && $input_array['required'] == true) ? '<span class="customers-manager-pn-tooltip" title="' . esc_html(__('Required field', 'customers-manager-pn')) . '">*</span>' : ''; ?>
                <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? '<i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-float-right">add</i>' : ''; ?>
              </label>

              <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
                <div class="customers-manager-pn-toggle-content customers-manager-pn-display-none-soft">
                  <small><?php echo wp_kses_post(wp_specialchars_decode($input_array['description'])); ?></small>
                </div>
              <?php endif ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="customers-manager-pn-display-inline-table <?php echo ((array_key_exists('label', $input_array) && empty($input_array['label'])) ? 'customers-manager-pn-width-100-percent' : ($customers_manager_pn_format == 'half' ? 'customers-manager-pn-width-60-percent' : 'customers-manager-pn-width-100-percent')); ?> customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-vertical-align-top">
          <div class="customers-manager-pn-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'customers-manager-pn-pl-30' : ''; ?>">
            <div class="customers-manager-pn-input-field">
              <?php self::customers_manager_pn_input_display($input_array, $type, $customers_manager_pn_id, $customers_manager_pn_meta_array, $customers_manager_pn_array_index); ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
  }

  /**
   * Display formatted values of customers_manager_pn_input_builder fields in frontend
   * 
   * @param array $customers_manager_pn_input The input array containing field configuration
   * @param string $customers_manager_pn_type The type of field (user, post, option)
   * @param int $customers_manager_pn_id The ID of the user/post/option
   * @param int $customers_manager_pn_meta_array Whether the field is part of a meta array
   * @param int $customers_manager_pn_array_index The index in the meta array
   * @return string Formatted HTML output of field values
   */
  public static function customers_manager_pn_input_display($customers_manager_pn_input, $customers_manager_pn_type, $customers_manager_pn_id = 0, $customers_manager_pn_meta_array = 0, $customers_manager_pn_array_index = 0) {
    // Get the current value using the new function
    $current_value = self::customers_manager_pn_get_field_value($customers_manager_pn_input['id'], $customers_manager_pn_type, $customers_manager_pn_id, $customers_manager_pn_meta_array, $customers_manager_pn_array_index, $customers_manager_pn_input);

    // Start the field value display
    ?>
      <div class="customers-manager-pn-field-value">
        <?php
        switch ($customers_manager_pn_input['input']) {
          case 'input':
            switch ($customers_manager_pn_input['type']) {
              case 'hidden':
                break;
              case 'nonce':
                break;
              case 'file':
                if (!empty($current_value)) {
                  $file_url = wp_get_attachment_url($current_value);
                  ?>
                    <div class="customers-manager-pn-file-display">
                      <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="customers-manager-pn-file-link">
                        <?php echo esc_html(basename($file_url)); ?>
                      </a>
                    </div>
                  <?php
                } else {
                  echo '<span class="customers-manager-pn-no-file">' . esc_html__('No file uploaded', 'customers-manager-pn') . '</span>';
                }
                break;

              case 'checkbox':
                ?>
                  <div class="customers-manager-pn-checkbox-display">
                    <span class="customers-manager-pn-checkbox-status <?php echo $current_value === 'on' ? 'checked' : 'unchecked'; ?>">
                      <?php echo $current_value === 'on' ? esc_html__('Yes', 'customers-manager-pn') : esc_html__('No', 'customers-manager-pn'); ?>
                    </span>
                  </div>
                <?php
                break;

              case 'radio':
                if (!empty($customers_manager_pn_input['radio_options'])) {
                  foreach ($customers_manager_pn_input['radio_options'] as $option) {
                    if ($current_value === $option['value']) {
                      ?>
                        <span class="customers-manager-pn-radio-selected"><?php echo esc_html($option['label']); ?></span>
                      <?php
                    }
                  }
                }
                break;

              case 'color':
                ?>
                  <div class="customers-manager-pn-color-display">
                    <span class="customers-manager-pn-color-preview" style="background-color: <?php echo esc_attr($current_value); ?>"></span>
                    <span class="customers-manager-pn-color-value"><?php echo esc_html($current_value); ?></span>
                  </div>
                <?php
                break;

              default:
                ?>
                  <span class="customers-manager-pn-text-value"><?php echo esc_html($current_value); ?></span>
                <?php
                break;
            }
            break;

          case 'select':
            if (!empty($customers_manager_pn_input['options']) && is_array($customers_manager_pn_input['options'])) {
              if (array_key_exists('multiple', $customers_manager_pn_input) && $customers_manager_pn_input['multiple']) {
                // Handle multiple select
                $selected_values = is_array($current_value) ? $current_value : array();
                if (!empty($selected_values)) {
                  ?>
                  <div class="customers-manager-pn-select-values customers-manager-pn-select-values-column">
                    <?php foreach ($selected_values as $value): ?>
                      <?php if (isset($customers_manager_pn_input['options'][$value])): ?>
                        <div class="customers-manager-pn-select-value-item"><?php echo esc_html($customers_manager_pn_input['options'][$value]); ?></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                  <?php
                }
              } else {
                // Handle single select
                $current_value = is_scalar($current_value) ? (string)$current_value : '';
                if (isset($customers_manager_pn_input['options'][$current_value])) {
                  ?>
                  <span class="customers-manager-pn-select-value"><?php echo esc_html($customers_manager_pn_input['options'][$current_value]); ?></span>
                  <?php
                }
              }
            }
            break;

          case 'textarea':
            ?>
              <div class="customers-manager-pn-textarea-value"><?php echo wp_kses_post(nl2br($current_value)); ?></div>
            <?php
            break;
          case 'image':
            if (!empty($current_value)) {
              $image_ids = is_array($current_value) ? $current_value : explode(',', $current_value);
              ?>
                <div class="customers-manager-pn-image-gallery">
                  <?php foreach ($image_ids as $image_id): ?>
                    <div class="customers-manager-pn-image-item">
                      <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php
            } else {
              ?>
                <span class="customers-manager-pn-no-image"><?php esc_html_e('No images uploaded', 'customers-manager-pn'); ?></span>
              <?php
            }
            break;
          case 'editor':
            ?>
              <div class="customers-manager-pn-editor-content"><?php echo wp_kses_post($current_value); ?></div>
            <?php
            break;
          case 'html':
            if (!empty($customers_manager_pn_input['html_content'])) {
              ?>
                <div class="customers-manager-pn-html-content"><?php echo wp_kses_post(do_shortcode($customers_manager_pn_input['html_content'])); ?></div>
              <?php
            }
            break;
          case 'html_multi':
            switch ($customers_manager_pn_type) {
              case 'user':
                $html_multi_fields_length = !empty(get_user_meta($customers_manager_pn_id, $customers_manager_pn_input['html_multi_fields'][0]['id'], true)) ? count(get_user_meta($customers_manager_pn_id, $customers_manager_pn_input['html_multi_fields'][0]['id'], true)) : 0;
                break;
              case 'post':
                $html_multi_fields_length = !empty(get_post_meta($customers_manager_pn_id, $customers_manager_pn_input['html_multi_fields'][0]['id'], true)) ? count(get_post_meta($customers_manager_pn_id, $customers_manager_pn_input['html_multi_fields'][0]['id'], true)) : 0;
                break;
              case 'option':
                $html_multi_fields_length = !empty(get_option($customers_manager_pn_input['html_multi_fields'][0]['id'])) ? count(get_option($customers_manager_pn_input['html_multi_fields'][0]['id'])) : 0;
            }

            ?>
              <div class="customers-manager-pn-html-multi-content">
                <?php if ($html_multi_fields_length): ?>
                  <?php foreach (range(0, ($html_multi_fields_length - 1)) as $length_index): ?>
                    <div class="customers-manager-pn-html-multi-group customers-manager-pn-display-table customers-manager-pn-width-100-percent customers-manager-pn-mb-30">
                      <?php foreach ($customers_manager_pn_input['html_multi_fields'] as $index => $html_multi_field): ?>
                          <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-60-percent">
                            <label><?php echo esc_html($html_multi_field['label']); ?></label>
                          </div>

                          <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-40-percent">
                            <?php self::customers_manager_pn_input_display($html_multi_field, $customers_manager_pn_type, $customers_manager_pn_id, 1, $length_index); ?>
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

  public static function customers_manager_pn_sanitizer($value, $node = '', $type = '', $field_config = []) {
    // Use the new validation system
    $result = CUSTOMERS_MANAGER_PN_Validation::customers_manager_pn_validate_and_sanitize($value, $node, $type, $field_config);
    
    // If validation failed, return empty value and log the error
    if (is_wp_error($result)) {
        return '';
    }
    
    return $result;
  }
}