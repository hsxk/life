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
		'/wp-content/plugins',
	),
	'worker' => array(
		'img' => array(
			'' => array(),
			'.webp' => array(),
		),
		'css' => array(
			
		),
		'js' => array(

		),
	),
	'wp' => array(
		'wexal_enqueue_opt' => array(
			array( 'cmd' => 'remove emoji' ),
			array(
				'cmd' => 'remove css',
				'args' => array(
					'dashicons',
					'vkExUnit_common_style',
					'vk-blocks-build-css',
					'wp-block-library',
					'islemag-fontawesome',
					'islemag-fonts',
				),
			),
			array(
				'cmd' => 'remove css',
				'args' => 'whats-new-style',
				'exclude' => array(
					'if' => array( 'is_page' => 'mailmag' ),
				),
			),
			array(
				'cmd' => 'add css',
				'args' => array(
					'user-font-awesome',
					'/wp-content/plugins/mfo/font-awesome.css',
				),
			),
			array(
				'cmd' => 'remove js',
				'args' => array(
					'wp-embed',
					'islemag-widget-js',
				),
			),
			array(
				'cmd' => 'remove js',
				'args' => array(
					'islemag-owl-carousel',
				),
				'exclude' => array( 'if' => 'is_front_page' ),
			),
			array(
				'cmd' => 'add js',
				'args' => array(
					'user-lazyload',
					'/wp-content/plugins/mfo/jquery.lazyload.min.js',
					array( 'jquery' ),
					false,
					true,
				),
			),
			array(
				'cmd' => 'add js',
				'args' => array(
					'user-sitejs',
					'/wp-content/plugins/mfo/site.js',
					array( 'jquery' ),
					false,
					true,
				),
			),
			array(
				'cmd' => 'remove hook',
				'args' => array( 'wp_head',	'veu_add_smooth_js' ),
			),
			array(
				'cmd' => 'remove hook',
				'args' => array( 'wp_footer', 'exUnit_print_fbId_script' ),
			),
			array(
				'cmd' => 'remove hook',
				'args' => array( 'wp_head', 'Closure', 10, true ),
			),
			array(
				'cmd' => 'remove hook',
				'args' => array( 'wp_head', array( 'WpSocialBookmarkingLight\Plugin', 'head' ), 10, true ),
				'apply' => array( 'if' => 'is_front_page | is_archive | is_page' ),
			),
			array(
				'cmd' => 'remove hook',
				'args' => array( 'wp_footer', array( 'WpSocialBookmarkingLight\Plugin', 'footer' ), 10, true ),
#				'apply' => array( 'if' => 'is_front_page|is_archive' ),
				'apply' => array( 'path' => '^/$', 'if' => 'is_archive | is_page' ),
			),

		),
		'wexal_footer' => array(
		),
		'wexal_flush' => array(
			array ( 'cmd' => 'shorten url' ),
			array ( 'cmd' => 'server push external css' ),
			array ( 'cmd' => 'user lazy load' ),
			array ( 
				'cmd' => 'defer external js',
				'args' => array( 'apply_script' => '', 'exclude_script' => '' ),
			),
		),
	),
);

return $conf;
} // function end 

} // class end
