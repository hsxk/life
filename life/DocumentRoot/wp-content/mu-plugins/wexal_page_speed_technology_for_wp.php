<?php

class wexal_page_speed_technology_for_wp {

public $pst;
public $lib;
public $default;

function __construct() {
	$pst = '';
	if ( isset( $_GET['pst'] ) ) {
		$pst = strtolower( $_GET['pst'] );
	} elseif ( isset( $_SERVER['HTTP_X_PST_CONFIGNAME'] ) ) { //GET and HEADER Priority to be fixed.
		$pst = strtolower( $_SERVER['HTTP_X_PST_CONFIGNAME'] );
	}
	if ( 'off' == $pst ) {
		return;
	}
	if ( isset( $_SERVER['HTTP_X_PST_DISABLE'] ) && 'on' == strtolower( $_SERVER['HTTP_X_PST_DISABLE'] ) ) {
		return;
	}
	foreach ( headers_list() as $header ) {
		if ( preg_match( '#^content-type:#i', $header ) ) {
			if ( ! preg_match( '#text/html#', $header ) ) {
				return;
			}
			break;
		}
	}
	$plugin_dir = WP_PLUGIN_DIR . '/wexal_page_speed_technology';
	$plugin = 'wexal_page_speed_technology/agent.php';
	$path = $plugin_dir . '/core/pst.control.php';
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_file( $path ) && is_plugin_active( $plugin ) ) {
		require_once( $path );
	} else {
		$path = preg_replace( '#^(/home/kusanagi/.*?)/.*$#', '$1', __FILE__ );
		$path .= '/wexal/page_speed_technology/pst.control.php';
		if ( is_file( $path ) ) {
			require_once( $path );
		} else {
			return;
		}
	}
	$this->pst = $wexal_pst_control;
	$conf = $this->pst->config;
	if ( ! in_array( 'wp', $conf['options'] ) ) {
		return;
	}
	if ( 'on' != $conf['pst'] && 'stg' != $pst ) {
		return;
	}

	$this->default = array(
		'type' => 'method',
		'cmd' => '',
		'apply' => array( 'path' => array( '.' ), 'if' => array() ),
		'exclude' => array( 'path' => array(), 'if' => array() ),
		'args' => array(),
	);

	add_action( 'plugins_loaded', array( $this, 'wexal_init' ), -11 );
}

