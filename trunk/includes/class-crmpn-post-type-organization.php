<?php
/**
 * Organization creator.
 *
 * This class defines Organization options, menus and templates.
 *
 * @link       padresenlanube.com/
 * @since      1.0.0
 * @package    CRMPN
 * @subpackage CRMPN/includes
 * @author     Padres en la Nube
 */
class CRMPN_Post_Type_organization {
  public function crmpn_organization_get_fields($organization_id = 0) {
    $crmpn_fields = [];
      $crmpn_fields['crmpn_organization_title'] = [
        'id' => 'crmpn_organization_title',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'required' => true,
        'value' => !empty($organization_id) ? esc_html(get_the_title($organization_id)) : '',
        'label' => __('Organization title', 'crmpn'),
        'placeholder' => __('Organization title', 'crmpn'),
      ];
      $crmpn_fields['crmpn_organization_description'] = [
        'id' => 'crmpn_organization_description',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'textarea',
        'required' => true,
        'value' => !empty($organization_id) ? (str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($organization_id)->post_content))) : '',
        'label' => __('Organization description', 'crmpn'),
        'placeholder' => __('Organization description', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_ajax_nonce'] = [
        'id' => 'crmpn_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $crmpn_fields;
  }

  /**
   * Build a list of WP users to populate owner/collaborator selects.
   *
   * @param bool $include_placeholder Include the default "select" option.
   * @return array
   */
  private function crmpn_get_owner_select_options($include_placeholder = true) {
    $options = $include_placeholder ? ['' => esc_html__('Select a user', 'crmpn')] : [];

    $users = get_users([
      'fields' => ['ID', 'display_name', 'user_email'],
      'orderby' => 'display_name',
      'order' => 'ASC',
    ]);

    if (!empty($users)) {
      foreach ($users as $user) {
        $label = '';

        if (class_exists('CRMPN_Functions_User')) {
          $label = CRMPN_Functions_User::crmpn_user_get_name($user->ID);
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
  private static function crmpn_get_funnel_select_options($organization_id = 0) {
    $options = ['' => esc_html__('Select a funnel', 'crmpn')];

    $funnel_args = [
      'post_type'   => 'crmpn_funnel',
      'numberposts' => -1,
      'orderby'     => 'title',
      'order'       => 'ASC',
      'fields'      => 'ids',
    ];

    if (!empty($organization_id)) {
      $funnel_args['meta_query'] = [
        'relation' => 'OR',
        [
          'key'     => 'crmpn_funnel_linked_organization_alt',
          'compare' => 'NOT EXISTS',
        ],
        [
          'key'     => 'crmpn_funnel_linked_organization_alt',
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
  private static function crmpn_get_funnel_stage_options($organization_id = 0) {
    $options = ['' => esc_html__('Select a funnel stage', 'crmpn')];

    if (empty($organization_id) || !class_exists('CRMPN_Post_Type_Funnel')) {
      return $options;
    }

    $funnel_id = intval(get_post_meta($organization_id, 'crmpn_organization_funnel_id', true));

    if (empty($funnel_id)) {
      return $options;
    }

    $stages = CRMPN_Post_Type_Funnel::crmpn_get_funnel_stages_list($funnel_id);

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
  private static function crmpn_get_funnel_status_options($include_placeholder = true) {
    $options = $include_placeholder ? ['' => esc_html__('Select a funnel status', 'crmpn')] : [];

    $statuses = [
      'not_started' => esc_html__('Not started', 'crmpn'),
      'in_progress' => esc_html__('In progress', 'crmpn'),
      'stalled'     => esc_html__('Stalled / needs attention', 'crmpn'),
      'won'         => esc_html__('Won', 'crmpn'),
      'lost'        => esc_html__('Lost', 'crmpn'),
    ];

    return $options + $statuses;
  }

  /**
   * Return the array of user IDs linked as contacts.
   *
   * @param int $organization_id
   * @return array
   */
  private static function crmpn_get_organization_contacts($organization_id) {
    $contacts = get_post_meta($organization_id, 'crmpn_organization_contacts', true);
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
  private static function crmpn_format_contact_name($user_id) {
    if (class_exists('CRMPN_Functions_User')) {
      return CRMPN_Functions_User::crmpn_user_get_name($user_id);
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
  private static function crmpn_render_contacts_list($organization_id) {
    $contacts = self::crmpn_get_organization_contacts($organization_id);

    if (empty($contacts)) {
      return '<p class="crmpn-m-0">' . esc_html__('No contacts linked yet.', 'crmpn') . '</p>';
    }

    ob_start();
    ?>
      <ul class="crmpn-list-style-none crmpn-m-0 crmpn-p-0">
        <?php foreach ($contacts as $contact_id): ?>
          <?php
            $user = get_user_by('id', $contact_id);
            if (!$user) {
              continue;
            }
            $display_name = self::crmpn_format_contact_name($contact_id);
            $email = $user->user_email;
            $edit_link = get_edit_user_link($contact_id);
          ?>
          <li class="crmpn-mb-10">
            <div class="crmpn-display-table crmpn-width-100-percent">
              <div class="crmpn-display-inline-table crmpn-width-70-percent">
                <a href="<?php echo esc_url($edit_link); ?>" target="_blank" class="crmpn-text-decoration-none">
                  <strong><?php echo esc_html($display_name); ?></strong>
                  <?php if (!empty($email)): ?>
                    <br><small><?php echo esc_html($email); ?></small>
                  <?php endif; ?>
                </a>
              </div>
              <div class="crmpn-display-inline-table crmpn-width-30-percent crmpn-text-align-right">
                <a href="#" 
                   class="crmpn-btn crmpn-btn-mini crmpn-remove-contact" 
                   data-contact-id="<?php echo esc_attr($contact_id); ?>"
                   data-org-alt-id="<?php echo esc_attr($organization_id); ?>">
                  <?php esc_html_e('Remove', 'crmpn'); ?>
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
  private function crmpn_render_contacts_field($organization_id) {
    if (empty($organization_id)) {
      return '<p class="crmpn-m-0">' . esc_html__('Save the organization to start adding contacts.', 'crmpn') . '</p>';
    }

    $list = self::crmpn_render_contacts_list($organization_id);

    ob_start();
    ?>
      <div class="crmpn-organization-contacts-field" data-crmpn_organization_alt-id="<?php echo esc_attr($organization_id); ?>">
        <div class="crmpn-organization-contacts-list">
          <?php echo wp_kses($list, CRMPN_KSES); ?>
        </div>
        <div class="crmpn-text-align-right crmpn-mt-15">
          <a href="#"
             class="crmpn-btn crmpn-btn-mini crmpn-popup-open-ajax"
             data-crmpn-popup-id="crmpn-popup-crmpn_contact-add"
             data-crmpn-ajax-type="crmpn_contact_new"
             data-crmpn_organization_alt-id="<?php echo esc_attr($organization_id); ?>">
            <?php esc_html_e('Add contact', 'crmpn'); ?>
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
  public function crmpn_organization_get_fields_meta($organization_id = 0) {
    $crmpn_fields_meta = [];
      $crmpn_fields_meta['crmpn_organization_section_basic_start'] = [
        'id' => 'crmpn_organization_section_basic_start',
        'section' => 'start',
        'label' => esc_html__('Basic organization data', 'crmpn'),
        'description' => esc_html__('Essential information to identify and contact the organization.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_contacts_block'] = [
        'id' => 'crmpn_organization_contacts_block',
        'input' => 'html',
        'skip_save' => true,
        'label' => esc_html__('Linked contacts', 'crmpn'),
        'html_content' => $this->crmpn_render_contacts_field($organization_id),
        'description' => esc_html__('Each contact is a regular WordPress user so you can edit it from the Users screen.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_legal_name'] = [
        'id' => 'crmpn_organization_legal_name',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Legal name', 'crmpn'),
        'placeholder' => esc_html__('e.g. Company Inc.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_trade_name'] = [
        'id' => 'crmpn_organization_trade_name',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Trade name', 'crmpn'),
        'placeholder' => esc_html__('Common brand name', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_segment'] = [
        'id' => 'crmpn_organization_segment',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Segment', 'crmpn'),
        'placeholder' => esc_html__('Select a segment', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a segment', 'crmpn'),
          'startup' => esc_html__('Startup', 'crmpn'),
          'smb' => esc_html__('Small / mid-sized business', 'crmpn'),
          'enterprise' => esc_html__('Enterprise', 'crmpn'),
          'nonprofit' => esc_html__('Nonprofit / third sector', 'crmpn'),
          'government' => esc_html__('Public sector', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_industry'] = [
        'id' => 'crmpn_organization_industry',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Industry', 'crmpn'),
        'placeholder' => esc_html__('Select an industry', 'crmpn'),
        'options' => [
          '' => esc_html__('Select an industry', 'crmpn'),
          'software' => esc_html__('Software / SaaS', 'crmpn'),
          'services' => esc_html__('Professional services', 'crmpn'),
          'manufacturing' => esc_html__('Manufacturing', 'crmpn'),
          'education' => esc_html__('Education', 'crmpn'),
          'health' => esc_html__('Healthcare', 'crmpn'),
          'finance' => esc_html__('Financial services', 'crmpn'),
          'retail' => esc_html__('Retail / eCommerce', 'crmpn'),
          'other' => esc_html__('Other', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_team_size'] = [
        'id' => 'crmpn_organization_team_size',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Team size', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a range', 'crmpn'),
          '1-10' => esc_html__('1 - 10 people', 'crmpn'),
          '11-50' => esc_html__('11 - 50 people', 'crmpn'),
          '51-200' => esc_html__('51 - 200 people', 'crmpn'),
          '201-500' => esc_html__('201 - 500 people', 'crmpn'),
          '500+' => esc_html__('More than 500 people', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_annual_revenue'] = [
        'id' => 'crmpn_organization_annual_revenue',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Annual revenue', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a range', 'crmpn'),
          '<250k' => esc_html__('Up to €250k', 'crmpn'),
          '250k-1m' => esc_html__('€250k - €1M', 'crmpn'),
          '1m-5m' => esc_html__('€1M - €5M', 'crmpn'),
          '5m-20m' => esc_html__('€5M - €20M', 'crmpn'),
          '>20m' => esc_html__('More than €20M', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_phone'] = [
        'id' => 'crmpn_organization_phone',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'tel',
        'label' => esc_html__('Primary phone', 'crmpn'),
        'placeholder' => esc_html__('e.g. +1 555 123 4567', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_email'] = [
        'id' => 'crmpn_organization_email',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'email',
        'label' => esc_html__('Primary email', 'crmpn'),
        'placeholder' => esc_html__('contact@company.com', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_website'] = [
        'id' => 'crmpn_organization_website',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'url',
        'label' => esc_html__('Website', 'crmpn'),
        'placeholder' => esc_html__('https://company.com', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_linkedin'] = [
        'id' => 'crmpn_organization_linkedin',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'url',
        'label' => esc_html__('LinkedIn profile', 'crmpn'),
        'placeholder' => esc_html__('https://www.linkedin.com/company/...', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_country'] = [
        'id' => 'crmpn_organization_country',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Country', 'crmpn'),
        'placeholder' => esc_html__('e.g. United States', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_region'] = [
        'id' => 'crmpn_organization_region',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Region / State', 'crmpn'),
        'placeholder' => esc_html__('e.g. California', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_city'] = [
        'id' => 'crmpn_organization_city',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('City', 'crmpn'),
        'placeholder' => esc_html__('e.g. San Francisco', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_address'] = [
        'id' => 'crmpn_organization_address',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Address', 'crmpn'),
        'placeholder' => esc_html__('Street, number, suite…', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_postal_code'] = [
        'id' => 'crmpn_organization_postal_code',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Postal code', 'crmpn'),
        'placeholder' => esc_html__('e.g. 94105', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_section_basic_end'] = [
        'id' => 'crmpn_organization_section_basic_end',
        'section' => 'end',
      ];
      $crmpn_fields_meta['crmpn_organization_section_advanced_start'] = [
        'id' => 'crmpn_organization_section_advanced_start',
        'section' => 'start',
        'label' => esc_html__('Advanced CRM fields', 'crmpn'),
        'description' => esc_html__('Strategic data to segment, prioritize, and plan commercial follow-up.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_fiscal_id'] = [
        'id' => 'crmpn_organization_fiscal_id',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Tax ID (VAT / EIN)', 'crmpn'),
        'placeholder' => esc_html__('Fiscal identifier', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_lead_source'] = [
        'id' => 'crmpn_organization_lead_source',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Lead source', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a source', 'crmpn'),
          'website' => esc_html__('Website / SEO', 'crmpn'),
          'ads' => esc_html__('Paid campaigns', 'crmpn'),
          'event' => esc_html__('Event', 'crmpn'),
          'referral' => esc_html__('Referral', 'crmpn'),
          'outbound' => esc_html__('Outbound prospecting', 'crmpn'),
          'partner' => esc_html__('Partner', 'crmpn'),
          'other' => esc_html__('Other source', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_lifecycle_stage'] = [
        'id' => 'crmpn_organization_lifecycle_stage',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Lifecycle stage', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a lifecycle stage', 'crmpn'),
          'lead' => esc_html__('Lead', 'crmpn'),
          'marketing_qualified' => esc_html__('Marketing qualified (MQL)', 'crmpn'),
          'sales_qualified' => esc_html__('Sales qualified (SQL)', 'crmpn'),
          'opportunity' => esc_html__('Opportunity', 'crmpn'),
          'customer' => esc_html__('Customer', 'crmpn'),
          'churned' => esc_html__('Churned', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_pipeline_stage'] = [
        'id' => 'crmpn_organization_pipeline_stage',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Pipeline stage', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a pipeline stage', 'crmpn'),
          'qualification' => esc_html__('Qualification', 'crmpn'),
          'discovery' => esc_html__('Discovery', 'crmpn'),
          'proposal' => esc_html__('Proposal', 'crmpn'),
          'negotiation' => esc_html__('Negotiation', 'crmpn'),
          'closed_won' => esc_html__('Closed won', 'crmpn'),
          'closed_lost' => esc_html__('Closed lost', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_priority'] = [
        'id' => 'crmpn_organization_priority',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Commercial priority', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a priority', 'crmpn'),
          'high' => esc_html__('High', 'crmpn'),
          'medium' => esc_html__('Medium', 'crmpn'),
          'low' => esc_html__('Low', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_health'] = [
        'id' => 'crmpn_organization_health',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Account health', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a health status', 'crmpn'),
          'healthy' => esc_html__('Healthy / growing', 'crmpn'),
          'risk' => esc_html__('At risk', 'crmpn'),
          'churn' => esc_html__('Possible churn', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_lead_score'] = [
        'id' => 'crmpn_organization_lead_score',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'range',
        'min' => 0,
        'max' => 100,
        'step' => 5,
        'label' => esc_html__('Lead score', 'crmpn'),
        'description' => esc_html__('0 = cold lead, 100 = hot lead.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_owner'] = [
        'id' => 'crmpn_organization_owner',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Primary owner', 'crmpn'),
        'options' => $this->crmpn_get_owner_select_options(),
      ];
      $crmpn_fields_meta['crmpn_organization_collaborators'] = [
        'id' => 'crmpn_organization_collaborators',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'multiple' => true,
        'label' => esc_html__('Assigned collaborators', 'crmpn'),
        'description' => esc_html__('Select other team members involved with the account.', 'crmpn'),
        'options' => $this->crmpn_get_owner_select_options(true),
      ];
      $crmpn_fields_meta['crmpn_organization_last_contact_date'] = [
        'id' => 'crmpn_organization_last_contact_date',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'date',
        'label' => esc_html__('Last contact', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_last_contact_channel'] = [
        'id' => 'crmpn_organization_last_contact_channel',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Last contact channel', 'crmpn'),
        'options' => [
          '' => esc_html__('Select a channel', 'crmpn'),
          'email' => esc_html__('Email', 'crmpn'),
          'call' => esc_html__('Call', 'crmpn'),
          'meeting' => esc_html__('Meeting', 'crmpn'),
          'chat' => esc_html__('Chat / messaging', 'crmpn'),
          'event' => esc_html__('Event', 'crmpn'),
          'other' => esc_html__('Other', 'crmpn'),
        ],
      ];
      $crmpn_fields_meta['crmpn_organization_next_action'] = [
        'id' => 'crmpn_organization_next_action',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'textarea',
        'label' => esc_html__('Próximo paso acordado', 'crmpn'),
        'placeholder' => esc_html__('Describe el siguiente hito o compromiso.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_billing_email'] = [
        'id' => 'crmpn_organization_billing_email',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'email',
        'label' => esc_html__('Email de facturación', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_billing_phone'] = [
        'id' => 'crmpn_organization_billing_phone',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'tel',
        'label' => esc_html__('Teléfono de facturación', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_billing_address'] = [
        'id' => 'crmpn_organization_billing_address',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Dirección de facturación', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_tags'] = [
        'id' => 'crmpn_organization_tags',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'input',
        'type' => 'text',
        'label' => esc_html__('Etiquetas', 'crmpn'),
        'placeholder' => esc_html__('Ej: Clientes VIP, Renovación, Partner', 'crmpn'),
        'description' => esc_html__('Separa las etiquetas con comas.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_notes'] = [
        'id' => 'crmpn_organization_notes',
        'class' => 'crmpn-input crmpn-width-100-percent',
        'input' => 'textarea',
        'label' => esc_html__('Notas internas', 'crmpn'),
        'placeholder' => esc_html__('Contexto adicional para el equipo comercial.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_section_advanced_end'] = [
        'id' => 'crmpn_organization_section_advanced_end',
        'section' => 'end',
      ];
      $crmpn_fields_meta['crmpn_organization_section_funnel_start'] = [
        'id' => 'crmpn_organization_section_funnel_start',
        'section' => 'start',
        'label' => esc_html__('Commercial funnel', 'crmpn'),
        'description' => esc_html__('Link this organization to a funnel and track its stage.', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_funnel_id'] = [
        'id' => 'crmpn_organization_funnel_id',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Assigned funnel', 'crmpn'),
        'options' => self::crmpn_get_funnel_select_options($organization_id),
        'placeholder' => esc_html__('Select a funnel', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_funnel_stage'] = [
        'id' => 'crmpn_organization_funnel_stage',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Current stage', 'crmpn'),
        'options' => self::crmpn_get_funnel_stage_options($organization_id),
        'placeholder' => esc_html__('Select a funnel stage', 'crmpn'),
        'description' => esc_html__('The available stages come from the funnel definition (one per line).', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_funnel_status'] = [
        'id' => 'crmpn_organization_funnel_status',
        'class' => 'crmpn-select crmpn-width-100-percent',
        'input' => 'select',
        'label' => esc_html__('Funnel status', 'crmpn'),
        'options' => self::crmpn_get_funnel_status_options(),
        'placeholder' => esc_html__('Select a funnel status', 'crmpn'),
      ];
      $crmpn_fields_meta['crmpn_organization_section_funnel_end'] = [
        'id' => 'crmpn_organization_section_funnel_end',
        'section' => 'end',
      ];
      $crmpn_fields_meta['crmpn_organization_form'] = [
        'id' => 'crmpn_organization_form',
        'input' => 'input',
        'type' => 'hidden',
      ];
      $crmpn_fields_meta['crmpn_ajax_nonce'] = [
        'id' => 'crmpn_ajax_nonce',
        'input' => 'input',
        'type' => 'nonce',
      ];
    return $crmpn_fields_meta;
  }

  /**
   * Register Organization.
   *
   * @since    1.0.0
   */
  public function crmpn_organization_register_post_type() {
    $labels = [
      'name'                => _x('Organization', 'Post Type general name', 'crmpn'),
      'singular_name'       => _x('Organization', 'Post Type singular name', 'crmpn'),
      'menu_name'           => esc_html(__('Organizations', 'crmpn')),
      'parent_item_colon'   => esc_html(__('Parent Organization', 'crmpn')),
      'all_items'           => esc_html(__('All Organizations', 'crmpn')),
      'view_item'           => esc_html(__('View Organization', 'crmpn')),
      'add_new_item'        => esc_html(__('Add new Organization', 'crmpn')),
      'add_new'             => esc_html(__('Add new Organization', 'crmpn')),
      'edit_item'           => esc_html(__('Edit Organization', 'crmpn')),
      'update_item'         => esc_html(__('Update Organization', 'crmpn')),
      'search_items'        => esc_html(__('Search Organizations', 'crmpn')),
      'not_found'           => esc_html(__('Not Organization found', 'crmpn')),
      'not_found_in_trash'  => esc_html(__('Not Organization found in Trash', 'crmpn')),
    ];

    $args = [
      'labels'              => $labels,
      'rewrite'             => ['slug' => (!empty(get_option('crmpn_organization_slug')) ? get_option('crmpn_organization_slug') : 'crmpn'), 'with_front' => false],
      'label'               => esc_html(__('Organizations', 'crmpn')),
      'description'         => esc_html(__('Organization description', 'crmpn')),
      'supports'            => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'page-attributes', ],
      'hierarchical'        => true,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => true,
      'show_in_nav_menus'   => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 5,
      'menu_icon'           => esc_url(CRMPN_URL . 'assets/media/crmpn-funnel-menu-icon.svg'),
      'can_export'          => true,
      'has_archive'         => true,
      'exclude_from_search' => false,
      'publicly_queryable'  => true,
      'capability_type'     => 'page',
      'capabilities'        => CRMPN_ROLE_CRMPN_ORGANIZATION_CAPABILITIES,
      'taxonomies'          => ['crmpn_organization_category'],
      'show_in_rest'        => true, /* REST API */
    ];

    register_post_type('crmpn_organization', $args);
    add_theme_support('post-thumbnails', ['page', 'crmpn_organization']);
  }

  /**
   * Add Organization dashboard metabox.
   *
   * @since    1.0.0
   */
  public function crmpn_organization_add_meta_box() {
    add_meta_box('crmpn_meta_box', esc_html(__('Organization details', 'crmpn')), [$this, 'crmpn_organization_meta_box_function'], 'crmpn_organization', 'normal', 'high', ['__block_editor_compatible_meta_box' => true,]);
  }

  /**
   * Defines Organization dashboard contents.
   *
   * @since    1.0.0
   */
  public function crmpn_organization_meta_box_function($post) {
    foreach (self::crmpn_organization_get_fields_meta($post->ID) as $crmpn_field) {
      if (!is_null(CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_field, 'post', $post->ID))) {
        echo wp_kses(CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_field, 'post', $post->ID), crmpn_KSES);
      }
    }
  }

  /**
   * Defines single template for Organization.
   *
   * @since    1.0.0
   */
  public function crmpn_organization_single_template($single) {
    if (get_post_type() == 'crmpn_organization') {
      if (file_exists(CRMPN_DIR . 'templates/public/single-crmpn_organization.php')) {
        return CRMPN_DIR . 'templates/public/single-crmpn_organization.php';
      }
    }

    return $single;
  }

  /**
   * Defines archive template for Organization.
   *
   * @since    1.0.0
   */
  public function crmpn_organization_archive_template($archive) {
    if (get_post_type() == 'crmpn_organization') {
      if (file_exists(CRMPN_DIR . 'templates/public/archive-crmpn_organization.php')) {
        return CRMPN_DIR . 'templates/public/archive-crmpn_organization.php';
      }
    }

    return $archive;
  }

  public function crmpn_organization_save_post($post_id, $cpt, $update) {
    if($cpt->post_type == 'crmpn_organization' && array_key_exists('crmpn_organization_form', $_POST)){
      // Always require nonce verification
      if (!array_key_exists('crmpn_ajax_nonce', $_POST)) {
        echo wp_json_encode([
          'error_key' => 'crmpn_nonce_error_required',
          'error_content' => esc_html(__('Security check failed: Nonce is required.', 'crmpn')),
        ]);

        exit;
      }

      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['crmpn_ajax_nonce'])), 'crmpn-nonce')) {
        echo wp_json_encode([
          'error_key' => 'crmpn_nonce_error_invalid',
          'error_content' => esc_html(__('Security check failed: Invalid nonce.', 'crmpn')),
        ]);

        exit;
      }

      if (!array_key_exists('crmpn_duplicate', $_POST)) {
        foreach (array_merge(self::crmpn_organization_get_fields(), self::crmpn_organization_get_fields_meta($post_id)) as $crmpn_field) {
          $crmpn_input = array_key_exists('input', $crmpn_field) ? $crmpn_field['input'] : '';

          if (array_key_exists($crmpn_field['id'], $_POST) || $crmpn_input == 'html_multi') {
            $crmpn_value = array_key_exists($crmpn_field['id'], $_POST) ? 
              CRMPN_Forms::crmpn_sanitizer(
                wp_unslash($_POST[$crmpn_field['id']]),
                $crmpn_field['input'], 
                !empty($crmpn_field['type']) ? $crmpn_field['type'] : '',
                $crmpn_field // Pass the entire field config
              ) : '';

            if (!empty($crmpn_input)) {
              switch ($crmpn_input) {
                case 'input':
                  if (array_key_exists('type', $crmpn_field) && $crmpn_field['type'] == 'checkbox') {
                    if (isset($_POST[$crmpn_field['id']])) {
                      update_post_meta($post_id, $crmpn_field['id'], $crmpn_value);
                    } else {
                      update_post_meta($post_id, $crmpn_field['id'], '');
                    }
                  } else {
                    update_post_meta($post_id, $crmpn_field['id'], $crmpn_value);
                  }

                  break;
                case 'select':
                  if (array_key_exists('multiple', $crmpn_field) && $crmpn_field['multiple']) {
                    $multi_array = [];
                    $empty = true;

                    foreach (wp_unslash($_POST[$crmpn_field['id']]) as $multi_value) {
                      $multi_array[] = CRMPN_Forms::crmpn_sanitizer(
                        $multi_value, 
                        $crmpn_field['input'], 
                        !empty($crmpn_field['type']) ? $crmpn_field['type'] : '',
                        $crmpn_field // Pass the entire field config
                      );
                    }

                    update_post_meta($post_id, $crmpn_field['id'], $multi_array);
                  } else {
                    update_post_meta($post_id, $crmpn_field['id'], $crmpn_value);
                  }
                  
                  break;
                case 'html_multi':
                  foreach ($crmpn_field['html_multi_fields'] as $crmpn_multi_field) {
                    if (array_key_exists($crmpn_multi_field['id'], $_POST)) {
                      $multi_array = [];
                      $empty = true;

                      // Sanitize the POST data before using it
                      $sanitized_post_data = isset($_POST[$crmpn_multi_field['id']]) ? 
                        array_map(function($value) {
                            return sanitize_text_field(wp_unslash($value));
                        }, (array)$_POST[$crmpn_multi_field['id']]) : [];
                      
                      foreach ($sanitized_post_data as $multi_value) {
                        if (!empty($multi_value)) {
                          $empty = false;
                        }

                        $multi_array[] = CRMPN_Forms::crmpn_sanitizer(
                          $multi_value, 
                          $crmpn_multi_field['input'], 
                          !empty($crmpn_multi_field['type']) ? $crmpn_multi_field['type'] : '',
                          $crmpn_multi_field // Pass the entire field config
                        );
                      }

                      if (!$empty) {
                        update_post_meta($post_id, $crmpn_multi_field['id'], $multi_array);
                      } else {
                        update_post_meta($post_id, $crmpn_multi_field['id'], '');
                      }
                    }
                  }

                  break;
                case 'tags':
                  // Handle tags field - save as array
                  $tags_array_field_name = $crmpn_field['id'] . '_tags_array';
                  if (array_key_exists($tags_array_field_name, $_POST)) {
                    $tags_json = CRMPN_Forms::crmpn_sanitizer(
                      wp_unslash($_POST[$tags_array_field_name]),
                      'input',
                      'text',
                      $crmpn_field
                    );
                    
                    // Decode JSON and save as array
                    $tags_array = json_decode($tags_json, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($tags_array)) {
                      update_post_meta($post_id, $crmpn_field['id'], $tags_array);
                    } else {
                      // Fallback: treat as comma-separated string
                      $tags_string = CRMPN_Forms::crmpn_sanitizer(
                        wp_unslash($_POST[$crmpn_field['id']]),
                        'input',
                        'text',
                        $crmpn_field
                      );
                      $tags_array = array_map('trim', explode(',', $tags_string));
                      $tags_array = array_filter($tags_array); // Remove empty values
                      update_post_meta($post_id, $crmpn_field['id'], $tags_array);
                    }
                  } else {
                    // Fallback: save the text input value as comma-separated array
                    $tags_string = CRMPN_Forms::crmpn_sanitizer(
                      wp_unslash($_POST[$crmpn_field['id']]),
                      'input',
                      'text',
                      $crmpn_field
                    );
                    $tags_array = array_map('trim', explode(',', $tags_string));
                    $tags_array = array_filter($tags_array); // Remove empty values
                    update_post_meta($post_id, $crmpn_field['id'], $tags_array);
                  }
                  break;
                default:
                  update_post_meta($post_id, $crmpn_field['id'], $crmpn_value);
                  break;
              }
            }
          } else {
            update_post_meta($post_id, $crmpn_field['id'], '');
          }
        }
      }
    }
  }

  public function crmpn_organization_form_save($element_id, $key_value, $crmpn_form_type, $crmpn_form_subtype) {
    $post_type = !empty(get_post_type($element_id)) ? get_post_type($element_id) : 'crmpn_organization';

    if ($post_type == 'crmpn_organization') {
      switch ($crmpn_form_type) {
        case 'post':
          switch ($crmpn_form_subtype) {
            case 'post_new':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'crmpn_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $post_functions = new CRMPN_Functions_Post();
              $organization_id = $post_functions->crmpn_insert_post(esc_html($crmpn_organization_title), $crmpn_organization_description, '', sanitize_title(esc_html($crmpn_organization_title)), 'crmpn_organization', 'publish', get_current_user_id());

              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  update_post_meta($organization_id, $key, $value);
                }
              }

              break;
            case 'post_edit':
              if (!empty($key_value)) {
                foreach ($key_value as $key => $value) {
                  if (strpos((string)$key, 'crmpn_') !== false) {
                    ${$key} = $value;
                    delete_post_meta($element_id, $key);
                  }
                }
              }

              $organization_id = $element_id;
              wp_update_post(['ID' => $organization_id, 'post_title' => $crmpn_organization_title, 'post_content' => $crmpn_organization_description,]);

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

  public function crmpn_organization_register_scripts() {
    if (!wp_script_is('crmpn-aux', 'registered')) {
      wp_register_script('crmpn-aux', CRMPN_URL . 'assets/js/crmpn-aux.js', [], CRMPN_VERSION, true);
    }

    if (!wp_script_is('crmpn-forms', 'registered')) {
      wp_register_script('crmpn-forms', CRMPN_URL . 'assets/js/crmpn-forms.js', [], CRMPN_VERSION, true);
    }
    
    if (!wp_script_is('crmpn-selector', 'registered')) {
      wp_register_script('crmpn-selector', CRMPN_URL . 'assets/js/crmpn-selector.js', [], CRMPN_VERSION, true);
    }
  }

  public function crmpn_organization_print_scripts() {
    wp_print_scripts(['crmpn-aux', 'crmpn-forms', 'crmpn-selector']);
  }

  public function crmpn_organization_list_wrapper() {
    ob_start();
    ?>
      <div class="crmpn-cpt-list crmpn-crmpn_organization-list crmpn-mb-50">
        <div class="crmpn-cpt-search-container crmpn-mb-20 crmpn-text-align-right">
          <div class="crmpn-cpt-search-wrapper">
            <input type="text" class="crmpn-cpt-search-input crmpn-input crmpn-display-none" placeholder="<?php esc_attr_e('Filter...', 'crmpn'); ?>" />
            <i class="material-icons-outlined crmpn-cpt-search-toggle crmpn-cursor-pointer crmpn-font-size-30 crmpn-vertical-align-middle crmpn-tooltip" title="<?php esc_attr_e('Search Organizations', 'crmpn'); ?>">search</i>
            
            <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_organization-add" data-crmpn-ajax-type="crmpn_organization_new">
              <i class="material-icons-outlined crmpn-cursor-pointer crmpn-font-size-30 crmpn-vertical-align-middle crmpn-tooltip" title="<?php esc_attr_e('Add new Organization', 'crmpn'); ?>">add</i>
            </a>
          </div>
        </div>

        <div class="crmpn-cpt-list-wrapper crmpn-crmpn_organization-list-wrapper">
          <?php echo wp_kses(self::crmpn_organization_list(), CRMPN_KSES); ?>
        </div>
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_organization_list() {
    $organization_atts = [
      'fields' => 'ids',
      'numberposts' => -1,
      'post_type' => 'crmpn_organization',
      'post_status' => 'any', 
      'orderby' => 'menu_order', 
      'order' => 'ASC', 
    ];
    
    if (class_exists('Polylang')) {
      $organization_atts['lang'] = pll_current_language('slug');
    }

    $organization = get_posts($organization_atts);

    // Filter assets based on user permissions
    $organization = CRMPN_Functions_User::crmpn_filter_user_posts($organization, 'crmpn_organization');

    ob_start();
    ?>
      <ul class="crmpn-organizations crmpn-list-style-none crmpn-p-0 crmpn-margin-auto">
        <?php if (!empty($organization)): ?>
          <?php foreach ($organization as $organization_id): ?>
            <li class="crmpn-organization crmpn-crmpn_organization-list-item crmpn-mb-10" data-crmpn_organization-id="<?php echo esc_attr($organization_id); ?>">
              <div class="crmpn-display-table crmpn-width-100-percent">
                <div class="crmpn-display-inline-table crmpn-width-60-percent">
                  <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_organization-view" data-crmpn-ajax-type="crmpn_organization_view" data-crmpn_organization-id="<?php echo esc_attr($organization_id); ?>">
                    <span><?php echo esc_html(get_the_title($organization_id)); ?></span>
                  </a>
                </div>

                <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right crmpn-position-relative">
                  <i class="material-icons-outlined crmpn-menu-more-btn crmpn-cursor-pointer crmpn-vertical-align-middle crmpn-font-size-30">more_vert</i>

                  <div class="crmpn-menu-more crmpn-z-index-99 crmpn-display-none-soft">
                    <ul class="crmpn-list-style-none">
                      <li>
                        <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_organization-view" data-crmpn-ajax-type="crmpn_organization_view" data-crmpn_organization-id="<?php echo esc_attr($organization_id); ?>">
                          <div class="crmpn-display-table crmpn-width-100-percent">
                            <div class="crmpn-display-inline-table crmpn-width-70-percent">
                              <p><?php esc_html_e('View Organization', 'crmpn'); ?></p>
                            </div>
                            <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right">
                              <i class="material-icons-outlined crmpn-vertical-align-middle crmpn-font-size-30 crmpn-ml-30">visibility</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_organization-edit" data-crmpn-ajax-type="crmpn_organization_edit" data-crmpn_organization-id="<?php echo esc_attr($organization_id); ?>"> 
                          <div class="crmpn-display-table crmpn-width-100-percent">
                            <div class="crmpn-display-inline-table crmpn-width-70-percent">
                              <p><?php esc_html_e('Edit Organization', 'crmpn'); ?></p>
                            </div>
                            <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right">
                              <i class="material-icons-outlined crmpn-vertical-align-middle crmpn-font-size-30 crmpn-ml-30">edit</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="crmpn-crmpn_organization-duplicate-post">
                          <div class="crmpn-display-table crmpn-width-100-percent">
                            <div class="crmpn-display-inline-table crmpn-width-70-percent">
                              <p><?php esc_html_e('Duplicate Organization', 'crmpn'); ?></p>
                            </div>
                            <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right">
                              <i class="material-icons-outlined crmpn-vertical-align-middle crmpn-font-size-30 crmpn-ml-30">copy</i>
                            </div>
                          </div>
                        </a>
                      </li>
                      <li>
                        <a href="#" class="crmpn-popup-open" data-crmpn-popup-id="crmpn-popup-crmpn_organization-remove">
                          <div class="crmpn-display-table crmpn-width-100-percent">
                            <div class="crmpn-display-inline-table crmpn-width-70-percent">
                              <p><?php esc_html_e('Remove Organization', 'crmpn'); ?></p>
                            </div>
                            <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-text-align-right">
                              <i class="material-icons-outlined crmpn-vertical-align-middle crmpn-font-size-30 crmpn-ml-30">delete</i>
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

        <li class="crmpn-add-new-cpt crmpn-mt-50 crmpn-organization" data-crmpn_organization-id="0">
          <?php if (is_user_logged_in()): ?>
            <a href="#" class="crmpn-popup-open-ajax crmpn-text-decoration-none" data-crmpn-popup-id="crmpn-popup-crmpn_organization-add" data-crmpn-ajax-type="crmpn_organization_new">
              <div class="crmpn-display-table crmpn-width-100-percent">
                <div class="crmpn-display-inline-table crmpn-width-20-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent crmpn-text-align-center">
                  <i class="material-icons-outlined crmpn-cursor-pointer crmpn-vertical-align-middle crmpn-font-size-30 crmpn-width-25">add</i>
                </div>
                <div class="crmpn-display-inline-table crmpn-width-80-percent crmpn-tablet-display-block crmpn-tablet-width-100-percent">
                  <?php esc_html_e('Add new Organization', 'crmpn'); ?>
                </div>
              </div>
            </a>
          <?php endif ?>
        </li>
      </ul>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_organization_view($organization_id) {  
    ob_start();
    self::crmpn_organization_register_scripts();
    self::crmpn_organization_print_scripts();
    ?>
      <div class="crmpn_organization-view crmpn-p-30" data-crmpn_organization-id="<?php echo esc_attr($organization_id); ?>">
        <h4 class="crmpn-text-align-center"><?php echo esc_html(get_the_title($organization_id)); ?></h4>
        
        <div class="crmpn-word-wrap-break-word">
          <p><?php echo wp_kses(str_replace(']]>', ']]&gt;', apply_filters('the_content', get_post($organization_id)->post_content)), CRMPN_KSES); ?></p>
        </div>

        <div class="crmpn_organization-view-list">
          <?php foreach (array_merge(self::crmpn_organization_get_fields(), self::crmpn_organization_get_fields_meta($organization_id)) as $crmpn_field): ?>
            <?php echo wp_kses(CRMPN_Forms::crmpn_input_display_wrapper($crmpn_field, 'post', $organization_id), CRMPN_KSES); ?>
          <?php endforeach ?>

          <div class="crmpn-text-align-right crmpn-organization" data-crmpn_organization-id="<?php echo esc_attr($organization_id); ?>">
            <a href="#" class="crmpn-btn crmpn-btn-mini crmpn-popup-open-ajax" data-crmpn-popup-id="crmpn-popup-crmpn_organization-edit" data-crmpn-ajax-type="crmpn_organization_edit"><?php esc_html_e('Edit Organization', 'crmpn'); ?></a>
          </div>
        </div>
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_organization_new() {
    if (!is_user_logged_in()) {
      wp_die(esc_html__('You must be logged in to create a new asset.', 'crmpn'), esc_html__('Access Denied', 'crmpn'), ['response' => 403]);
    }

    ob_start();
    self::crmpn_organization_register_scripts();
    self::crmpn_organization_print_scripts();
    ?>
      <div class="crmpn_organization-new crmpn-p-30">
        <h4 class="crmpn-mb-30"><?php esc_html_e('Add new Organization', 'crmpn'); ?></h4>

        <form action="" method="post" id="crmpn-organization-form-new" class="crmpn-form">      
          <?php foreach (array_merge(self::crmpn_organization_get_fields(), self::crmpn_organization_get_fields_meta(0)) as $crmpn_field): ?>
            <?php echo wp_kses(CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_field, 'post'), CRMPN_KSES); ?>
          <?php endforeach ?>

          <div class="crmpn-text-align-right">
            <input class="crmpn-btn" data-crmpn-type="post" data-crmpn-subtype="post_new" data-crmpn-post-type="crmpn_organization" type="submit" value="<?php esc_attr_e('Create Organization', 'crmpn'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_organization_edit($organization_id) {
    ob_start();
    self::crmpn_organization_register_scripts();
    self::crmpn_organization_print_scripts();
    ?>
      <div class="crmpn_organization-edit crmpn-p-30">
        <p class="crmpn-text-align-center crmpn-mb-0"><?php esc_html_e('Editing', 'crmpn'); ?></p>
        <h4 class="crmpn-text-align-center crmpn-mb-30"><?php echo esc_html(get_the_title($organization_id)); ?></h4>

        <form action="" method="post" id="crmpn-organization-form-edit" class="crmpn-form">      
          <?php foreach (array_merge(self::crmpn_organization_get_fields(), self::crmpn_organization_get_fields_meta($organization_id)) as $crmpn_field): ?>
            <?php echo wp_kses(CRMPN_Forms::crmpn_input_wrapper_builder($crmpn_field, 'post', $organization_id), CRMPN_KSES); ?>
          <?php endforeach ?>

          <div class="crmpn-text-align-right">
            <input class="crmpn-btn" type="submit" data-crmpn-type="post" data-crmpn-subtype="post_edit" data-crmpn-post-type="crmpn_organization" data-crmpn-post-id="<?php echo esc_attr($organization_id); ?>" value="<?php esc_attr_e('Save Organization', 'crmpn'); ?>"/>
          </div>
        </form> 
      </div>
    <?php
    $crmpn_return_string = ob_get_contents(); 
    ob_end_clean(); 
    return $crmpn_return_string;
  }

  public function crmpn_organization_history_add($organization_id) {  
    $crmpn_meta = get_post_meta($organization_id);
    $crmpn_meta_array = [];

    if (!empty($crmpn_meta)) {
      foreach ($crmpn_meta as $crmpn_meta_key => $crmpn_meta_value) {
        if (strpos((string)$crmpn_meta_key, 'crmpn_') !== false && !empty($crmpn_meta_value[0])) {
          $crmpn_meta_array[$crmpn_meta_key] = $crmpn_meta_value[0];
        }
      }
    }
    
    if(empty(get_post_meta($organization_id, 'crmpn_organization_history', true))) {
      update_post_meta($organization_id, 'crmpn_organization_history', [strtotime('now') => $crmpn_meta_array]);
    } else {
      $crmpn_post_meta_new = get_post_meta($organization_id, 'crmpn_organization_history', true);
      $crmpn_post_meta_new[strtotime('now')] = $crmpn_meta_array;
      update_post_meta($organization_id, 'crmpn_organization_history', $crmpn_post_meta_new);
    }
  }

  public function crmpn_organization_get_next($organization_id) {
    $crmpn_organization_periodicity = get_post_meta($organization_id, 'crmpn_organization_periodicity', true);
    $crmpn_organization_date = get_post_meta($organization_id, 'crmpn_organization_date', true);
    $crmpn_organization_time = get_post_meta($organization_id, 'crmpn_organization_time', true);

    $crmpn_organization_timestamp = strtotime($crmpn_organization_date . ' ' . $crmpn_organization_time);

    if (!empty($crmpn_organization_periodicity) && !empty($crmpn_organization_timestamp)) {
      $now = strtotime('now');

      while ($crmpn_organization_timestamp < $now) {
        $crmpn_organization_timestamp = strtotime('+' . str_replace('_', ' ', $crmpn_organization_periodicity), $crmpn_organization_timestamp);
      }

      return $crmpn_organization_timestamp;
    }
  }

  public function crmpn_organization_owners($organization_id) {
    $crmpn_owners = get_post_meta($organization_id, 'crmpn_owners', true);
    $crmpn_owners_array = [get_post($organization_id)->post_author];

    if (!empty($crmpn_owners)) {
      foreach ($crmpn_owners as $owner_id) {
        $crmpn_owners_array[] = $owner_id;
      }
    }

    return array_unique($crmpn_owners_array);
  }
}

