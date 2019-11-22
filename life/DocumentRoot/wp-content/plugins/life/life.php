<?php 
/*-------------------------------------------------------------------------------
                                    Webp
-------------------------------------------------------------------------------*/
if(get_option('life_options_webp')){
/*---------------------------Allow upload--------------------------------------*/
function add_webp_upload( $array ) {
	$array['webp'] = 'image/webp';
		return $array; 
}
add_filter( 'mime_types', 'add_webp_upload', 10, 1 );
/*--------------------------Show webp in medialist-----------------------------*/
function media_show_webp($result, $path) {
	$info = @getimagesize( $path );
		if($info['mime'] == 'image/webp') {
				$result = true;
		}
	return $result;
}
add_filter( 'file_is_displayable_image', 'media_show_webp', 10, 2 );
}
/*-------------------------------------------------------------------------------
                                  Exif
-------------------------------------------------------------------------------*/
if(get_option('life_options_exif')){
/*-----------------------------Get exif----------------------------------------*/
function get_exif(){
   global $wpdb;
   $filename = "";
   $tablename = $table_prefix.'posts';
   preg_match_all('(.*)/wp-content/uploads/[0-9]{4}/[0-9]{2}/(.*)',$_POST["guid"],$filename);
   if(exif_imagetype($filename[3])){
   $exif = exif_read_data($filename[3],'IFD0');
   if($exif){
	  $wpdb->update($tablename,array('post_content' => $exif),array('ID'=>$_POST["ID"]));
	}
   else{
	  $wpdb->insert($tablename,array('post_content' => 'No exif information'),array('ID'=>$_POST["ID"]));
	}
}
}
add_action('add_attachment','get_exif',10,1);
#apply_filters('media_upload_tabs',array)
}
/*-------------------------------------------------------------------------------
                          Show error message when 500
-------------------------------------------------------------------------------*/
function get_my_custom_die_handler($message, $title='', $args=array()) {
   exit;
   }
add_filter('wp_die_handler', 'get_my_custom_die_handler');

/*-------------------------------------------------------------------------------
                              Post's title must
--------------------------------------------------------------------------------*/
if(get_option('life_options_title')){
function required_title() {
?>
<script type="text/javascript">
  jQuery(document).ready(function($){
      if('post' == $('#post_type').val()){
            $("#post").submit(function(e){
                  if('' == $('#title').val()) {
                        alert('Title must be entered');
                        $('#ajax-loading').css('visibility', 'hidden');
                        $('#publish').removeClass('button-primary-disabled');
                        $('#title').focus();
                        return false;
                    }
             });
	  }
  });
</script>
<?php
}
add_action('admin_head-post-new.php', 'required_title');
add_action('admin_head-post.php', 'required_title');
}
/*-------------------------------------------------------------------------------
                             Remove_updates
--------------------------------------------------------------------------------*/
if(get_option('life_options_updates')){
#$user = wp_get_current_user();    //更新可能ユーザー追加
#if ( !($user->user_login == 'psuser')){   
function remove_updates()
{
  global $wp_version;
  return (object) array(
  'last_checked'    => time(),
  'updates'         => array(),
  'version_checked' => $wp_version
  );  
}
add_filter('pre_site_transient_update_core', 'remove_updates');
add_filter('pre_site_transient_update_plugins', 'remove_updates');
add_filter('automatic_updater_disabled', '__return_true');
remove_action('load-plugins.php', 'wp_update_plugins');
remove_action('load-update.php', 'wp_update_plugins');
remove_action('load-update-core.php', 'wp_update_plugins');
#}
}
/*------------------------------------------------------------------------------
                       Disable F12, Copy, paste, cut
------------------------------------------------------------------------------*/
if(get_option('life_options_copy')){
function disable_f12_copy_paste(){
if(!current_user_can('manage_options')){
?>
<script type="text/javascript">
   //Disable right click menu
   document.oncontextmenu = new Function("return false;");
   //Disable F12
   document.onkeydown = document.onkeyup = document.onkeypress = function(event) {
      var e = event || window.event || arguments.callee.caller.arguments[0];
	  if (e && e.keyCode == 123) {
	        e.returnValue = false;
			return (false);
			}
   };
   //Disable text selection
   document.onselectstart = function(){ return false; };
   //Disable copy
   document.oncopy = function(){ return false; };
   //Disable cut
   document.oncut = function(){ return false; };
   //Disable paste
   document.onpaste = function(){ return false; };
</script>
<?php
}
}
add_action('wp_footer', 'disable_f12_copy_paste');
}
?>
