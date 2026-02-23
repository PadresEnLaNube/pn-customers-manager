(function(blocks, element, i18n) {
  var registerBlockType = blocks.registerBlockType;
  var __ = i18n.__;

  registerBlockType('pn-customers-manager/commercial-panel', {
    title: __('Panel Comercial pn-customers-manager', 'pn-customers-manager'),
    icon: 'businessperson',
    category: 'widgets',
    attributes: {},
    edit: function() {
      return element.createElement(
        'div',
        { className: 'pn-customers-manager-commercial-panel-block-preview', style: { padding: '24px', background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '8px', textAlign: 'center' } },
        element.createElement('strong', null, __('Panel de Agente Comercial', 'pn-customers-manager')),
        element.createElement(
          'p',
          null,
          __('Este es un bloque dinamico. El panel real se mostrara en el frontal.', 'pn-customers-manager')
        )
      );
    },
    save: function() { return null; },
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n);
