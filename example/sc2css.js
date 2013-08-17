
function sc2css_render_city( city ) {
   
   $('#city-map-container').append( '<div id="city-map"></div>' );

   // Add each tile to the scene.
   $.each( city['ALTM'], function( row_index, row ) {
      $('#city-map').append(
         '<div class="city-map-row" id="city-map-row-' + row_index + '"></div>'
      );
      $.each( row, function( tile_index, tile ) {
         $('#city-map-row-' + row_index).append(
            '<div class="city-map-tile city-map-alt-' + 
               tile['altitude'] + '"></div>'
         );
      } );
   } );

   $('#city-map').addClass( 'city-map-animate' );
}

