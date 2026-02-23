<?php
/**
 * Validation and sanitization handler for Customers Manager PN plugin.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

class PN_CUSTOMERS_MANAGER_Validation {
    /**
     * Validate and sanitize input data
     *
     * @param mixed  $value        The value to validate and sanitize
     * @param string $node         The input node type (input, select, textarea, etc.)
     * @param string $type         The input type (text, email, url, etc.)
     * @param array  $field_config Additional field configuration
     * @return mixed|WP_Error Sanitized value or WP_Error on validation failure
     */
    public static function pn_customers_manager_validate_and_sanitize($value, $node = '', $type = '', $field_config = []) {
        // First validate
        $validation_result = self::pn_customers_manager_validate($value, $type, $field_config);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Then sanitize
        return self::pn_customers_manager_sanitize($value, $node, $type);
    }

    /**
     * Validate input data
     *
     * @param mixed  $value        The value to validate
     * @param string $type         The input type
     * @param array  $field_config Additional field configuration
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public static function pn_customers_manager_validate($value, $type, $field_config = []) {
        // Required field validation
        if (!empty($field_config['required']) && empty($value)) {
            return new WP_Error(
                'required_field', 
                sprintf(
                    /* translators: %s: Field label or "This field" */
                    __('%s is required.', 'pn-customers-manager'),
                    !empty($field_config['label']) ? $field_config['label'] : __('This field', 'pn-customers-manager')
                )
            );
        }

        // Skip further validation if value is empty and not required
        if (empty($value) && empty($field_config['required'])) {
            return true;
        }
        
        // Type-specific validation
        switch ($type) {
            case 'email':
                if (!is_email($value)) {
                    return new WP_Error(
                        'invalid_email', 
                        __('Please enter a valid email address.', 'pn-customers-manager')
                    );
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return new WP_Error(
                        'invalid_url', 
                        __('Please enter a valid URL.', 'pn-customers-manager')
                    );
                }
                break;
                
            case 'number':
            case 'tel':
                if (!is_numeric($value)) {
                    return new WP_Error(
                        'invalid_number', 
                        __('Please enter a valid number.', 'pn-customers-manager')
                    );
                }
                // Range validation
                if (isset($field_config['min']) && $value < $field_config['min']) {
                    return new WP_Error(
                        'number_too_small', 
                        sprintf(
                            /* translators: %s: Minimum number value */
                            __('Number must be at least %s.', 'pn-customers-manager'),
                            $field_config['min']
                        )
                    );
                }
                if (isset($field_config['max']) && $value > $field_config['max']) {
                    return new WP_Error(
                        'number_too_large', 
                        sprintf(
                            /* translators: %s: Maximum number value */
                            __('Number must be at most %s.', 'pn-customers-manager'),
                            $field_config['max']
                        )
                    );
                }
                break;
                
            case 'text':
            case 'textarea':
                // Length validation
                if (isset($field_config['minlength']) && strlen($value) < $field_config['minlength']) {
                    return new WP_Error(
                        'text_too_short', 
                        sprintf(
                            /* translators: %d: Minimum character count */
                            __('Text must be at least %d characters long.', 'pn-customers-manager'),
                            $field_config['minlength']
                        )
                    );
                }
                if (isset($field_config['maxlength']) && strlen($value) > $field_config['maxlength']) {
                    return new WP_Error(
                        'text_too_long', 
                        sprintf(
                            /* translators: %d: Maximum character count */
                            __('Text must be at most %d characters long.', 'pn-customers-manager'),
                            $field_config['maxlength']
                        )
                    );
                }
                break;

            case 'date':
                if (!self::pn_customers_manager_is_valid_date($value, $field_config['format'] ?? 'Y-m-d')) {
                    return new WP_Error(
                        'invalid_date',
                        __('Please enter a valid date.', 'pn-customers-manager')
                    );
                }
                break;

            case 'select':
                if (!empty($field_config['options']) && !in_array($value, array_keys($field_config['options']))) {
                    return new WP_Error(
                        'invalid_option',
                        __('Please select a valid option.', 'pn-customers-manager')
                    );
                }
                break;

            case 'select-multiple':
                if (!is_array($value)) {
                    return new WP_Error(
                        'invalid_multiple_select',
                        __('Invalid selection format.', 'pn-customers-manager')
                    );
                }
                if (!empty($field_config['options'])) {
                    foreach ($value as $selected) {
                        if (!in_array($selected, array_keys($field_config['options']))) {
                            return new WP_Error(
                                'invalid_option',
                                __('Please select valid options.', 'pn-customers-manager')
                            );
                        }
                    }
                }
                break;
        }
        
        return true;
    }

    /**
     * Sanitize input data
     *
     * @param mixed  $value The value to sanitize
     * @param string $node  The input node type
     * @param string $type  The input type
     * @return mixed Sanitized value
     */
    public static function pn_customers_manager_sanitize($value, $node = '', $type = '') {
        switch (strtolower($node)) {
            case 'input':
                switch (strtolower($type)) {
                    case 'email':
                        return sanitize_email($value);
                    case 'url':
                        return sanitize_url($value);
                    case 'color':
                        return sanitize_hex_color($value);
                    case 'number':
                    case 'tel':
                        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    case 'date':
                        return sanitize_text_field($value); // Date format is validated separately
                    default:
                        return sanitize_text_field($value);
                }

            case 'select':
                switch ($type) {
                    case 'select-multiple':
                        if (!is_array($value)) {
                            return [];
                        }
                        return array_map('sanitize_key', $value);
                    default:
                        return sanitize_key($value);
                }

            case 'textarea':
            case 'editor':
                return wp_kses_post($value);

            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Validate date format
     *
     * @param string $date   Date string to validate
     * @param string $format Expected date format
     * @return bool True if date is valid
     */
    private static function pn_customers_manager_is_valid_date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Get validation error message
     *
     * @param WP_Error $error The error object
     * @return string The error message
     */
    public static function pn_customers_manager_get_error_message($error) {
        if (!is_wp_error($error)) {
            return '';
        }
        return $error->get_error_message();
    }
} 