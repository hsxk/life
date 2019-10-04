<?php

class wexal_page_speed_technology_lib_for_wp {

public $pst;
public $mpdir;
public $event_ob_start = 'init';
public $priority_ob_start = 11;

function __construct( $pst=false ) {
	$this->pst = $pst;
	$this->mpdir = preg_replace( '#'.$this->pst->DocumentRoot.'#', '', WP_CONTENT_DIR ) . '/mu-plugins/pst';
}

function get_ob_params() {
	$s = $this->event_ob_start;
	$p = $this->priority_ob_start;
	$hooks = array(
		"plugin_loaded",
		"sanitize_comment_cookies",
		"setup_theme",
		"after_setup_theme",
		"init",
		"wp_loaded",
		"wp",
		"template_redirect",
	);
	if ( ! in_array( $s, $hooks ) ) {
		$s = 'init';
	}

	if ( ! is_numeric( $p ) ) {
		$p = 11;
	}
	return array( 'event_ob_start' => $s, 'priority_ob_start' => $p );
}

function merge( $def, $args ) {
	$ret = array();
	foreach ( $def as $key => $val ) {
		if ( isset( $args[ $key ] ) ) {
			$ret[] = $args[ $key ];
		} else {
			$ret[] = $val;
		}
	}
	return $ret;
}

function wexal_preload( $args ) {
	foreach ( $args as $url ) {
		$media = '';
		if ( is_array( $url ) && count( $url ) == 1) {
			$media = array_shift( array_keys( $url ) );
			$media = ' media="(' . $media . ')"';
			$url = array_shift( $url );
		}
		$ret = pathinfo( $url );
		if ( ! isset( $ret['extension'] ) ) {
			continue;
		}
		$ext = $ret['extension'];
		$url = esc_url( $url );
		if ( in_array( $ext, array( 'woff2', 'woff', 'eot', 'ttf', 'otf' ) ) ) {
			echo "<link rel='preload' href='$url' as='font'$media type='font/$ext' crossorigin>\n";
		} elseif ( in_array( $ext, array(
			'png', 'jpeg', 'jpg', 'gif', 'webp', 'jp2',
			'tiff', 'tif', 'svg', 'jxr', 'bmp' ) ) ) {
			echo "<link rel='preload' href='$url' as='image'$media>\n";
		} elseif ( 'js' == $ext ) {
			echo "<link rel='preload' href='$url' as='script'$media>\n";
		} elseif ( 'css' == $ext ) {
			echo "<link rel='preload' href='$url' as='style'$media>\n";
		}

	}
}

function wexal_remove_emoji( $args ) {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles', 10 );
}

function wexal_remove_meta( $args ) {
	$def = array(
		'feed_links' => 2,
		'feed_links_extra' => 3,
		'rsd_link' => 10,
		'wlwmanifest_link' => 10,
		'adjacent_posts_rel_link_wp_head' => 10,
		'rest_output_link_wp_head' => 10,
		'wp_oembed_add_discovery_links' => 10,
		'wp_oembed_add_host_js' => 10,
		'wp_shortlink_wp_head' => 10,
		'rel_canonical' => 10,
		'wp_generator' => 10,
	);
	$args = array_merge( $def, $args );
	foreach ( $args as $key => $val ) {
		if ( is_numeric( $val ) ) {
			remove_action( 'wp_head', $key, $val );
		}
	}
}

function wexal_remove_header( $args ) {
	$def = array(
		'pings_open' => true,
		'rest_output_link_header' => 11,
		'wp_shortlink_header' => 11,
	);
	$args = array_merge( $def, $args );
	if ( $args['pings_open'] ) {
		add_filter( 'pings_open', function() { return 0; } );
	}
	unset( $args['pings_open'] );
	foreach ( $args as $key => $val ) {
		if ( is_numeric( $val ) ) {
			remove_action( 'template_redirect', $key, $val );
		}
	}
}

function wexal_remove_js( $args ) {
	foreach ( $args as $handle ) {
		wp_deregister_script( $handle );
	}
}

function wexal_add_js( $args ) {
	$def = array( '', false, array(), false, false );
	$args = $this->merge( $def, $args );
	list( $handle, $src, $deps, $ver, $in_footer ) = $args;
	wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
}

function wexal_remove_css( $args ) {
	foreach ( $args as $handle ) {
		wp_deregister_style( $handle );
	}
}

function wexal_add_css( $args ) {
	$def = array( '', '', array(), false, 'all' );
	$args = $this->merge( $def, $args );
	list( $handle, $src, $deps, $ver, $media ) = $args;
	wp_enqueue_style( $handle, $src, $deps, $ver, $media );
}

function wexal_remove_hook( $args ) {
	global $wp_filter;
	$def = array( '', '', 10, false );
	$args = $this->merge( $def, $args );
	list( $tag, $function_to_remove, $priority, $is_object ) = $args;

	if ( false == $is_object ) {
		remove_filter( $tag, $function_to_remove, $priority );
	} else {
		$arr = $wp_filter[$tag][$priority];
		foreach ( $arr as $key => $val ) {
			$func = $val['function'];
			if ( is_object ($func ) )  {
				$cn = get_class( $func );
				if ( $cn == $function_to_remove ) {
					remove_filter( $tag, $func, $priority );
				}
			} elseif ( isset( $func[0] ) && is_object( $func[0] ) ) {
				if ( ! is_array( $function_to_remove ) || count( $function_to_remove ) < 2 ) {
					continue;
				}
				$cn = get_class( $func[0] );
				if ( $cn == $function_to_remove[0] && $func[1] == $function_to_remove[1] ) {
					remove_filter( $tag, array( $func[0], $func[1] ), $priority );
				}
			}
		}
	}
}

function wexal_opt_genericons( $args ) {
	$this->wexal_remove_css( array( 'genericons' ) );
	$this->wexal_add_css( array( 'wexal-opt-genericons', $this->mpdir . '/css/genericons.css' ) );
}

function wexal_remove_wpcf7( $args ) {
	wp_deregister_script( 'contact-form-7' );
	wp_deregister_style( 'contact-form-7' );
}

function wexal_flush_server_push_external_css( &$str, $args ) {
	if ( preg_match_all( '#<link rel=[^>]*?stylesheet[^>]*?href=(\'|")([^>\#]*?)(\'|")#', $str, $m ) ) {
		$urls = $m[2];
		foreach ( $urls as $url ) {
			header("Link: <$url>; rel=preload; as=style", false);
		}
	}
}

function wexal_flush_shorten_url( &$str, $args ) {
	if ( ! isset( $_SERVER['HTTP_HOST'] ) ) { return; }
	$host = $_SERVER['HTTP_HOST'];
	if ( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] ) {
		$protocol = 'https';
	} else {
		$protocol = 'http';
	}
	$host = preg_quote( $host, '#' );
	$str = preg_replace( "#$protocol://$host/wp-#", '/wp-', $str );
	$str = preg_replace( "# (href|src)=(\"|')https://$host/#", ' ${1}=${2}/', $str );
	$str = preg_replace( "#=(\"|')/wp-content/uploads/#", '=$1/_wu/', $str );
	$str = preg_replace( "#=(\"|')/wp-content/themes/#", '=$1/_wt/', $str );
	$str = preg_replace( "#=(\"|')/wp-content/plugins/#", '=$1/_wp/', $str );
	$str = preg_replace( "#=(\"|')/wp-includes/#", '=$1/_wi/', $str );
}

