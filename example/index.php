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
  <style type="text/css">
   body {
    background: black;
   }
  </style>
  <script type="text/javascript" src="three.min.js"></script>
  <script type="text/javascript" src="jquery.min.js"></script>
  <script type="text/javascript" src="sc23d.js"></script>
  <script type="text/javascript">
   $(document).ready( function() {
    $.getJSON( '<?php echo( $city_name ); ?>.json', function( data ) {
      sc23d_render_city( data );
    } );
   } );
  </script>
 </head>
 <body>
  <div style="
   display: table; margin: 0px auto
  " id="city-map-container"></div>
 </body>
</html>
