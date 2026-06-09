(function (blocks, element, blockEditor, components) {
	var el = element.createElement;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;

	blocks.registerBlockType('hpk-panneaupocket/iframe', {
		edit: function (props) {
			var attrs = props.attributes;
			return el(
				'div',
				{ className: 'hpk-pp-block-placeholder' },
				el('p', {}, 'PanneauPocket Widget (iframe)'),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Paramètres', initialOpen: true },
						el(SelectControl, {
							label: 'Mode',
							value: attrs.mode,
							options: [
								{ label: 'widget', value: 'widget' },
								{ label: 'widgetTv', value: 'widgetTv' }
							],
							onChange: function (v) { props.setAttributes({ mode: v }); }
						}),
						el(TextControl, {
							label: 'Auto-navigation (sec)',
							value: String(attrs.autoNavigation),
							onChange: function (v) { props.setAttributes({ autoNavigation: parseInt(v, 10) || 0 }); }
						}),
						el(TextControl, {
							label: 'bgColor',
							value: attrs.bgColor,
							onChange: function (v) { props.setAttributes({ bgColor: v }); }
						}),
						el(TextControl, {
							label: 'City ID',
							value: attrs.cityId,
							onChange: function (v) { props.setAttributes({ cityId: v }); }
						}),
						el(TextControl, {
							label: 'Largeur',
							value: attrs.width,
							onChange: function (v) { props.setAttributes({ width: v }); }
						}),
						el(TextControl, {
							label: 'Hauteur',
							value: attrs.height,
							onChange: function (v) { props.setAttributes({ height: v }); }
						})
					)
				)
			);
		}
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
