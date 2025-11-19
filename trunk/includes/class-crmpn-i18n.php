<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin so that it is ready for translation.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_i18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function crmpn_load_plugin_textdomain() {
		load_plugin_textdomain(
			'crmpn',
			false,
			dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
		);
	}

	public function crmpn_pll_get_post_types($post_types, $is_settings) {
    if ($is_settings){
        unset($post_types['crmpn_recipe']);
    } else {
        $post_types['crmpn_recipe'] = 'crmpn_recipe';
    }

    return $post_types;
  }

  public function crmpn_timestamp_server_gap() {
    $time = new DateTime(gmdate('Y-m-d H:i:s', time()));
    $current_time = new DateTime(gmdate('Y-m-d H:i:s', current_time('timestamp')));

    $interval = $current_time->diff($time);
    return ((($interval->invert) ? '-' : '+') . $interval->d . ' days ') . ((($interval->invert) ? '-' : '+') . $interval->h . ' hours ') . ((($interval->invert) ? '-' : '+') . $interval->i . ' minutes ') . ((($interval->invert) ? '-' : '+') . $interval->s . ' seconds');
  }

  public static function crmpn_get_post($post_id) {
  	// CRMPN_i18n::crmpn_get_post($post_id);
  	if (class_exists('Polylang') && function_exists('pll_get_post') && function_exists('pll_current_language')) {
  		return pll_get_post($post_id, pll_current_language('slug'));
  	} else {
  		return $post_id;
  	}
  }

  public static function crmpn_get_term($term_id) {
  	// CRMPN_i18n::crmpn_get_term($term_id);
  	if (class_exists('Polylang') && function_exists('pll_get_term') && function_exists('pll_current_language')) {
  		return pll_get_term($term_id, pll_current_language('slug'));
  	} else {
  		return $term_id;
  	}
  }
}