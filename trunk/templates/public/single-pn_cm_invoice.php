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
$invoice_irpf_rate       = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_irpf_rate', true ) );
$invoice_irpf_amount     = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_irpf_amount', true ) );
$invoice_client_notes    = get_post_meta( $invoice_id, 'pn_cm_invoice_client_notes', true );
$invoice_token           = get_post_meta( $invoice_id, 'pn_cm_invoice_token', true );
$invoice_subtotal        = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_subtotal', true ) );
$invoice_tax_amount      = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_tax_amount', true ) );
$invoice_discount_amount = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_discount_amount', true ) );
$invoice_total           = floatval( get_post_meta( $invoice_id, 'pn_cm_invoice_total', true ) );
$invoice_paid_at         = get_post_meta( $invoice_id, 'pn_cm_invoice_paid_at', true );
$invoice_type            = get_post_meta( $invoice_id, 'pn_cm_invoice_type', true );

// Organization data.
$org_id        = get_post_meta( $invoice_id, 'pn_cm_invoice_organization_id', true );
$org_name      = ! empty( $org_id ) ? get_the_title( $org_id ) : '';
$org_email     = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_email', true ) : '';
$org_phone     = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_phone', true ) : '';
$org_address   = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_address', true ) : '';
$org_city      = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_city', true ) : '';
$org_postal    = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_postal_code', true ) : '';
$org_country_code = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_country', true ) : '';
$org_country   = PN_CUSTOMERS_MANAGER_Post_Type_Organization::pn_cm_organization_get_country_name( $org_country_code );
$org_fiscal_id = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_fiscal_id', true ) : '';

// Company data from settings (shared with budget).
$company_name      = get_option( 'pn_customers_manager_budget_company_name', '' );
$company_address   = get_option( 'pn_customers_manager_budget_company_address', '' );
$company_fiscal_id = get_option( 'pn_customers_manager_budget_company_fiscal_id', '' );
$company_logo_raw  = get_option( 'pn_customers_manager_budget_company_logo', '' );
$company_logo      = ( ! empty( $company_logo_raw ) && is_numeric( $company_logo_raw ) )
	? wp_get_attachment_url( intval( $company_logo_raw ) )
	: $company_logo_raw;
$invoice_conditions = get_post_meta( $invoice_id, 'pn_cm_invoice_conditions', true );
$invoice_terms_setting = get_option( 'pn_customers_manager_invoice_terms', '' );
$terms              = ! empty( $invoice_conditions ) ? $invoice_conditions : ( ! empty( $invoice_terms_setting ) ? $invoice_terms_setting : get_option( 'pn_customers_manager_budget_terms', '' ) );
$footer_image_raw   = get_post_meta( $invoice_id, 'pn_cm_invoice_footer_image', true );
$footer_image       = ( ! empty( $footer_image_raw ) && is_numeric( $footer_image_raw ) )
	? wp_get_attachment_url( intval( $footer_image_raw ) )
	: $footer_image_raw;

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
		'irpfRate'         => $invoice_irpf_rate,
		'isAdmin'          => $is_admin,
		'i18n'             => array(
			'error' => __( 'An error occurred. Please try again.', 'pn-customers-manager' ),
		),
	)
);

