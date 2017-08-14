(function() {
	if ( typeof narnoo_tiles === 'undefined' ) {
		return;
	}
	
	jQuery(document).ready(function ($) {
		$(".narnoo_tiles").each(function() {
			var $that = $(this);
			$.ajax({
				url: narnoo_tiles_ajax_url,
				dataType: 'json',
				timeout: 60000,
				data: [
					{ 'name': 'narnoo_tiles_shortcode_count', 'value': $that.attr( 'data-count' ) },
					{ 'name': 'action', 'value': 'narnoo_operator_lib_request' },
					{ 'name': 'lib_path', 'value': narnoo_tiles_file_url },
					{ 'name': 'album_name', 'value': $that.attr( 'data-album-name' ) }
				],
				type: 'POST',
				error: function( jqXHR, textStatus, errorThrown ) {
					console.error( 'Error (Narnoo Tiles Gallery): ' + textStatus + ' ' + errorThrown );
					console.error( jqXHR );
				},
				success: function( data, textStatus, jqXHR ) {
					$that.html( data.response );
					var id = $that.attr("id");
					var i = id.substr(id.lastIndexOf("_") + 1);
					$that.tilesGallery({
						width: $that.attr( 'data-width' ),
						height: $that.attr( 'data-height' ),
						margin: 5,
						caption: true,
						captionOnMouseOver: true,
						callback: tilesCallbackFn( $that )
					});
				}
			});							
		});		
	});

	function tilesCallbackFn( $that ) {
		return function () {
			$that.find('a').lightBox();
		};
	}
	
	function add_css(filename) {
		var fileref = document.createElement("link");
		fileref.setAttribute("rel", "stylesheet");
		fileref.setAttribute("type", "text/css");
		fileref.setAttribute("href", narnoo_tiles_url + filename);
		document.getElementsByTagName("head")[0].appendChild(fileref);
	}

	// add CSS stylesheets
	add_css( 'css/jquery-tilesgallery.css' );
	add_css( 'css/jquery.lightbox-0.5.css' );
	add_css( 'css/narnoo_tiles_gallery.css' );  
})();
