<?php

class wexal_pst_help {

function __construct( $pst=false ) {
	$this->pst = $pst;
	$this->help();
}

function help() {
$profile_dir = dirname( $this->pst->wexal_dir );

$sec1 = <<< EOM
------ directory tree -----
$profile_dir/
     ├─ DocumentRoot/wp-content/mu-plugins/pst/
     ├─ log/pst/
     └─ wexal/
          ├─ config_sample/
          ├─ optdir/
          ├─ page_speed_technology/
          ├─ pst.config.yaml
          ├─ tmpdir/
          └─ userdir/

------ pst ------
init       [--http] [--rebuildconf] [--plugin]
make_config|make
edit_config|edit
on
off
watch      [on|off|restart]
out        [on|off]
lighthouse [url]
RPA        [screenshot|linklist] [url]
AI         [config]
opt        [dir|file] [--force]
  ex) opt /wp-content/uploads --force
opt_image  [dir|file] [--force]
opt_js     [dir|file] [--force]
opt_css    [dir|file] [--force]
uncss      [dir|file] [--force]
bcache     [clear]
fcache     [clear]
cron       [on|off]
purge      [dir]
-v|--version|version
-h|--help|help

----- lua directive (fcache) -----
enable: [1=true, 0=false]
exptime: [seconds]

ex) default value
enable: 1
exptime: 60


EOM;
echo $sec1;

$str = file_get_contents( dirname( __FILE__ ) . '/lib_for_lua.lua' );
preg_match_all( '#lib.(header|body)_filter_([^\s]+?)\s*=\s*function#', $str, $m );

$d = $m[1];
$header = array();
$body = array();
foreach ( $d as $key => $line ) {
	if ( 'header' == $line ) {
		$header[] = preg_replace( '#_#', ' ', $m[2][$key] );
	} else {
		$body[] = preg_replace( '#_#', ' ', $m[2][$key] );
	}
}

echo "----- lua directive (header_filter) -----\n";
echo join( "\n", $header ) ."\n\n";

echo "----- lua directive (body_filter) -----\n";
echo join( "\n", $body ) . "\n\n";

echo "----- wp directive (general) -----\n";

$str = file_get_contents( dirname( __FILE__ ) . '/lib_for_wp.php' );
preg_match_all( '#function wexal_(.*)\(#', $str, $m );

$d = $m[1];
$general = array();
$flush = array();
foreach ( $d as $line ) {
	if ( preg_match( '#^flush_#', $line ) ) {
		$line = preg_replace( '#^flush_#', '', $line );
		$line = preg_replace( '#_#', ' ', $line );
		$flush[] = $line;
	} else {
		$line = preg_replace( '#_#', ' ', $line );
		$general[] = $line;
	}
}

echo join( "\n", $general ) . "\n\n------ wp directive (flush) -----\n";
echo join( "\n", $flush ) . "\n";


} // function end

} // class end
new wexal_pst_help( $this );
