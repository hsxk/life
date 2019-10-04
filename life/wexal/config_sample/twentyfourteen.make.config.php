<?php

class user_wexal_make_config extends wexal_make_config {

function __construct( $pst ) {
	parent::__construct( $pst );

}

function config() {

$conf = array(
	'pst' => 'on',
	'watch' => 'on',
	'timezone' => 'Asia/Tokyo',
	'global_exclude' => array(
		'~$',
		'/\.',
		'/wp-admin',
		'/wp-includes',
		'/wp-content/upgrade',
	),
	'worker' => array(
		'img' => array(
			'' => array(),
			'.webp' => array(),
			'.jp2' => array(),
			'.jxr' => array(),
		),
		'css' => array(
			
		),
		'js' => array(

		),
	),
	'wp' => array(
		'wexal_enqueue_opt' => array(
			array ( 'cmd' => 'remove emoji' ),
			array (
				'cmd' => 'remove css',
				'args' => array(
					'twentyfourteen-style',
					'twentyfourteen-lato',
					'twentyfourteen-ie',
					'twentyfourteen-block-style',
					'wp-block-library-theme',
					'wp-block-library',
				),
			),
			array (
				'cmd' => 'remove css',
				'args' => array(
					'heateor_sss_frontend_css',
					'heateor_sss_sharing_default_svg',
				),
				'exclude' => array(
					'if' => array( 'is_singular' => '' ),
				),
			),
			array (
				'cmd' => 'remove js',
				'args' => array(
					'wp-embed',
				),
			),	
			array ( 'cmd' => 'opt genericons' ),
			array (
				'cmd' => 'remove js',
				'args' => array(
					'heateor_sss_sharing_js',
				),
			),
		),
		'wexal_footer' => array(
		),
		'wexal_flush' => array(
			array ( 'cmd' => 'shorten url' ),
			array ( 'cmd' => 'server push external css' ),
			array (
				'cmd' => 'defer external js',
				'apply' => array(
					'if' => array( 'is_front_page' => '' ),
				),
			),
		),
	),
);

return $conf;
} // function end 

} // class end
