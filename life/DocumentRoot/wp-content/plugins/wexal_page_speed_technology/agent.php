<?php
/*
 Plugin Name: WEXAL Page Speed Technology (PST)
 Plugin URI: http://www.wexal.jp/
 Description: Speed up your WordPress.
 Author: Prime Strategy Co.,LTD.
 Version: 1.1.8
 Author URI: https://www.prime-strategy.co.jp/
 License: GPLv2 or later
*/

class wexal_page_speed_techonology_wp_agent {

public $version;

function __construct() {
	$this->version = get_file_data( __FILE__, array( 'version' => 'Version' ) );
	register_activation_hook( __FILE__, array( $this, 'plugin_activate' ) );
	add_action( 'admin_notices', array( $this, 'message' ) ); 	
	add_action( 'admin_menu', array( $this, 'admin_menu' ) ); 	
}

function plugin_activate() {
	global $wp_filesystem;

	if ( file_exists( '/usr/bin/pst' ) && file_exists( WPMU_PLUGIN_DIR .'/wexal_page_speed_technology_for_wp.php' ) ) {
		return;
	}
	ob_start();
	$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array());
	ob_end_clean();
	if ( false === $creds || ! WP_Filesystem( $creds) ) { 
		if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
			echo esc_html( $wp_filesystem->errors->get_error_message() );
		} else {
			_e( 'Unable to connect to the filesystem. Please confirm your credentials.' );
		}
		exit;
	}
	$f = $wp_filesystem;
	$template = plugin_dir_path( __FILE__ ) . 'template/mu-plugins';
	if ( ! $f->is_dir( WPMU_PLUGIN_DIR ) && ! $f->mkdir( WPMU_PLUGIN_DIR ) ) {
		echo __( 'Could not create directory.' ) . WPMU_PLUGIN_DIR;
		exit;
	}
	$this->copy_dir( WPMU_PLUGIN_DIR, $template, $f );
}

function copy_dir( $target, $source, $f ) {
	$d = dir( $source );
	while ( false !== ( $e = $d->read() ) ) {
		if ( $e == '.' || $e == '..' ) {
			continue;
		}
		$s = $source . '/' . $e;
		$t = $target . '/' . $e;
		if ( is_dir( $s ) ) {
			if ( ! $f->is_dir( $t ) && ! $f->mkdir( $t ) ) {
				echo __( 'Could not create directory.' ) . $t;
				exit;
			}
			$this->copy_dir( $t, $s, $f );
		} elseif ( is_file( $s ) ) {
			$f->copy( $s, $t, true);
		}
	}
	$d->close();
}

function message() {

}

function admin_menu() {
	add_menu_page( 'WEXAL Page Speed Technology', 'WEXAL Page Speed Technology', 'administrator', __FILE__, array( $this, 'menu_page' ), '', '80.012' );
	add_action( 'admin_init', array( $this, 'settings' ) );
//	wp_enqueue_script( 'jquery-ui-tabs' );
	wp_enqueue_style( 'jsoneditor', 'https://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css' );
	wp_enqueue_script( 'jsoneditor', plugins_url( '/core/js/jsoneditor.min.js', __FILE__ ) );
}

function settings() {
	register_setting( 'wexal_pst', 'wexal_pst_pst', array( $this, 'pst_call_back') );
	register_setting( 'wexal_pst', 'wexal_pst_conf_wp_stg', array( $this, 'conf_call_back' ) );
}

function pst_call_back( $str ) {
	if ( 'on' !== $str ) {
		$str = 'off';
	}
	return $str;
}

function conf_call_back( $str ) {
	if ( isset( $_POST['wexal_pst_wp2prod'] ) && 'prod' == $_POST['wexal_pst_wp2prod'] ) {
		$tmp = json_decode( $str, true );
		if ( is_array( $tmp ) && false != $tmp	) {
			update_option( 'wexal_pst_conf_wp_prod', $tmp );
		}
	}
	return $str;
}



function menu_page() {
	$pst  = get_option( 'wexal_pst_pst' );
	$conf_wp_stg = get_option( 'wexal_pst_conf_wp_stg' );
	$conf_wp_prod = get_option( 'wexal_pst_conf_wp_prod' );
//	var_dump( $conf_wp_prod );exit;
	$def = array(
		'wexal_init' => array(),
		'wexal_enqueue_opt' => array(),
		'wexal_head' => array(),
		'wexal_footer' => array(),
		'wexal_flush' => array(),
	);
	if ( false == $conf_wp_stg ) {

		$conf_wp_stg = json_encode( $def, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES );
	}
	if ( $conf_wp_prod ) {
		$conf_wp_prod = json_encode( $conf_wp_prod, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES );
	}

?>
<div class="wrap">
    <h2>WEXAL Page Speed Technology</h2>
    <form method="post" action="options.php">
	<?php settings_fields( 'wexal_pst' ); ?>
	<?php do_settings_sections( 'wexal_pst' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Page Speed Technology</th>
				<td>
					<input type="radio" name="wexal_pst_pst" value="on" <?php if ( 'on' == $pst ) { echo 'checked="checked"'; } ?>/>on
					<input type="radio" name="wexal_pst_pst" value="off" <?php if ( 'on' != $pst ) { echo 'checked="checked"'; } ?>/>off
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">wp directive builder</th>
				<td>
					<div id='editor_holder'></div>
					<button type="button" id='submit1'>Submit (generate config)</button>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">wp directive (staging)</th>
				<td>
					<textarea readonly="readonly" id="wexal_pst_conf_wp_stg" name="wexal_pst_conf_wp_stg" cols=120 rows=20><?php echo esc_textarea( $conf_wp_stg );?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">wp directive (production)</th>
				<td>
					<textarea readonly="readonly" id="wexal_pst_conf_wp_prod" name="wexal_pst_conf_wp_prod" cols=120 rows=20><?php echo esc_textarea( $conf_wp_prod );?></textarea><br />
					<input type="checkbox" name="wexal_pst_wp2prod" value="prod">apply wp directive staging to produciton
				</td>
			</tr>
		</table>
   		<?php submit_button(); ?>
    </form>
</div>

<script>
<?php require_once( plugin_dir_path( __FILE__ ) . 'core/js/wp_directive.js' ); ?>
</script>


<?php
}

} // class end

$wexal_page_speed_techonology_wp_agent = new wexal_page_speed_techonology_wp_agent();

