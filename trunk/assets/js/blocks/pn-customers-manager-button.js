(function(blocks, element, i18n, blockEditor, components) {
  var registerBlockType = blocks.registerBlockType;
  var Fragment = element.Fragment;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var TextControl = components.TextControl;
  var ToggleControl = components.ToggleControl;
  var SelectControl = components.SelectControl;

  registerBlockType('pn-customers-manager/button', {
    title: __('Button pn-customers-manager', 'pn-customers-manager'),
    icon: 'button',
    category: 'widgets',
    description: __('Configurable button with styles, icons and link options.', 'pn-customers-manager'),
    attributes: {
      text:         { type: 'string',  default: '' },
      url:          { type: 'string',  default: '#' },
      targetBlank:  { type: 'boolean', default: false },
      nofollow:     { type: 'boolean', default: false },
      style:        { type: 'string',  default: 'solid' },
      color:        { type: 'string',  default: '' },
      textColor:    { type: 'string',  default: '' },
      size:         { type: 'string',  default: 'medium' },
      align:        { type: 'string',  default: 'center' },
      icon:         { type: 'string',  default: '' },
      iconPosition: { type: 'string',  default: 'left' }
    },

    edit: function(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      // Size padding map
      var sizePadding = { small: '6px 16px', medium: '10px 24px', large: '14px 32px' };
      var padding = sizePadding[attributes.size] || sizePadding.medium;

      // Compute inline styles based on style attribute
      var btnStyle = {
        display: 'inline-flex',
        alignItems: 'center',
        gap: '6px',
        padding: padding,
        borderRadius: '4px',
        fontSize: '14px',
        fontFamily: 'inherit',
        textDecoration: 'none',
        cursor: 'pointer',
        border: '2px solid transparent'
      };

      var bgColor = attributes.color || '#0000aa';
      var txtColor = attributes.textColor || '';

      if (attributes.style === 'solid') {
        btnStyle.backgroundColor = bgColor;
        btnStyle.color = txtColor || '#ffffff';
        btnStyle.borderColor = bgColor;
      } else if (attributes.style === 'outline') {
        btnStyle.backgroundColor = 'transparent';
        btnStyle.borderColor = bgColor;
        btnStyle.color = txtColor || bgColor;
      } else {
        btnStyle.backgroundColor = 'transparent';
        btnStyle.color = txtColor || bgColor;
        btnStyle.borderColor = 'transparent';
      }

      var displayText = attributes.text || __('Button', 'pn-customers-manager');

      // Build icon element
      var iconEl = attributes.icon
        ? element.createElement('i', { className: 'material-icons-outlined', style: { fontSize: '18px', verticalAlign: 'middle' } }, attributes.icon)
        : null;

      var btnChildren = [];
      if (iconEl && attributes.iconPosition === 'left') { btnChildren.push(iconEl); }
      btnChildren.push(element.createElement('span', null, displayText));
      if (iconEl && attributes.iconPosition === 'right') { btnChildren.push(iconEl); }

      return element.createElement(
        Fragment,
        null,
        // Inspector controls (right sidebar)
        element.createElement(
          InspectorControls,
          null,
          // Panel: Button settings
          element.createElement(
            PanelBody,
            { title: __('Button settings', 'pn-customers-manager'), initialOpen: true },
            element.createElement(TextControl, {
              label: __('Button text', 'pn-customers-manager'),
              value: attributes.text,
              onChange: function(value) { setAttributes({ text: value }); }
            }),
            element.createElement(TextControl, {
              label: __('URL', 'pn-customers-manager'),
              value: attributes.url,
              onChange: function(value) { setAttributes({ url: value }); }
            }),
            element.createElement(ToggleControl, {
              label: __('Open in new tab', 'pn-customers-manager'),
              checked: attributes.targetBlank,
              onChange: function(value) { setAttributes({ targetBlank: value }); }
            }),
            element.createElement(ToggleControl, {
              label: __('Add rel=nofollow', 'pn-customers-manager'),
              checked: attributes.nofollow,
              onChange: function(value) { setAttributes({ nofollow: value }); }
            })
          ),
          // Panel: Appearance
          element.createElement(
            PanelBody,
            { title: __('Appearance', 'pn-customers-manager'), initialOpen: false },
            element.createElement(SelectControl, {
              label: __('Style', 'pn-customers-manager'),
              value: attributes.style,
              options: [
                { label: 'Solid', value: 'solid' },
                { label: 'Outline', value: 'outline' },
                { label: 'Transparent', value: 'transparent' }
              ],
              onChange: function(value) { setAttributes({ style: value }); }
            }),
            element.createElement(TextControl, {
              label: __('Background color', 'pn-customers-manager'),
              value: attributes.color,
              placeholder: '#0000aa',
              onChange: function(value) { setAttributes({ color: value }); }
            }),
            element.createElement(TextControl, {
              label: __('Text color', 'pn-customers-manager'),
              value: attributes.textColor,
              placeholder: '#ffffff',
              onChange: function(value) { setAttributes({ textColor: value }); }
            }),
            element.createElement(SelectControl, {
              label: __('Size', 'pn-customers-manager'),
              value: attributes.size,
              options: [
                { label: 'Small', value: 'small' },
                { label: 'Medium', value: 'medium' },
                { label: 'Large', value: 'large' }
              ],
              onChange: function(value) { setAttributes({ size: value }); }
            }),
            element.createElement(SelectControl, {
              label: __('Alignment', 'pn-customers-manager'),
              value: attributes.align,
              options: [
                { label: 'Left', value: 'left' },
                { label: 'Center', value: 'center' },
                { label: 'Right', value: 'right' }
              ],
              onChange: function(value) { setAttributes({ align: value }); }
            })
          ),
          // Panel: Icon
          element.createElement(
            PanelBody,
            { title: __('Icon', 'pn-customers-manager'), initialOpen: false },
            element.createElement(TextControl, {
              label: __('Material Icon name', 'pn-customers-manager'),
              value: attributes.icon,
              help: __('e.g. shopping_cart, arrow_forward', 'pn-customers-manager'),
              onChange: function(value) { setAttributes({ icon: value }); }
            }),
            attributes.icon
              ? element.createElement(SelectControl, {
                  label: __('Icon position', 'pn-customers-manager'),
                  value: attributes.iconPosition,
                  options: [
                    { label: 'Left', value: 'left' },
                    { label: 'Right', value: 'right' }
                  ],
                  onChange: function(value) { setAttributes({ iconPosition: value }); }
                })
              : null
          )
        ),
        // Editor preview
        element.createElement(
          'div',
          { style: { textAlign: attributes.align } },
          element.createElement('span', { style: btnStyle }, btnChildren)
        )
      );
    },

    save: function() { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);
