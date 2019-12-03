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
  $table = $table_prefix.'postmeta';
  $query = 'SELECT post_id FROM '.$table.' WHERE meta_key = \'gps\'';
  $post_ids = $wpdb->get_col( $query);
  $locations = '[';
  $last = key($post_ids);
  foreach( $post_ids as $key => $post_id ) {
  	$metadata = get_post_meta($post_id,'gps');
	if($key != $last){
	$locations .= '{lat:'.$metadata[0]['longitude'].',lng:'.$metadata[0]['latitude'].'},';
		}
	$locations .= '{lat:'.$metadata[0]['longitude'].',lng:'.$metadata[0]['latitude'].'}]';
	}
	echo $locations;
  ?>
    <h3>My Google Maps Demo</h3>
    <!--The div element for the map -->
    <div id="map"></div>
    <script>
// Initialize and add the map
function initMap() {
  // The location of Uluru
  var locations = <?php  echo $locations ; ?>
  // The map, centered at Uluru
  var map = new google.maps.Map(
      document.getElementById('map'), {zoom: 14, center: <?php  echo '{lat:'.$metadata[0]['latitude'].',lng:'.$metadata[0]['longitude'].'}'; ?>});
  // The marker, positioned at Uluru
  var marker = new google.maps.Marker({position: locations, map: map});
}
    </script>
    <!--Load the API from the specified URL
    * The async attribute allows the browser to render the page while the API loads
    * The key parameter will contain your own API key (which is not needed for this tutorial)
    * The callback parameter executes the initMap() function
    -->
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB3HNBEo_ND6z7s3ethaRA0lPxikOUqjwU&callback=initMap">
    </script>
<?php get_footer(); ?>
