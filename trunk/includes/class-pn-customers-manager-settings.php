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
  public function pn_customers_manager_get_options() {
    $pn_customers_manager_options = [];

    // Commercial section
    $pn_customers_manager_options['pn_customers_manager_commercial_section_start'] = [
      'id' => 'pn_customers_manager_commercial_section_start',
      'section' => 'start',
      'label' => __('Commercial', 'pn-customers-manager'),
      'description' => __('Commercial agents system configuration.', 'pn-customers-manager'),
    ];

    $pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
    $page_options = ['' => __('-- Select page --', 'pn-customers-manager')];
    if (!empty($pages)) {
      foreach ($pages as $page) {
        $page_options[$page->ID] = $page->post_title;
      }
    }

    $pn_customers_manager_options['pn_customers_manager_commercial_crm_page'] = [
      'id' => 'pn_customers_manager_commercial_crm_page',
      'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
      'input' => 'select',
      'options' => $page_options,
      'label' => __('CRM Page', 'pn-customers-manager'),
      'description' => __('Select the CRM page that approved commercial agents will access.', 'pn-customers-manager'),
    ];

    $pn_customers_manager_options['pn_customers_manager_commercial_section_end'] = [
      'id' => 'pn_customers_manager_commercial_section_end',
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

    // ── End API Configuration ──
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
    }
    
    // Check if user has any of the required capabilities
    $has_cap = current_user_can('edit_pn_cm_funnel') || current_user_can('edit_pn_cm_organization') || current_user_can('manage_options');
    
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

    // Add Commercial Agents submenu
    if (current_user_can('manage_options')) {
      $pending_commercial = PN_CUSTOMERS_MANAGER_Commercial::get_pending_count();
      $commercial_badge = $pending_commercial > 0
        ? ' <span class="awaiting-mod pn-customers-manager-menu-badge">' . esc_html($pending_commercial) . '</span>'
        : '';

      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Commercial Agents', 'pn-customers-manager'),
        esc_html__('Commercial Agents', 'pn-customers-manager') . $commercial_badge,
        'manage_options',
        'pn_customers_manager_commercial_agents',
        ['PN_CUSTOMERS_MANAGER_Commercial', 'render_admin_commercial_agents']
      );
    }

    // WhatsApp AI — hidden page (accessible via direct URL or shortcode, not shown in menu)
    if (current_user_can('manage_options')) {
      add_submenu_page(
        null,
        esc_html__('WhatsApp AI', 'pn-customers-manager'),
        '',
        'manage_options',
        'pn_customers_manager_whatsapp_ai',
        ['PN_CUSTOMERS_MANAGER_WhatsApp_AI', 'render_admin_page']
      );
    }

    // Instagram AI — hidden page (accessible via direct URL or shortcode, not shown in menu)
    if (current_user_can('manage_options')) {
      add_submenu_page(
        null,
        esc_html__('Instagram AI', 'pn-customers-manager'),
        '',
        'manage_options',
        'pn_customers_manager_instagram_ai',
        ['PN_CUSTOMERS_MANAGER_Instagram_AI', 'render_admin_page']
      );
    }

    // Add Contact Messages submenu
    if (current_user_can('manage_options')) {
      $unread = PN_CUSTOMERS_MANAGER_Contact_Messages::get_unread_count();
      $badge  = $unread > 0
        ? ' <span class="awaiting-mod pn-customers-manager-menu-badge">' . esc_html($unread) . '</span>'
        : '';

      add_submenu_page(
        'pn_customers_manager_options',
        esc_html__('Messages', 'pn-customers-manager'),
        esc_html__('Messages', 'pn-customers-manager') . $badge,
        'manage_options',
        'pn_customers_manager_contact_messages',
        ['PN_CUSTOMERS_MANAGER_Contact_Messages', 'render_page']
      );
    }
	}

	public function pn_customers_manager_options() {
    $organization_page = self::pn_customers_manager_find_organization_page();
	  ?>
	    <div class="pn-customers-manager-options pn-customers-manager-max-width-1000 pn-customers-manager-margin-auto pn-customers-manager-mt-50 pn-customers-manager-mb-50">
        <img src="<?php echo esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/banner-1544x500.png'); ?>" alt="<?php esc_html_e('Plugin main Banner', 'pn-customers-manager'); ?>" title="<?php esc_html_e('Plugin main Banner', 'pn-customers-manager'); ?>" class="pn-customers-manager-width-100-percent pn-customers-manager-border-radius-20 pn-customers-manager-mb-30">
        <h1 class="pn-customers-manager-mb-30"><?php esc_html_e('PN Customers Manager Settings', 'pn-customers-manager'); ?></h1>
        <?php if (!$organization_page): ?>
          <div class="pn-customers-manager-options-fields pn-customers-manager-mb-30">
            <div class="pn-customers-manager-p-30">
              <p class="pn-customers-manager-mb-15">
                <?php esc_html_e('No page with the Organizations block has been detected on your site. Click the button below to automatically create a new page with the Organizations block already inserted. Once the page is created, you will be redirected to its editor so you can review it and publish it or make any changes you need.', 'pn-customers-manager'); ?>
              </p>
              <button type="button" id="pn-customers-manager-create-organization-page" class="pn-customers-manager-btn pn-customers-manager-btn-mini">
                <?php esc_html_e('Create Organizations page', 'pn-customers-manager'); ?>
              </button>
            </div>
          </div>
          <?php
          wp_enqueue_script(
            'pn-customers-manager-settings',
            PN_CUSTOMERS_MANAGER_URL . 'assets/js/admin/pn-customers-manager-settings.js',
            [],
            PN_CUSTOMERS_MANAGER_VERSION,
            true
          );

          wp_localize_script('pn-customers-manager-settings', 'pnCmSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('pn-customers-manager-nonce'),
            'i18n'    => [
              'creatingPage' => __('Creating page...', 'pn-customers-manager'),
              'createPage'   => __('Create Organizations page', 'pn-customers-manager'),
              'errorCreating' => __('An error occurred while creating the page.', 'pn-customers-manager'),
            ],
          ]);
          ?>
        <?php endif; ?>
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

      <!-- Sticky settings footer bar -->
      <div id="pn-customers-manager-settings-footer" class="pn-customers-manager-settings-footer">
        <div class="pn-customers-manager-settings-footer-inner">
          <div class="pn-customers-manager-settings-footer-left">
            <span class="pn-customers-manager-settings-footer-plugin-name">PN Customers Manager</span>
            <span class="pn-customers-manager-settings-footer-version">v<?php echo esc_html(PN_CUSTOMERS_MANAGER_VERSION); ?></span>
          </div>
          <div class="pn-customers-manager-settings-footer-right">
            <input type="file" id="pn-customers-manager-settings-import-file" class="pn-customers-manager-settings-hidden-input" accept=".json">
            <button type="button" id="pn-customers-manager-settings-import" class="pn-customers-manager-settings-footer-icon-btn" title="<?php esc_attr_e('Import settings', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">file_upload</span>
            </button>
            <button type="button" id="pn-customers-manager-settings-export" class="pn-customers-manager-settings-footer-icon-btn" title="<?php esc_attr_e('Export settings', 'pn-customers-manager'); ?>">
              <span class="material-icons-outlined">file_download</span>
            </button>
            <button type="button" id="pn-customers-manager-settings-save" class="pn-customers-manager-btn pn-customers-manager-btn-mini">
              <?php esc_html_e('Save options', 'pn-customers-manager'); ?>
            </button>
          </div>
        </div>
      </div>

      <?php
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

      wp_localize_script('pn-customers-manager-settings-footer', 'pnCustomersManagerSettingsFooter', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('pn-customers-manager-nonce'),
        'i18n'    => [
          'confirmImport'  => __('This will overwrite your current settings. Continue?', 'pn-customers-manager'),
          'importSuccess'  => __('Settings imported successfully. Reloading...', 'pn-customers-manager'),
          'importError'    => __('Error importing settings.', 'pn-customers-manager'),
          'invalidFile'    => __('Invalid JSON file.', 'pn-customers-manager'),
          'exportError'    => __('Error exporting settings.', 'pn-customers-manager'),
        ],
      ]);
      ?>
	  <?php
	}

  public static function pn_customers_manager_find_organization_page() {
    $pages = get_posts([
      'post_type'   => 'page',
      'post_status' => ['publish', 'draft', 'private'],
      'numberposts' => -1,
      'fields'      => 'ids',
    ]);

    foreach ($pages as $page_id) {
      $content = get_post_field('post_content', $page_id);

      if (
        has_shortcode($content, 'pn-customers-manager-organization-list') ||
        strpos($content, '<!-- wp:pn-customers-manager/organization-list') !== false
      ) {
        return $page_id;
      }
    }

    return false;
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