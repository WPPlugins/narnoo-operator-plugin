<?php
/**
 * Helper functions used throughout plugin.
 **/
class Narnoo_Operator_Helper {	
	/**
	 * Returns true if current Wordpress version supports wp_enqueue_script in HTML body (3.3 and above); false otherwise.
	 **/
	static function wp_supports_enqueue_script_in_body() {
		global $wp_version;
		$version = explode( '.', $wp_version );
		if ( intval( $version[0] < 3 ) || ( intval( $version[0] ) == 3 && intval( $version[1] ) < 3 ) ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Show generic notification message.
	 **/
	static function show_notification( $msg ) {
		echo '<div class="updated"><p>' . $msg . '</p></div>';
	}

	/**
	 * Show generic error message.
	 **/
	static function show_error( $msg ) {
		echo '<div class="error"><p>' . $msg . '</p></div>';
	}
	
	/**
	 * In case of API error (e.g. invalid API keys), display error message.
	 **/
	static function show_api_error( $ex, $prefix_msg = '' ) {
		$error_msg = $ex->getMessage();
		$msg = '<strong>' . __( 'Narnoo API error:', NARNOO_OPERATOR_I18N_DOMAIN ) . '</strong> ' . $prefix_msg . ' ' . $error_msg;
		if ( false !== strchr( strtolower( $error_msg ), ' authentication fail' ) ) { 
			$msg .= '<br />' . sprintf( 
				__( 'Please ensure your API settings in the <strong><a href="%1$s">Settings->Narnoo API</a></strong> page are correct and try again.', NARNOO_OPERATOR_I18N_DOMAIN ),
				NARNOO_OPERATOR_SETTINGS_PAGE
			);
		}
		self::show_error( $msg );
	}
	
	/**
	 * Inits and returns operator request object with user's access and secret keys.
	 * If either app or secret key is empty, returns null.
	 **/
	static function init_api() {
		$options = get_option( 'narnoo_operator_settings' );
		
		if ( empty( $options['access_key'] ) || empty( $options['secret_key'] ) ) {
			return null;
		}
		
		$request = new OperatorNarnooRequest();
		$request->setAuth ( $options['access_key'], $options['secret_key'] );
		$request->sandbox = false;
		
		return $request;
	}
	
	/**
	 * Prints out the HTML/Javascript for a single item that will be processed via AJAX.
	 **/
	static function print_ajax_script_body( $id, $func_name, $params_array ) {
		?>
		<li>
			<img id="narnoo-icon-process-<?php echo $id; ?>" src="<?php echo admin_url(); ?>images/wpspin_light.gif" /> 
			<img style="display:none;" id="narnoo-icon-success-<?php echo $id; ?>" src="<?php echo admin_url(); ?>images/yes.png" /> 
			<img style="display:none;" id="narnoo-icon-fail-<?php echo $id; ?>" src="<?php echo admin_url(); ?>images/no.png" /> 
			<span><?php echo __( 'Item ID:', NARNOO_OPERATOR_I18N_DOMAIN ) . ' ' . $id . '...'; ?></span>
			<strong><span id="narnoo-item-<?php echo $id; ?>"><?php _e( 'Processing...', NARNOO_OPERATOR_I18N_DOMAIN ); ?></span></strong>
		</li>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: { action: 'narnoo_operator_api_request', 
							func_name: '<?php echo $func_name; ?>', 
							param_array: [ <?php echo "'" . implode( "','", $params_array ) . "'"; ?> ] },
					timeout: 60000,
					dataType: "json",
					success: 
						function(response, textStatus, jqXHR) {     
							$('#narnoo-icon-process-<?php echo $id; ?>').hide();
							processed++;
							
							if (response['success'] === 'success' && response['msg']) {
								$('#narnoo-icon-success-<?php echo $id; ?>').show();
								$('#narnoo-item-<?php echo $id; ?>').html(response['msg']);
								success++;										
							} else {
								$('#narnoo-icon-fail-<?php echo $id; ?>').show();
								$('#narnoo-item-<?php echo $id; ?>').html('<?php _e( 'AJAX error: Unexpected response', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');										
							}
							
							check_complete($);
						},
					error: 
						function(jqXHR, textStatus, errorThrown) {
							$('#narnoo-icon-process-<?php echo $id; ?>').hide();
							$('#narnoo-icon-fail-<?php echo $id; ?>').show();
							processed++;
		
							if (textStatus === 'timeout') {   // server timeout
								$('#narnoo-item-<?php echo $id; ?>').html('<?php _e( 'AJAX error: Server timeout', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');
							} else {                  // other error
								$('#narnoo-item-<?php echo $id; ?>').html(jqXHR.responseText);
							}
							
							check_complete($);
						}
				});
			});
		</script>
		<?php
	}
	
	/**
	 * Prints out the footer HTML/Javascript needed for AJAX processing.
	 **/
	static function print_ajax_script_footer( $total_count, $back_button_text, $extra_button_text = '' ) {
		?>
		<div class="narnoo-completed" style="display:none;">
			<br />
			<p><strong><?php echo sprintf( __( "Processing completed. %s of %d item(s) successful.", NARNOO_OPERATOR_I18N_DOMAIN ), '<span id="narnoo-success-count"></span>', $total_count ); ?></strong></p>
		</div>
		<p class="submit narnoo-completed" style="display:none;">
			<?php 
			if ( ! empty( $extra_button_text ) ) {
				?><input type="submit" name="extra_button" id="extra_button" class="button-secondary" value="<?php echo $extra_button_text; ?>" /><?php
			}
			?>
			<input type="submit" name="back" id="cancel" class="button-secondary" value="<?php echo $back_button_text; ?>" />
		</p>
		<script type="text/javascript">
			var success = 0; 
			var processed = 0;
			function check_complete($) {
				if (processed >= <?php echo $total_count; ?>) {
					$('#narnoo-success-count').text(success);
					$('.narnoo-completed').show();
				}							
			}
		</script>
		<?php
	}
	
	/**
	 * Returns HTML and Javascript required for selection of album page/album names, and querying of
	 * album pages via AJAX.
	 **/
	static function get_album_select_html_script( $albums, $total_pages, $current_album_page, $current_album_name ) {		
		ob_start();
		?>
		<input type="hidden" id="narnoo_album_name" name="narnoo_album_name" value="<?php echo esc_attr( $current_album_name ); ?>" />
		<select name="narnoo_album_page" id="narnoo-album-page-select">
		<?php
		for ( $i = 0; $i < $total_pages; $i++ ) {
			$selected = '';
			if ( ( $i + 1 ) === $current_album_page ) {
				$selected = 'selected="selected"';
			}
			?><option value="<?php echo $i + 1; ?>"<?php echo $selected;?>><?php printf( __( 'Album page %d', NARNOO_OPERATOR_I18N_DOMAIN ), ($i + 1) ); ?></option><?php
		}
		?>
		</select>
		
		<?php
		// prepare "select album" element for every page
		for ( $i = 0; $i < $total_pages; $i++ ) {
			$is_current_page = ( $current_album_page === ( $i + 1 ) );
	
			$hidden = ' data-loaded="yes"';
			$disabled = '';
			if ( ! $is_current_page ) {
				$hidden = ' data-loaded="no" style="display:none;"';
				$disabled = ' disabled="disabled"';
			}
			?>
			<span class="narnoo-album-select-span" id="narnoo-album-select-span-<?php echo $i + 1; ?>"<?php echo $hidden; ?>>
				<span class="narnoo-album-select-span-process" style="display:none;">
					<img class="narnoo-icon-process" src="<?php echo admin_url(); ?>images/wpspin_light.gif" />
					<img class="narnoo-icon-fail" src="<?php echo admin_url(); ?>images/no.png" />
					<span class="narnoo-album-select-msg"></span>
				</span>
				<select class="narnoo-album-select" name="narnoo_album_id"<?php echo $disabled; ?>>
				<?php
				foreach ( $albums as $album ) {
					$album_name = stripslashes( $album->album_name );
					$selected = '';
					if ( $current_album_name === $album_name ) {
						$selected = ' selected="selected"';
					}
					?><option value="<?php echo $album->album_id; ?>"<?php echo $selected; ?>><?php echo esc_html( $album_name ); ?></option><?php
				}
				?>
				</select>
			</span>
		<?php
		}
		?>
		<script type="text/javascript">
			function updateQueryStringParameter(uri, key, value) {
				var re = new RegExp("([?|&])" + key + "=.*?(&|$)", "i");
				separator = uri.indexOf('?') !== -1 ? "&" : "?";
				value = encodeURIComponent(value);
				if (uri.match(re)) {
					return uri.replace(re, '$1' + key + "=" + value + '$2');
				}
				else {
					return uri + separator + key + "=" + value;
				}
			}
			
			jQuery('document').ready(function($) {
				$('#album_select_button').click(function(e, ui) {
					page = $('#narnoo-album-page-select').val();
					$selected = $('#narnoo-album-select-span-' + page).find('.narnoo-album-select option:selected');
					$('#narnoo_album_name').val($selected.html());
					
					// rebuild form action query string to ensure album name, id and page are in sync
					$form = $('#narnoo-albums-form');
					if ($form.length > 0) {
						new_query = $form.attr('action');
						new_query = updateQueryStringParameter(new_query, 'album', $selected.val());
						new_query = updateQueryStringParameter(new_query, 'album_name', $selected.html() );
						new_query = updateQueryStringParameter(new_query, 'album_page', page);
						$form.attr('action', new_query);
					}
				});
				
				$('#narnoo-album-page-select').change(function(e, ui) {
					var $this = $(this);
					var page = $(this).val();
					
					$('.narnoo-album-select').attr("disabled", "disabled").hide();
					$('.narnoo-album-select-span').hide();	
	
					var $album_select_span = $('#narnoo-album-select-span-' + page);
					var $album_select = $album_select_span.find('.narnoo-album-select');
					var $album_select_span_process = $album_select_span.find('.narnoo-album-select-span-process');
					var $album_select_icon_fail = $album_select_span.find('.narnoo-icon-fail');
					var $album_select_icon_process = $album_select_span.find('.narnoo-icon-process');
					var $album_select_msg = $album_select_span.find('.narnoo-album-select-msg');
					
					if ($album_select_span.data('loaded') === 'yes') {
						$album_select_span.find('.narnoo-album-select').removeAttr("disabled").show();
						$("#album_select_button").removeAttr('disabled');
					} else {
						$album_select_span_process.show();
						$("#album_select_button").attr('disabled', 'disabled');
	
						if ($album_select_span.data('loaded') === 'no') {
							$album_select_span.data("loaded", "loading");
							$album_select_icon_fail.hide();
							$album_select_icon_process.show();
							$album_select_msg.html("<?php _e( 'Retrieving album names...', NARNOO_OPERATOR_I18N_DOMAIN ); ?>");
							
							// request album names via AJAX from server
							$.ajax({
								type: 'POST',
								url: ajaxurl,
								data: { action: 'narnoo_operator_api_request', 
										func_name: 'getAlbums', 
										param_array: [ page ] },
								timeout: 60000,
								dataType: "json",
								success: 
									function(response, textStatus, jqXHR) {     
										$album_select_icon_process.hide();
										
										error_msg = "<?php _e( 'AJAX error: Unexpected response', NARNOO_OPERATOR_I18N_DOMAIN ); ?>";
										if (response['success'] === 'success' && response['msg'] && response['response'] && response['response']['operator_albums']) {
											items = response['response']['operator_albums'];
											if (items.length === 0) {
												error_msg = "<?php _e( 'No albums found!' ); ?>";
											} else {
												// populate the select element with album names
												options = '';
												for (index in items) {
													item = items[index];
													options += '<option value="' + item['album_id'] + '">' + item['album_name'] + '</option>';
												}
												$album_select.html(options);
												
												$album_select_msg.html('');	
												$album_select_span.data("loaded", "yes");
												if (page === $this.val()) {	// ensure the current page is still selected
													$album_select.removeAttr('disabled').show();
													$("#album_select_button").removeAttr('disabled');
												}
												return;
											}
										}
										
										$album_select_icon_fail.show();
										$album_select_span.data("loaded", "no");
										$album_select_msg.html(error_msg);
									},
								error: 
									function(jqXHR, textStatus, errorThrown) {
										$album_select_icon_process.hide();
										$album_select_icon_fail.show();
										$album_select_span.data("loaded", "no");
							
										if (textStatus === 'timeout') {   // server timeout
											$album_select_msg.html('<?php _e( 'AJAX error: Server timeout', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');
										} else {                  // other error
											$album_select_msg.html(jqXHR.responseText);
										}
									}
							});
						}
					}
					
					$album_select_span.show();
				});
			});
		</script>
		<?php
		
		return ob_get_clean();
	}
	
	/**
	 * Handling of AJAX request fatal error.
	 **/
	static function ajax_fatal_error( $sErrorMessage = '' ) {
		header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
		die( $sErrorMessage );
	}
	
	/**
	 * Handling of AJAX API requests. 
	 **/
	static function ajax_api_request() {
		if ( ! isset( $_POST['func_name'] ) || ! isset( $_POST['param_array'] ) ) {
			self::ajax_fatal_error( __( 'AJAX error: Missing arguments.', NARNOO_OPERATOR_I18N_DOMAIN ) );
		}
		$func_name = $_POST['func_name'];
		$param_array = $_POST['param_array'];
		
		// init the API request object
		$request = Narnoo_Operator_Helper::init_api();
		if ( is_null( $request ) ) {
			self::ajax_fatal_error( __( 'Narnoo API error: Incorrect API keys specified.', NARNOO_OPERATOR_I18N_DOMAIN ) ); 
		}
		
		// attempt to call API function with specified params
		$response = array();
		try {
			$response['response'] = call_user_func_array( array( $request, $func_name), $param_array );
			if ( false === $response['response'] ) {
				self::ajax_fatal_error( __( 'AJAX error: Invalid function or arguments specified.', NARNOO_OPERATOR_I18N_DOMAIN ) );
			}
			$response['success'] = 'success';
			
			// set success message depending on API function called
			$response['msg'] = __( 'Success!', NARNOO_OPERATOR_I18N_DOMAIN );
			$item = $response['response'];
			if ( ! is_null( $item ) ) {
				if ( isset( $item->success ) && isset( $item->success->successMessage ) ) {
					// copy success message directly from API response
					$response['msg'] = $item->success->successMessage;
				}
				if ( 'downloadBrochure' === $func_name ) {
					$item->download_brochure_to_pdf_path = uncdata( $item->download_brochure_to_pdf_path );
					$response['msg'] .= ' <a target="_blank" href="' . $item->download_brochure_to_pdf_path . '">' . __( 'Download PDF brochure', NARNOO_OPERATOR_I18N_DOMAIN ) . '</a>';
				} else if ( 'downloadImage' === $func_name ) {
					$item->download_image_link = uncdata( $item->download_image_link );
					$response['msg'] .= ' <a target="_blank" href="' . $item->download_image_link . '">' . __( 'Download image link', NARNOO_OPERATOR_I18N_DOMAIN ) . '</a>';
				} else if ( 'downloadVideo' === $func_name ) {
					$item->download_video_stream_path = uncdata( $item->download_video_stream_path );
					$item->original_video_path = uncdata( $item->original_video_path );
					$response['msg'] .= ' <a target="_blank" href="' . $item->download_video_stream_path . '">' . __( 'Download video stream path', NARNOO_OPERATOR_I18N_DOMAIN ) . '</a>';
					$response['msg'] .= ' <a target="_blank" href="' . $item->original_video_path . '">' . __( 'Original video path', NARNOO_OPERATOR_I18N_DOMAIN ) . '</a>';
				} else if ( 'getAlbums' === $func_name ) {
					// ensure each album name has slashes stripped
					$albums = $item->operator_albums;
					if ( is_array( $albums ) ) {
						foreach ( $albums as $album ) {
							$album->album_name = stripslashes( $album->album_name );
						}
					}
				} else if ( 'getProductTextWords' === $func_name ) {
					$text = $item->text;
					if ( is_null( $text ) ) {
						throw new Exception( __( 'Unexpected response: product text is null.', NARNOO_OPERATOR_I18N_DOMAIN ) );
					}
					ob_start();
					?>
					<p>
						<strong>Product 50 words</strong><br /><?php echo $text->word_50; ?><br />
						<br />
						<strong>Product 100 words</strong><br /><?php echo $text->word_100; ?><br />
						<br />
						<strong>Product 150 words</strong><br /><?php echo $text->word_150; ?>
					</p>
					<?php
					$response['msg'] = ob_get_clean();
				}
			}
		} catch ( Exception $ex ) {
			self::ajax_fatal_error( __( 'Narnoo API error: ', NARNOO_OPERATOR_I18N_DOMAIN ) . $ex->getMessage() );
		}
		
		echo json_encode( $response );
		die();
	}
}