<?php 
/*
Plugin Name: Life
Description:LIFEのために開発したプラグイン
Version: 0.4.0
Author: haokexin
Author URI: https://hkx.monster
*/

/*----------------------------------------
          インクルードファイル
-----------------------------------------*/
include_once "life.php";
include_once "functions.php";

/*----------------------------------------
			plugin用js追加
-----------------------------------------*/
function life_include() {
	if( is_admin() ) {
		wp_enqueue_script( 'life_js', WP_PLUGIN_URL . '/life/assets/js/common.js', array() );
		wp_enqueue_style( 'life_css', WP_PLUGIN_URL . '/life/assets/css/style.css' );
	}
}
add_action( 'admin_enqueue_scripts', 'life_include' );

/*-----------------------------------------
          管理画面メニュー追加
-----------------------------------------*/
function life_plugin_menu(){
   add_menu_page( 'Life Settings',
                  'Life',
				  'manage_options',
				  'life_plugin',
				  'life_plugin_page',
				  WP_PLUGIN_URL.'/life/assets/img/panda.png',
				  '66' );
}
add_action('admin_menu','life_plugin_menu');

/*-----------------------------------------
          管理画面フレーム
-----------------------------------------*/
function life_plugin_page(){
if ( !current_user_can( 'manage_options' ) )  {
   wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
   }
   ?>
	<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php settings_errors();  //更新通知を表示する ?>  
	<form  action="options.php" method="post">
	<?php
		settings_fields('life-group');
		do_settings_sections('life-group');
		submit_button();
	?>
	</form>
	</div>
<?php
}

/*---------------------------------------
            Option登録
---------------------------------------*/
function life_admin_init(){
register_setting( 'life-group', 'life_options_webp', array('default'=>'0' ) );
register_setting( 'life-group', 'life_options_exif', array('default'=>'0' ) );
register_setting( 'life-group', 'life_options_title', array('default'=>'0' ) );
register_setting( 'life-group', 'life_options_updates', array('default'=>'0'));
register_setting( 'life-group', 'life_options_copy', array('default'=>'0' ) );
register_setting( 'life-group', 'life_options_photo', array('default'=>'0' ) );
register_setting( 'life-group', 'life_options_size', array('default'=>'0' ) );
register_setting( 'life-group', 'life_options_adminbar', array('default'=>'0' ) );
register_setting( 'life-group', 'life_options_post_branch', array('default'=>'0' ) );
add_settings_section('life_plugin_options', 'Checkbox Settings', 'checkbox', 'life-group' );
}
add_action('admin_init','life_admin_init');

/*---------------------------------------
          管理画面内容
---------------------------------------*/
function checkbox(){
	$exif = get_option('life_options_exif');
	$title = get_option('life_options_title');
	$updates = get_option('life_options_updates');
	$copy = get_option('life_options_copy');
	$webp = get_option('life_options_webp');
	$photo = get_option('life_options_photo');
	$size = get_option('life_options_size');
	$adminbar = get_option('life_options_adminbar');
	$postbranch = get_option('life_options_post_branch');
	echo control_switch_block( "life_options_webp", $webp, 'Allow uploading webp images' );
	echo control_switch_block( "life_options_exif", $exif, 'Get exif from image' );
	echo control_switch_block( "life_options_title", $title, 'Post\'s title must' );
	echo control_switch_block( "life_options_updates", $updates, 'Remove updates' );
	echo control_switch_block( "life_options_copy", $copy, 'Disable f12,copy,paste but except managers' );
	echo control_switch_block( "life_options_photo", $photo, 'Allow originalsize photo upload' );
	echo control_switch_block( "life_options_size", $size, 'Show all image sizes' );
	echo control_switch_block( "life_options_adminbar", $adminbar, 'Remove admin bar' );
	echo control_switch_block( "life_options_post_branch", $postbranch, 'Post branch' );
}
?>
