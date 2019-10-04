<?php
/**
 * Plugin Name: Elementor Google Map Extended
 * Description: An Extended of Elementor Google Map Widget - Easily add multiple address pins onto the same map with support for different map types (Road Map/Satellite/Hybrid/Terrain) and custom map style. Freely edit info window content of your pins with the standard Elementor text editor. And many more custom map options.
 * Plugin URI:  https://internetcss.com/
 * Version:     1.2.1
 * Author:      InternetCSS
 * Author URI:  https://internetcss.com/about-us
 * Text Domain: extended-google-map-for-elementor
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'EB_GOOGLE_MAP_EXTENDED__FILE__', __FILE__ );
define( 'EB_GOOGLE_MAP_EXTENDED__DIR__', __DIR__ );
define( 'EB_GOOGLE_MAP_EXTENDED_VERSION', '1.2.1' );
/**
 * Main EB_Elementor_Google_Map_Class
 * @since 1.2
 */

final class EB_Elementor_Google_Map_Class {
	/**
	 * Constructor
	 *
	 * @since 1.2
	 * @access public
	 */
	public function __construct() {

		// Load translation
		add_action( 'init', array( $this, 'i18n' ) );

		// Init Plugin
		require_once EB_GOOGLE_MAP_EXTENDED__DIR__ . '/includes/class-extended-google-map-for-elementor.php';
	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 * Fired by `init` action hook.
	 *
	 * @since 1.2
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'extended-google-map-for-elementor' );
	}
}
new EB_Elementor_Google_Map_Class();