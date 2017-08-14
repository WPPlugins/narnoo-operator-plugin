<?php
/**
 * Narnoo Operator - Images table for the Narnoo Library tab (Add/Insert Media window).
 **/
class Narnoo_Operator_Library_Images_Table extends WP_List_Table {
	function column_default( $item, $column_name ) {
		switch( $column_name ) { 
			case 'caption':
			case 'entry_date':
			case 'image_id':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	function column_show_hide( $item ){
		return '<a href="#" class="narnoo-show-hide" data-id="' . $item['image_id'] . '">Show</a>';
	}
	
	function column_thumbnail_image( $item ) {    
		return "<img src='" . $item['thumbnail_image'] . "' />";
	}
	
	function get_columns() {
		return array(
			'thumbnail_image'	=> __( 'Thumbnail', NARNOO_OPERATOR_I18N_DOMAIN ),
			'caption'			=> __( 'Caption', NARNOO_OPERATOR_I18N_DOMAIN ),
			'entry_date'		=> __( 'Entry Date', NARNOO_OPERATOR_I18N_DOMAIN ),
			'image_id'			=> __( 'Image ID', NARNOO_OPERATOR_I18N_DOMAIN ),
			'show_hide'			=> ''
		);
	}
	
	/**
	 * Request the current page data from Narnoo API server.
	 **/
	function get_current_page_data() {
		$data = array( 'total_pages' => 1, 'items' => array() );
		
		$list = null;
		$current_page = $this->get_pagenum();
		$request = Narnoo_Operator_Helper::init_api();
		if ( ! is_null( $request ) ) {
			try {
				$list = $request->getImages( $current_page );
				if ( ! is_array( $list->operator_images ) ) {
					throw new Exception( sprintf( __( "Error retrieving images. Unexpected format in response page #%d.", NARNOO_OPERATOR_I18N_DOMAIN ), $current_page ) );
				}
			} catch ( Exception $ex ) {
				Narnoo_Operator_Helper::show_api_error( $ex );
			} 
		}
		
		if ( ! is_null( $list ) ) {
			$data['total_pages'] = max( 1, intval( $list->total_pages ) );
			foreach ( $list->operator_images as $image ) {
				$item['thumbnail_image'] = $image->thumb_image_path;
				$item['caption'] = $image->image_caption;
				$item['entry_date'] = $image->entry_date;
				$item['image_id'] = $image->image_id;
				$item['medium_image'] = $image->preview_image_path;
				$item['large_image'] = $image->large_image_path;
				$data['items'][] = $item;
			}
		}

		return $data;
	}

	/**
	 * Prepare the items for display.
	 **/
	function prepare_items() {		
		$this->_column_headers = $this->get_column_info();
			
		$data = $this->get_current_page_data();
		$this->items = $data['items'];
		
		$this->set_pagination_args( array(
			'total_items'	=> count( $data['items'] ),
			'total_pages'	=> $data['total_pages']
		) );  
	}
	
	/**
	 * Append Javascript to show/hide info rows and insert into columns.
	 **/
	function display() {
		?>
		<script type="text/javascript">
			function escapeHtml(unsafe) {
				return unsafe
					.replace(/&/g, "&amp;")
					.replace(/</g, "&lt;")
					.replace(/>/g, "&gt;")
					.replace(/"/g, "&quot;")
					.replace(/'/g, "&#039;");
			}

			jQuery("document").ready(function($) {
				$( '<style type="text/css">#media-upload table{ width: 100% !important; } #media-upload .align-item { float: left; display: inline-block; width: 100px; } #media-upload .image-size-item { display: inline-block; width: 100px; } #media-upload input[type=text], #media-upload textarea { width: 98%; } #media-upload .narnoo-insert-info-row td, #media-upload .narnoo-insert-info-row th { border-top-width: 0px !important; border-bottom-width: 0px !important; }</style>' ).appendTo( $('head') );

				$('.narnoo-show-hide').click(function(e, ui) {
					e.preventDefault();
					
					$infoRows = $('.narnoo-insert-info-row-' + $(this).attr('data-id') );
					if ($(this).text() == 'Hide') {
						// hide the info rows
						$infoRows.hide();
						$(this).text('Show');
					} else {
						// show the info rows
						$infoRows.show();
						$(this).text('Hide');
					}
					
					return false;
				});
			
				function insertImage(url, alt, title, caption, align, id, size) {
					// prepare image tag/caption shortcode and send to editor
					var win = window.dialogArguments || opener || parent || top;
					var alignClass = '';
					if (caption === '') {
						alignClass = ' class="align' + align + '"'; 
					}
					
					var $iconProcess = $('#narnoo-icon-process-' + id);
					var $iconFail = $('#narnoo-icon-fail-' + id);
					var $processMsg = $('#narnoo-process-msg-' + id);

					$processMsg.html('<?php _e( 'Loading image...', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');
					$iconProcess.show();
					$iconFail.hide();

					// need to load image to get width and height
					var img = new Image();
					
					img.onload = function() {
						var img_tag = '<img src="' + url + '" alt="' + alt + '" title="' + title + '"' + alignClass + ' height="' + this.height + '" width="' + this.width + '"/>';
						if (caption !== '') {
							img_tag = '[caption id="narnoo-caption-' + id + '" align="align' + align + '" width="' + this.width + '"]' + img_tag + ' ' + caption + '[/caption]';
						}
						win.send_to_editor(img_tag);
					}
					var cancel_imgload = function() {
						$iconProcess.hide();
						$iconFail.show();
						htmlMsg = '<?php printf( __( 'Failed to load %s image <a target="_blank" href="%s">%s</a>. Please check the link and try again.', NARNOO_OPERATOR_I18N_DOMAIN ), '[size]', '[url]', '[urltext]' ); ?>';
						htmlMsg = htmlMsg.replace('[size]', size);
						htmlMsg = htmlMsg.replace('[url]', url);
						htmlMsg = htmlMsg.replace('[urltext]', url);
						$processMsg.html(htmlMsg);
					};
					img.onerror = cancel_imgload;
					img.onabort = cancel_imgload;
					img.src = url;
				}				
				
				$('.narnoo-insert-img').click(function() {
					var image_id = $(this).data("id");
					$size_elem = $('input:radio[name=narnoo-image-size-' + image_id + ']:checked');
					var url = $size_elem.data('url');
					var size = $size_elem.val();
					var title = escapeHtml($('input:text[name=narnoo-title-' + image_id + ']').val());
					var alt = escapeHtml($('input:text[name=narnoo-image-alt-' + image_id + ']').val());
					var caption = escapeHtml($('textarea[name=narnoo-caption-' + image_id + ']').val());
					var align = $('input:radio[name=narnoo-align-' + image_id + ']:checked').val();
					insertImage(url, alt, title, caption, align, image_id, size);
				});
			});
		</script>
		<?php
		
		parent::display();
	}
	
	/**
	 * For every row item, add additional initially-hidden row for Inserting image.
	 **/
	function single_row( $item ) {
		parent::single_row( $item );
		
		?>
		<tr style="display:none;" class="narnoo-insert-info-row narnoo-insert-info-row-<?php echo $item['image_id']; ?> post_title form-required">
			<th valign="top" scope="row" class="label"><label><span class="alignleft"><?php _e( 'Title', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></span><span class="alignright"><abbr title="required" class="required">*</abbr></span><br class="clear"></label></th>
			<td class="field" colspan="<?php echo count( $this->get_columns() ) - 1; ?>"><input type="text" class="text" name="narnoo-title-<?php echo $item['image_id']; ?>" value="Narnoo Image #<?php echo $item['image_id']; ?>" aria-required="true"></td>
		</tr>
		<tr style="display:none;" class="narnoo-insert-info-row narnoo-insert-info-row-<?php echo $item['image_id']; ?> image_alt">
			<th valign="top" scope="row" class="label"><label><span class="alignleft"><?php _e( 'Alternate Text', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></span><br class="clear"></label></th>
			<td class="field" colspan="<?php echo count( $this->get_columns() ) - 1; ?>"><input type="text" class="text" name="narnoo-image-alt-<?php echo $item['image_id']; ?>" value=""><p class="help"><?php _e( 'Alt text for the image, e.g. "The Mona Lisa"', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></p></td>
		</tr>
		<tr style="display:none;" class="narnoo-insert-info-row narnoo-insert-info-row-<?php echo $item['image_id']; ?> post_excerpt">
			<th valign="top" scope="row" class="label"><label><span class="alignleft"><?php _e( 'Caption', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></span><br class="clear"></label></th>
			<td class="field" colspan="<?php echo count( $this->get_columns() ) - 1; ?>"><textarea name="narnoo-caption-<?php echo $item['image_id']; ?>" id="narnoo-caption-<?php echo $item['image_id']; ?>"><?php echo $item['caption']; ?></textarea></td>
		</tr>
		<tr style="display:none;" class="narnoo-insert-info-row narnoo-insert-info-row-<?php echo $item['image_id']; ?> align">
			<th valign="top" class="label"><label><span class="alignleft"><?php _e( 'Alignment', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></span><br class="clear"></label></th>
			<td class="field" colspan="<?php echo count( $this->get_columns() ) - 1; ?>">
				<div class="align-item"><input type="radio" name="narnoo-align-<?php echo $item['image_id']; ?>" id="narnoo-align-none-<?php echo $item['image_id']; ?>" value="none" checked="checked"><label for="narnoo-align-none-<?php echo $item['image_id']; ?>" class="align image-align-none-label"><?php _e( 'None', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></label></div>
				<div class="align-item"><input type="radio" name="narnoo-align-<?php echo $item['image_id']; ?>" id="narnoo-align-left-<?php echo $item['image_id']; ?>" value="left"><label for="narnoo-align-left-<?php echo $item['image_id']; ?>" class="align image-align-left-label"><?php _e( 'Left', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></label></div>
				<div class="align-item"><input type="radio" name="narnoo-align-<?php echo $item['image_id']; ?>" id="narnoo-align-center-<?php echo $item['image_id']; ?>" value="center"><label for="narnoo-align-center-<?php echo $item['image_id']; ?>" class="align image-align-center-label"><?php _e( 'Center', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></label></div>
				<div class="align-item"><input type="radio" name="narnoo-align-<?php echo $item['image_id']; ?>" id="narnoo-align-right-<?php echo $item['image_id']; ?>" value="right"><label for="narnoo-align-right-<?php echo $item['image_id']; ?>" class="align image-align-right-label"><?php _e( 'Right', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></label></div>
			</td>
		</tr>
		<tr style="display:none;" class="narnoo-insert-info-row narnoo-insert-info-row-<?php echo $item['image_id']; ?> image-size">
			<th valign="top" class="label"><label><span class="alignleft"><?php _e( 'Size', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></span><br class="clear"></label></th>
			<td class="field" colspan="<?php echo count( $this->get_columns() ) - 1; ?>">
				<?php
					$disabled = 'disabled="disabled"';
					$thumbnail_disabled = empty( $item['thumbnail_image'] ) ? $disabled : '' ;
					$medium_disabled = empty( $item['medium_image'] ) ? $disabled : '';
					$large_disabled = empty( $item['large_image'] ) ? $disabled : '';
				?>
				<div class="image-size-item"><input <?php echo $thumbnail_disabled; ?> data-url="<?php echo $item['thumbnail_image']; ?>" type="radio" name="narnoo-image-size-<?php echo $item['image_id']; ?>" id="narnoo-image-thumbnail-size-<?php echo $item['image_id']; ?>" value="<?php _e( 'thumbnail', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?>"><label for="narnoo-image-thumbnail-size-<?php echo $item['image_id']; ?>"><?php _e( 'Thumbnail', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></label></div>
				<div class="image-size-item"><input <?php echo $medium_disabled; ?> data-url="<?php echo $item['medium_image']; ?>" type="radio" name="narnoo-image-size-<?php echo $item['image_id']; ?>" id="narnoo-image-medium-size-<?php echo $item['image_id']; ?>" value="<?php _e( 'medium-sized', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?>" checked="checked"><label for="narnoo-image-medium-size-<?php echo $item['image_id']; ?>"><?php _e( 'Medium', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></label></div>
				<div class="image-size-item"><input <?php echo $large_disabled; ?> data-url="<?php echo $item['large_image']; ?>" type="radio" name="narnoo-image-size-<?php echo $item['image_id']; ?>" id="narnoo-image-large-size-<?php echo $item['image_id']; ?>" value="<?php _e( 'large-sized', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?>"><label for="narnoo-image-large-size-<?php echo $item['image_id']; ?>"><?php _e( 'Large', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?></label></div>
			</td>
		</tr>
		<tr style="display:none;" class="narnoo-insert-info-row narnoo-insert-info-row-<?php echo $item['image_id']; ?>">
			<td colspan="<?php echo count( $this->get_columns() ); ?>" style="border-bottom-width: 1px !important;">
				<input data-id="<?php echo $item['image_id']; ?>" type='button' class='narnoo-insert-img button' value='<?php esc_attr_e( 'Insert into Post', NARNOO_DISTRIBUTOR_I18N_DOMAIN ); ?>' />
				<br /><br />
				<img style="display:none;" id="narnoo-icon-process-<?php echo $item['image_id']; ?>" src="<?php echo admin_url(); ?>images/wpspin_light.gif" />
				<img style="display:none;" id="narnoo-icon-fail-<?php echo $item['image_id']; ?>" src="<?php echo admin_url(); ?>images/no.png" />
				<img style="display:none;" id="narnoo-icon-success-<?php echo $item['image_id']; ?>" src="<?php echo admin_url(); ?>images/yes.png" />
				<span id="narnoo-process-msg-<?php echo $item['image_id']; ?>"></span>
			</td>
		</tr>
		<?php
	}
}    