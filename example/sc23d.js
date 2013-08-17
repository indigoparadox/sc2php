
var sc23dScene = null,
   sc23dRenderer = null,
   sc23dCamera = null,
   sc23dMap = null;

function sc23d_render_city( city ) {
   var WIDTH = 800,
      HEIGHT = 600,
      VIEW_ANGLE = 45,
      ASPECT = WIDTH / HEIGHT,
      NEAR = 0.1,
      FAR = 10000;

   // Setup the camera.
   sc23dRenderer = new THREE.WebGLRenderer();
   sc23dCamera = new THREE.PerspectiveCamera( VIEW_ANGLE, ASPECT, NEAR, FAR );
   sc23dCamera.eulerOrder = 'YXZ';
   sc23dCamera.position.x = 0;
   sc23dCamera.position.y = -1100;
   sc23dCamera.position.z = 600;
   sc23dScene = new THREE.Scene();

   console.log( city['XTER'][126][0] );

   // Add each tile to the scene.
   var map_geom = new THREE.Geometry();
   var water_geom = new THREE.Geometry();
   $.each( city['ALTM'], function( row_index, row ) {
      var x = -640 + (row_index * 10);
      var y = -640;
      $.each( row, function( tile_index, tile ) {
         var z_nw = 0,
            z_ne = 0,
            z_se = 0,
            z_sw = 0;

         // TODO: Set the Z to the water line for water tiles.

         /*
         if( city['XTER'][row_index][tile_index]['raised']['nw'] ) {
            z_nw = 10;
         }
         if( city['XTER'][row_index][tile_index]['raised']['ne'] ) {
            z_ne = 10;
         }
         if( city['XTER'][row_index][tile_index]['raised']['se'] ) {
            z_se = 10;
         }
         if( city['XTER'][row_index][tile_index]['raised']['sw'] ) {
            z_sw = 10;
         }
         */

         // Create a square for the tile.
         var tile_geom = new THREE.Geometry();
         tile_geom.vertices.push( new THREE.Vector3(
            x, y, (tile['altitude'] * 10) + z_nw
         ) );
         tile_geom.vertices.push( new THREE.Vector3(
            x + 10, y, (tile['altitude'] * 10) + z_ne
         ) );
         tile_geom.vertices.push( new THREE.Vector3(
            x + 10, y + 10, (tile['altitude'] * 10) + z_se
         ) );
         tile_geom.vertices.push( new THREE.Vector3(
            x, y + 10, (tile['altitude'] * 10) + z_sw
         ) );
         tile_geom.faces.push( new THREE.Face3( 0, 1, 2 ) );
         tile_geom.faces.push( new THREE.Face3( 2, 3, 0 ) );

         // Set the tile color based on the presence of water.
         var color = 0;
         if( tile.water ) {
            THREE.GeometryUtils.merge( water_geom, tile_geom );
         } else {
            THREE.GeometryUtils.merge( map_geom, tile_geom );
         }

         // Add the tile to the map group.

         y += 10;
      } );
   } );

   // Create separare meshes for land and water.
   sc23dMap = new THREE.Object3D();
   sc23dMap.add( new THREE.Mesh(
      map_geom,
      new THREE.MeshBasicMaterial( {
         'color': 0xffffe5, 'wireframe': false
      } )
   ) );
   sc23dMap.add( new THREE.Mesh(
      water_geom,
      new THREE.MeshBasicMaterial( {
         'color': 0x0000ee, 'wireframe': false
      } )
   ) );

   // Setup the scene.
   sc23dScene.add( sc23dMap );
   sc23dScene.add( sc23dCamera );
   sc23dCamera.lookAt( sc23dScene.position );
   sc23dRenderer.setSize( WIDTH, HEIGHT );
   sc23dRenderer.setClearColor( new THREE.Color( 0x000000 ) );
   $('#city-map-container').append( sc23dRenderer.domElement );

   sc23dAnimate();
}

function sc23dAnimate() {

   requestAnimationFrame( sc23dAnimate );

   sc23dMap.rotation.z -= 0.01;

   sc23dRenderer.render( sc23dScene, sc23dCamera );
}

