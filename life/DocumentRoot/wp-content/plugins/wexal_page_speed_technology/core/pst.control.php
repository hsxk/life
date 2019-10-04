<?php

class wexal_pst_control {

public $config;
public $wexal_dir;
public $pst_dir;
public $DocumentRoot;
public $userdir;
public $version = '1.1.8';

function __construct() {

	$profile = '';
	if ( preg_match( '#^/home/kusanagi/([^/]+?)(/|$)#', __FILE__, $m ) ) {
		$profile = $m[1];
	}
	
	if ( $profile ) {
		$this->wexal_dir =  '/home/kusanagi/' . $profile . '/wexal';
	} else {
		$this->wexal_dir =  WP_CONTENT_DIR . '/wexal';
	}
	
	$this->pst_dir = $this->wexal_dir . '/page_speed_technology';
	$this->userdir = $this->wexal_dir . '/userdir';
	$this->DocumentRoot = isset( $_SERVER['DOCUMENT_ROOT'] ) ? $_SERVER['DOCUMENT_ROOT'] : '';
	$this->set_config();
}

function set_config() {

	$pst = get_option( 'wexal_pst_pst' );
	$wp  = get_option( 'wexal_pst_conf_wp_prod' );

	$optimize_dir = '_wexal';
	$profile_dir  = '';
	require( $this->pst_dir . '/default.php' );

	$this->config = $default;
	if ( 'on' == $pst ) {
		$this->config['pst'] = 'on';
	}
	$this->config['wp'] = array ();
	if ( is_array( $wp ) ) {
		$this->config['wp'] = $wp;
	}

}

} // end class

$wexal_pst_control = new wexal_pst_control();

