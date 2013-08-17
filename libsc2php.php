<?php

/*
A simple PHP library to decode SimCity 2000 saved cities into a more readily 
usable form. Might be useful for making things like online leaderboards or
WebGL-based 3D city drive-throughs. Based heavily on the file format
information from: http://djm.cc/simcity-2000-info.txt

Written by indigoparadox, https://bitbucket.org/indigoparadox/sc2php/overview

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// define: XLAB segment label indexes
define( 'SC2_XLAB_MAYOR_NAME', 0 );

// define: helpful map property constants
define( 'SC2_MAP_ROWS_MAX', 128 );

// define: readable binary bit constants
define( 'SC2_BIT0', 1 );
define( 'SC2_BIT1', 2 );
define( 'SC2_BIT2', 4 );
define( 'SC2_BIT3', 8 );
define( 'SC2_BIT4', 16 );
define( 'SC2_BIT5', 32 );
define( 'SC2_BIT6', 64 );
define( 'SC2_BIT7', 128 );

// return: true if valid SC2k file
function sc2_verify( $sc2_file ) {
   
   // Verify open/not null.
   if( empty( $sc2_file ) ) {
      return false;
   }

   // Verify magic number and IFF file type.
   $magic_number = fread( $sc2_file, 4 );
   fseek( $sc2_file, 8 );
   $file_type = fread( $sc2_file, 4 );
   return 'FORM' == $magic_number && 'SCDH' == $file_type; 
}

// return: uncompressed segment data
function _sc2_rle_decode( $data ) {
   // Unpack the bytes from the binary string to decode.
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
   switch( $id ) {
      case 'CNAM':
         return $data;

      case 'ALTM':
         $unpacked = unpack( 'C*', $data );
         unset( $data );

         // Each map row is 2 bytes.
         $map_rows_times_2 = SC2_MAP_ROWS_MAX * 2;

         // TODO: Rather than n1 followed by anding, unpack the 4-bit altitude
         //       number directly, somehow.

         // The first four bits of a tile long hold the altitude.
         $tile_altitude_bits = SC2_BIT0 + SC2_BIT1 + SC2_BIT2 + SC2_BIT3;

         // Divide the map into 128 rows of 128 columns of tiles.
         $rows = array();
         for( $i = 1 ; $i <= count( $unpacked ) ; $i += $map_rows_times_2 ) {
            $row = array();
            for( $j = 0 ; $j < $map_rows_times_2 ; $j += 2 ) {
               // Pack the bytes and then unpack them into big-endian 16-bit
               // ints.
               $tile = unpack( 'n1', pack(
                  'C2', $unpacked[$i + $j], $unpacked[$i + $j + 1]
               ) );
               $tile = array(
                  'altitude' => $tile_altitude_bits & $tile[1],
                  'water' => (SC2_BIT7 & $tile[1]) ? true : false,
               );
               $row[] = $tile;
            }
            $rows[] = $row;
         }
         return $rows;
      
      case 'MISC':
         // Repack the data after uncompressing it and unpack it as longs.
         $decoded = _sc2_rle_decode( $data );
         unset( $data );
         $repacked = '';
         for( $i = 0 ; count( $decoded ) > $i ; $i++ ) {
            $repacked .= pack( 'C1', $decoded[$i] );
         }
         $longs = unpack( 'N1200', $repacked );

         // If we ever figure out what those other numbers do, we can add them
         // with a meaningful index here. This makes it simpler to deal with the
         // data array client-side if it gets translated to JSON.
         return array(
            'founding_year' => $longs[4],
            'days_elapsed' => $longs[5],
            'money_supply' => $longs[6],
            'simnation_pop' => $longs[21],
            'neighbor1_pop' => $longs[440],
            'neighbor2_pop' => $longs[444],
            'neighbor3_pop' => $longs[448],
            'neighbor4_pop' => $longs[452],
         );

      case 'XTER':
         $decoded = _sc2_rle_decode( $data );
         unset( $data );
      
         // Generally, bits 4 and 5 of each tile decide its water coverage.
         $tile_water_bits = SC2_BIT4 + SC2_BIT5;

         // For canal/surf tiles, bits 0-3 decide the directions they point.
         $tile_canal_bits = SC2_BIT0 + SC2_BIT1 + SC2_BIT3;

         // Parse and divide the tile shape data into 128 rows of 128 columns.
         $rows = array();
         for( $i = 0 ; count( $decoded ) > $i ; $i++ ) {
            if( 0 == $i % SC2_MAP_ROWS_MAX ) {
               if( isset( $row ) ) {
                  // Append the finished 25-byte label to the array.
                  $rows[] = $row;
               }
               $row = array();
            }

            // Set the defaults for this tile and process them below.
            $tile = array(
               'raised' => array( 'nw' => 0, 'ne' => 0, 'se' => 0, 'sw' => 0 ),
               'water' => 'none',
               'canal' => array( 'n' => 0, 'e' => 0, 's' => 0, 'w' => 0 ),
            );
            
            // Detect some special exceptions.
            if( 0x3e == $decoded[$i] ) {
               // TODO: Taken from the spec, what does this mean, precisely?
               $tile['water'] = 'waterfall';

            } elseif( 0x40 & $decoded[$i] ) {
               // Figure out the surface water situation.
               $tile['water'] = 'surface';
               switch( $tile_canal_bits & $decoded[$i] ) {
                  case 0x0:
                     $tile['canal']['e'] = 1;
                     $tile['canal']['w'] = 1;
                     break;

                  case 0x1:
                     $tile['canal']['e'] = 1;
                     $tile['canal']['w'] = 1;
                     break;

                  case 0x2:
                     $tile['canal']['s'] = 1;
                     break;

                  case 0x3:
                     $tile['canal']['w'] = 1;
                     break;

                  case 0x4:
                     $tile['canal']['n'] = 1;
                     break;
                     
                  case 0x5:
                     $tile['canal']['s'] = 1;
                     break;
               }

            } else {
               // Figure out the raised corners of this tile.
               switch( 15 & $decoded[$i] ) {
                  case 0x1:
                     $tile['raised']['nw'] = 1;
                     $tile['raised']['ne'] = 1;
                     break;

                  case 0x2:
                     $tile['raised']['ne'] = 1;
                     $tile['raised']['se'] = 1;
                     break;

                  case 0x3:
                     $tile['raised']['se'] = 1;
                     $tile['raised']['sw'] = 1;
                     break;

                  case 0x4:
                     $tile['raised']['sw'] = 1;
                     $tile['raised']['nw'] = 1;
                     break;

                  case 0x5:
                     $tile['raised']['sw'] = 1;
                     $tile['raised']['nw'] = 1;
                     break;

                  case 0x6:
                     $tile['raised']['ne'] = 1;
                     $tile['raised']['sw'] = 1;
                     $tile['raised']['se'] = 1;
                     break;

                  case 0x7:
                     $tile['raised']['sw'] = 1;
                     $tile['raised']['se'] = 1;
                     $tile['raised']['nw'] = 1;
                     break;

                  case 0x8:
                     $tile['raised']['sw'] = 1;
                     $tile['raised']['nw'] = 1;
                     $tile['raised']['ne'] = 1;
                     break;

                  case 0x9:
                     $tile['raised']['ne'] = 1;
                     break;

                  case 0xa:
                     $tile['raised']['se'] = 1;
                     break;

                  case 0xb:
                     $tile['raised']['sw'] = 1;
                     break;

                  case 0xc:
                     $tile['raised']['nw'] = 1;
                     break;

                  case 0xd:
                     $tile['raised']['nw'] = 1;
                     $tile['raised']['ne'] = 1;
                     $tile['raised']['se'] = 1;
                     $tile['raised']['sw'] = 1;
                     break;
               }

               // Figure out the water situation on this tile.
               switch( $tile_water_bits & $decoded[$i] ) {
                  case 0x10:
                     $tile['water'] = 'submerged';
                     break;

                  case 0x20:
                     $tile['water'] = 'partial';
                     break;
               }
            }

            $row[] = $tile;
         }
         $rows[] = $row;
         return $rows;

      case 'XLAB':
         $decoded = _sc2_rle_decode( $data );
         unset( $data );

         // Loop through the byte array and decode the 25-byte strings contained
         // within.
         $output = array();
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

      // Skip loading other segments for now since they're not even meaningfully
      // decoded yet.
      default:
         //return _sc2_rle_decode( $data );
         return null;
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
      $segments[$header['type']] = $data;
   }

   return $segments;
}

