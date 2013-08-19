<?php
require_once( '../libsc2php.php' );

/* $city = 'test';
if( isset( $_REQUEST['city'] ) ) {
   $city = $_REQUEST['city'];
} */

// See if a hash or city file was given.
if( isset( $_FILES['city-upload'] ) && 0 == $_FILES['city-upload']['error'] ) {
   // Process uploaded city if and only if it's a valid city.
   $sc2_file = fopen( $_FILES['city-upload']['tmp_name'], 'rb' );
   if( sc2_verify( $sc2_file ) ) {
      // Hash and parse the contents.
      $hash = md5( file_get_contents( $_FILES['city-upload']['tmp_name'] ) );

      //for( array(
      //print_r( sc2_segment_data( $sc2_file, 'XTER' ) );
      //die();

      if( file_exists( $hash.'.json' ) ) {
         unlink( $hash.'.json' );
      }
      // Write out a cache json file if not already present.
      $cache_file = fopen( $hash.'.json', 'w' );

      // One way is to grab everything with sc2_segments(), though this may
      // take a lot of memory!
      //$segments = sc2_segments( $sc2_file );
      //fwrite( $cache_file, json_encode( $segments ) );

      // A slightly more efficient way is to grab one segment at a time,
      // from only the segments we know we'll use, and write them to the
      // file as we get them.
      $usable_segments = array(
         'CNAM', 'MISC', 'XTER', 'XBLD', 'ALTM', 'XLAB'
      );
      fwrite( $cache_file, '{' );
      for( $i = 0 ; count( $usable_segments ) > $i ; $i++ ) {
         fwrite( $cache_file, '"'.$usable_segments[$i].'":' );
         $segment_data = sc2_segment_data( $sc2_file, $usable_segments[$i] );
         fwrite( $cache_file, json_encode( $segment_data ) );
         unset( $segment_data ); // Reclaim memory.
         if( count( $usable_segments ) - 1 > $i ) {
            fwrite( $cache_file, ',' );
         }
      }
      fwrite( $cache_file, '}' );

      fclose( $cache_file );
   }
   fclose( $sc2_file );
   unlink( $_FILES['city-upload']['tmp_name'] );
   
} elseif( isset( $_GET['hash'] ) && ctype_alnum( $_GET['hash'] ) ) {
   $hash = $_GET['hash'];
}

// Only even try to show the segments if we have a valid hash.
if( isset( $_GET['src'] ) && isset( $hash ) ) {
   $src = true;
}

?><html>
 <head>
  <title>SimCity 2000 Decoding Demo</title>
  <style type="text/css">
  </style>
  <link rel="stylesheet" type="text/css" href="style.css" />
  <script type="text/javascript" src="three.min.js"></script>
  <script type="text/javascript" src="jquery.min.js"></script>
  <script type="text/javascript" src="sc23d.js"></script>
  <?php if( isset( $hash ) ) { ?>
  <script type="text/javascript">
   $(document).ready( function() {
    $.getJSON( '<?php echo( $hash ); ?>.json', function( data ) {
      sc23d_render_city( data, 800, 600, '#city-map-container' );
      $('h1').text( data['CNAM'] );
      $('#city-mayor-name').text( 'Mayor: ' + data['XLAB'][0] );
    } );
   } );
  </script>
  <?php } ?>
 </head>
 <body>
  <?php if( isset( $hash ) ) { ?>
  <h1><?php echo( $hash ); ?></h1>
  <p id="city-mayor-name"></p>
  <p id="city-hash-link">
   <a href="?hash=<?php echo( $hash ); ?>">Permalink</a>
  </p>
  <?php } ?>
  <div id="city-map-container"></div>
  <form
   enctype="multipart/form-data"
   action="index.php"
   method="post"
   id="city-upload-form"
  >
   <h2>Upload New City</h2>
   <label for="city-upload">City File:</label>
   <input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
   <input id="city-upload" name="city-upload" type="file" />
   <input type="submit" value="Upload" />
  </form> 
  <?php if( isset( $src ) ) { ?>
  <pre id="city-map-src">
   <?php print_r( $segments ); ?>
  </pre>
  <?php } ?>
 </body>
</html>
