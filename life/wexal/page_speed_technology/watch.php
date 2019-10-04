<?php

class wexal_pst_watch {

public $pst;
public $worker;

function __construct( $pst ) {
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

	while ( ! feof( STDIN ) ) {
		$line = trim( fgets( STDIN ) );
		$arr = mb_split( '__wexal__' , $line );
		if ( 4 == count( $arr ) ) {
			list( $time, $path, $event, $cookie ) = $arr;
			$log = join ( ' ', $arr );
			error_log( 'watch ' . $log . "\n", 3, $logfile );
			$this->dispatch( $arr );
		} else {
			$line = date( "Y-m-d H:i:s" ) . ' ' . $line;
			error_log( 'watch ' . $line . "\n", 3, $logfile );
		}
	}

	$line = date( "Y-m-d H:i:s" ) . ' Watches shutdown.';
	error_log( 'watch ' . $line . "\n", 3, $logfile );
} // end main

function dispatch( $arr=array() ) {
	list( $time, $path, $event, $cookie ) = $arr;

	if ( 'CLOSE_WRITE,CLOSE' == $event ) {
		$this->worker->opt_file( $path );
	} elseif ( 'DELETE,ISDIR' == $event || 'DELETE' == $event) {
		$this->worker->delete_cache( $path );
	} elseif ( 'MOVED_FROM' == $event || 'MOVED_FROM,ISDIR' == $event ) {
		$this->worker->moved_from( $path, $event, $cookie );
	} elseif ( 'MOVED_TO' == $event || 'MOVED_TO,ISDIR' == $event ) {
		$this->worker->moved_to( $path, $event, $cookie );
	} 
} // end dispatch

} // end class

require_once( dirname( __FILE__ ) . '/pst.control.php' );

new wexal_pst_watch( $wexal_pst_control );

