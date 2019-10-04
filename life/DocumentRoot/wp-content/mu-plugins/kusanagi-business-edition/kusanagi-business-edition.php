<?php if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'KUSANAGI_BE_VERSION', '1.0.0' );

define( 'KUSANAGI_BE_PLUGIN', __FILE__ );
define( 'KUSANAGI_BE_PLUGIN_BASENAME', plugin_basename( KUSANAGI_BE_PLUGIN ) );
define( 'KUSANAGI_BE_PLUGIN_DIR', plugin_dir_path( KUSANAGI_BE_PLUGIN ) );
define( 'KUSANAGI_BE_PLUGIN_DIR_URL', plugin_dir_url( KUSANAGI_BE_PLUGIN ) );
define( 'KUSANAGI_BE_PLUGIN_NAME', trim( dirname( KUSANAGI_BE_PLUGIN_BASENAME ), '/' ) );

define( 'KUSANAGI_BE_PS_URL', 'https://marketplace-admin.kusanagi-hosting.com/wp-content/plugins/kusanagi-saas-admin/global/api.php' );
define( 'KUSANAGI_BE_INSTANCE_URL', 'http://169.254.169.254/latest/dynamic/instance-identity/document' );

require_once KUSANAGI_BE_PLUGIN_DIR . 'functions.php';



/**
 * Load the plugin text domain for translation.
 * @since    1.0.0
 */
add_action( 'plugins_loaded', 'KUSANAGI_BE_load_plugin_textdomain' );
function KUSANAGI_BE_load_plugin_textdomain() {
	load_muplugin_textdomain( 'kusanagi-be', KUSANAGI_BE_PLUGIN_NAME . '/languages' );
}



/**
 * Add menu for this plugin
 * @since    1.0.0
 */
//add_action( 'admin_menu', 'KUSANAGI_BE_add_manage_menu' );
function KUSANAGI_BE_add_manage_menu() {
	$slug = add_submenu_page( 'kusanagi-core/core.php', 'Business Edition', 'Business Edition', 'manage_options', 'kusanagi-be', 'KUSANAGI_BE_read_front_page' );
	add_action( 'load-' . $slug, 'KUSANAGI_BE_check_func_switcher' ); // See /functions.php
	add_action( 'load-' . $slug, 'KUSANAGI_BE_admin_enqueue_scripts' );
}
function KUSANAGI_BE_read_front_page() {
	include( KUSANAGI_BE_PLUGIN_DIR . 'template/front-page.php' );
}
function KUSANAGI_BE_admin_enqueue_scripts() {
	wp_enqueue_style( 'kusanagi-be-css', KUSANAGI_BE_PLUGIN_DIR_URL . 'template/css/style.css', array(), KUSANAGI_BE_VERSION );
}
