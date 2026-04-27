<?php
/**
 * Public template for single budget view.
 * Accessed via token-based URL (no login required).
 *
 * @package PN_Customers_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get budget data - set by the template_redirect handler via set_query_var().
$budget_id = get_query_var( 'pn_cm_budget_id', 0 );

if ( empty( $budget_id ) ) {
	wp_die( esc_html__( 'Budget not found.', 'pn-customers-manager' ) );
}
$budget_number          = get_post_meta( $budget_id, 'pn_cm_budget_number', true );
$budget_date            = get_post_meta( $budget_id, 'pn_cm_budget_date', true );
$budget_valid_until     = get_post_meta( $budget_id, 'pn_cm_budget_valid_until', true );
$budget_status          = get_post_meta( $budget_id, 'pn_cm_budget_status', true );
$budget_tax_rate        = floatval( get_post_meta( $budget_id, 'pn_cm_budget_tax_rate', true ) );
$budget_discount_rate   = floatval( get_post_meta( $budget_id, 'pn_cm_budget_discount_rate', true ) );
$budget_client_notes    = get_post_meta( $budget_id, 'pn_cm_budget_client_notes', true );
$budget_token           = get_post_meta( $budget_id, 'pn_cm_budget_token', true );
$budget_subtotal        = floatval( get_post_meta( $budget_id, 'pn_cm_budget_subtotal', true ) );
$budget_tax_amount      = floatval( get_post_meta( $budget_id, 'pn_cm_budget_tax_amount', true ) );
$budget_discount_amount = floatval( get_post_meta( $budget_id, 'pn_cm_budget_discount_amount', true ) );
$budget_total           = floatval( get_post_meta( $budget_id, 'pn_cm_budget_total', true ) );

// Organization data.
$org_id        = get_post_meta( $budget_id, 'pn_cm_budget_organization_id', true );
$org_name      = ! empty( $org_id ) ? get_the_title( $org_id ) : '';
$org_email     = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_email', true ) : '';
$org_phone     = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_phone', true ) : '';
$org_address   = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_address', true ) : '';
$org_city      = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_city', true ) : '';
$org_postal    = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_postal_code', true ) : '';
$org_country   = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_country', true ) : '';
$org_fiscal_id = ! empty( $org_id ) ? get_post_meta( $org_id, 'pn_cm_organization_fiscal_id', true ) : '';

// Company data from settings.
$company_name      = get_option( 'pn_customers_manager_budget_company_name', '' );
$company_address   = get_option( 'pn_customers_manager_budget_company_address', '' );
$company_fiscal_id = get_option( 'pn_customers_manager_budget_company_fiscal_id', '' );
$company_logo_raw  = get_option( 'pn_customers_manager_budget_company_logo', '' );
$company_logo      = ( ! empty( $company_logo_raw ) && is_numeric( $company_logo_raw ) )
	? wp_get_attachment_url( intval( $company_logo_raw ) )
	: $company_logo_raw;
$terms             = get_option( 'pn_customers_manager_budget_terms', '' );

// Currency settings.
$currency_symbol   = get_option( 'pn_customers_manager_budget_currency_symbol', '€' );
$currency_position = get_option( 'pn_customers_manager_budget_currency_position', 'after' );

// Admin check for inline editing on public page.
$is_admin = current_user_can( 'manage_options' );

// Hide admin bar on this standalone page (prevents Chrome local-network-access dialog).
show_admin_bar( false );

// Budget items from post meta.
$items = PN_CUSTOMERS_MANAGER_Post_Type_Budget::get_budget_items( $budget_id );

// Detect optional items.
$has_optional = false;
foreach ( $items as $item ) {
	if ( ! empty( $item['is_optional'] ) ) {
		$has_optional = true;
		break;
	}
}

// Status display.
$status_labels = array(
	'draft'    => __( 'Draft', 'pn-customers-manager' ),
	'sent'     => __( 'Sent', 'pn-customers-manager' ),
	'accepted' => __( 'Accepted', 'pn-customers-manager' ),
	'rejected' => __( 'Rejected', 'pn-customers-manager' ),
);
$status_label  = isset( $status_labels[ $budget_status ] ) ? $status_labels[ $budget_status ] : $budget_status;

// Format currency helper.
$format_currency = function ( $amount ) use ( $currency_symbol, $currency_position ) {
	$formatted = number_format( floatval( $amount ), 2, ',', '.' );
	return 'before' === $currency_position ? $currency_symbol . $formatted : $formatted . ' ' . $currency_symbol;
};

// Enqueue styles and scripts.
wp_enqueue_style( 'pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-budget.css', array(), PN_CUSTOMERS_MANAGER_VERSION );
wp_enqueue_style( 'material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined', array(), null );
wp_enqueue_script( 'pn-customers-manager-budget', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-budget.js', array( 'jquery' ), PN_CUSTOMERS_MANAGER_VERSION, true );
wp_localize_script(
	'pn-customers-manager-budget',
	'pnCmBudget',
	array(
		'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
		'nonce'          => wp_create_nonce( 'pn-customers-manager-nonce' ),
		'budgetId'       => $budget_id,
		'budgetToken'    => $budget_token,
		'currencySymbol' => $currency_symbol,
		'currencyPosition' => $currency_position,
		'taxRate'        => $budget_tax_rate,
		'discountRate'   => $budget_discount_rate,
		'isLoggedIn'     => is_user_logged_in(),
		'i18n'           => array(
			'confirmAccept' => __( 'Are you sure you want to accept this budget?', 'pn-customers-manager' ),
			'confirmReject' => __( 'Are you sure you want to reject this budget?', 'pn-customers-manager' ),
			'accepted'      => __( 'Budget accepted successfully.', 'pn-customers-manager' ),
			'rejected'      => __( 'Budget rejected.', 'pn-customers-manager' ),
			'error'         => __( 'An error occurred. Please try again.', 'pn-customers-manager' ),
			'emailRequired' => __( 'Please enter your email address to accept this budget.', 'pn-customers-manager' ),
			'emailInvalid'  => __( 'Please enter a valid email address.', 'pn-customers-manager' ),
		),
	)
);

// Admin inline editing scripts.
if ( $is_admin ) {
	// WP 6.7+ replaced underscore.js with a minimal compat shim missing methods
	// required by wp.media(). Override with full library before scripts are printed.
	add_action( 'wp_enqueue_scripts', function () {
		wp_deregister_script( 'underscore' );
		wp_register_script( 'underscore', PN_CUSTOMERS_MANAGER_URL . 'assets/js/vendor/underscore.min.js', array(), '1.13.7' );
	}, 1 );

	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script(
		'pn-customers-manager-budget-admin',
		PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-budget-admin.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		PN_CUSTOMERS_MANAGER_VERSION,
		true
	);
	wp_localize_script(
		'pn-customers-manager-budget-admin',
		'pnCmBudgetAdmin',
		array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'pn-customers-manager-nonce' ),
			'budgetId'          => $budget_id,
			'currencySymbol'    => $currency_symbol,
			'currencyPosition'  => $currency_position,
			'defaultHourlyRate' => get_option( 'pn_customers_manager_budget_default_hourly_rate', '0' ),
			'hasOptionalItems'  => $has_optional,
			'i18n'              => array(
				'error'         => __( 'An error occurred.', 'pn-customers-manager' ),
				'confirmDelete' => __( 'Are you sure you want to delete this item?', 'pn-customers-manager' ),
				'noDescription' => __( 'Please enter a description.', 'pn-customers-manager' ),
				'newPhase'      => __( 'New phase', 'pn-customers-manager' ),
				'selectImage'   => __( 'Select image', 'pn-customers-manager' ),
				'useImage'      => __( 'Use image', 'pn-customers-manager' ),
			),
		)
	);
}

// ─── Dequeue unnecessary assets loaded by common.php and theme ────────────────
// This standalone page only needs: budget CSS/JS, material-icons, jQuery,
// and for admins: popups, sortable, budget-admin JS.
add_action( 'wp_enqueue_scripts', function () use ( $is_admin ) {
	// Styles to always remove (conflict with budget standalone layout).
	$styles_to_remove = array(
		'pn-customers-manager',              // Main plugin CSS — overrides budget layout
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

	// Remove ALL theme and other plugin styles to prevent layout conflicts.
	// Keep our whitelist + WP core styles needed by wp.media for admins.
	$allowed_styles = array(
		'pn-customers-manager-budget',
		'material-icons',
		'pn-customers-manager-popups',
		'wph-material-icons-outlined',
	);

	if ( $is_admin ) {
		// Admins need WP core styles for wp.media modal.
		$allowed_styles = array_merge( $allowed_styles, array(
			'dashicons', 'media-views', 'imgareaselect', 'buttons',
			'editor-buttons', 'wp-auth-check', 'wp-mediaelement',
			'pn-customers-manager-invoice',
		) );
	}

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
	// Skip script filtering for admins: they need wp.media and other WP core scripts.
	if ( ! $is_admin ) {
		$allowed_scripts = array(
			'jquery',
			'jquery-core',
			'jquery-migrate',
			'pn-customers-manager-budget',
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
	<?php
	$pn_cm_og_title = sprintf( __( 'Budget %s', 'pn-customers-manager' ), $budget_number );
	if ( ! empty( $company_name ) ) {
		$pn_cm_og_title .= ' — ' . $company_name;
	}
	$pn_cm_og_description = '';
	if ( ! empty( $org_name ) ) {
		$pn_cm_og_description .= $org_name . ' · ';
	}
	$pn_cm_og_description .= $format_currency( $budget_total );
	?>
	<title><?php echo esc_html( $pn_cm_og_title ); ?></title>
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
		'identifier'      => $budget_number,
		'description'     => sprintf( 'Budget %s', $budget_number ),
		'totalPaymentDue' => array(
			'@type'    => 'PriceSpecification',
			'price'    => number_format( $budget_total, 2, '.', '' ),
			'priceCurrency' => $pn_cm_currency_code,
		),
		'paymentStatus'   => ( 'accepted' === $budget_status ) ? 'PaymentComplete' : 'PaymentDue',
	);
	if ( ! empty( $budget_date ) ) {
		$pn_cm_schema['paymentDueDate'] = $budget_date;
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
<body class="pn-customers-manager-budget-public">
	<div class="pn-cm-budget-wrapper">
		<!-- Header -->
		<header class="pn-cm-budget-header">
			<div class="pn-cm-budget-header-company">
				<?php if ( ! empty( $company_logo ) ) : ?>
					<img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company_name ); ?>" class="pn-cm-budget-logo">
				<?php endif; ?>
				<div class="pn-cm-budget-company-info">
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
			<div class="pn-cm-budget-header-meta">
				<h1><?php echo esc_html( sprintf( __( 'Budget %s', 'pn-customers-manager' ), $budget_number ) ); ?></h1>
				<span class="pn-cm-budget-status pn-cm-budget-status-<?php echo esc_attr( $budget_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
			</div>
		</header>

		<!-- Budget info row -->
		<div class="pn-cm-budget-info-row">
			<div class="pn-cm-budget-info-block">
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
			<div class="pn-cm-budget-info-block">
				<h3><?php esc_html_e( 'Details', 'pn-customers-manager' ); ?></h3>
				<p><strong><?php esc_html_e( 'Date:', 'pn-customers-manager' ); ?></strong> <?php echo esc_html( $budget_date ); ?></p>
				<p><strong><?php esc_html_e( 'Valid until:', 'pn-customers-manager' ); ?></strong> <?php echo esc_html( $budget_valid_until ); ?></p>
			</div>
		</div>

		<!-- Client notes -->
		<?php if ( ! empty( $budget_client_notes ) ) : ?>
			<div class="pn-cm-budget-client-notes">
				<p><?php echo nl2br( esc_html( $budget_client_notes ) ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Items -->
		<div class="pn-cm-budget-items-section" data-budget-id="<?php echo esc_attr( $budget_id ); ?>">
			<?php if ( $is_admin ) : ?>
				<input type="hidden" id="pn_cm_budget_tax_rate" value="<?php echo esc_attr( $budget_tax_rate ); ?>" />
				<input type="hidden" id="pn_cm_budget_discount_rate" value="<?php echo esc_attr( $budget_discount_rate ); ?>" />
			<?php endif; ?>
			<div class="pn-cm-budget-items-list" id="pn-cm-budget-items-body">
				<?php if ( ! empty( $groups ) ) : ?>
					<?php foreach ( $groups as $group ) : ?>
						<?php if ( null === $group['phase'] ) : ?>
							<?php // Loose items (no phase parent). ?>
							<?php foreach ( $group['items'] as $item ) : ?>
								<?php if ( 'image' === $item['item_type'] ) { continue; } ?>
								<div class="pn-cm-budget-item-row <?php echo ! empty( $item['is_optional'] ) && empty( $item['is_selected'] ) ? 'pn-cm-budget-item-deselected' : ''; ?>"
									data-item-id="<?php echo esc_attr( $item['id'] ); ?>"
									data-item-type="<?php echo esc_attr( $item['item_type'] ); ?>"
									data-item-description="<?php echo esc_attr( $item['description'] ); ?>"
									data-item-quantity="<?php echo esc_attr( number_format( floatval( $item['quantity'] ), 2, '.', '' ) ); ?>"
									data-item-unit-price="<?php echo esc_attr( number_format( floatval( $item['unit_price'] ), 2, '.', '' ) ); ?>"
									data-item-optional="<?php echo esc_attr( $item['is_optional'] ); ?>"
									data-item-total="<?php echo esc_attr( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ); ?>">
									<?php if ( $is_admin ) : ?>
										<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'pn-customers-manager' ); ?>">drag_indicator</i></span>
									<?php endif; ?>
									<div class="pn-cm-budget-item-content">
										<div class="pn-cm-budget-item-title">
											<span class="pn-cm-budget-col-desc"><?php echo esc_html( $item['description'] ); ?></span>
											<?php if ( $has_optional ) : ?>
												<span class="pn-cm-budget-col-toggle">
													<?php if ( ! empty( $item['is_optional'] ) ) : ?>
														<label class="pn-cm-budget-toggle">
															<input type="checkbox" class="pn-cm-budget-toggle-item" data-item-id="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( $item['is_selected'], 1 ); ?> <?php echo ( ! $is_admin && 'sent' !== $budget_status ) ? 'disabled' : ''; ?>>
															<span class="pn-cm-budget-toggle-slider"></span>
														</label>
													<?php endif; ?>
												</span>
											<?php endif; ?>
											<?php if ( $is_admin ) : ?>
												<span class="pn-cm-budget-col-actions">
													<a href="#" class="pn-cm-budget-row-edit" title="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
													<a href="#" class="pn-cm-budget-row-delete" title="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
												</span>
											<?php endif; ?>
										</div>
										<div class="pn-cm-budget-item-meta">
											<span class="pn-cm-budget-col-type"><i class="material-icons-outlined" title="<?php echo 'hours' === $item['item_type'] ? esc_attr__( 'Hourly rate', 'pn-customers-manager' ) : esc_attr__( 'Fixed price', 'pn-customers-manager' ); ?>"><?php echo 'hours' === $item['item_type'] ? 'schedule' : 'payments'; ?></i></span>
											<span class="pn-cm-budget-col-qty"><?php echo esc_html( number_format( floatval( $item['quantity'] ), 2, ',', '.' ) ); ?></span>
											<span class="pn-cm-budget-col-price"><?php echo esc_html( $format_currency( $item['unit_price'] ) ); ?></span>
											<span class="pn-cm-budget-col-total"><?php echo esc_html( $format_currency( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ) ); ?></span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<?php
							// Phase group.
							$phase_item   = $group['phase'];
							$phase_items  = $group['items'];
							$all_selected = true;
							$phase_total  = 0;
							foreach ( $phase_items as $pi ) {
								if ( 'image' === $pi['item_type'] ) {
									continue;
								}
								if ( empty( $pi['is_selected'] ) ) {
									$all_selected = false;
								} else {
									$phase_total += floatval( $pi['quantity'] ) * floatval( $pi['unit_price'] );
								}
							}
							?>
							<div class="pn-cm-budget-phase-group" data-phase-id="<?php echo esc_attr( $phase_item['id'] ); ?>">
								<div class="pn-cm-budget-item-row pn-cm-budget-item-phase"
									data-item-id="<?php echo esc_attr( $phase_item['id'] ); ?>"
									data-item-type="phase"
									data-item-description="<?php echo esc_attr( $phase_item['description'] ); ?>">
									<?php if ( $is_admin ) : ?>
										<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'pn-customers-manager' ); ?>">drag_indicator</i></span>
									<?php endif; ?>
									<i class="material-icons-outlined pn-cm-budget-phase-toggle" title="<?php esc_attr_e( 'Collapse / Expand', 'pn-customers-manager' ); ?>">expand_more</i>
									<div class="pn-cm-budget-item-content">
										<span class="pn-cm-budget-col-phase"><?php echo esc_html( $phase_item['description'] ); ?></span>
										<span class="pn-cm-budget-phase-total"><?php echo esc_html( $format_currency( $phase_total ) ); ?></span>
										<?php if ( ! empty( $phase_items ) && ( $is_admin || 'sent' === $budget_status ) ) : ?>
											<label class="pn-cm-budget-toggle pn-cm-budget-phase-switch">
												<input type="checkbox" class="pn-cm-budget-phase-switch-input" <?php checked( $all_selected ); ?>>
												<span class="pn-cm-budget-toggle-slider"></span>
											</label>
										<?php endif; ?>
										<?php if ( $is_admin ) : ?>
											<span class="pn-cm-budget-col-actions">
												<a href="#" class="pn-cm-budget-row-edit" title="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
												<a href="#" class="pn-cm-budget-row-delete" title="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
											</span>
										<?php endif; ?>
									</div>
								</div>
								<div class="pn-cm-budget-phase-items">
									<?php foreach ( $phase_items as $item ) : ?>
										<?php if ( 'image' === $item['item_type'] ) { continue; } ?>
										<div class="pn-cm-budget-item-row <?php echo ! empty( $item['is_optional'] ) && empty( $item['is_selected'] ) ? 'pn-cm-budget-item-deselected' : ''; ?>"
											data-item-id="<?php echo esc_attr( $item['id'] ); ?>"
											data-item-type="<?php echo esc_attr( $item['item_type'] ); ?>"
											data-item-description="<?php echo esc_attr( $item['description'] ); ?>"
											data-item-quantity="<?php echo esc_attr( number_format( floatval( $item['quantity'] ), 2, '.', '' ) ); ?>"
											data-item-unit-price="<?php echo esc_attr( number_format( floatval( $item['unit_price'] ), 2, '.', '' ) ); ?>"
											data-item-optional="<?php echo esc_attr( $item['is_optional'] ); ?>"
											data-item-total="<?php echo esc_attr( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ); ?>">
											<?php if ( $is_admin ) : ?>
												<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'pn-customers-manager' ); ?>">drag_indicator</i></span>
											<?php endif; ?>
											<div class="pn-cm-budget-item-content">
												<div class="pn-cm-budget-item-title">
													<span class="pn-cm-budget-col-desc"><?php echo esc_html( $item['description'] ); ?></span>
													<?php if ( $has_optional ) : ?>
														<span class="pn-cm-budget-col-toggle">
															<?php if ( ! empty( $item['is_optional'] ) ) : ?>
																<label class="pn-cm-budget-toggle">
																	<input type="checkbox" class="pn-cm-budget-toggle-item" data-item-id="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( $item['is_selected'], 1 ); ?> <?php echo ( ! $is_admin && 'sent' !== $budget_status ) ? 'disabled' : ''; ?>>
																	<span class="pn-cm-budget-toggle-slider"></span>
																</label>
															<?php endif; ?>
														</span>
													<?php endif; ?>
													<?php if ( $is_admin ) : ?>
														<span class="pn-cm-budget-col-actions">
															<a href="#" class="pn-cm-budget-row-edit" title="<?php esc_attr_e( 'Edit', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">edit</i></a>
															<a href="#" class="pn-cm-budget-row-delete" title="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
														</span>
													<?php endif; ?>
												</div>
												<div class="pn-cm-budget-item-meta">
													<span class="pn-cm-budget-col-type"><i class="material-icons-outlined" title="<?php echo 'hours' === $item['item_type'] ? esc_attr__( 'Hourly rate', 'pn-customers-manager' ) : esc_attr__( 'Fixed price', 'pn-customers-manager' ); ?>"><?php echo 'hours' === $item['item_type'] ? 'schedule' : 'payments'; ?></i></span>
													<span class="pn-cm-budget-col-qty"><?php echo esc_html( number_format( floatval( $item['quantity'] ), 2, ',', '.' ) ); ?></span>
													<span class="pn-cm-budget-col-price"><?php echo esc_html( $format_currency( $item['unit_price'] ) ); ?></span>
													<span class="pn-cm-budget-col-total"><?php echo esc_html( $format_currency( floatval( $item['quantity'] ) * floatval( $item['unit_price'] ) ) ); ?></span>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="pn-cm-budget-no-items">
						<?php esc_html_e( 'No items in this budget.', 'pn-customers-manager' ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Images gallery -->
			<?php
			$gallery_images = [];
			if ( ! empty( $groups ) ) {
				foreach ( $groups as $gallery_group ) {
					foreach ( $gallery_group['items'] as $gi ) {
						if ( 'image' === $gi['item_type'] ) {
							$gallery_images[] = $gi;
						}
					}
				}
			}
			?>
			<?php if ( ! empty( $gallery_images ) || $is_admin ) : ?>
				<div class="pn-cm-budget-images-gallery" id="pn-cm-budget-images-gallery" data-budget-id="<?php echo esc_attr( $budget_id ); ?>">
					<?php foreach ( $gallery_images as $gi ) : ?>
						<div class="pn-cm-budget-item-row pn-cm-budget-item-image pn-cm-budget-gallery-item"
							data-item-id="<?php echo esc_attr( $gi['id'] ); ?>"
							data-item-type="image"
							data-item-description="<?php echo esc_attr( $gi['description'] ); ?>">
							<div class="pn-cm-budget-item-content">
								<img src="<?php echo esc_url( $gi['description'] ); ?>" class="pn-cm-budget-image-preview" alt="" />
								<?php if ( $is_admin ) : ?>
									<span class="pn-cm-budget-col-actions">
										<a href="#" class="pn-cm-budget-row-delete" title="<?php esc_attr_e( 'Delete', 'pn-customers-manager' ); ?>"><i class="material-icons-outlined">delete</i></a>
									</span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Footer image -->
			<?php
			$footer_image_id = get_post_meta( $budget_id, 'pn_cm_budget_footer_image', true );
			if ( ! empty( $footer_image_id ) && is_numeric( $footer_image_id ) ) :
				$footer_image_url = wp_get_attachment_url( intval( $footer_image_id ) );
				if ( $footer_image_url ) :
					?>
					<div class="pn-cm-budget-footer-image">
						<img src="<?php echo esc_url( $footer_image_url ); ?>" alt="<?php esc_attr_e( 'Budget image', 'pn-customers-manager' ); ?>" />
					</div>
					<?php
				endif;
			endif;
			?>

			<?php if ( $is_admin ) : ?>
				<div class="pn-cm-budget-admin-buttons">
					<a href="#" class="pn-cm-budget-btn pn-cm-budget-btn-secondary pn-customers-manager-budget-add-phase">
						<i class="material-icons-outlined">segment</i>
						<?php esc_html_e( 'Add phase', 'pn-customers-manager' ); ?>
					</a>
					<a href="#" class="pn-cm-budget-btn pn-cm-budget-btn-secondary pn-customers-manager-budget-add-item">
						<i class="material-icons-outlined">add</i>
						<?php esc_html_e( 'Add item', 'pn-customers-manager' ); ?>
					</a>
					<a href="#" class="pn-cm-budget-btn pn-cm-budget-btn-secondary pn-customers-manager-budget-add-image">
						<i class="material-icons-outlined">image</i>
						<?php esc_html_e( 'Add image', 'pn-customers-manager' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

		<!-- Totals -->
		<div class="pn-cm-budget-totals">
			<div class="pn-cm-budget-totals-row">
				<span><?php esc_html_e( 'Subtotal', 'pn-customers-manager' ); ?></span>
				<span id="pn-cm-budget-subtotal"><?php echo esc_html( $format_currency( $budget_subtotal ) ); ?></span>
			</div>
			<?php if ( $budget_discount_rate > 0 ) : ?>
				<div class="pn-cm-budget-totals-row pn-cm-budget-totals-discount">
					<span><?php echo esc_html( sprintf( __( 'Discount (%s%%)', 'pn-customers-manager' ), number_format( $budget_discount_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-budget-discount">-<?php echo esc_html( $format_currency( $budget_discount_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $budget_tax_rate > 0 ) : ?>
				<div class="pn-cm-budget-totals-row">
					<span><?php echo esc_html( sprintf( __( 'Tax (%s%%)', 'pn-customers-manager' ), number_format( $budget_tax_rate, 2, ',', '.' ) ) ); ?></span>
					<span id="pn-cm-budget-tax"><?php echo esc_html( $format_currency( $budget_tax_amount ) ); ?></span>
				</div>
			<?php endif; ?>
			<div class="pn-cm-budget-totals-row pn-cm-budget-totals-total">
				<span><?php esc_html_e( 'Total', 'pn-customers-manager' ); ?></span>
				<span id="pn-cm-budget-total"><?php echo esc_html( $format_currency( $budget_total ) ); ?></span>
			</div>
		</div>

		<!-- Actions (only when status = sent) -->
		<?php if ( 'sent' === $budget_status ) : ?>
			<?php if ( ! is_user_logged_in() ) : ?>
				<div class="pn-cm-budget-email-prompt" id="pn-cm-budget-email-prompt" style="display:none;">
					<label for="pn-cm-budget-acceptor-email"><?php esc_html_e( 'Your email address', 'pn-customers-manager' ); ?></label>
					<div class="pn-cm-budget-email-prompt-row">
						<input type="email" id="pn-cm-budget-acceptor-email" class="pn-cm-budget-email-input" placeholder="email@example.com" required />
						<button type="button" id="pn-cm-budget-confirm-accept" class="pn-cm-budget-btn pn-cm-budget-btn-accept">
							<i class="material-icons-outlined">check</i>
							<?php esc_html_e( 'Confirm', 'pn-customers-manager' ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>
			<div class="pn-cm-budget-actions">
				<button type="button" id="pn-cm-budget-accept" class="pn-cm-budget-btn pn-cm-budget-btn-accept">
					<i class="material-icons-outlined">check</i>
					<?php esc_html_e( 'Accept budget', 'pn-customers-manager' ); ?>
				</button>
			</div>
		<?php elseif ( 'accepted' === $budget_status ) : ?>
			<div class="pn-cm-budget-status-message pn-cm-budget-status-accepted-msg">
				<i class="material-icons-outlined">check</i>
				<?php
				$accepted_at = get_post_meta( $budget_id, 'pn_cm_budget_accepted_at', true );
				echo esc_html(
					sprintf(
						/* translators: %s: date and time when the budget was accepted */
						__( 'This budget was accepted on %s.', 'pn-customers-manager' ),
						$accepted_at ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $accepted_at ) ) : ''
					)
				);
				?>
			</div>
		<?php elseif ( 'rejected' === $budget_status ) : ?>
			<div class="pn-cm-budget-status-message pn-cm-budget-status-rejected-msg">
				<i class="material-icons-outlined">cancel</i>
				<?php
				$rejected_at = get_post_meta( $budget_id, 'pn_cm_budget_rejected_at', true );
				echo esc_html(
					sprintf(
						/* translators: %s: date and time when the budget was rejected */
						__( 'This budget was rejected on %s.', 'pn-customers-manager' ),
						$rejected_at ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $rejected_at ) ) : ''
					)
				);
				?>
			</div>
		<?php endif; ?>


		<!-- Terms -->
		<?php if ( ! empty( $terms ) ) : ?>
			<div class="pn-cm-budget-terms">
				<h3><?php esc_html_e( 'Terms and conditions', 'pn-customers-manager' ); ?></h3>
				<div class="pn-cm-budget-terms-content">
					<?php echo nl2br( esc_html( $terms ) ); ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Print button -->
		<div class="pn-cm-budget-print-btn-wrapper">
			<button type="button" id="pn-cm-budget-print" class="pn-cm-budget-btn pn-cm-budget-btn-print">
				<i class="material-icons-outlined">picture_as_pdf</i>
				<?php esc_html_e( 'Generate PDF', 'pn-customers-manager' ); ?>
			</button>
		</div>
	</div>

	<!-- Sticky totals bar -->
	<div class="pn-cm-budget-sticky-totals" id="pn-cm-budget-sticky-totals">
		<div class="pn-cm-budget-sticky-inner">
			<div class="pn-cm-budget-sticky-item">
				<span class="pn-cm-budget-sticky-label"><?php esc_html_e( 'Total w/o tax', 'pn-customers-manager' ); ?></span>
				<span class="pn-cm-budget-sticky-value" id="pn-cm-budget-sticky-subtotal"><?php echo esc_html( $format_currency( $budget_subtotal ) ); ?></span>
			</div>
			<div class="pn-cm-budget-sticky-item pn-cm-budget-sticky-item-total">
				<span class="pn-cm-budget-sticky-label"><?php esc_html_e( 'Total', 'pn-customers-manager' ); ?></span>
				<span class="pn-cm-budget-sticky-value" id="pn-cm-budget-sticky-total"><?php echo esc_html( $format_currency( $budget_total ) ); ?></span>
			</div>
		</div>
	</div>

	<?php wp_footer(); ?>
</body>
</html>
