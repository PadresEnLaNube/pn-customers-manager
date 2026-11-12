<?php
/**
 * iCalendar Download Handler
 *
 * Handles iCalendar file downloads
 *
 * @link       padresenlanube.com/
 * @since      1.1.75
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */

if (!defined('ABSPATH')) exit;

class PN_CUSTOMERS_MANAGER_WooCommerce_iCal_Download {

	public function __construct() {
		add_action('init', [$this, 'handle_download']);
	}

	public function handle_download() {
		if (isset($_GET['pn_cm_ical'])) {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-Disposition: attachment; filename=invite.ics');

			$ics = new PN_CUSTOMERS_MANAGER_ICS(array(
				'location'    => isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '',
				'description' => isset($_GET['description']) ? sanitize_text_field($_GET['description']) : '',
				'dtstart'     => isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : '',
				'dtend'       => isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : '',
				'summary'     => isset($_GET['summary']) ? sanitize_text_field($_GET['summary']) : '',
				'url'         => isset($_GET['url']) ? esc_url_raw($_GET['url']) : ''
			));

			echo $ics->to_string(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar data output with Content-Type: text/calendar header; inputs are sanitized above.
			exit;
		}
	}
}
