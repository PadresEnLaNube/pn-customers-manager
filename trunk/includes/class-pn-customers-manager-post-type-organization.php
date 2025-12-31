<?php
/**
 * Organization creator.
 *
 * This class defines Organization options, menus and templates.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    PN_CUSTOMERS_MANAGER
 * @subpackage PN_CUSTOMERS_MANAGER/includes
 * @author     Padres en la Nube
 */
class PN_CUSTOMERS_MANAGER_Post_Type_organization {
  public function cm_pn_org_get_fields($organization_id = 0) {
    $PN_CUSTOMERS_MANAGER_fields = [];
      $PN_CUSTOMERS_MANAGER_fields['cm_pn_org_title'] = [
        'id' => 'cm_pn_org_title',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'required' => true,
        'value' => !empty($organization_id) ? esc_html(get_the_title($organization_id)) : '',
        'label' => __('Organization title', 'pn-customers-manager'),
        'placeholder' => __('Organization title', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields['cm_pn_org_description'] = [
        'id' => 'cm_pn_org_description',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'textarea',
        'required' => true,
        'value' => !empty($organization_id) ? (str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($organization_id)->post_content))) : '',
        'label' => __('Organization description', 'pn-customers-manager'),
        'placeholder' => __('Organization description', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['PN_CUSTOMERS_MANAGER_ajax_nonce'] = [
        'id' => 'PN_CUSTOMERS_MANAGER_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $PN_CUSTOMERS_MANAGER_fields;
  }

  /**
   * Build a list of WP users to populate owner/collaborator selects.
   *
   * @param bool $include_placeholder Include the default "select" option.
   * @return array
   */
  private function PN_CUSTOMERS_MANAGER_get_owner_select_options($include_placeholder = true) {
    $options = $include_placeholder ? ['' => esc_html__('Select a user', 'pn-customers-manager')] : [];

    $users = get_users([
      'fields' => ['ID', 'display_name', 'user_email'],
      'orderby' => 'display_name',
      'order' => 'ASC',
    ]);

    if (!empty($users)) {
      foreach ($users as $user) {
        $label = '';

        if (class_exists('PN_CUSTOMERS_MANAGER_Functions_User')) {
          $label = PN_CUSTOMERS_MANAGER_Functions_User::PN_CUSTOMERS_MANAGER_user_get_name($user->ID);
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
  private static function PN_CUSTOMERS_MANAGER_get_funnel_select_options($organization_id = 0) {
    $options = ['' => esc_html__('Select a funnel', 'pn-customers-manager')];

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
  private static function PN_CUSTOMERS_MANAGER_get_funnel_stage_options($organization_id = 0) {
    $options = ['' => esc_html__('Select a funnel stage', 'pn-customers-manager')];

    if (empty($organization_id) || !class_exists('PN_CUSTOMERS_MANAGER_Post_Type_Funnel')) {
      return $options;
    }

    $funnel_id = intval(get_post_meta($organization_id, 'cm_pn_org_funnel_id', true));

    if (empty($funnel_id)) {
      return $options;
    }

    $stages = PN_CUSTOMERS_MANAGER_Post_Type_Funnel::PN_CUSTOMERS_MANAGER_get_funnel_stages_list($funnel_id);

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
  private static function PN_CUSTOMERS_MANAGER_get_funnel_status_options($include_placeholder = true) {
    $options = $include_placeholder ? ['' => esc_html__('Select a funnel status', 'pn-customers-manager')] : [];

    $statuses = [
      'not_started' => esc_html__('Not started', 'pn-customers-manager'),
      'in_progress' => esc_html__('In progress', 'pn-customers-manager'),
      'stalled'     => esc_html__('Stalled / needs attention', 'pn-customers-manager'),
      'won'         => esc_html__('Won', 'pn-customers-manager'),
      'lost'        => esc_html__('Lost', 'pn-customers-manager'),
    ];

    return $options + $statuses;
  }

  /**
   * Return the array of user IDs linked as contacts.
   *
   * @param int $organization_id
   * @return array
   */
  private static function PN_CUSTOMERS_MANAGER_get_organization_contacts($organization_id) {
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
    if (class_exists('PN_CUSTOMERS_MANAGER_Functions_User')) {
      return PN_CUSTOMERS_MANAGER_Functions_User::PN_CUSTOMERS_MANAGER_user_get_name($user_id);
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
  private static function PN_CUSTOMERS_MANAGER_render_contacts_list($organization_id) {
    $contacts = self::PN_CUSTOMERS_MANAGER_get_organization_contacts($organization_id);

    if (empty($contacts)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('No contacts linked yet.', 'pn-customers-manager') . '</p>';
    }

    ob_start();
    ?>
      <ul class="pn-customers-manager-list-style-none pn-customers-manager-m-0 pn-customers-manager-p-0">
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
          <li class="pn-customers-manager-mb-10">
            <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                <a href="<?php echo esc_url($edit_link); ?>" target="_blank" class="pn-customers-manager-text-decoration-none">
                  <strong><?php echo esc_html($display_name); ?></strong>
                  <?php if (!empty($email)): ?>
                    <br><small><?php echo esc_html($email); ?></small>
                  <?php endif; ?>
                </a>
              </div>
              <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-30-percent pn-customers-manager-text-align-right">
                <a href="#" 
                   class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-remove-contact" 
                   data-contact-id="<?php echo esc_attr($contact_id); ?>"
                   data-org-alt-id="<?php echo esc_attr($organization_id); ?>">
                  <?php esc_html_e('Remove', 'pn-customers-manager'); ?>
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
  private function PN_CUSTOMERS_MANAGER_render_contacts_field($organization_id) {
    if (empty($organization_id)) {
      return '<p class="pn-customers-manager-m-0">' . esc_html__('Save the organization to start adding contacts.', 'pn-customers-manager') . '</p>';
    }

    $list = self::PN_CUSTOMERS_MANAGER_render_contacts_list($organization_id);

    ob_start();
    ?>
      <div class="pn-customers-manager-organization-contacts-field" data-cm_pn_org_alt-id="<?php echo esc_attr($organization_id); ?>">
        <div class="pn-customers-manager-organization-contacts-list">
          <?php echo wp_kses($list, PN_CUSTOMERS_MANAGER_KSES); ?>
        </div>
        <div class="pn-customers-manager-text-align-right pn-customers-manager-mt-15">
          <a href="#"
             class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-popup-open-ajax"
             data-pn-customers-manager-popup-id="pn-customers-manager-popup-PN_CUSTOMERS_MANAGER_contact-add"
             data-pn-customers-manager-ajax-type="PN_CUSTOMERS_MANAGER_contact_new"
             data-cm_pn_org_alt-id="<?php echo esc_attr($organization_id); ?>">
            <?php esc_html_e('Add contact', 'pn-customers-manager'); ?>
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
    $PN_CUSTOMERS_MANAGER_fields_meta = [];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_section_basic_start'] = [
        'id' => 'cm_pn_org_section_basic_start',
        'section' => 'start',
        'label' => esc_html__('Basic organization data', 'pn-customers-manager'),
        'description' => esc_html__('Essential information to identify and contact the organization.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_contacts_block'] = [
        'id' => 'cm_pn_org_contacts_block',
        'input' => 'html',
        'skip_save' => true,
        'label' => esc_html__('Linked contacts', 'pn-customers-manager'),
        'html_content' => $this->PN_CUSTOMERS_MANAGER_render_contacts_field($organization_id),
        'description' => esc_html__('Each contact is a regular WordPress user so you can edit it from the Users screen.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_legal_name'] = [
        'id' => 'cm_pn_org_legal_name',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Legal name', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. Company Inc.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_trade_name'] = [
        'id' => 'cm_pn_org_trade_name',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Trade name', 'pn-customers-manager'),
        'placeholder' => esc_html__('Common brand name', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_segment'] = [
        'id' => 'cm_pn_org_segment',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Segment', 'pn-customers-manager'),
        'placeholder' => esc_html__('Select a segment', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a segment', 'pn-customers-manager'),
          'startup' => esc_html__('Startup', 'pn-customers-manager'),
          'smb' => esc_html__('Small / mid-sized business', 'pn-customers-manager'),
          'enterprise' => esc_html__('Enterprise', 'pn-customers-manager'),
          'nonprofit' => esc_html__('Nonprofit / third sector', 'pn-customers-manager'),
          'government' => esc_html__('Public sector', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_industry'] = [
        'id' => 'cm_pn_org_industry',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Industry', 'pn-customers-manager'),
        'placeholder' => esc_html__('Select an industry', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select an industry', 'pn-customers-manager'),
          'software' => esc_html__('Software / SaaS', 'pn-customers-manager'),
          'services' => esc_html__('Professional services', 'pn-customers-manager'),
          'manufacturing' => esc_html__('Manufacturing', 'pn-customers-manager'),
          'education' => esc_html__('Education', 'pn-customers-manager'),
          'health' => esc_html__('Healthcare', 'pn-customers-manager'),
          'finance' => esc_html__('Financial services', 'pn-customers-manager'),
          'retail' => esc_html__('Retail / eCommerce', 'pn-customers-manager'),
          'other' => esc_html__('Other', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_team_size'] = [
        'id' => 'cm_pn_org_team_size',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Team size', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a range', 'pn-customers-manager'),
          '1-10' => esc_html__('1 - 10 people', 'pn-customers-manager'),
          '11-50' => esc_html__('11 - 50 people', 'pn-customers-manager'),
          '51-200' => esc_html__('51 - 200 people', 'pn-customers-manager'),
          '201-500' => esc_html__('201 - 500 people', 'pn-customers-manager'),
          '500+' => esc_html__('More than 500 people', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_annual_revenue'] = [
        'id' => 'cm_pn_org_annual_revenue',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Annual revenue', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a range', 'pn-customers-manager'),
          '<250k' => esc_html__('Up to €250k', 'pn-customers-manager'),
          '250k-1m' => esc_html__('€250k - €1M', 'pn-customers-manager'),
          '1m-5m' => esc_html__('€1M - €5M', 'pn-customers-manager'),
          '5m-20m' => esc_html__('€5M - €20M', 'pn-customers-manager'),
          '>20m' => esc_html__('More than €20M', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_phone'] = [
        'id' => 'cm_pn_org_phone',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'tel',
        'label' => esc_html__('Primary phone', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. +1 555 123 4567', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_email'] = [
        'id' => 'cm_pn_org_email',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'email',
        'label' => esc_html__('Primary email', 'pn-customers-manager'),
        'placeholder' => esc_html__('contact@company.com', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_website'] = [
        'id' => 'cm_pn_org_website',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'url',
        'label' => esc_html__('Website', 'pn-customers-manager'),
        'placeholder' => esc_html__('https://company.com', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_linkedin'] = [
        'id' => 'cm_pn_org_linkedin',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'url',
        'label' => esc_html__('LinkedIn profile', 'pn-customers-manager'),
        'placeholder' => esc_html__('https://www.linkedin.com/company/...', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_country'] = [
        'id' => 'cm_pn_org_country',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Country', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. United States', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_region'] = [
        'id' => 'cm_pn_org_region',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Region / State', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. California', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_city'] = [
        'id' => 'cm_pn_org_city',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('City', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. San Francisco', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_address'] = [
        'id' => 'cm_pn_org_address',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Address', 'pn-customers-manager'),
        'placeholder' => esc_html__('Street, number, suite…', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_postal_code'] = [
        'id' => 'cm_pn_org_postal_code',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Postal code', 'pn-customers-manager'),
        'placeholder' => esc_html__('e.g. 94105', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_section_basic_end'] = [
        'id' => 'cm_pn_org_section_basic_end',
        'section' => 'end',
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_section_advanced_start'] = [
        'id' => 'cm_pn_org_section_advanced_start',
        'section' => 'start',
        'label' => esc_html__('Advanced CRM fields', 'pn-customers-manager'),
        'description' => esc_html__('Strategic data to segment, prioritize, and plan commercial follow-up.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_fiscal_id'] = [
        'id' => 'cm_pn_org_fiscal_id',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Tax ID (VAT / EIN)', 'pn-customers-manager'),
        'placeholder' => esc_html__('Fiscal identifier', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_lead_source'] = [
        'id' => 'cm_pn_org_lead_source',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Lead source', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a source', 'pn-customers-manager'),
          'website' => esc_html__('Website / SEO', 'pn-customers-manager'),
          'ads' => esc_html__('Paid campaigns', 'pn-customers-manager'),
          'event' => esc_html__('Event', 'pn-customers-manager'),
          'referral' => esc_html__('Referral', 'pn-customers-manager'),
          'outbound' => esc_html__('Outbound prospecting', 'pn-customers-manager'),
          'partner' => esc_html__('Partner', 'pn-customers-manager'),
          'other' => esc_html__('Other source', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_lifecycle_stage'] = [
        'id' => 'cm_pn_org_lifecycle_stage',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Lifecycle stage', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a lifecycle stage', 'pn-customers-manager'),
          'lead' => esc_html__('Lead', 'pn-customers-manager'),
          'marketing_qualified' => esc_html__('Marketing qualified (MQL)', 'pn-customers-manager'),
          'sales_qualified' => esc_html__('Sales qualified (SQL)', 'pn-customers-manager'),
          'opportunity' => esc_html__('Opportunity', 'pn-customers-manager'),
          'customer' => esc_html__('Customer', 'pn-customers-manager'),
          'churned' => esc_html__('Churned', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_pipeline_stage'] = [
        'id' => 'cm_pn_org_pipeline_stage',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Pipeline stage', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a pipeline stage', 'pn-customers-manager'),
          'qualification' => esc_html__('Qualification', 'pn-customers-manager'),
          'discovery' => esc_html__('Discovery', 'pn-customers-manager'),
          'proposal' => esc_html__('Proposal', 'pn-customers-manager'),
          'negotiation' => esc_html__('Negotiation', 'pn-customers-manager'),
          'closed_won' => esc_html__('Closed won', 'pn-customers-manager'),
          'closed_lost' => esc_html__('Closed lost', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_priority'] = [
        'id' => 'cm_pn_org_priority',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Commercial priority', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a priority', 'pn-customers-manager'),
          'high' => esc_html__('High', 'pn-customers-manager'),
          'medium' => esc_html__('Medium', 'pn-customers-manager'),
          'low' => esc_html__('Low', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_health'] = [
        'id' => 'cm_pn_org_health',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Account health', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a health status', 'pn-customers-manager'),
          'healthy' => esc_html__('Healthy / growing', 'pn-customers-manager'),
          'risk' => esc_html__('At risk', 'pn-customers-manager'),
          'churn' => esc_html__('Possible churn', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_lead_score'] = [
        'id' => 'cm_pn_org_lead_score',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'range',
        'min' => 0,
        'max' => 100,
        'step' => 5,
        'label' => esc_html__('Lead score', 'pn-customers-manager'),
        'description' => esc_html__('0 = cold lead, 100 = hot lead.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_owner'] = [
        'id' => 'cm_pn_org_owner',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Primary owner', 'pn-customers-manager'),
        'options' => $this->PN_CUSTOMERS_MANAGER_get_owner_select_options(),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_collaborators'] = [
        'id' => 'cm_pn_org_collaborators',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'multiple' => true,
        'label' => esc_html__('Assigned collaborators', 'pn-customers-manager'),
        'description' => esc_html__('Select other team members involved with the account.', 'pn-customers-manager'),
        'options' => $this->PN_CUSTOMERS_MANAGER_get_owner_select_options(true),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_last_contact_date'] = [
        'id' => 'cm_pn_org_last_contact_date',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => esc_html__('Last contact', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_last_contact_channel'] = [
        'id' => 'cm_pn_org_last_contact_channel',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Last contact channel', 'pn-customers-manager'),
        'options' => [
          '' => esc_html__('Select a channel', 'pn-customers-manager'),
          'email' => esc_html__('Email', 'pn-customers-manager'),
          'call' => esc_html__('Call', 'pn-customers-manager'),
          'meeting' => esc_html__('Meeting', 'pn-customers-manager'),
          'chat' => esc_html__('Chat / messaging', 'pn-customers-manager'),
          'event' => esc_html__('Event', 'pn-customers-manager'),
          'other' => esc_html__('Other', 'pn-customers-manager'),
        ],
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_next_action'] = [
        'id' => 'cm_pn_org_next_action',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'textarea',
        'label' => esc_html__('Próximo paso acordado', 'pn-customers-manager'),
        'placeholder' => esc_html__('Describe el siguiente hito o compromiso.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_billing_email'] = [
        'id' => 'cm_pn_org_billing_email',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'email',
        'label' => esc_html__('Email de facturación', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_billing_phone'] = [
        'id' => 'cm_pn_org_billing_phone',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'tel',
        'label' => esc_html__('Teléfono de facturación', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_billing_address'] = [
        'id' => 'cm_pn_org_billing_address',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Dirección de facturación', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_tags'] = [
        'id' => 'cm_pn_org_tags',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Etiquetas', 'pn-customers-manager'),
        'placeholder' => esc_html__('Ej: Clientes VIP, Renovación, Partner', 'pn-customers-manager'),
        'description' => esc_html__('Separa las etiquetas con comas.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_notes'] = [
        'id' => 'cm_pn_org_notes',
        'class' => 'pn-customers-manager-input pn-customers-manager-width-100-percent',
        'input' => 'textarea',
        'label' => esc_html__('Notas internas', 'pn-customers-manager'),
        'placeholder' => esc_html__('Contexto adicional para el equipo comercial.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_section_advanced_end'] = [
        'id' => 'cm_pn_org_section_advanced_end',
        'section' => 'end',
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_section_funnel_start'] = [
        'id' => 'cm_pn_org_section_funnel_start',
        'section' => 'start',
        'label' => esc_html__('Commercial funnel', 'pn-customers-manager'),
        'description' => esc_html__('Link this organization to a funnel and track its stage.', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_funnel_id'] = [
        'id' => 'cm_pn_org_funnel_id',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Assigned funnel', 'pn-customers-manager'),
        'options' => self::PN_CUSTOMERS_MANAGER_get_funnel_select_options($organization_id),
        'placeholder' => esc_html__('Select a funnel', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_funnel_stage'] = [
        'id' => 'cm_pn_org_funnel_stage',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Current stage', 'pn-customers-manager'),
        'options' => self::PN_CUSTOMERS_MANAGER_get_funnel_stage_options($organization_id),
        'placeholder' => esc_html__('Select a funnel stage', 'pn-customers-manager'),
        'description' => esc_html__('The available stages come from the funnel definition (one per line).', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_funnel_status'] = [
        'id' => 'cm_pn_org_funnel_status',
        'class' => 'pn-customers-manager-select pn-customers-manager-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Funnel status', 'pn-customers-manager'),
        'options' => self::PN_CUSTOMERS_MANAGER_get_funnel_status_options(),
        'placeholder' => esc_html__('Select a funnel status', 'pn-customers-manager'),
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_section_funnel_end'] = [
        'id' => 'cm_pn_org_section_funnel_end',
        'section' => 'end',
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['cm_pn_org_form'] = [
        'id' => 'cm_pn_org_form',
        'input' => 'input',
        'type' => 'hidden',
      ];
      $PN_CUSTOMERS_MANAGER_fields_meta['PN_CUSTOMERS_MANAGER_ajax_nonce'] = [
        'id' => 'PN_CUSTOMERS_MANAGER_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $PN_CUSTOMERS_MANAGER_fields_meta;
  }

  /**
   * Register Organization.
   *
   * @since    1.0.0
   */
  public function cm_pn_org_register_post_type() {
    $labels = [
      'name'                => _x('Organization', 'Post Type general name', 'pn-customers-manager'),
      'singular_name'       => _x('Organization', 'Post Type singular name', 'pn-customers-manager'),
      'menu_name'           => esc_html(__('Organizations', 'pn-customers-manager')),
      'parent_item_colon'   => esc_html(__('Parent Organization', 'pn-customers-manager')),
      'all_items'           => esc_html(__('All Organizations', 'pn-customers-manager')),
      'view_item'           => esc_html(__('View Organization', 'pn-customers-manager')),
      'add_new_item'        => esc_html(__('Add new Organization', 'pn-customers-manager')),
      'add_new'             => esc_html(__('Add new Organization', 'pn-customers-manager')),
      'edit_item'           => esc_html(__('Edit Organization', 'pn-customers-manager')),
      'update_item'         => esc_html(__('Update Organization', 'pn-customers-manager')),
      'search_items'        => esc_html(__('Search Organizations', 'pn-customers-manager')),
      'not_found'           => esc_html(__('Not Organization found', 'pn-customers-manager')),
      'not_found_in_trash'  => esc_html(__('Not Organization found in Trash', 'pn-customers-manager')),
    ];

    $args = [
      'labels'              => $labels,
      'rewrite'             => ['slug' => (!empty(get_option('cm_pn_org_slug')) ? get_option('cm_pn_org_slug') : 'pn-customers-manager'), 'with_front' => false],
      'label'               => esc_html(__('Organizations', 'pn-customers-manager')),
      'description'         => esc_html(__('Organization description', 'pn-customers-manager')),
      'supports'            => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'page-attributes', ],
      'hierarchical'        => true,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => false, // Menu added manually in settings
      'show_in_nav_menus'   => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 5,
      'menu_icon'           => esc_url(PN_CUSTOMERS_MANAGER_URL . 'assets/media/pn-customers-manager-funnel-menu-icon.svg'),
      'can_export'          => true,
      'has_archive'         => true,
      'exclude_from_search' => false,
      'publicly_queryable'  => true,
      'capability_type'     => 'page',
      'capabilities'        => PN_CUSTOMERS_MANAGER_ROLE_CM_PN_ORG_CAPABILITIES,
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
    add_meta_box('PN_CUSTOMERS_MANAGER_meta_box', esc_html(__('Organization details', 'pn-customers-manager')), [$this, 'cm_pn_org_meta_box_function'], 'cm_pn_org', 'normal', 'high', ['__block_editor_compatible_meta_box' => true,]);
  }

  /**
   * Defines Organization dashboard contents.
   *
   * @since    1.0.0
   */
  public function cm_pn_org_meta_box_function($post) {
    foreach (self::cm_pn_org_get_fields_meta($post->ID) as $PN_CUSTOMERS_MANAGER_field) {
      if (!is_null(cm_pn_forms::PN_CUSTOMERS_MANAGER_input_wrapper_builder($PN_CUSTOMERS_MANAGER_field, 'post', $post->ID))) {
        echo wp_kses(cm_pn_forms::PN_CUSTOMERS_MANAGER_input_wrapper_builder($PN_CUSTOMERS_MANAGER_field, 'post', $post->ID), PN_CUSTOMERS_MANAGER_KSES);
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
      if (file_exists(PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-cm_pn_org.php')) {
        return PN_CUSTOMERS_MANAGER_DIR . 'templates/public/single-cm_pn_org.php';
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
      if (file_exists(PN_CUSTOMERS_MANAGER_DIR . 'templates/public/archive-cm_pn_org.php')) {
        return PN_CUSTOMERS_MANAGER_DIR . 'templates/public/archive-cm_pn_org.php';
      }
    }

    return $archive;
  }

  public function cm_pn_org_save_post($post_id, $cpt, $update) {
    if($cpt->post_type == 'cm_pn_org' && array_key_exists('cm_pn_org_form', $_POST)){
      // Always require nonce verification
      if (!array_key_exists('PN_CUSTOMERS_MANAGER_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'PN_CUSTOMERS_MANAGER_nonce_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'pn-customers-manager')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['PN_CUSTOMERS_MANAGER_ajax_nonce'])), 'pn-customers-manager-nonce')) {
        echo wp_json_encode([
          'error_key' => 'PN_CUSTOMERS_MANAGER_nonce_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'pn-customers-manager')),
        ]);

        exit;
      }

      if (!array_key_exists('PN_CUSTOMERS_MANAGER_duplicate', $_POST)) {
        foreach (array_merge(self::cm_pn_org_get_fields(), self::cm_pn_org_get_fields_meta($post_id)) as $PN_CUSTOMERS_MANAGER_field) {
          $PN_CUSTOMERS_MANAGER_input = array_key_exists('input', $PN_CUSTOMERS_MANAGER_field) ? $PN_CUSTOMERS_MANAGER_field['input'] : '';

          if (array_key_exists($PN_CUSTOMERS_MANAGER_field['id'], $_POST) || $PN_CUSTOMERS_MANAGER_input == 'html_multi') {
            $PN_CUSTOMERS_MANAGER_value = array_key_exists($PN_CUSTOMERS_MANAGER_field['id'], $_POST) ? 
              cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                wp_unslash($_POST[$PN_CUSTOMERS_MANAGER_field['id']]),
                $PN_CUSTOMERS_MANAGER_field['input'], 
                !empty($PN_CUSTOMERS_MANAGER_field['type']) ? $PN_CUSTOMERS_MANAGER_field['type'] : '',
                $PN_CUSTOMERS_MANAGER_field // Pass the entire field config
              ) : '';

            if (!empty($PN_CUSTOMERS_MANAGER_input)) {
              switch ($PN_CUSTOMERS_MANAGER_input) {
                case 'input':
                  if (array_key_exists('type', $PN_CUSTOMERS_MANAGER_field) && $PN_CUSTOMERS_MANAGER_field['type'] == 'checkbox') {
                    if (isset($_POST[$PN_CUSTOMERS_MANAGER_field['id']])) {
                      update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], $PN_CUSTOMERS_MANAGER_value);
                    } else {
                      update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], '');
                    }
                  } else {
                    update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], $PN_CUSTOMERS_MANAGER_value);
                  }

                  break;
                case 'select':
                  if (array_key_exists('multiple', $PN_CUSTOMERS_MANAGER_field) && $PN_CUSTOMERS_MANAGER_field['multiple']) {
                    $multi_array = [];
                    $empty = true;

                    foreach (wp_unslash($_POST[$PN_CUSTOMERS_MANAGER_field['id']]) as $multi_value) {
                      $multi_array[] = cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                        $multi_value, 
                        $PN_CUSTOMERS_MANAGER_field['input'], 
                        !empty($PN_CUSTOMERS_MANAGER_field['type']) ? $PN_CUSTOMERS_MANAGER_field['type'] : '',
                        $PN_CUSTOMERS_MANAGER_field // Pass the entire field config
                      );
                    }

                    update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], $multi_array);
                  } else {
                    update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], $PN_CUSTOMERS_MANAGER_value);
                  }
                  
                  break;
                case 'html_multi':
                  foreach ($PN_CUSTOMERS_MANAGER_field['html_multi_fields'] as $PN_CUSTOMERS_MANAGER_multi_field) {
                    if (array_key_exists($PN_CUSTOMERS_MANAGER_multi_field['id'], $_POST)) {
                      $multi_array = [];
                      $empty = true;

                      // Sanitize the POST data before using it
                      $sanitized_post_data = isset($_POST[$PN_CUSTOMERS_MANAGER_multi_field['id']]) ? 
                        array_map(function($value) {
                            return sanitize_text_field(wp_unslash($value));
                        }, (array)$_POST[$PN_CUSTOMERS_MANAGER_multi_field['id']]) : [];
                      
                      foreach ($sanitized_post_data as $multi_value) {
                        if (!empty($multi_value)) {
                          $empty = false;
                        }

                        $multi_array[] = cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                          $multi_value, 
                          $PN_CUSTOMERS_MANAGER_multi_field['input'], 
                          !empty($PN_CUSTOMERS_MANAGER_multi_field['type']) ? $PN_CUSTOMERS_MANAGER_multi_field['type'] : '',
                          $PN_CUSTOMERS_MANAGER_multi_field // Pass the entire field config
                        );
                      }

                      if (!$empty) {
                        update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_multi_field['id'], $multi_array);
                      } else {
                        update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_multi_field['id'], '');
                      }
                    }
                  }

                  break;
                case 'tags':
                  // Handle tags field - save as array
                  $tags_array_field_name = $PN_CUSTOMERS_MANAGER_field['id'] . '_tags_array';
                  if (array_key_exists($tags_array_field_name, $_POST)) {
                    $tags_json = cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                      wp_unslash($_POST[$tags_array_field_name]),
                      'input',
                      'text',
                      $PN_CUSTOMERS_MANAGER_field
                    );
                    
                    // Decode JSON and save as array
                    $tags_array = json_decode($tags_json, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($tags_array)) {
                      update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], $tags_array);
                    } else {
                      // Fallback: treat as comma-separated string
                      $tags_string = cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                        wp_unslash($_POST[$PN_CUSTOMERS_MANAGER_field['id']]),
                        'input',
                        'text',
                        $PN_CUSTOMERS_MANAGER_field
                      );
                      $tags_array = array_map('trim', explode(',', $tags_string));
                      $tags_array = array_filter($tags_array); // Remove empty values
                      update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], $tags_array);
                    }
                  } else {
                    // Fallback: save the text input value as comma-separated array
                    $tags_string = cm_pn_forms::PN_CUSTOMERS_MANAGER_sanitizer(
                      wp_unslash($_POST[$PN_CUSTOMERS_MANAGER_field['id']]),
                      'input',
                      'text',
                      $PN_CUSTOMERS_MANAGER_field
                    );
                    $tags_array = array_map('trim', explode(',', $tags_string));
                    $tags_array = array_filter($tags_array); // Remove empty values
                    update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], $tags_array);
                  }
                  break;
                default:
                  update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], $PN_CUSTOMERS_MANAGER_value);
                  break;
              }
            }
          } else {
            update_post_meta($post_id, $PN_CUSTOMERS_MANAGER_field['id'], '');
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
                  if (strpos((string)$key, 'PN_CUSTOMERS_MANAGER_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $post_functions = new PN_CUSTOMERS_MANAGER_Functions_Post();
              $organization_id = $post_functions->PN_CUSTOMERS_MANAGER_insert_post(esc_html($cm_pn_org_title), $cm_pn_org_description, '', sanitize_title(esc_html($cm_pn_org_title)), 'cm_pn_org', 'publish', get_current_user_id());

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($organization_id, $key, $value);
                }
              }

              break;
            case 'post_edit':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'PN_CUSTOMERS_MANAGER_') !== false) {
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
    if (!wp_script_is('pn-customers-manager-aux', 'registered')) {
      wp_register_script('pn-customers-manager-aux', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-aux.js', [], PN_CUSTOMERS_MANAGER_VERSION, true);
    }

    if (!wp_script_is('pn-customers-manager-forms', 'registered')) {
      wp_register_script('pn-customers-manager-forms', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-forms.js', [], PN_CUSTOMERS_MANAGER_VERSION, true);
    }
    
    if (!wp_script_is('pn-customers-manager-selector', 'registered')) {
      wp_register_script('pn-customers-manager-selector', PN_CUSTOMERS_MANAGER_URL . 'assets/js/pn-customers-manager-selector.js', [], PN_CUSTOMERS_MANAGER_VERSION, true);
    }
  }

  public function cm_pn_org_print_scripts() {
    wp_print_scripts(['pn-customers-manager-aux', 'pn-customers-manager-forms', 'pn-customers-manager-selector']);
  }

  public function cm_pn_org_list_wrapper() {
    // If user is not logged in, return only the call to action (no search/add buttons)
    if (!is_user_logged_in()) {
      return self::cm_pn_org_list();
    }

    ob_start();
    ?>
      <div class="pn-customers-manager-cpt-list pn-customers-manager-cm_pn_org-list pn-customers-manager-mb-50">
        <div class="pn-customers-manager-cpt-search-container pn-customers-manager-mb-20 pn-customers-manager-text-align-right">
          <div class="pn-customers-manager-cpt-search-wrapper">
            <input type="text" class="pn-customers-manager-cpt-search-input pn-customers-manager-input pn-customers-manager-display-none" placeholder="<?php esc_attr_e('Filter...', 'pn-customers-manager'); ?>" />
            <i class="material-icons-outlined pn-customers-manager-cpt-search-toggle pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Search Organizations', 'pn-customers-manager'); ?>">search</i>
            
            <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-cm_pn_org-add" data-pn-customers-manager-ajax-type="cm_pn_org_new">
              <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-font-size-30 pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" title="<?php esc_attr_e('Add new Organization', 'pn-customers-manager'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="pn-customers-manager-cpt-list-wrapper pn-customers-manager-cm_pn_org-list-wrapper">
          <?php echo wp_kses(self::cm_pn_org_list(), PN_CUSTOMERS_MANAGER_KSES); ?>
        </div>
      </div>
    <?php
    $PN_CUSTOMERS_MANAGER_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $PN_CUSTOMERS_MANAGER_return_string;
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
      'pn-customers-manager-organization-list-block',
      PN_CUSTOMERS_MANAGER_URL . 'assets/js/blocks/pn-customers-manager-organization-list.js',
      ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
      PN_CUSTOMERS_MANAGER_VERSION,
      true
    );

    if (function_exists('wp_set_script_translations')) {
      wp_set_script_translations('pn-customers-manager-organization-list-block', 'pn-customers-manager');
    }

    register_block_type('pn-customers-manager/organization-list', [
      'editor_script'   => 'pn-customers-manager-organization-list-block',
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
    return do_shortcode('[pn-customers-manager-organization-list]');
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
        'PN_CUSTOMERS_MANAGER_call_to_action_class' => 'pn-customers-manager-p-50 pn-customers-manager-pt-30 pn-customers-manager-max-width-700 pn-customers-manager-margin-auto',
        'PN_CUSTOMERS_MANAGER_call_to_action_icon' => 'admin_panel_settings',
        'PN_CUSTOMERS_MANAGER_call_to_action_title' => __('You need an account', 'pn-customers-manager'),
        'PN_CUSTOMERS_MANAGER_call_to_action_content' => __('You must be registered on the platform to access this tool.', 'pn-customers-manager'),
        'PN_CUSTOMERS_MANAGER_call_to_action_button_text' => __('Create an account', 'pn-customers-manager'),
      ];

      if ($userspn_active) {
        // If userspn is active, use popup button
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_link'] = '#';
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_class'] = 'userspn-profile-popup-btn';
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_data_key'] = 'data-userspn-action';
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_data_value'] = 'register';
      } else {
        // If userspn is not active, use WordPress registration URL
        if (function_exists('wp_registration_url')) {
          $registration_url = wp_registration_url();
        } else {
          $registration_url = wp_login_url();
          $registration_url = add_query_arg('action', 'register', $registration_url);
        }
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_link'] = $registration_url;
        $cta_atts['PN_CUSTOMERS_MANAGER_call_to_action_button_class'] = '';
      }

      // Build shortcode attributes string
      $shortcode_atts = '';
      foreach ($cta_atts as $key => $value) {
        $shortcode_atts .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
      }

      // Return call to action
      return do_shortcode('[pn-customers-manager-call-to-action' . $shortcode_atts . ']');
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
    $organization = PN_CUSTOMERS_MANAGER_Functions_User::PN_CUSTOMERS_MANAGER_filter_user_posts($organization, 'cm_pn_org');

    ob_start();
    ?>
      <ul class="pn-customers-manager-organizations pn-customers-manager-list-style-none pn-customers-manager-p-0 pn-customers-manager-margin-auto">
        <?php if (!empty($organization)): ?>
          <?php foreach ($organization as $organization_id): ?>
            <li class="pn-customers-manager-organization pn-customers-manager-cm_pn_org-list-item pn-customers-manager-mb-10" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-80-percent">
                  <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-cm_pn_org-view" data-pn-customers-manager-ajax-type="cm_pn_org_view" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
                    <span><?php echo esc_html(get_the_title($organization_id)); ?></span>
                  </a>
                </div>

                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right pn-customers-manager-position-relative">
                  <i class="material-icons-outlined pn-customers-manager-menu-more-btn pn-customers-manager-cursor-pointer pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30">more_vert</i>

                  <div class="pn-customers-manager-menu-more pn-customers-manager-z-index-99 pn-customers-manager-display-none-soft">
                    <ul class="pn-customers-manager-list-style-none">
                      <li>
                        <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-cm_pn_org-view" data-pn-customers-manager-ajax-type="cm_pn_org_view" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
                          <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                              <p><?php esc_html_e('View Organization', 'pn-customers-manager'); ?></p>
                            </div>
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">visibility</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-cm_pn_org-edit" data-pn-customers-manager-ajax-type="cm_pn_org_edit" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>"> 
                          <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                              <p><?php esc_html_e('Edit Organization', 'pn-customers-manager'); ?></p>
                            </div>
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">edit</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="pn-customers-manager-cm_pn_org-duplicate-post">
                          <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                              <p><?php esc_html_e('Duplicate Organization', 'pn-customers-manager'); ?></p>
                            </div>
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">copy</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="pn-customers-manager-popup-open" data-pn-customers-manager-popup-id="pn-customers-manager-popup-cm_pn_org-remove">
                          <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-70-percent">
                              <p><?php esc_html_e('Remove Organization', 'pn-customers-manager'); ?></p>
                            </div>
                            <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-text-align-right">
                              <i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-ml-30">delete</i>
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

        <li class="pn-customers-manager-add-new-cpt pn-customers-manager-mt-50 pn-customers-manager-organization" data-cm_pn_org-id="0">
          <?php if (is_user_logged_in()): ?>
            <a href="#" class="pn-customers-manager-popup-open-ajax pn-customers-manager-text-decoration-none" data-pn-customers-manager-popup-id="pn-customers-manager-popup-cm_pn_org-add" data-pn-customers-manager-ajax-type="cm_pn_org_new">
              <div class="pn-customers-manager-display-table pn-customers-manager-width-100-percent">
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-20-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent pn-customers-manager-text-align-center">
                  <i class="material-icons-outlined pn-customers-manager-cursor-pointer pn-customers-manager-vertical-align-middle pn-customers-manager-font-size-30 pn-customers-manager-width-25">add</i>
                </div>
                <div class="pn-customers-manager-display-inline-table pn-customers-manager-width-80-percent pn-customers-manager-tablet-display-block pn-customers-manager-tablet-width-100-percent">
                  <?php esc_html_e('Add new Organization', 'pn-customers-manager'); ?>
                </div>
              </div>
            </a>
          <?php endif ?>
        </li>
      </ul>
    <?php
    $PN_CUSTOMERS_MANAGER_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $PN_CUSTOMERS_MANAGER_return_string;
  }

  public function cm_pn_org_view($organization_id) {  
    ob_start();
    self::cm_pn_org_register_scripts();
    self::cm_pn_org_print_scripts();
    ?>
      <div class="cm_pn_org-view pn-customers-manager-p-30" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
        <h4 class="pn-customers-manager-text-align-center"><?php echo esc_html(get_the_title($organization_id)); ?></h4>
        
        <div class="pn-customers-manager-word-wrap-break-word">
          <p><?php echo wp_kses(str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($organization_id)->post_content)), PN_CUSTOMERS_MANAGER_KSES); ?></p>
        </div>

        <div class="cm_pn_org-view-list">
          <?php foreach (array_merge(self::cm_pn_org_get_fields(), self::cm_pn_org_get_fields_meta($organization_id)) as $PN_CUSTOMERS_MANAGER_field): ?>
            <?php echo wp_kses(cm_pn_forms::PN_CUSTOMERS_MANAGER_input_display_wrapper($PN_CUSTOMERS_MANAGER_field, 'post', $organization_id), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right pn-customers-manager-organization" data-cm_pn_org-id="<?php echo esc_attr($organization_id); ?>">
            <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-popup-open-ajax" data-pn-customers-manager-popup-id="pn-customers-manager-popup-cm_pn_org-edit" data-pn-customers-manager-ajax-type="cm_pn_org_edit"><?php esc_html_e('Edit Organization', 'pn-customers-manager'); ?></a>
          </div>
        </div>
      </div>
    <?php
    $PN_CUSTOMERS_MANAGER_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $PN_CUSTOMERS_MANAGER_return_string;
  }

  public function cm_pn_org_new() {
    if (!is_user_logged_in()) {
      wp_die(esc_html__('You must be logged in to create a new asset.', 'pn-customers-manager'), esc_html__('Access Denied', 'pn-customers-manager'), ['response' => 403]);
    }

    ob_start();
    self::cm_pn_org_register_scripts();
    self::cm_pn_org_print_scripts();
    ?>
      <div class="cm_pn_org-new pn-customers-manager-p-30">
        <h4 class="pn-customers-manager-mb-30"><?php esc_html_e('Add new Organization', 'pn-customers-manager'); ?></h4>

        <form action="" method="post" id="pn-customers-manager-organization-form-new" class="pn-customers-manager-form">      
          <?php foreach (array_merge(self::cm_pn_org_get_fields(), self::cm_pn_org_get_fields_meta(0)) as $PN_CUSTOMERS_MANAGER_field): ?>
            <?php echo wp_kses(cm_pn_forms::PN_CUSTOMERS_MANAGER_input_wrapper_builder($PN_CUSTOMERS_MANAGER_field, 'post'), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right">
            <input class="pn-customers-manager-btn" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_new" data-pn-customers-manager-post-type="cm_pn_org" type="submit" value="<?php esc_attr_e('Create Organization', 'pn-customers-manager'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $PN_CUSTOMERS_MANAGER_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $PN_CUSTOMERS_MANAGER_return_string;
  }

  public function cm_pn_org_edit($organization_id) {
    ob_start();
    self::cm_pn_org_register_scripts();
    self::cm_pn_org_print_scripts();
    ?>
      <div class="cm_pn_org-edit pn-customers-manager-p-30">
        <p class="pn-customers-manager-text-align-center pn-customers-manager-mb-0"><?php esc_html_e('Editing', 'pn-customers-manager'); ?></p>
        <h4 class="pn-customers-manager-text-align-center pn-customers-manager-mb-30"><?php echo esc_html(get_the_title($organization_id)); ?></h4>

        <form action="" method="post" id="pn-customers-manager-organization-form-edit" class="pn-customers-manager-form">      
          <?php foreach (array_merge(self::cm_pn_org_get_fields(), self::cm_pn_org_get_fields_meta($organization_id)) as $PN_CUSTOMERS_MANAGER_field): ?>
            <?php echo wp_kses(cm_pn_forms::PN_CUSTOMERS_MANAGER_input_wrapper_builder($PN_CUSTOMERS_MANAGER_field, 'post', $organization_id), PN_CUSTOMERS_MANAGER_KSES); ?>
          <?php endforeach ?>

          <div class="pn-customers-manager-text-align-right">
            <input class="pn-customers-manager-btn" type="submit" data-pn-customers-manager-type="post" data-pn-customers-manager-subtype="post_edit" data-pn-customers-manager-post-type="cm_pn_org" data-pn-customers-manager-post-id="<?php echo esc_attr($organization_id); ?>" value="<?php esc_attr_e('Save Organization', 'pn-customers-manager'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $PN_CUSTOMERS_MANAGER_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $PN_CUSTOMERS_MANAGER_return_string;
  }

  public function cm_pn_org_history_add($organization_id) {  
    $PN_CUSTOMERS_MANAGER_meta = get_post_meta($organization_id);
    $PN_CUSTOMERS_MANAGER_meta_array = [];

    if (!empty($PN_CUSTOMERS_MANAGER_meta)) {
      foreach ($PN_CUSTOMERS_MANAGER_meta as $PN_CUSTOMERS_MANAGER_meta_key => $PN_CUSTOMERS_MANAGER_meta_value) {
        if (strpos((string)$PN_CUSTOMERS_MANAGER_meta_key, 'PN_CUSTOMERS_MANAGER_') !== false && !empty($PN_CUSTOMERS_MANAGER_meta_value[0])) {
          $PN_CUSTOMERS_MANAGER_meta_array[$PN_CUSTOMERS_MANAGER_meta_key] = $PN_CUSTOMERS_MANAGER_meta_value[0];
        }
      }
    }
    
    if(empty(get_post_meta($organization_id, 'cm_pn_org_history', true))) {
      update_post_meta($organization_id, 'cm_pn_org_history', [strtotime('now') => $PN_CUSTOMERS_MANAGER_meta_array]);
    } else {
      $PN_CUSTOMERS_MANAGER_post_meta_new = get_post_meta($organization_id, 'cm_pn_org_history', true);
      $PN_CUSTOMERS_MANAGER_post_meta_new[strtotime('now')] = $PN_CUSTOMERS_MANAGER_meta_array;
      update_post_meta($organization_id, 'cm_pn_org_history', $PN_CUSTOMERS_MANAGER_post_meta_new);
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
    $PN_CUSTOMERS_MANAGER_owners = get_post_meta($organization_id, 'PN_CUSTOMERS_MANAGER_owners', true);
    $PN_CUSTOMERS_MANAGER_owners_array = [get_post($organization_id)->post_author];

    if (!empty($PN_CUSTOMERS_MANAGER_owners)) {
      foreach ($PN_CUSTOMERS_MANAGER_owners as $owner_id) {
        $PN_CUSTOMERS_MANAGER_owners_array[] = $owner_id;
      }
    }

    return array_unique($PN_CUSTOMERS_MANAGER_owners_array);
  }
}

