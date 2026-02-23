(function(blocks, element, i18n, blockEditor, components) {
  const { registerBlockType } = blocks;
  const { Fragment } = element;
  const { __ } = i18n;
  const { InspectorControls } = blockEditor;
  const { PanelBody, TextControl, ToggleControl, RangeControl } = components;

  registerBlockType('pn-customers-manager/funnel-list', {
    title: __('Funnel List', 'pn-customers-manager'),
    icon: 'list-view',
    category: 'widgets',
    description: __('Displays a list of funnels from the CRM.', 'pn-customers-manager'),
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
              { title: __('List Settings', 'pn-customers-manager'), initialOpen: true },
              element.createElement(ToggleControl, {
                label: __('Show search', 'pn-customers-manager'),
                checked: attributes.showSearch,
                onChange: (value) => setAttributes({ showSearch: value }),
                help: __('Shows the search field to filter funnels.', 'pn-customers-manager'),
              }),
              element.createElement(ToggleControl, {
                label: __('Show add button', 'pn-customers-manager'),
                checked: attributes.showAddButton,
                onChange: (value) => setAttributes({ showAddButton: value }),
                help: __('Shows the button to add new funnels.', 'pn-customers-manager'),
              }),
              element.createElement(RangeControl, {
                label: __('Funnels per page', 'pn-customers-manager'),
                value: attributes.postsPerPage,
                onChange: (value) => setAttributes({ postsPerPage: value }),
                min: 1,
                max: 50,
                help: __('Number of funnels to display per page.', 'pn-customers-manager'),
              })
            )
          ),
          element.createElement(
            'div',
            { className: 'pn-customers-manager-funnel-list-block-preview' },
            element.createElement(
              'div',
              { style: { padding: '20px', border: '1px dashed #ccc', borderRadius: '4px', textAlign: 'center' } },
              element.createElement('strong', { style: { display: 'block', marginBottom: '10px' } }, __('Funnel List', 'pn-customers-manager')),
              element.createElement(
                'p',
                { style: { margin: '5px 0', color: '#666' } },
                __('This block will display the funnel list on the frontend.', 'pn-customers-manager')
              ),
              element.createElement(
                'div',
                { style: { marginTop: '15px', fontSize: '12px', color: '#999' } },
                __('Search: ', 'pn-customers-manager') + (attributes.showSearch ? __('Yes', 'pn-customers-manager') : __('No', 'pn-customers-manager')),
                element.createElement('br'),
                __('Add button: ', 'pn-customers-manager') + (attributes.showAddButton ? __('Yes', 'pn-customers-manager') : __('No', 'pn-customers-manager')),
                element.createElement('br'),
                __('Per page: ', 'pn-customers-manager') + attributes.postsPerPage
              )
            )
          )
        )
      );
    },
    save: () => null, // The block is dynamically rendered on the server
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);

