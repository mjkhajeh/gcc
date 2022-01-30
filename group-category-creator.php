<?php
/*
Plugin Name: Group category creator
Plugin URI: https://wordpress.org/plugins/group-category-creator
Description: Now you can create multi categories in one second with one click.
Version: 1.4.3.8
Author: MohammadJafar Khajeh
Author URI: https://mjkhajeh.ir
Text Domain: gcc
Domain Path: /languages
*/
namespace mjgcc;

if (!defined('ABSPATH')) exit;

class gcc {
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return	A single instance of this class.
	 */
	public static function get_instance() {
		static $instance = null;
		if( $instance === null ) {
			$instance = new self;
		}
		return $instance;
	}
	
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'constants' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'includes' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'i18n' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}
	
	public function constants() {
		if( ! defined( 'GCC_DIR' ) )
			define( 'GCC_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );

		if( ! defined( 'GCC_URI' ) )
			define( 'GCC_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

		if( ! defined( 'GCC_VERSION' ) )
			define( 'GCC_VERSION', "1.4.3.8" );
	}
	
	public function includes() {
		if ( is_admin() ) {
			include( GCC_DIR . 'admin/menu.php' );
		}
	}
	
	public function i18n() {
		// Load languages
		load_plugin_textdomain( 'gcc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function enqueue() {
		wp_register_script( 'gcc', GCC_URI . "assets/js/gcc.js", array(), GCC_VERSION, true );
		wp_register_style( 'gcc', GCC_URI . "assets/css/gcc.css", array(), GCC_VERSION );
	}
}
gcc::get_instance();

// Changelogs

/** 1.4.3.8
 * Add ability to specify parents in textarea to faster creating
 * Use WordPress hook to show notices
 * Display errors more clearly
 * Insert categories in better way
 * Sanitizing & Escaping variables for security
 * Added 'gcc_ignored_custom_taxonomies' filter
 */

/** 1.3.0.2
 * Update to compatible with WP 5.7.2.
 * Added custom taxonomy section to create terms in custom taxonomies
 */

/** 1.1.0.1
 * Compatible with 5.7
 * Fix bug of 'gcc_tabs_slug' and 'gcc_taxonomies' filters
 * namespaces added
 */

/** 1.0.0.0
 * Everything started from here!
 */