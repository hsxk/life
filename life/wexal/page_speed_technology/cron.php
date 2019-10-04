<?php

class wexal_cron {

public $timestamp;
public $pst;
public $worker;

function __construct( $pst ) {
	global $argv;
	array_shift( $argv );
	$this->timestamp = time();
	$this->pst = $pst;
	if ( isset( $pst->config['timezone'] ) ) {
		date_default_timezone_set( $pst->config['timezone'] );
	}

	if ( ! isset( $pst->config['cron'] ) || ! is_array( $pst->config['cron'] ) ) { return false; };

	// default setting
	$cron = $pst->config['cron'];
	$def = array( 'job' => array() );
	$cron = array_merge( $def, $cron );

	$def = array(
		'cmd'      => '',
		'args'     => array(),
		'schedule' => array( '*', '*', '*', '*', '*' )	// TODO
	);
	if ( is_array( $cron['job'] ) ) {
		foreach ( $cron['job'] as $key => $val ) {
			if ( is_array( $val ) ) {
				$cron['job'][ $key ] = array_merge( $def, $cron['job'][ $key ] );
			}
		}
	}

	foreach ( $cron['job'] as $key => $val ) {
		if ( $this->is_schedule( $val['schedule'] ) ) {
			$this->dispatch( $val );
		}
	}
}

function dispatch( $arr ) {
	$method = $arr['cmd'];
	$param = $arr['args'];

	if ( in_array( $method, array( 'opt_js', 'opt_css', 'opt_image', 'opt', 'uncss' ) ) ) {
		array_unshift( $param, $method );
		require_once( dirname( __FILE__ ) .'/worker.php' );
		if ( is_file( $this->pst->userdir . '/worker.php' ) ) {
			require_once( $this->pst->userdir .'/worker.php' );
			$this->worker = new user_wexal_pst_worker( $this->pst, $param );
		} else {
			$this->worker = new wexal_pst_worker( $this->pst, $param );
		}
	} elseif ( 'bcache' == $method && 'clear' == $param[0] ) {
		$cmd = 'pst bcache clear';
		$out = shell_exec( $cmd );
		if ( $out ) {
			$this->log( $out );	
		}
	} elseif ( 'fcache' == $method && 'clear' == $param[0] ) {
		$cmd = 'pst fcache clear';
		$out = shell_exec( $cmd );
		if ( $out ) {
			$this->log( $out );	
		}
	}
}

function is_schedule( $schedule ) {
	$d = getdate( $this->timestamp );
	$date = array( $d['minutes'], $d['hours'], $d['mday'], $d['mon'], $d['wday'] );

	$ret = true;
	foreach ( $schedule as $key => $val ) {
		// '*'
		if ( preg_match( '#^\*$#', $val ) ) {
			continue;
		// '0'
		} elseif ( preg_match( '#^\d+$#', $val ) ) {
			if ( intval( $date[ $key ] ) == intval( $val ) ) {
				continue;
			} else {
				$ret = false;
				break;
			}
		} elseif ( preg_match( '#^\d+(,\d+)+$#', $val ) ) {
			$matches = preg_split( '#,#', $val );
			foreach ( $matches as $match ) {
				if ( intval( $date[ $key ] ) == intval( $match ) ) {
					continue 2;
				}
			}
			$ret = false;
			break;
		} elseif ( preg_match( '#^(\d+)-(\d+)$#', $val, $matches ) ) {
			if ( intval( $date[ $key ] ) >= intval( $matches[1] ) && intval( $date[ $key ] ) <= intval( $matches[2] ) ) {
				continue;
			} else {
				$ret = false;
				break;
			}
		} elseif ( preg_match( '#^\*/(\d+)$#', $val, $matches ) ) {
			if ( 0 == intval( $date[ $key ] ) % intval( $matches[1] ) ) {
				continue;
			} else {
				$ret = false;
				break;
			}
		} else {
			$ret = false;
			break;
		}
	}
	return $ret;
}

function log( $log ) {
	$logfile = $this->pst->logdir . '/page_speed_technology.log';
	error_log( 'cron ' . date( "Y-m-d H:i:s" ) . ' ' . $log . "\n", 3, $logfile );
}

} // end class

require_once( dirname( __FILE__ ) . '/pst.control.php' );

new wexal_cron ( $wexal_pst_control );

