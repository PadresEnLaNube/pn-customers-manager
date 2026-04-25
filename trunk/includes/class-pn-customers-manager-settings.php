<?php
/**
 * Settings manager.
 *
 * This class defines plugin settings, both in dashboard or in front-end.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Settings {
  private static $managed_pages = [
    'pn_customers_manager_commercial_crm_page' => [
      'shortcode' => 'pn-customers-manager-organization-list',
      'block'     => 'pn-customers-manager/organization-list',
      'label'     => 'Organizations',
    ],
    'pn_customers_manager_page_budget_list' => [
      'shortcode' => 'pn-customers-manager-budget-list',
      'block'     => null,
      'label'     => 'Budgets',
    ],
    'pn_customers_manager_page_invoice_list' => [
      'shortcode' => 'pn-customers-manager-invoice-list',
      'block'     => null,
      'label'     => 'Invoices',
    ],
  ];

  public static function pn_customers_manager_get_managed_pages() {
    return self::$managed_pages;
  }

  public function pn_customers_manager_get_options() {
    $pn_customers_manager_options = [];

    // Pages section
    $pn_customers_manager_options['pn_customers_manager_commercial_section_start'] = [
      'id' => 'pn_customers_manager_commercial_section_start',
      'section' => 'start',
      'label' => __('Pages', 'pn-customers-manager'),
      'description' => __('Page assignments for the CRM.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_commercial_crm_page'] = [
      'id' => 'pn_customers_manager_commercial_crm_page',
      'input' => 'page_manager',
      'label' => __('Organizations', 'pn-customers-manager'),
      'description' => __('Page that approved commercial agents will access to manage organizations.', 'pn-customers-manager'),
      'shortcode' => 'pn-customers-manager-organization-list',
      'page_option' => 'pn_customers_manager_commercial_crm_page',
    ];

    $pn_customers_manager_options['pn_customers_manager_page_budget_list'] = [
      'id' => 'pn_customers_manager_page_budget_list',
      'input' => 'page_manager',
      'label' => __('Budgets', 'pn-customers-manager'),
      'shortcode' => 'pn-customers-manager-budget-list',
      'page_option' => 'pn_customers_manager_page_budget_list',
    ];

    $pn_customers_manager_options['pn_customers_manager_page_invoice_list'] = [
      'id' => 'pn_customers_manager_page_invoice_list',
      'input' => 'page_manager',
      'label' => __('Invoices', 'pn-customers-manager'),
      'shortcode' => 'pn-customers-manager-invoice-list',
      'page_option' => 'pn_customers_manager_page_invoice_list',
    ];

    $pn_customers_manager_options['pn_customers_manager_commercial_section_end'] = [
      'id' => 'pn_customers_manager_commercial_section_end',
      'section' => 'end',
    ];

    // Projections section
    $pn_customers_manager_options['pn_customers_manager_projections_section_start'] = [
      'id' => 'pn_customers_manager_projections_section_start',
      'section' => 'start',
      'label' => __('Projections', 'pn-customers-manager'),
      'description' => __('Configure how often the system collects automatic snapshots of your business metrics. These snapshots build the historical evolution chart that you can compare against manual projections.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_projection_frequency'] = [
      'id' => 'pn_customers_manager_projection_frequency',
      'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
      'input' => 'select',
      'options' => [
        'hourly'     => __('Hourly', 'pn-customers-manager'),
        'twicedaily' => __('Twice daily', 'pn-customers-manager'),
        'daily'      => __('Daily', 'pn-customers-manager'),
        'weekly'     => __('Weekly', 'pn-customers-manager'),
      ],
      'value' => 'daily',
      'label' => __('Snapshot frequency', 'pn-customers-manager'),
      'description' => __('How often the cron collects a new snapshot of all metrics. Changing this value reschedules the cron automatically.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_projections_section_end'] = [
      'id' => 'pn_customers_manager_projections_section_end',
      'section' => 'end',
    ];

    // Email Campaigns section
    $email_campaigns_options = PN_CUSTOMERS_MANAGER_Email_Campaigns::get_settings_section();
    $pn_customers_manager_options = array_merge($pn_customers_manager_options, $email_campaigns_options);

    // Referral section
    $pn_customers_manager_options['pn_customers_manager_referral_section_start'] = [
      'id' => 'pn_customers_manager_referral_section_start',
      'section' => 'start',
      'label' => __('Referrals', 'pn-customers-manager'),
      'description' => __('Referral system and QR code configuration.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_enabled'] = [
      'id' => 'pn_customers_manager_referral_enabled',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'value' => 'on',
      'label' => __('Enable referral system', 'pn-customers-manager'),
      'description' => __('Enables the referral system with QR code so users can invite others. Administrators will always see the referral panel even if this option is disabled.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_show_ranking'] = [
      'id' => 'pn_customers_manager_referral_show_ranking',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Show top referrers ranking', 'pn-customers-manager'),
      'description' => __('Shows the list of top referrers in the referral panel.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_share_text'] = [
      'id' => 'pn_customers_manager_referral_share_text',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Text to share referral link', 'pn-customers-manager'),
      'description' => __('Default text that accompanies the referral link when sharing on social media. The link is automatically added at the end. Users can customize their own text from their panel.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_qr_branding'] = [
      'id' => 'pn_customers_manager_referral_qr_branding',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'image',
      'label' => __('QR code logo', 'pn-customers-manager'),
      'description' => __('Select an image to display in the center of the QR code. Recommended: square, minimum 80x80px.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_reminder_max_sends'] = [
      'id' => 'pn_customers_manager_referral_reminder_max_sends',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '3',
      'label' => __('Maximum number of reminders per referral', 'pn-customers-manager'),
      'description' => __('Maximum number of reminder emails to be sent to each pending referral. Set to 0 to disable reminders.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_reminder_frequency'] = [
      'id' => 'pn_customers_manager_referral_reminder_frequency',
      'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
      'input' => 'select',
      'options' => [
        '1'  => __('Every 1 day', 'pn-customers-manager'),
        '2'  => __('Every 2 days', 'pn-customers-manager'),
        '3'  => __('Every 3 days', 'pn-customers-manager'),
        '5'  => __('Every 5 days', 'pn-customers-manager'),
        '7'  => __('Every 7 days', 'pn-customers-manager'),
        '14' => __('Every 14 days', 'pn-customers-manager'),
        '30' => __('Every 30 days', 'pn-customers-manager'),
      ],
      'value' => '7',
      'label' => __('Reminder frequency', 'pn-customers-manager'),
      'description' => __('Interval of days between each reminder sent to pending referrals.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_bizcard_phrases'] = [
      'id' => 'pn_customers_manager_referral_bizcard_phrases',
      'input' => 'html_multi',
      'label' => __('Business card phrases', 'pn-customers-manager'),
      'description' => __('Predefined phrases that users can select for the back of their business card.', 'pn-customers-manager'),
      'html_multi_fields' => [
        [
          'id'          => 'pn_customers_manager_referral_bizcard_phrase_text',
          'class'       => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
          'input'       => 'input',
          'type'        => 'text',
          'label'       => __('Phrase', 'pn-customers-manager'),
          'placeholder' => __('Write an inspiring phrase...', 'pn-customers-manager'),
        ],
      ],
    ];

    $pn_customers_manager_options['pn_customers_manager_referral_section_end'] = [
      'id' => 'pn_customers_manager_referral_section_end',
      'section' => 'end',
    ];

    // ── Budgets & Invoices section ──
    $pn_customers_manager_options['pn_customers_manager_budget_section_start'] = [
      'id' => 'pn_customers_manager_budget_section_start',
      'section' => 'start',
      'label' => __('Budgets & Invoices', 'pn-customers-manager'),
      'description' => __('Configure default values and company information for budget and invoice generation.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_dashboard_link'] = [
      'id' => 'pn_customers_manager_budget_dashboard_link',
      'input' => 'html',
      'html_content' => '<a href="' . esc_url(admin_url('edit.php?post_type=pn_cm_budget')) . '" target="_blank" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent"><i class="material-icons-outlined pn-customers-manager-vertical-align-middle">open_in_new</i> ' . esc_html__('Open Budgets in Dashboard', 'pn-customers-manager') . '</a>',
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_public_slug'] = [
      'id' => 'pn_customers_manager_budget_public_slug',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'value' => 'budget',
      'label' => __('Public URL slug', 'pn-customers-manager'),
      'description' => __('Slug used in the public budget URL (e.g. yoursite.com/<strong>budget</strong>/token). Save and flush permalinks after changing.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_number_prefix'] = [
      'id' => 'pn_customers_manager_budget_number_prefix',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'value' => 'BUD',
      'label' => __('Budget number prefix', 'pn-customers-manager'),
      'description' => __('Prefix for auto-generated budget numbers (e.g. BUD-00001).', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_next_number'] = [
      'id' => 'pn_customers_manager_budget_next_number',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '1',
      'label' => __('Next budget number', 'pn-customers-manager'),
      'description' => __('The next auto-incremented number to be assigned. This value increases automatically.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_default_hourly_rate'] = [
      'id' => 'pn_customers_manager_budget_default_hourly_rate',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '0',
      'label' => __('Default hourly rate', 'pn-customers-manager'),
      'description' => __('Default unit price when adding hourly items.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_default_tax_rate'] = [
      'id' => 'pn_customers_manager_budget_default_tax_rate',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '21',
      'label' => __('Default tax rate (%)', 'pn-customers-manager'),
      'description' => __('Default tax percentage applied to new budgets.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_currency_symbol'] = [
      'id' => 'pn_customers_manager_budget_currency_symbol',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'value' => '€',
      'label' => __('Currency symbol', 'pn-customers-manager'),
      'description' => __('Currency symbol to display in budgets (e.g. €, $, £).', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_currency_position'] = [
      'id' => 'pn_customers_manager_budget_currency_position',
      'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
      'input' => 'select',
      'options' => [
        'before' => __('Before amount (€100)', 'pn-customers-manager'),
        'after'  => __('After amount (100 €)', 'pn-customers-manager'),
      ],
      'value' => 'after',
      'label' => __('Currency position', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_default_validity_days'] = [
      'id' => 'pn_customers_manager_budget_default_validity_days',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '30',
      'label' => __('Default validity (days)', 'pn-customers-manager'),
      'description' => __('Number of days a budget remains valid from the issue date.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_company_name'] = [
      'id' => 'pn_customers_manager_budget_company_name',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Company name', 'pn-customers-manager'),
      'description' => __('Your company name as it will appear in the budget header.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_company_address'] = [
      'id' => 'pn_customers_manager_budget_company_address',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Company address', 'pn-customers-manager'),
      'description' => __('Full company address shown in the budget header.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_company_fiscal_id'] = [
      'id' => 'pn_customers_manager_budget_company_fiscal_id',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Company fiscal ID', 'pn-customers-manager'),
      'description' => __('Tax identification number (CIF / NIF / VAT / EIN).', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_company_logo'] = [
      'id' => 'pn_customers_manager_budget_company_logo',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'image',
      'label' => __('Company logo', 'pn-customers-manager'),
      'description' => __('Logo displayed in the budget header. Select from the media library.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_terms'] = [
      'id' => 'pn_customers_manager_budget_terms',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Terms and conditions', 'pn-customers-manager'),
      'description' => __('Default terms and conditions shown at the bottom of every budget.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_default_client_notes'] = [
      'id' => 'pn_customers_manager_budget_default_client_notes',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Default client notes', 'pn-customers-manager'),
      'description' => __('Default notes visible to the client, pre-filled when creating a new budget.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_budget_section_end'] = [
      'id' => 'pn_customers_manager_budget_section_end',
      'section' => 'end',
    ];

    // ── Invoices section ──
    $pn_customers_manager_options['pn_customers_manager_invoice_section_start'] = [
      'id' => 'pn_customers_manager_invoice_section_start',
      'section' => 'start',
      'label' => __('Invoice settings', 'pn-customers-manager'),
      'description' => __('Invoice-specific defaults. Company info, currency, and tax rate are shared with budgets above.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_dashboard_link'] = [
      'id' => 'pn_customers_manager_invoice_dashboard_link',
      'input' => 'html',
      'html_content' => '<a href="' . esc_url(admin_url('edit.php?post_type=pn_cm_invoice')) . '" target="_blank" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent"><i class="material-icons-outlined pn-customers-manager-vertical-align-middle">open_in_new</i> ' . esc_html__('Open Invoices in Dashboard', 'pn-customers-manager') . '</a>',
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_public_slug'] = [
      'id' => 'pn_customers_manager_invoice_public_slug',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'value' => 'invoice',
      'label' => __('Public URL slug', 'pn-customers-manager'),
      'description' => __('Slug used in the public invoice URL (e.g. yoursite.com/<strong>invoice</strong>/token). Save and flush permalinks after changing.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_number_prefix'] = [
      'id' => 'pn_customers_manager_invoice_number_prefix',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'value' => 'INV',
      'label' => __('Invoice number prefix', 'pn-customers-manager'),
      'description' => __('Prefix for auto-generated invoice numbers (e.g. INV-00001).', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_next_number'] = [
      'id' => 'pn_customers_manager_invoice_next_number',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '1',
      'label' => __('Next invoice number', 'pn-customers-manager'),
      'description' => __('The next auto-incremented number to be assigned. This value increases automatically.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_default_due_days'] = [
      'id' => 'pn_customers_manager_invoice_default_due_days',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '30',
      'label' => __('Default due days', 'pn-customers-manager'),
      'description' => __('Number of days until the invoice due date, starting from the issue date.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_default_tax_rate'] = [
      'id' => 'pn_customers_manager_invoice_default_tax_rate',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '21',
      'label' => __('Default tax rate (%)', 'pn-customers-manager'),
      'description' => __('Default tax percentage applied to new invoices. Leave empty to use the budget default.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_terms'] = [
      'id' => 'pn_customers_manager_invoice_terms',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Invoice terms and conditions', 'pn-customers-manager'),
      'description' => __('Default terms and conditions shown at the bottom of every invoice. Leave empty to use budget terms.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_default_client_notes'] = [
      'id' => 'pn_customers_manager_invoice_default_client_notes',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Default client notes', 'pn-customers-manager'),
      'description' => __('Default notes visible to the client, pre-filled when creating a new invoice.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_invoice_section_end'] = [
      'id' => 'pn_customers_manager_invoice_section_end',
      'section' => 'end',
    ];

    // ── API Configuration (parent section) ──
    $pn_customers_manager_options['pn_customers_manager_api_section_start'] = [
      'id' => 'pn_customers_manager_api_section_start',
      'section' => 'start',
      'label' => __('API Configuration', 'pn-customers-manager'),
      'description' => __('Configure the keys and credentials of external APIs used by the plugin. Each service has its own subsection with detailed instructions.', 'pn-customers-manager'),
    ];

    // ── OpenAI (nested subsection) ──
    $pn_customers_manager_options['pn_customers_manager_openai_section_start'] = [
      'id' => 'pn_customers_manager_openai_section_start',
      'section' => 'start',
      'label' => __('OpenAI', 'pn-customers-manager'),
      'description' => __('OpenAI API configuration for AI-powered message processing.<br><br><strong>Prerequisites:</strong><br>1. Create an account at <a href="https://platform.openai.com/" target="_blank">OpenAI Platform</a>.<br>2. Add a payment method at <a href="https://platform.openai.com/settings/organization/billing/overview" target="_blank">Billing</a> for available credit.<br>3. Generate an API Key from <a href="https://platform.openai.com/api-keys" target="_blank">API Keys</a>.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_openai_key'] = [
      'id' => 'pn_customers_manager_whatsapp_openai_key',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'password',
      'label' => __('OpenAI API Key', 'pn-customers-manager'),
      'description' => __('<strong>How to get it:</strong><br>1. Go to <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a>.<br>2. Click &laquo;Create new secret key&raquo;.<br>3. Copy the generated key (starts with <code>sk-</code>) and paste it here.<br><strong>Important:</strong> The key is only shown once when created. If you lose it, you will need to generate a new one.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_ai_model'] = [
      'id' => 'pn_customers_manager_whatsapp_ai_model',
      'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
      'input' => 'select',
      'options' => [
        'gpt-4o'      => 'GPT-4o (~$2.50 / $10 per 1M tokens)',
        'gpt-4o-mini' => 'GPT-4o Mini (~$0.15 / $0.60 per 1M tokens)',
        'gpt-4-turbo' => 'GPT-4 Turbo (~$10 / $30 per 1M tokens)',
      ],
      'value' => 'gpt-4o-mini',
      'label' => __('Default AI model', 'pn-customers-manager'),
      'description' => __('Default OpenAI model to use. Price is shown as input/output per million tokens. <code>gpt-4o-mini</code> is the most economical and recommended for most use cases.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_system_prompt'] = [
      'id' => 'pn_customers_manager_whatsapp_system_prompt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Default system prompt', 'pn-customers-manager'),
      'description' => __('Base instructions the AI will receive before each conversation. Define the personality, tone and rules of the assistant here. This prompt can be overridden per node in the Funnel Builder.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_order_redirect_message'] = [
      'id' => 'pn_customers_manager_order_redirect_message',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'textarea',
      'label' => __('Custom order redirect message', 'pn-customers-manager'),
      'description' => __('Message the AI will use when it cannot process an order through chat (e.g. B2B orders, bulk purchases, special shipping requests). Leave empty for a default message redirecting to phone or website contact methods.', 'pn-customers-manager'),
      'placeholder' => __('e.g. For bulk orders or special requests, please call us at +34 600 000 000 or email pedidos@example.com', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_temperature'] = [
      'id' => 'pn_customers_manager_whatsapp_temperature',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'number',
      'value' => '0.7',
      'label' => __('Default temperature', 'pn-customers-manager'),
      'description' => __('Controls the creativity of AI responses. <strong>0</strong> = deterministic and precise. <strong>1</strong> = balanced (recommended). <strong>2</strong> = very creative and varied. Default: <code>0.7</code>.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_openai_test'] = [
      'id' => 'pn_customers_manager_openai_test',
      'input' => 'html',
      'label' => __('Test OpenAI connection', 'pn-customers-manager'),
      'description' => __('Sends a test message to the OpenAI API to verify that the API Key and model work correctly. It will use the model and temperature configured above.', 'pn-customers-manager'),
      'html_content' => '<input type="button" id="pn-cm-openai-test-btn" class="pn-customers-manager-btn pn-customers-manager-btn-mini" value="' . esc_attr__('Test OpenAI', 'pn-customers-manager') . '" /><span id="pn-cm-openai-test-result" class="pn-customers-manager-ml-10"></span>',
    ];

    $pn_customers_manager_options['pn_customers_manager_openai_section_end'] = [
      'id' => 'pn_customers_manager_openai_section_end',
      'section' => 'end',
    ];

    // ── WhatsApp IA (nested subsection) ──
    $pn_customers_manager_options['pn_customers_manager_whatsapp_section_start'] = [
      'id' => 'pn_customers_manager_whatsapp_section_start',
      'section' => 'start',
      'label' => __('WhatsApp AI', 'pn-customers-manager'),
      'description' => __('WhatsApp Business Platform (Meta) integration to automatically receive and respond to messages with AI.<br><br><strong>Prerequisites:</strong><br>1. Create a <a href="https://business.facebook.com/" target="_blank">Meta Business</a> account.<br>2. Create an app at <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> with WhatsApp product.<br>3. Configure a phone number in the WhatsApp section of your app.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_access_token'] = [
      'id' => 'pn_customers_manager_whatsapp_access_token',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'password',
      'label' => __('WhatsApp Access Token', 'pn-customers-manager'),
      'description' => __('<strong>How to get it (temporary token):</strong><br>1. Go to <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> &gt; your app &gt; WhatsApp &gt; API Setup.<br>2. In the &laquo;Temporary access token&raquo; section, click <strong>Generate</strong> and copy the token. <em>Expires in ~24 hours.</em><br><br><strong>Permanent token (recommended for production):</strong><br>1. Go to <a href="https://business.facebook.com/settings/" target="_blank">Meta Business Suite</a> &gt; Settings &gt; Business settings.<br>2. Under <strong>Users &gt; System Users</strong>, create a System User of type <em>Admin</em>.<br>3. Assign the WhatsApp App asset to it.<br>4. Generate a token with the <code>whatsapp_business_messaging</code> permission.<br>5. This token does not expire and does not need renewal.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_phone_number_id'] = [
      'id' => 'pn_customers_manager_whatsapp_phone_number_id',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Phone Number ID', 'pn-customers-manager'),
      'description' => __('<strong>How to get it:</strong><br>1. Go to <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> &gt; your app &gt; WhatsApp &gt; API Setup.<br>2. In the &laquo;From&raquo; section your phone number appears with its <strong>Phone number ID</strong> below.<br>3. Copy the numeric ID (e.g.: <code>123456789012345</code>) and paste it here.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_verify_token'] = [
      'id' => 'pn_customers_manager_whatsapp_verify_token',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Verify Token', 'pn-customers-manager'),
      'description' => __('<strong>How to configure it:</strong><br>1. Create any secret string (e.g.: <code>my_secure_token_2024</code>) and type it here.<br>2. Save the settings.<br>3. When configuring the webhook on Meta (below), you will be asked for a &laquo;Verify Token&raquo;: use exactly the same string.<br>Meta will send a GET request with this token to verify that the webhook is yours.', 'pn-customers-manager'),
    ];

    $webhook_url = rest_url('pn-cm/v1/whatsapp/webhook');
    $pn_customers_manager_options['pn_customers_manager_whatsapp_webhook_url'] = [
      'id' => 'pn_customers_manager_whatsapp_webhook_url',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'value' => $webhook_url,
      'label' => __('Webhook URL', 'pn-customers-manager'),
      'description' => __('<strong>How to configure the webhook on Meta:</strong><br>1. Copy the URL shown above.<br>2. Go to <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> &gt; your app &gt; WhatsApp &gt; Configuration.<br>3. In the &laquo;Webhook&raquo; section, click &laquo;Edit&raquo;.<br>4. Paste the URL in the &laquo;Callback URL&raquo; field.<br>5. In &laquo;Verify Token&raquo; type the same token you configured above.<br>6. Click &laquo;Verify and Save&raquo;.<br>7. In &laquo;Webhook fields&raquo;, subscribe to the <code>messages</code> field.', 'pn-customers-manager'),
      'disabled' => true,
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_test_phone'] = [
      'id' => 'pn_customers_manager_whatsapp_test_phone',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Test phone', 'pn-customers-manager'),
      'description' => __('Phone number to send the test message to. Include the country code without the + sign (e.g.: <code>34612345678</code> for Spain).', 'pn-customers-manager'),
      'placeholder' => '34612345678',
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_test'] = [
      'id' => 'pn_customers_manager_whatsapp_test',
      'input' => 'html',
      'label' => __('Test WhatsApp send', 'pn-customers-manager'),
      'description' => __('Sends a test message to the number above to verify that the Access Token and Phone Number ID are correct. <strong>Save settings before testing.</strong>', 'pn-customers-manager'),
      'html_content' => '<input type="button" id="pn-cm-whatsapp-test-btn" class="pn-customers-manager-btn pn-customers-manager-btn-mini" value="' . esc_attr__('Send test message', 'pn-customers-manager') . '" /><span id="pn-cm-whatsapp-test-result" class="pn-customers-manager-ml-10"></span>',
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_test_receive'] = [
      'id' => 'pn_customers_manager_whatsapp_test_receive',
      'input' => 'html',
      'label' => __('Test message reception', 'pn-customers-manager'),
      'description' => __('Verifies that the webhook is correctly configured and your server can receive WhatsApp messages.<br><strong>Instructions:</strong><br>1. In <em>Meta Developers &gt; Your App &gt; WhatsApp &gt; Configuration</em>, configure the <strong>Webhook URL</strong> with the address shown above and the <strong>Verify Token</strong>.<br>2. In the <strong>Webhook fields</strong> table, enable the <strong>&laquo;messages&raquo;</strong> field. Without this, Meta will not send incoming messages to your server.<br>3. Click &laquo;Listen for messages&raquo;.<br>4. Send a WhatsApp message to your business number.<br>5. If everything is set up correctly, the message will appear here within seconds.', 'pn-customers-manager'),
      'html_content' => '<input type="button" id="pn-cm-whatsapp-receive-btn" class="pn-customers-manager-btn pn-customers-manager-btn-mini" value="' . esc_attr__('Listen for messages', 'pn-customers-manager') . '" /><input type="button" id="pn-cm-whatsapp-receive-stop-btn" class="pn-customers-manager-btn pn-customers-manager-btn-mini" value="' . esc_attr__('Stop', 'pn-customers-manager') . '" style="display:none;margin-left:6px;" /><span id="pn-cm-whatsapp-receive-result" class="pn-customers-manager-ml-10"></span>',
    ];

    $pn_customers_manager_options['pn_customers_manager_whatsapp_section_end'] = [
      'id' => 'pn_customers_manager_whatsapp_section_end',
      'section' => 'end',
    ];

    // ── Instagram AI (nested subsection) ──
    $pn_customers_manager_options['pn_customers_manager_instagram_section_start'] = [
      'id' => 'pn_customers_manager_instagram_section_start',
      'section' => 'start',
      'label' => __('Instagram AI', 'pn-customers-manager'),
      'description' => __('Instagram Messenger API (Meta) integration to automatically receive and respond to DMs with AI.<br><br><strong>Prerequisites:</strong><br>1. Create a <a href="https://business.facebook.com/" target="_blank">Meta Business</a> account.<br>2. Create an app at <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> with Instagram product.<br>3. Configure the Instagram account in your Meta app.<br>4. The OpenAI API Key is shared with the WhatsApp AI section above — configure it there.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_instagram_access_token'] = [
      'id' => 'pn_customers_manager_instagram_access_token',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'password',
      'label' => __('Instagram Access Token', 'pn-customers-manager'),
      'description' => __('<strong>How to get it:</strong><br>1. Go to <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> &gt; your app &gt; Instagram &gt; API Setup.<br>2. Generate an access token with <code>instagram_manage_messages</code> permission.<br><br><strong>Permanent token (recommended):</strong><br>Use a System User token from <a href="https://business.facebook.com/settings/" target="_blank">Meta Business Suite</a> with <code>instagram_manage_messages</code> permission.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_instagram_page_id'] = [
      'id' => 'pn_customers_manager_instagram_page_id',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Instagram Page ID', 'pn-customers-manager'),
      'description' => __('<strong>How to get it:</strong><br>1. Go to <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> &gt; your app &gt; Instagram &gt; API Setup.<br>2. Copy the Instagram-scoped Page ID (numeric ID) and paste it here.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_instagram_verify_token'] = [
      'id' => 'pn_customers_manager_instagram_verify_token',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Verify Token', 'pn-customers-manager'),
      'description' => __('<strong>How to configure it:</strong><br>1. Create any secret string (e.g.: <code>my_ig_token_2024</code>) and type it here.<br>2. Save the settings.<br>3. When configuring the webhook on Meta, use exactly the same string as the &laquo;Verify Token&raquo;.', 'pn-customers-manager'),
    ];

    $ig_webhook_url = rest_url('pn-cm/v1/instagram/webhook');
    $pn_customers_manager_options['pn_customers_manager_instagram_webhook_url'] = [
      'id' => 'pn_customers_manager_instagram_webhook_url',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'value' => $ig_webhook_url,
      'label' => __('Webhook URL', 'pn-customers-manager'),
      'description' => __('<strong>How to configure the webhook on Meta:</strong><br>1. Copy the URL shown above.<br>2. Go to <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a> &gt; your app &gt; Instagram &gt; Webhooks.<br>3. Paste the URL in the &laquo;Callback URL&raquo; field.<br>4. In &laquo;Verify Token&raquo; type the same token you configured above.<br>5. Click &laquo;Verify and Save&raquo;.<br>6. Subscribe to the <code>messages</code> webhook field.', 'pn-customers-manager'),
      'disabled' => true,
    ];

    $pn_customers_manager_options['pn_customers_manager_instagram_test_ig_id'] = [
      'id' => 'pn_customers_manager_instagram_test_ig_id',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Test Instagram User ID', 'pn-customers-manager'),
      'description' => __('Instagram-scoped User ID (IGSID) to send the test message to. You can find it in the webhook payload when someone sends you a DM.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_instagram_test'] = [
      'id' => 'pn_customers_manager_instagram_test',
      'input' => 'html',
      'label' => __('Test Instagram send', 'pn-customers-manager'),
      'description' => __('Sends a test message to the IGSID above to verify that the Access Token and Page ID are correct. <strong>Save settings before testing.</strong>', 'pn-customers-manager'),
      'html_content' => '<input type="button" id="pn-cm-instagram-test-btn" class="pn-customers-manager-btn pn-customers-manager-btn-mini" value="' . esc_attr__('Send test message', 'pn-customers-manager') . '" /><span id="pn-cm-instagram-test-result" class="pn-customers-manager-ml-10"></span>',
    ];

    $pn_customers_manager_options['pn_customers_manager_instagram_test_receive'] = [
      'id' => 'pn_customers_manager_instagram_test_receive',
      'input' => 'html',
      'label' => __('Test message reception', 'pn-customers-manager'),
      'description' => __('Verifies that the webhook is correctly configured and your server can receive Instagram DMs.<br><strong>Instructions:</strong><br>1. Configure the <strong>Webhook URL</strong> and <strong>Verify Token</strong> in your Meta app.<br>2. Subscribe to the <strong>&laquo;messages&raquo;</strong> webhook field.<br>3. Click &laquo;Listen for messages&raquo;.<br>4. Send a DM to your Instagram account.<br>5. If everything is set up correctly, the message will appear here within seconds.', 'pn-customers-manager'),
      'html_content' => '<input type="button" id="pn-cm-instagram-receive-btn" class="pn-customers-manager-btn pn-customers-manager-btn-mini" value="' . esc_attr__('Listen for messages', 'pn-customers-manager') . '" /><input type="button" id="pn-cm-instagram-receive-stop-btn" class="pn-customers-manager-btn pn-customers-manager-btn-mini" value="' . esc_attr__('Stop', 'pn-customers-manager') . '" style="display:none;margin-left:6px;" /><span id="pn-cm-instagram-receive-result" class="pn-customers-manager-ml-10"></span>',
    ];

    $pn_customers_manager_options['pn_customers_manager_instagram_section_end'] = [
      'id' => 'pn_customers_manager_instagram_section_end',
      'section' => 'end',
    ];

    // ── Social Media APIs (nested subsection) ──
    $pn_customers_manager_options['pn_customers_manager_social_media_section_start'] = [
      'id' => 'pn_customers_manager_social_media_section_start',
      'section' => 'start',
      'label' => __('Social Media APIs', 'pn-customers-manager'),
      'description' => __('Configure social media API credentials to collect analytics data for the Projections dashboard (followers, engagement, impressions).<br><br>These APIs are used exclusively for reading public metrics from your own accounts.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_social_ig_token'] = [
      'id' => 'pn_customers_manager_social_ig_token',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'password',
      'label' => __('Instagram Graph API Token', 'pn-customers-manager'),
      'description' => __('Long-lived token with <code>instagram_basic</code> and <code>instagram_manage_insights</code> permissions. Generate it from <a href="https://developers.facebook.com/apps/" target="_blank">Meta for Developers</a>.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_social_ig_account_id'] = [
      'id' => 'pn_customers_manager_social_ig_account_id',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Instagram Business Account ID', 'pn-customers-manager'),
      'description' => __('The numeric Instagram Business or Creator account ID. Found in the Graph API Explorer or your Meta app dashboard.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_social_fb_token'] = [
      'id' => 'pn_customers_manager_social_fb_token',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'password',
      'label' => __('Facebook Page Access Token', 'pn-customers-manager'),
      'description' => __('A Page Access Token with <code>pages_read_engagement</code> and <code>read_insights</code> permissions.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_social_fb_page_id'] = [
      'id' => 'pn_customers_manager_social_fb_page_id',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Facebook Page ID', 'pn-customers-manager'),
      'description' => __('The numeric ID of your Facebook Page. Found in Page Settings > About.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_social_tw_bearer'] = [
      'id' => 'pn_customers_manager_social_tw_bearer',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'password',
      'label' => __('Twitter/X Bearer Token', 'pn-customers-manager'),
      'description' => __('Bearer Token from your Twitter/X Developer App. Generate it at <a href="https://developer.twitter.com/en/portal/dashboard" target="_blank">developer.twitter.com</a>.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_social_tw_account_id'] = [
      'id' => 'pn_customers_manager_social_tw_account_id',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'text',
      'label' => __('Twitter/X Account ID', 'pn-customers-manager'),
      'description' => __('The numeric user ID of your Twitter/X account. You can find it using the <a href="https://tweeterid.com/" target="_blank">TweeterID</a> tool.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_social_media_test'] = [
      'id' => 'pn_customers_manager_social_media_test',
      'input' => 'html',
      'label' => __('Test social media connections', 'pn-customers-manager'),
      'description' => __('Tests the connection to all configured social media APIs. <strong>Save settings before testing.</strong>', 'pn-customers-manager'),
      'html_content' => '<input type="button" id="pn-cm-social-media-test-btn" class="pn-customers-manager-btn pn-customers-manager-btn-mini" value="' . esc_attr__('Test connections', 'pn-customers-manager') . '" /><span id="pn-cm-social-media-test-result" class="pn-customers-manager-ml-10"></span>',
    ];

    $pn_customers_manager_options['pn_customers_manager_social_media_section_end'] = [
      'id' => 'pn_customers_manager_social_media_section_end',
      'section' => 'end',
    ];

    // ── End API Configuration ──
    // ── Akismet (nested subsection) ──
    $akismet_active    = class_exists('Akismet');
    $akismet_has_key   = $akismet_active && method_exists('Akismet', 'get_api_key') && Akismet::get_api_key();
    $akismet_status_html = '';
    if (!$akismet_active) {
      $akismet_status_html = '<span style="color:#b32d2e;">' . esc_html__('Plugin Akismet no instalado o no activo.', 'pn-customers-manager') . '</span>';
    } elseif (!$akismet_has_key) {
      $akismet_status_html = '<span style="color:#b32d2e;">' . esc_html__('Plugin Akismet activo, pero sin clave de API configurada.', 'pn-customers-manager') . '</span>';
    } else {
      $akismet_status_html = '<span style="color:#2e7d32;">' . esc_html__('Plugin Akismet activo y configurado.', 'pn-customers-manager') . '</span>';
    }

    $pn_customers_manager_options['pn_customers_manager_akismet_section_start'] = [
      'id' => 'pn_customers_manager_akismet_section_start',
      'section' => 'start',
      'label' => __('Akismet', 'pn-customers-manager'),
      'description' => __('Integration with the Akismet plugin to automatically detect bots and spam in the contact form.<br><br><strong>Prerequisites:</strong><br>1. Install and activate the <a href="https://wordpress.org/plugins/akismet/" target="_blank">Akismet Anti-Spam</a> plugin.<br>2. Sign up at <a href="https://akismet.com/" target="_blank">akismet.com</a> and configure the API key in Akismet settings.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_akismet_status'] = [
      'id' => 'pn_customers_manager_akismet_status',
      'input' => 'html',
      'label' => __('Akismet status', 'pn-customers-manager'),
      'description' => __('Current status of the Akismet integration on this site.', 'pn-customers-manager'),
      'html_content' => $akismet_status_html,
    ];

    $pn_customers_manager_options['pn_customers_manager_akismet_enabled'] = [
      'id' => 'pn_customers_manager_akismet_enabled',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'value' => 'on',
      'label' => __('Enable Akismet for contact form', 'pn-customers-manager'),
      'description' => __('When enabled, every contact form submission will be checked against the Akismet API. Suspected bots will still be stored but automatically flagged as spam and excluded from notification emails.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_akismet_discard'] = [
      'id' => 'pn_customers_manager_akismet_discard',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Discard obvious spam silently', 'pn-customers-manager'),
      'description' => __('If Akismet returns <code>discard</code> (highest spam confidence), reject the submission without saving it to the database. If this option is disabled, the message is still stored but marked as spam.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_akismet_section_end'] = [
      'id' => 'pn_customers_manager_akismet_section_end',
      'section' => 'end',
    ];

    $pn_customers_manager_options['pn_customers_manager_api_section_end'] = [
      'id' => 'pn_customers_manager_api_section_end',
      'section' => 'end',
    ];

    // Color customization section
    $pn_customers_manager_options['pn_customers_manager_colors_section_start'] = [
      'id' => 'pn_customers_manager_colors_section_start',
      'section' => 'start',
      'label' => __('Colors', 'pn-customers-manager'),
      'description' => __('Customize the colors used throughout the plugin by modifying the CSS root variables.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_color_main'] = [
      'id' => 'pn_customers_manager_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Color', 'pn-customers-manager'),
      'description' => __('Primary color used for text, backgrounds, and borders (default: #0000aa)', 'pn-customers-manager'),
      'value' => '#0000aa',
    ];

    $pn_customers_manager_options['pn_customers_manager_bg_color_main'] = [
      'id' => 'pn_customers_manager_bg_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Background Color', 'pn-customers-manager'),
      'description' => __('Primary background color (default: #0000aa)', 'pn-customers-manager'),
      'value' => '#0000aa',
    ];

    $pn_customers_manager_options['pn_customers_manager_border_color_main'] = [
      'id' => 'pn_customers_manager_border_color_main',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Main Border Color', 'pn-customers-manager'),
      'description' => __('Primary border color (default: #0000aa)', 'pn-customers-manager'),
      'value' => '#0000aa',
    ];

    $pn_customers_manager_options['pn_customers_manager_color_main_alt'] = [
      'id' => 'pn_customers_manager_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Main Color', 'pn-customers-manager'),
      'description' => __('Alternative color for text, backgrounds, and borders (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $pn_customers_manager_options['pn_customers_manager_bg_color_main_alt'] = [
      'id' => 'pn_customers_manager_bg_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Background Color', 'pn-customers-manager'),
      'description' => __('Alternative background color (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $pn_customers_manager_options['pn_customers_manager_border_color_main_alt'] = [
      'id' => 'pn_customers_manager_border_color_main_alt',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Alternative Border Color', 'pn-customers-manager'),
      'description' => __('Alternative border color (default: #232323)', 'pn-customers-manager'),
      'value' => '#232323',
    ];

    $pn_customers_manager_options['pn_customers_manager_color_main_blue'] = [
      'id' => 'pn_customers_manager_color_main_blue',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Blue Color', 'pn-customers-manager'),
      'description' => __('Blue accent color (default: #6e6eff)', 'pn-customers-manager'),
      'value' => '#6e6eff',
    ];

    $pn_customers_manager_options['pn_customers_manager_color_main_grey'] = [
      'id' => 'pn_customers_manager_color_main_grey',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'color',
      'label' => __('Grey Color', 'pn-customers-manager'),
      'description' => __('Grey color for backgrounds (default: #f5f5f5)', 'pn-customers-manager'),
      'value' => '#f5f5f5',
    ];

    $pn_customers_manager_options['pn_customers_manager_colors_section_end'] = [
      'id' => 'pn_customers_manager_colors_section_end',
      'section' => 'end',
    ];

    // System section
    $pn_customers_manager_options['pn_customers_manager_system_section_start'] = [
      'id' => 'pn_customers_manager_system_section_start',
      'section' => 'start',
      'label' => __('System', 'pn-customers-manager'),
      'description' => __('General plugin settings and configuration.', 'pn-customers-manager'),
    ];

    foreach (PN_CUSTOMERS_MANAGER_CPTS as $pn_customers_manager_cpt_key => $pn_customers_manager_cpt_value) {
      $pn_customers_manager_options['pn_customers_manager_' . $pn_customers_manager_cpt_key . '_slug'] = [
        'id' => 'pn_customers_manager_' . $pn_customers_manager_cpt_key . '_slug',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => sprintf(
          /* translators: %s: Post type name */
          __('%s slug', 'pn-customers-manager'),
          $pn_customers_manager_cpt_value
        ),
        'placeholder' => sprintf(
          /* translators: %s: Post type name */
          __('%s slug', 'pn-customers-manager'),
          $pn_customers_manager_cpt_value
        ),
        'description' => sprintf(
          /* translators: %1$s: Post type name, %2$s: Archive URL, %3$s: Archive URL, %4$s: Single post URL */
          __('This option sets the slug of the %1$s archive page, and the %1$s pages. By default they will be:', 'pn-customers-manager'),
          $pn_customers_manager_cpt_value
        ) . '<br><a href="' . esc_url(home_url('/' . $pn_customers_manager_cpt_key . '-slug')) . '" target="_blank">' . esc_url(home_url('/' . $pn_customers_manager_cpt_key . '-slug')) . '</a><br>' . esc_url(home_url('/' . $pn_customers_manager_cpt_key . '-slug/' . $pn_customers_manager_cpt_key)),
      ];
    }

    $pn_customers_manager_options['pn_customers_manager_allow_crm_indexing'] = [
      'id' => 'pn_customers_manager_allow_crm_indexing',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Allow search engine indexing on CRM pages', 'pn-customers-manager'),
      'description' => __('By default, CRM management pages include a noindex tag to prevent search engines from indexing them. Enable this option to remove those tags and allow indexing.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_options_remove'] = [
      'id' => 'pn_customers_manager_options_remove',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'input',
      'type' => 'checkbox',
      'label' => __('Remove plugin options on deactivation', 'pn-customers-manager'),
      'description' => __('If you activate this option the plugin will remove all options on deactivation. Please, be careful. This process cannot be undone.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_system_section_end'] = [
      'id' => 'pn_customers_manager_system_section_end',
      'section' => 'end',
    ];

    // User Roles section
    $pn_customers_manager_options['pn_customers_manager_roles_section_start'] = [
      'id' => 'pn_customers_manager_roles_section_start',
      'section' => 'start',
      'label' => __('User Roles', 'pn-customers-manager'),
      'description' => __('Manage plugin user roles. You can assign or remove roles from registered users.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_role_selector_manager'] = [
      'id' => 'pn_customers_manager_role_selector_manager',
      'input' => 'user_role_selector',
      'label' => __('PN Customers Manager', 'pn-customers-manager'),
      'role' => 'pn_customers_manager_role_manager',
      'role_label' => __('PN Customers Manager', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_role_selector_client'] = [
      'id' => 'pn_customers_manager_role_selector_client',
      'input' => 'user_role_selector',
      'label' => __('Client - PN', 'pn-customers-manager'),
      'role' => 'pn_customers_manager_role_client',
      'role_label' => __('Client - PN', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_role_selector_commercial'] = [
      'id' => 'pn_customers_manager_role_selector_commercial',
      'input' => 'user_role_selector',
      'label' => __('Commercial Agent - PN', 'pn-customers-manager'),
      'role' => 'pn_customers_manager_role_commercial',
      'role_label' => __('Commercial Agent - PN', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_roles_section_end'] = [
      'id' => 'pn_customers_manager_roles_section_end',
      'section' => 'end',
    ];

    $pn_customers_manager_options['pn_customers_manager_nonce'] = [
      'id' => 'pn_customers_manager_nonce',
      'input' => 'input',
      'type' => 'nonce',
    ];

    return $pn_customers_manager_options;
  }

	/**
	 * Administrator menu.
	 *
	 * @since    1.0.0
	 */
	public function pn_customers_manager_admin_menu() {
    // Determine the capability to use for the main menu
    // Use the first available capability, or manage_options as fallback
    $menu_cap = 'manage_options';
    if (current_user_can('edit_pn_cm_funnel')) {
      $menu_cap = 'edit_pn_cm_funnel';
    } elseif (current_user_can('edit_pn_cm_organization')) {
      $menu_cap = 'edit_pn_cm_organization';
    } elseif (current_user_can('pn_cm_manage_crm')) {
      $menu_cap = 'pn_cm_manage_crm';
    }

    // Check if user has any of the required capabilities
    $has_cap = current_user_can('edit_pn_cm_funnel') || current_user_can('edit_pn_cm_organization') || current_user_can('manage_options') || current_user_can('pn_cm_manage_crm');
    
    if (!$has_cap) {
      return;
    }

    add_menu_page(
      esc_html__('PN Customers Manager', 'pn-customers-manager'), 
      esc_html__('PN Customers Manager', 'pn-customers-manager'), 
      $menu_cap, 
      'pn_customers_manager_options', 
      [$this, 'pn_customers_manager_options'], 
      esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/pn-customers-manager-menu-icon.svg'),
    );
		
    add_submenu_page(
      'pn_customers_manager_options',
      esc_html__('Settings', 'pn-customers-manager'), 
      esc_html__('Settings', 'pn-customers-manager'), 
      $menu_cap, 
      'pn_customers_manager_options', 
      [$this, 'pn_customers_manager_options'], 
    );

    // Add Funnels submenu (only if user has the capability)
    if (current_user_can('edit_pn_cm_funnel')) {
      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Funnels', 'pn-customers-manager'),
        esc_html__('Funnels', 'pn-customers-manager'),
        'edit_pn_cm_funnel',
        'edit.php?post_type=pn_cm_funnel'
      );

      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Funnel Builder', 'pn-customers-manager'),
        esc_html__('Funnel Builder', 'pn-customers-manager'),
        'edit_pn_cm_funnel',
        'pn_customers_manager_funnel_builder',
        ['PN_CUSTOMERS_MANAGER_Funnel_Builder', 'render_page']
      );
    }

    // Add Organizations submenu (only if user has the capability)
    if (current_user_can('edit_pn_cm_organization')) {
      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Organizations', 'pn-customers-manager'),
        esc_html__('Organizations', 'pn-customers-manager'),
        'edit_pn_cm_organization',
        'edit.php?post_type=pn_cm_organization'
      );
    }

    // Add Budgets submenu (only if user has the capability)
    if (current_user_can('edit_pn_cm_budget')) {
      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Budgets', 'pn-customers-manager'),
        esc_html__('Budgets', 'pn-customers-manager'),
        'edit_pn_cm_budget',
        'edit.php?post_type=pn_cm_budget'
      );
    }

    // Add Invoices submenu (only if user has the capability)
    if (current_user_can('edit_pn_cm_invoice')) {
      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Invoices', 'pn-customers-manager'),
        esc_html__('Invoices', 'pn-customers-manager'),
        'edit_pn_cm_invoice',
        'edit.php?post_type=pn_cm_invoice'
      );
    }

    // Add Commercial Agents submenu
    if (current_user_can('pn_cm_manage_crm')) {
      $pending_commercial = PN_CUSTOMERS_MANAGER_Commercial::get_pending_count();
      $commercial_badge = $pending_commercial > 0
        ? ' <span class="awaiting-mod pn-customers-manager-menu-badge">' . esc_html($pending_commercial) . '</span>'
        : '';

      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Commercial Agents', 'pn-customers-manager'),
        esc_html__('Commercial Agents', 'pn-customers-manager') . $commercial_badge,
        'pn_cm_manage_crm',
        'pn_customers_manager_commercial_agents',
        ['PN_CUSTOMERS_MANAGER_Commercial', 'render_admin_commercial_agents']
      );
    }

    // WhatsApp AI — hidden page (accessible via direct URL or shortcode, not shown in menu)
    if (current_user_can('pn_cm_manage_crm')) {
      add_submenu_page(
        null,
        esc_html__('WhatsApp AI', 'pn-customers-manager'),
        '',
        'pn_cm_manage_crm',
        'pn_customers_manager_whatsapp_ai',
        ['PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'render_admin_page']
      );
    }

    // Instagram AI — hidden page (accessible via direct URL or shortcode, not shown in menu)
    if (current_user_can('pn_cm_manage_crm')) {
      add_submenu_page(
        null,
        esc_html__('Instagram AI', 'pn-customers-manager'),
        '',
        'pn_cm_manage_crm',
        'pn_customers_manager_instagram_ai',
        ['PN_CUSTOMERS_MANAGER_Instagram_AI', 'render_admin_page']
      );
    }

    // Add Projections submenu
    if (current_user_can('pn_cm_manage_crm')) {
      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Projections', 'pn-customers-manager'),
        esc_html__('Projections', 'pn-customers-manager'),
        'pn_cm_manage_crm',
        'pn_customers_manager_projections',
        ['PN_CUSTOMERS_MANAGER_Projections', 'render_page']
      );
    }

    // Add Contact Messages submenu
    if (current_user_can('pn_cm_manage_crm')) {
      $unread = PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count();
      $badge  = $unread > 0
        ? ' <span class="awaiting-mod pn-customers-manager-menu-badge">' . esc_html($unread) . '</span>'
        : '';

      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Messages', 'pn-customers-manager'),
        esc_html__('Messages', 'pn-customers-manager') . $badge,
        'pn_cm_manage_crm',
        'pn_customers_manager_contact_messages',
        ['PN_CUSTOMERS_MANAGER_Contact_Messages', 'render_page']
      );
    }

    // Add Mail Statistics submenu
    if (current_user_can('pn_cm_manage_crm')) {
      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Statistics', 'pn-customers-manager'),
        esc_html__('Statistics', 'pn-customers-manager'),
        'pn_cm_manage_crm',
        'pn_customers_manager_mail_stats',
        ['PN_CUSTOMERS_MANAGER_Mail_Stats', 'render_page']
      );
    }
	}

	public function pn_customers_manager_options() {
	  ?>
	    <div class="pn-customers-manager-options pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mt-50 pn-customers-manager-mb-50">
        <img src="<?php echo esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/banner-1544x500.png'); ?>" alt="<?php esc_html_e('Plugin main Banner', 'pn-customers-manager'); ?>" title="<?php esc_html_e('Plugin main Banner', 'pn-customers-manager'); ?>" class="pn-customers-manager-width-100-percent pn-customers-manager-border-radius-20 pn-customers-manager-mb-30">
        <h1 class="pn-customers-manager-mb-30"><?php esc_html_e('PN Customers Manager Settings', 'pn-customers-manager'); ?></h1>
        <?php PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_page_manager_alerts(self::pn_customers_manager_get_managed_pages()); ?>
        <div class="pn-customers-manager-options-fields pn-customers-manager-mb-30 pn-customers-manager-settings-pb-80">
          <form action="" method="post" id="pn-customers-manager-form-setting" class="pn-customers-manager-form pn-customers-manager-p-30">
          <?php
            $options = self::pn_customers_manager_get_options();

            foreach ($options as $pn_customers_manager_option) {
              PN_CUSTOMERS_MANAGER_Forms::pn_customers_manager_input_wrapper_builder($pn_customers_manager_option, 'option', 0, 0, 'half');
            }
          ?>
          <input type="submit" name="pn_customers_manager_submit" id="pn_customers_manager_submit" class="pn-customers-manager-settings-hidden-submit" data-pn-customers-manager-type="option" value="<?php esc_attr_e('Save options', 'pn-customers-manager'); ?>">
          </form>
        </div>
      </div>

      <?php
      // --- Recommended plugins ---
      $pn_family = [
        'mailpn' => [
          'name' => 'MailPN',
          'file' => 'mailpn/mailpn.php',
          'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 840 720"><path d="m558-240-170-170 56-56 114 114 226-226 56 56zM400-680 720-880H80zm0 80-320-200v400h206l80 80H80Q47-320 23.5-343.5 0-367 0-400V-880Q0-913 23.5-936.5 47-960 80-960h640q33 0 56.5 23.5 23.5 23.5 23.5 56.5v174l-80 80v-174zm0 0zm0-80zm0 80z" fill="#ffcc00"/></svg>',
          'settings_page' => 'mailpn_options',
          'desc' => __('Email marketing and newsletter campaigns.', 'pn-customers-manager'),
        ],
        'userspn' => [
          'name' => 'UsersPN',
          'file' => 'userspn/userspn.php',
          'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z" fill="#00aa44"/></svg>',
          'settings_page' => 'userspn_options',
          'desc' => __('User management and registration forms.', 'pn-customers-manager'),
        ],
        'pn-tasks-manager' => [
          'name' => 'PN Tasks Manager',
          'file' => 'pn-tasks-manager/pn-tasks-manager.php',
          'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="m438-240 226-226-58-58-169 169-84-84-57 57 142 142ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z" fill="#552200"/></svg>',
          'settings_page' => 'pn_tasks_manager_options',
          'desc' => __('Task and project management.', 'pn-customers-manager'),
        ],
        'pn-cookies-manager' => [
          'name' => 'PN Cookies Manager',
          'file' => 'pn-cookies-manager/pn-cookies-manager.php',
          'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path d="M480-80Q397-80 324-111.5 251-143 197-197 143-251 111.5-324 80-397 80-480q0-75 29-147 29-72 81-128.5 52-56.5 125-91 73-34.5 160-34.5 21 0 43 2 22 2 45 7-9 45 6 85 15 40 45 66.5 30 26.5 71.5 36.5 41.5 10 85.5-5-26 59 7.5 113 33.5 54 99.5 56 1 11 1.5 20.5.5 9.5.5 20.5 0 82-31.5 154.5-31.5 72.5-85.5 127-54 54.5-127 86Q563-80 480-80Zm-60-480q25 0 42.5-17.5Q480-595 480-620q0-25-17.5-42.5T420-680q-25 0-42.5 17.5T360-620q0 25 17.5 42.5T420-560Zm-80 200q25 0 42.5-17.5Q400-395 400-420q0-25-17.5-42.5T340-480q-25 0-42.5 17.5T280-420q0 25 17.5 42.5T340-360Zm260 40q17 0 28.5-11.5Q640-343 640-360q0-17-11.5-28.5T600-400q-17 0-28.5 11.5T560-360q0 17 11.5 28.5T600-320ZM480-160q122 0 216.5-84 94.5-84 103.5-214-50-22-78.5-60-28.5-38-38.5-85-77-11-132-66-55-55-68-132-80-2-140.5 29-60.5 31-101 79.5-40.5 48.5-61 105.5-20.5 57-20.5 107 0 133 93.5 226.5T480-160Zm0-324Z" fill="#803300"/></svg>',
          'settings_page' => 'pn_cookies_manager_options',
          'desc' => __('Cookie consent and GDPR compliance.', 'pn-customers-manager'),
        ],
      ];
      $pn_recommended = ['mailpn', 'userspn'];
      if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      $pn_installed = get_plugins();
      $pn_rp_badge = 0;
      foreach ($pn_recommended as $pn_s) {
        if (isset($pn_family[$pn_s]) && !isset($pn_installed[$pn_family[$pn_s]['file']])) {
          $pn_rp_badge++;
        }
      }
      ?>

      <!-- Sticky settings footer bar -->
      <div id="pn-customers-manager-settings-footer" class="pn-customers-manager-settings-footer">
        <div class="pn-customers-manager-settings-footer-inner">
          <div class="pn-customers-manager-settings-footer-left">
            <span class="pn-customers-manager-settings-footer-plugin-name">PN Customers Manager</span>
            <span class="pn-customers-manager-settings-footer-version">v<?php echo esc_html(PN_CUSTOMERS_MANAGER_VERSION); ?></span>
          </div>
          <div class="pn-customers-manager-settings-footer-right">
            <button type="button" id="pn-customers-manager-settings-recommended" class="pn-customers-manager-settings-footer-icon-btn pn-cm-rp-btn pn-customers-manager-tooltip" title="<?php esc_attr_e('Recommended plugins', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">add</span>
              <?php if ($pn_rp_badge > 0) : ?>
                <span class="pn-cm-rp-badge"><?php echo (int) $pn_rp_badge; ?></span>
              <?php endif; ?>
            </button>
            <input type="file" id="pn-customers-manager-settings-import-file" class="pn-customers-manager-settings-hidden-input" accept=".json">
            <button type="button" id="pn-customers-manager-settings-import" class="pn-customers-manager-settings-footer-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e('Import settings', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">file_upload</span>
            </button>
            <button type="button" id="pn-customers-manager-settings-export" class="pn-customers-manager-settings-footer-icon-btn pn-customers-manager-tooltip" title="<?php esc_attr_e('Export settings', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">file_download</span>
            </button>
            <button type="button" id="pn-customers-manager-settings-save" class="pn-customers-manager-btn pn-customers-manager-btn-mini">
              <?php esc_html_e('Save options', 'pn-customers-manager'); ?>
            </button>
          </div>
        </div>
      </div>

      <!-- Recommended plugins popup -->
      <div class="pn-customers-manager-popup-overlay pn-customers-manager-display-none-soft" style="z-index:1000000;"></div>
      <div id="pn-customers-manager-recommended-plugins" class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft" style="z-index:1000001;">
        <div class="pn-customers-manager-popup-content" style="padding:30px;">
          <h3 style="margin:0 0 8px;"><?php esc_html_e('Recommended Plugins', 'pn-customers-manager'); ?></h3>
          <p style="color:#787c82;margin:0 0 20px;"><?php esc_html_e('Enhance your workflow with these companion plugins.', 'pn-customers-manager'); ?></p>
          <div class="pn-cm-rp-list">
            <?php foreach ($pn_family as $pn_slug => $pn_pl) :
              $pn_is_installed = isset($pn_installed[$pn_pl['file']]);
              $pn_is_active    = $pn_is_installed && is_plugin_active($pn_pl['file']);
              $pn_is_rec       = in_array($pn_slug, $pn_recommended, true);
            ?>
            <div class="pn-cm-rp-card" data-slug="<?php echo esc_attr($pn_slug); ?>">
              <div class="pn-cm-rp-icon"><?php echo $pn_pl['icon']; ?></div>
              <div class="pn-cm-rp-info">
                <div class="pn-cm-rp-name">
                  <?php echo esc_html($pn_pl['name']); ?>
                  <?php if ($pn_is_rec) : ?>
                    <span class="pn-cm-rp-recommended"><?php esc_html_e('Recommended', 'pn-customers-manager'); ?></span>
                  <?php endif; ?>
                </div>
                <div class="pn-cm-rp-desc"><?php echo esc_html($pn_pl['desc']); ?></div>
              </div>
              <div class="pn-cm-rp-action">
                <?php if ($pn_is_active) : ?>
                  <span class="pn-cm-rp-active-badge"><?php esc_html_e('Active', 'pn-customers-manager'); ?></span>
                <?php elseif ($pn_is_installed) : ?>
                  <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent pn-cm-rp-activate" data-slug="<?php echo esc_attr($pn_slug); ?>"><?php esc_html_e('Activate', 'pn-customers-manager'); ?></button>
                <?php else : ?>
                  <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent pn-cm-rp-install" data-slug="<?php echo esc_attr($pn_slug); ?>"><?php esc_html_e('Install', 'pn-customers-manager'); ?></button>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <?php
      wp_enqueue_style('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-popups.css', [], PN_CUSTOMERS_MANAGER_VERSION);
      wp_enqueue_script('pn-customers-manager-popups', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-popups.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);

      wp_enqueue_style('pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/css/pn-customers-manager-tooltips.css', [], PN_CUSTOMERS_MANAGER_VERSION);
      wp_enqueue_script('pn-customers-manager-tooltips', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-tooltips.js', ['jquery'], PN_CUSTOMERS_MANAGER_VERSION, true);

      wp_enqueue_script(
        'pn-customers-manager-api-tests',
        PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-api-tests.js',
        [],
        PN_CUSTOMERS_MANAGER_VERSION,
        true
      );

      wp_localize_script('pn-customers-manager-api-tests', 'pnCmApiTests', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('pn-customers-manager-nonce'),
        'i18n'    => [
          'testing'         => __('Testing...', 'pn-customers-manager'),
          'success'         => __('Connection successful.', 'pn-customers-manager'),
          'error'           => __('Error:', 'pn-customers-manager'),
          'noPhone'         => __('Enter a test phone number.', 'pn-customers-manager'),
          'waSent'          => __('Message sent successfully.', 'pn-customers-manager'),
          'waError'         => __('Error sending message.', 'pn-customers-manager'),
          'listening'       => __('Listening', 'pn-customers-manager'),
          'listenStopped'   => __('Listening stopped.', 'pn-customers-manager'),
          'listenTimeout'   => __('Timeout. No messages received.', 'pn-customers-manager'),
          'messageReceived' => __('Message received from', 'pn-customers-manager'),
          'noIgId'          => __('Enter a test Instagram User ID.', 'pn-customers-manager'),
          'igSent'          => __('Instagram message sent successfully.', 'pn-customers-manager'),
          'igError'         => __('Error sending Instagram message.', 'pn-customers-manager'),
          'igListening'     => __('Listening for Instagram DMs', 'pn-customers-manager'),
          'igMessageReceived' => __('Instagram DM received from', 'pn-customers-manager'),
        ],
      ]);

      wp_enqueue_script(
        'pn-customers-manager-settings-footer',
        PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-settings-footer.js',
        [],
        PN_CUSTOMERS_MANAGER_VERSION,
        true
      );

      $pn_rp_settings = [];
      foreach ($pn_family as $pn_slug => $pn_pl) {
        $pn_rp_settings[$pn_slug] = admin_url('admin.php?page=' . $pn_pl['settings_page']);
      }

      wp_localize_script('pn-customers-manager-settings-footer', 'pnCustomersManagerSettingsFooter', [
        'ajaxUrl'       => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('pn-customers-manager-nonce'),
        'settingsPages' => $pn_rp_settings,
        'i18n'          => [
          'confirmImport'  => __('This will overwrite your current settings. Continue?', 'pn-customers-manager'),
          'importSuccess'  => __('Settings imported successfully. Reloading...', 'pn-customers-manager'),
          'importError'    => __('Error importing settings.', 'pn-customers-manager'),
          'invalidFile'    => __('Invalid JSON file.', 'pn-customers-manager'),
          'exportError'    => __('Error exporting settings.', 'pn-customers-manager'),
          'installing'     => __('Installing...', 'pn-customers-manager'),
          'activating'     => __('Activating...', 'pn-customers-manager'),
          'installError'   => __('Error installing plugin.', 'pn-customers-manager'),
          'activateError'  => __('Error activating plugin.', 'pn-customers-manager'),
          'active'         => __('Active', 'pn-customers-manager'),
          'activate'       => __('Activate', 'pn-customers-manager'),
        ],
      ]);
      ?>
	  <?php
	}

  public function pn_customers_manager_activated_plugin($plugin) {
    if($plugin == 'pn-customers-manager/pn-customers-manager.php') {
      if (get_option('pn_customers_manager_pages_funnel') && get_option('pn_customers_manager_url_main')) {
        if (!get_transient('pn_customers_manager_just_activated') && !defined('DOING_AJAX')) {
          set_transient('pn_customers_manager_just_activated', true, 30);
        }
      }
    }
  }

  public function pn_customers_manager_check_activation() {
    // Only run in admin and not during AJAX requests
    if (!is_admin() || defined('DOING_AJAX')) {
      return;
    }

    // Run pending schema upgrades on plugin updates (no re-activation needed).
    if (get_option('pn_customers_manager_db_version', '0') !== PN_CUSTOMERS_MANAGER_DB_VERSION) {
      require_once PN_CUSTOMERS_MANAGER_DIR . 'includes/class-pn-customers-manager-activator.php';
      PN_CUSTOMERS_MANAGER_Activator::pn_customers_manager_create_tables();
    }

    // Keep projections cron aligned with the configured frequency.
    if (function_exists('pn_customers_manager_reschedule_projections_cron')) {
      pn_customers_manager_reschedule_projections_cron();
    }

    // Check if we're already in the redirection process
    if (get_option('pn_customers_manager_redirecting')) {
      delete_option('pn_customers_manager_redirecting');
      return;
    }

    if (get_transient('pn_customers_manager_just_activated')) {
      $target_url = admin_url('admin.php?page=pn_customers_manager_options');
      
      if ($target_url) {
        // Mark that we're in the redirection process
        update_option('pn_customers_manager_redirecting', true);
        
        // Remove the transient
        delete_transient('pn_customers_manager_just_activated');
        
        // Redirect and exit
        wp_safe_redirect(esc_url($target_url));
        exit;
      }
    }
  }

  /**
   * Adds the Settings link to the plugin list
   */
  public function pn_customers_manager_plugin_action_links($links) {
      $settings_link = '<a href="admin.php?page=pn_customers_manager_options">' . esc_html__('Settings', 'pn-customers-manager') . '</a>';
      array_unshift($links, $settings_link);
      
      return $links;
  }

}