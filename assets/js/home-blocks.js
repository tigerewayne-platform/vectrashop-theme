/* Editor previews for the Vectra homepage blocks (rendered by PHP). */
(function (wp) {
	var el = wp.element.createElement;
	var SSR = wp.serverSideRender;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var Inspector = wp.blockEditor.InspectorControls;
	var C = wp.components;

	function preview(name) {
		return function (props) {
			return el('div', useBlockProps(), el(SSR, { block: name, attributes: props.attributes }));
		};
	}
	['vectra/hero-slider', 'vectra/category-carousel', 'vectra/brand-strip'].forEach(function (name) {
		wp.blocks.registerBlockType(name, { edit: preview(name), save: function () { return null; } });
	});

	wp.blocks.registerBlockType('vectra/product-rail', {
		edit: function (props) {
			var a = props.attributes, set = props.setAttributes;
			function text(key, label, help) {
				return el(C.TextControl, { label: label, help: help, value: a[key] || '', onChange: function (v) { var o = {}; o[key] = v; set(o); } });
			}
			return el('div', useBlockProps(),
				el(Inspector, null, el(C.PanelBody, { title: 'Products' },
					text('title', 'Heading'),
					text('cats', 'Category slugs', 'Comma separated, e.g. ups-inverters,power-banks. Empty = all.'),
					text('excludeCats', 'Leave out category slugs'),
					el(C.RangeControl, { label: 'How many', min: 4, max: 24, value: a.limit, onChange: function (v) { set({ limit: v }); } }),
					el(C.TextControl, { label: 'Max price (R, 0 = any)', type: 'number', value: a.maxPrice, onChange: function (v) { set({ maxPrice: +v || 0 }); } }),
					el(C.SelectControl, { label: 'Order', value: a.orderby, options: [{ label: 'Best selling', value: 'sales' }, { label: 'Highest price', value: 'price' }, { label: 'Newest', value: 'date' }], onChange: function (v) { set({ orderby: v }); } }),
					text('link', 'See all link'),
					text('linkLabel', 'See all label')
				)),
				el(SSR, { block: 'vectra/product-rail', attributes: a })
			);
		},
		save: function () { return null; }
	});
})(window.wp);
