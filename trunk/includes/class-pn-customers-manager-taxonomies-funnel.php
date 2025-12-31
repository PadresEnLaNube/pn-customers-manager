<?php
/**
 * Funnel taxonomies creator.
 *
 * This class defines Funnel taxonomies.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Taxonomies_Funnel { 
	/**
	 * Register taxonomies.
	 *
	 * @since    1.0.0
	 */
	public static function PN_CUSTOMERS_MANAGER_register_taxonomies() {
		$taxonomies = [
			'cm_pn_funnel_category' => [
				'name'              		=> _x('Funnel category', 'Taxonomy general name', 'pn-customers-manager'),
				'singular_name'     		=> _x('Funnel category', 'Taxonomy singular name', 'pn-customers-manager'),
				'search_items'     			=> esc_html(__('Search Funnel categories', 'pn-customers-manager')),
	        'all_items'         			=> esc_html(__('All Funnel categories', 'pn-customers-manager')),
	        'parent_item'       			=> esc_html(__('Parent Funnel category', 'pn-customers-manager')),
	        'parent_item_colon' 			=> esc_html(__('Parent Funnel category:', 'pn-customers-manager')),
	        'edit_item'         			=> esc_html(__('Edit Funnel category', 'pn-customers-manager')),
	        'update_item'       			=> esc_html(__('Update Funnel category', 'pn-customers-manager')),
	        'add_new_item'      			=> esc_html(__('Add New Funnel category', 'pn-customers-manager')),
	        'new_item_name'     			=> esc_html(__('New Funnel category', 'pn-customers-manager')),
	        'menu_name'         			=> esc_html(__('Funnel categories', 'pn-customers-manager')),
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
	    		'capabilities'      		=> PN_CUSTOMERS_MANAGER_ROLE_CM_PN_FUNNEL_CAPABILITIES,
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

			register_taxonomy($taxonomy, 'cm_pn_funnel', $args);
			register_taxonomy_for_object_type($taxonomy, 'cm_pn_funnel');
		}
	}
}