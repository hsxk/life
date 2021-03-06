<?php
/*
 * Template Name: google map
 */
 ?>
<?php
	global $wpdb;
	$post_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'gps'" );
	$center = google_map_center( $post_ids );
	if ( isset( $post_ids ) && $post_ids != null ) {
		$markers = '';
		$content = '';
		foreach( $post_ids as $key => $post_id ) {
			#var_dump( get_post_custom_keys( $post_id ) ); //select all metakeys from post
			$content .= google_maps_content( $post_id );
			$markers .= google_map_marker( $post_id );
			}
		} 
	else {
		$markers = google_map_marker($post_ids);
		$content = google_maps_content( $post_ids );
		}
?>
<h3>Life Maps</h3>
<div id="map"></div>
<script>
	var map;
	function initMap() {
		var map = new google.maps.Map(
		document.getElementById( 'map' ), { zoom: 16, center: <?php echo $center; ?> } );
		<?php echo $content; ?>
		<?php echo $markers; ?>
	}
</script>
<script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB3HNBEo_ND6z7s3ethaRA0lPxikOUqjwU&callback=initMap"></script>