function is_apply_script( $src, $args ) {
	$ret = false;
	$def = array( 'apply_script' => '.', 'exclude_script' => false );
	$args = array_merge( $def, $args );

	if ( $args['apply_script'] && preg_match( '#' . $args['apply_script'] . '#' , $src ) ) {
		$ret = true;
	}

	if ( $args['exclude_script'] && preg_match( '#' . $args['exclude_script'] . '#' , $src ) ) {
		$ret = false;
	}

	return $ret;
}

function wexal_flush_defer_external_js( &$str, $args ) {
	$arr = mb_split( "\n", $str );
	array_pop( $arr );
	$new = array();
	foreach ( $arr as $line ) {
		if ( preg_match( '#<script type=\'text/javascript\' src=\'([^>]+?)\'#', $line, $m ) ) {
			$src = $m[1];
			if ( $this->is_apply_script( $src, $args ) ) {
				$line = preg_replace(
					'#<script type=\'text/javascript\' src=#',
					'<script defer src=',
					$line
				);
			}
		}
		$new[] = $line;
	}
	$str = join( "\n", $new );

}

function wexal_flush_set_cookie_for_cdn( &$str, $args ) {
	if ( ! isset( $_SERVER['WEXAL_PST_EXT'] ) ) { return; }
	$ext = $_SERVER['WEXAL_PST_EXT'];

	$def = array( 'domain' => false, 'external_url' => false );
	$args = array_merge( $def, $args );
	extract( $args, EXTR_SKIP );
	if ( true == $domain ) {
		setcookie( 'WEXAL_PST_EXT', $ext, 0, '/', $domain );
	} else {
		setcookie( 'WEXAL_PST_EXT', $ext, 0, '/', '' );
	}

	if ( true == $external_url ) {
		if ( preg_match( '#\?#', $external_url ) ) {
			$delim = '&';
		} else {
			$delim = '?';
		}
		$str = preg_replace( '#</head>#', "<script async src='${external_url}${delim}WEXAL_PST_EXT=${ext}'></script>\n</head>", $str, 1 );
	}
}

