tinymce.PluginManager.add('theme_blocks_plugin', function ThemeBlocksMcePlugin(editor) {

	function escapeRegExp(str) {
		return str.replace(/[|\\{}()[\]^$+*?.]/g, '\\$&');
	}

	function replaceBlockShortcodes(content) {
		var shortcode = escapeRegExp(ThemeBlocksConfig.shortcode);
		var shortcodeRegex = new RegExp('\\[' + shortcode + '([^\\]]*)\\]', 'g');
		return content.replace(shortcodeRegex, function (match) {
			return html('wp-theme-block', match);
		});
	}

	function html(cls, data) {
		data = window.encodeURIComponent(data);
		return '<img src="' + tinymce.Env.transparentSrc + '" class="mceItem ' + cls + '" ' +
			'data-theme-block="' + data + '" data-mce-resize="false" data-mce-placeholder="1" alt="" width="100px" />';
	}

	function getAttr(str, name) {
		name = new RegExp(name + '=\"([^\"]+)\"').exec(str);
		return name ? window.decodeURIComponent(name[ 1 ]) : '';
	}

	function restoreBlockShortcodes(content) {
		return content.replace(/(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function (match, image) {
			var data = getAttr(image, 'data-theme-block');

			if (data) {
				return '<p>' + data + '</p>';
			}

			return match;
		});
	}

	editor.on('BeforeSetContent', function (event) {
		event.content = replaceBlockShortcodes(event.content);
	});

	editor.on('GetContent', function (event) {
		event.content = restoreBlockShortcodes(event.content);
	});

	editor.addCommand('ThemeBlocksPopup', function (ui, data) {
		var block = '';
		if (data.block) {
			block = data.block;
		}
		editor.windowManager.open({
			title: 'Theme Block',
			body: [
				{
					type: 'listbox',
					name: 'block',
					label: 'Block',
					value: block,
					values: ThemeBlocksConfig.blocks,
				},
			],
			onsubmit: function (e) {
				var shortcode_str = '[' + ThemeBlocksConfig.shortcode + ' block="' + e.data.block + '"]';
				editor.insertContent(shortcode_str);
			},
		});
	});

	editor.addButton('theme_blocks_button', {
		icon: 'theme-blocks',
		tooltip: 'Insert Theme Block',
		onclick: function () {
			editor.execCommand('ThemeBlocksPopup', '', {
				block: ''
			});
		},
	});

	editor.on('DblClick', function (e) {
		if (e.target.nodeName == 'IMG' && editor.dom.hasClass(e.target, 'wp-theme-block')) {
			var data = window.decodeURIComponent(editor.dom.getAttrib(e.target, 'data-theme-block'));
			var block = getAttr(data, 'block');
			editor.execCommand('ThemeBlocksPopup', '', {
				block: block,
			});
		}
	});
});
