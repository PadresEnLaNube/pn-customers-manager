(function(blocks, element, i18n, blockEditor, components) {
  var registerBlockType = blocks.registerBlockType;
  var Fragment = element.Fragment;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var TextControl = components.TextControl;
  var ToggleControl = components.ToggleControl;
  var SelectControl = components.SelectControl;
  var TextareaControl = components.TextareaControl;

  var data = window.pnCmWooCtaBlock || {};
  var colors = data.colors || {};
  var products = data.products || [];
  var categories = data.categories || [];
  var tags = data.tags || [];

  /**
   * Renders a color control with a native color picker + hex text input.
   */
  function ColorControl(props) {
    var label = props.label;
    var value = props.value || '';
    var placeholder = props.placeholder || '#000000';
    var onChange = props.onChange;
    var displayValue = value || placeholder;

    return element.createElement(
      'div',
      { style: { marginBottom: '16px' } },
      element.createElement('label', {
        style: { display: 'block', marginBottom: '4px', fontSize: '11px', fontWeight: '500', textTransform: 'uppercase' }
      }, label),
      element.createElement(
        'div',
        { style: { display: 'flex', gap: '8px', alignItems: 'center' } },
        element.createElement('input', {
          type: 'color',
          value: displayValue,
          onChange: function(e) { onChange(e.target.value); },
          style: { width: '40px', height: '36px', padding: '2px', border: '1px solid #ccc', borderRadius: '4px', cursor: 'pointer', background: 'none' }
        }),
        element.createElement('input', {
          type: 'text',
          value: value,
          placeholder: placeholder,
          onChange: function(e) { onChange(e.target.value); },
          style: { flex: '1', padding: '6px 10px', border: '1px solid #ccc', borderRadius: '4px', fontSize: '13px', fontFamily: 'monospace' }
        })
      )
    );
  }

  function getTargetOptions(ctaType) {
    if (ctaType === 'product') {
      return [{ label: __('-- Select product --', 'pn-customers-manager'), value: '0' }].concat(
        products.map(function(p) { return { label: p.title, value: String(p.id) }; })
      );
    }
    if (ctaType === 'category') {
      return [{ label: __('-- Select category --', 'pn-customers-manager'), value: '0' }].concat(
        categories.map(function(c) { return { label: c.name, value: String(c.id) }; })
      );
    }
    if (ctaType === 'tag') {
      return [{ label: __('-- Select tag --', 'pn-customers-manager'), value: '0' }].concat(
        tags.map(function(t) { return { label: t.name, value: String(t.id) }; })
      );
    }
    return [];
  }

  function getTargetLabel(ctaType) {
    if (ctaType === 'product') return __('Product', 'pn-customers-manager');
    if (ctaType === 'category') return __('Category', 'pn-customers-manager');
    if (ctaType === 'tag') return __('Tag', 'pn-customers-manager');
    return '';
  }

  registerBlockType('pn-customers-manager/woo-cta', {
    title: __('WooCommerce CTA', 'pn-customers-manager'),
    icon: 'megaphone',
    category: 'widgets',
    description: __('Call to Action block for WooCommerce products, categories, tags, or shop page.', 'pn-customers-manager'),
    attributes: {
      ctaType:         { type: 'string',  default: 'product' },
      heading:         { type: 'string',  default: '' },
      description:     { type: 'string',  default: '' },
      buttonText:      { type: 'string',  default: 'Ver producto' },
      targetId:        { type: 'number',  default: 0 },
      targetBlank:     { type: 'boolean', default: false },
      bgColor:         { type: 'string',  default: '' },
      textColor:       { type: 'string',  default: '' },
      buttonColor:     { type: 'string',  default: '' },
      buttonTextColor: { type: 'string',  default: '' },
      align:           { type: 'string',  default: 'center' },
      style:           { type: 'string',  default: 'card' },
      showImage:       { type: 'boolean', default: true }
    },

    edit: function(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      var bg = attributes.bgColor || colors.bg_color_main || '#0000aa';
      var text = attributes.textColor || '#ffffff';
      var btnBg = attributes.buttonColor || colors.color_main_alt || '#232323';
      var btnText = attributes.buttonTextColor || '#ffffff';

      var isCard = attributes.style === 'card';

      var containerStyle = {
        backgroundColor: bg,
        color: text,
        borderRadius: isCard ? '8px' : '0',
        overflow: 'hidden',
        display: isCard ? 'flex' : 'block',
        maxWidth: isCard ? '600px' : '100%',
        margin: '0 auto',
        textAlign: isCard ? 'left' : 'center',
        position: 'relative'
      };

      if (!isCard) {
        containerStyle.padding = '48px 24px';
      }

      var imageStyle = {
        width: isCard ? '200px' : '100%',
        minHeight: isCard ? '200px' : '0',
        maxHeight: isCard ? 'none' : '200px',
        backgroundColor: '#e0e0e0',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: '#999',
        fontSize: '13px',
        flexShrink: '0',
        objectFit: 'cover'
      };

      var contentStyle = {
        padding: isCard ? '20px' : '0',
        flex: '1'
      };

      var headingStyle = {
        margin: '0 0 8px',
        fontSize: '20px',
        fontWeight: '600',
        color: text
      };

      var descStyle = {
        margin: '0 0 16px',
        fontSize: '14px',
        opacity: '0.9',
        color: text
      };

      var btnStyle = {
        display: 'inline-block',
        padding: '10px 24px',
        backgroundColor: btnBg,
        color: btnText,
        borderRadius: '4px',
        fontSize: '14px',
        fontWeight: '500',
        textDecoration: 'none',
        border: 'none',
        cursor: 'pointer'
      };

      var headingText = attributes.heading || __('CTA Heading', 'pn-customers-manager');
      var descText = attributes.description || __('Add a description for your call to action.', 'pn-customers-manager');

      var imageEl = attributes.showImage
        ? element.createElement('div', { style: imageStyle }, __('Image', 'pn-customers-manager'))
        : null;

      return element.createElement(
        Fragment,
        null,
        element.createElement(
          InspectorControls,
          null,
          // Panel: CTA Settings
          element.createElement(
            PanelBody,
            { title: __('CTA Settings', 'pn-customers-manager'), initialOpen: true },
            element.createElement(SelectControl, {
              label: __('CTA Type', 'pn-customers-manager'),
              value: attributes.ctaType,
              options: [
                { label: __('Product', 'pn-customers-manager'), value: 'product' },
                { label: __('Category', 'pn-customers-manager'), value: 'category' },
                { label: __('Tag', 'pn-customers-manager'), value: 'tag' },
                { label: __('Shop', 'pn-customers-manager'), value: 'shop' }
              ],
              onChange: function(value) { setAttributes({ ctaType: value, targetId: 0 }); }
            }),
            attributes.ctaType !== 'shop'
              ? element.createElement(SelectControl, {
                  label: getTargetLabel(attributes.ctaType),
                  value: String(attributes.targetId),
                  options: getTargetOptions(attributes.ctaType),
                  onChange: function(value) { setAttributes({ targetId: parseInt(value, 10) || 0 }); }
                })
              : null,
            element.createElement(TextControl, {
              label: __('Heading', 'pn-customers-manager'),
              value: attributes.heading,
              onChange: function(value) { setAttributes({ heading: value }); }
            }),
            element.createElement(TextareaControl, {
              label: __('Description', 'pn-customers-manager'),
              value: attributes.description,
              onChange: function(value) { setAttributes({ description: value }); }
            }),
            element.createElement(TextControl, {
              label: __('Button text', 'pn-customers-manager'),
              value: attributes.buttonText,
              onChange: function(value) { setAttributes({ buttonText: value }); }
            }),
            element.createElement(ToggleControl, {
              label: __('Open in new tab', 'pn-customers-manager'),
              checked: attributes.targetBlank,
              onChange: function(value) { setAttributes({ targetBlank: value }); }
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
                { label: __('Card', 'pn-customers-manager'), value: 'card' },
                { label: __('Banner', 'pn-customers-manager'), value: 'banner' }
              ],
              onChange: function(value) { setAttributes({ style: value }); }
            }),
            element.createElement(ColorControl, {
              label: __('Background color', 'pn-customers-manager'),
              value: attributes.bgColor,
              placeholder: colors.bg_color_main || '#0000aa',
              onChange: function(value) { setAttributes({ bgColor: value }); }
            }),
            element.createElement(ColorControl, {
              label: __('Text color', 'pn-customers-manager'),
              value: attributes.textColor,
              placeholder: '#ffffff',
              onChange: function(value) { setAttributes({ textColor: value }); }
            }),
            element.createElement(ColorControl, {
              label: __('Button color', 'pn-customers-manager'),
              value: attributes.buttonColor,
              placeholder: colors.color_main_alt || '#232323',
              onChange: function(value) { setAttributes({ buttonColor: value }); }
            }),
            element.createElement(ColorControl, {
              label: __('Button text color', 'pn-customers-manager'),
              value: attributes.buttonTextColor,
              placeholder: '#ffffff',
              onChange: function(value) { setAttributes({ buttonTextColor: value }); }
            }),
            element.createElement(SelectControl, {
              label: __('Alignment', 'pn-customers-manager'),
              value: attributes.align,
              options: [
                { label: __('Left', 'pn-customers-manager'), value: 'left' },
                { label: __('Center', 'pn-customers-manager'), value: 'center' },
                { label: __('Right', 'pn-customers-manager'), value: 'right' }
              ],
              onChange: function(value) { setAttributes({ align: value }); }
            }),
            element.createElement(ToggleControl, {
              label: __('Show image', 'pn-customers-manager'),
              checked: attributes.showImage,
              onChange: function(value) { setAttributes({ showImage: value }); }
            })
          )
        ),
        // Editor preview
        element.createElement(
          'div',
          { style: { textAlign: attributes.align } },
          element.createElement(
            'div',
            { className: 'pn-cm-woo-cta pn-cm-woo-cta--' + attributes.style, style: containerStyle },
            imageEl,
            element.createElement(
              'div',
              { style: contentStyle },
              element.createElement('h3', { style: headingStyle }, headingText),
              element.createElement('p', { style: descStyle }, descText),
              element.createElement('span', { style: btnStyle }, attributes.buttonText || 'Ver producto')
            )
          )
        )
      );
    },

    save: function() { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);