function wexal_flush_replace( &$str, $args ) {
	$def = array( '/_#_#_#_/', '_#_#_#_', -1 );
	if ( isset( $args[0] ) && ! is_array( $args[0] ) ) {
		$args = array( $args );
	}
	foreach ( $args as $arr ) {
		if ( ! is_array( $arr ) ) { continue; }
		$arr = $this->merge( $def, $arr );
		list( $pattern, $replacement, $limit ) = $arr;
		$pattern = '#' . preg_quote( $pattern, '#' ) . '#';
		$str = preg_replace( $pattern, $replacement, $str, $limit );
	}
}

function wexal_flush_replace_anything( &$str, $args ) {
	$def = array( '/_#_#_#_/', '_#_#_#_', -1 );
	if ( isset( $args[0] ) && ! is_array( $args[0] ) ) {
		$args = array( $args );
	}
	foreach ( $args as $arr ) {
		if ( ! is_array( $arr ) ) { continue; }
		$arr = $this->merge( $def, $arr );
		list( $pattern, $replacement, $limit ) = $arr;
		$this->safe_replace( $pattern, $replacement, $str, $limit );
	}
}

function safe_replace( $pattern="/_#_#_#_/", $replacement="_#_#_#_", &$str, $limit=1 ) {
	if ( @preg_match( $pattern, $str ) ) {
		$str = preg_replace( $pattern, $replacement, $str, $limit );
		return true;
	} else {
		return false;
	}
}

function wexal_flush_lazy_youtube( &$str, $args ) {
	$reg = '#(<iframe[^>]*?width="(.*?)" height="(.*?)"[^>]*?)src(="https://www.youtube.com/embed/(.*)[^>]*?' . '></iframe>)#';

	if ( preg_match( $reg, $str ) ) {
		$def = array( 'mobile' => 'mq', 'pc' => 'hq', 'ratio' => '56.25' );
		$args = array_merge( $def, $args );
		extract( $args, EXTR_SKIP );

		$str = preg_replace( $reg, '<div class="youtube">${1}data-src${4}</div>', $str);
		$tmp = "<script>_wexal_pst.lazy_youtube={'mobile':'$mobile', 'pc':'$pc'}</script><script defer src='/wp-content/mu-plugins/pst/js/lazy_youtube.js?v={$this->pst->version}'></script>";
		$tmp .= "<style>.youtube img:hover { opacity: 0.5; } .youtube { position: relative; padding-bottom: ${ratio}%; height: 0; overflow: hidden; } .youtube iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style>\n</body>";
		$str = preg_replace( '#</body>#', $tmp, $str );
	}
}

