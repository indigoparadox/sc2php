<?php

// define: MISC segment data indexes
define( 'SC2_MISC_FOUNDING_YEAR', 3 );
define( 'SC2_MISC_DAYS_ELAPSED', 4 );
define( 'SC2_MISC_MONEY_SUPPLY', 5 );
define( 'SC2_MISC_SIMNATION_POP', 20 );
define( 'SC2_MISC_NEIGHBOR_1_POP', 439 );
define( 'SC2_MISC_NEIGHBOR_2_POP', 443 );
define( 'SC2_MISC_NEIGHBOR_3_POP', 447 );
define( 'SC2_MISC_NEIGHBOR_4_POP', 451 );

// define: XLAB segment label indexes
define( 'SC2_XLAB_MAYOR_NAME', 0 );

// return: true if valid SC2k file
function sc2_verify( $sc2_file ) {
   
   // TODO: Verify not null/open.

   // Verify magic number and IFF file type.
   $magic_number = fread( $sc2_file, 4 );
   fseek( $sc2_file, 8 );
   $file_type = fread( $sc2_file, 4 );
   return 'FORM' == $magic_number && 'SCDH' == $file_type; 
}

function _sc2_rle_decode( $data ) {
   // Unpack the bytes from the binary string and start decoding them.
   $data = unpack( 'C*', $data );

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

   return $output;
}

// return: unpacked segment data
function _sc2_segment_unpack( $id, $data ) {
   if( 'CNAM' == $id ) {
      return $data;
   } elseif( 'ALTM' == $id ) {
      // Unpack the uncompressed altitude map, resetting array indices.
      return array_values( unpack( 'C*', $data ) );
   } elseif( 'MISC' == $id ) {
      // Repack the uncompressed data and unpack it as longs.
      $decoded = _sc2_rle_decode( $data );
      unset( $data );
      $repacked = '';
      for( $i = 0 ; count( $decoded ) > $i ; $i++ ) {
         $repacked .= pack( 'C1', $decoded[$i] );
      }
      // Reset array indices from unpack()'s 1-indexed scheme.
      return array_values( unpack( 'N1200', $repacked ) );
   } elseif( 'XLAB' == $id ) {
      $decoded = _sc2_rle_decode( $data );
      unset( $data );
      $output = array();
      // Loop through the byte array and decode the 25-byte strings contained
      // within.
      for( $i = 0 ; count( $decoded ) > $i ; $i++ ) {
         if( 0 == $i % 25 ) {
            if( isset( $label ) ) {
               // Append the finished 25-byte label to the array.
               $output[] = $label;
            }
            // Start again (or anew).
            $label = '';
         } else {
            $label .= chr( $decoded[$i] );
         }
      }
      return $output;
   } else {
      return _sc2_rle_decode( $data );
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

