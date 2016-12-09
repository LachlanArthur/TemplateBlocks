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

// TODO
// - Render error messages to admin

class ThemeBlocks {

	protected static $instance = null;

	public $config = [
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

		if ( !is_admin() ) {
			var_dump($this->blocks);
		}
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

				$this->blocks[ $file ] = _cleanup_header_comment( $header[1] );
			}
		}
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
