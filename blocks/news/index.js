(function (blocks, element, blockEditor, components) {
	var el = element.createElement;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;

	blocks.registerBlockType('hpk-panneaupocket/news', {
		edit: function (props) {
			var attrs = props.attributes;
			return el(
				'div',
				{ className: 'hpk-pp-block-placeholder' },
				el('p', {}, 'PanneauPocket Actualités'),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Paramètres', initialOpen: true },
						el(SelectControl, {
							label: 'Layout',
							value: attrs.layout,
							options: [
								{ label: 'Grid', value: 'grid' },
								{ label: 'List', value: 'list' },
								{ label: 'Compact', value: 'compact' }
							],
							onChange: function (v) { props.setAttributes({ layout: v }); }
						}),
						el(TextControl, {
							label: 'Limit',
							value: String(attrs.limit),
							onChange: function (v) { props.setAttributes({ limit: parseInt(v, 10) || 6 }); }
						}),
						el(ToggleControl, {
							label: 'Afficher la date',
							checked: attrs.showDate,
							onChange: function (v) { props.setAttributes({ showDate: v }); }
						}),
						el(ToggleControl, {
							label: 'Afficher l\'image',
							checked: attrs.showImage,
							onChange: function (v) { props.setAttributes({ showImage: v }); }
						}),
						el(ToggleControl, {
							label: 'Afficher le type',
							checked: attrs.showType,
							onChange: function (v) { props.setAttributes({ showType: v }); }
						}),
						el(ToggleControl, {
							label: 'Pagination',
							checked: attrs.pagination,
							onChange: function (v) { props.setAttributes({ pagination: v }); }
						})
					)
				)
			);
		}
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
