<?php

class wexal_pst_runner {

public $pst;
public $worker;
public $index;

function __construct( $pst ) {
	global $argv;
	array_shift( $argv );	// runner.php
	array_shift( $argv );	// wdir
	$index = array_shift( $argv );
	if ( is_numeric( $index ) ) {
		$this->index = $index;
	}
	else {
		$this->index = 0;
	}
	$this->pst = $pst;
	if ( isset( $pst->config['timezone'] ) ) {
		date_default_timezone_set( $pst->config['timezone'] );
	}
	require_once( dirname( __FILE__ ) .'/worker.php' );
	if ( is_file( $this->pst->userdir . '/worker.php' ) ) {
		require_once( $this->pst->userdir .'/worker.php' );
		$this->worker = new user_wexal_pst_worker( $pst );
	} else {
		$this->worker = new wexal_pst_worker( $pst );
	}

	$this->main();
}

function main() {
	$logfile = $this->pst->logdir . '/page_speed_technology.log'; 
	$loop = true;

	$line = date( "Y-m-d H:i:s" ) . ' Runner established.';
	error_log( 'runner[' . $this->index . '] ' . $line . "\n", 3, $logfile );

	while ( $loop ) {
		$loop = $this->worker->opt_file_wait();
		$line = date( "Y-m-d H:i:s" ) . ' ' . $loop;
		error_log( 'runner[' . $this->index . '] ' . $line . "\n", 3, $logfile );
		if ( false == $loop ) {
			break;
		}
	}

	$line = date( "Y-m-d H:i:s" ) . ' Runner shutdown.';
	error_log( 'runner[' . $this->index . '] ' . $line . "\n", 3, $logfile );
} // end main

} // end class

require_once( dirname( __FILE__ ) . '/pst.control.php' );

new wexal_pst_runner( $wexal_pst_control );

