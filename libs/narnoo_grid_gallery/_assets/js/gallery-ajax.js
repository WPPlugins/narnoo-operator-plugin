function narnoo_getGallery(i,name,page_no,downloadable,bandwidth){
	jQuery(document).ready(function ($) {
		$.ajax({
			url: narnoo_grid_ajax_url,
			dataType: 'json',
			//timeout: 60000,
			type: "GET",
			data: [
				{ 'name': 'narnoo_grid_shortcode_count', 'value': i },
				{ 'name': 'action', 'value': 'narnoo_operator_lib_request' },
				{ 'name': 'lib_path', 'value': narnoo_grid_file_url },
				{ 'name': 'album_name', 'value': name },
				{ 'name': 'narnoo_page', 'value': page_no },
				{ 'name': 'downloadable', 'value': downloadable },
				{ 'name': 'bandwidth', 'value': bandwidth }
			],
			beforeSend: function (  ) {
				$('#narnoo-grid-gallery-loader-container-' + i).show();
				$('#narnoo-grid-gallery-' + i).hide();
			},
			success: function( data ) {
				$('#narnoo-grid-gallery-loader-container-' + i).hide();
				$('#narnoo-grid-gallery-' + i).html( data.response );
				$('#narnoo-grid-gallery-' + i).show();
				narnoo_noo_gallery(i);
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				console.error( 'Error (Narnoo Grid Gallery): ' + textStatus + ' ' + errorThrown );
				console.error( jqXHR );
			}
		});
	});
}

function narnoo_noo_gallery( i ){
	jQuery(document).ready(function ($) {
		/* GALLERIES															*/
		/* -------------------------------------------------------------------- */
		
		// Add Fancybox lightboxing to each of the images.
		try { $('.fancybox').fancybox(); } catch(err) { /* Error Stuff */ }
	
		// On window resize, adjust the sizing.
		//$(window).resize(function(){
		//	setTimeout("resizeGalleries()",100);
		//	setTimeout("resizeChosenWidths()",100);
		//	setTimeout("generateGraphs()", 100);
		//});
		
		// When you check a checkbox, add some styling to the image block
		$('#narnoo-grid-gallery-' + i + ' .narnoo-gallery-item .checkbox-block input').click(function(){
		
			var checkedLayer = $(this).parent().parent().find('.checked-layer');
		
			if ($(this).attr('checked')){
				checkedLayer.show();
			} else {
				checkedLayer.hide();
			}
			
		});
		
		$('#narnoo-grid-gallery-' + i + ' .narnoo-gallery').find('.next').click(function(){
			var thisGallery = $(this).parent().parent().parent().find('.narnoo-gallery-wrap');
			
			// Get page information
			var pageDisplayContent = $(this).parent().find('.pagedisplay').val();
			pageDisplayContent = pageDisplayContent.split('/');
			currentPage = pageDisplayContent[0];
			totalPages = pageDisplayContent[1];
			var nextPage = parseInt(currentPage) + 1;
			
			// Get this galleries height
			var galleryHeight = $(this).parent().parent().parent().find('.narnoo-gallery-wrap').height();
			var galleryHeight = galleryHeight + 10;
			
			// Slide the gallery to the next page
			if (nextPage <= totalPages){
				galleryPaginate(thisGallery,nextPage,galleryHeight,totalPages);
			}
		});
		
		$('#narnoo-grid-gallery-' + i + ' .narnoo-gallery').find('.prev').click(function(){
			var thisGallery = $(this).parent().parent().parent().find('.narnoo-gallery-wrap');
			
			// Get page information
			var pageDisplayContent = $(this).parent().find('.pagedisplay').val();
			pageDisplayContent = pageDisplayContent.split('/');
			currentPage = pageDisplayContent[0];
			totalPages = pageDisplayContent[1];
			var prevPage = parseInt(currentPage) - 1;
			
			// Get this galleries height
			var galleryHeight = $(this).parent().parent().parent().find('.narnoo-gallery-wrap').height();
			var galleryHeight = galleryHeight + 10;
			
			// Slide the gallery to the previous page
			if (prevPage > 0){
				galleryPaginate(thisGallery,prevPage,galleryHeight,totalPages);
			}
		});
		
		$('#narnoo-grid-gallery-' + i + ' .narnoo-gallery').find('.last').click(function(){
			var thisGallery = $(this).parent().parent().parent().find('.narnoo-gallery-wrap');
			
			// Get page information
			var pageDisplayContent = $(this).parent().find('.pagedisplay').val();
			pageDisplayContent = pageDisplayContent.split('/');
			currentPage = pageDisplayContent[0];
			totalPages = pageDisplayContent[1];
			
			// Get this galleries height
			var galleryHeight = $(this).parent().parent().parent().find('.narnoo-gallery-wrap').height();
			var galleryHeight = galleryHeight + 10;
			
			// Slide the gallery to the last page
			galleryPaginate(thisGallery,totalPages,galleryHeight,totalPages);
		});
		
		$('#narnoo-grid-gallery-' + i + ' .narnoo-gallery').find('.first').click(function(){
			var thisGallery = $(this).parent().parent().parent().find('.narnoo-gallery-wrap');
			
			// Get page information
			var pageDisplayContent = $(this).parent().find('.pagedisplay').val();
			pageDisplayContent = pageDisplayContent.split('/');
			currentPage = pageDisplayContent[0];
			totalPages = pageDisplayContent[1];
			
			// Get this galleries height
			var galleryHeight = $(this).parent().parent().parent().find('.narnoo-gallery-wrap').height();
			var galleryHeight = galleryHeight + 10;
			
			// Slide the gallery to the first page
			galleryPaginate(thisGallery,1,galleryHeight,totalPages);
		});
	});
}
/* /// END - GALLERIES /// */

/* /// START - DOWNLOAD FUNCTIONS /// */

//function to alert
function narnoo_alertMobile(mediaPage){
	var response = confirm('This is a large file so wifi access is recommended before downloading: Continue?');
	if (response==true) {window.open(""+mediaPage+"", "_blank")}; 			
}
/* /// END - DOWNLOAD FUNCTIONS /// */
	

(function() {
	if ( typeof narnoo_grid === 'undefined' ) {
		return;
	}
	
	jQuery(document).ready(function ($) {
		$(".narnoo_grid").each(function() {
			narnoo_getGallery( $(this).attr( 'data-count' ), $(this).attr( 'data-album-name' ), '1', $(this).attr( 'data-downloadable' ), $(this).attr( 'data-bandwidth' ) );
		});		
	});
	
	function add_css(filename) {
		var fileref = document.createElement("link");
		fileref.setAttribute("rel", "stylesheet");
		fileref.setAttribute("type", "text/css");
		fileref.setAttribute("href", narnoo_grid_url + filename);
		document.getElementsByTagName("head")[0].appendChild(fileref);
	}

	// add CSS stylesheets
	add_css( '_assets/css/gallery.css' );
	add_css( '_assets/bootstrap/css/bootstrap.css' );
	add_css( '_assets/js/fancybox/jquery.fancybox-1.3.4.css' );  	
})();



	
