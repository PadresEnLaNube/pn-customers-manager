(function(blocks, element, i18n, blockEditor, components) {
  const { registerBlockType } = blocks;
  const { Fragment } = element;
  const { __ } = i18n;
  const { InspectorControls } = blockEditor;
  const { PanelBody, TextControl, ToggleControl, CheckboxControl } = components;

  // Campos públicos del CPT Organization obtenidos desde PHP
  // Se obtienen dinámicamente desde crmpnOrganizationFields (localizado desde PHP)
  const ORGANIZATION_FIELDS = (typeof crmpnOrganizationFields !== 'undefined' && Array.isArray(crmpnOrganizationFields))
    ? crmpnOrganizationFields
    : [];

  registerBlockType('pn-customers-manager/client-form', {
    title: __('Formulario de organización pn-customers-manager', 'pn-customers-manager'),
    icon: 'forms',
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
      formId: {
        type: 'string',
        default: '',
      },
      organizationFields: {
        type: 'array',
        default: [],
      },
    },
    edit: (props) => {
      const { attributes, setAttributes } = props;

      const toggleOrganizationField = (fieldId, isChecked) => {
        const current = Array.isArray(attributes.organizationFields)
          ? attributes.organizationFields
          : [];

        if (isChecked) {
          if (!current.includes(fieldId)) {
            setAttributes({ organizationFields: [...current, fieldId] });
          }
        } else {
          setAttributes({
            organizationFields: current.filter((id) => id !== fieldId),
          });
        }
      };

      return (
        element.createElement(
          Fragment,
          null,
          element.createElement(
            InspectorControls,
            null,
            element.createElement(
              PanelBody,
              { title: __('Ajustes del formulario', 'pn-customers-manager'), initialOpen: true },
              element.createElement(ToggleControl, {
                label: __('Mostrar título', 'pn-customers-manager'),
                checked: attributes.showTitle,
                onChange: (value) => setAttributes({ showTitle: value }),
              }),
              element.createElement(TextControl, {
                label: __('Título', 'pn-customers-manager'),
                value: attributes.title,
                onChange: (value) => setAttributes({ title: value }),
                placeholder: __('Alta de organización', 'pn-customers-manager'),
              }),
              element.createElement(TextControl, {
                label: __('Descripción', 'pn-customers-manager'),
                value: attributes.description,
                onChange: (value) => setAttributes({ description: value }),
                placeholder: __('Describe el propósito del formulario…', 'pn-customers-manager'),
              }),
              element.createElement(TextControl, {
                label: __('ID personalizado (opcional)', 'pn-customers-manager'),
                value: attributes.formId,
                onChange: (value) => setAttributes({ formId: value }),
                help: __('Úsalo si necesitas un ID fijo para integraciones.', 'pn-customers-manager'),
              })
            ),
            element.createElement(
              PanelBody,
              { title: __('Campos de la organización', 'pn-customers-manager'), initialOpen: false },
              element.createElement(
                'p',
                null,
                __('Título y descripción son obligatorios. Selecciona qué otros campos del CPT Organization se mostrarán en el formulario.', 'pn-customers-manager')
              ),
              ORGANIZATION_FIELDS.map((field) =>
                element.createElement(CheckboxControl, {
                  key: field.id,
                  label: field.label,
                  checked: Array.isArray(attributes.organizationFields)
                    ? attributes.organizationFields.includes(field.id)
                    : false,
                  onChange: (isChecked) => toggleOrganizationField(field.id, isChecked),
                })
              )
            )
          ),
          element.createElement(
            'div',
            { className: 'pn-customers-manager-client-form-block-preview' },
            element.createElement('strong', null, __('Formulario de alta de organización', 'pn-customers-manager')),
            element.createElement(
              'p',
              null,
              __('Este es un bloque dinámico. El formulario real se mostrará en el frontal.', 'pn-customers-manager')
            )
          )
        )
      );
    },
    save: () => null,
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);

