<?php get_header(); ?>
    <style>
       /* Set the size of the div element that contains the map */
      #map {
        height: 400px;  /* The height is 400 pixels */
        width: 100%;  /* The width is the width of the web page */
       }
    </style>
  <?php
  global $wpdb;
  $post_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'gps'" );
  $markers = '';
  $last = key($post_ids);
  foreach( $post_ids as $key => $post_id ) {
  	$metadata = get_post_meta($post_id,'gps');
	$locations = '{lat:'.$metadata[0]['latitude'].',lng:'.$metadata[0]['longitude'].'}';
			$thumbnail = wp_get_attachment_image_src( $post_id ,'map-icon',true);
	$markers .= 'var marker = new google.maps.Marker({position:'.$locations.',icon:\''.$thumbnail[0].'\', map:map});';
  }
  ?>
    <h3>Life Maps</h3>
    <!--The div element for the map -->
    <div id="map"></div>
    <script>
	var map;
	// Initialize and add the map
function initMap() {
  // The map, centered at Uluru
  var map = new google.maps.Map(
      document.getElementById('map'), {zoom: 14, center: <?php  echo '{lat:'.$metadata[0]['latitude'].',lng:'.$metadata[0]['longitude'].'}'; ?>});
  // The marker, positioned at Uluru
  <?php echo $markers; ?>
}
    </script>
    <!--Load the API from the specified URL
    * The async attribute allows the browser to render the page while the API loads
    * The key parameter will contain your own API key (which is not needed for this tutorial)
    * The callback parameter executes the initMap() function
    -->
	<script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js">
	</script>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB3HNBEo_ND6z7s3ethaRA0lPxikOUqjwU&callback=initMap">
    </script>
<?php get_footer(); ?>
