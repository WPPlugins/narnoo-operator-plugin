function escapeHtml(unsafe) {
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

jQuery(document).ready(function($) {
	$('#shortcode').change(function() {
		$('.shortcode_section').hide();
		$('#' + $(this).val() + '_section').show();
	});
	
	// handle brochure insert
	$('#brochure_insert').click(function() {
		var id = parseInt( $('#brochure_id').val() );
		if (isNaN(id)) {
			tinyMCE.activeEditor.windowManager.alert('Id must be numeric');
			return;
		}
		
		var shortcode = '[narnoo_operator_brochure id="' + id + '"';

		function add_to_shortcode_if_not_blank(attr_name) {
			attr_val = $('#brochure_' + attr_name).val();
			if (attr_val != '') {
				shortcode += ' ' + attr_name + '="' + escapeHtml(attr_val) + '"';
			}
		}
		
		add_to_shortcode_if_not_blank('width');
		add_to_shortcode_if_not_blank('height');
		add_to_shortcode_if_not_blank('image');
		add_to_shortcode_if_not_blank('align');
		add_to_shortcode_if_not_blank('img_title');
		add_to_shortcode_if_not_blank('img_alt');
		
		shortcode += ']';
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, escapeHtml(shortcode));
		tinyMCEPopup.close();
	});
	
	// handle video insert
	$('#video_insert').click(function() {
		var id = parseInt( $('#video_id').val() );
		if (isNaN(id)) {
			tinyMCE.activeEditor.windowManager.alert('Id must be numeric');
			return;
		}
		
		var shortcode = '[narnoo_operator_video id="' + id + '"';

		function add_to_shortcode_if_not_blank(attr_name) {
			attr_val = $('#video_' + attr_name).val();
			if (attr_val != '') {
				shortcode += ' ' + attr_name + '="' + escapeHtml(attr_val) + '"';
			}
		}
		
		add_to_shortcode_if_not_blank('width');
		add_to_shortcode_if_not_blank('height');
		add_to_shortcode_if_not_blank('autoplay');
		add_to_shortcode_if_not_blank('play_btn_scale');
		add_to_shortcode_if_not_blank('text_color');
		add_to_shortcode_if_not_blank('play_color');
		add_to_shortcode_if_not_blank('playover_color');
		add_to_shortcode_if_not_blank('playbk_color');
		add_to_shortcode_if_not_blank('playbk_alpha');
		add_to_shortcode_if_not_blank('playbkover_color');
		add_to_shortcode_if_not_blank('rollover_color');
		add_to_shortcode_if_not_blank('bar_color');
		add_to_shortcode_if_not_blank('clocktotal_color');
		add_to_shortcode_if_not_blank('timesplit_color');
		add_to_shortcode_if_not_blank('clock_color');
		add_to_shortcode_if_not_blank('loaded_color');
		add_to_shortcode_if_not_blank('playhead_color');
		add_to_shortcode_if_not_blank('progress_color');
		
		shortcode += ']';
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, escapeHtml(shortcode));
		tinyMCEPopup.close();
	});
	
	// handle tiles gallery insert
	$('#tiles_gallery_insert').click(function() {
		var album_name = $('#tiles_gallery_album_name').val();
		if (album_name == '') {
			tinyMCE.activeEditor.windowManager.alert('album_name must not be blank');
			return;
		}
		
		var shortcode = '[narnoo_operator_tiles_gallery album_name="' + album_name + '"';

		function add_to_shortcode_if_not_blank(attr_name) {
			attr_val = $('#tiles_gallery_' + attr_name).val();
			if (attr_val != '') {
				shortcode += ' ' + attr_name + '="' + escapeHtml(attr_val) + '"';
			}
		}
		
		add_to_shortcode_if_not_blank('width');
		add_to_shortcode_if_not_blank('height');
		add_to_shortcode_if_not_blank('border_color');

		shortcode += ']';
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, escapeHtml(shortcode));
		tinyMCEPopup.close();
	});

	// handle grid gallery insert
	$('#grid_gallery_insert').click(function() {
		var album_name = $('#grid_gallery_album_name').val();
		if (album_name == '') {
			tinyMCE.activeEditor.windowManager.alert('album_name must not be blank');
			return;
		}
		
		var shortcode = '[narnoo_operator_grid_gallery album_name="' + album_name + '"';

		function add_to_shortcode_if_not_blank(attr_name) {
			attr_val = $('#grid_gallery_' + attr_name).val();
			if (attr_val != '') {
				shortcode += ' ' + attr_name + '="' + escapeHtml(attr_val) + '"';
			}
		}
		
		add_to_shortcode_if_not_blank('width');
		add_to_shortcode_if_not_blank('height');
		add_to_shortcode_if_not_blank('downloadable');
		add_to_shortcode_if_not_blank('bandwidth');

		shortcode += ']';
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, escapeHtml(shortcode));
		tinyMCEPopup.close();
	});

	// handle slider gallery insert
	$('#slider_gallery_insert').click(function() {
		var album_name = $('#slider_gallery_album_name').val();
		if (album_name == '') {
			tinyMCE.activeEditor.windowManager.alert('album_name must not be blank');
			return;
		}
		
		var shortcode = '[narnoo_operator_slider_gallery album_name="' + album_name + '"';

		function add_to_shortcode_if_not_blank(attr_name) {
			attr_val = $('#slider_gallery_' + attr_name).val();
			if (attr_val != '') {
				shortcode += ' ' + attr_name + '="' + escapeHtml(attr_val) + '"';
			}
		}
		
		add_to_shortcode_if_not_blank('width');
		add_to_shortcode_if_not_blank('timer');
		add_to_shortcode_if_not_blank('circular');
		
		shortcode += ']';
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, escapeHtml(shortcode));
		tinyMCEPopup.close();
	});

	// handle single link gallery insert
	$('#single_link_gallery_insert').click(function() {
		var album_name = $('#single_link_gallery_album_name').val();
		if (album_name == '') {
			tinyMCE.activeEditor.windowManager.alert('album_name must not be blank');
			return;
		}
		
		var shortcode = '[narnoo_operator_single_link_gallery album_name="' + album_name + '"';

		function add_to_shortcode_if_not_blank(attr_name) {
			attr_val = $('#single_link_gallery_' + attr_name).val();
			if (attr_val != '') {
				shortcode += ' ' + attr_name + '="' + escapeHtml(attr_val) + '"';
			}
		}
		
		add_to_shortcode_if_not_blank('width');
		add_to_shortcode_if_not_blank('height');
		
		shortcode += ']';
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, escapeHtml(shortcode));
		tinyMCEPopup.close();
	});
});