// Admin inline editing scripts.
if ( $is_admin ) {
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'pn-cm-chartjs', PN_CUSTOMERS_MANAGER_URL . 'assets/js/vendor/chart.min.js', array(), PN_CUSTOMERS_MANAGER_VERSION, true );
	wp_enqueue_script(
		'pn-customers-manager-invoice-admin',
		PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-invoice-admin.js',
		array( 'jquery', 'jquery-ui-sortable', 'pn-cm-chartjs' ),
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
				'error'              => __( 'An error occurred.', 'pn-customers-manager' ),
				'confirmDelete'      => __( 'Are you sure you want to delete this item?', 'pn-customers-manager' ),
				'confirmSend'        => __( 'Are you sure you want to send this invoice?', 'pn-customers-manager' ),
				'invoiceSent'        => __( 'Invoice sent successfully.', 'pn-customers-manager' ),
				'invoiceRemoved'     => __( 'Invoice removed.', 'pn-customers-manager' ),
				'invoiceDuplicated'  => __( 'Invoice duplicated.', 'pn-customers-manager' ),
				'noDescription'      => __( 'Please enter a description.', 'pn-customers-manager' ),
				'newPhase'           => __( 'New phase', 'pn-customers-manager' ),
				'importItemsTitle'   => __( 'Import items from CSV', 'pn-customers-manager' ),
				'importDropText'     => __( 'Drag & drop a CSV file here or click to select', 'pn-customers-manager' ),
				'importInvalidFile'  => __( 'Please select a valid CSV file.', 'pn-customers-manager' ),
				'importSuccess'      => __( 'items imported successfully.', 'pn-customers-manager' ),
				'noItemsSelected'    => __( 'No items selected.', 'pn-customers-manager' ),
				'confirmBulkDelete'  => __( 'Are you sure you want to delete the selected items?', 'pn-customers-manager' ),
			),
		)
	);

	// Enqueue popups CSS/JS (directly with full path — common.php skips token pages).
	wp_enqueue_style( 'pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', array(), PN_CUSTOMERS_MANAGER_VERSION );
	wp_enqueue_script( 'pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', array( 'jquery' ), PN_CUSTOMERS_MANAGER_VERSION, true );

	// Payment table styles (shared with budget).
	wp_enqueue_style( 'pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-budget.css', array(), PN_CUSTOMERS_MANAGER_VERSION );

	// Tooltip CSS/JS (directly with full path — common.php skips token pages).
	wp_enqueue_style( 'pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-tooltips.css', array(), PN_CUSTOMERS_MANAGER_VERSION );
	wp_enqueue_script( 'pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-tooltips.js', array( 'jquery' ), PN_CUSTOMERS_MANAGER_VERSION, true );
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
		'wph-owl',
		'pn-customers-manager-referral',
		'pn-customers-manager-qrcode',
		'pn-customers-manager-referral-qr',
		'pn-customers-manager-referral-bizcard',
		'pn-customers-manager-commercial',
		'pn-customers-manager-email-campaigns',
	);

	if ( ! $is_admin ) {
		// Non-admin: also remove popups, tooltips, budget CSS, and material icons (not needed for public view).
		$styles_to_remove[]  = 'pn-customers-manager-popups';
		$styles_to_remove[]  = 'pn-customers-manager-tooltips';
		$styles_to_remove[]  = 'pn-customers-manager-budget';
		$styles_to_remove[]  = 'wph-material-icons-outlined';
		$scripts_to_remove[] = 'pn-customers-manager-popups';
		$scripts_to_remove[] = 'pn-customers-manager-tooltips';
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
		'pn-customers-manager-budget',
		'pn-customers-manager-tooltips',
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
		'pn-cm-chartjs',
		'pn-customers-manager-tooltips',
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
	<?php /* translators: %s: invoice reference number */ ?>
	<title><?php echo esc_html( sprintf( __( 'Invoice %s', 'pn-customers-manager' ), $invoice_number ) ); ?></title>
	<?php
	// Open Graph meta tags.
	/* translators: %s: invoice reference number */
	$pn_cm_og_title = sprintf( __( 'Invoice %s', 'pn-customers-manager' ), $invoice_number );
	if ( ! empty( $company_name ) ) {
		$pn_cm_og_title .= ' — ' . $company_name;
	}
	$pn_cm_og_description = '';
	if ( ! empty( $org_name ) ) {
		$pn_cm_og_description .= $org_name . ' · ';
	}
	$pn_cm_og_description .= $format_currency( $invoice_total );
	?>
	<meta property="og:title" content="<?php echo esc_attr( $pn_cm_og_title ); ?>" />
	<meta property="og:description" content="<?php echo esc_attr( $pn_cm_og_description ); ?>" />
	<meta property="og:type" content="website" />
	<?php if ( ! empty( $company_logo ) ) : ?>
		<meta property="og:image" content="<?php echo esc_url( $company_logo ); ?>" />
	<?php endif; ?>
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
		<?php echo wp_kses_post( PN_CUSTOMERS_MANAGER_Post_Type_Budget::render_admin_nav( 'invoices', $invoice_id ) ); ?>

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
				<?php /* translators: %s: invoice reference number */ ?>
				<h1><?php echo esc_html( sprintf( __( 'Invoice %s', 'pn-customers-manager' ), $invoice_number ) ); ?></h1>
				<?php if ( $is_admin ) : ?>
					<div class="pn-cm-invoice-status-wrapper">
						<span class="pn-cm-invoice-status pn-cm-invoice-status-<?php echo esc_attr( $invoice_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
						<select class="pn-cm-invoice-status-select" data-current="<?php echo esc_attr( $invoice_status ); ?>">
							<?php foreach ( $status_labels as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $invoice_status, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php else : ?>
					<span class="pn-cm-invoice-status pn-cm-invoice-status-<?php echo esc_attr( $invoice_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
				<?php endif; ?>
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
				<p><strong><?php esc_html_e( 'Invoice date:', 'pn-customers-manager' ); ?></strong> <?php echo esc_html( ! empty( $invoice_date ) ? date_i18n( get_option( 'date_format' ), strtotime( $invoice_date ) ) : '' ); ?></p>
				<p><strong><?php esc_html_e( 'Due date:', 'pn-customers-manager' ); ?></strong> <?php echo esc_html( ! empty( $invoice_due_date ) ? date_i18n( get_option( 'date_format' ), strtotime( $invoice_due_date ) ) : '' ); ?></p>
			</div>
		</div>

		<!-- Client notes -->
		<?php if ( ! empty( $invoice_client_notes ) ) : ?>
			<div class="pn-cm-invoice-client-notes">
				<?php echo wp_kses_post( $invoice_client_notes ); ?>
			</div>
		<?php endif; ?>

		<!-- Items -->
		<div class="pn-cm-invoice-items-section" data-invoice-id="<?php echo esc_attr( $invoice_id ); ?>">
			<?php if ( $is_admin ) : ?>
				<input type="hidden" id="pn_cm_invoice_tax_rate" value="<?php echo esc_attr( $invoice_tax_rate ); ?>" />
				<input type="hidden" id="pn_cm_invoice_discount_rate" value="<?php echo esc_attr( $invoice_discount_rate ); ?>" />
				<input type="hidden" id="pn_cm_invoice_irpf_rate" value="<?php echo esc_attr( $invoice_irpf_rate ); ?>" />
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
				<div class="pn-cm-invoice-admin-buttons pn-cm-budget-admin-buttons">
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-customers-manager-invoice-add-phase" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Add phase', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">segment</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-customers-manager-invoice-add-item" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Add item', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">add</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-invoice-export-items" data-invoice-id="<?php echo esc_attr( $invoice_id ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Export items', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">download</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-invoice-import-items" data-invoice-id="<?php echo esc_attr( $invoice_id ); ?>" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Import items', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">upload</i>
					</a>
					<span class="pn-cm-budget-admin-buttons-separator"></span>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-invoice-bulk-select-toggle" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Select items', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">checklist</i>
					</a>
					<a href="#" class="pn-cm-budget-admin-icon-btn pn-customers-manager-tooltip pn-cm-invoice-bulk-delete" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Delete selected', 'pn-customers-manager' ); ?>">
						<i class="material-icons-outlined">delete_sweep</i>
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
					<?php /* translators: %s: discount percentage (e.g. "10.00") */ ?>
					<span><?php echo esc_html( sprintf( __( 'Discount (%s%%)', 'pn-customers-manager' ), number_format( $invoice_discount_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-invoice-discount">-<?php echo esc_html( $format_currency( $invoice_discount_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $invoice_tax_rate > 0 ) : ?>
				<div class="pn-cm-invoice-totals-row">
					<?php /* translators: %s: tax percentage (e.g. "21.00") */ ?>
					<span><?php echo esc_html( sprintf( __( 'Tax (%s%%)', 'pn-customers-manager' ), number_format( $invoice_tax_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-invoice-tax"><?php echo esc_html( $format_currency( $invoice_tax_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $invoice_irpf_rate > 0 ) : ?>
				<div class="pn-cm-invoice-totals-row pn-cm-invoice-totals-irpf">
					<?php /* translators: %s: IRPF withholding tax percentage (e.g. "15.00") */ ?>
					<span><?php echo esc_html( sprintf( __( 'IRPF (%s%%)', 'pn-customers-manager' ), number_format( $invoice_irpf_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-invoice-irpf">-<?php echo esc_html( $format_currency( $invoice_irpf_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<div class="pn-cm-invoice-totals-row pn-cm-invoice-totals-total">
				<span><?php esc_html_e( 'Total', 'pn-customers-manager' ); ?></span>
				<span id="pn-cm-invoice-total"><?php echo esc_html( $format_currency( $invoice_total ) ); ?></span>
			</div>
		</div>

		<!-- Footer image -->
		<?php if ( ! empty( $footer_image ) ) : ?>
			<div class="pn-cm-invoice-footer-image">
				<img src="<?php echo esc_url( $footer_image ); ?>" alt="" />
			</div>
		<?php endif; ?>

		<!-- Payments -->
		<?php
		$invoice_payments = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_payments( $invoice_id, 'invoice' );

		$payment_methods_labels = array(
			'cash'          => __( 'Cash', 'pn-customers-manager' ),
			'bank_transfer' => __( 'Bank transfer', 'pn-customers-manager' ),
			'card'          => __( 'Card', 'pn-customers-manager' ),
			'paypal'        => __( 'PayPal', 'pn-customers-manager' ),
			'bizum'         => __( 'Bizum', 'pn-customers-manager' ),
			'other'         => __( 'Other', 'pn-customers-manager' ),
		);
		$payment_categories  = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_payment_category_labels();
		$payment_type_labels = array(
			'income'  => __( 'Income', 'pn-customers-manager' ),
			'expense' => __( 'Expense', 'pn-customers-manager' ),
		);

		$visible_payments      = array();
		$invoice_total_income  = 0;
		$invoice_total_expense = 0;
		foreach ( $invoice_payments as $bp ) {
			$bp_type    = isset( $bp['type'] ) ? $bp['type'] : 'income';
			$bp_visible = isset( $bp['visible'] ) ? intval( $bp['visible'] ) : 1;
			if ( 'expense' === $bp_type ) {
				$invoice_total_expense += floatval( $bp['amount'] );
			} else {
				$invoice_total_income += floatval( $bp['amount'] );
			}
			if ( $bp_visible ) {
				$visible_payments[] = $bp;
			}
		}
		$invoice_remaining = $invoice_total - $invoice_total_income;

		$show_payments = $is_admin || ! empty( $visible_payments );
		if ( $show_payments ) :
			$expense_by_category = array();
			foreach ( $invoice_payments as $bp_chart ) {
				$bpc_type = isset( $bp_chart['type'] ) ? $bp_chart['type'] : 'income';
				if ( 'expense' === $bpc_type ) {
					$bpc_cat = isset( $bp_chart['category'] ) ? $bp_chart['category'] : 'other';
					if ( ! isset( $expense_by_category[ $bpc_cat ] ) ) {
						$expense_by_category[ $bpc_cat ] = 0;
					}
					$expense_by_category[ $bpc_cat ] += floatval( $bp_chart['amount'] );
				}
			}
		?>
		<div class="pn-cm-invoice-payments pn-cm-payments-collapsed" id="pn-cm-invoice-payments" data-post-id="<?php echo esc_attr( $invoice_id ); ?>"
			 data-total-income="<?php echo esc_attr( $invoice_total_income ); ?>"
			 data-total-expense="<?php echo esc_attr( $invoice_total_expense ); ?>"
			 data-remaining="<?php echo esc_attr( $invoice_remaining ); ?>"
			 data-expense-categories="<?php echo esc_attr( wp_json_encode( $expense_by_category ) ); ?>"
			 data-category-labels="<?php echo esc_attr( wp_json_encode( $payment_categories ) ); ?>">
			<div class="pn-cm-payments-header">
				<h3><i class="material-icons-outlined pn-cm-payments-toggle">expand_more</i><?php esc_html_e( 'Payments', 'pn-customers-manager' ); ?></h3>
			</div>
			<div class="pn-cm-payments-body">

			<?php if ( $is_admin ) : ?>
				<?php if ( ! empty( $invoice_payments ) ) : ?>
					<div class="pn-cm-invoice-payments-list pn-cm-budget-payments-admin-table">
						<table class="pn-cm-payments-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date', 'pn-customers-manager' ); ?></th>
									<th><?php esc_html_e( 'Concept', 'pn-customers-manager' ); ?></th>
									<th><?php esc_html_e( 'Type', 'pn-customers-manager' ); ?></th>
									<th><?php esc_html_e( 'Category', 'pn-customers-manager' ); ?></th>
									<th><?php esc_html_e( 'Recipient', 'pn-customers-manager' ); ?></th>
									<th><?php esc_html_e( 'Amount', 'pn-customers-manager' ); ?></th>
									<th><?php esc_html_e( 'Method', 'pn-customers-manager' ); ?></th>
									<th><?php esc_html_e( 'Notes', 'pn-customers-manager' ); ?></th>
									<th title="<?php esc_attr_e( 'Visible to client', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined pn-cm-payment-th-icon">visibility</i></th>
									<th></th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $invoice_payments as $bp ) :
									$bp_type    = isset( $bp['type'] ) ? $bp['type'] : 'income';
									$bp_cat     = isset( $bp['category'] ) ? $bp['category'] : 'client_payment';
									$bp_visible = isset( $bp['visible'] ) ? intval( $bp['visible'] ) : 1;
								?>
									<tr class="pn-cm-payment-row-<?php echo esc_attr( $bp_type ); ?>"
										data-payment-id="<?php echo esc_attr( $bp['id'] ); ?>"
										data-date="<?php echo esc_attr( $bp['date'] ); ?>"
										data-concept="<?php echo esc_attr( isset( $bp['concept'] ) ? $bp['concept'] : '' ); ?>"
										data-type="<?php echo esc_attr( $bp_type ); ?>"
										data-category="<?php echo esc_attr( $bp_cat ); ?>"
										data-recipient="<?php echo esc_attr( isset( $bp['recipient'] ) ? $bp['recipient'] : '' ); ?>"
										data-amount="<?php echo esc_attr( floatval( $bp['amount'] ) ); ?>"
										data-method="<?php echo esc_attr( $bp['method'] ); ?>"
										data-notes="<?php echo esc_attr( $bp['notes'] ); ?>"
										data-visible="<?php echo esc_attr( $bp_visible ); ?>">
										<td data-label="<?php esc_attr_e( 'Date', 'pn-customers-manager' ); ?>"><?php echo esc_html( $bp['date'] ); ?></td>
										<td data-label="<?php esc_attr_e( 'Concept', 'pn-customers-manager' ); ?>"><?php echo esc_html( isset( $bp['concept'] ) ? $bp['concept'] : '' ); ?></td>
										<td data-label="<?php esc_attr_e( 'Type', 'pn-customers-manager' ); ?>"><span class="pn-cm-payment-type-badge pn-cm-payment-type-<?php echo esc_attr( $bp_type ); ?>"><?php echo esc_html( isset( $payment_type_labels[ $bp_type ] ) ? $payment_type_labels[ $bp_type ] : $bp_type ); ?></span></td>
										<td data-label="<?php esc_attr_e( 'Category', 'pn-customers-manager' ); ?>"><?php echo esc_html( isset( $payment_categories[ $bp_cat ] ) ? $payment_categories[ $bp_cat ] : $bp_cat ); ?></td>
										<td data-label="<?php esc_attr_e( 'Recipient', 'pn-customers-manager' ); ?>"><?php echo esc_html( isset( $bp['recipient'] ) ? $bp['recipient'] : '' ); ?></td>
										<td data-label="<?php esc_attr_e( 'Amount', 'pn-customers-manager' ); ?>"><?php echo esc_html( $format_currency( floatval( $bp['amount'] ) ) ); ?></td>
										<td data-label="<?php esc_attr_e( 'Method', 'pn-customers-manager' ); ?>"><?php echo esc_html( isset( $payment_methods_labels[ $bp['method'] ] ) ? $payment_methods_labels[ $bp['method'] ] : $bp['method'] ); ?></td>
										<td data-label="<?php esc_attr_e( 'Notes', 'pn-customers-manager' ); ?>"><?php echo esc_html( $bp['notes'] ); ?></td>
										<td data-label="<?php esc_attr_e( 'Visible', 'pn-customers-manager' ); ?>" class="pn-cm-payment-td-actions">
											<a href="#" class="pn-cm-payment-toggle-visibility pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Toggle client visibility', 'pn-customers-manager' ); ?>" data-payment-id="<?php echo esc_attr( $bp['id'] ); ?>">
												<i class="material-icons-outlined <?php echo $bp_visible ? 'pn-cm-payment-state-visible' : 'pn-cm-payment-state-hidden'; ?>"><?php echo $bp_visible ? 'visibility' : 'visibility_off'; ?></i>
											</a>
										</td>
										<td class="pn-cm-payment-td-actions">
											<a href="#" class="pn-cm-payment-edit pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>" data-payment-id="<?php echo esc_attr( $bp['id'] ); ?>">
												<i class="material-icons-outlined">edit</i>
											</a>
										</td>
										<td class="pn-cm-payment-td-actions">
											<a href="#" class="pn-cm-invoice-payment-remove pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>" data-payment-id="<?php echo esc_attr( $bp['id'] ); ?>">
												<i class="material-icons-outlined">delete</i>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<?php $invoice_net_result = $invoice_total_income - $invoice_total_expense; ?>
				<div class="pn-cm-invoice-payments-summary pn-cm-budget-payments-summary">
					<span><?php esc_html_e( 'Total income', 'pn-customers-manager' ); ?>: <strong class="pn-cm-payment-summary-income"><?php echo esc_html( $format_currency( $invoice_total_income ) ); ?></strong></span>
					<span><?php esc_html_e( 'Total expenses', 'pn-customers-manager' ); ?>: <strong class="pn-cm-payment-summary-expense"><?php echo esc_html( $format_currency( $invoice_total_expense ) ); ?></strong></span>
					<span><?php esc_html_e( 'Result', 'pn-customers-manager' ); ?>: <strong class="<?php echo $invoice_net_result >= 0 ? 'pn-cm-payment-summary-income' : 'pn-cm-payment-summary-expense'; ?>"><?php echo esc_html( $format_currency( $invoice_net_result ) ); ?></strong></span>
					<span><?php esc_html_e( 'Remaining', 'pn-customers-manager' ); ?>: <strong><?php echo esc_html( $format_currency( $invoice_remaining ) ); ?></strong></span>
				</div>

				<div class="pn-cm-payments-charts">
					<div class="pn-cm-payments-chart-box">
						<canvas class="pn-cm-payments-chart-balance" width="180" height="180"></canvas>
					</div>
					<div class="pn-cm-payments-chart-box">
						<canvas class="pn-cm-payments-chart-categories" width="180" height="180"></canvas>
					</div>
				</div>

				<div class="pn-cm-invoice-payments-add-form pn-cm-budget-payments-add-form">
					<input type="date" class="pn-cm-payment-date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
					<input type="text" class="pn-cm-payment-concept" placeholder="<?php esc_attr_e( 'Concept', 'pn-customers-manager' ); ?>" />
					<select class="pn-cm-payment-type">
						<option value="income"><?php esc_html_e( 'Income', 'pn-customers-manager' ); ?></option>
						<option value="expense"><?php esc_html_e( 'Expense', 'pn-customers-manager' ); ?></option>
					</select>
					<select class="pn-cm-payment-category">
						<?php foreach ( $payment_categories as $pk => $pl ) : ?>
							<option value="<?php echo esc_attr( $pk ); ?>"><?php echo esc_html( $pl ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" class="pn-cm-payment-recipient" placeholder="<?php esc_attr_e( 'Recipient', 'pn-customers-manager' ); ?>" />
					<input type="number" class="pn-cm-payment-amount" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Amount', 'pn-customers-manager' ); ?>" />
					<select class="pn-cm-payment-method">
						<?php foreach ( $payment_methods_labels as $mk => $ml ) : ?>
							<option value="<?php echo esc_attr( $mk ); ?>"><?php echo esc_html( $ml ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" class="pn-cm-payment-notes" placeholder="<?php esc_attr_e( 'Notes', 'pn-customers-manager' ); ?>" />
					<label class="pn-cm-payment-visible-label">
						<label class="pn-cm-payment-switch"><input type="checkbox" class="pn-cm-payment-visible" /><span class="pn-cm-payment-switch-slider"></span></label>
						<?php esc_html_e( 'Visible', 'pn-customers-manager' ); ?>
					</label>
					<button type="button" class="pn-cm-invoice-payment-add-btn pn-cm-budget-payment-add-btn"><?php esc_html_e( 'Add', 'pn-customers-manager' ); ?></button>
				</div>

			<?php else : ?>
				<?php if ( ! empty( $visible_payments ) ) : ?>
					<div class="pn-cm-invoice-payments-list">
						<?php foreach ( $visible_payments as $bp ) : ?>
							<div class="pn-cm-invoice-payments-row pn-cm-budget-payments-row">
								<span class="pn-cm-invoice-payments-date pn-cm-budget-payments-date"><?php echo esc_html( $bp['date'] ); ?></span>
								<?php if ( ! empty( $bp['concept'] ) ) : ?>
									<span class="pn-cm-invoice-payments-concept pn-cm-budget-payments-concept"><?php echo esc_html( $bp['concept'] ); ?></span>
								<?php endif; ?>
								<span class="pn-cm-invoice-payments-amount pn-cm-budget-payments-amount"><?php echo esc_html( $format_currency( floatval( $bp['amount'] ) ) ); ?></span>
								<span class="pn-cm-invoice-payments-method pn-cm-budget-payments-method"><?php echo esc_html( isset( $payment_methods_labels[ $bp['method'] ] ) ? $payment_methods_labels[ $bp['method'] ] : $bp['method'] ); ?></span>
								<?php if ( ! empty( $bp['notes'] ) ) : ?>
									<span class="pn-cm-invoice-payments-notes pn-cm-budget-payments-notes"><?php echo esc_html( $bp['notes'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php
				$client_total_paid = 0;
				foreach ( $visible_payments as $vp ) {
					$vp_type = isset( $vp['type'] ) ? $vp['type'] : 'income';
					if ( 'income' === $vp_type ) {
						$client_total_paid += floatval( $vp['amount'] );
					}
				}
				$client_remaining = $invoice_total - $client_total_paid;
				?>
				<div class="pn-cm-invoice-payments-summary pn-cm-budget-payments-summary">
					<span><?php esc_html_e( 'Total paid', 'pn-customers-manager' ); ?>: <strong><?php echo esc_html( $format_currency( $client_total_paid ) ); ?></strong></span>
					<span><?php esc_html_e( 'Remaining', 'pn-customers-manager' ); ?>: <strong><?php echo esc_html( $format_currency( $client_remaining ) ); ?></strong></span>
				</div>
			<?php endif; ?>
			</div><!-- .pn-cm-payments-body -->
		</div>
		<?php endif; ?>

		<!-- Deliverables -->
		<?php
		$invoice_files = PN_CUSTOMERS_MANAGER_Post_Type_Invoice::get_invoice_files( $invoice_id );
		if ( ! empty( $invoice_files ) && 'draft' !== $invoice_status ) :
		?>
			<div class="pn-cm-invoice-files-section">
				<h3><i class="material-icons-outlined" style="vertical-align: middle; margin-right: 6px;">folder_zip</i><?php esc_html_e( 'Deliverables', 'pn-customers-manager' ); ?></h3>
				<?php foreach ( $invoice_files as $file ) : ?>
					<div class="pn-cm-invoice-file-row" data-file-id="<?php echo esc_attr( $file['id'] ); ?>">
						<span class="pn-cm-invoice-file-icon"><i class="material-icons-outlined">description</i></span>
						<span class="pn-cm-invoice-file-name"><?php echo esc_html( $file['filename'] ); ?></span>
						<span class="pn-cm-invoice-file-size"><?php echo esc_html( size_format( $file['size'] ) ); ?></span>
						<a href="#" class="pn-cm-invoice-download-file pn-cm-invoice-btn pn-cm-invoice-btn-outline" data-file-id="<?php echo esc_attr( $file['id'] ); ?>">
							<i class="material-icons-outlined" style="font-size: 16px; vertical-align: middle;">download</i>
							<?php esc_html_e( 'Download', 'pn-customers-manager' ); ?>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

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

		<?php if ( $invoice_type !== 'expense' ) : ?>
		<!-- Payment details -->
		<?php
		$payment_details = get_option( 'pn_customers_manager_invoice_payment_details', '' );
		if ( ! empty( $payment_details ) ) :
		?>
			<div class="pn-cm-invoice-payment-details">
				<h3><?php esc_html_e( 'Payment details', 'pn-customers-manager' ); ?></h3>
				<div class="pn-cm-invoice-payment-details-content">
					<?php echo wp_kses_post( nl2br( $payment_details ) ); ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Terms -->
		<?php if ( ! empty( $terms ) ) : ?>
			<div class="pn-cm-invoice-terms">
				<div class="pn-cm-invoice-terms-content">
					<?php echo wp_kses_post( $terms ); ?>
				</div>
			</div>
		<?php endif; ?>
		<?php endif; ?>

		<!-- Print / Download button -->
		<div class="pn-cm-invoice-print-btn-wrapper">
			<?php if ( $invoice_type === 'expense' && ! empty( $invoice_files ) ) :
				$first_file = reset( $invoice_files );
			?>
				<a href="#" class="pn-cm-invoice-btn pn-cm-invoice-btn-print pn-cm-invoice-download-file" data-file-id="<?php echo esc_attr( $first_file['id'] ); ?>" style="text-decoration:none;">
					<i class="material-icons-outlined">download</i>
					<?php esc_html_e( 'Download invoice', 'pn-customers-manager' ); ?>
				</a>
			<?php else : ?>
				<button type="button" id="pn-cm-invoice-print" class="pn-cm-invoice-btn pn-cm-invoice-btn-print">
					<i class="material-icons-outlined">picture_as_pdf</i>
					<?php esc_html_e( 'Generate PDF', 'pn-customers-manager' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<!-- Sticky totals bar -->
	<div class="pn-cm-invoice-sticky-totals" id="pn-cm-invoice-sticky-totals">
		<div class="pn-cm-invoice-sticky-inner">
			<div class="pn-cm-invoice-sticky-item pn-cm-invoice-sticky-item-total">
				<span class="pn-cm-invoice-sticky-label"><?php esc_html_e( 'Total', 'pn-customers-manager' ); ?></span>
				<span class="pn-cm-invoice-sticky-value" id="pn-cm-invoice-sticky-total"><?php echo esc_html( $format_currency( $invoice_total ) ); ?></span>
			</div>
		</div>
	</div>

	<?php wp_footer(); ?>
</body>
</html>
