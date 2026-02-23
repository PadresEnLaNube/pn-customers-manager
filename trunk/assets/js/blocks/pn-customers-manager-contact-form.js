(function(blocks, element, i18n, blockEditor, components) {
  const { registerBlockType } = blocks;
  const { Fragment } = element;
  const { __ } = i18n;
  const { InspectorControls } = blockEditor;
  const { PanelBody, TextControl, ToggleControl, TextareaControl } = components;

  registerBlockType('pn-customers-manager/contact-form', {
    title: __('Formulario de contacto pn-customers-manager', 'pn-customers-manager'),
    icon: 'email',
    category: 'widgets',
    attributes: {
      showTitle: {
        type: 'boolean',
        default: true,
      },
      title: {
        type: 'string',
        default: '',
      },
      description: {
        type: 'string',
        default: '',
      },
      recipientEmail: {
        type: 'string',
        default: '',
      },
    },
    edit: function(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      return (
        element.createElement(
          Fragment,
          null,
          element.createElement(
            InspectorControls,
            null,
            element.createElement(
              PanelBody,
              { title: __('Contact form settings', 'pn-customers-manager'), initialOpen: true },
              element.createElement(ToggleControl, {
                label: __('Show title', 'pn-customers-manager'),
                checked: attributes.showTitle,
                onChange: function(value) { setAttributes({ showTitle: value }); },
              }),
              element.createElement(TextControl, {
                label: __('Title', 'pn-customers-manager'),
                value: attributes.title,
                onChange: function(value) { setAttributes({ title: value }); },
                placeholder: __('Contact', 'pn-customers-manager'),
              }),
              element.createElement(TextareaControl, {
                label: __('Description', 'pn-customers-manager'),
                value: attributes.description,
                onChange: function(value) { setAttributes({ description: value }); },
                placeholder: __('Send us a message and we will get back to you as soon as possible.', 'pn-customers-manager'),
              }),
              element.createElement(TextControl, {
                label: __('Recipient email', 'pn-customers-manager'),
                value: attributes.recipientEmail,
                onChange: function(value) { setAttributes({ recipientEmail: value }); },
                help: __('Leave empty to use the site administrator email.', 'pn-customers-manager'),
                placeholder: 'admin@example.com',
              })
            )
          ),
          element.createElement(
            'div',
            { className: 'pn-customers-manager-contact-form-block-preview', style: { padding: '20px', backgroundColor: '#f9f9f9', borderRadius: '4px', border: '1px dashed #ccc' } },
            element.createElement('strong', null, __('Contact form', 'pn-customers-manager')),
            element.createElement(
              'p',
              { style: { marginBottom: '10px', color: '#666' } },
              __('This is a dynamic block. The real form will be displayed in the front.', 'pn-customers-manager')
            ),
            element.createElement(
              'div',
              { style: { display: 'flex', gap: '10px', marginBottom: '8px' } },
              element.createElement('div', { style: { flex: 1, height: '36px', backgroundColor: '#e0e0e0', borderRadius: '4px' } }),
              element.createElement('div', { style: { flex: 1, height: '36px', backgroundColor: '#e0e0e0', borderRadius: '4px' } })
            ),
            element.createElement('div', { style: { height: '36px', backgroundColor: '#e0e0e0', borderRadius: '4px', marginBottom: '8px' } }),
            element.createElement('div', { style: { height: '80px', backgroundColor: '#e0e0e0', borderRadius: '4px', marginBottom: '8px' } }),
            element.createElement('div', { style: { width: '140px', height: '36px', backgroundColor: 'var(--pn-customers-manager-color-main, #0000aa)', borderRadius: '4px' } })
          )
        )
      );
    },
    save: function() { return null; },
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);
