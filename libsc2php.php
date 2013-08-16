<?php

function sc2_segment_types() {
   return array(
      'MISC' => 4800,    
      'ALTM' => 32768,
      'XTER' => 16384,    
      'XBLD' => 16384,    
      'XZON' => 16384,    
      'XUND' => 16384,    
      'XTXT' => 16384,    
      'XLAB' => 6400,    
      'XMIC' => 1200,    
      'XTHG' => 480,    
      'XBIT' => 16384,    
      'XTRF' => 4096,    
      'XPLT' => 4096,    
      'XVAL' => 4096,    
      'XCRM' => 4096,    
      'XPLC' => 1024,    
      'XFIR' => 1024,    
      'XPOP' => 1024,    
      'XROG' => 1024,    
      'XGRP' => 3328,    
      'CNAM' => 32,
   );
}

function sc2_verify( $sc2_file ) {
   
   // TODO: Verify not null/open.

   // Verify magic number and IFF file type.
   $magic_number = fread( $sc2_file, 4 );
   fseek( $sc2_file, 8 );
   $file_type = fread( $sc2_file, 4 );
   return 'FORM' == $magic_number && 'SCDH' == $file_type; 
}

function _sc2_segment_unpack( $id, $data ) {
   if( 'CNAM' == $id ) {
      return $data;
   } else {
      return base64_encode( $data );
   }
}

function sc2_segments( $sc2_file ) {
   $segments = array();
   $bytes = 0;

   // Read the file header.
   fseek( $sc2_file, 0 );
   $sc2_header = unpack(
      'A4type/N1length/A4filetype',
      fread( $sc2_file, 12 )
   );

   // Iterate through each segment until we get to the end. (-4?)
   while( $bytes < $sc2_header['length'] - 4 ) {
      $bytes += 8; // Every segment head is 8 bytes.
      $header = unpack(
         'A4type/N1length',
         fread( $sc2_file, 8 )
      );
      $bytes += $header['length'];
      $segments[] = array(
         'header' => $header,
         'data' => _sc2_segment_unpack(
            $header['type'],
            fread( $sc2_file, $header['length'] )
         ),
      );

      /*
      $segment_type = fread( $sc2_file, 4 );
      $segment_len = fread( $sc2_file, 4 );
      //print_r( intval( $segment_len ) );
      $segment = array( 'type' => $segment_type );
      if( 0 < intval( $segment_len ) ) {
         $segment['data'] = fread( $sc2_file, intval( $segment_len ) );
      }
      $segments[] = $segment;
      */
   }

   return $segments;
}

function sc2_title( $sc2_file ) {
   
}

