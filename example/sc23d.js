
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

   // Add each tile to the scene.
   sc23dMap = new THREE.Object3D();
   $.each( city['ALTM'], function( row_index, row ) {
      var x = -640 + (row_index * 10);
      var y = -640;
      $.each( row, function( tile_index, tile ) {
         // Create a square for the tile.
         var tile_geom = new THREE.Geometry();
         tile_geom.vertices.push( new THREE.Vector3(
            x, y, tile['altitude'] * 10
         ) );
         tile_geom.vertices.push( new THREE.Vector3(
            x + 10, y, tile['altitude'] * 10 
         ) );
         tile_geom.vertices.push( new THREE.Vector3(
            x + 10, y + 10, tile['altitude'] * 10 
         ) );
         tile_geom.vertices.push( new THREE.Vector3(
            x, y + 10, tile['altitude'] * 10
         ) );
         tile_geom.faces.push( new THREE.Face3( 0, 1, 2 ) );
         tile_geom.faces.push( new THREE.Face3( 2, 3, 0 ) );

         // Set the tile color based on the presence of water.
         var color = 0;
         if( tile.water ) {
            color = 0x0000ee;
         } else {
            color = 0xffffe5;
         }

         // Add the tile to the map group.
         sc23dMap.add( new THREE.Mesh(
            tile_geom, new THREE.MeshBasicMaterial( {
               'color': color
            } )
         ) );

         y += 10;
      } );
   } );

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

   sc23dMap.rotation.z += 0.05;

   sc23dRenderer.render( sc23dScene, sc23dCamera );
}

