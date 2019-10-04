<?php
require_once 'utils.php';

class wexal_pst_control {

public $config;
public $wexal_dir;
public $pst_dir;
public $DocumentRoot;
public $nginx_ssl_conf;
public $logdir;
public $userdir;
public $version = '1.1.8';

function __construct() {
	global $argv;

	$this->wexal_dir = dirname( dirname( __FILE__ ) );
	$this->pst_dir = dirname( __FILE__ );
	$this->userdir = $this->wexal_dir . '/userdir';
	$this->profile = basename( dirname( $this->wexal_dir ) );
	$this->logdir = "/home/kusanagi/{$this->profile}/log/pst";

	$this->pst_confs_dir = "{$this->wexal_dir}/page_speed_technology/confs";

	$this->httpd_pst_conf_dir = "/etc/httpd/conf.d/{$this->profile}_pst";
	$this->httpd_ssl_conf = '/etc/httpd/conf.d/' . $this->profile . '_ssl.conf';
	$this->httpd_http_conf = '/etc/httpd/conf.d/' . $this->profile . '_http.conf';

	$this->nginx_pst_conf_dir = "/etc/nginx/conf.d/{$this->profile}_pst";
	$this->nginx_ssl_conf = '/etc/nginx/conf.d/' . $this->profile . '_ssl.conf';
	$this->nginx_http_conf = '/etc/nginx/conf.d/' . $this->profile . '_http.conf';

	$this->DocumentRoot = dirname( $this->wexal_dir ) . '/DocumentRoot';

	$this->set_config();

	if ( isset( $argv[1] ) ) {
		$method = $argv[1];
		if ( isset( $argv[2] ) ) {
			$param = $argv[2];
		} else {
			$param = false;
		}
		
		if ( method_exists( $this, $method ) ) {
			array_shift( $argv );
			array_shift( $argv );
			$this->argv = $argv;
			$this->$method( $param );
		}
		else if ( in_array( $method, array( 'opt_js', 'opt_css', 'opt_image', 'opt', 'uncss', 'purge' ) ) ) {
			array_shift( $argv );
			$this->dispath( $argv ); 
		}
		else if ( preg_match( '#/pst\.control\.php$#', $argv[0] ) ) {
			echo "Bad command: pst $argv[1]\n";
			echo "Try: pst help\n";
		}
	}
}

function init() {
	$p = $this->pst_dir . '/make.config.php';
	$u = $this->userdir . '/make.config.php';
	$y = $this->wexal_dir . '/pst.config.yaml';
	$protocol = 'https';
	if ( in_array( '--http', $this->argv ) ) {
		$protocol = 'http';
	}

	if ( is_file( $p ) ) {
		$conf = file_get_contents( $p );
		if ( preg_match( '#__protocol__#', $conf ) ) {
			$conf = preg_replace( '#__protocol__#', $protocol, $conf );
			file_put_contents( $p, $conf );
		}
	}

	if ( is_file( $u ) ) {
		$conf = file_get_contents( $u );
		if ( preg_match( '#__protocol__#', $conf ) ) {
			$conf = preg_replace( '#__protocol__#', $protocol, $conf );
			file_put_contents( $u, $conf );
			$cmd = 'chown kusanagi.kusanagi ' . escapeshellarg( $u );
			$ret = shell_exec( $cmd );
		}
	}

	if ( is_file( $this->wexal_dir . '/pst.config.json' ) && ! is_file( $y ) ) {
		$yaml = yaml_parse_file( $this->wexal_dir . '/pst.config.json' );
		if ( $yaml ) {
			$this->config = $yaml;
			yaml_emit_file( $y, $yaml );
			$cmd = 'chown kusanagi.kusanagi ' . escapeshellarg( $y );
			$ret = shell_exec( $cmd );
			rename( $this->wexal_dir . '/pst.config.json', $this->wexal_dir . '/pst.config.json.bak' );
		} else {
			echo "Error: Can not parse config file.\n";
			exit;
		}
	}

	if ( ! is_file( $this->wexal_dir . '/pst.config.yaml' ) ) {
		$this->make_config( 'not' );
	}

	if ( ! is_file( $this->userdir . '/pst.config.yaml' ) ) {
		$cmd = 'cp -p ' . escapeshellarg( $y ) . ' ' . escapeshellarg( $this->userdir . '/pst.config.yaml' );
		$ret = shell_exec( $cmd );
	}

	require_once( dirname( __FILE__ ) . '/init.php' );
	new wexal_pst_init( $this );
}

function make() {
	$this->make_config();
}

function make_config( $flag=false ) {
	if ( ! empty($this->config['conf']) &&
		 'yaml' == $this->config['conf']['format'] && is_file( $this->userdir . '/pst.config.yaml' ) ) {
		require_once( dirname( __FILE__ ) . '/make.config.yaml.php' );
		new wexal_make_config_yaml( $this );
	} else { 
	
		require_once( dirname( __FILE__ ) . '/make.config.php' );
		if ( is_file( $this->userdir . '/make.config.php' ) ) {
			require_once( $this->userdir .'/make.config.php' );
			new user_wexal_make_config( $this );	
		} else {
			new wexal_make_config( $this );
		}
	}

	if ( 'not' != $flag ) {
		$method = $this->config['pst'];
		$this->$method();
	}
}

function get_config( $param=false ) {
	if ( $param && isset( $this->config[$param] ) ) {
		$conf = $this->config[$param];
		if ( is_array( $conf ) ) {
			print_r( $conf );
		} else {
			echo $conf;
		}
	} elseif ( false == $param ) {
		print_r( $this->config );
	}
}

function out( $param=false ) {
	$ln = $this->DocumentRoot . '/out';
	$t = $this->userdir . '/out';
	if ( 'on' == $param ) {
		if ( ! file_exists( $ln ) && file_exists( $t ) ) {
			symlink( $t, $ln );
		}
	} elseif ( 'off' == $param ) {
		if ( is_link( $ln ) ) {
			unlink( $ln );
		}
	}
}

function lighthouse( $param=false ) {
	if ( false == $param ) {
		if ( is_file( $this->userdir . '/host.txt' ) ) {
			$host = file_get_contents( $this->userdir . '/host.txt' );
		} else {
			$host = file_get_contents( $this->pst_dir . '/host.txt' );
		}
		$host = trim( $host );
		$url = $this->config['protocol'] . "://$host/";
	} else {
		$url = $param;
	}

	$url = escapeshellarg( $url );
	$t = escapeshellarg( $this->userdir . '/out/lighthouse.html' );
	$cmd = "lighthouse $url " . '--chrome-flags="--no-sandbox --headless" --output html --output-path ' . $t;
	$ret = shell_exec( $cmd );
}

function RPA( $param=false ) {
	if ( false == $param ) { return; }
	$u = $this->userdir . "/js/$param.js";
	$p = $this->pst_dir . "/js/$param.js";
	if ( is_file( $u ) ) {
		$t = escapeshellarg( $u );
	} elseif ( is_file( $p ) ) {
		$t = escapeshellarg( $p );
	} else {
		return;
	}
	
	$args = '';
	if ( isset( $this->argv[1] ) ) {
		$tmp = $this->argv;
		array_shift( $tmp );
		$args = array();
		foreach ( $tmp as $val ) {
			$args[] = escapeshellarg( $val );
		}
		$args = join( " ", $args );
	}

	$cmd  = "/usr/local/bin/node $t $args";
	$ret = shell_exec( $cmd );
	echo $ret;
}

function AI( $param=false ) {
	if ( false == $param ) { return; }
	$param = 'ai.'. $param;
	$this->RPA( $param );
}

function dispath( $param=array() ) {
	if ( ! is_array( $param ) ) {
		$param = array( 'optimize' );
	}

	require_once( dirname( __FILE__ ) .'/worker.php' );
	if ( is_file( $this->userdir . '/worker.php' ) ) {
		require_once( $this->userdir .'/worker.php' );
		new user_wexal_pst_worker( $this, $param );
	} else {
		new wexal_pst_worker( $this, $param );
	}

}

function on() {
	$this->config['pst'] = 'on';
	$this->put_config();
	$optdir = $this->DocumentRoot . '/' . $this->config['odir'];
	$target = $this->wexal_dir . '/optdir';
	if ( ! file_exists( $optdir ) ) {
		symlink( $target, $optdir );		
	}

	if ( ! in_array( 'apply_logged_in_user', $this->config['options'] ) && ! is_link( $optdir . '/_wexal.org' ) ) {
		symlink( $this->DocumentRoot, $optdir . '/_wexal.org' );
	} elseif ( in_array( 'apply_logged_in_user', $this->config['options'] ) && is_link( $optdir . '/_wexal.org' ) ) {
		unlink( $optdir . '/_wexal.org' );
	}

	$restart_nginx = false;
	$server_common_conf = "{$this->nginx_pst_conf_dir}/server_common.conf";
	if ( file_exists( $server_common_conf ) ) {
		$conf = file_get_contents( $server_common_conf );
		$org = $conf;
		$conf = preg_replace ( '/brotli off;\s*##\s*on\/off/', 'brotli on; ## on/off', $conf );
		$conf = preg_replace ( '/gzip off;\s*##\s*on\/off/', 'gzip on; ## on/off', $conf );
		$conf = preg_replace ( '/pagespeed unplugged;\s*##\s*on\/unplugged/', 'pagespeed on; ## on/unplugged', $conf );
		if ( $conf && $org != $conf ) {
			file_put_contents( $server_common_conf, $conf );
			$restart_nginx = true;
		}
	} else {
		wexal_pst_utils::print_error( $server_common_conf );
	}

	$location_php_lua = "{$this->nginx_pst_conf_dir}/location_php_lua.conf";
	if ( file_exists( $location_php_lua ) ) {
		$conf = file_get_contents( $location_php_lua );
		$org = $conf;
		if ( in_array( 'lua', $this->config['options'] ) ) {
			$conf = preg_replace ( '/^#+((\t| )*?(header|body)_filter_by_lua_block.*#.*PST$)/m', '$1', $conf );
		} else {
			$conf = preg_replace ( '/^([^#\n\r]+?(\t| )*?(header|body)_filter_by_lua_block.*#.*PST$)/m', '#$1', $conf );
		}
		if ( $conf && $org != $conf ) {
			file_put_contents( $location_php_lua, $conf );
			$restart_nginx = true;
		}
	} else {
		wexal_pst_utils::print_error( $location_php_lua );
	}

	if ( $restart_nginx ) {
		$ret = wexal_pst_utils::restart_service_on_enabled('nginx');
	}

	$restart_httpd = false;
	$virtualhost_common = "{$this->httpd_pst_conf_dir}/virtualhost_common.conf";
	if ( file_exists( $virtualhost_common ) ) {
		$conf = file_get_contents( $virtualhost_common );
		$org = $conf;
		$conf = preg_replace ( '/[^# ]SetEnv no-gzip/', '	# SetEnv no-gzip', $conf );
		if ( $conf && $org != $conf ) {
			file_put_contents( $virtualhost_common, $conf );
			$restart_httpd = true;
		}
	} else {
		wexal_pst_utils::print_error( $virtualhost_common );
	}

	if ( $restart_httpd ) {
		$ret = wexal_pst_utils::restart_service_on_enabled('httpd');
	}
}

function off() {
	$this->config['pst'] = 'off';
	$optdir = $this->DocumentRoot . '/' . $this->config['odir'];
	if ( is_link( $optdir ) ) {
		unlink( $optdir );		
	}
	$this->put_config();

	$restart_nginx = false;
	$server_common_conf = "{$this->nginx_pst_conf_dir}/server_common.conf";
	if ( file_exists( $server_common_conf ) ) {
		$conf = file_get_contents( $server_common_conf );
		$org = $conf;
		$conf = preg_replace ( '/brotli on;\s*##\s*on\/off/', 'brotli off; ## on/off', $conf );
		$conf = preg_replace ( '/gzip on;\s*##\s*on\/off/', 'gzip off; ## on/off', $conf );
		$conf = preg_replace ( '/pagespeed on;\s*##\s*on\/unplugged/', 'pagespeed unplugged; ## on/unplugged', $conf );
		if ( $conf && $org != $conf ) {
			file_put_contents( $server_common_conf, $conf );
			$restart_nginx = true;
		}
	} else {
		wexal_pst_utils::print_error( $server_common_conf );
	}

	$location_php_lua = "{$this->nginx_pst_conf_dir}/location_php_lua.conf";
	if ( file_exists( $location_php_lua ) ) {
		$conf = file_get_contents( $location_php_lua );
		$org = $conf;
		$conf = preg_replace ( '/^([^#\n\r]+?(\t| )*?(header|body)_filter_by_lua_block.*#.*PST$)/m', '#$1', $conf );
		if ( $conf && $org != $conf ) {
			file_put_contents( $location_php_lua, $conf );
			$restart_nginx = true;
		}
	} else {
		wexal_pst_utils::print_error( $location_php_lua );
	}

	if ( $restart_nginx ) {
		$ret = wexal_pst_utils::restart_service_on_enabled('nginx');
	}

	$restart_httpd = false;
	$virtualhost_common = "{$this->httpd_pst_conf_dir}/virtualhost_common.conf";
	if ( file_exists( $virtualhost_common ) ) {
		$conf = file_get_contents( $virtualhost_common );
		$org = $conf;
		$conf = preg_replace ( '/# SetEnv no-gzip/', 'SetEnv no-gzip', $conf );
		if ( $conf && $org != $conf ) {
			file_put_contents( $virtualhost_common, $conf );
			$restart_httpd = true;
		}
	} else {
		wexal_pst_utils::print_error( $virtualhost_common );
	}
	
	if ( $restart_httpd ) {
		$ret = wexal_pst_utils::restart_service_on_enabled('httpd');
	}
}

function get_exclude( $ret ) {
	$exclude = '(' . join( '|', array_merge( $this->config['global_exclude'], $this->config['watch_additional_exclude'] ) ) . ')';
	if ( ! $ret ) {
		echo $exclude;
	} else {
		return $exclude;
	}
}

function get_tdir( $ret ) {
	$tdir = $this->config['tdir'];
	if ( ! $ret ) {
		echo $tdir;
	} else {
		return $tdir;
	}
}

function get_pst( $ret ) {
	$pst = $this->config['pst'];
	if ( ! $ret ) {
		echo $pst;
	} else {
		return $pst;
	}
}

function get_editor( $ret ) {
	$str = $this->config['conf']['editor'];
	if ( ! $ret ) {
		echo $str;
	} else {
		return $str;
	}
}

function get_format( $ret ) {
	$str = $this->config['conf']['format'];
	if ( ! $ret ) {
		echo $str;
	} else {
		return $str;
	}
}

function get_prun( $ret ) {
	$prun = 0;
	$cmd = 'cat /proc/cpuinfo';
	$out = shell_exec( $cmd );
	$out = mb_split( "\n", $out );
	foreach ( $out as $line ) {
		if ( preg_match( '#^processor\s+:\s+#', $line ) ) {
			$prun++;
		}
	}
	if ( ! $ret ) {
		echo $prun;
	} else {
		return $prun;
	}
}

function set_config() {
	if ( ! is_file( $this->wexal_dir . '/pst.config.yaml' ) ) {
		$this->config = array( 'pst' => 'off' );
		return;
	}
	$yaml = yaml_parse_file( $this->wexal_dir . '/pst.config.yaml' );

	$optimize_dir = '_wexal';
	$profile_dir = dirname( dirname( dirname( __FILE__ ) ) );
	require( $profile_dir . '/wexal/page_speed_technology/default.php' );
	$yaml = array_merge( $default, $yaml );

	$this->config = $yaml;
}

function put_config() {
	$file = $this->wexal_dir . '/pst.config.yaml';
	$yaml = yaml_emit_file( $file, $this->config );
	chown( $file, 'kusanagi' );
	chgrp( $file, 'kusanagi' );
}

function watch( $flag ) {
	if ( $flag == 'off' ) {
		$cmd = "pgrep -f 'inotifywait {$this->config['tdir']}'";
		$out = shell_exec( $cmd );
		$out = mb_split( "\n", $out );
		foreach ( $out as $line ) {
			if ( is_numeric( $line ) ) {
				$cmd = "kill -KILL $line";
				echo $cmd . "\n";
				shell_exec( $cmd );
			}
		}
	} elseif ( $flag == 'on' ) {
		$cmd = "pst watch on";
		$out = shell_exec( $cmd );
		echo $out;
	} elseif ( $flag == 'status' ) {
		$cmd = "pgrep -f -l 'inotifywait {$this->config['tdir']}'";
		$out = shell_exec( $cmd );
		if ( $out ) {
			echo "on";
		} else {
			echo "off";
		}
	}
}

function runner( $flag ) {
	if ( $flag == 'off' ) {
		$cmd = "pgrep -f 'runner.php {$this->config['tdir']}'";
		$out = shell_exec( $cmd );
		$out = mb_split( "\n", $out );
		foreach ( $out as $line ) {
			if ( is_numeric( $line ) ) {
				$cmd = "kill -KILL $line";
				echo $cmd . "\n";
				shell_exec( $cmd );
			}
		}
	} elseif ( $flag == 'on' ) {
		$cmd = "pst runner on";
		$out = shell_exec( $cmd );
		echo $out;
	} elseif ( $flag == 'status' ) {
		$cmd = "pgrep -f -l 'runner.php {$this->config['tdir']}'";
		$out = shell_exec( $cmd );
		if ( $out ) {
			echo "on";
		} else {
			echo "off";
		}
	}
}

function status() {
	echo "WEXAL Page Speed Technology = " . $this->config['pst'] . "\n";
	$cmd = "pgrep -f -l 'inotifywait {$this->config['tdir']}'";
	$out = shell_exec( $cmd );
	echo $out;
	$cmd = "pgrep -f -l 'watch.php {$this->config['tdir']}'";
	$out = shell_exec( $cmd );
	if ( $out ) {
		$out = preg_replace( '#php7#', 'watch', $out );
	}
	echo $out;
	$cmd = "pgrep -f -l 'runner.php {$this->config['tdir']}'";
	$out = shell_exec( $cmd );
	if ( $out ) {
		$out = preg_replace( '#php7#', 'runner', $out );
	}
	echo $out;
}

function bcache( $flag ) {
	if ( 'status' == $flag ) {
		if ( is_file( $this->DocumentRoot . '/wp-config.php' ) ) {
			$wpconfig = $this->DocumentRoot . '/wp-config.php';
		} else if ( is_file( dirname( $this->DocumentRoot ) . '/wp-config.php' ) ) {
			$wpconfig = dirname( $this->DocumentRoot ) . '/wp-config.php';
		}
		$conf = file_get_contents( $wpconfig );
		if ( preg_match( '/^\s*define\s*\(\s*\'WP_CACHE\'/m', $conf ) ) {
			echo 'on';
		} else {
			echo 'off';
		}
	}
}

function fcache( $flag ) {
	if ( 'status' == $flag ) {
		$conf = file_get_contents( $this->nginx_http_conf );
		if ( preg_match( '/set\s*\$do_not_cache\s*0\s*;\s*##\s*page\s*cache/', $conf ) ) {
			echo 'on';
		} else {
			echo 'off';
		}
	} elseif ( 'fqdn' == $flag ) {
		$conf = file_get_contents( $this->nginx_http_conf );
		if ( preg_match( '/^[\s\t]+server_name[\s\t]+([^\s\t;]+);/m', $conf, $matches ) ) {
			echo "$matches[1]";
		}
	}
}

function cron( $flag ) {
	$cron = '/etc/cron.d/pst_' . $this->profile;

	if ( $flag == 'off' ) {
		if ( is_file( $cron ) ) {
			unlink( $cron );
		}
	} elseif ( $flag == 'on' ) {
		if ( ! is_file( $cron ) ) {
			$crontab = <<< "EOM"
* * * * * root (cd /home/kusanagi/$this->profile && pst cron run)

EOM;
			file_put_contents( $cron, $crontab );
		}
	} elseif ( $flag == 'status' ) {
		if ( is_file( $cron ) ) {
			echo "on";
		} else {
			echo "off";
		}
	}
}

function version( $ver ) {
	echo "Global : Version $ver\nProfile: Version $this->version\n";
}

function help() {
	require_once( dirname( __FILE__ ) . '/help.php' );
}

} // end class

$wexal_pst_control = new wexal_pst_control();