function wexal_init() {
	$global_exclude = '#(' . join( '|', $this->pst->config['global_exclude']) . ')#';
 	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	if ( preg_match( '#\?[^\?]+$#', $request_uri ) ) {
		$request_uri = preg_replace( '#\?[^\?]+$#', '', $request_uri );
	}
	if ( preg_match( $global_exclude, $request_uri ) ) {
		return;
	}
	if ( in_array( 'apply_logged_in_user' ,$this->pst->config['options'] ) || ! is_user_logged_in() ) {
		require_once( $this->pst->pst_dir . '/lib_for_wp.php' );
		if ( is_file( $this->pst->userdir . '/lib_for_wp.php' ) ) {
			require_once( $this->pst->userdir . '/lib_for_wp.php' );
			$this->lib = new user_wexal_page_speed_technology_lib_for_wp( $this->pst );
		} else {
			$this->lib = new wexal_page_speed_technology_lib_for_wp( $this->pst );
		}

		if ( isset( $this->pst->config['overrides'] ) ) {
			foreach ( $this->pst->config['overrides'] as $key => $val ) {
				$default = array(
					'device' => '',
					'path' => '.',
					'if' => false,
					'node' => array(),
					'applied' => array(
						'wp' => array()
					),
				);
				$val = array_merge( $default, $val );
				if ( ! in_array( 'wp', $val['node'] ) ) {
					continue;
				}
				$is_device = false;
				if ( 'mobile' == $val['device'] && is_kusanagi_mobile() ) {
					$is_device = true;
				}
				if ( 'tablet' == $val['device'] && is_kusanagi_tablet() ) {
					$is_device = true;
				}
				if ( 'pc' == $val['device'] && is_kusanagi_pc() ) {
					$is_device = true;
				}
				$is_path = false;
				if ( @preg_match( '#' . $val['path'] . '#', $request_uri ) ) {
					$is_path = true;
				}
				$is_if = false;
				if ( 1 == $val['if'] ) {
					$is_if = true;
				}
				if ( $is_device && $is_path && $is_if && $val['applied']['wp']) {
					$this->pst->config['wp'] = $val['applied']['wp'];
					break;
				}
			}
		}

		$default = array(
			'wexal_init' => array(),
			'wexal_head' => array(),
			'wexal_enqueue_opt' => array(),
			'wexal_footer' => array(),
			'wexal_flush' => array(),
		);

		if ( ! isset( $this->pst->config['wp'] ) || ! is_array( $this->pst->config['wp'] ) ) {
			$this->pst->config['wp'] = array();
		}
		$this->pst->config['wp'] = array_merge( $default, $this->pst->config['wp'] );
		$ob = $this->lib->get_ob_params();
		add_action( $ob['event_ob_start'], array( $this, 'wexal_ob_start'), $ob['priority_ob_start'] );
		add_action( 'init', array( $this, 'wexal_wp_init'), -1 );
		add_action( 'wp_head', array( $this, 'wexal_wp_head'), 10 );
		add_action( 'wp_footer', array( $this, 'wexal_footer'), PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', array( $this, 'wexal_enqueue_opt'), PHP_INT_MAX );
	}
}

function wexal_ob_start() {
	ob_start( array( $this, 'wexal_flush' ) );
}

function wexal_wp_init() {
	$conf = $this->pst->config['wp']['wexal_init'];
	$this->wexal_check_and_do_cmd( $conf );
}

function wexal_wp_head() {
	$conf = $this->pst->config['wp']['wexal_head'];
	$this->wexal_check_and_do_cmd( $conf );
}

function wexal_enqueue_opt() {
	$conf = $this->pst->config['wp']['wexal_enqueue_opt'];
	$this->wexal_check_and_do_cmd( $conf );
}

function wexal_footer() {
	$conf = $this->pst->config['wp']['wexal_footer'];
	$this->wexal_check_and_do_cmd( $conf );
}

function wexal_flush( $str ) {
	$conf = $this->pst->config['wp']['wexal_flush'];
	$this->wexal_check_and_do_cmd( $conf, $str, 'flush_' );
	$opt_js = $this->pst->config['tdir'] . '/' . $this->pst->config['odir'] . '/wp-content/mu-plugins/pst/js/wexal_pst_init.js.opt.js';
	if ( is_file( $opt_js ) ) {
		$js = $opt_js;
	} else {
		$js = WP_CONTENT_DIR . '/mu-plugins/pst/js/wexal_pst_init.js';
	}
	$js = file_get_contents( $js );
	$str = preg_replace( '#<head([^>]*?)>#', "<head$1>\n<script>$js</script>", $str, 1 );
	return $str;
}

function is_apply( $arr ) {
	
	$arr = array_merge( $this->default, $arr );
	$ret = false;
	$apply = $arr['apply'];
	$exclude = $arr['exclude'];

	$uri = $_SERVER['REQUEST_URI'];
	foreach ($apply as $rule => $val) {
		if ( ! is_array( $val ) ) {
			$val = array( $val );
		}
		foreach ( $val as $key => $row ) {
			if ( false == $row && 'if' != $rule ) {
				continue;
			}
			if ( 'path' == $rule ) {
				if ( preg_match ( "#$row#", $uri ) ) {
					$ret = true;
				}
			} elseif ( 'if' == $rule ) {
				if ( is_numeric( $key ) ) {
					$key = $row;
					$row = '';
				}
				$key = mb_split( '\|', $key );
				foreach ( $key as $func ) {
					$func = trim( $func );
					if ( function_exists( $func ) ) {
						if ( $func( $row ) ) {
							$ret = true;
						}
					}
				}
			}
		}
	}

	foreach ($exclude as $rule => $val ) {
		if ( ! is_array( $val ) ) {
			$val = array( $val );
		}
		foreach ( $val as $key => $row ) {
			if ( false == $row && 'if' != $rule ) {
				continue;
			}
			if ( 'path' == $rule ) {
				if ( preg_match ( "#$row#", $uri ) ) {
					$ret = false;
				}
			} elseif ( 'if' == $rule ) {
				if ( is_numeric( $key ) ) {
					$key = $row;
					$row = '';
				}
				$key = mb_split( '\|', $key );
				foreach ( $key as $func ) {
					$func = trim( $func );
					if ( function_exists( $func ) ) {
						if ( $func( $row ) ) {
							$ret = false;
						}
					}
				}
			}
		}
	}

	return $ret;
}

function wexal_check_and_do_cmd( $conf, &$str='', $prefix='' ) {
	if ( ! is_array( $conf ) ) { return; }
	foreach ($conf as $row ) {
		$row = array_merge( $this->default, $row );
		if ( ! $this->is_apply( $row ) ) {	continue; }
		if ( 'method' == $row['type'] ) {
			$method = preg_replace( '/ /', '_', $row['cmd'] );
			$method = 'wexal_' . $prefix . $method;
			$args = $row['args'];
			if ( ! is_array( $args ) ) {
				$args = array( $args );
			}
			if ( method_exists( $this->lib, $method ) ) {
				if ( $prefix ) {
					$this->lib->$method( $str, $args );
				} else {
					$this->lib->$method( $args );
				}
			}
		}
	}
} // function end

} // class end

new wexal_page_speed_technology_for_wp();
