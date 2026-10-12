(function(blocks, element, i18n, blockEditor, components) {
  var registerBlockType = blocks.registerBlockType;
  var Fragment = element.Fragment;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var TextControl = components.TextControl;
  var CheckboxControl = components.CheckboxControl;
  var Button = components.Button;

  var blockData = window.pnCmProductCalendarBlock || {};
  var products = blockData.products || [];
  var labels = blockData.i18n || {};

  registerBlockType('pn-customers-manager/product-calendar', {
    title: labels.title || __('Calendario de producto', 'pn-customers-manager'),
    icon: 'calendar-alt',
    category: 'widgets',
    description: __('Bloque para mostrar un calendario centrado y extractos con CTA de los productos con Calendario de producto seleccionados.', 'pn-customers-manager'),
    attributes: {
      selectedProducts: {
        type: 'array',
        default: null
      },
      heading: {
        type: 'string',
        default: ''
      },
      buttonText: {
        type: 'string',
        default: labels.defaultBtnText || 'Comprar producto'
      }
    },

    edit: function(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      var allIds = products.map(function(p) { return p.id; });
      var selected = attributes.selectedProducts;

      // Initialize with all products if selected is null (first insert)
      if (selected === null || typeof selected === 'undefined') {
        selected = allIds;
        setAttributes({ selectedProducts: allIds });
      }

      function toggleProduct(pid, checked) {
        var pidNum = parseInt(pid, 10);
        var updated = selected.slice();
        if (checked) {
          if (updated.indexOf(pidNum) === -1) {
            updated.push(pidNum);
          }
        } else {
          updated = updated.filter(function(id) { return id !== pidNum; });
        }
        setAttributes({ selectedProducts: updated });
      }

      function selectAll() {
        setAttributes({ selectedProducts: allIds.slice() });
      }

      function deselectAll() {
        setAttributes({ selectedProducts: [] });
      }

      var selectedProductsList = products.filter(function(p) {
        return selected.indexOf(p.id) !== -1;
      });

      return element.createElement(
        Fragment,
        null,
        element.createElement(
          InspectorControls,
          null,
          element.createElement(
            PanelBody,
            { title: __('Ajustes del Calendario de producto', 'pn-customers-manager'), initialOpen: true },
            element.createElement(TextControl, {
              label: labels.heading || __('Título del bloque', 'pn-customers-manager'),
              value: attributes.heading,
              onChange: function(val) { setAttributes({ heading: val }); }
            }),
            element.createElement(TextControl, {
              label: labels.buttonText || __('Texto del botón CTA', 'pn-customers-manager'),
              value: attributes.buttonText,
              onChange: function(val) { setAttributes({ buttonText: val }); }
            }),
            element.createElement('div', { style: { marginTop: '20px' } },
              element.createElement('label', {
                style: { display: 'block', fontWeight: '700', marginBottom: '8px', fontSize: '13px' }
              }, labels.selectProducts || __('Productos a mostrar en el bloque', 'pn-customers-manager')),

              products.length > 0 ? element.createElement('div', { style: { display: 'flex', gap: '8px', marginBottom: '12px' } },
                element.createElement(Button, {
                  isSecondary: true,
                  isSmall: true,
                  onClick: selectAll
                }, labels.selectAll || __('Seleccionar todos', 'pn-customers-manager')),
                element.createElement(Button, {
                  isSecondary: true,
                  isSmall: true,
                  onClick: deselectAll
                }, labels.deselectAll || __('Deseleccionar todos', 'pn-customers-manager'))
              ) : null,

              products.length === 0
                ? element.createElement('p', { style: { color: '#888', fontSize: '13px' } }, labels.noProductsFound || __('No hay productos con Calendario de producto activado.', 'pn-customers-manager'))
                : products.map(function(p) {
                    var isChecked = selected.indexOf(p.id) !== -1;
                    return element.createElement(CheckboxControl, {
                      key: p.id,
                      label: p.title + ' (ID: ' + p.id + ')',
                      checked: isChecked,
                      onChange: function(checked) { toggleProduct(p.id, checked); }
                    });
                  })
            )
          )
        ),
        element.createElement(
          'div',
          { className: 'pn-cm-product-calendar-block-preview', style: { border: '1px solid #e2e8f0', borderRadius: '12px', padding: '24px', background: '#fff', textAlign: 'center' } },
          attributes.heading ? element.createElement('h3', { style: { marginTop: '0', marginBottom: '18px', textAlign: 'center' } }, attributes.heading) : null,
          element.createElement('div', { style: { background: '#fcfcfc', border: '1px solid #e2e8f0', padding: '20px', borderRadius: '12px', maxWidth: '380px', margin: '0 auto 28px', textAlign: 'center' } },
            element.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontWeight: 'bold', marginBottom: '14px' } },
              element.createElement('button', { type: 'button', disabled: true, className: 'pn-cm-calendar-nav-btn' },
                element.createElement('i', { className: 'material-icons-outlined' }, 'chevron_left')
              ),
              element.createElement('span', { style: { fontSize: '16px', fontWeight: '700' } }, __('Vista previa - Calendario de 1 mes', 'pn-customers-manager')),
              element.createElement('button', { type: 'button', disabled: true, className: 'pn-cm-calendar-nav-btn' },
                element.createElement('i', { className: 'material-icons-outlined' }, 'chevron_right')
              )
            ),
            element.createElement('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', gap: '8px', fontSize: '12px', fontWeight: 'bold', color: '#666' } },
              element.createElement('div', null, 'Lu'),
              element.createElement('div', null, 'Ma'),
              element.createElement('div', null, 'Mi'),
              element.createElement('div', null, 'Ju'),
              element.createElement('div', null, 'Vi'),
              element.createElement('div', null, 'Sá'),
              element.createElement('div', null, 'Do')
            )
          ),
          element.createElement('div', { style: { display: 'flex', flexDirection: 'column', gap: '16px', textAlign: 'left' } },
            element.createElement('strong', { style: { fontSize: '15px', color: '#333' } }, __('Productos seleccionados (' + selectedProductsList.length + '):', 'pn-customers-manager')),
            selectedProductsList.length === 0
              ? element.createElement('p', { style: { color: '#e53e3e', fontStyle: 'italic', background: '#fff5f5', padding: '12px', borderRadius: '6px', border: '1px solid #fed7d7' } }, __('Ningún producto seleccionado. Marca los productos en el panel lateral.', 'pn-customers-manager'))
              : selectedProductsList.map(function(p) {
                  return element.createElement('div', {
                    key: p.id,
                    style: { border: '1px solid #e2e8f0', borderRadius: '8px', padding: '16px', background: '#fafafa', display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '12px' }
                  },
                    element.createElement('div', null,
                      element.createElement('div', { style: { fontWeight: 'bold', fontSize: '16px' } }, p.title),
                      element.createElement('div', { style: { fontSize: '13px', color: '#0000aa', marginTop: '4px', display: 'flex', alignItems: 'center', gap: '4px', fontWeight: '600' } },
                        element.createElement('i', { className: 'material-icons-outlined', style: { fontSize: '16px' } }, 'calendar_today'),
                        element.createElement('span', null, __('Fechas cargadas automáticamente desde la edición del producto', 'pn-customers-manager'))
                      )
                    ),
                    element.createElement('span', {
                      style: { background: '#0000aa', color: '#fff', padding: '8px 16px', borderRadius: '6px', fontSize: '13px', fontWeight: '600', display: 'inline-flex', alignItems: 'center', gap: '6px' }
                    },
                      attributes.buttonText || 'Comprar producto',
                      element.createElement('i', { className: 'material-icons-outlined', style: { fontSize: '16px' } }, 'arrow_forward')
                    )
                  );
                })
          )
        )
      );
    },
    save: function() { return null; } // Dynamic render callback in PHP
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);
