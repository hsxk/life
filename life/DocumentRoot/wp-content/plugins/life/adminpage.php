<?php 
/*
Plugin Name: Life
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
    
    <div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php settings_errors(); ?>
	<form id="life-settings" action="options.php" method="post">
	<?php
	if ( current_user_can( 'manage_options' ) ) {
	    settings_fields('life-group');
		do_settings_sections('life-group');
	    submit_button();
	}
	?>
	<form>
	</div>
	<?php
}
function life_admin_init(){
register_setting( 'life-group', 'life_options' );
add_settings_section('webp_ID', 'Allow uploading webp images', 'webp_radio', 'life-group');
}
add_action('admin_init','life_admin_init');
function webp_radio(){
echo 'on:<input type="radio" name="'.__FUNCTION__.'"/>off:<input type="radio" name="'.__FUNCTION__.'"/>';
}

 
?>
