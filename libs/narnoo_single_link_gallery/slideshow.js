(function() {
	if ( typeof narnoo_slideshow === 'undefined' ) {
		return;
	}

	jQuery( document ).ready( function( $ ) {
		$( '.narnoo_slg' ).each( function() {
			var $that = $(this);
			$.ajax({
				url: narnoo_slideshow_ajax_url,
				dataType: 'json',
				timeout: 60000,
				data: [
					{ 'name': 'narnoo_slg_shortcode_count', 'value': $that.attr( 'data-count' ) },
					{ 'name': 'action', 'value': 'narnoo_operator_lib_request' },
					{ 'name': 'lib_path', 'value': narnoo_slideshow_file_url },
					{ 'name': 'album_name', 'value': $that.attr( 'data-album-name' ) },
					{ 'name': 'width', 'value': $that.attr( 'data-width' ) },
					{ 'name': 'height', 'value': $that.attr( 'data-height' ) }
				],
				type: 'POST',
				error: function( jqXHR, textStatus, errorThrown ) {
					console.error( 'Error (Narnoo Single Link Gallery): ' + textStatus + ' ' + errorThrown );
					console.error( jqXHR );
					
					build_imagebox();
				},
				success: function( data, textStatus, jqXHR ) {
					$that.html( data.response );
					imagebox.creategallery( 'narnoo_slg' + $that.attr( 'data-count' ), $that.attr( 'data-album-name' ), {
						// gallery options go here, these will affect only the images in this gallery
						galleryTitle: '%GALLERY%:  %LIST%'
					});
					
					build_imagebox();
				}
			});							
		});
	});   

	function build_imagebox() {
		arguments.callee.staticCount = arguments.callee.staticCount || 0;
		arguments.callee.staticCount++;
		if ( arguments.callee.staticCount >= narnoo_slideshow.count ) {
			// build imagebox once all galleries have been loaded
			imagebox.build({
				// global options go here, these will affect all images
			});		
		}
	}
	
	function add_css(filename) {
		var fileref = document.createElement("link")
		fileref.setAttribute("rel", "stylesheet")
		fileref.setAttribute("type", "text/css")
		fileref.setAttribute("href", narnoo_slideshow_url + filename)
		document.getElementsByTagName("head")[0].appendChild(fileref);
	}

	// CSS for single link gallery
	add_css( 'imagebox/imagebox.min.css' );
	add_css( 'style.css' );	
})();
