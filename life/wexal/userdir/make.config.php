<?php

class user_wexal_make_config extends wexal_make_config {

function __construct( $pst ) {
	parent::__construct( $pst );

}

function config() {

$conf = array(
	'pst' => 'off',
	'watch' => 'on',
	'timezone' => 'Asia/Tokyo',
	'protocol' => 'https',
	'conf' => array(
		'editor' => 'vim',
		'format' => 'yaml',
	),
	'options' => array( 'wp' ),
	'global_exclude' => array(
		'~$',
		'/\.',
		'/wp-admin',
		'/wp-includes',
		'/wp-content/upgrade',
		'/wp-json/',
		'/wp-content/plugins',
	),
	'watch_additional_exclude' => array(),
	'worker' => array(
		'img' => array(
			'' => array(),
			'.webp' => array(),
		),
		'css' => array(),
		'js' => array(),
	),
	'lua' => array(
		'fcache' => array( 'enable' => 1, 'exptime' => 60 ),
		'header_filter' => array(),
		'body_filter' => array(),
	),
	'wp' => array(
		'wexal_init' => array(
		),
		'wexal_head' => array(
		),
		'wexal_enqueue_opt' => array(
		),
		'wexal_footer' => array(
		),
		'wexal_flush' => array(
			array ( 'cmd' => 'server push external css' ),
		),
	),
);

return $conf;
} // function end 

} // class end
