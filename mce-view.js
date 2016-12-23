/* global wp, tinyMCE, ajaxurl, ThemeBlocksConfig */
(function ($) {
	var config = ThemeBlocksConfig;
	wp.mce = wp.mce || {};
	wp.mce[ config.shortcode ] = {
		shortcode_data: {},
		getContent: function () {
			var plugin = this;
			var options = this.shortcode.attrs.named;
			var placeholderClass = 'theme-block-placeholder-' + Math.random().toString(36).substr(2);
			var placeholder = '<div class="' + placeholderClass + '">Loading...</div>';
			$.get({
				url: ajaxurl,
				method: 'get',
				data: $.extend({
					action: 'theme_blocks_mce_preview',
				}, options),
				success: function (data) {
					plugin.setContent(data);
				},
				error: function () {
					plugin.setContent('Error loading theme block.');
				}
			});
			return placeholder;
		},
		edit: function (data) {
			var shortcode_data = wp.shortcode.next(config.shortcode, data);
			var values = shortcode_data.shortcode.attrs.named;
			wp.mce[ config.shortcode ].popupwindow(tinyMCE.activeEditor, values);
		},
		popupwindow: function (editor, values, onsubmit_callback) {
			values = values || {};
			if (typeof onsubmit_callback !== 'function') {
				onsubmit_callback = function (e) {
					var args = {
						tag: config.shortcode,
						type: 'single',
						attrs: {
							block: e.data.block,
						},
					};
					editor.insertContent(wp.shortcode.string(args));
				};
			}
			editor.windowManager.open({
				title: 'Theme Block',
				body: [
					{
						type: 'listbox',
						name: 'block',
						label: 'Block',
						value: values.block,
						values: config.blocks,
					},
				],
				onsubmit: onsubmit_callback,
			});
		}
	};
	if (wp.mce.views) {
		wp.mce.views.register(config.shortcode, wp.mce[ config.shortcode ]);
	}
})(jQuery);
