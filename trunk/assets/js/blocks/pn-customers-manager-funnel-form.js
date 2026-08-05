(function(blocks, element, i18n, blockEditor, components) {
  var registerBlockType = blocks.registerBlockType;
  var Fragment = element.Fragment;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;

  var funnelsData = (window.pnCmFunnelFormBlock && window.pnCmFunnelFormBlock.funnels) || [];

  function getFunnelOptions() {
    var opts = [{ label: __('-- Select funnel --', 'pn-customers-manager'), value: '' }];
    for (var i = 0; i < funnelsData.length; i++) {
      opts.push({
        label: funnelsData[i].funnelTitle,
        value: String(funnelsData[i].funnelId),
      });
    }
    return opts;
  }

  function getFormOptions(funnelId) {
    var opts = [{ label: __('-- Select form --', 'pn-customers-manager'), value: '' }];
    if (!funnelId) return opts;
    for (var i = 0; i < funnelsData.length; i++) {
      if (String(funnelsData[i].funnelId) === String(funnelId)) {
        var nodes = funnelsData[i].formNodes || [];
        for (var j = 0; j < nodes.length; j++) {
          opts.push({
            label: nodes[j].label,
            value: nodes[j].nodeId,
          });
        }
        break;
      }
    }
    return opts;
  }

  registerBlockType('pn-customers-manager/funnel-form', {
    title: __('Funnel Form', 'pn-customers-manager'),
    icon: 'feedback',
    category: 'widgets',
    attributes: {
      funnelId: {
        type: 'number',
        default: 0,
      },
      nodeId: {
        type: 'string',
        default: '',
      },
    },
    edit: function(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      var selectedFunnelLabel = '';
      var selectedFormLabel = '';
      if (attributes.funnelId) {
        for (var i = 0; i < funnelsData.length; i++) {
          if (funnelsData[i].funnelId === attributes.funnelId) {
            selectedFunnelLabel = funnelsData[i].funnelTitle;
            var nodes = funnelsData[i].formNodes || [];
            for (var j = 0; j < nodes.length; j++) {
              if (nodes[j].nodeId === attributes.nodeId) {
                selectedFormLabel = nodes[j].label;
                break;
              }
            }
            break;
          }
        }
      }

      return element.createElement(
        Fragment,
        null,
        element.createElement(
          InspectorControls,
          null,
          element.createElement(
            PanelBody,
            { title: __('Funnel Form settings', 'pn-customers-manager'), initialOpen: true },
            element.createElement(SelectControl, {
              label: __('Funnel', 'pn-customers-manager'),
              value: attributes.funnelId ? String(attributes.funnelId) : '',
              options: getFunnelOptions(),
              onChange: function(value) {
                setAttributes({ funnelId: value ? parseInt(value, 10) : 0, nodeId: '' });
              },
            }),
            element.createElement(SelectControl, {
              label: __('Form', 'pn-customers-manager'),
              value: attributes.nodeId || '',
              options: getFormOptions(attributes.funnelId),
              onChange: function(value) {
                setAttributes({ nodeId: value });
              },
            })
          )
        ),
        element.createElement(
          'div',
          { className: 'pn-cm-funnel-form-block-preview', style: { padding: '20px', backgroundColor: '#f9f9f9', borderRadius: '4px', border: '1px dashed #ccc' } },
          element.createElement('strong', null, __('Funnel Form', 'pn-customers-manager')),
          attributes.funnelId && attributes.nodeId
            ? element.createElement(
                'p',
                { style: { marginBottom: '10px', color: '#666' } },
                selectedFunnelLabel + ' — ' + selectedFormLabel
              )
            : element.createElement(
                'p',
                { style: { marginBottom: '10px', color: '#999' } },
                __('Select a funnel and form in the block settings.', 'pn-customers-manager')
              ),
          element.createElement(
            'div',
            { style: { display: 'flex', gap: '10px', marginBottom: '8px' } },
            element.createElement('div', { style: { flex: 1, height: '36px', backgroundColor: '#e0e0e0', borderRadius: '4px' } }),
            element.createElement('div', { style: { flex: 1, height: '36px', backgroundColor: '#e0e0e0', borderRadius: '4px' } })
          ),
          element.createElement('div', { style: { height: '36px', backgroundColor: '#e0e0e0', borderRadius: '4px', marginBottom: '8px' } }),
          element.createElement('div', { style: { width: '140px', height: '36px', backgroundColor: 'var(--pn-customers-manager-color-main, #0000aa)', borderRadius: '4px' } })
        )
      );
    },
    save: function() { return null; },
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);
