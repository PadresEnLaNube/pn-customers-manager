(function(blocks, element, i18n, blockEditor, components) {
  var registerBlockType = blocks.registerBlockType;
  var Fragment = element.Fragment;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;

  var campaignOptions = [{ label: __('-- All campaigns --', 'pn-customers-manager'), value: '' }];

  if (typeof pnCMEmailCampaigns !== 'undefined' && pnCMEmailCampaigns.campaigns) {
    pnCMEmailCampaigns.campaigns.forEach(function(campaign) {
      campaignOptions.push({
        label: campaign.label,
        value: campaign.value
      });
    });
  }

  registerBlockType('pn-customers-manager/email-campaigns', {
    title: __('Email Campaigns pn-customers-manager', 'pn-customers-manager'),
    icon: 'email',
    category: 'widgets',
    description: __('Email campaigns panel with mailpn integration.', 'pn-customers-manager'),
    attributes: {
      campaign: {
        type: 'string',
        default: ''
      }
    },
    edit: function(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      return element.createElement(
        Fragment,
        null,
        element.createElement(
          InspectorControls,
          null,
          element.createElement(
            PanelBody,
            { title: __('Campaign settings', 'pn-customers-manager'), initialOpen: true },
            element.createElement(SelectControl, {
              label: __('Campaign', 'pn-customers-manager'),
              value: attributes.campaign,
              options: campaignOptions,
              onChange: function(value) { setAttributes({ campaign: value }); },
              help: __('Select a specific campaign or leave empty to display all campaigns.', 'pn-customers-manager')
            })
          )
        ),
        element.createElement(
          'div',
          { className: 'pn-customers-manager-email-campaigns-block-preview', style: { padding: '24px', background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '8px', textAlign: 'center' } },
          element.createElement('strong', null, __('Email Campaigns', 'pn-customers-manager')),
          element.createElement(
            'p',
            null,
            attributes.campaign
              ? __('Campaign: ', 'pn-customers-manager') + attributes.campaign
              : __('All campaigns will be displayed.', 'pn-customers-manager')
          )
        )
      );
    },
    save: function() { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);
