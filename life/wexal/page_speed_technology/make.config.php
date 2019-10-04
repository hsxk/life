<?php

class wexal_make_config {

function __construct( $pst ) {

$optimize_dir = '_wexal';
$profile_dir = dirname( dirname( dirname( __FILE__ ) ) );

require( $profile_dir . '/wexal/page_speed_technology/default.php' );

$arr = $this->config();
$arr = array_merge( $default, $arr );
if ( ! in_array( "/$optimize_dir", $arr['global_exclude'] ) ) {
	array_unshift( $arr['global_exclude'],  "/$optimize_dir" );
}
$pst->config = $arr;
$pst->put_config();

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
