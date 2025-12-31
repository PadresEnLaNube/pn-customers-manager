<?php
/**
 * Class PN_CUSTOMERS_MANAGER_Popups
 * Handles popup functionality for the Customers Manager PN plugin
 */
class PN_CUSTOMERS_MANAGER_Popups {
    /**
     * The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main PN_CUSTOMERS_MANAGER_Popups Instance
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
            'id' => uniqid('pn-customers-manager-popup-'),
            'class' => '',
            'closeButton' => true,
            'overlayClose' => true,
            'escClose' => true
        );

        $options = wp_parse_args($options, $defaults);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($options['id']); ?>" class="pn-customers-manager-popup pn-customers-manager-display-none-soft <?php echo esc_attr($options['class']); ?>">
            <div class="pn-customers-manager-popup-overlay"></div>
            <div class="pn-customers-manager-popup-content">
                <?php if ($options['closeButton']) : ?>
                    <button type="button" class="pn-customers-manager-popup-close"><i class="material-icons-outlined">close</i></button>
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
            ? "PN_CUSTOMERS_MANAGER_Popups.close('" . esc_js($id) . "');"
            : "PN_CUSTOMERS_MANAGER_Popups.close();";
            
        wp_add_inline_script('pn-customers-manager-popups', $script);
        return '';
    }
} 