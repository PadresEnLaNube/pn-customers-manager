(function(blocks, element, i18n, blockEditor, components) {
  var registerBlockType = blocks.registerBlockType;
  var Fragment = element.Fragment;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var TextControl = components.TextControl;
  var TextareaControl = components.TextareaControl;
  var ToggleControl = components.ToggleControl;
  var SelectControl = components.SelectControl;

  registerBlockType('pn-customers-manager/social-media', {
    title: __('Social Media pn-customers-manager', 'pn-customers-manager'),
    icon: 'share',
    category: 'widgets',
    description: __('Display social media links in Linktree style.', 'pn-customers-manager'),
    attributes: {
      title:           { type: 'string',  default: '' },
      subtitle:        { type: 'string',  default: '' },
      profileImage:    { type: 'string',  default: '' },
      style:           { type: 'string',  default: 'default' },
      columns:         { type: 'string',  default: '1' },
      showIcons:       { type: 'boolean', default: true },
      buttonStyle:     { type: 'string',  default: 'solid' },
      bgColor:         { type: 'string',  default: '' },
      buttonColor:     { type: 'string',  default: '' },
      buttonTextColor: { type: 'string',  default: '' }
    },

    edit: function(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      return element.createElement(
        Fragment,
        null,
        // Inspector controls (right sidebar)
        element.createElement(
          InspectorControls,
          null,
          // Panel: Content settings
          element.createElement(
            PanelBody,
            { title: __('Content', 'pn-customers-manager'), initialOpen: true },
            element.createElement(TextControl, {
              label: __('Title', 'pn-customers-manager'),
              value: attributes.title,
              placeholder: __('Follow us on social media', 'pn-customers-manager'),
              onChange: function(value) { setAttributes({ title: value }); }
            }),
            element.createElement(TextareaControl, {
              label: __('Subtitle', 'pn-customers-manager'),
              value: attributes.subtitle,
              placeholder: __('Connect with us', 'pn-customers-manager'),
              onChange: function(value) { setAttributes({ subtitle: value }); }
            }),
            element.createElement(TextControl, {
              label: __('Profile Image ID', 'pn-customers-manager'),
              value: attributes.profileImage,
              help: __('Enter the attachment ID of the profile image', 'pn-customers-manager'),
              type: 'number',
              onChange: function(value) { setAttributes({ profileImage: value }); }
            })
          ),
          // Panel: Display settings
          element.createElement(
            PanelBody,
            { title: __('Display', 'pn-customers-manager'), initialOpen: false },
            element.createElement(SelectControl, {
              label: __('Style', 'pn-customers-manager'),
              value: attributes.style,
              options: [
                { label: 'Default', value: 'default' },
                { label: 'Compact', value: 'compact' },
                { label: 'Minimal', value: 'minimal' }
              ],
              onChange: function(value) { setAttributes({ style: value }); }
            }),
            element.createElement(SelectControl, {
              label: __('Button Style', 'pn-customers-manager'),
              value: attributes.buttonStyle,
              options: [
                { label: 'Solid', value: 'solid' },
                { label: 'Outline', value: 'outline' },
                { label: 'Minimal', value: 'minimal' }
              ],
              onChange: function(value) { setAttributes({ buttonStyle: value }); }
            }),
            element.createElement(SelectControl, {
              label: __('Columns', 'pn-customers-manager'),
              value: attributes.columns,
              options: [
                { label: '1 Column', value: '1' },
                { label: '2 Columns', value: '2' }
              ],
              onChange: function(value) { setAttributes({ columns: value }); }
            }),
            element.createElement(ToggleControl, {
              label: __('Show Icons', 'pn-customers-manager'),
              checked: attributes.showIcons,
              onChange: function(value) { setAttributes({ showIcons: value }); }
            })
          ),
          // Panel: Colors
          element.createElement(
            PanelBody,
            { title: __('Colors', 'pn-customers-manager'), initialOpen: false },
            element.createElement(TextControl, {
              label: __('Background Color', 'pn-customers-manager'),
              value: attributes.bgColor,
              placeholder: '#ffffff',
              help: __('Leave empty to use default', 'pn-customers-manager'),
              onChange: function(value) { setAttributes({ bgColor: value }); }
            }),
            element.createElement(TextControl, {
              label: __('Button Color', 'pn-customers-manager'),
              value: attributes.buttonColor,
              placeholder: '#667eea',
              help: __('Leave empty to use default', 'pn-customers-manager'),
              onChange: function(value) { setAttributes({ buttonColor: value }); }
            }),
            element.createElement(TextControl, {
              label: __('Button Text Color', 'pn-customers-manager'),
              value: attributes.buttonTextColor,
              placeholder: '#ffffff',
              help: __('Leave empty to use default', 'pn-customers-manager'),
              onChange: function(value) { setAttributes({ buttonTextColor: value }); }
            })
          )
        ),
        // Editor preview
        element.createElement(
          'div',
          {
            style: {
              padding: '20px',
              border: '1px solid #e0e0e0',
              borderRadius: '8px',
              textAlign: 'center',
              background: attributes.bgColor || '#f9f9f9'
            }
          },
          [
            element.createElement('div', {
              key: 'title',
              style: {
                fontSize: '20px',
                marginBottom: '10px',
                fontWeight: '600'
              }
            }, attributes.title || __('Social Media Links', 'pn-customers-manager')),
            element.createElement('div', {
              key: 'subtitle',
              style: {
                fontSize: '14px',
                color: '#666',
                marginBottom: '20px'
              }
            }, attributes.subtitle || __('Configure links in plugin settings', 'pn-customers-manager')),
            element.createElement('div', {
              key: 'button',
              style: {
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '10px',
                padding: '12px 20px',
                background: attributes.buttonStyle === 'solid' ? (attributes.buttonColor || '#667eea') : '#fff',
                color: attributes.buttonStyle === 'solid' ? (attributes.buttonTextColor || '#fff') : (attributes.buttonColor || '#667eea'),
                border: attributes.buttonStyle === 'outline' ? '2px solid ' + (attributes.buttonColor || '#667eea') : 'none',
                borderRadius: '8px',
                maxWidth: '300px',
                margin: '0 auto'
              }
            },
              (function() {
                var btnChildren = [];
                if (attributes.showIcons) {
                  btnChildren.push(element.createElement('i', {
                    key: 'icon',
                    className: 'material-icons-outlined',
                    style: { fontSize: '20px' }
                  }, 'share'));
                }
                btnChildren.push(element.createElement('span', { key: 'text' }, __('Example Social Link', 'pn-customers-manager')));
                return btnChildren;
              })()
            ),
            element.createElement('p', {
              key: 'help',
              style: {
                marginTop: '20px',
                fontSize: '12px',
                color: '#999'
              }
            }, __('Social media links are configured in Marketing > Redes Sociales settings', 'pn-customers-manager'))
          ]
        )
      );
    },

    save: function() { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);
