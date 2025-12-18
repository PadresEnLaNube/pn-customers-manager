<?php
/**
 * Fired from activate() function.
 *
 * This class defines all post types necessary to run during the plugin's life cycle.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Forms {
  /**
   * Plaform forms.
   *
   * @since    1.0.0
   */

  /**
   * Get the current value of a field based on its type and storage
   * 
   * @param string $field_id The field ID
   * @param string $crmpn_type The type of field (user, post, option)
   * @param int $crmpn_id The ID of the user/post/option
   * @param int $crmpn_meta_array Whether the field is part of a meta array
   * @param int $crmpn_array_index The index in the meta array
   * @param array $crmpn_input The input array containing field configuration
   * @return mixed The current value of the field
   */
  private static function crmpn_get_field_value($field_id, $crmpn_type, $crmpn_id = 0, $crmpn_meta_array = 0, $crmpn_array_index = 0, $crmpn_input = []) {
    $current_value = '';

    if ($crmpn_meta_array) {
      switch ($crmpn_type) {
        case 'user':
          $meta = get_user_meta($crmpn_id, $field_id, true);
          if (is_array($meta) && isset($meta[$crmpn_array_index])) {
            $current_value = $meta[$crmpn_array_index];
          }
          break;
        case 'post':
          $meta = get_post_meta($crmpn_id, $field_id, true);
          if (is_array($meta) && isset($meta[$crmpn_array_index])) {
            $current_value = $meta[$crmpn_array_index];
          }
          break;
        case 'option':
          $option = get_option($field_id);
          if (is_array($option) && isset($option[$crmpn_array_index])) {
            $current_value = $option[$crmpn_array_index];
          }
          break;
      }
    } else {
      switch ($crmpn_type) {
        case 'user':
          $current_value = get_user_meta($crmpn_id, $field_id, true);
          break;
        case 'post':
          $current_value = get_post_meta($crmpn_id, $field_id, true);
          break;
        case 'option':
          $current_value = get_option($field_id);
          break;
      }
    }

    // If no value is found and there's a default value in the input config, use it
    // BUT NOT for checkboxes in multiple fields, as empty string and 'off' are valid states (unchecked)
    if (empty($current_value) && !empty($crmpn_input['value'])) {
      // For checkboxes in multiple fields, don't override empty values or 'off' with default
      if (!($crmpn_meta_array && isset($crmpn_input['type']) && $crmpn_input['type'] === 'checkbox')) {
        $current_value = $crmpn_input['value'];
      }
    }
    
    // For checkboxes in multiple fields, normalize 'off' to empty string for display
    if ($crmpn_meta_array && isset($crmpn_input['type']) && $crmpn_input['type'] === 'checkbox' && $current_value === 'off') {
      $current_value = '';
    }

    return $current_value;
  }

  public static function crmpn_input_builder($crmpn_input, $crmpn_type, $crmpn_id = 0, $disabled = 0, $crmpn_meta_array = 0, $crmpn_array_index = 0) {
    // Get the current value using the new function
    $crmpn_value = self::crmpn_get_field_value($crmpn_input['id'], $crmpn_type, $crmpn_id, $crmpn_meta_array, $crmpn_array_index, $crmpn_input);

    $crmpn_parent_block = (!empty($crmpn_input['parent']) ? 'data-crmpn-parent="' . $crmpn_input['parent'] . '"' : '') . ' ' . (!empty($crmpn_input['parent_option']) ? 'data-crmpn-parent-option="' . $crmpn_input['parent_option'] . '"' : '');

    switch ($crmpn_input['input']) {
      case 'input':        
        switch ($crmpn_input['type']) {
          case 'file':
            ?>
              <?php if (empty($crmpn_value)): ?>
                <p class="crmpn-m-10"><?php esc_html_e('No file found', 'crmpn'); ?></p>
              <?php else: ?>
                <p class="crmpn-m-10">
                  <a href="<?php echo esc_url(get_post_meta($crmpn_id, $crmpn_input['id'], true)['url']); ?>" target="_blank"><?php echo esc_html(basename(get_post_meta($crmpn_id, $crmpn_input['id'], true)['url'])); ?></a>
                </p>
              <?php endif ?>
            <?php
            break;
          case 'checkbox':
            ?>
              <label class="crmpn-switch">
                <input id="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" class="<?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?> crmpn-checkbox crmpn-checkbox-switch crmpn-field" type="<?php echo esc_attr($crmpn_input['type']); ?>" <?php echo $crmpn_value == 'on' ? 'checked="checked"' : ''; ?> <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?> <?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] ? 'multiple' : ''); ?> <?php echo wp_kses_post($crmpn_parent_block); ?>>
                <span class="crmpn-slider crmpn-round"></span>
              </label>
            <?php
            break;
          case 'radio':
            ?>
              <div class="crmpn-input-radio-wrapper">
                <?php if (!empty($crmpn_input['radio_options'])): ?>
                  <?php foreach ($crmpn_input['radio_options'] as $radio_option): ?>
                    <div class="crmpn-input-radio-item">
                      <label for="<?php echo esc_attr($radio_option['id']); ?>">
                        <?php echo wp_kses_post(wp_specialchars_decode($radio_option['label'])); ?>
                        
                        <input type="<?php echo esc_attr($crmpn_input['type']); ?>"
                          id="<?php echo esc_attr($radio_option['id']); ?>"
                          name="<?php echo esc_attr($crmpn_input['id']); ?>"
                          value="<?php echo esc_attr($radio_option['value']); ?>"
                          <?php echo $crmpn_value == $radio_option['value'] ? 'checked="checked"' : ''; ?>
                          <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == 'true') ? 'required' : ''); ?>>

                        <div class="crmpn-radio-control"></div>
                      </label>
                    </div>
                  <?php endforeach ?>
                <?php endif ?>
              </div>
            <?php
            break;
          case 'range':
            ?>
              <div class="crmpn-input-range-wrapper">
                <div class="crmpn-width-100-percent">
                  <?php if (!empty($crmpn_input['crmpn_label_min'])): ?>
                    <p class="crmpn-input-range-label-min"><?php echo esc_html($crmpn_input['crmpn_label_min']); ?></p>
                  <?php endif ?>

                  <?php if (!empty($crmpn_input['crmpn_label_max'])): ?>
                    <p class="crmpn-input-range-label-max"><?php echo esc_html($crmpn_input['crmpn_label_max']); ?></p>
                  <?php endif ?>
                </div>

                <input type="<?php echo esc_attr($crmpn_input['type']); ?>" id="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" class="crmpn-input-range <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>" <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo (isset($crmpn_input['crmpn_max']) ? 'max=' . esc_attr($crmpn_input['crmpn_max']) : ''); ?> <?php echo (isset($crmpn_input['crmpn_min']) ? 'min=' . esc_attr($crmpn_input['crmpn_min']) : ''); ?> <?php echo (((array_key_exists('step', $crmpn_input) && $crmpn_input['step'] != '')) ? 'step="' . esc_attr($crmpn_input['step']) . '"' : ''); ?> <?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] ? 'multiple' : ''); ?> value="<?php echo (!empty($crmpn_input['button_text']) ? esc_html($crmpn_input['button_text']) : esc_html($crmpn_value)); ?>"/>
                <h3 class="crmpn-input-range-output"></h3>
              </div>
            <?php
            break;
          case 'stars':
            $crmpn_stars = !empty($crmpn_input['stars_number']) ? $crmpn_input['stars_number'] : 5;
            ?>
              <div class="crmpn-input-stars-wrapper">
                <div class="crmpn-width-100-percent">
                  <?php if (!empty($crmpn_input['crmpn_label_min'])): ?>
                    <p class="crmpn-input-stars-label-min"><?php echo esc_html($crmpn_input['crmpn_label_min']); ?></p>
                  <?php endif ?>

                  <?php if (!empty($crmpn_input['crmpn_label_max'])): ?>
                    <p class="crmpn-input-stars-label-max"><?php echo esc_html($crmpn_input['crmpn_label_max']); ?></p>
                  <?php endif ?>
                </div>

                <div class="crmpn-input-stars crmpn-text-align-center crmpn-pt-20">
                  <?php foreach (range(1, $crmpn_stars) as $index => $star): ?>
                    <i class="material-icons-outlined crmpn-input-star">
                      <?php echo ($index < intval($crmpn_value)) ? 'star' : 'star_outlined'; ?>
                    </i>
                  <?php endforeach ?>
                </div>

                <input type="number" <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?> <?php echo ((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') ? 'disabled' : ''); ?> id="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" class="crmpn-input-hidden-stars <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>" min="1" max="<?php echo esc_attr($crmpn_stars) ?>" value="<?php echo esc_attr($crmpn_value); ?>">
              </div>
            <?php
            break;
          case 'submit':
            $post_id_value = '';
            if (!empty($crmpn_input['post_id'])) {
              $post_id_value = esc_attr($crmpn_input['post_id']);
            } elseif (!empty($crmpn_id) && $crmpn_type === 'post') {
              $post_id_value = esc_attr($crmpn_id);
            } elseif (!empty(get_the_ID())) {
              $post_id_value = esc_attr(get_the_ID());
            }
            $post_type_value = !empty($crmpn_input['post_type']) ? esc_attr($crmpn_input['post_type']) : '';
            ?>
              <div class="crmpn-text-align-right">
                <input type="submit" value="<?php echo esc_attr($crmpn_input['value']); ?>" name="<?php echo esc_attr($crmpn_input['id']); ?>" id="<?php echo esc_attr($crmpn_input['id']); ?>" class="crmpn-btn" data-crmpn-type="<?php echo esc_attr($crmpn_type); ?>" data-crmpn-subtype="<?php echo ((array_key_exists('subtype', $crmpn_input)) ? esc_attr($crmpn_input['subtype']) : ''); ?>" data-crmpn-user-id="<?php echo esc_attr($crmpn_id); ?>" data-crmpn-post-id="<?php echo $post_id_value; ?>" <?php echo !empty($post_type_value) ? 'data-crmpn-post-type="' . $post_type_value . '"' : ''; ?>/><?php esc_html(CRMPN_Data::crmpn_loader()); ?>
              </div>
            <?php
            break;
          case 'hidden':
            ?>
              <input type="hidden" id="<?php echo esc_attr($crmpn_input['id']); ?>" name="<?php echo esc_attr($crmpn_input['id']); ?>" value="<?php echo esc_attr($crmpn_value); ?>" <?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] == 'true' ? 'multiple' : ''); ?>>
            <?php
            break;
          case 'nonce':
            ?>
              <input type="hidden" id="<?php echo esc_attr($crmpn_input['id']); ?>" name="<?php echo esc_attr($crmpn_input['id']); ?>" value="<?php echo esc_attr(wp_create_nonce('crmpn-nonce')); ?>">
            <?php
            break;
          case 'password':
            ?>
              <div class="crmpn-password-checker">
                <div class="crmpn-password-input crmpn-position-relative">
                  <input id="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] == 'true') ? '[]' : ''); ?>" name="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] == 'true') ? '[]' : ''); ?>" <?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] == 'true' ? 'multiple' : ''); ?> class="crmpn-field crmpn-password-strength <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>" type="<?php echo esc_attr($crmpn_input['type']); ?>" <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == 'true') ? 'required' : ''); ?> <?php echo ((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') ? 'disabled' : ''); ?> value="<?php echo (!empty($crmpn_input['button_text']) ? esc_html($crmpn_input['button_text']) : esc_attr($crmpn_value)); ?>" placeholder="<?php echo (array_key_exists('placeholder', $crmpn_input) ? esc_attr($crmpn_input['placeholder']) : ''); ?>" <?php echo wp_kses_post($crmpn_parent_block); ?>/>

                  <a href="#" class="crmpn-show-pass crmpn-cursor-pointer crmpn-display-none-soft">
                    <i class="material-icons-outlined crmpn-font-size-20">visibility</i>
                  </a>
                </div>

                <div id="crmpn-popover-pass" class="crmpn-display-none-soft">
                  <div class="crmpn-progress-bar-wrapper">
                    <div class="crmpn-password-strength-bar"></div>
                  </div>

                  <h3 class="crmpn-mt-20"><?php esc_html_e('Password strength checker', 'crmpn'); ?> <i class="material-icons-outlined crmpn-cursor-pointer crmpn-close-icon crmpn-mt-30">close</i></h3>
                  <ul class="crmpn-list-style-none">
                    <li class="low-upper-case">
                      <i class="material-icons-outlined crmpn-font-size-20 crmpn-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Lowercase & Uppercase', 'crmpn'); ?></span>
                    </li>
                    <li class="one-number">
                      <i class="material-icons-outlined crmpn-font-size-20 crmpn-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Number (0-9)', 'crmpn'); ?></span>
                    </li>
                    <li class="one-special-char">
                      <i class="material-icons-outlined crmpn-font-size-20 crmpn-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Special Character (!@#$%^&*)', 'crmpn'); ?></span>
                    </li>
                    <li class="eight-character">
                      <i class="material-icons-outlined crmpn-font-size-20 crmpn-vertical-align-middle">radio_button_unchecked</i>
                      <span><?php esc_html_e('Atleast 8 Character', 'crmpn'); ?></span>
                    </li>
                  </ul>
                </div>
              </div>
            <?php
            break;
          case 'color':
            ?>
              <input id="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" <?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] ? 'multiple' : ''); ?> class="crmpn-field <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>" type="<?php echo esc_attr($crmpn_input['type']); ?>" <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> value="<?php echo (!empty($crmpn_value) ? esc_attr($crmpn_value) : '#000000'); ?>" placeholder="<?php echo (array_key_exists('placeholder', $crmpn_input) ? esc_attr($crmpn_input['placeholder']) : ''); ?>" <?php echo wp_kses_post($crmpn_parent_block); ?>/>
            <?php
            break;
          default:
            ?>
              <input 
                <?php /* ID and name attributes */ ?>
                id="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" 
                name="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>"
                
                <?php /* Type and styling */ ?>
                class="crmpn-field <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>" 
                type="<?php echo esc_attr($crmpn_input['type']); ?>"
                
                <?php /* State attributes */ ?>
                <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?>
                <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
                <?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] ? 'multiple' : ''); ?>
                
                <?php /* Validation and limits */ ?>
                <?php echo (((array_key_exists('step', $crmpn_input) && $crmpn_input['step'] != '')) ? 'step="' . esc_attr($crmpn_input['step']) . '"' : ''); ?>
                <?php echo (isset($crmpn_input['max']) ? 'max="' . esc_attr($crmpn_input['max']) . '"' : ''); ?>
                <?php echo (isset($crmpn_input['min']) ? 'min="' . esc_attr($crmpn_input['min']) . '"' : ''); ?>
                <?php echo (isset($crmpn_input['maxlength']) ? 'maxlength="' . esc_attr($crmpn_input['maxlength']) . '"' : ''); ?>
                <?php echo (isset($crmpn_input['pattern']) ? 'pattern="' . esc_attr($crmpn_input['pattern']) . '"' : ''); ?>
                
                <?php /* Content attributes */ ?>
                value="<?php echo (!empty($crmpn_input['button_text']) ? esc_html($crmpn_input['button_text']) : esc_html($crmpn_value)); ?>"
                placeholder="<?php echo (array_key_exists('placeholder', $crmpn_input) ? esc_html($crmpn_input['placeholder']) : ''); ?>"
                
                <?php /* Custom data attributes */ ?>
                <?php echo wp_kses_post($crmpn_parent_block); ?>
              />
            <?php
            break;
        }
        break;
      case 'select':
        if (!empty($crmpn_input['options']) && is_array($crmpn_input['options'])) {
          ?>
          <select 
            id="<?php echo esc_attr($crmpn_input['id']); ?>" 
            name="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" 
            class="crmpn-field <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>"
            <?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? 'multiple' : ''; ?>
            <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?>
            <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
            <?php echo wp_kses_post($crmpn_parent_block); ?>
          >
            <?php if (array_key_exists('placeholder', $crmpn_input) && !empty($crmpn_input['placeholder'])): ?>
              <option value=""><?php echo esc_html($crmpn_input['placeholder']); ?></option>
            <?php endif; ?>
            
            <?php 
            $selected_values = array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] ? 
              (is_array($crmpn_value) ? $crmpn_value : array()) : 
              array($crmpn_value);
            
            foreach ($crmpn_input['options'] as $value => $label): 
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
          <textarea id="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" name="<?php echo esc_attr($crmpn_input['id']) . ((array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? '[]' : ''); ?>" <?php echo wp_kses_post($crmpn_parent_block); ?> class="crmpn-field <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>" <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?> <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> <?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple'] ? 'multiple' : ''); ?> placeholder="<?php echo (array_key_exists('placeholder', $crmpn_input) ? esc_attr($crmpn_input['placeholder']) : ''); ?>"><?php echo esc_html($crmpn_value); ?></textarea>
        <?php
        break;
      case 'image':
        ?>
          <div class="crmpn-field crmpn-images-block" <?php echo wp_kses_post($crmpn_parent_block); ?> data-crmpn-multiple="<?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? 'true' : 'false'; ?>">
            <?php if (!empty($crmpn_value)): ?>
              <div class="crmpn-images">
                <?php foreach (explode(',', $crmpn_value) as $crmpn_image): ?>
                  <?php echo wp_get_attachment_image($crmpn_image, 'medium'); ?>
                <?php endforeach ?>
              </div>

              <div class="crmpn-text-align-center crmpn-position-relative"><a href="#" class="crmpn-btn crmpn-btn-mini crmpn-image-btn"><?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? esc_html(__('Edit images', 'crmpn')) : esc_html(__('Edit image', 'crmpn')); ?></a></div>
            <?php else: ?>
              <div class="crmpn-images"></div>

              <div class="crmpn-text-align-center crmpn-position-relative"><a href="#" class="crmpn-btn crmpn-btn-mini crmpn-image-btn"><?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? esc_html(__('Add images', 'crmpn')) : esc_html(__('Add image', 'crmpn')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($crmpn_input['id']); ?>" name="<?php echo esc_attr($crmpn_input['id']); ?>" class="crmpn-display-none crmpn-image-input" type="text" value="<?php echo esc_attr($crmpn_value); ?>"/>
          </div>
        <?php
        break;
      case 'video':
        ?>
        <div class="crmpn-field crmpn-videos-block" <?php echo wp_kses_post($crmpn_parent_block); ?>>
            <?php if (!empty($crmpn_value)): ?>
              <div class="crmpn-videos">
                <?php foreach (explode(',', $crmpn_value) as $crmpn_video): ?>
                  <div class="crmpn-video crmpn-tooltip" title="<?php echo esc_html(get_the_title($crmpn_video)); ?>"><i class="dashicons dashicons-media-video"></i></div>
                <?php endforeach ?>
              </div>

              <div class="crmpn-text-align-center crmpn-position-relative"><a href="#" class="crmpn-btn crmpn-video-btn"><?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? esc_html(__('Edit videos', 'crmpn')) : esc_html(__('Edit video', 'crmpn')); ?></a></div>
            <?php else: ?>
              <div class="crmpn-videos"></div>

              <div class="crmpn-text-align-center crmpn-position-relative"><a href="#" class="crmpn-btn crmpn-video-btn"><?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? esc_html(__('Add videos', 'crmpn')) : esc_html(__('Add video', 'crmpn')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($crmpn_input['id']); ?>" name="<?php echo esc_attr($crmpn_input['id']); ?>" class="crmpn-display-none crmpn-video-input" type="text" value="<?php echo esc_attr($crmpn_value); ?>"/>
          </div>
        <?php
        break;
      case 'audio':
        ?>
          <div class="crmpn-field crmpn-audios-block" <?php echo wp_kses_post($crmpn_parent_block); ?>>
            <?php if (!empty($crmpn_value)): ?>
              <div class="crmpn-audios">
                <?php foreach (explode(',', $crmpn_value) as $crmpn_audio): ?>
                  <div class="crmpn-audio crmpn-tooltip" title="<?php echo esc_html(get_the_title($crmpn_audio)); ?>"><i class="dashicons dashicons-media-audio"></i></div>
                <?php endforeach ?>
              </div>

              <div class="crmpn-text-align-center crmpn-position-relative"><a href="#" class="crmpn-btn crmpn-btn-mini crmpn-audio-btn"><?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? esc_html(__('Edit audios', 'crmpn')) : esc_html(__('Edit audio', 'crmpn')); ?></a></div>
            <?php else: ?>
              <div class="crmpn-audios"></div>

              <div class="crmpn-text-align-center crmpn-position-relative"><a href="#" class="crmpn-btn crmpn-btn-mini crmpn-audio-btn"><?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? esc_html(__('Add audios', 'crmpn')) : esc_html(__('Add audio', 'crmpn')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($crmpn_input['id']); ?>" name="<?php echo esc_attr($crmpn_input['id']); ?>" class="crmpn-display-none crmpn-audio-input" type="text" value="<?php echo esc_attr($crmpn_value); ?>"/>
          </div>
        <?php
        break;
      case 'file':
        ?>
          <div class="crmpn-field crmpn-files-block" <?php echo wp_kses_post($crmpn_parent_block); ?>>
            <?php if (!empty($crmpn_value)): ?>
              <div class="crmpn-files crmpn-text-align-center">
                <?php foreach (explode(',', $crmpn_value) as $crmpn_file): ?>
                  <embed src="<?php echo esc_url(wp_get_attachment_url($crmpn_file)); ?>" type="application/pdf" class="crmpn-embed-file"/>
                <?php endforeach ?>
              </div>

              <div class="crmpn-text-align-center crmpn-position-relative"><a href="#" class="crmpn-btn crmpn-btn-mini crmpn-file-btn"><?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? esc_html(__('Edit files', 'crmpn')) : esc_html(__('Edit file', 'crmpn')); ?></a></div>
            <?php else: ?>
              <div class="crmpn-files"></div>

              <div class="crmpn-text-align-center crmpn-position-relative"><a href="#" class="crmpn-btn crmpn-btn-mini crmpn-btn-mini crmpn-file-btn"><?php echo (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) ? esc_html(__('Add files', 'crmpn')) : esc_html(__('Add file', 'crmpn')); ?></a></div>
            <?php endif ?>

            <input id="<?php echo esc_attr($crmpn_input['id']); ?>" name="<?php echo esc_attr($crmpn_input['id']); ?>" class="crmpn-display-none crmpn-file-input crmpn-btn-mini" type="text" value="<?php echo esc_attr($crmpn_value); ?>"/>
          </div>
        <?php
        break;
      case 'editor':
        ?>
          <div class="crmpn-field" <?php echo wp_kses_post($crmpn_parent_block); ?>>
            <textarea id="<?php echo esc_attr($crmpn_input['id']); ?>" name="<?php echo esc_attr($crmpn_input['id']); ?>" class="crmpn-input crmpn-width-100-percent crmpn-wysiwyg"><?php echo ((empty($crmpn_value)) ? (array_key_exists('placeholder', $crmpn_input) ? esc_attr($crmpn_input['placeholder']) : '') : esc_html($crmpn_value)); ?></textarea>
          </div>
        <?php
        break;
      case 'html':
        ?>
          <div class="crmpn-field" <?php echo wp_kses_post($crmpn_parent_block); ?>>
            <?php echo !empty($crmpn_input['html_content']) ? wp_kses(do_shortcode($crmpn_input['html_content']), CRMPN_KSES) : ''; ?>
          </div>
        <?php
        break;
      case 'html_multi':
        switch ($crmpn_type) {
          case 'user':
            $html_multi_fields_length = !empty(get_user_meta($crmpn_id, $crmpn_input['html_multi_fields'][0]['id'], true)) ? count(get_user_meta($crmpn_id, $crmpn_input['html_multi_fields'][0]['id'], true)) : 0;
            break;
          case 'post':
            $html_multi_fields_length = !empty(get_post_meta($crmpn_id, $crmpn_input['html_multi_fields'][0]['id'], true)) ? count(get_post_meta($crmpn_id, $crmpn_input['html_multi_fields'][0]['id'], true)) : 0;
            break;
          case 'option':
            $html_multi_fields_length = !empty(get_option($crmpn_input['html_multi_fields'][0]['id'])) ? count(get_option($crmpn_input['html_multi_fields'][0]['id'])) : 0;
        }

        ?>
          <div class="crmpn-field crmpn-html-multi-wrapper crmpn-mb-50" <?php echo wp_kses_post($crmpn_parent_block); ?>>
            <?php if ($html_multi_fields_length): ?>
              <?php foreach (range(0, ($html_multi_fields_length - 1)) as $length_index): ?>
                <div class="crmpn-html-multi-group crmpn-display-table crmpn-width-100-percent crmpn-mb-30">
                  <div class="crmpn-display-inline-table crmpn-width-90-percent">
                    <?php foreach ($crmpn_input['html_multi_fields'] as $index => $html_multi_field): ?>
                      <?php if (isset($html_multi_field['label']) && !empty($html_multi_field['label'])): ?>
                        <label><?php echo esc_html($html_multi_field['label']); ?></label>
                      <?php endif; ?>

                      <?php self::crmpn_input_builder($html_multi_field, $crmpn_type, $crmpn_id, false, true, $length_index); ?>
                    <?php endforeach ?>
                  </div>
                  <div class="crmpn-display-inline-table crmpn-width-10-percent crmpn-text-align-center">
                    <i class="material-icons-outlined crmpn-cursor-move crmpn-multi-sorting crmpn-vertical-align-super crmpn-tooltip" title="<?php esc_html_e('Order element', 'crmpn'); ?>">drag_handle</i>
                  </div>

                  <div class="crmpn-text-align-right">
                    <a href="#" class="crmpn-html-multi-remove-btn"><i class="material-icons-outlined crmpn-cursor-pointer crmpn-tooltip" title="<?php esc_html_e('Remove element', 'crmpn'); ?>">remove</i></a>
                  </div>
                </div>
              <?php endforeach ?>
            <?php else: ?>
              <div class="crmpn-html-multi-group crmpn-mb-50">
                <div class="crmpn-display-inline-table crmpn-width-90-percent">
                  <?php foreach ($crmpn_input['html_multi_fields'] as $html_multi_field): ?>
                    <?php self::crmpn_input_builder($html_multi_field, $crmpn_type); ?>
                  <?php endforeach ?>
                </div>
                <div class="crmpn-display-inline-table crmpn-width-10-percent crmpn-text-align-center">
                  <i class="material-icons-outlined crmpn-cursor-move crmpn-multi-sorting crmpn-vertical-align-super crmpn-tooltip" title="<?php esc_html_e('Order element', 'crmpn'); ?>">drag_handle</i>
                </div>

                <div class="crmpn-text-align-right">
                  <a href="#" class="crmpn-html-multi-remove-btn crmpn-tooltip" title="<?php esc_html_e('Remove element', 'crmpn'); ?>"><i class="material-icons-outlined crmpn-cursor-pointer">remove</i></a>
                </div>
              </div>
            <?php endif ?>

            <div class="crmpn-text-align-right">
              <a href="#" class="crmpn-html-multi-add-btn crmpn-tooltip" title="<?php esc_html_e('Add element', 'crmpn'); ?>"><i class="material-icons-outlined crmpn-cursor-pointer crmpn-font-size-40">add</i></a>
            </div>
          </div>
        <?php
        break;
      case 'audio_recorder':
        // Enqueue CSS and JS files for audio recorder
        wp_enqueue_style('crmpn-audio-recorder', CRMPN_URL . 'assets/css/crmpn-audio-recorder.css', array(), '1.0.0');
        wp_enqueue_script('crmpn-audio-recorder', CRMPN_URL . 'assets/js/crmpn-audio-recorder.js', array('jquery'), '1.0.0', true);
        
        // Localize script with AJAX data
        wp_localize_script('crmpn-audio-recorder', 'crmpn_audio_recorder_vars', array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('crmpn_audio_nonce'),
        ));
        
        ?>
          <div class="crmpn-audio-recorder-status crmpn-display-none-soft">
            <p class="crmpn-recording-status"><?php esc_html_e('Ready to record', 'crmpn'); ?></p>
          </div>
          
          <div class="crmpn-audio-recorder-wrapper">
            <div class="crmpn-audio-recorder-controls">
              <div class="crmpn-display-table crmpn-width-100-percent">
                <div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-center crmpn-mb-20">
                  <button type="button" class="crmpn-btn crmpn-btn-primary crmpn-start-recording" <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined crmpn-vertical-align-middle">mic</i>
                    <?php esc_html_e('Start recording', 'crmpn'); ?>
                  </button>
                </div>

                <div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-center crmpn-mb-20">
                  <button type="button" class="crmpn-btn crmpn-btn-secondary crmpn-stop-recording" style="display: none;" <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined crmpn-vertical-align-middle">stop</i>
                    <?php esc_html_e('Stop recording', 'crmpn'); ?>
                  </button>
                </div>
              </div>

              <div class="crmpn-display-table crmpn-width-100-percent">
                <div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-center crmpn-mb-20">
                  <button type="button" class="crmpn-btn crmpn-btn-secondary crmpn-play-audio" style="display: none;" <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined crmpn-vertical-align-middle">play_arrow</i>
                    <?php esc_html_e('Play audio', 'crmpn'); ?>
                  </button>
                </div>

                <div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-center crmpn-mb-20">
                  <button type="button" class="crmpn-btn crmpn-btn-secondary crmpn-stop-audio" style="display: none;" <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                    <i class="material-icons-outlined crmpn-vertical-align-middle">stop</i>
                    <?php esc_html_e('Stop audio', 'crmpn'); ?>
                  </button>
                </div>
              </div>
            </div>

            <div class="crmpn-audio-recorder-visualizer" style="display: none;">
              <canvas class="crmpn-audio-canvas" width="300" height="60"></canvas>
            </div>

            <div class="crmpn-audio-recorder-timer" style="display: none;">
              <span class="crmpn-recording-time">00:00</span>
            </div>

            <div class="crmpn-audio-transcription-controls crmpn-display-none-soft crmpn-display-table crmpn-width-100-percent crmpn-mb-20">
              <div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-center">
                <button type="button" class="crmpn-btn crmpn-btn-primary crmpn-transcribe-audio" <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                  <i class="material-icons-outlined crmpn-vertical-align-middle">translate</i>
                  <?php esc_html_e('Transcribe Audio', 'crmpn'); ?>
                </button>
              </div>

              <div class="crmpn-display-inline-table crmpn-width-50-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-center">
                <button type="button" class="crmpn-btn crmpn-btn-secondary crmpn-clear-transcription" <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>>
                  <i class="material-icons-outlined crmpn-vertical-align-middle">clear</i>
                  <?php esc_html_e('Clear', 'crmpn'); ?>
                </button>
              </div>
            </div>

            <div class="crmpn-audio-transcription-loading">
              <?php echo esc_html(CRMPN_Data::crmpn_loader()); ?>
            </div>

            <div class="crmpn-audio-transcription-result">
              <textarea 
                id="<?php echo esc_attr($crmpn_input['id']); ?>" 
                name="<?php echo esc_attr($crmpn_input['id']); ?>" 
                class="crmpn-field crmpn-transcription-textarea <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>" 
                placeholder="<?php echo (array_key_exists('placeholder', $crmpn_input) ? esc_attr($crmpn_input['placeholder']) : esc_attr__('Transcribed text will appear here...', 'crmpn')); ?>"
                <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?>
                <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?>
                <?php echo wp_kses_post($crmpn_parent_block); ?>
                rows="4"
                style="width: 100%; margin-top: 10px;"
              ><?php echo esc_textarea($crmpn_value); ?></textarea>
            </div>

            <div class="crmpn-audio-transcription-error crmpn-display-none-soft">
              <p class="crmpn-error-message"></p>
            </div>

            <div class="crmpn-audio-transcription-success crmpn-display-none-soft">
              <p class="crmpn-success-message"></p>
            </div>

            <!-- Hidden input to store audio data -->
            <input type="hidden" 
                  id="<?php echo esc_attr($crmpn_input['id']); ?>_audio_data" 
                  name="<?php echo esc_attr($crmpn_input['id']); ?>_audio_data" 
                  value="" />
          </div>
        <?php
        break;
      case 'tags':
        // Get current tags value
        $current_tags = self::crmpn_get_crmpn_value($crmpn_type, $crmpn_id, $crmpn_input);
        $tags_array = is_array($current_tags) ? $current_tags : [];
        $tags_string = implode(', ', $tags_array);
        ?>
        <div class="crmpn-tags-wrapper" <?php echo wp_kses_post($crmpn_parent_block); ?>>
          <input type="text" 
            id="<?php echo esc_attr($crmpn_input['id']); ?>" 
            name="<?php echo esc_attr($crmpn_input['id']); ?>" 
            class="crmpn-field crmpn-tags-input <?php echo array_key_exists('class', $crmpn_input) ? esc_attr($crmpn_input['class']) : ''; ?>" 
            value="<?php echo esc_attr($tags_string); ?>" 
            placeholder="<?php echo (array_key_exists('placeholder', $crmpn_input) ? esc_attr($crmpn_input['placeholder']) : ''); ?>"
            <?php echo ((array_key_exists('required', $crmpn_input) && $crmpn_input['required'] == true) ? 'required' : ''); ?>
            <?php echo (((array_key_exists('disabled', $crmpn_input) && $crmpn_input['disabled'] == 'true') || $disabled) ? 'disabled' : ''); ?> />
          
          <div class="crmpn-tags-suggestions" style="display: none;">
            <div class="crmpn-tags-suggestions-list"></div>
          </div>
          
          <div class="crmpn-tags-display">
            <?php if (!empty($tags_array)): ?>
              <?php foreach ($tags_array as $tag): ?>
                <span class="crmpn-tag">
                  <?php echo esc_html($tag); ?>
                  <i class="material-icons-outlined crmpn-tag-remove">close</i>
                </span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <input type="hidden" 
            id="<?php echo esc_attr($crmpn_input['id']); ?>_tags_array" 
            name="<?php echo esc_attr($crmpn_input['id']); ?>_tags_array" 
            value="<?php echo esc_attr(json_encode($tags_array)); ?>" />
        </div>
        <?php
        break;
    }
  }

  public static function crmpn_input_wrapper_builder($input_array, $type, $crmpn_id = 0, $disabled = 0, $crmpn_format = 'half'){
    ?>
      <?php if (array_key_exists('section', $input_array) && !empty($input_array['section'])): ?>      
        <?php if ($input_array['section'] == 'start'): ?>
          <div class="crmpn-toggle-wrapper crmpn-section-wrapper crmpn-position-relative crmpn-mb-30 <?php echo array_key_exists('class', $input_array) ? esc_attr($input_array['class']) : ''; ?>" id="<?php echo array_key_exists('id', $input_array) ? esc_attr($input_array['id']) : ''; ?>">
            <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
              <i class="material-icons-outlined crmpn-section-helper crmpn-color-main-0 crmpn-tooltip" title="<?php echo wp_kses_post($input_array['description']); ?>">help</i>
            <?php endif ?>

            <a href="#" class="crmpn-toggle crmpn-width-100-percent crmpn-text-decoration-none">
              <div class="crmpn-display-table crmpn-width-100-percent crmpn-mb-20">
                <div class="crmpn-display-inline-table crmpn-width-90-percent">
                  <label class="crmpn-cursor-pointer crmpn-mb-20 crmpn-color-main-0"><?php echo wp_kses_post($input_array['label']); ?></label>
                </div>
                <div class="crmpn-display-inline-table crmpn-width-10-percent crmpn-text-align-right">
                  <i class="material-icons-outlined crmpn-cursor-pointer crmpn-color-main-0">add</i>
                </div>
              </div>
            </a>

            <div class="crmpn-content crmpn-pl-10 crmpn-toggle-content crmpn-mb-20 crmpn-display-none-soft">
        <?php elseif ($input_array['section'] == 'end'): ?>
            </div>
          </div>
        <?php endif ?>
      <?php else: ?>
        <div class="crmpn-input-wrapper <?php echo esc_attr($input_array['id']); ?> <?php echo !empty($input_array['tabs']) ? 'crmpn-input-tabbed' : ''; ?> crmpn-input-field-<?php echo esc_attr($input_array['input']); ?> <?php echo (!empty($input_array['required']) && $input_array['required'] == true) ? 'crmpn-input-field-required' : ''; ?> <?php echo ($disabled) ? 'crmpn-input-field-disabled' : ''; ?> crmpn-mb-30">
          <?php if (array_key_exists('label', $input_array) && !empty($input_array['label'])): ?>
            <div class="crmpn-display-inline-table <?php echo (($crmpn_format == 'half' && !(array_key_exists('type', $input_array) && $input_array['type'] == 'submit')) ? 'crmpn-width-40-percent' : 'crmpn-width-100-percent'); ?> crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-vertical-align-top">
              <div class="crmpn-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'crmpn-pl-30' : ''; ?>">
                <label class="crmpn-vertical-align-middle crmpn-display-block <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? 'crmpn-toggle' : ''; ?>" for="<?php echo esc_attr($input_array['id']); ?>"><?php echo wp_kses($input_array['label'], CRMPN_KSES); ?> <?php echo (array_key_exists('required', $input_array) && !empty($input_array['required']) && $input_array['required'] == true) ? '<span class="crmpn-tooltip" title="' . esc_html(__('Required field', 'crmpn')) . '">*</span>' : ''; ?><?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? '<i class="material-icons-outlined crmpn-cursor-pointer crmpn-float-right">add</i>' : ''; ?></label>

                <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
                  <div class="crmpn-toggle-content crmpn-display-none-soft">
                    <small><?php echo wp_kses_post(wp_specialchars_decode($input_array['description'])); ?></small>
                  </div>
                <?php endif ?>
              </div>
            </div>
          <?php endif ?>

          <div class="crmpn-display-inline-table <?php echo ((array_key_exists('label', $input_array) && empty($input_array['label'])) ? 'crmpn-width-100-percent' : (($crmpn_format == 'half' && !(array_key_exists('type', $input_array) && $input_array['type'] == 'submit')) ? 'crmpn-width-60-percent' : 'crmpn-width-100-percent')); ?> crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-vertical-align-top">
            <div class="crmpn-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'crmpn-pl-30' : ''; ?>">
              <div class="crmpn-input-field"><?php self::crmpn_input_builder($input_array, $type, $crmpn_id, $disabled); ?></div>
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
   * @param int $crmpn_id The ID of the user/post/option
   * @param int $crmpn_meta_array Whether the field is part of a meta array
   * @param int $crmpn_array_index The index in the meta array
   * @param string $crmpn_format The display format ('half' or 'full')
   * @return string Formatted HTML output
   */
  public static function crmpn_input_display_wrapper($input_array, $type, $crmpn_id = 0, $crmpn_meta_array = 0, $crmpn_array_index = 0, $crmpn_format = 'half') {
    ob_start();
    ?>
    <?php if (array_key_exists('section', $input_array) && !empty($input_array['section'])): ?>      
      <?php if ($input_array['section'] == 'start'): ?>
        <div class="crmpn-toggle-wrapper crmpn-section-wrapper crmpn-position-relative crmpn-mb-30 <?php echo array_key_exists('class', $input_array) ? esc_attr($input_array['class']) : ''; ?>" id="<?php echo array_key_exists('id', $input_array) ? esc_attr($input_array['id']) : ''; ?>">
          <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
            <i class="material-icons-outlined crmpn-section-helper crmpn-color-main-0 crmpn-tooltip" title="<?php echo wp_kses_post($input_array['description']); ?>">help</i>
          <?php endif ?>

          <a href="#" class="crmpn-toggle crmpn-width-100-percent crmpn-text-decoration-none">
            <div class="crmpn-display-table crmpn-width-100-percent crmpn-mb-20">
              <div class="crmpn-display-inline-table crmpn-width-90-percent">
                <label class="crmpn-cursor-pointer crmpn-mb-20 crmpn-color-main-0"><?php echo wp_kses($input_array['label'], CRMPN_KSES); ?></label>
              </div>
              <div class="crmpn-display-inline-table crmpn-width-10-percent crmpn-text-align-right">
                <i class="material-icons-outlined crmpn-cursor-pointer crmpn-color-main-0">add</i>
              </div>
            </div>
          </a>

          <div class="crmpn-content crmpn-pl-10 crmpn-toggle-content crmpn-mb-20 crmpn-display-none-soft">
      <?php elseif ($input_array['section'] == 'end'): ?>
          </div>
        </div>
      <?php endif ?>
    <?php else: ?>
      <div class="crmpn-input-wrapper <?php echo esc_attr($input_array['id']); ?> crmpn-input-display-<?php echo esc_attr($input_array['input']); ?> <?php echo (!empty($input_array['required']) && $input_array['required'] == true) ? 'crmpn-input-field-required' : ''; ?> crmpn-mb-30">
        <?php if (array_key_exists('label', $input_array) && !empty($input_array['label'])): ?>
          <div class="crmpn-display-inline-table <?php echo ($crmpn_format == 'half' ? 'crmpn-width-40-percent' : 'crmpn-width-100-percent'); ?> crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-vertical-align-top">
            <div class="crmpn-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'crmpn-pl-30' : ''; ?>">
              <label class="crmpn-vertical-align-middle crmpn-display-block <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? 'crmpn-toggle' : ''; ?>" for="<?php echo esc_attr($input_array['id']); ?>">
                <?php echo wp_kses($input_array['label'], CRMPN_KSES); ?>
                <?php echo (array_key_exists('required', $input_array) && !empty($input_array['required']) && $input_array['required'] == true) ? '<span class="crmpn-tooltip" title="' . esc_html(__('Required field', 'crmpn')) . '">*</span>' : ''; ?>
                <?php echo (array_key_exists('description', $input_array) && !empty($input_array['description'])) ? '<i class="material-icons-outlined crmpn-cursor-pointer crmpn-float-right">add</i>' : ''; ?>
              </label>

              <?php if (array_key_exists('description', $input_array) && !empty($input_array['description'])): ?>
                <div class="crmpn-toggle-content crmpn-display-none-soft">
                  <small><?php echo wp_kses_post(wp_specialchars_decode($input_array['description'])); ?></small>
                </div>
              <?php endif ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="crmpn-display-inline-table <?php echo ((array_key_exists('label', $input_array) && empty($input_array['label'])) ? 'crmpn-width-100-percent' : ($crmpn_format == 'half' ? 'crmpn-width-60-percent' : 'crmpn-width-100-percent')); ?> crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-vertical-align-top">
          <div class="crmpn-p-10 <?php echo (array_key_exists('parent', $input_array) && !empty($input_array['parent']) && $input_array['parent'] != 'this') ? 'crmpn-pl-30' : ''; ?>">
            <div class="crmpn-input-field">
              <?php self::crmpn_input_display($input_array, $type, $crmpn_id, $crmpn_meta_array, $crmpn_array_index); ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
  }

  /**
   * Display formatted values of crmpn_input_builder fields in frontend
   * 
   * @param array $crmpn_input The input array containing field configuration
   * @param string $crmpn_type The type of field (user, post, option)
   * @param int $crmpn_id The ID of the user/post/option
   * @param int $crmpn_meta_array Whether the field is part of a meta array
   * @param int $crmpn_array_index The index in the meta array
   * @return string Formatted HTML output of field values
   */
  public static function crmpn_input_display($crmpn_input, $crmpn_type, $crmpn_id = 0, $crmpn_meta_array = 0, $crmpn_array_index = 0) {
    // Get the current value using the new function
    $current_value = self::crmpn_get_field_value($crmpn_input['id'], $crmpn_type, $crmpn_id, $crmpn_meta_array, $crmpn_array_index, $crmpn_input);

    // Start the field value display
    ?>
      <div class="crmpn-field-value">
        <?php
        switch ($crmpn_input['input']) {
          case 'input':
            switch ($crmpn_input['type']) {
              case 'hidden':
                break;
              case 'nonce':
                break;
              case 'file':
                if (!empty($current_value)) {
                  $file_url = wp_get_attachment_url($current_value);
                  ?>
                    <div class="crmpn-file-display">
                      <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="crmpn-file-link">
                        <?php echo esc_html(basename($file_url)); ?>
                      </a>
                    </div>
                  <?php
                } else {
                  echo '<span class="crmpn-no-file">' . esc_html__('No file uploaded', 'crmpn') . '</span>';
                }
                break;

              case 'checkbox':
                ?>
                  <div class="crmpn-checkbox-display">
                    <span class="crmpn-checkbox-status <?php echo $current_value === 'on' ? 'checked' : 'unchecked'; ?>">
                      <?php echo $current_value === 'on' ? esc_html__('Yes', 'crmpn') : esc_html__('No', 'crmpn'); ?>
                    </span>
                  </div>
                <?php
                break;

              case 'radio':
                if (!empty($crmpn_input['radio_options'])) {
                  foreach ($crmpn_input['radio_options'] as $option) {
                    if ($current_value === $option['value']) {
                      ?>
                        <span class="crmpn-radio-selected"><?php echo esc_html($option['label']); ?></span>
                      <?php
                    }
                  }
                }
                break;

              case 'color':
                ?>
                  <div class="crmpn-color-display">
                    <span class="crmpn-color-preview" style="background-color: <?php echo esc_attr($current_value); ?>"></span>
                    <span class="crmpn-color-value"><?php echo esc_html($current_value); ?></span>
                  </div>
                <?php
                break;

              default:
                ?>
                  <span class="crmpn-text-value"><?php echo esc_html($current_value); ?></span>
                <?php
                break;
            }
            break;

          case 'select':
            if (!empty($crmpn_input['options']) && is_array($crmpn_input['options'])) {
              if (array_key_exists('multiple', $crmpn_input) && $crmpn_input['multiple']) {
                // Handle multiple select
                $selected_values = is_array($current_value) ? $current_value : array();
                if (!empty($selected_values)) {
                  ?>
                  <div class="crmpn-select-values crmpn-select-values-column">
                    <?php foreach ($selected_values as $value): ?>
                      <?php if (isset($crmpn_input['options'][$value])): ?>
                        <div class="crmpn-select-value-item"><?php echo esc_html($crmpn_input['options'][$value]); ?></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                  <?php
                }
              } else {
                // Handle single select
                $current_value = is_scalar($current_value) ? (string)$current_value : '';
                if (isset($crmpn_input['options'][$current_value])) {
                  ?>
                  <span class="crmpn-select-value"><?php echo esc_html($crmpn_input['options'][$current_value]); ?></span>
                  <?php
                }
              }
            }
            break;

          case 'textarea':
            ?>
              <div class="crmpn-textarea-value"><?php echo wp_kses_post(nl2br($current_value)); ?></div>
            <?php
            break;
          case 'image':
            if (!empty($current_value)) {
              $image_ids = is_array($current_value) ? $current_value : explode(',', $current_value);
              ?>
                <div class="crmpn-image-gallery">
                  <?php foreach ($image_ids as $image_id): ?>
                    <div class="crmpn-image-item">
                      <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php
            } else {
              ?>
                <span class="crmpn-no-image"><?php esc_html_e('No images uploaded', 'crmpn'); ?></span>
              <?php
            }
            break;
          case 'editor':
            ?>
              <div class="crmpn-editor-content"><?php echo wp_kses_post($current_value); ?></div>
            <?php
            break;
          case 'html':
            if (!empty($crmpn_input['html_content'])) {
              ?>
                <div class="crmpn-html-content"><?php echo wp_kses_post(do_shortcode($crmpn_input['html_content'])); ?></div>
              <?php
            }
            break;
          case 'html_multi':
            switch ($crmpn_type) {
              case 'user':
                $html_multi_fields_length = !empty(get_user_meta($crmpn_id, $crmpn_input['html_multi_fields'][0]['id'], true)) ? count(get_user_meta($crmpn_id, $crmpn_input['html_multi_fields'][0]['id'], true)) : 0;
                break;
              case 'post':
                $html_multi_fields_length = !empty(get_post_meta($crmpn_id, $crmpn_input['html_multi_fields'][0]['id'], true)) ? count(get_post_meta($crmpn_id, $crmpn_input['html_multi_fields'][0]['id'], true)) : 0;
                break;
              case 'option':
                $html_multi_fields_length = !empty(get_option($crmpn_input['html_multi_fields'][0]['id'])) ? count(get_option($crmpn_input['html_multi_fields'][0]['id'])) : 0;
            }

            ?>
              <div class="crmpn-html-multi-content">
                <?php if ($html_multi_fields_length): ?>
                  <?php foreach (range(0, ($html_multi_fields_length - 1)) as $length_index): ?>
                    <div class="crmpn-html-multi-group crmpn-display-table crmpn-width-100-percent crmpn-mb-30">
                      <?php foreach ($crmpn_input['html_multi_fields'] as $index => $html_multi_field): ?>
                          <div class="crmpn-display-inline-table crmpn-width-60-percent">
                            <label><?php echo esc_html($html_multi_field['label']); ?></label>
                          </div>

                          <div class="crmpn-display-inline-table crmpn-width-40-percent">
                            <?php self::crmpn_input_display($html_multi_field, $crmpn_type, $crmpn_id, 1, $length_index); ?>
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

  public static function crmpn_sanitizer($value, $node = '', $type = '', $field_config = []) {
    // Use the new validation system
    $result = CRMPN_Validation::crmpn_validate_and_sanitize($value, $node, $type, $field_config);
    
    // If validation failed, return empty value and log the error
    if (is_wp_error($result)) {
        return '';
    }
    
    return $result;
  }
}