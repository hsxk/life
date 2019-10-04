<?php

class wexal_pst_init {

public $pst;

function __construct( $pst ) {
	$this->pst = $pst;
	$docroot = $pst->DocumentRoot;
	$odir = $pst->config['odir'];
	$status  = $pst->config['pst'];

	$optdir = "{$pst->wexal_dir}/optdir";
	$ln = "$docroot/$odir";

	if ( ! is_dir ( $optdir ) ) {
		$cmd = "mkdir $optdir";
		$ret = shell_exec( $cmd );
	}
	
	if ( ! is_link( $optdir . '/_wexal.org' ) ) {
		symlink( $docroot, $optdir . '/_wexal.org' );
	}

	$logdir = "/home/kusanagi/{$pst->profile}/log/pst";
	if ( ! is_dir ( $logdir ) ) {
		mkdir( $logdir, 0755, true );
	}

	if ( 'on' == $status ) {
		if ( ! is_link( $ln ) ) {
			symlink( $optdir, $ln );
		}
	} else {
		if ( is_link( $ln ) ) {
			unlink( $ln );
		}	
	}
	
	$this->extra();
	$this->nginx();
	$this->httpd();

	$method = $pst->config['pst'];
	$pst->$method();

	$flag = $pst->config['watch'];
	if ( 'on' == $flag ) {
		$pst->watch( 'off' );
	} 
	$pst->watch( $flag );

	$flag = $pst->get_prun( true );
	if ( $flag ) {
		$pst->runner( 'off' );
	} 
	$pst->runner( 'on' );
}

function extra() {
	$conf = file_get_contents( $this->pst->nginx_ssl_conf );
	if ( preg_match( '#server_name\s+([^\s;]+)#', $conf, $m ) ) {
		$host = $m[1];
		$profile = $this->pst->profile;
		$protocol = 'https';
		if ( in_array( '--http', $this->pst->argv )
			|| ( isset( $this->pst->config['protocol'] ) 
				&& 'http' == $this->pst->config['protocol'] ) ) {
			$protocol = 'http';
		}
		$url = "$protocol://$host/";
		$cmd = "/usr/local/bin/node " . $this->pst->pst_dir . '/js/linklist.js ' . escapeshellarg( $url ) . ' 2>/dev/null';

		$loop = array(
			'/js/uncss/postcss.config.js',
			'/js/screenshot.js',
			'/js/linklist.js',
			'/js/ai.config.js',
		);
		
		$linkarr = array();
		if ( ! is_file( $this->pst->pst_dir . '/linklist.yaml' ) ) {
			$linklist = shell_exec( $cmd );
#			$linklist = preg_replace( "#'#", '"', $linklist );
			$linkarr = yaml_parse( $linklist );
			if ( $linkarr ) {
				file_put_contents( $this->pst->pst_dir . '/linklist.yaml', $linklist );
			}
		} else {
			$linkarr =	yaml_parse_file( $this->pst->pst_dir . '/linklist.yaml' );
		}

		$urls = "'" . $url  . "'";
		if ( $linkarr ) {
			$arr = array_keys( $linkarr );
			$urls = array();
			foreach ( $arr as $val ) {
				$urls[] = "'" . $val . "'";
			}
			$urls = join( ",\n\t\t\t\t" , $urls );
		}

		foreach ( $loop as $path ) {
			$conf = file_get_contents( $this->pst->pst_dir . $path );
			$conf = preg_replace( '#__profile__#', $profile, $conf );
			$conf = preg_replace( '#__wexal_dir__#', $this->pst->wexal_dir, $conf );
			$conf = preg_replace( '#__url__#', $url, $conf );
			$conf = preg_replace( '#__urls__#', $urls, $conf );
			file_put_contents( $this->pst->pst_dir . $path, $conf );
//			if ( ! is_file( $this->pst->userdir . $path ) ) {
//				file_put_contents( $this->pst->userdir . $path, $conf );
//				$cmd = "chown kusanagi.kusanagi " . escapeshellarg( $this->pst->userdir . $path );
//				$ret = shell_exec( $cmd );
//			}
		}
		file_put_contents( $this->pst->pst_dir . '/host.txt', $host );
		if ( ! is_file( $this->pst->userdir . '/host.txt' ) ) {
			file_put_contents( $this->pst->userdir . '/host.txt', $host );
			$cmd = "chown kusanagi.kusanagi " . escapeshellarg( $this->pst->userdir . '/host.txt' );
			$ret = shell_exec( $cmd );
		}
	}
}

function nginx() {
$confs = array(
	'nginx_ssl' => $this->pst->nginx_ssl_conf,
	'nginx_http' => $this->pst->nginx_http_conf,
);

$profile = preg_replace( '#\.#', '_d_', $this->pst->profile );
$profile = preg_replace( '#-#', '_h_', $profile );

$rebuild_conf = in_array( '--rebuildconf', $this->pst->argv );
if ( ! file_exists( $this->pst->nginx_pst_conf_dir ) ) {
	mkdir( $this->pst->nginx_pst_conf_dir, 0755, true );
} elseif ( ! $rebuild_conf ) {
	return;
}
$pst_conf_dir = str_replace( '/etc/nginx/', '', $this->pst->nginx_pst_conf_dir );

foreach ( glob( "{$this->pst->pst_confs_dir}/nginx_*.conf") as $org_pst_conf ) {
	$name = str_replace( 'nginx_', '', basename( $org_pst_conf ) );
	$pst_conf = "{$this->pst->nginx_pst_conf_dir}/{$name}";
	if ( file_exists( $pst_conf ) ) {
		unlink( $pst_conf );
	}
	$content = file_get_contents( $org_pst_conf );
	if ( in_array( $name, array( 'location_php_lua.conf' ) ) ) {
		$content = preg_replace( '#profile#', $this->pst->profile, $content );
	} else {
		$content = preg_replace( '#profile#', $profile, $content );
	}
	wexal_pst_utils::create_new_pst_file( $pst_conf, $content );
}

foreach ( $confs as $conf_key => $conf_val ) { 
	if ( $rebuild_conf ) {
		wexal_pst_utils::restore_pst_file( $conf_val );
	}
	$conf = file_get_contents( $conf_val );
	if ( preg_match( '/PST section/', $conf ) ) { continue; }
	$arr = mb_split( "\n", $conf );
	array_pop( $arr );

	$lua_conf = '';
	if ( ! preg_match( '/_filter_by_lua/', $conf ) ) {
		$lua_conf = "include {$pst_conf_dir}/location_php_lua.conf;";
	}

	$new_conf = array();
	$depth = 0;
	$indent = '';
	$reg = preg_quote( '\.(jpg|jpeg|gif|png|css|js|swf', '#' );
	$reg2 = preg_quote( 'if ($http_cookie ~* "comment_author', '#' );
	$reg3 = preg_quote( 'if ($request_uri ~* "(/wp-admin/|/xmlrpc.php', '#' );

	$skip = false;
	$sec2_done = false;
	foreach ( $arr as $line ) {

		if ( preg_match( '/^\s*#/', $line ) ) {
			$new_conf[] = $line;
			continue;
		}
		
		if ( preg_match( '#location\s*\/\s*\{#', $line ) ) {
			$new_conf[] = $indent."### PST section start ###";
			$new_conf[] = $indent."include {$pst_conf_dir}/server_common.conf;";
			$new_conf[] = $indent."### PST section end ###";
			$new_conf[] = '';
		} elseif ( 'nginx_ssl' == $conf_key && preg_match( '#^\s*server\s*\{\s*$#', $line ) ) {
			$new_conf[] = $indent."### PST map start ###";
			$new_conf[] = $indent."include {$pst_conf_dir}/http_map.conf;";
			$new_conf[] = $indent."### PST map end ###";
			$new_conf[] = '';
		} elseif ( preg_match( '#fastcgi_param SCRIPT_FILENAME#', $line ) ) {
			$new_conf[] = $line;
			$new_conf[] = $indent."fastcgi_param WEXAL_PST_EXT \$ext_{$profile};";
			continue;
		} elseif ( preg_match( '/set\s*\$do_not_cache\s*\d;\s*##*\s*page\s*cache/', $line ) ) {
			$new_conf[] = $indent.'set $do_not_pagespeed 0; ## pagespeed';
		} elseif ( preg_match( '#fastcgi_cache_key\s+(.*)$#', $line, $m ) ) {
			$new_conf[] = $indent."set \$fastcgi_cache_key " . $m[1];
			$new_conf[] = '#'.$indent.'set $fastcgi_cache_key "$ext_' . $profile . ':'.$m[1].'"; ## if using CDN';
			$new_conf[] = $indent."fastcgi_cache_key    \$fastcgi_cache_key;";
			continue;
		} elseif ( preg_match ( "#$reg2|$reg3#", $line ) ) {
			$depth++;
			$indent = str_repeat( "\t", $depth );
			$new_conf[] = $line;
			$new_conf[] = $indent."set \$do_not_pagespeed 1;";
			continue;
		} elseif ( preg_match ( '#fastcgi_cache\s+wpcache;#', $line ) ) {
			$new_conf[] = $indent."set \$tmp_pst 'on';";
			$new_conf[] = $indent.'if ( $arg_pst = "off" ) {';
			$indent = str_repeat( "\t", ++$depth );
			$new_conf[] = $indent.'set $do_not_pagespeed 1;';
			$new_conf[] = $indent.'brotli off;';
			$new_conf[] = $indent.'gzip off;';
			$new_conf[] = $indent.'set $tmp_pst "_tmp_pst_off_";';
			$indent = str_repeat( "\t", --$depth );
			$new_conf[] = $indent.'}';
			$new_conf[] = '';
			$new_conf[] = $indent.'if ( $do_not_pagespeed = 0 ) {';
			$indent = str_repeat( "\t", ++$depth );
			$new_conf[] = $indent.'pagespeed Allow "*";';
			$new_conf[] = $indent.'pagespeed EnableFilters collapse_whitespace,trim_urls,remove_comments;';
			$indent = str_repeat( "\t", --$depth );
			$new_conf[] = $indent.'}';
			$new_conf[] = $indent.'add_header Set-Cookie "tmp_pst=$tmp_pst; Path=/;";';

			if ( $lua_conf ) {
				$new_conf[] = '';
				$new_conf[] = $indent.$lua_conf;
				$new_conf[] = '';
			}
		} elseif ( preg_match ( '#location\s*~\*\s*' . $reg . '#', $line ) ) {
			$new_conf[] = $indent."include {$pst_conf_dir}/location_proxy_images.conf;";
			$new_conf[] = $indent."include {$pst_conf_dir}/location_images.conf;";
			$sec2_done = true;
			$skip = true;
			$sec2_depth = $depth;
		}

		if ( preg_match( '/\{/', $line ) ) {
			$depth++;
		} elseif ( preg_match( '/\}/', $line ) ) {
			$depth--;
		}

		if ( $sec2_done && $sec2_depth == $depth ) {
			$skip = false;
			$sec2_done = false;
			continue;
		}

		if ( $skip ) {
			continue;
		}
	
		$new_conf[] = $line;

		if ( $depth >= 1 ) {
			$indent = str_repeat( "\t", $depth );
		} else {
			$indent = '';
		}
	}

	if ( 0 !== $depth ) {
		return;
	}

	wexal_pst_utils::create_new_pst_file( $conf_val, $new_conf );
	$ret = shell_exec( 'nginx -t 2>&1' );
	if ( preg_match ( '/test is successful/', $ret ) ) {
		$ret = wexal_pst_utils::restart_service_on_enabled('nginx');
	} else {
		var_dump( $ret );
		rename( $conf_val . '.before.pst', $conf_val );
	}
} // end foreach

} // function end

/**
 * Setting httpd config
 */
function httpd() {
	$confs = array(
		'httpd_ssl' => $this->pst->httpd_ssl_conf,
		'httpd_http' => $this->pst->httpd_http_conf,
	);

	$rebuild_conf = in_array( '--rebuildconf', $this->pst->argv );
	if ( ! file_exists( $this->pst->httpd_pst_conf_dir ) ) {
		mkdir( $this->pst->httpd_pst_conf_dir, 0755, true );
	} elseif ( ! $rebuild_conf ) {
		return;
	}
	$pst_conf_dir = str_replace( '/etc/httpd/', '', $this->pst->httpd_pst_conf_dir );

	foreach ( glob( "{$this->pst->pst_confs_dir}/httpd_*.conf") as $org_pst_conf ) {
		$name = str_replace( 'httpd_', '', basename( $org_pst_conf ) );
		$pst_conf = "{$this->pst->httpd_pst_conf_dir}/{$name}";
		if ( file_exists( $pst_conf ) ) {
			unlink( $pst_conf );
		}
		$content = file_get_contents( $org_pst_conf );
		$content = preg_replace( '#profile#', $this->pst->profile, $content );
		wexal_pst_utils::create_new_pst_file( $pst_conf, $content );
	}

	foreach ( $confs as $conf_key => $conf_val ) {
		if ( $rebuild_conf ) {
			wexal_pst_utils::restore_pst_file( $conf_val );
		}
		$conf = file_get_contents( $conf_val );
		if ( preg_match( '/PST section/', $conf ) ) { continue; }
		$arr = mb_split( "\n", $conf );
		array_pop($arr);

		$new_conf = array();
		$depth = 0;
		$indent = '';
		foreach ( $arr as $line ) {
			
			if ( preg_match( '/^\s*#/', $line ) ) {
				$new_conf[] = $line;
				continue;
			}

			if ( preg_match ( '#</VirtualHost>#', $line ) ) {
				$new_conf[] = $indent.'### PST section start ###';
				$new_conf[] = $indent."Include {$pst_conf_dir}/virtualhost_common.conf";
				$new_conf[] = $indent.'### PST section end ###';
			} elseif ( 'httpd_http' ==  $conf_key && preg_match ( '#RewriteCond %{HTTPS} off#', $line ) ) {
				$last_line = array_pop( $new_conf );
				if ( preg_match( '#RewriteEngine#', $last_line ) ) {
					$new_conf[] = $indent.'RewriteEngine On';
					$new_conf[] = $indent.'RewriteRule . - [E=REDIRECT_SSL:off]';
					$new_conf[] = $indent.'RewriteCond %{ENV:REDIRECT_SSL} ^on$';
				} else {
					$new_conf[] = $last_line;
				}
			}

			$new_conf[] = $line;

			if ( preg_match( '#<[^/](.*?)>#', $line ) ) {
				$depth++;
			} elseif ( preg_match( '#</(.*?)>#', $line ) ) {
				$depth--;
			}
			if ( $depth >= 1 ) {
				$indent = str_repeat( "\t", $depth );
			} else {
				$indent = '';
			}
		}

		if ( 0 !== $depth ) {
			return;
		}
		
		wexal_pst_utils::create_new_pst_file( $conf_val, $new_conf );
		$ret = shell_exec( 'httpd -t 2>&1' );
		if ( preg_match ( '/Syntax OK/', trim($ret) ) ) {
			$ret = wexal_pst_utils::restart_service_on_enabled('httpd');
		} else {
			wexal_pst_utils::restore_pst_file( $conf_val );
		}
	}
} // function end

} // class end

