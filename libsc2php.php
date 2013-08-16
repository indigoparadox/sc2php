<?php

// return: true if valid SC2k file
function sc2_verify( $sc2_file ) {
   
   // TODO: Verify not null/open.

   // Verify magic number and IFF file type.
   $magic_number = fread( $sc2_file, 4 );
   fseek( $sc2_file, 8 );
   $file_type = fread( $sc2_file, 4 );
   return 'FORM' == $magic_number && 'SCDH' == $file_type; 
}

// return: unpacked segment data
function _sc2_segment_unpack( $id, $data ) {
   $output = array();
   if( 'CNAM' == $id ) {
      return $data;
   } elseif( 'ALTM' == $id ) {
      // Unpack the uncompressed altitude map.
      $unpacked_data = unpack( 'C*', $data );
      for( $i = 1 ; count( $unpacked_data ) + 1 > $i ; $i++ ) {
         $output[] = $unpacked_data[$i];
      }
      return $output;
   } else {
      // Unpack the bytes from the binary string and start decoding them.
      $data = unpack( 'C*', $data );

      /*echo( '<div style="float: left">' );
      echo( '<h2>Data</h2>' );
      print_r( $data );
      echo( '</div>' );*/
      
      // Decode the simple RLE-esque sequence for the given segment.
      for( $i = 1 ; count( $data ) + 1 > $i ; $i++ ) {
         if( 1 <= $data[$i] && 127 >= $data[$i] ) {
            // Unencoded data bytes follow.
            $seq = $data[$i];
            for( $j = 0 ; $seq > $j ; $j++ ) {
               $output[] = $data[++$i];
            }

         } elseif( 129 <= $data[$i] && 255 >= $data[$i] ) {
            // An encoded data byte follows.
            $repeat = $data[$i] - 127;
            $i++; // Proceed to the data byte.
            for( $j = 0 ; $repeat > $j ; $j++ ) {
               $output[] = $data[$i];
            }
         }
      }

      /*echo( '<div style="float: left">' );
      echo( '<h2>Output</h2>' );
      print_r( $output );
      echo( '</div>' );*/

      return $output;
   }
}

// return: unpacked segment list
function sc2_segments( $sc2_file ) {
   $segments = array();
   $bytes = 0;

   // Read the file header.
   fseek( $sc2_file, 0 );
   $sc2_header = unpack(
      'A4type/N1packed/A4filetype',
      fread( $sc2_file, 12 )
   );

   // Iterate through each segment until we get to the end. (-4?)
   while( $bytes < $sc2_header['packed'] - 4 ) {
      $bytes += 8; // Every segment head is 8 bytes.
      $header = unpack(
         'A4type/N1packed',
         fread( $sc2_file, 8 )
      );
      $bytes += $header['packed'];
      $data = _sc2_segment_unpack(
         $header['type'],
         fread( $sc2_file, $header['packed'] )
      );
      $segments[] = array(
         'header' => $header,
         'length' => count( $data ),
         'data' => $data,
      );
   }

   return $segments;
}

