<?php
/**
 * Funnel taxonomies creator.
 *
 * This class defines Funnel taxonomies.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Taxonomies_Funnel { 
	/**
	 * Register taxonomies.
	 *
	 * @since    1.0.0
	 */
	public static function crmpn_register_taxonomies() {
		$taxonomies = [
			'crmpn_funnel_category' => [
				'name'              		=> _x('Funnel category', 'Taxonomy general name', 'crmpn'),
				'singular_name'     		=> _x('Funnel category', 'Taxonomy singular name', 'crmpn'),
				'search_items'     			=> esc_html(__('Search Funnel categories', 'crmpn')),
	        'all_items'         			=> esc_html(__('All Funnel categories', 'crmpn')),
	        'parent_item'       			=> esc_html(__('Parent Funnel category', 'crmpn')),
	        'parent_item_colon' 			=> esc_html(__('Parent Funnel category:', 'crmpn')),
	        'edit_item'         			=> esc_html(__('Edit Funnel category', 'crmpn')),
	        'update_item'       			=> esc_html(__('Update Funnel category', 'crmpn')),
	        'add_new_item'      			=> esc_html(__('Add New Funnel category', 'crmpn')),
	        'new_item_name'     			=> esc_html(__('New Funnel category', 'crmpn')),
	        'menu_name'         			=> esc_html(__('Funnel categories', 'crmpn')),
				'archive'			      	=> true,
				'slug'			      		=> 'funnel-category',
			],
		];

	  foreach ($taxonomies as $taxonomy => $options) {
	  	$labels = [
				'name'          			=> $options['name'],
				'singular_name' 			=> $options['singular_name'],
			];

			$args = [
				'labels'            		=> $labels,
				'hierarchical'      		=> true,
				'public'            		=> true,
				'show_ui' 					=> true,
				'query_var'         		=> true,
				'rewrite'           		=> true,
				'show_in_rest'      		=> true,
	    		'capabilities'      		=> CRMPN_ROLE_CRMPN_BASECPT_CAPABILITIES,
			];

			if ($options['archive']) {
				$args['public'] = true;
				$args['publicly_queryable'] = true;
				$args['show_in_nav_menus'] = true;
				$args['query_var'] = $taxonomy;
				$args['show_ui'] = true;
				$args['rewrite'] = [
					'slug' 					=> $options['slug'],
				];
			}

			register_taxonomy($taxonomy, 'crmpn_funnel', $args);
			register_taxonomy_for_object_type($taxonomy, 'crmpn_funnel');
		}
	}
}