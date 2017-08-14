(function() {
	if ( typeof narnoo_sliders === 'undefined' ) {
		return;
	}

	jQuery( document ).ready( function( $ ) {
		$( '.narnoo_slider' ).each( function() {
			var $that = $(this);
			$.ajax({
				url: narnoo_sliders_ajax_url,
				dataType: 'json',
				timeout: 60000,
				data: [
					{ 'name': 'narnoo_slider_shortcode_count', 'value': $that.attr( 'data-count' ) },
					{ 'name': 'action', 'value': 'narnoo_operator_lib_request' },
					{ 'name': 'lib_path', 'value': narnoo_sliders_file_url },
					{ 'name': 'album_name', 'value': $that.attr( 'data-album-name' ) }
				],
				type: 'POST',
				error: function( jqXHR, textStatus, errorThrown ) {
					console.error( 'Error (Narnoo Slider Gallery): ' + textStatus + ' ' + errorThrown );
					console.error( jqXHR );
				},
				success: function( data, textStatus, jqXHR ) {
					$that.html( data.response ).gallerify();
				}
			});							
		});
	});   
	
	function add_css(filename) {
		var fileref = document.createElement("link")
		fileref.setAttribute("rel", "stylesheet")
		fileref.setAttribute("type", "text/css")
		fileref.setAttribute("href", narnoo_sliders_url + filename)
		document.getElementsByTagName("head")[0].appendChild(fileref);
	}
	
	// CSS for slider gallery
	add_css( 'css/gallerify.css' );
})();
