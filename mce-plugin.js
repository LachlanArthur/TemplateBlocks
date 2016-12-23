/* global wp, tinyMCE, ajaxurl, ThemeBlocksConfig */
(function () {
	tinyMCE.PluginManager.add('theme_blocks_plugin', function ThemeBlocksMcePlugin(editor) {
		editor.addButton('theme_blocks_button', {
			icon: 'theme-blocks',
			tooltip: 'Insert Theme Block',
			onclick: function () {
				wp.mce[ ThemeBlocksConfig.shortcode ].popupwindow(editor);
			},
		});
	});
})();
