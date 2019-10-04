<?php

	$default = array(
		'pst' => 'off',
		'watch' => 'on',
		'tdir' => $profile_dir . '/DocumentRoot',
		'odir' => $optimize_dir,
		'timezone' => 'Asia/Tokyo',
		'protocol' => 'https',
		'conf' => array(
			'editor' => 'vim',
			'format' => 'yaml',
		),
		'options' => array( 'wp' ),
		'global_exclude' => array(
			"/$optimize_dir",
			'~$',
			'/\.',
			'/wp-admin',
			'/wp-includes',
			'/wp-content/upgrade',
			'/wp-json/',
			'/wp-content/plugins',
		),
		'watch_additional_exclude' => array(),
		'lua' => array(
			'fcache' => array( 'enable' => 1, 'exptime' => 60 ),
			'header_filter' => array(),
			'body_filter' => array(),
		),
		'worker' => array(
		),
	);