function wexal_flush_engagement_delay( &$str, $args ) {
	$def = array( 
		'score' => 250,
		'pscore' => 10,
		'high' => 'DOMContentLoaded',
		'low' => 'load',
		'delay' => 1000,
		'ratio' => 4,
		'max-age' => 7776000,
		'inline' => false,
		'debug' => false,
		'scripts' => array()
	);
	$args = array_merge( $def, $args );
	$dbg = 'false';
	if ( $args['debug'] ) { $dbg = 'true'; }
	$version = $this->pst->version;
	$js = '/pst/js/engagement_delay.js';
	$script = "<script>!function(){var w=_wexal_pst.en;w.sc=${args['score']};w.hg='${args['high']}';w.lw='${args['low']}';w.dl=${args['delay']};w.rt=${args['ratio']};w.ma=${args['max-age']};w.ps=${args['pscore']};w.dbg=$dbg}();</script>\n";

	if ( $args['inline'] ) {
		$js = WPMU_PLUGIN_DIR . $js;
		$documentroot_reg = '#'.preg_quote( $this->pst->DocumentRoot, '#' ).'#';
		$opt_dir = $this->pst->wexal_dir . '/optdir';
		$opt_js = preg_replace( $documentroot_reg, $opt_dir, $js . '.opt.js');
		if ( is_file ( $opt_js ) ) {
			$js = $opt_js;
		}
		$js = file_get_contents( $js );
		$script .= "<script>$js</script>\n";
	} else {
		$js = WPMU_PLUGIN_URL . $js;
		$script .= "<script type='text/javascript' src='$js?v=${version}'></script>\n";
		header("Link: <$js?v=${version}>; rel=preload; as=script", false);
	}

	$str = preg_replace( '#</body>#', "$script\n</body>", $str );

	$def = array( 'name' => '', 'type' => 'closure', 'pattern' => '_#_#_#_', 'args' => '_#_#_#_', 'path' => '', 'needle' => '_#_#_#_', 'query' => 'auto', 'sync' => 'sync', 'cmd' => array() );
	$count = 0;
	foreach ( $args['scripts'] as $row ) {
		$row = array_merge( $def, $row );
		$name = $row['name'];
		$type = $row['type'];
		$pattern = $row['pattern'];
		$p = $row['args'];
		$path = $row['path'];
		$needle = $row['needle'];
		$cmd = $row['cmd'];
		$query = $row['query'];
		$sync = $row['sync'];

		$count++;
		if ( '' == $name ) {
			$name = "f$count";
		}
		
		$s = '';
		$x = '';
		$c = ",c:$count";
		if ( 'sync' == $sync ) { $s = ",s:1"; }
		if ( in_array( $type, array( 'inline jsx', 'closurex', 'cssx', 'jsx' ) ) ) {
			 $x = ",x:1";
		}

		if ( 'inline js' == $type || 'inline jsx' == $type ) {
			$needle = preg_quote( $needle, '#' );
			$regex = '#<script[^<]*?' . '>' . '([^<]*?' . $needle . '.*?)</script>#s';
			$replace = "<script>_wexal_pst.en.fn['$name']={f:function(){\$1},p:[]$s$x$c}</script>";
			$this->safe_replace( $regex, $replace, $str );

		} elseif ( 'closure' == $type || 'closurex' == $type ) {
			$reg = preg_quote( $pattern, '/' );
			$typereg = '#^\s*(\(|\!|\+|\-|void|typeof)\s*function#';
			if ( @preg_match( $typereg, $pattern, $m ) ) {
				$typeof = $m[1];
			} else {
				continue;
			}

			$pattern = preg_replace( $typereg, 'function', $pattern );
			$this->safe_replace( "/$reg/", "_wexal_pst.en.fn['$name']={f:$pattern", $str );
			$reg = preg_quote( $p, '/' );
			if ( '(' == $typeof && ! preg_match( "/\s*\(\s*$reg.*\)\s?\);/", $str) ) {
				$prefix = '\)';
			} else {
				$prefix = '';
			}
			$this->safe_replace( "/$prefix\s*\(\s*($reg).*?;/", ',p:[${1}]' . "$s$x$c" .'};', $str );

			if ( $cmd ) {
				$j=0;
				foreach( $cmd as $row ) {
					if ( isset( $row['function'] ))  {
						$func = $row['function'];
					} else {
						$func = $name;
					}
					if ( isset( $row['args'] ) ) {
						$p = $row['args'];
					} else {
						continue;
					}
					$reg = '/(;|\s|^)' . preg_quote( $func, '/' ) . '\s*\(\s*' . preg_quote( $p, '/' ) . '\s*\)\s*;/' ;
					if ( 0 == $j ) {				$this->safe_replace( $reg, "\${1}_wexal_pst.en.fn['$name'].cmd=[['$func',$p]];", $str );
						$this->safe_replace( $reg, "\${1}_wexal_pst.en.fn['$name'].cmd=[['$func',$p]];", $str );
						$j++;
					} else {
						$this->safe_replace( $reg, "\${1}_wexal_pst.en.fn['$name'].cmd.push( ['$func',$p] );", $str );
					} 
				}
	
			}

		} elseif ( 'css' == $type || 'cssx' == $type ) {
			if ( false == $path ) {	continue; }
			$js_path = esc_js( $path );
			$str_path = preg_quote( $path, '/' );
			$reg = '<link[^>]*?rel\s*=\s*(\'|")stylesheet[^>]*?href\s*=\s*(\'|")' . $str_path . '(\?[^\'">]+)?(\'|")[^>]*?' . '>';
			if ( 'auto' == $query && preg_match( "/$reg/", $str, $m ) ) {
				$js_path .= $m[3];
			}

			$this->safe_replace( "/$reg/", "<script>_wexal_pst.en.css['$name']={url:'${js_path}'$s$x$c}</script>", $str );

		} elseif ( 'js' == $type || 'jsx' == $type ) {
			if ( false == $path ) {	continue; }
			$js_path = esc_js( $path );
			$str_path = preg_quote( $path, '/' );
			$reg = '<script[^>]*?src\s*=\s*(\'|")' . $str_path . '(\?[^\'">]+)?(\'|")[^>]*?' . '><\/script>';
			if ( 'auto' == $query && preg_match( "/$reg/", $str, $m ) ) {
				$js_path .= $m[2];
			}

			$this->safe_replace( "/$reg/", "<script>_wexal_pst.en.js['$name']={url:'${js_path}'$s$x$c}</script>", $str );

		}


	}

}

} // class end
