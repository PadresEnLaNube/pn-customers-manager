<?php
/**
 * Class CRMPN_Popups
 * Handles popup functionality for the CRMPN plugin
 */
class CRMPN_Popups {
    /**
     * The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main CRMPN_Popups Instance
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
            'id' => uniqid('crmpn-popup-'),
            'class' => '',
            'closeButton' => true,
            'overlayClose' => true,
            'escClose' => true
        );

        $options = wp_parse_args($options, $defaults);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($options['id']); ?>" class="crmpn-popup crmpn-display-none-soft <?php echo esc_attr($options['class']); ?>">
            <div class="crmpn-popup-overlay"></div>
            <div class="crmpn-popup-content">
                <?php if ($options['closeButton']) : ?>
                    <button type="button" class="crmpn-popup-close"><i class="material-icons-outlined">close</i></button>
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
            ? "CRMPN_Popups.close('" . esc_js($id) . "');"
            : "CRMPN_Popups.close();";
            
        wp_add_inline_script('crmpn-popups', $script);
        return '';
    }
} 