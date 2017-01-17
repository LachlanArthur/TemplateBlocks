# Notes

##TODO

- [x] Blocks go in theme: `blocks/$name.php`
- [x] Read block info from file using [get_file_data](https://developer.wordpress.org/reference/functions/get_file_data/). Also see [File Header docs](https://codex.wordpress.org/File_Header).
- [x] TinyMCE integration
	- [x] Toolbar button
	- [x] Editor plugin
		- [x] Select block from dropdown
		- [x] Preview result in visual editor
		- [ ] Load in wpview sandbox iframe with theme CSS
		- [ ] Display better loading animation
- [ ] Unwrap `<p>` tags from blocks
	- [ ] Provide option to override this in block file comment (`Unwrap: false`)

## Future

- Configurable shortcode options in admin.
  Defined in block.
- Role-based permissions for blocks. Probably impossible.