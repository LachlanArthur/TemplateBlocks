<?php
/*
Plugin Name: Theme Blocks
Description: Include configurable PHP blocks from the active theme using a shortcode
Version:     0.1
Author:      Lachlan Arthur
Author URI:  https://lach.la
*/

namespace LA\ThemeBlocks;

if (!defined('ABSPATH')) {
	exit;
}

class ThemeBlocks {

	protected static $instance = null;

	public $config = [
		'shortcode' => 'theme-block',
		'templateDir' => '/blocks', // Filter to blank string to use theme root.
	];

	private $blocks = null;

	static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function __construct() {
		$this->overrideConfig();
		$this->findBlocks();
		$this->registerShortcode();
		$this->adminHooks();
	}

	function overrideConfig() {
		$config_keys = array_keys( $this->config );
		$config_defaults = $this->config;
		$this->config = array_reduce( $config_keys, function( $filtered, $key ) use ( $config_defaults ) {
			$filtered[ $key ] = apply_filters( __NAMESPACE__.'\\overrideConfig', $config_defaults[ $key ], $key );
			return $filtered;
		}, []);
	}

	function findBlocks() {
		if ( is_null( $this->blocks ) ) {
			$this->blocks = [];

			$files = $this->getFiles( 'php', 1, true );

			foreach ( $files as $file => $full_path ) {
				if ( $full_path === false ) continue;
				if ( ! preg_match( '|Block Name:(.*)$|mi', file_get_contents( $full_path ), $header ) ) {
					continue;
				}

				// Strip extension
				$blockName = preg_replace( '/\\.php$/', '', $file );

				$this->blocks[ $blockName ] = [
					'name' => _cleanup_header_comment( $header[1] ),
					'path' => $full_path,
				];
			}
		}
	}

	function registerShortcode() {
		add_shortcode( $this->config['shortcode'], [ $this, 'renderBlock' ] );
	}

	function adminHooks() {
		if ( is_admin() ) {
			add_action( 'admin_init', [ $this, 'adminInit' ] );
			add_action( 'admin_head', [ $this, 'adminHead' ] );
			add_action( 'admin_enqueue_scripts', array($this , 'adminScripts' ) );
		}
	}

	function adminInit() {
		add_action( 'wp_ajax_theme_blocks_mce_preview', array( $this, 'renderBlockPreview' ) );
		add_editor_style( plugins_url( '/mce-content.css' , __FILE__ ) );
	}

	function adminHead() {
		if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) return;

		if ( 'true' != get_user_option( 'rich_editing' ) ) return;

		add_filter( 'mce_external_plugins', [ $this ,'registerMcePlugin' ] );
		add_filter( 'mce_buttons_2', [ $this, 'registerMceButton' ] );
	}

	function registerMcePlugin( $plugins ) {
		$plugins[ 'theme_blocks_plugin' ] = plugins_url( '/mce-plugin.js' , __FILE__ );
		return $plugins;
	}

	function registerMceButton( $buttons ) {
		$buttons[] = 'theme_blocks_button';
		return $buttons;
	}

	function adminScripts() {
		wp_enqueue_style( 'theme-blocks-shortcode', plugins_url( '/mce-plugin.css' , __FILE__ ) );

		wp_enqueue_script( 'theme-blocks-mce-view', plugins_url( '/mce-view.js', __FILE__ ), [ 'shortcode', 'wp-util', 'jquery' ], false, true );
		$jsConfig = $this->config;
		$jsBlocks = wp_list_pluck( $this->blocks, 'name' );
		$jsConfig['blocks'] = array_map( function( $key, $name ) {
			return [
				'text' => $name,
				'value' => $key,
			];
		}, array_keys( $jsBlocks ), array_values( $jsBlocks ) );
		wp_localize_script( 'theme-blocks-mce-view', 'ThemeBlocksConfig', $jsConfig );
	}

	function renderBlock( $attributes ) {
		$attributes = shortcode_atts( [
			'block' => '',
		], $attributes, $this->config['shortcode'] );

		if ( empty( $attributes['block'] ) ) {
			return $this->renderError( 'No block selected' );
		}

		$blockName = $attributes['block'];

		if ( !array_key_exists( $blockName, $this->blocks ) ) {
			return $this->renderError( 'Invalid block selected' );
		}

		$blockTemplate = $this->blocks[ $blockName ][ 'path' ];

		ob_start();
		load_template( $blockTemplate, false );
		$blockContent = ob_get_clean();

		return $blockContent;
	}

	function renderBlockPreview() {
		global $wp_styles;

		// Load frontend styles in preview
		ob_start();
		$wp_styles->reset();
		do_action('wp_enqueue_scripts');
		$wp_styles->do_items();
		$wp_styles->do_footer_items();
		$styles = ob_get_clean();

		wp_send_json([
			'head' => $styles,
			'body' => $this->renderBlock( $_GET ),
		]);
	}

	function renderError( $message ) {
		// Only show errors to an admin.
		if ( current_user_can( 'manage_options' ) ) {
			return 'ThemeBlocks error: ' . $message;
		}
		return '';
	}

	public function getFiles( $type = null, $depth = 0, $search_parent = false ) {
		$theme = wp_get_theme();

		$files = (array) self::scandir( $theme->get_stylesheet_directory() . $this->config['templateDir'], $type, $depth );
	
		if ( $search_parent && $theme->parent() )
			$files += (array) self::scandir( $theme->get_template_directory() . $this->config['templateDir'], $type, $depth );

		return $files;
	}

	// Copy of private function WP_Theme::scandir
	private static function scandir( $path, $extensions = null, $depth = 0, $relative_path = '' ) {
		if ( ! is_dir( $path ) )
			return false;
	
		if ( $extensions ) {
			$extensions = (array) $extensions;
			$_extensions = implode( '|', $extensions );
		}
	
		$relative_path = trailingslashit( $relative_path );
		if ( '/' == $relative_path )
			$relative_path = '';
	
		$results = scandir( $path );
		$files = array();
	
		foreach ( $results as $result ) {
			if ( '.' == $result[0] )
				continue;
			if ( is_dir( $path . '/' . $result ) ) {
				if ( ! $depth || 'CVS' == $result )
					continue;
				$found = self::scandir( $path . '/' . $result, $extensions, $depth - 1 , $relative_path . $result );
				$files = array_merge_recursive( $files, $found );
			} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
				$files[ $relative_path . $result ] = $path . '/' . $result;
			}
		}
	
		return $files;
	}
}

new ThemeBlocks();
