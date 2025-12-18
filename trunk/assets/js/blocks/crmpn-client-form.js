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

  registerBlockType('crmpn/client-form', {
    title: __('Formulario de organización CRMPN', 'crmpn'),
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
              { title: __('Ajustes del formulario', 'crmpn'), initialOpen: true },
              element.createElement(ToggleControl, {
                label: __('Mostrar título', 'crmpn'),
                checked: attributes.showTitle,
                onChange: (value) => setAttributes({ showTitle: value }),
              }),
              element.createElement(TextControl, {
                label: __('Título', 'crmpn'),
                value: attributes.title,
                onChange: (value) => setAttributes({ title: value }),
                placeholder: __('Alta de organización', 'crmpn'),
              }),
              element.createElement(TextControl, {
                label: __('Descripción', 'crmpn'),
                value: attributes.description,
                onChange: (value) => setAttributes({ description: value }),
                placeholder: __('Describe el propósito del formulario…', 'crmpn'),
              }),
              element.createElement(TextControl, {
                label: __('ID personalizado (opcional)', 'crmpn'),
                value: attributes.formId,
                onChange: (value) => setAttributes({ formId: value }),
                help: __('Úsalo si necesitas un ID fijo para integraciones.', 'crmpn'),
              })
            ),
            element.createElement(
              PanelBody,
              { title: __('Campos de la organización', 'crmpn'), initialOpen: false },
              element.createElement(
                'p',
                null,
                __('Título y descripción son obligatorios. Selecciona qué otros campos del CPT Organization se mostrarán en el formulario.', 'crmpn')
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
            { className: 'crmpn-client-form-block-preview' },
            element.createElement('strong', null, __('Formulario de alta de organización', 'crmpn')),
            element.createElement(
              'p',
              null,
              __('Este es un bloque dinámico. El formulario real se mostrará en el frontal.', 'crmpn')
            )
          )
        )
      );
    },
    save: () => null,
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components);

