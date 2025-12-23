<?php
/**
 * Class CUSTOMERS_MANAGER_PN_Popups
 * Handles popup functionality for the Customers Manager PN plugin
 */
class CUSTOMERS_MANAGER_PN_Popups {
    /**
     * The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main CUSTOMERS_MANAGER_PN_Popups Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Open a popup
     */
    public static function open($content, $options = array()) {
        $defaults = array(
            'id' => uniqid('customers-manager-pn-popup-'),
            'class' => '',
            'closeButton' => true,
            'overlayClose' => true,
            'escClose' => true
        );

        $options = wp_parse_args($options, $defaults);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($options['id']); ?>" class="customers-manager-pn-popup customers-manager-pn-display-none-soft <?php echo esc_attr($options['class']); ?>">
            <div class="customers-manager-pn-popup-overlay"></div>
            <div class="customers-manager-pn-popup-content">
                <?php if ($options['closeButton']) : ?>
                    <button type="button" class="customers-manager-pn-popup-close"><i class="material-icons-outlined">close</i></button>
                <?php endif; ?>
                <?php echo wp_kses_post($content); ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Close a popup
     */
    public static function close($id = null) {
        $script = $id 
            ? "CUSTOMERS_MANAGER_PN_Popups.close('" . esc_js($id) . "');"
            : "CUSTOMERS_MANAGER_PN_Popups.close();";
            
        wp_add_inline_script('customers-manager-pn-popups', $script);
        return '';
    }
} 