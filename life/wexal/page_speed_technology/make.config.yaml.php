<?php

class wexal_make_config_yaml {

function __construct( $pst ) {

$this->pst = $pst;
$optimize_dir = '_wexal';
$profile_dir = dirname( dirname( dirname( __FILE__ ) ) );

require( $profile_dir . '/wexal/page_speed_technology/default.php' );

$arr = $this->config();
$arr = array_merge( $default, $arr );
if ( ! in_array( "/$optimize_dir", $arr['global_exclude'] ) ) {
	array_unshift( $arr['global_exclude'],  "/$optimize_dir" );
}

$pst->config = $arr;
$pst->put_config();

} 

function config() {

$conf = yaml_parse_file( $this->pst->userdir . '/pst.config.yaml' );

if ( ! is_array ( $conf ) ) {
	echo "Error: userdir/pst.config.yaml is not correct.\n";
	exit;
}

return $conf;
} // function end

} // class end
