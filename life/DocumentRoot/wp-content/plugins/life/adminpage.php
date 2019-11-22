<?php 
/*
Plugin Name: Life
Description:LIFEのために開発したプラグイン
Version: 0.2
Author: haokexin
Author URI: https://hkx.monster
*/

/*----------------------------------------
          インクルードファイル
-----------------------------------------*/
include_once "life.php";
include_once "control.php";

/*-----------------------------------------
          管理画面メニュー追加
-----------------------------------------*/
function life_plugin_menu(){
   add_menu_page( 'Life Settings',
                  'Life',
				  'manage_options', 
				  'life_plugin',
				  'life_plugin_page',
				  '../wp-content/plugins/life/panda.png',
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
    <link rel="stylesheet" href="<?php echo WP_PLUGIN_URL ?>/life/style.css" type="text/css">
    <script src="<?php echo WP_PLUGIN_URL ?>/life/common.js"></script>
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
register_setting( 'life-group', 'life_options_webp', array('type' => int,'default'=>'0' ) );
register_setting( 'life-group', 'life_options_exif', array('type' => int,'default'=>'0' ) );
register_setting( 'life-group', 'life_options_title', array('type' => int,'default'=>'0' ) );
register_setting( 'life-group', 'life_options_updates', array('type' => int,'default'=>'0'));
register_setting( 'life-group', 'life_options_copy', array('type' => int,'default'=>'0' ) );
add_settings_section('onoffswitchlife_options_webp', 'Checkbox Settings', 'checkbox', 'life-group' );
}
add_action('admin_init','life_admin_init');

/*---------------------------------------
          管理画面内容
---------------------------------------*/
function checkbox(){
    $exif = get_option('life_options_exif');
	$title = get_option('life_options_title');
    $updates = get_option('life_options_updates');
	$copy= get_option('life_options_copy');
    $webp = get_option('life_options_webp');
    echo control_switch_block( "life_options_webp", $webp, 'Allow uploading webp images' );
	echo control_switch_block( "life_options_exif", $exif, 'Get exif from image' );
	echo control_switch_block( "life_options_title", $title, 'Post\'s title must' );
	echo control_switch_block( "life_options_updates", $updates, 'Remove updates' );
    echo control_switch_block( "life_options_copy", $copy, 'Disable f12,copy,paste but except managers' );
}
?>
