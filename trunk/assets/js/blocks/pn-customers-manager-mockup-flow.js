(function (blocks, element, components, blockEditor, i18n) {
	var el = element.createElement;
	var SelectControl = components.SelectControl;
	var Placeholder = components.Placeholder;
	var useBlockProps = blockEditor.useBlockProps;

	var data = typeof pnCmMockupFlowBlock !== 'undefined' ? pnCmMockupFlowBlock : { flows: [] };

	var options = [{ label: '— ' + (i18n.__('Select a Mockup Flow', 'pn-customers-manager') || 'Select a Mockup Flow') + ' —', value: '0' }];
	data.flows.forEach(function (f) {
		options.push({ label: f.title, value: String(f.id) });
	});

	blocks.registerBlockType('pn-customers-manager/mockup-flow', {
		title: i18n.__('Mockup Flow', 'pn-customers-manager') || 'Mockup Flow',
		description: i18n.__('Display a Mockup Flow viewer.', 'pn-customers-manager') || 'Display a Mockup Flow viewer.',
		icon: 'images-alt2',
		category: 'widgets',
		attributes: {
			mockupFlowId: { type: 'number', default: 0 }
		},

		edit: function (props) {
			var blockProps = useBlockProps ? useBlockProps() : {};
			var mockupFlowId = props.attributes.mockupFlowId;

			var selectedFlow = data.flows.find(function (f) { return f.id === mockupFlowId; });

			return el('div', blockProps,
				el(SelectControl, {
					label: i18n.__('Mockup Flow', 'pn-customers-manager') || 'Mockup Flow',
					value: String(mockupFlowId || 0),
					options: options,
					onChange: function (val) {
						props.setAttributes({ mockupFlowId: parseInt(val) || 0 });
					}
				}),
				mockupFlowId > 0
					? el('div', { style: { padding: '20px', background: '#f0f0f0', borderRadius: '6px', textAlign: 'center', marginTop: '8px' } },
						el('span', { className: 'dashicons dashicons-images-alt2', style: { fontSize: '32px', color: '#999' } }),
						el('p', { style: { margin: '8px 0 0', color: '#666' } }, selectedFlow ? selectedFlow.title : 'Mockup Flow #' + mockupFlowId)
					)
					: el(Placeholder, {
						icon: 'images-alt2',
						label: i18n.__('Mockup Flow', 'pn-customers-manager') || 'Mockup Flow',
						instructions: i18n.__('Select a Mockup Flow to display.', 'pn-customers-manager') || 'Select a Mockup Flow to display.'
					})
			);
		},

		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.components,
	window.wp.blockEditor,
	window.wp.i18n
);
