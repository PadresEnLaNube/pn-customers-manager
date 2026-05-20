<?php
/**
 * One-time migration script for Calendar Booking Products data
 *
 * Migrates meta keys from old 'free' plugin to new structure
 *
 * @link       padresenlanube.com/
 * @since      1.1.75
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce_Migration {

	/**
	 * Migrate Calendar Booking Products data from free plugin to pn-customers-manager
	 *
	 * @return array Statistics about the migration
	 */
	public static function migrate_calendar_booking_data() {
		global $wpdb;

		// Flag to avoid multiple executions
		$migrated = get_option('pn_cm_woo_calendar_migrated', false);
		if ($migrated) {
			return ['status' => 'already_migrated', 'message' => __('Migration already completed', 'pn-customers-manager')];
		}

		$stats = [
			'products_migrated' => 0,
			'meta_keys_updated' => 0,
			'order_meta_updated' => 0,
			'errors' => [],
		];

		// Find all products with calendar booking products active
		$products = get_posts([
			'post_type' => 'product',
			'posts_per_page' => -1,
			'meta_key' => '_free_camp_enabled',
			'meta_value' => 'yes',
			'post_status' => 'any',
		]);

		// Mapping of old to new meta keys
		$old_to_new_keys = [
			'_free_camp_enabled' => '_pn_cm_woo_calendar_enabled',
			'_free_camp_show_availability' => '_pn_cm_woo_calendar_show_availability',
			'_free_camp_day_price' => '_pn_cm_woo_calendar_day_price',
			'_free_camp_member_day_price' => '_pn_cm_woo_calendar_member_day_price',
			'_free_camp_schedule' => '_pn_cm_woo_calendar_schedule',
			'_free_camp_weeks' => '_pn_cm_woo_calendar_weeks',
			'_free_camp_bookings' => '_pn_cm_woo_calendar_bookings',
		];

		foreach ($products as $product) {
			try {
				foreach ($old_to_new_keys as $old_key => $new_key) {
					$value = get_post_meta($product->ID, $old_key, true);
					if ($value !== '' && $value !== false) {
						update_post_meta($product->ID, $new_key, $value);
						$stats['meta_keys_updated']++;
					}
				}

				// Migrate member role: free_role_parent → array with that role
				// Only add if there's a member price configured
				$member_price = get_post_meta($product->ID, '_pn_cm_woo_calendar_member_day_price', true);
				if (!empty($member_price) && $member_price > 0) {
					update_post_meta($product->ID, '_pn_cm_woo_calendar_member_roles', ['free_role_parent']);
				}

				$stats['products_migrated']++;
			} catch (Exception $e) {
				$stats['errors'][] = "Product {$product->ID}: " . $e->getMessage();
			}
		}

		// Migrate meta in orders (order line items)
		$order_meta_updated = $wpdb->query($wpdb->prepare("
			UPDATE {$wpdb->prefix}woocommerce_order_itemmeta
			SET meta_key = REPLACE(meta_key, '_free_camp_', '_pn_cm_woo_calendar_')
			WHERE meta_key LIKE %s
		", '_free_camp_%'));

		if ($order_meta_updated !== false) {
			$stats['order_meta_updated'] = $order_meta_updated;
		}

		// Migrate order meta (bookings counted flag)
		$wpdb->query("
			UPDATE {$wpdb->prefix}postmeta
			SET meta_key = '_pn_cm_woo_calendar_bookings_counted'
			WHERE meta_key = '_free_camp_bookings_counted'
			AND post_id IN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order')
		");

		// Mark as migrated
		update_option('pn_cm_woo_calendar_migrated', true);
		update_option('pn_cm_woo_calendar_migration_log', $stats);

		return $stats;
	}

	/**
	 * Get migration status and log
	 *
	 * @return array Migration status and log
	 */
	public static function get_migration_status() {
		$migrated = get_option('pn_cm_woo_calendar_migrated', false);
		$log = get_option('pn_cm_woo_calendar_migration_log', []);

		return [
			'migrated' => $migrated,
			'log' => $log,
		];
	}

	/**
	 * Reset migration flag (for testing purposes only)
	 */
	public static function reset_migration_flag() {
		delete_option('pn_cm_woo_calendar_migrated');
		delete_option('pn_cm_woo_calendar_migration_log');
	}
}
