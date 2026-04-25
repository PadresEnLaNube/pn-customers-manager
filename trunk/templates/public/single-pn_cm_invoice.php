<?php
/**
 * Public template for single invoice view.
 * Accessed via token-based URL (no login required).
 *
 * @package PN_Customers_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get invoice data - set by the template_redirect handler via set_query_var().
$invoice_id = get_query_var( 'pn_cm_invoice_id', 0 );

if ( empty( $invoice_id ) ) {
	wp_die( esc_html__( 'Invoice not found.', 'pn-customers-manager' ) );
}

$invoice_number          = get_post_meta( $invoice_id, 'pn_cm_invoice_number', true );
$invoice_date            = get_post_meta( $invoice_id, 'pn_cm_invoice_date', true );
$invoice_due_date        = get_post_meta( $invoice_id, 'pn_cm_invoice_due_date', true );
$invoice_status          = get_post_meta( $invoice_id, 'pn_cm_invoice_status', true );
$invoice_tax_rate        = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_tax_rate', true ) );
$invoice_discount_rate   = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_discount_rate', true ) );
$invoice_client_notes    = get_post_meta( $invoice_id, 'pn_cm_invoice_client_notes', true );
$invoice_token           = get_post_meta( $invoice_id, 'pn_cm_invoice_token', true );
$invoice_subtotal        = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_subtotal', true ) );
$invoice_tax_amount      = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_tax_amount', true ) );
$invoice_discount_amount = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_discount_amount', true ) );
$invoice_total           = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_total', true ) );
$invoice_paid_at         = get_post_meta( $invoice_id, 'pn_cm_invoice_paid_at', true );

// Organization data.
$org_id        = get_post_meta( $invoice_id, 'pn_cm_invoice_organization_id', true );
$org_name      = ! empty( $org_id ) ? get_the_title( $org_id ) : '';
$org_email     = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_email', true ) : '';
$org_phone     = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_phone', true ) : '';
$org_address   = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_address', true ) : '';
$org_city      = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_city', true ) : '';
$org_postal    = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_postal_code', true ) : '';
$org_country   = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_country', true ) : '';
$org_fiscal_id = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_fiscal_id', true ) : '';

// Company data from settings (shared with budget).
$company_name      = get_option( 'pn_customers_manager_budget_company_name', '' );
$company_address   = get_option( 'pn_customers_manager_budget_company_address', '' );
$company_fiscal_id = get_option( 'pn_customers_manager_budget_company_fiscal_id', '' );
$company_logo_raw  = get_option( 'pn_customers_manager_budget_company_logo', '' );
$company_logo      = ( ! empty( $company_logo_raw ) && is_numeric( $company_logo_raw ) )
	? wp_get_attachment_url( intval( $company_logo_raw ) )
	: $company_logo_raw;
$terms             = get_option( 'pn_customers_manager_budget_terms', '' );

// Currency settings (shared with budget).
$currency_symbol   = get_option( 'pn_customers_manager_budget_currency_symbol', '€' );
$currency_position = get_option( 'pn_customers_manager_budget_currency_position', 'after' );

// Admin check for inline editing on public page.
$is_admin = current_user_can( 'manage_options' );

// Hide admin bar on this standalone page (prevents Chrome local-network-access dialog).
show_admin_bar( false );

// Invoice items from post meta.
$items = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_items( $invoice_id );

// Status display.
$status_labels = array(
	'draft'     => __( 'Draft', 'pn-customers-manager' ),
	'sent'      => __( 'Sent', 'pn-customers-manager' ),
	'paid'      => __( 'Paid', 'pn-customers-manager' ),
	'cancelled' => __( 'Cancelled', 'pn-customers-manager' ),
);
$status_label  = isset( $status_labels[ $invoice_status ] ) ? $status_labels[ $invoice_status ] : $invoice_status;

// Format currency helper.
$format_currency = function ( $amount ) use ( $currency_symbol, $currency_position ) {
	$formatted = number_format( floatval( $amount ), 2, ',', '.' );
	return 'before' === $currency_position ? $currency_symbol . $formatted : $formatted . ' ' . $currency_symbol;
};

// Enqueue styles and scripts.
wp_enqueue_style( 'pn-customers-manager-invoice', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-invoice.css', array(), PN_CUSTOMERS_MANAGER_VERSION );
wp_enqueue_style( 'material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined', array(), null );
wp_enqueue_script( 'pn-customers-manager-invoice', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-invoice.js', array( 'jquery' ), PN_CUSTOMERS_MANAGER_VERSION, true );
wp_localize_script(
	'pn-customers-manager-invoice',
	'pnCmInvoice',
	array(
		'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
		'nonce'            => wp_create_nonce( 'pn-customers-manager-nonce' ),
		'invoiceId'        => $invoice_id,
		'invoiceToken'     => $invoice_token,
		'currencySymbol'   => $currency_symbol,
		'currencyPosition' => $currency_position,
		'discountRate'     => $invoice_discount_rate,
		'taxRate'          => $invoice_tax_rate,
		'i18n'             => array(
			'error' => __( 'An error occurred. Please try again.', 'pn-customers-manager' ),
		),
	)
);

// Admin inline editing scripts.
if ( $is_admin ) {
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script(
		'pn-customers-manager-invoice-admin',
		PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-invoice-admin.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		PN_CUSTOMERS_MANAGER_VERSION,
		true
	);
	wp_localize_script(
		'pn-customers-manager-invoice-admin',
		'pnCmInvoiceAdmin',
		array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'pn-customers-manager-nonce' ),
			'invoiceId'         => $invoice_id,
			'currencySymbol'    => $currency_symbol,
			'currencyPosition'  => $currency_position,
			'defaultHourlyRate' => get_option( 'pn_customers_manager_budget_default_hourly_rate', '0' ),
			'i18n'              => array(
				'error'             => __( 'An error occurred.', 'pn-customers-manager' ),
				'confirmDelete'     => __( 'Are you sure you want to delete this item?', 'pn-customers-manager' ),
				'confirmSend'       => __( 'Are you sure you want to send this invoice?', 'pn-customers-manager' ),
				'invoiceSent'       => __( 'Invoice sent successfully.', 'pn-customers-manager' ),
				'invoiceRemoved'    => __( 'Invoice removed.', 'pn-customers-manager' ),
				'invoiceDuplicated' => __( 'Invoice duplicated.', 'pn-customers-manager' ),
				'noDescription'     => __( 'Please enter a description.', 'pn-customers-manager' ),
				'newPhase'          => __( 'New phase', 'pn-customers-manager' ),
			),
		)
	);

	// Enqueue popups CSS/JS if available.
	if ( wp_style_is( 'pn-customers-manager-popups', 'registered' ) ) {
		wp_enqueue_style( 'pn-customers-manager-popups' );
	}
	if ( wp_script_is( 'pn-customers-manager-popups', 'registered' ) ) {
		wp_enqueue_script( 'pn-customers-manager-popups' );
	}
}

// ─── Dequeue unnecessary assets loaded by common.php and theme ────────────────
// This standalone page only needs: invoice CSS/JS, material-icons, jQuery,
// and for admins: popups, sortable, invoice-admin JS.
add_action( 'wp_enqueue_scripts', function () use ( $is_admin ) {
	// Styles to always remove (conflict with invoice standalone layout).
	$styles_to_remove = array(
		'pn-customers-manager',              // Main plugin CSS — overrides invoice layout
		'pn-customers-manager-selector',
		'wph-trumbowyg',
		'pn-customers-manager-tooltips',
		'wph-owl',
		'pn-customers-manager-referral',
		'pn-customers-manager-commercial',
		'pn-customers-manager-email-campaigns',
	);

	// Scripts to always remove (not needed or cause layout shift).
	$scripts_to_remove = array(
		'pn-customers-manager',              // Main plugin JS
		'pn-customers-manager-aux',          // Initializes Selector on ALL selects — causes layout shift
		'pn-customers-manager-selector',
		'pn-customers-manager-forms',
		'pn-customers-manager-ajax',
		'wph-trumbowyg',
		'pn-customers-manager-tooltips',
		'wph-owl',
		'pn-customers-manager-referral',
		'pn-customers-manager-qrcode',
		'pn-customers-manager-referral-qr',
		'pn-customers-manager-referral-bizcard',
		'pn-customers-manager-commercial',
		'pn-customers-manager-email-campaigns',
	);

	if ( ! $is_admin ) {
		// Non-admin: also remove popups and material icons (not needed for public view).
		$styles_to_remove[]  = 'pn-customers-manager-popups';
		$styles_to_remove[]  = 'wph-material-icons-outlined';
		$scripts_to_remove[] = 'pn-customers-manager-popups';
		$scripts_to_remove[] = 'jquery-ui-sortable';
	}

	foreach ( $styles_to_remove as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	foreach ( $scripts_to_remove as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}

	// Also remove ALL theme and other plugin styles to prevent dark mode / layout conflicts.
	// Only keep our explicit whitelist.
	$allowed_styles = array(
		'pn-customers-manager-invoice',
		'material-icons',
		'pn-customers-manager-popups',
		'wph-material-icons-outlined',
	);

	global $wp_styles;
	if ( ! empty( $wp_styles->queue ) ) {
		$queued_styles = array_values( $wp_styles->queue );
		foreach ( $queued_styles as $handle ) {
			if ( ! in_array( $handle, $allowed_styles, true ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}

	// Also remove ALL theme/other plugin scripts — only keep our whitelist.
	$allowed_scripts = array(
		'jquery',
		'jquery-core',
		'jquery-migrate',
		'jquery-ui-core',
		'jquery-ui-sortable',
		'jquery-ui-mouse',
		'jquery-ui-widget',
		'pn-customers-manager-invoice',
		'pn-customers-manager-invoice-admin',
		'pn-customers-manager-popups',
	);

	global $wp_scripts;
	if ( ! empty( $wp_scripts->queue ) ) {
		$queued_scripts = array_values( $wp_scripts->queue );
		foreach ( $queued_scripts as $handle ) {
			if ( ! in_array( $handle, $allowed_scripts, true ) ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	}
}, 999 );

// Pre-process items into groups: loose items and phase groups.
$groups        = array();
$current_phase = null;
foreach ( $items as $item ) {
	if ( 'phase' === $item['item_type'] ) {
		$current_phase = count( $groups );
		$groups[]      = array(
			'phase' => $item,
			'items' => array(),
		);
	} else {
		if ( null !== $current_phase ) {
			$groups[ $current_phase ]['items'][] = $item;
		} else {
			// Loose item (before any phase).
			$groups[] = array(
				'phase' => null,
				'items' => array( $item ),
			);
		}
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( sprintf( __( 'Invoice %s', 'pn-customers-manager' ), $invoice_number ) ); ?></title>
	<?php
	// Schema.org JSON-LD structured data.
	$pn_cm_currency_map = array( '€' => 'EUR', '$' => 'USD', '£' => 'GBP', '¥' => 'JPY' );
	$pn_cm_currency_code = isset( $pn_cm_currency_map[ $currency_symbol ] ) ? $pn_cm_currency_map[ $currency_symbol ] : $currency_symbol;
	$pn_cm_schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'Invoice',
		'identifier'      => $invoice_number,
		'description'     => sprintf( 'Invoice %s', $invoice_number ),
		'totalPaymentDue' => array(
			'@type'    => 'PriceSpecification',
			'price'    => number_format( $invoice_total, 2, '.', '' ),
			'priceCurrency' => $pn_cm_currency_code,
		),
		'paymentStatus'   => ( 'paid' === $invoice_status ) ? 'PaymentComplete' : 'PaymentDue',
	);
	if ( ! empty( $invoice_date ) ) {
		$pn_cm_schema['datePublished'] = $invoice_date;
	}
	if ( ! empty( $invoice_due_date ) ) {
		$pn_cm_schema['paymentDueDate'] = $invoice_due_date;
	}
	if ( ! empty( $company_name ) ) {
		$pn_cm_schema['provider'] = array(
			'@type' => 'Organization',
			'name'  => $company_name,
		);
	}
	if ( ! empty( $org_name ) ) {
		$pn_cm_schema['customer'] = array(
			'@type' => 'Organization',
			'name'  => $org_name,
		);
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $pn_cm_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
	<?php wp_head(); ?>
</head>
<body class="pn-customers-manager-invoice-public">
	<div class="pn-cm-invoice-wrapper">
		<!-- Header -->
		<header class="pn-cm-invoice-header">
			<div class="pn-cm-invoice-header-company">
				<?php if ( ! empty( $company_logo ) ) : ?>
					<img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company_name ); ?>" class="pn-cm-invoice-logo">
				<?php endif; ?>
				<div class="pn-cm-invoice-company-info">
					<?php if ( ! empty( $company_name ) ) : ?>
						<h2><?php echo esc_html( $company_name ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $company_address ) ) : ?>
						<p><?php echo nl2br( esc_html( $company_address ) ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $company_fiscal_id ) ) : ?>
						<p><?php echo esc_html( $company_fiscal_id ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<div class="pn-cm-invoice-header-meta">
				<h1><?php echo esc_html( sprintf( __( 'Invoice %s', 'pn-customers-manager' ), $invoice_number ) ); ?></h1>
				<span class="pn-cm-invoice-status pn-cm-invoice-status-<?php echo esc_attr( $invoice_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
			</div>
		</header>

		<!-- Invoice info row -->
		<div class="pn-cm-invoice-info-row">
			<div class="pn-cm-invoice-info-block">
				<h3><?php esc_html_e( 'Bill to', 'pn-customers-manager' ); ?></h3>
				<?php if ( ! empty( $org_name ) ) : ?>
					<p><strong><?php echo esc_html( $org_name ); ?></strong></p>
				<?php endif; ?>
				<?php if ( ! empty( $org_address ) ) : ?>
					<p><?php echo esc_html( implode( ', ', array_filter( array( $org_address, $org_postal, $org_city, $org_country ) ) ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $org_fiscal_id ) ) : ?>
					<p><?php echo esc_html( $org_fiscal_id ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $org_email ) ) : ?>
					<p><?php echo esc_html( $org_email ); ?></p>
				<?php endif; ?>
			</div>
			<div class="pn-cm-invoice-info-block">
				<h3><?php esc_html_e( 'Details', 'pn-customers-manager' ); ?></h3>
				<p><strong><?php esc_html_e( 'Date:', 'pn-customers-manager' ); ?></strong> <?php echo esc_html( $invoice_date ); ?></p>
				<p><strong><?php esc_html_e( 'Due date:', 'pn-customers-manager' ); ?></strong> <?php echo esc_html( $invoice_due_date ); ?></p>
			</div>
		</div>

		<!-- Client notes -->
		<?php if ( ! empty( $invoice_client_notes ) ) : ?>
			<div class="pn-cm-invoice-client-notes">
				<p><?php echo nl2br( esc_html( $invoice_client_notes ) ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Items -->
		<div class="pn-cm-invoice-items-section" data-invoice-id="<?php echo esc_attr( $invoice_id ); ?>">
			<?php if ( $is_admin ) : ?>
				<input type="hidden" id="pn_cm_invoice_tax_rate" value="<?php echo esc_attr( $invoice_tax_rate ); ?>" />
				<input type="hidden" id="pn_cm_invoice_discount_rate" value="<?php echo esc_attr( $invoice_discount_rate ); ?>" />
			<?php endif; ?>
			<div class="pn-cm-invoice-items-list" id="pn-cm-invoice-items-body">
				<?php if ( ! empty( $groups ) ) : ?>
					<?php foreach ( $groups as $group ) : ?>
						<?php if ( null === $group['phase'] ) : ?>
							<?php // Loose items (no phase parent). ?>
							<?php foreach ( $group['items'] as $item ) : ?>
								<div class="pn-cm-invoice-item-row"
									data-item-id="<?php echo esc_attr( $item['id'] ); ?>"
									data-item-type="<?php echo esc_attr( $item['item_type'] ); ?>"
									data-item-description="<?php echo esc_attr( $item['description'] ); ?>"
									data-item-quantity="<?php echo esc_attr( number_format( floatval( $item['quantity'] ), 2, '.', '' ) ); ?>"
									data-item-unit-price="<?php echo esc_attr( number_format( floatval( $item['unit_price'] ), 2, '.', '' ) ); ?>"
									data-item-total="<?php echo esc_attr( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ); ?>">
									<?php if ( $is_admin ) : ?>
										<span class="pn-cm-invoice-col-drag"><i class="material-icons-outlined pn-cm-invoice-drag-handle">drag_indicator</i></span>
									<?php endif; ?>
									<div class="pn-cm-invoice-item-content">
										<div class="pn-cm-invoice-item-title">
											<span class="pn-cm-invoice-col-desc"><?php echo esc_html( $item['description'] ); ?></span>
											<?php if ( $is_admin ) : ?>
												<span class="pn-cm-invoice-col-actions">
													<a href="#" class="pn-cm-invoice-row-edit" title="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
													<a href="#" class="pn-cm-invoice-row-delete" title="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
												</span>
											<?php endif; ?>
										</div>
										<div class="pn-cm-invoice-item-meta">
											<span class="pn-cm-invoice-col-type"><i class="material-icons-outlined"><?php echo 'hours' === $item['item_type'] ? 'schedule' : 'payments'; ?></i></span>
											<span class="pn-cm-invoice-col-qty"><?php echo esc_html( number_format( floatval( $item['quantity'] ), 2, ',', '.' ) ); ?></span>
											<span class="pn-cm-invoice-col-price"><?php echo esc_html( $format_currency( $item['unit_price'] ) ); ?></span>
											<span class="pn-cm-invoice-col-total"><?php echo esc_html( $format_currency( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ) ); ?></span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<?php
							// Phase group.
							$phase_item  = $group['phase'];
							$phase_items = $group['items'];
							?>
							<div class="pn-cm-invoice-phase-group" data-phase-id="<?php echo esc_attr( $phase_item['id'] ); ?>">
								<div class="pn-cm-invoice-item-row pn-cm-invoice-item-phase"
									data-item-id="<?php echo esc_attr( $phase_item['id'] ); ?>"
									data-item-type="phase"
									data-item-description="<?php echo esc_attr( $phase_item['description'] ); ?>">
									<?php if ( $is_admin ) : ?>
										<span class="pn-cm-invoice-col-drag"><i class="material-icons-outlined pn-cm-invoice-drag-handle">drag_indicator</i></span>
									<?php endif; ?>
									<i class="material-icons-outlined pn-cm-invoice-phase-toggle">expand_more</i>
									<div class="pn-cm-invoice-item-content">
										<span class="pn-cm-invoice-col-phase"><?php echo esc_html( $phase_item['description'] ); ?></span>
										<?php if ( $is_admin ) : ?>
											<span class="pn-cm-invoice-col-actions">
												<a href="#" class="pn-cm-invoice-row-edit" title="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
												<a href="#" class="pn-cm-invoice-row-delete" title="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
											</span>
										<?php endif; ?>
									</div>
								</div>
								<div class="pn-cm-invoice-phase-items">
									<?php foreach ( $phase_items as $item ) : ?>
										<div class="pn-cm-invoice-item-row"
											data-item-id="<?php echo esc_attr( $item['id'] ); ?>"
											data-item-type="<?php echo esc_attr( $item['item_type'] ); ?>"
											data-item-description="<?php echo esc_attr( $item['description'] ); ?>"
											data-item-quantity="<?php echo esc_attr( number_format( floatval( $item['quantity'] ), 2, '.', '' ) ); ?>"
											data-item-unit-price="<?php echo esc_attr( number_format( floatval( $item['unit_price'] ), 2, '.', '' ) ); ?>"
											data-item-total="<?php echo esc_attr( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ); ?>">
											<?php if ( $is_admin ) : ?>
												<span class="pn-cm-invoice-col-drag"><i class="material-icons-outlined pn-cm-invoice-drag-handle">drag_indicator</i></span>
											<?php endif; ?>
											<div class="pn-cm-invoice-item-content">
												<div class="pn-cm-invoice-item-title">
													<span class="pn-cm-invoice-col-desc"><?php echo esc_html( $item['description'] ); ?></span>
													<?php if ( $is_admin ) : ?>
														<span class="pn-cm-invoice-col-actions">
															<a href="#" class="pn-cm-invoice-row-edit" title="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
															<a href="#" class="pn-cm-invoice-row-delete" title="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
														</span>
													<?php endif; ?>
												</div>
												<div class="pn-cm-invoice-item-meta">
													<span class="pn-cm-invoice-col-type"><i class="material-icons-outlined"><?php echo 'hours' === $item['item_type'] ? 'schedule' : 'payments'; ?></i></span>
													<span class="pn-cm-invoice-col-qty"><?php echo esc_html( number_format( floatval( $item['quantity'] ), 2, ',', '.' ) ); ?></span>
													<span class="pn-cm-invoice-col-price"><?php echo esc_html( $format_currency( $item['unit_price'] ) ); ?></span>
													<span class="pn-cm-invoice-col-total"><?php echo esc_html( $format_currency( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ) ); ?></span>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="pn-cm-invoice-no-items">
						<?php esc_html_e( 'No items in this invoice.', 'pn-customers-manager' ); ?>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( $is_admin ) : ?>
				<div class="pn-cm-invoice-admin-buttons">
					<a href="#" class="pn-cm-invoice-btn pn-cm-invoice-btn-secondary pn-customers-manager-invoice-add-phase">
						<i class="material-icons-outlined">segment</i>
						<?php esc_html_e( 'Add phase', 'pn-customers-manager' ); ?>
					</a>
					<a href="#" class="pn-cm-invoice-btn pn-cm-invoice-btn-secondary pn-customers-manager-invoice-add-item">
						<i class="material-icons-outlined">add</i>
						<?php esc_html_e( 'Add item', 'pn-customers-manager' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

		<!-- Totals -->
		<div class="pn-cm-invoice-totals">
			<div class="pn-cm-invoice-totals-row">
				<span><?php esc_html_e( 'Subtotal', 'pn-customers-manager' ); ?></span>
				<span id="pn-cm-invoice-subtotal"><?php echo esc_html( $format_currency( $invoice_subtotal ) ); ?></span>
			</div>
			<?php if ( $invoice_discount_rate > 0 ) : ?>
				<div class="pn-cm-invoice-totals-row pn-cm-invoice-totals-discount">
					<span><?php echo esc_html( sprintf( __( 'Discount (%s%%)', 'pn-customers-manager' ), number_format( $invoice_discount_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-invoice-discount">-<?php echo esc_html( $format_currency( $invoice_discount_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $invoice_tax_rate > 0 ) : ?>
				<div class="pn-cm-invoice-totals-row">
					<span><?php echo esc_html( sprintf( __( 'Tax (%s%%)', 'pn-customers-manager' ), number_format( $invoice_tax_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-invoice-tax"><?php echo esc_html( $format_currency( $invoice_tax_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<div class="pn-cm-invoice-totals-row pn-cm-invoice-totals-total">
				<span><?php esc_html_e( 'Total', 'pn-customers-manager' ); ?></span>
				<span id="pn-cm-invoice-total"><?php echo esc_html( $format_currency( $invoice_total ) ); ?></span>
			</div>
		</div>

		<!-- Status messages -->
		<?php if ( 'paid' === $invoice_status && ! empty( $invoice_paid_at ) ) : ?>
			<div class="pn-cm-invoice-status-message pn-cm-invoice-status-paid-msg">
				<i class="material-icons-outlined">check_circle</i>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: date and time when the invoice was paid */
						__( 'Paid on %s.', 'pn-customers-manager' ),
						date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $invoice_paid_at ) )
					)
				);
				?>
			</div>
		<?php elseif ( 'cancelled' === $invoice_status ) : ?>
			<div class="pn-cm-invoice-status-message pn-cm-invoice-status-cancelled-msg">
				<i class="material-icons-outlined">cancel</i>
				<?php esc_html_e( 'This invoice has been cancelled.', 'pn-customers-manager' ); ?>
			</div>
		<?php endif; ?>

		<!-- Terms -->
		<?php if ( ! empty( $terms ) ) : ?>
			<div class="pn-cm-invoice-terms">
				<h3><?php esc_html_e( 'Terms and conditions', 'pn-customers-manager' ); ?></h3>
				<div class="pn-cm-invoice-terms-content">
					<?php echo nl2br( esc_html( $terms ) ); ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Print button -->
		<div class="pn-cm-invoice-print-btn-wrapper">
			<button type="button" id="pn-cm-invoice-print" class="pn-cm-invoice-btn pn-cm-invoice-btn-print">
				<i class="material-icons-outlined">picture_as_pdf</i>
				<?php esc_html_e( 'Generate PDF', 'pn-customers-manager' ); ?>
			</button>
		</div>
	</div>

	<?php wp_footer(); ?>
</body>
</html>
