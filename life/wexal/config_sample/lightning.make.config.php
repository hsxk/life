<?php

class user_wexal_make_config extends wexal_make_config {

function __construct( $pst ) {
	parent::__construct( $pst );
}

function config() {

$config = array(
	'pst' => 'on',
	'watch' => 'off',
	'timezone' => 'Asia/Tokyo',
	'conf' => array(
		'editor' => 'vim',
		'format' => 'yaml',
	),
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
		),
		'css' => array(
			
		),
		'js' => array(

		),
	),
	'wp' => array(
		'wexal_init' => array(
			array ( 'cmd' => 'remove header' ),
		),
		'wexal_enqueue_opt' => array(
			array ( 'cmd' => 'remove emoji'	),
			array ( 'cmd' => 'remove meta'	),
			array ( 'cmd' => 'engagement delay'	),
			array (
				'cmd' => 'remove css',
				'args' => array(
					'lightning-theme-style',
					'wp-block-library',
					'font-awesome',
				),
			),
			array (
				'cmd' => 'remove js',
				'args' => array(
					'wp-embed',
					'bootstrap-js',
				),
			),
			array (
				'cmd' => 'remove wpcf7',
				'exclude' => array(
					'if' => array( 'is_page' => 'contactus' ),
				),
			),
		),
		'wexal_footer' => array(
			array (	'cmd' => 'delay font awesome css script' ),
		),
		'wexal_flush' => array(
			array (	'cmd' => 'shorten url' ),
			array ( 'cmd' => 'server push external css' ),
			array (	
				'cmd' => 'lazy youtube',
				'args' => array(
					'pc' => 'mq',
					'mobile' => 'mq',
					'ratio' => '56.25',
				),
			),
			array (
				'cmd' => 'engagement delay',
				'args' => array(
					'score' => 1000,
					'delay' => 3000,
					'scripts' => array(
						array(
							'name' => 'ga',
							'type' => 'closure',
							'pattern'=> '(function(w',
							'args' => "window,document,'script','dataLayer','GTM-PV5XBLC",
						),
					),
				),
			),
			array (
				'cmd' => 'defer external js',
				'exclude' => array(
					'if' => array( 'is_page' => 'contactus' ),
				),
			),
			array(
				'cmd' => 'set cookie for cdn',
				'args' => array(
					'domain' => 'wexal.jp',
					'external_url' => 'https://www.prime-strategy.co.jp/test.js',
				),
			),
			array(
				'cmd' => 'replace',
				'args' => array( '<link rel="apple-touch-icon-precomposed" href="/_wu/2019/07/cropped-favicon-180x180.png" />' => '' ),
			),
			array(
				'cmd' => 'replace anything',
				'args' => array(
					array( '#<meta name="msapplication-TileImage"[^>]+?>#', '', 1 ),
				),
			),
		),
	),
);

return $config;
} // function end 

} // class end
