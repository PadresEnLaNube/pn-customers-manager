<?php
/**
 * Funnel taxonomies creator.
 *
 * This class defines Funnel taxonomies.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Taxonomies_Funnel { 
	/**
	 * Register taxonomies.
	 *
	 * @since    1.0.0
	 */
	public static function customers_manager_pn_register_taxonomies() {
		$taxonomies = [
			'cm_pn_funnel_category' => [
				'name'              		=> _x('Funnel category', 'Taxonomy general name', 'customers-manager-pn'),
				'singular_name'     		=> _x('Funnel category', 'Taxonomy singular name', 'customers-manager-pn'),
				'search_items'     			=> esc_html(__('Search Funnel categories', 'customers-manager-pn')),
	        'all_items'         			=> esc_html(__('All Funnel categories', 'customers-manager-pn')),
	        'parent_item'       			=> esc_html(__('Parent Funnel category', 'customers-manager-pn')),
	        'parent_item_colon' 			=> esc_html(__('Parent Funnel category:', 'customers-manager-pn')),
	        'edit_item'         			=> esc_html(__('Edit Funnel category', 'customers-manager-pn')),
	        'update_item'       			=> esc_html(__('Update Funnel category', 'customers-manager-pn')),
	        'add_new_item'      			=> esc_html(__('Add New Funnel category', 'customers-manager-pn')),
	        'new_item_name'     			=> esc_html(__('New Funnel category', 'customers-manager-pn')),
	        'menu_name'         			=> esc_html(__('Funnel categories', 'customers-manager-pn')),
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
	    		'capabilities'      		=> CUSTOMERS_MANAGER_PN_ROLE_CM_PN_FUNNEL_CAPABILITIES,
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