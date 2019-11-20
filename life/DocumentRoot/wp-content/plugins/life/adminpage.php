<?php 
/*
Plugin Name: Life
Version: 0.2
Author: haokexin
Author URI: https://hkx.monster
*/
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

function life_plugin_page(){
if ( !current_user_can( 'manage_options' ) )  {
   wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
   }
   ?>
    <link rel="stylesheet" href="<?php echo WP_PLUGIN_URL ?>/life/style.css" type="text/css">
    <script src="<?php echo WP_PLUGIN_URL ?>/life/common.js"></script>
    <div class="wrap">
	<?php if ( current_user_can( 'manage_options' ) ): ?>
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php settings_errors(); ?>
	<form  action="options.php" method="post">
	<?php
	    settings_fields('life-group');
		do_settings_sections('life-group');
	    submit_button();
	?>
	</form>
	</div>
	<?php
	else: 
	    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	?>
	</form>
	</div>
	<?php endif;} ?>

<?php
$switch =  '<div class="testswitch">
            <input class="testswitch-checkbox" id="onoffswitch" type="checkbox">
            <label class="testswitch-label" for="onoffswitch">
                <span class="testswitch-inner" data-on="ON" data-off="OFF"></span>
                <span class="testswitch-switch"></span>
            </label>
            </div>
            <br><br>';
function life_admin_init(){
$settings = array( webp => false,
                   exif => false,
                   title => false,
                   updates => false,);
register_setting( 'life-group', 'life_options_webp', array('type' => int,'default'=>'0'));
register_setting( 'life-group', 'life_options_exif', array('type' => int,'default'=>'0'));
register_setting( 'life-group', 'life_options_title', array('type' => int,'default'=>'0'));
register_setting( 'life-group', 'life_options_updates', array('type' => int,'default'=>'0'));
add_settings_section('webp_ID', 'Allow uploading webp images', 'webp_radio', 'life-group');
add_settings_section('exif_ID', 'Get exif from image', 'exif_radio', 'life-group');
add_settings_section('title_ID', 'Post\'s title must', 'title_radio', 'life-group');
add_settings_section('updates_ID', 'Remove updates', 'updates_radio', 'life-group');
}
add_action('admin_init','life_admin_init');
function switchbutton($option_name,$option_value){
    if(!$option_value){           
        $checkbox='<input class="testswitch-checkbox" name="'.$option_name.'" id="onoffswitch'.$option_name.'"  type="checkbox" value="0">';}
    else{      
        $checkbox='<input class="testswitch-checkbox" name="'.$option_name.'" id="onoffswitch'.$option_name.'" checked="checked"  type="checkbox" value="1">';}
        $switch='<div class="testswitch">'.$checkbox.'<label class="testswitch-label" for="onoffswitch'.$option_name.'"><span class="testswitch-inner" data-on="ON" data-off="OFF"></span><span class="testswitch-switch"></span></label></div><br><br>';
        return $switch;
}
function webp_radio(){
    $webp = get_option('life_options_webp');
    echo switchbutton("life_options_webp",$webp);
}
function exif_radio(){
    $exif = get_option('life_options_exif');
    echo switchbutton("life_options_exif",$exif);
}
function title_radio(){
    $title = get_option('life_options_title');
    echo switchbutton("life_options_title",$title);
}
function updates_radio(){
    $updates = get_option('life_options_updates');
    echo switchbutton("life_options_updates",$updates);
}
 
?>
