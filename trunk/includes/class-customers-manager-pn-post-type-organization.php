<?php
/**
 * Organization creator.
 *
 * This class defines Organization options, menus and templates.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CUSTOMERS_MANAGER_PN
 * @subpackage CUSTOMERS_MANAGER_PN/includes
 * @author     Padres en la Nube
 */
class CUSTOMERS_MANAGER_PN_Post_Type_organization {
  public function cm_pn_org_get_fields($organization_id = 0) {
    $customers_manager_pn_fields = [];
      $customers_manager_pn_fields['cm_pn_org_title'] = [
        'id' => 'cm_pn_org_title',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'required' => true,
        'value' => !empty($organization_id) ? esc_html(get_the_title($organization_id)) : '',
        'label' => __('Organization title', 'customers-manager-pn'),
        'placeholder' => __('Organization title', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields['cm_pn_org_description'] = [
        'id' => 'cm_pn_org_description',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'textarea',
        'required' => true,
        'value' => !empty($organization_id) ? (str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($organization_id)->post_content))) : '',
        'label' => __('Organization description', 'customers-manager-pn'),
        'placeholder' => __('Organization description', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['customers_manager_pn_ajax_nonce'] = [
        'id' => 'customers_manager_pn_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $customers_manager_pn_fields;
  }

  /**
   * Build a list of WP users to populate owner/collaborator selects.
   *
   * @param bool $include_placeholder Include the default "select" option.
   * @return array
   */
  private function customers_manager_pn_get_owner_select_options($include_placeholder = true) {
    $options = $include_placeholder ? ['' => esc_html__('Select a user', 'customers-manager-pn')] : [];

    $users = get_users([
      'fields' => ['ID', 'display_name', 'user_email'],
      'orderby' => 'display_name',
      'order' => 'ASC',
    ]);

    if (!empty($users)) {
      foreach ($users as $user) {
        $label = '';

        if (class_exists('CUSTOMERS_MANAGER_PN_Functions_User')) {
          $label = CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_get_name($user->ID);
        }

        if (empty($label)) {
          $label = !empty($user->display_name) ? $user->display_name : $user->user_email;
        }

        $options[$user->ID] = esc_html($label);
      }
    }

    return $options;
  }

  /**
   * Build the list of funnels that can be linked to an organization.
   *
   * @param int $organization_id
   * @return array
   */
  private static function customers_manager_pn_get_funnel_select_options($organization_id = 0) {
    $options = ['' => esc_html__('Select a funnel', 'customers-manager-pn')];

    $funnel_args = [
      'post_type'   => 'cm_pn_funnel',
      'numberposts' => -1,
      'orderby'     => 'title',
      'order'       => 'ASC',
      'fields'      => 'ids',
    ];

    if (!empty($organization_id)) {
      $funnel_args['meta_query'] = [
        'relation' => 'OR',
        [
          'key'     => 'cm_pn_funnel_linked_organization_alt',
          'compare' => 'NOT EXISTS',
        ],
        [
          'key'     => 'cm_pn_funnel_linked_organization_alt',
          'value'   => $organization_id,
          'compare' => '=',
        ],
      ];
    }

    $funnels = get_posts($funnel_args);

    if (!empty($funnels)) {
      foreach ($funnels as $funnel_id) {
        $options[$funnel_id] = esc_html(get_the_title($funnel_id));
      }
    }

    return $options;
  }

  /**
   * Return the list of stages available for the funnel currently linked.
   *
   * @param int $organization_id
   * @return array
   */
  private static function customers_manager_pn_get_funnel_stage_options($organization_id = 0) {
    $options = ['' => esc_html__('Select a funnel stage', 'customers-manager-pn')];

    if (empty($organization_id) || !class_exists('CUSTOMERS_MANAGER_PN_Post_Type_Funnel')) {
      return $options;
    }

    $funnel_id = intval(get_post_meta($organization_id, 'cm_pn_org_funnel_id', true));

    if (empty($funnel_id)) {
      return $options;
    }

    $stages = CUSTOMERS_MANAGER_PN_Post_Type_Funnel::customers_manager_pn_get_funnel_stages_list($funnel_id);

    if (!empty($stages)) {
      foreach ($stages as $stage) {
        $options[$stage] = esc_html($stage);
      }
    }

    return $options;
  }

  /**
   * Shared list of funnel status options.
   *
   * @param bool $include_placeholder
   * @return array
   */
  private static function customers_manager_pn_get_funnel_status_options($include_placeholder = true) {
    $options = $include_placeholder ? ['' => esc_html__('Select a funnel status', 'customers-manager-pn')] : [];

    $statuses = [
      'not_started' => esc_html__('Not started', 'customers-manager-pn'),
      'in_progress' => esc_html__('In progress', 'customers-manager-pn'),
      'stalled'     => esc_html__('Stalled / needs attention', 'customers-manager-pn'),
      'won'         => esc_html__('Won', 'customers-manager-pn'),
      'lost'        => esc_html__('Lost', 'customers-manager-pn'),
    ];

    return $options + $statuses;
  }

  /**
   * Return the array of user IDs linked as contacts.
   *
   * @param int $organization_id
   * @return array
   */
  private static function customers_manager_pn_get_organization_contacts($organization_id) {
    $contacts = get_post_meta($organization_id, 'cm_pn_org_contacts', true);
    if (empty($contacts) || !is_array($contacts)) {
      return [];
    }
    return array_map('intval', $contacts);
  }

  /**
   * Build a readable name for a contact.
   *
   * @param int $user_id
   * @return string
   */
  private static function cm_pn_format_contact_name($user_id) {
    if (class_exists('CUSTOMERS_MANAGER_PN_Functions_User')) {
      return CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_user_get_name($user_id);
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
      return '';
    }

    return !empty($user->display_name) ? $user->display_name : $user->user_email;
  }

  /**
   * Render the contacts list for organization.
   *
   * @param int $organization_id
   * @return string
   */
  private static function customers_manager_pn_render_contacts_list($organization_id) {
    $contacts = self::customers_manager_pn_get_organization_contacts($organization_id);

    if (empty($contacts)) {
      return '<p class="customers-manager-pn-m-0">' . esc_html__('No contacts linked yet.', 'customers-manager-pn') . '</p>';
    }

    ob_start();
    ?>
      <ul class="customers-manager-pn-list-style-none customers-manager-pn-m-0 customers-manager-pn-p-0">
        <?php foreach ($contacts as $contact_id): ?>
          <?php
            $user = get_user_by('id', $contact_id);
            if (!$user) {
              continue;
            }
            $display_name = self::cm_pn_format_contact_name($contact_id);
            $email = $user->user_email;
            $edit_link = get_edit_user_link($contact_id);
          ?>
          <li class="customers-manager-pn-mb-10">
            <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
              <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                <a href="<?php echo esc_url($edit_link); ?>" target="_blank" class="customers-manager-pn-text-decoration-none">
                  <strong><?php echo esc_html($display_name); ?></strong>
                  <?php if (!empty($email)): ?>
                    <br><small><?php echo esc_html($email); ?></small>
                  <?php endif; ?>
                </a>
              </div>
              <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-30-percent customers-manager-pn-text-align-right">
                <a href="#" 
                   class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-remove-contact" 
                   data-contact-id="<?php echo esc_attr($contact_id); ?>"
                   data-org-alt-id="<?php echo esc_attr($organization_id); ?>">
                  <?php esc_html_e('Remove', 'customers-manager-pn'); ?>
                </a>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php
    return ob_get_clean();
  }

  /**
   * Render the contacts field for organization.
   *
   * @param int $organization_id
   * @return string
   */
  private function customers_manager_pn_render_contacts_field($organization_id) {
    if (empty($organization_id)) {
      return '<p class="customers-manager-pn-m-0">' . esc_html__('Save the organization to start adding contacts.', 'customers-manager-pn') . '</p>';
    }

    $list = self::customers_manager_pn_render_contacts_list($organization_id);

    ob_start();
    ?>
      <div class="customers-manager-pn-organization-contacts-field" data-cm_pn_org_alt-id="<?php echo esc_attr($organization_id); ?>">
        <div class="customers-manager-pn-organization-contacts-list">
          <?php echo wp_kses($list, CUSTOMERS_MANAGER_PN_KSES); ?>
        </div>
        <div class="customers-manager-pn-text-align-right customers-manager-pn-mt-15">
          <a href="#"
             class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-popup-open-ajax"
             data-customers-manager-pn-popup-id="customers-manager-pn-popup-customers_manager_pn_contact-add"
             data-customers-manager-pn-ajax-type="customers_manager_pn_contact_new"
             data-cm_pn_org_alt-id="<?php echo esc_attr($organization_id); ?>">
            <?php esc_html_e('Add contact', 'customers-manager-pn'); ?>
          </a>
        </div>
      </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Meta fields for Organization.
   *
   * This replicates ALL meta fields from the main Organization CPT
   * but with field names adapted for organization.
   *
   * @param int $organization_id
   * @return array
   */
  public function cm_pn_org_get_fields_meta($organization_id = 0) {
    $customers_manager_pn_fields_meta = [];
      $customers_manager_pn_fields_meta['cm_pn_org_section_basic_start'] = [
        'id' => 'cm_pn_org_section_basic_start',
        'section' => 'start',
        'label' => esc_html__('Basic organization data', 'customers-manager-pn'),
        'description' => esc_html__('Essential information to identify and contact the organization.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_contacts_block'] = [
        'id' => 'cm_pn_org_contacts_block',
        'input' => 'html',
        'skip_save' => true,
        'label' => esc_html__('Linked contacts', 'customers-manager-pn'),
        'html_content' => $this->customers_manager_pn_render_contacts_field($organization_id),
        'description' => esc_html__('Each contact is a regular WordPress user so you can edit it from the Users screen.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_legal_name'] = [
        'id' => 'cm_pn_org_legal_name',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Legal name', 'customers-manager-pn'),
        'placeholder' => esc_html__('e.g. Company Inc.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_trade_name'] = [
        'id' => 'cm_pn_org_trade_name',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Trade name', 'customers-manager-pn'),
        'placeholder' => esc_html__('Common brand name', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_segment'] = [
        'id' => 'cm_pn_org_segment',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Segment', 'customers-manager-pn'),
        'placeholder' => esc_html__('Select a segment', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a segment', 'customers-manager-pn'),
          'startup' => esc_html__('Startup', 'customers-manager-pn'),
          'smb' => esc_html__('Small / mid-sized business', 'customers-manager-pn'),
          'enterprise' => esc_html__('Enterprise', 'customers-manager-pn'),
          'nonprofit' => esc_html__('Nonprofit / third sector', 'customers-manager-pn'),
          'government' => esc_html__('Public sector', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_industry'] = [
        'id' => 'cm_pn_org_industry',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Industry', 'customers-manager-pn'),
        'placeholder' => esc_html__('Select an industry', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select an industry', 'customers-manager-pn'),
          'software' => esc_html__('Software / SaaS', 'customers-manager-pn'),
          'services' => esc_html__('Professional services', 'customers-manager-pn'),
          'manufacturing' => esc_html__('Manufacturing', 'customers-manager-pn'),
          'education' => esc_html__('Education', 'customers-manager-pn'),
          'health' => esc_html__('Healthcare', 'customers-manager-pn'),
          'finance' => esc_html__('Financial services', 'customers-manager-pn'),
          'retail' => esc_html__('Retail / eCommerce', 'customers-manager-pn'),
          'other' => esc_html__('Other', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_team_size'] = [
        'id' => 'cm_pn_org_team_size',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Team size', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a range', 'customers-manager-pn'),
          '1-10' => esc_html__('1 - 10 people', 'customers-manager-pn'),
          '11-50' => esc_html__('11 - 50 people', 'customers-manager-pn'),
          '51-200' => esc_html__('51 - 200 people', 'customers-manager-pn'),
          '201-500' => esc_html__('201 - 500 people', 'customers-manager-pn'),
          '500+' => esc_html__('More than 500 people', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_annual_revenue'] = [
        'id' => 'cm_pn_org_annual_revenue',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Annual revenue', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a range', 'customers-manager-pn'),
          '<250k' => esc_html__('Up to €250k', 'customers-manager-pn'),
          '250k-1m' => esc_html__('€250k - €1M', 'customers-manager-pn'),
          '1m-5m' => esc_html__('€1M - €5M', 'customers-manager-pn'),
          '5m-20m' => esc_html__('€5M - €20M', 'customers-manager-pn'),
          '>20m' => esc_html__('More than €20M', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_phone'] = [
        'id' => 'cm_pn_org_phone',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'tel',
        'label' => esc_html__('Primary phone', 'customers-manager-pn'),
        'placeholder' => esc_html__('e.g. +1 555 123 4567', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_email'] = [
        'id' => 'cm_pn_org_email',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'email',
        'label' => esc_html__('Primary email', 'customers-manager-pn'),
        'placeholder' => esc_html__('contact@company.com', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_website'] = [
        'id' => 'cm_pn_org_website',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'url',
        'label' => esc_html__('Website', 'customers-manager-pn'),
        'placeholder' => esc_html__('https://company.com', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_linkedin'] = [
        'id' => 'cm_pn_org_linkedin',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'url',
        'label' => esc_html__('LinkedIn profile', 'customers-manager-pn'),
        'placeholder' => esc_html__('https://www.linkedin.com/company/...', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_country'] = [
        'id' => 'cm_pn_org_country',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Country', 'customers-manager-pn'),
        'placeholder' => esc_html__('e.g. United States', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_region'] = [
        'id' => 'cm_pn_org_region',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Region / State', 'customers-manager-pn'),
        'placeholder' => esc_html__('e.g. California', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_city'] = [
        'id' => 'cm_pn_org_city',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('City', 'customers-manager-pn'),
        'placeholder' => esc_html__('e.g. San Francisco', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_address'] = [
        'id' => 'cm_pn_org_address',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Address', 'customers-manager-pn'),
        'placeholder' => esc_html__('Street, number, suite…', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_postal_code'] = [
        'id' => 'cm_pn_org_postal_code',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Postal code', 'customers-manager-pn'),
        'placeholder' => esc_html__('e.g. 94105', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_section_basic_end'] = [
        'id' => 'cm_pn_org_section_basic_end',
        'section' => 'end',
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_section_advanced_start'] = [
        'id' => 'cm_pn_org_section_advanced_start',
        'section' => 'start',
        'label' => esc_html__('Advanced CRM fields', 'customers-manager-pn'),
        'description' => esc_html__('Strategic data to segment, prioritize, and plan commercial follow-up.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_fiscal_id'] = [
        'id' => 'cm_pn_org_fiscal_id',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Tax ID (VAT / EIN)', 'customers-manager-pn'),
        'placeholder' => esc_html__('Fiscal identifier', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_lead_source'] = [
        'id' => 'cm_pn_org_lead_source',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Lead source', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a source', 'customers-manager-pn'),
          'website' => esc_html__('Website / SEO', 'customers-manager-pn'),
          'ads' => esc_html__('Paid campaigns', 'customers-manager-pn'),
          'event' => esc_html__('Event', 'customers-manager-pn'),
          'referral' => esc_html__('Referral', 'customers-manager-pn'),
          'outbound' => esc_html__('Outbound prospecting', 'customers-manager-pn'),
          'partner' => esc_html__('Partner', 'customers-manager-pn'),
          'other' => esc_html__('Other source', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_lifecycle_stage'] = [
        'id' => 'cm_pn_org_lifecycle_stage',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Lifecycle stage', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a lifecycle stage', 'customers-manager-pn'),
          'lead' => esc_html__('Lead', 'customers-manager-pn'),
          'marketing_qualified' => esc_html__('Marketing qualified (MQL)', 'customers-manager-pn'),
          'sales_qualified' => esc_html__('Sales qualified (SQL)', 'customers-manager-pn'),
          'opportunity' => esc_html__('Opportunity', 'customers-manager-pn'),
          'customer' => esc_html__('Customer', 'customers-manager-pn'),
          'churned' => esc_html__('Churned', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_pipeline_stage'] = [
        'id' => 'cm_pn_org_pipeline_stage',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Pipeline stage', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a pipeline stage', 'customers-manager-pn'),
          'qualification' => esc_html__('Qualification', 'customers-manager-pn'),
          'discovery' => esc_html__('Discovery', 'customers-manager-pn'),
          'proposal' => esc_html__('Proposal', 'customers-manager-pn'),
          'negotiation' => esc_html__('Negotiation', 'customers-manager-pn'),
          'closed_won' => esc_html__('Closed won', 'customers-manager-pn'),
          'closed_lost' => esc_html__('Closed lost', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_priority'] = [
        'id' => 'cm_pn_org_priority',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Commercial priority', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a priority', 'customers-manager-pn'),
          'high' => esc_html__('High', 'customers-manager-pn'),
          'medium' => esc_html__('Medium', 'customers-manager-pn'),
          'low' => esc_html__('Low', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_health'] = [
        'id' => 'cm_pn_org_health',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Account health', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a health status', 'customers-manager-pn'),
          'healthy' => esc_html__('Healthy / growing', 'customers-manager-pn'),
          'risk' => esc_html__('At risk', 'customers-manager-pn'),
          'churn' => esc_html__('Possible churn', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_lead_score'] = [
        'id' => 'cm_pn_org_lead_score',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'range',
        'min' => 0,
        'max' => 100,
        'step' => 5,
        'label' => esc_html__('Lead score', 'customers-manager-pn'),
        'description' => esc_html__('0 = cold lead, 100 = hot lead.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_owner'] = [
        'id' => 'cm_pn_org_owner',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Primary owner', 'customers-manager-pn'),
        'options' => $this->customers_manager_pn_get_owner_select_options(),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_collaborators'] = [
        'id' => 'cm_pn_org_collaborators',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'multiple' => true,
        'label' => esc_html__('Assigned collaborators', 'customers-manager-pn'),
        'description' => esc_html__('Select other team members involved with the account.', 'customers-manager-pn'),
        'options' => $this->customers_manager_pn_get_owner_select_options(true),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_last_contact_date'] = [
        'id' => 'cm_pn_org_last_contact_date',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => esc_html__('Last contact', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_last_contact_channel'] = [
        'id' => 'cm_pn_org_last_contact_channel',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Last contact channel', 'customers-manager-pn'),
        'options' => [
          '' => esc_html__('Select a channel', 'customers-manager-pn'),
          'email' => esc_html__('Email', 'customers-manager-pn'),
          'call' => esc_html__('Call', 'customers-manager-pn'),
          'meeting' => esc_html__('Meeting', 'customers-manager-pn'),
          'chat' => esc_html__('Chat / messaging', 'customers-manager-pn'),
          'event' => esc_html__('Event', 'customers-manager-pn'),
          'other' => esc_html__('Other', 'customers-manager-pn'),
        ],
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_next_action'] = [
        'id' => 'cm_pn_org_next_action',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'textarea',
        'label' => esc_html__('Próximo paso acordado', 'customers-manager-pn'),
        'placeholder' => esc_html__('Describe el siguiente hito o compromiso.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_billing_email'] = [
        'id' => 'cm_pn_org_billing_email',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'email',
        'label' => esc_html__('Email de facturación', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_billing_phone'] = [
        'id' => 'cm_pn_org_billing_phone',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'tel',
        'label' => esc_html__('Teléfono de facturación', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_billing_address'] = [
        'id' => 'cm_pn_org_billing_address',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Dirección de facturación', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_tags'] = [
        'id' => 'cm_pn_org_tags',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Etiquetas', 'customers-manager-pn'),
        'placeholder' => esc_html__('Ej: Clientes VIP, Renovación, Partner', 'customers-manager-pn'),
        'description' => esc_html__('Separa las etiquetas con comas.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_notes'] = [
        'id' => 'cm_pn_org_notes',
        'class' => 'customers-manager-pn-input customers-manager-pn-width-100-percent',
        'input' => 'textarea',
        'label' => esc_html__('Notas internas', 'customers-manager-pn'),
        'placeholder' => esc_html__('Contexto adicional para el equipo comercial.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_section_advanced_end'] = [
        'id' => 'cm_pn_org_section_advanced_end',
        'section' => 'end',
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_section_funnel_start'] = [
        'id' => 'cm_pn_org_section_funnel_start',
        'section' => 'start',
        'label' => esc_html__('Commercial funnel', 'customers-manager-pn'),
        'description' => esc_html__('Link this organization to a funnel and track its stage.', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_funnel_id'] = [
        'id' => 'cm_pn_org_funnel_id',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Assigned funnel', 'customers-manager-pn'),
        'options' => self::customers_manager_pn_get_funnel_select_options($organization_id),
        'placeholder' => esc_html__('Select a funnel', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_funnel_stage'] = [
        'id' => 'cm_pn_org_funnel_stage',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Current stage', 'customers-manager-pn'),
        'options' => self::customers_manager_pn_get_funnel_stage_options($organization_id),
        'placeholder' => esc_html__('Select a funnel stage', 'customers-manager-pn'),
        'description' => esc_html__('The available stages come from the funnel definition (one per line).', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_funnel_status'] = [
        'id' => 'cm_pn_org_funnel_status',
        'class' => 'customers-manager-pn-select customers-manager-pn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Funnel status', 'customers-manager-pn'),
        'options' => self::customers_manager_pn_get_funnel_status_options(),
        'placeholder' => esc_html__('Select a funnel status', 'customers-manager-pn'),
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_section_funnel_end'] = [
        'id' => 'cm_pn_org_section_funnel_end',
        'section' => 'end',
      ];
      $customers_manager_pn_fields_meta['cm_pn_org_form'] = [
        'id' => 'cm_pn_org_form',
        'input' => 'input',
        'type' => 'hidden',
      ];
      $customers_manager_pn_fields_meta['customers_manager_pn_ajax_nonce'] = [
        'id' => 'customers_manager_pn_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $customers_manager_pn_fields_meta;
  }

  /**
   * Register Organization.
   *
   * @since    1.0.0
   */
  public function cm_pn_org_register_post_type() {
    $labels = [
      'name'                => _x('Organization', 'Post Type general name', 'customers-manager-pn'),
      'singular_name'       => _x('Organization', 'Post Type singular name', 'customers-manager-pn'),
      'menu_name'           => esc_html(__('Organizations', 'customers-manager-pn')),
      'parent_item_colon'   => esc_html(__('Parent Organization', 'customers-manager-pn')),
      'all_items'           => esc_html(__('All Organizations', 'customers-manager-pn')),
      'view_item'           => esc_html(__('View Organization', 'customers-manager-pn')),
      'add_new_item'        => esc_html(__('Add new Organization', 'customers-manager-pn')),
      'add_new'             => esc_html(__('Add new Organization', 'customers-manager-pn')),
      'edit_item'           => esc_html(__('Edit Organization', 'customers-manager-pn')),
      'update_item'         => esc_html(__('Update Organization', 'customers-manager-pn')),
      'search_items'        => esc_html(__('Search Organizations', 'customers-manager-pn')),
      'not_found'           => esc_html(__('Not Organization found', 'customers-manager-pn')),
      'not_found_in_trash'  => esc_html(__('Not Organization found in Trash', 'customers-manager-pn')),
    ];

    $args = [
      'labels'              => $labels,
      'rewrite'             => ['slug' => (!empty(get_option('cm_pn_org_slug')) ? get_option('cm_pn_org_slug') : 'customers-manager-pn'), 'with_front' => false],
      'label'               => esc_html(__('Organizations', 'customers-manager-pn')),
      'description'         => esc_html(__('Organization description', 'customers-manager-pn')),
      'supports'            => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'page-attributes', ],
      'hierarchical'        => true,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => false, // Menu added manually in settings
      'show_in_nav_menus'   => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 5,
      'menu_icon'           => esc_url(CUSTOMERS_MANAGER_PN_URL . 'assets/media/customers-manager-pn-funnel-menu-icon.svg'),
      'can_export'          => true,
      'has_archive'         => true,
      'exclude_from_search' => false,
      'publicly_queryable'  => true,
      'capability_type'     => 'page',
      'capabilities'        => CUSTOMERS_MANAGER_PN_ROLE_CM_PN_ORG_CAPABILITIES,
      'taxonomies'          => ['cm_pn_org_category'],
      'show_in_rest'        => true, /* REST API */
    ];

    register_post_type('cm_pn_org', $args);
    add_theme_support('post-thumbnails', ['page', 'cm_pn_org']);
  }

  /**
   * Add Organization dashboard metabox.
   *
   * @since    1.0.0
   */
  public function cm_pn_org_add_meta_box() {
    add_meta_box('customers_manager_pn_meta_box', esc_html(__('Organization details', 'customers-manager-pn')), [$this, 'cm_pn_org_meta_box_function'], 'cm_pn_org', 'normal', 'high', ['__block_editor_compatible_meta_box' => true,]);
  }

  /**
   * Defines Organization dashboard contents.
   *
   * @since    1.0.0
   */
  public function cm_pn_org_meta_box_function($post) {
    foreach (self::cm_pn_org_get_fields_meta($post->ID) as $customers_manager_pn_field) {
      if (!is_null(cm_pn_forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_field, 'post', $post->ID))) {
        echo wp_kses(cm_pn_forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_field, 'post', $post->ID), customers_manager_pn_KSES);
      }
    }
  }

  /**
   * Defines single template for Organization.
   *
   * @since    1.0.0
   */
  public function cm_pn_org_single_template($single) {
    if (get_post_type() == 'cm_pn_org') {
      if (file_exists(CUSTOMERS_MANAGER_PN_DIR . 'templates/public/single-cm_pn_org.php')) {
        return CUSTOMERS_MANAGER_PN_DIR . 'templates/public/single-cm_pn_org.php';
      }
    }

    return $single;
  }

  /**
   * Defines archive template for Organization.
   *
   * @since    1.0.0
   */
  public function cm_pn_org_archive_template($archive) {
    if (get_post_type() == 'cm_pn_org') {
      if (file_exists(CUSTOMERS_MANAGER_PN_DIR . 'templates/public/archive-cm_pn_org.php')) {
        return CUSTOMERS_MANAGER_PN_DIR . 'templates/public/archive-cm_pn_org.php';
      }
    }

    return $archive;
  }

  public function cm_pn_org_save_post($post_id, $cpt, $update) {
    if($cpt->post_type == 'cm_pn_org' && array_key_exists('cm_pn_org_form', $_POST)){
      // Always require nonce verification
      if (!array_key_exists('customers_manager_pn_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'customers_manager_pn_nonce_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'customers-manager-pn')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['customers_manager_pn_ajax_nonce'])), 'customers-manager-pn-nonce')) {
        echo wp_json_encode([
          'error_key' => 'customers_manager_pn_nonce_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'customers-manager-pn')),
        ]);

        exit;
      }

      if (!array_key_exists('customers_manager_pn_duplicate', $_POST)) {
        foreach (array_merge(self::cm_pn_org_get_fields(), self::cm_pn_org_get_fields_meta($post_id)) as $customers_manager_pn_field) {
          $customers_manager_pn_input = array_key_exists('input', $customers_manager_pn_field) ? $customers_manager_pn_field['input'] : '';

          if (array_key_exists($customers_manager_pn_field['id'], $_POST) || $customers_manager_pn_input == 'html_multi') {
            $customers_manager_pn_value = array_key_exists($customers_manager_pn_field['id'], $_POST) ? 
              cm_pn_forms::customers_manager_pn_sanitizer(
                wp_unslash($_POST[$customers_manager_pn_field['id']]),
                $customers_manager_pn_field['input'], 
                !empty($customers_manager_pn_field['type']) ? $customers_manager_pn_field['type'] : '',
                $customers_manager_pn_field // Pass the entire field config
              ) : '';

            if (!empty($customers_manager_pn_input)) {
              switch ($customers_manager_pn_input) {
                case 'input':
                  if (array_key_exists('type', $customers_manager_pn_field) && $customers_manager_pn_field['type'] == 'checkbox') {
                    if (isset($_POST[$customers_manager_pn_field['id']])) {
                      update_post_meta($post_id, $customers_manager_pn_field['id'], $customers_manager_pn_value);
                    } else {
                      update_post_meta($post_id, $customers_manager_pn_field['id'], '');
                    }
                  } else {
                    update_post_meta($post_id, $customers_manager_pn_field['id'], $customers_manager_pn_value);
                  }

                  break;
                case 'select':
                  if (array_key_exists('multiple', $customers_manager_pn_field) && $customers_manager_pn_field['multiple']) {
                    $multi_array = [];
                    $empty = true;

                    foreach (wp_unslash($_POST[$customers_manager_pn_field['id']]) as $multi_value) {
                      $multi_array[] = cm_pn_forms::customers_manager_pn_sanitizer(
                        $multi_value, 
                        $customers_manager_pn_field['input'], 
                        !empty($customers_manager_pn_field['type']) ? $customers_manager_pn_field['type'] : '',
                        $customers_manager_pn_field // Pass the entire field config
                      );
                    }

                    update_post_meta($post_id, $customers_manager_pn_field['id'], $multi_array);
                  } else {
                    update_post_meta($post_id, $customers_manager_pn_field['id'], $customers_manager_pn_value);
                  }
                  
                  break;
                case 'html_multi':
                  foreach ($customers_manager_pn_field['html_multi_fields'] as $customers_manager_pn_multi_field) {
                    if (array_key_exists($customers_manager_pn_multi_field['id'], $_POST)) {
                      $multi_array = [];
                      $empty = true;

                      // Sanitize the POST data before using it
                      $sanitized_post_data = isset($_POST[$customers_manager_pn_multi_field['id']]) ? 
                        array_map(function($value) {
                            return sanitize_text_field(wp_unslash($value));
                        }, (array)$_POST[$customers_manager_pn_multi_field['id']]) : [];
                      
                      foreach ($sanitized_post_data as $multi_value) {
                        if (!empty($multi_value)) {
                          $empty = false;
                        }

                        $multi_array[] = cm_pn_forms::customers_manager_pn_sanitizer(
                          $multi_value, 
                          $customers_manager_pn_multi_field['input'], 
                          !empty($customers_manager_pn_multi_field['type']) ? $customers_manager_pn_multi_field['type'] : '',
                          $customers_manager_pn_multi_field // Pass the entire field config
                        );
                      }

                      if (!$empty) {
                        update_post_meta($post_id, $customers_manager_pn_multi_field['id'], $multi_array);
                      } else {
                        update_post_meta($post_id, $customers_manager_pn_multi_field['id'], '');
                      }
                    }
                  }

                  break;
                case 'tags':
                  // Handle tags field - save as array
                  $tags_array_field_name = $customers_manager_pn_field['id'] . '_tags_array';
                  if (array_key_exists($tags_array_field_name, $_POST)) {
                    $tags_json = cm_pn_forms::customers_manager_pn_sanitizer(
                      wp_unslash($_POST[$tags_array_field_name]),
                      'input',
                      'text',
                      $customers_manager_pn_field
                    );
                    
                    // Decode JSON and save as array
                    $tags_array = json_decode($tags_json, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($tags_array)) {
                      update_post_meta($post_id, $customers_manager_pn_field['id'], $tags_array);
                    } else {
                      // Fallback: treat as comma-separated string
                      $tags_string = cm_pn_forms::customers_manager_pn_sanitizer(
                        wp_unslash($_POST[$customers_manager_pn_field['id']]),
                        'input',
                        'text',
                        $customers_manager_pn_field
                      );
                      $tags_array = array_map('trim', explode(',', $tags_string));
                      $tags_array = array_filter($tags_array); // Remove empty values
                      update_post_meta($post_id, $customers_manager_pn_field['id'], $tags_array);
                    }
                  } else {
                    // Fallback: save the text input value as comma-separated array
                    $tags_string = cm_pn_forms::customers_manager_pn_sanitizer(
                      wp_unslash($_POST[$customers_manager_pn_field['id']]),
                      'input',
                      'text',
                      $customers_manager_pn_field
                    );
                    $tags_array = array_map('trim', explode(',', $tags_string));
                    $tags_array = array_filter($tags_array); // Remove empty values
                    update_post_meta($post_id, $customers_manager_pn_field['id'], $tags_array);
                  }
                  break;
                default:
                  update_post_meta($post_id, $customers_manager_pn_field['id'], $customers_manager_pn_value);
                  break;
              }
            }
          } else {
            update_post_meta($post_id, $customers_manager_pn_field['id'], '');
          }
        }
      }
    }
  }

  public function cm_pn_org_form_save($element_id, $key_value, $cm_pn_form_type, $cm_pn_form_subtype) {
    $post_type = !empty(get_post_type($element_id)) ? get_post_type($element_id) : 'cm_pn_org';

    if ($post_type == 'cm_pn_org') {
      switch ($cm_pn_form_type) {
        case 'post':
          switch ($cm_pn_form_subtype) {
            case 'post_new':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'customers_manager_pn_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $post_functions = new CUSTOMERS_MANAGER_PN_Functions_Post();
              $organization_id = $post_functions->customers_manager_pn_insert_post(esc_html($cm_pn_org_title), $cm_pn_org_description, '', sanitize_title(esc_html($cm_pn_org_title)), 'cm_pn_org', 'publish', get_current_user_id());

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($organization_id, $key, $value);
                }
              }

              break;
            case 'post_edit':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'customers_manager_pn_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $organization_id = $element_id;
              wp_update_post(['ID' => $organization_id, 'post_title' => $cm_pn_org_title, 'post_content' => $cm_pn_org_description,]);

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($organization_id, $key, $value);
                }
              }

              break;
          }
      }
    }
  }

  public function cm_pn_org_register_scripts() {
    if (!wp_script_is('customers-manager-pn-aux', 'registered')) {
      wp_register_script('customers-manager-pn-aux', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-aux.js', [], CUSTOMERS_MANAGER_PN_VERSION, true);
    }

    if (!wp_script_is('customers-manager-pn-forms', 'registered')) {
      wp_register_script('customers-manager-pn-forms', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-forms.js', [], CUSTOMERS_MANAGER_PN_VERSION, true);
    }
    
    if (!wp_script_is('customers-manager-pn-selector', 'registered')) {
      wp_register_script('customers-manager-pn-selector', CUSTOMERS_MANAGER_PN_URL . 'assets/js/customers-manager-pn-selector.js', [], CUSTOMERS_MANAGER_PN_VERSION, true);
    }
  }

  public function cm_pn_org_print_scripts() {
    wp_print_scripts(['customers-manager-pn-aux', 'customers-manager-pn-forms', 'customers-manager-pn-selector']);
  }

  public function cm_pn_org_list_wrapper() {
    // If user is not logged in, return only the call to action (no search/add buttons)
    if (!is_user_logged_in()) {
      return self::cm_pn_org_list();
    }

    ob_start();
    ?>
      <div class="customers-manager-pn-cpt-list customers-manager-pn-cm_pn_org-list customers-manager-pn-mb-50">
        <div class="customers-manager-pn-cpt-search-container customers-manager-pn-mb-20 customers-manager-pn-text-align-right">
          <div class="customers-manager-pn-cpt-search-wrapper">
            <input type="text" class="customers-manager-pn-cpt-search-input customers-manager-pn-input customers-manager-pn-display-none" placeholder="<?php esc_attr_e('Filter...', 'customers-manager-pn'); ?>" />
            <i class="material-icons-outlined customers-manager-pn-cpt-search-toggle customers-manager-pn-cursor-pointer customers-manager-pn-font-size-30 customers-manager-pn-vertical-align-middle customers-manager-pn-tooltip" title="<?php esc_attr_e('Search Organizations', 'customers-manager-pn'); ?>">search</i>
            
            <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-cm_pn_org-add" data-customers-manager-pn-ajax-type="cm_pn_org_new">
              <i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-font-size-30 customers-manager-pn-vertical-align-middle customers-manager-pn-tooltip" title="<?php esc_attr_e('Add new Organization', 'customers-manager-pn'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="customers-manager-pn-cpt-list-wrapper customers-manager-pn-cm_pn_org-list-wrapper">
          <?php echo wp_kses(self::cm_pn_org_list(), CUSTOMERS_MANAGER_PN_KSES); ?>
        </div>
      </div>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  /**
   * Register Gutenberg block for organization list.
   *
   * @since    1.0.0
   */
  public function register_organization_list_block() {
    if (!function_exists('register_block_type')) {
      return;
    }

    wp_register_script(
      'customers-manager-pn-organization-list-block',
      CUSTOMERS_MANAGER_PN_URL . 'assets/js/blocks/customers-manager-pn-organization-list.js',
      ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
      CUSTOMERS_MANAGER_PN_VERSION,
      true
    );

    if (function_exists('wp_set_script_translations')) {
      wp_set_script_translations('customers-manager-pn-organization-list-block', 'customers-manager-pn');
    }

    register_block_type('customers-manager-pn/organization-list', [
      'editor_script'   => 'customers-manager-pn-organization-list-block',
      'render_callback' => [$this, 'render_organization_list_block'],
      'attributes'      => [
        'showSearch'   => [
          'type'    => 'boolean',
          'default' => true,
        ],
        'showAddButton' => [
          'type'    => 'boolean',
          'default' => true,
        ],
        'postsPerPage' => [
          'type'    => 'number',
          'default' => 10,
        ],
      ],
    ]);
  }

  /**
   * Render callback for the organization list Gutenberg block.
   *
   * @param array $attributes Block attributes.
   * @return string
   */
  public function render_organization_list_block($attributes = []) {
    // The shortcode handles all the logic, we just render it
    return do_shortcode('[customers-manager-pn-organization-list]');
  }

  public function cm_pn_org_list() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
      // Check if userspn plugin is active
      $userspn_active = false;
      
      // First, try to check if plugin.php is available and use is_plugin_active
      if (!function_exists('is_plugin_active') && file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
      }
      
      if (function_exists('is_plugin_active')) {
        $userspn_active = is_plugin_active('userspn/userspn.php') || is_plugin_active('users-pn/users-pn.php');
      }
      
      // Fallback: check if userspn class or function exists
      if (!$userspn_active) {
        $userspn_active = class_exists('USERSPN') || class_exists('USERS_PN') || function_exists('userspn_profile_popup');
      }

      // Prepare call to action parameters
      $cta_atts = [
        'customers_manager_pn_call_to_action_class' => 'customers-manager-pn-p-50 customers-manager-pn-pt-30 customers-manager-pn-max-width-700 customers-manager-pn-margin-auto',
        'customers_manager_pn_call_to_action_icon' => 'admin_panel_settings',
        'customers_manager_pn_call_to_action_title' => __('You need an account', 'customers-manager-pn'),
        'customers_manager_pn_call_to_action_content' => __('You must be registered on the platform to access this tool.', 'customers-manager-pn'),
        'customers_manager_pn_call_to_action_button_text' => __('Create an account', 'customers-manager-pn'),
      ];

      if ($userspn_active) {
        // If userspn is active, use popup button
        $cta_atts['customers_manager_pn_call_to_action_button_link'] = '#';
        $cta_atts['customers_manager_pn_call_to_action_button_class'] = 'userspn-profile-popup-btn';
        $cta_atts['customers_manager_pn_call_to_action_button_data_key'] = 'data-userspn-action';
        $cta_atts['customers_manager_pn_call_to_action_button_data_value'] = 'register';
      } else {
        // If userspn is not active, use WordPress registration URL
        if (function_exists('wp_registration_url')) {
          $registration_url = wp_registration_url();
        } else {
          $registration_url = wp_login_url();
          $registration_url = add_query_arg('action', 'register', $registration_url);
        }
        $cta_atts['customers_manager_pn_call_to_action_button_link'] = $registration_url;
        $cta_atts['customers_manager_pn_call_to_action_button_class'] = '';
      }

      // Build shortcode attributes string
      $shortcode_atts = '';
      foreach ($cta_atts as $key => $value) {
        $shortcode_atts .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
      }

      // Return call to action
      return do_shortcode('[customers-manager-pn-call-to-action' . $shortcode_atts . ']');
    }

    $organization_atts = [
      'fields' => 'ids',
      'numberposts' => -1,
      'post_type' => 'cm_pn_org',
      'post_status' => 'any', 
      'orderby' => 'menu_order', 
      'order' => 'ASC', 
    ];
    
    if (class_exists('Polylang')) {
      $organization_atts['lang'] = pll_current_language('slug');
    }

    $organization = get_posts($organization_atts);

    // Filter assets based on user permissions
    $organization = CUSTOMERS_MANAGER_PN_Functions_User::customers_manager_pn_filter_user_posts($organization, 'cm_pn_org');

    ob_start();
    ?>
      <ul class="customers-manager-pn-organizations customers-manager-pn-list-style-none customers-manager-pn-p-0 customers-manager-pn-margin-auto">
        <?php if (!empty($organization)): ?>
          <?php foreach ($organization as $organization_id): ?>
            <li class="customers-manager-pn-organization customers-manager-pn-cm_pn_org-list-item customers-manager-pn-mb-10" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
              <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-60-percent">
                  <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-cm_pn_org-view" data-customers-manager-pn-ajax-type="cm_pn_org_view" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
                    <span><?php echo esc_html(get_the_title($organization_id)); ?></span>
                  </a>
                </div>

                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right customers-manager-pn-position-relative">
                  <i class="material-icons-outlined customers-manager-pn-menu-more-btn customers-manager-pn-cursor-pointer customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30">more_vert</i>

                  <div class="customers-manager-pn-menu-more customers-manager-pn-z-index-99 customers-manager-pn-display-none-soft">
                    <ul class="customers-manager-pn-list-style-none">
                      <li>
                        <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-cm_pn_org-view" data-customers-manager-pn-ajax-type="cm_pn_org_view" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
                          <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                              <p><?php esc_html_e('View Organization', 'customers-manager-pn'); ?></p>
                            </div>
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right">
                              <i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-ml-30">visibility</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-cm_pn_org-edit" data-customers-manager-pn-ajax-type="cm_pn_org_edit" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>"> 
                          <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                              <p><?php esc_html_e('Edit Organization', 'customers-manager-pn'); ?></p>
                            </div>
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right">
                              <i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-ml-30">edit</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="customers-manager-pn-cm_pn_org-duplicate-post">
                          <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                              <p><?php esc_html_e('Duplicate Organization', 'customers-manager-pn'); ?></p>
                            </div>
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right">
                              <i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-ml-30">copy</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="customers-manager-pn-popup-open" data-customers-manager-pn-popup-id="customers-manager-pn-popup-cm_pn_org-remove">
                          <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-70-percent">
                              <p><?php esc_html_e('Remove Organization', 'customers-manager-pn'); ?></p>
                            </div>
                            <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-text-align-right">
                              <i class="material-icons-outlined customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-ml-30">delete</i>
                            </div>
                          </div>
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </li>
          <?php endforeach ?>
        <?php endif ?>

        <li class="customers-manager-pn-add-new-cpt customers-manager-pn-mt-50 customers-manager-pn-organization" data-cm_pn_org-id="0">
          <?php if (is_user_logged_in()): ?>
            <a href="#" class="customers-manager-pn-popup-open-ajax customers-manager-pn-text-decoration-none" data-customers-manager-pn-popup-id="customers-manager-pn-popup-cm_pn_org-add" data-customers-manager-pn-ajax-type="cm_pn_org_new">
              <div class="customers-manager-pn-display-table customers-manager-pn-width-100-percent">
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-20-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent customers-manager-pn-text-align-center">
                  <i class="material-icons-outlined customers-manager-pn-cursor-pointer customers-manager-pn-vertical-align-middle customers-manager-pn-font-size-30 customers-manager-pn-width-25">add</i>
                </div>
                <div class="customers-manager-pn-display-inline-table customers-manager-pn-width-80-percent customers-manager-pn-tablet-display-block customers-manager-pn-tablet-width-100-percent">
                  <?php esc_html_e('Add new Organization', 'customers-manager-pn'); ?>
                </div>
              </div>
            </a>
          <?php endif ?>
        </li>
      </ul>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function cm_pn_org_view($organization_id) {  
    ob_start();
    self::cm_pn_org_register_scripts();
    self::cm_pn_org_print_scripts();
    ?>
      <div class="cm_pn_org-view customers-manager-pn-p-30" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
        <h4 class="customers-manager-pn-text-align-center"><?php echo esc_html(get_the_title($organization_id)); ?></h4>
        
        <div class="customers-manager-pn-word-wrap-break-word">
          <p><?php echo wp_kses(str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($organization_id)->post_content)), CUSTOMERS_MANAGER_PN_KSES); ?></p>
        </div>

        <div class="cm_pn_org-view-list">
          <?php foreach (array_merge(self::cm_pn_org_get_fields(), self::cm_pn_org_get_fields_meta($organization_id)) as $customers_manager_pn_field): ?>
            <?php echo wp_kses(cm_pn_forms::customers_manager_pn_input_display_wrapper($customers_manager_pn_field, 'post', $organization_id), CUSTOMERS_MANAGER_PN_KSES); ?>
          <?php endforeach ?>

          <div class="customers-manager-pn-text-align-right customers-manager-pn-organization" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
            <a href="#" class="customers-manager-pn-btn customers-manager-pn-btn-mini customers-manager-pn-popup-open-ajax" data-customers-manager-pn-popup-id="customers-manager-pn-popup-cm_pn_org-edit" data-customers-manager-pn-ajax-type="cm_pn_org_edit"><?php esc_html_e('Edit Organization', 'customers-manager-pn'); ?></a>
          </div>
        </div>
      </div>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function cm_pn_org_new() {
    if (!is_user_logged_in()) {
      wp_die(esc_html__('You must be logged in to create a new asset.', 'customers-manager-pn'), esc_html__('Access Denied', 'customers-manager-pn'), ['response' => 403]);
    }

    ob_start();
    self::cm_pn_org_register_scripts();
    self::cm_pn_org_print_scripts();
    ?>
      <div class="cm_pn_org-new customers-manager-pn-p-30">
        <h4 class="customers-manager-pn-mb-30"><?php esc_html_e('Add new Organization', 'customers-manager-pn'); ?></h4>

        <form action="" method="post" id="customers-manager-pn-organization-form-new" class="customers-manager-pn-form">      
          <?php foreach (array_merge(self::cm_pn_org_get_fields(), self::cm_pn_org_get_fields_meta(0)) as $customers_manager_pn_field): ?>
            <?php echo wp_kses(cm_pn_forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_field, 'post'), CUSTOMERS_MANAGER_PN_KSES); ?>
          <?php endforeach ?>

          <div class="customers-manager-pn-text-align-right">
            <input class="customers-manager-pn-btn" data-customers-manager-pn-type="post" data-customers-manager-pn-subtype="post_new" data-customers-manager-pn-post-type="cm_pn_org" type="submit" value="<?php esc_attr_e('Create Organization', 'customers-manager-pn'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function cm_pn_org_edit($organization_id) {
    ob_start();
    self::cm_pn_org_register_scripts();
    self::cm_pn_org_print_scripts();
    ?>
      <div class="cm_pn_org-edit customers-manager-pn-p-30">
        <p class="customers-manager-pn-text-align-center customers-manager-pn-mb-0"><?php esc_html_e('Editing', 'customers-manager-pn'); ?></p>
        <h4 class="customers-manager-pn-text-align-center customers-manager-pn-mb-30"><?php echo esc_html(get_the_title($organization_id)); ?></h4>

        <form action="" method="post" id="customers-manager-pn-organization-form-edit" class="customers-manager-pn-form">      
          <?php foreach (array_merge(self::cm_pn_org_get_fields(), self::cm_pn_org_get_fields_meta($organization_id)) as $customers_manager_pn_field): ?>
            <?php echo wp_kses(cm_pn_forms::customers_manager_pn_input_wrapper_builder($customers_manager_pn_field, 'post', $organization_id), CUSTOMERS_MANAGER_PN_KSES); ?>
          <?php endforeach ?>

          <div class="customers-manager-pn-text-align-right">
            <input class="customers-manager-pn-btn" type="submit" data-customers-manager-pn-type="post" data-customers-manager-pn-subtype="post_edit" data-customers-manager-pn-post-type="cm_pn_org" data-customers-manager-pn-post-id="<?php echo esc_attr($organization_id); ?>" value="<?php esc_attr_e('Save Organization', 'customers-manager-pn'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $customers_manager_pn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $customers_manager_pn_return_string;
  }

  public function cm_pn_org_history_add($organization_id) {  
    $customers_manager_pn_meta = get_post_meta($organization_id);
    $customers_manager_pn_meta_array = [];

    if (!empty($customers_manager_pn_meta)) {
      foreach ($customers_manager_pn_meta as $customers_manager_pn_meta_key => $customers_manager_pn_meta_value) {
        if (strpos((string)$customers_manager_pn_meta_key, 'customers_manager_pn_') !== false && !empty($customers_manager_pn_meta_value[0])) {
          $customers_manager_pn_meta_array[$customers_manager_pn_meta_key] = $customers_manager_pn_meta_value[0];
        }
      }
    }
    
    if(empty(get_post_meta($organization_id, 'cm_pn_org_history', true))) {
      update_post_meta($organization_id, 'cm_pn_org_history', [strtotime('now') => $customers_manager_pn_meta_array]);
    } else {
      $customers_manager_pn_post_meta_new = get_post_meta($organization_id, 'cm_pn_org_history', true);
      $customers_manager_pn_post_meta_new[strtotime('now')] = $customers_manager_pn_meta_array;
      update_post_meta($organization_id, 'cm_pn_org_history', $customers_manager_pn_post_meta_new);
    }
  }

  public function cm_pn_org_get_next($organization_id) {
    $cm_pn_org_periodicity = get_post_meta($organization_id, 'cm_pn_org_periodicity', true);
    $cm_pn_org_date = get_post_meta($organization_id, 'cm_pn_org_date', true);
    $cm_pn_org_time = get_post_meta($organization_id, 'cm_pn_org_time', true);

    $cm_pn_org_timestamp = strtotime($cm_pn_org_date . ' ' . $cm_pn_org_time);

    if (!empty($cm_pn_org_periodicity) && !empty($cm_pn_org_timestamp)) {
      $now = strtotime('now');

      while ($cm_pn_org_timestamp < $now) {
        $cm_pn_org_timestamp = strtotime('+' . str_replace('_', ' ', $cm_pn_org_periodicity), $cm_pn_org_timestamp);
      }

      return $cm_pn_org_timestamp;
    }
  }

  public function cm_pn_org_owners($organization_id) {
    $customers_manager_pn_owners = get_post_meta($organization_id, 'customers_manager_pn_owners', true);
    $customers_manager_pn_owners_array = [get_post($organization_id)->post_author];

    if (!empty($customers_manager_pn_owners)) {
      foreach ($customers_manager_pn_owners as $owner_id) {
        $customers_manager_pn_owners_array[] = $owner_id;
      }
    }

    return array_unique($customers_manager_pn_owners_array);
  }
}

