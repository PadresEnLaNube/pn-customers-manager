(function(blocks, element, i18n, blockEditor, components) {
  const { registerBlockType } = blocks;
  const { Fragment } = element;
  const { __ } = i18n;
  const { InspectorControls } = blockEditor;
  const { PanelBody, TextControl, ToggleControl, RangeControl } = components;

  registerBlockType('customers-manager-pn/organization-list', {
    title: __('Organization List', 'customers-manager-pn'),
    icon: 'list-view',
    category: 'widgets',
    description: __('Displays a list of organizations from the CRM.', 'customers-manager-pn'),
    attributes: {
      showSearch: {
        type: 'boolean',
        default: true,
      },
      showAddButton: {
        type: 'boolean',
        default: true,
      },
      postsPerPage: {
        type: 'number',
        default: 10,
      },
    },
    edit: (props) => {
      const { attributes, setAttributes } = props;

      return (
        element.createElement(
          Fragment,
          null,
          element.createElement(
            InspectorControls,
            null,
            element.createElement(
              PanelBody,
              { title: __('List Settings', 'customers-manager-pn'), initialOpen: true },
              element.createElement(ToggleControl, {
                label: __('Show search', 'customers-manager-pn'),
                checked: attributes.showSearch,
                onChange: (value) => setAttributes({ showSearch: value }),
                help: __('Shows the search field to filter organizations.', 'customers-manager-pn'),
              }),
              element.createElement(ToggleControl, {
                label: __('Show add button', 'customers-manager-pn'),
                checked: attributes.showAddButton,
                onChange: (value) => setAttributes({ showAddButton: value }),
                help: __('Shows the button to add new organizations.', 'customers-manager-pn'),
              }),
              element.createElement(RangeControl, {
                label: __('Organizations per page', 'customers-manager-pn'),
                value: attributes.postsPerPage,
                onChange: (value) => setAttributes({ postsPerPage: value }),
                min: 1,
                max: 50,
                help: __('Number of organizations to display per page.', 'customers-manager-pn'),
              })
            )
          ),
          element.createElement(
            'div',
            { className: 'customers-manager-pn-organization-list-block-preview' },
            element.createElement(
              'div',
              { style: { padding: '20px', border: '1px dashed #ccc', borderRadius: '4px', textAlign: 'center' } },
              element.createElement('strong', { style: { display: 'block', marginBottom: '10px' } }, __('Organization List', 'customers-manager-pn')),
              element.createElement(
                'p',
                { style: { margin: '5px 0', color: '#666' } },
                __('This block will display the organization list on the frontend.', 'customers-manager-pn')
              ),
              element.createElement(
                'div',
                { style: { marginTop: '15px', fontSize: '12px', color: '#999' } },
                __('Search: ', 'customers-manager-pn') + (attributes.showSearch ? __('Yes', 'customers-manager-pn') : __('No', 'customers-manager-pn')),
                element.createElement('br'),
                __('Add button: ', 'customers-manager-pn') + (attributes.showAddButton ? __('Yes', 'customers-manager-pn') : __('No', 'customers-manager-pn')),
                element.createElement('br'),
                __('Per page: ', 'customers-manager-pn') + attributes.postsPerPage
              )
            )
          )
        )
      );
    },
    save: () => null, // The block is dynamically rendered on the server
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);

