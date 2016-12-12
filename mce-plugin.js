(function () {
	tinymce.PluginManager.add('theme-blocks', function ThemeBlocksMcePlugin(editor) {

		function escapeRegExp(str) {
			return str.replace(/[|\\{}()[\]^$+*?.]/g, '\\$&');
		}

		function replaceBlockShortcodes(content) {
			var shortcode = escapeRegExp(ThemeBlocksConfig.shortcode);
			var shortcodeRegex = new RegExp('\\[' + shortcode + '([^\\]]*)\\]', 'g');
			return content.replace(/\[asdf([^\]]*)\]/g, function (match) {
				return html('wp-theme-block', match);
			});
		}

		function html(cls, data) {
			data = window.encodeURIComponent(data);
			return '<img src="' + tinymce.Env.transparentSrc + '" class="mceItem ' + cls + '" ' +
				'data-theme-block="' + data + '" data-mce-resize="false" data-mce-placeholder="1" alt="" />';
		}

		function restoreBlockShortcodes(content) {
			function getAttr(str, name) {
				name = new RegExp(name + '=\"([^\"]+)\"').exec(str);
				return name ? window.decodeURIComponent(name[ 1 ]) : '';
			}

			return content.replace(/(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function (match, image) {
				var data = getAttr(image, 'data-theme-block');

				if (data) {
					return '<p>' + data + '</p>';
				}

				return match;
			});
		}

		editor.on('BeforeSetContent', function (event) {
			if (!editor.plugins.wpview || typeof wp === 'undefined' || !wp.mce) {
				event.content = replaceBlockShortcodes(event.content);
			}
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
						values: ThemeBlocksConfig.blocks.reduce(function (blocks, name, key) {
							blocks.push({ text: name, value: key });
							return blocks;
						}, []),
					},
				],
				onsubmit: function (e) {
					var shortcode_str = '[' + ThemeBlocksConfig.shortcode + ' ' + e.data.block + ']';
					editor.insertContent(shortcode_str);
				},
			});
		});

		editor.addButton('ThemeBlocks', {
			icon: 'theme-blocks',
			tooltip: 'Insert Theme Block',
			onclick: function () {
				editor.execCommand('ThemeBlocksPopup', '', {
					block: ''
				});
			}
		});

		editor.on('DblClick', function (e) {
			editBlock(e.target);
			if (e.target.nodeName == 'IMG' && editor.dom.hasClass('wp-bs3_panel')) {
				var data = window.decodeURIComponent(editor.dom.getAttrib(node, 'data-theme-block'));
				console.log(data);
				editor.execCommand('ThemeBlocksPopup', '', {
					//block: data,
				});
			}
		});
	});
})();