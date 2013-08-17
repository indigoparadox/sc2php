<?php
require_once( '../libsc2php.php' );
$city_name = 'test';
if( !file_exists( $city_name.'.json' ) ) {
   $sc2_file = fopen( $city_name.'.sc2', 'rb' );
   if( !sc2_verify( $sc2_file ) ) {
      echo( 'ERROR: Bad file.' );
   } else {
      $segments = sc2_segments( $sc2_file );
      fclose( $sc2_file );
      $cache_file = fopen( $city_name.'.json', 'w' );
      fwrite( $cache_file, json_encode( $segments ) );
      fclose( $cache_file );
   }
}
?><html>
 <head>
  <title>SimCity 2000 Decoding Demo</title>
  <link rel="stylesheet" type="text/css" href="sc2css.css" />
  <script
   type="text/javascript"
   src="http://code.jquery.com/jquery-2.0.3.min.js"
  ></script>
  <script type="text/javascript" src="sc2css.js"></script>
  <script type="text/javascript">
   $(document).ready( function() {
    $.getJSON( '<?php echo( $city_name ); ?>.json', function( data ) {
      sc2css_render_city( data );
    } );
   } );
  </script>
 </head>
 <body>
  <div id="city-map-container"></div>
 </body>
</html>
