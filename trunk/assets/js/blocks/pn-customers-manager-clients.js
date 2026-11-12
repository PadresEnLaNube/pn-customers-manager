/**
 * Clients List Gutenberg Block.
 *
 * @since 1.2.48
 */
(function (blocks, element, blockEditor, components) {
  var el = element.createElement;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var ToggleControl = components.ToggleControl;
  var SelectControl = components.SelectControl;
  var RangeControl = components.RangeControl;

  blocks.registerBlockType('pn-customers-manager/clients-list', {
    title: 'Clients List',
    icon: 'groups',
    category: 'widgets',
    description: 'Display a CRM clients list with stage filtering.',

    attributes: {
      showSearch: { type: 'boolean', default: true },
      showStageFilter: { type: 'boolean', default: true },
      viewMode: { type: 'string', default: 'grid' },
      perPage: { type: 'number', default: 20 },
      showFlags: { type: 'boolean', default: true },
    },

    edit: function (props) {
      var attrs = props.attributes;

      return el(
        element.Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: 'Settings', initialOpen: true },
            el(ToggleControl, {
              label: 'Show search',
              checked: attrs.showSearch,
              onChange: function (val) { props.setAttributes({ showSearch: val }); },
            }),
            el(ToggleControl, {
              label: 'Show stage filter',
              checked: attrs.showStageFilter,
              onChange: function (val) { props.setAttributes({ showStageFilter: val }); },
            }),
            el(SelectControl, {
              label: 'View mode',
              value: attrs.viewMode,
              options: [
                { label: 'Grid', value: 'grid' },
                { label: 'List', value: 'list' },
              ],
              onChange: function (val) { props.setAttributes({ viewMode: val }); },
            }),
            el(RangeControl, {
              label: 'Per page',
              value: attrs.perPage,
              min: 5,
              max: 100,
              onChange: function (val) { props.setAttributes({ perPage: val }); },
            }),
            el(ToggleControl, {
              label: 'Show country flags',
              checked: attrs.showFlags,
              onChange: function (val) { props.setAttributes({ showFlags: val }); },
            })
          )
        ),
        el(
          'div',
          { className: 'pn-cm-clients-block-preview', style: { padding: '40px 20px', textAlign: 'center', background: '#f6f7f7', borderRadius: '8px', border: '1px dashed #c3c4c7' } },
          el('span', { className: 'dashicons dashicons-groups', style: { fontSize: '32px', color: '#787c82', display: 'block', marginBottom: '8px' } }),
          el('p', { style: { margin: 0, color: '#787c82' } }, 'Clients List — ' + attrs.viewMode + ' view, ' + attrs.perPage + ' per page')
        )
      );
    },

    save: function () {
      return null; // Server-side rendered.
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components
);
