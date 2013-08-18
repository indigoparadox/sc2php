<?php
require_once( '../libsc2php.php' );
// Get the arguments.
$action = '';
if( isset( $_REQUEST['action'] ) ) {
   $action = $_REQUEST['action'];
}

// Load the city data.
$city_name = 'test';
unlink( $city_name.'.json' );
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
} else {
   $segments = file_get_contents( $city_name.'.json' );
   $segments = json_decode( $segments );
}
?><html>
 <head>
  <title>SimCity 2000 Decoding Demo</title>
  <style type="text/css">
   html, body {
    background: black;
    color: white;
    margin: 0px;
    padding: 0px;
   }
   body * {
    margin: 0px;
    padding: 0px;
   }
  </style>
  <script type="text/javascript" src="three.min.js"></script>
  <script type="text/javascript" src="jquery.min.js"></script>
  <script type="text/javascript" src="sc23d.js"></script>
  <?php if( 'src' != $action ) { ?>
  <script type="text/javascript">
   $(document).ready( function() {
    $.getJSON( '<?php echo( $city_name ); ?>.json', function( data ) {
      sc23d_render_city( data );
    } );
   } );
  </script>
  <?php } ?>
 </head>
 <body>
  <pre>
  <?php if( 'src' == $action ) {
   print_r( $segments );
  } ?>
  </pre>
 </body>
</html>
