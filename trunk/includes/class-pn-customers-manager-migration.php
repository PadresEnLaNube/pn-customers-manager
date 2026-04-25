<?php
/**
 * One-time migration: move budget items from custom table to post meta.
 *
 * @package PN_CUSTOMERS_MANAGER
 */

class PN_CUSTOMERS_MANAGER_Migration {

  /**
   * Migrate budget items from pn_cm_budget_items table to post meta.
   * Safe to run multiple times — skips already-migrated budgets.
   */
  public static function migrate_budget_items_to_meta() {
    global $wpdb;
    $table = $wpdb->prefix . 'pn_cm_budget_items';

    // Check if table exists
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
      update_option( 'pn_cm_budget_items_migrated', '1' );
      return;
    }

    // Get all distinct budget_ids
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $budget_ids = $wpdb->get_col( "SELECT DISTINCT budget_id FROM {$table}" );

    if ( empty( $budget_ids ) ) {
      update_option( 'pn_cm_budget_items_migrated', '1' );
      return;
    }

    foreach ( $budget_ids as $budget_id ) {
      $budget_id = intval( $budget_id );

      // Skip if already migrated
      if ( get_post_meta( $budget_id, 'pn_cm_budget_items', true ) ) {
        continue;
      }

      // Verify post exists and is a budget
      if ( get_post_type( $budget_id ) !== 'pn_cm_budget' ) {
        continue;
      }

      $rows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT * FROM {$table} WHERE budget_id = %d ORDER BY sort_order ASC",
          $budget_id
        ),
        ARRAY_A
      );

      $items  = [];
      $max_id = 0;
      foreach ( $rows as $row ) {
        $item = [
          'id'          => intval( $row['id'] ),
          'item_type'   => $row['item_type'],
          'description' => $row['description'],
          'quantity'    => floatval( $row['quantity'] ),
          'unit_price'  => floatval( $row['unit_price'] ),
          'total'       => floatval( $row['total'] ),
          'is_optional' => intval( $row['is_optional'] ),
          'is_selected' => intval( $row['is_selected'] ),
          'sort_order'  => intval( $row['sort_order'] ),
        ];
        $items[] = $item;
        if ( $item['id'] > $max_id ) {
          $max_id = $item['id'];
        }
      }

      update_post_meta( $budget_id, 'pn_cm_budget_items', $items );
      update_post_meta( $budget_id, 'pn_cm_budget_next_item_id', $max_id + 1 );
    }

    update_option( 'pn_cm_budget_items_migrated', '1' );
  }
}
