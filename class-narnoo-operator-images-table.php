<?php
/**
 * Narnoo Operator - Images table.
 **/
class Narnoo_Operator_Images_Table extends WP_List_Table {
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

	function column_thumbnail_image( $item ) {    
		$actions = array(
			'add_to_album'  => sprintf( 
									'<a href="?%s">%s</a>', 
									build_query( 
										array(
											'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '',
											'paged' => $this->get_pagenum(),
											'action' => 'add_to_album', 
											'images[]' => $item['image_id'], 
											'url' . $item['image_id'] => urlencode( str_replace( '.', '%2E', $item['thumbnail_image'] ) )
										)
									),
									__( 'Add to album', NARNOO_OPERATOR_I18N_DOMAIN ) 
								),
			'delete'    	=> sprintf( 
									'<a href="?%s">%s</a>', 
									build_query( 
										array(
											'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '',
											'paged' => $this->get_pagenum(),
											'action' => 'delete', 
											'images[]' => $item['image_id'], 
											'url' . $item['image_id'] => urlencode( str_replace( '.', '%2E', $item['thumbnail_image'] ) )
										)
									),
									__( 'Delete' ) 
								),
			'download'    	=> sprintf( 
									'<a href="?%s">%s</a>', 
									build_query( 
										array(
											'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '',
											'paged' => $this->get_pagenum(),
											'action' => 'download', 
											'images[]' => $item['image_id'], 
										)
									),
									__( 'Download', NARNOO_OPERATOR_I18N_DOMAIN ) 
								),
		);
		return sprintf( 
			'<input type="hidden" name="url%1$s" value="%2$s" /> %3$s <br /> %4$s', 
			$item['image_id'],
			$item['thumbnail_image'],
			"<img src='" . $item['thumbnail_image'] . "' />", 
			$this->row_actions($actions) 
		);
	}
	
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="images[]" value="%s" />', $item['image_id']
		);    
	}

	function get_columns() {
		return array(
			'cb'				=> '<input type="checkbox" />',
			'thumbnail_image'	=> __( 'Thumbnail', NARNOO_OPERATOR_I18N_DOMAIN ),
			'caption'			=> __( 'Caption', NARNOO_OPERATOR_I18N_DOMAIN ),
			'entry_date'		=> __( 'Entry Date', NARNOO_OPERATOR_I18N_DOMAIN ),
			'image_id'			=> __( 'Image ID', NARNOO_OPERATOR_I18N_DOMAIN )
		);
	}
	
	function get_bulk_actions() {
		$actions = array(
			'add_to_album'	=> __( 'Add to album', NARNOO_OPERATOR_I18N_DOMAIN ),
			'delete'    	=> __( 'Delete' ),
			'download'		=> __( 'Download', NARNOO_OPERATOR_I18N_DOMAIN )
		);
		return $actions;
	}

	/**
	 * Process actions and returns true if the rest of the table SHOULD be rendered.
	 * Returns false otherwise.
	 **/
	function process_action() {
		if ( isset( $_REQUEST['cancel'] ) ) {
			Narnoo_Operator_Helper::show_notification( __( 'Action cancelled.', NARNOO_OPERATOR_I18N_DOMAIN ) );
			return true;
		}
		
		if ( isset( $_REQUEST['back'] ) ) {
			return true;
		}
		
		if ( isset( $_REQUEST['extra_button'] ) ) {
			// redirect to album page if user clicked "View album" after adding images
			if ( isset( $_REQUEST['view_album'] ) && $_REQUEST['view_album'] === 'view_album' ) {
				?>
				<p><img src="<?php echo admin_url(); ?>images/wpspin_light.gif" /> <?php printf( __( "Redirecting to album '%s'...", NARNOO_OPERATOR_I18N_DOMAIN ), htmlspecialchars( stripslashes( $_REQUEST['narnoo_album_name'] ) ) ); ?></p>
				<script type="text/javascript">
				window.location = "admin.php?page=narnoo-operator-albums&album=<?php echo isset( $_REQUEST['narnoo_album_id'] ) ? $_REQUEST['narnoo_album_id'] : ''; ?>&album_name=<?php echo isset( $_REQUEST['narnoo_album_name'] ) ? urlencode( stripslashes( $_REQUEST['narnoo_album_name'] ) ) : ''; ?>&album_page=<?php echo isset( $_REQUEST['narnoo_album_page'] ) ? $_REQUEST['narnoo_album_page'] : ''; ?>";
				</script>
				<?php
				exit();
			}				
		}
				
		$action = $this->current_action();
		if ( false !== $action ) {
			$image_ids = isset( $_REQUEST['images'] ) ? $_REQUEST['images'] : array();
			$num_ids = count( $image_ids );
			if ( empty( $image_ids ) || ! is_array( $image_ids ) || $num_ids === 0 ) {
				return true;				
			}
			
			switch ( $action ) {
			
				// confirm add to album
				case 'add_to_album':
					// retrieve list of albums		
					$list = null;
					$request = Narnoo_Operator_Helper::init_api();
					if ( ! is_null( $request ) ) {
						try {
							$list = $request->getAlbums();
							if ( ! is_array( $list->operator_albums ) ) {
								throw new Exception( sprintf( __( "Error retrieving albums. Unexpected format in response page #%d.", NARNOO_OPERATOR_I18N_DOMAIN ), $current_page ) );
							}
						} catch ( Exception $ex ) {
							Narnoo_Operator_Helper::show_api_error( $ex );
						} 				
					}
							
					// no albums retrieved
					if ( is_null( $list ) ) {
						return true;
					}
					if ( count( $list->operator_albums ) === 0 ) {
						Narnoo_Operator_Helper::show_error( sprintf( __( '<strong>ERROR:</strong> No albums found. Please <strong><a href="%s">create an album</a></strong> first!', NARNOO_OPERATOR_I18N_DOMAIN ), "?" . build_query( array( 'page' => 'narnoo-operator-albums', 'action' => 'create' ) ) ) );
						return true;
					}

					$total_pages = max( 1, intval( $list->total_pages ) );
					
					?>
					<h3><?php _e( 'Confirm add to album', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<?php
					
					foreach ( $list->operator_albums as $album ) { ?>
						<input type="hidden" name="album<?php echo $album->album_id; ?>" value="<?php echo esc_attr( $album->album_name ); ?>" /><?php
					}
					?>
					<p>
						<?php printf( __( 'Please select an album to add the following %d image(s) to:', NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?>
						<?php echo Narnoo_Operator_Helper::get_album_select_html_script( $list->operator_albums, $total_pages, 1, '' ); ?>
					</p>
					<ol>
					<?php 
					foreach ( $image_ids as $id ) { 
						?>
						<input type="hidden" name="images[]" value="<?php echo $id; ?>" />
						<li><span><?php echo __( 'Image ID:', NARNOO_OPERATOR_I18N_DOMAIN ) . ' ' . $id; ?></span><span><img style="vertical-align: middle; padding-left: 20px;" src="<?php echo ( isset( $_REQUEST[ 'url' . $id ] ) ? $_REQUEST[ 'url' . $id ] : '' ); ?>" /></span></li>
						<?php 
					} 
					?>
					</ol>
					<input type="hidden" name="action" value="do_add_to_album" />
					<p class="submit">
						<input type="submit" name="submit" id="album_select_button" class="button-secondary" value="<?php _e( 'Confirm Add to Album', NARNOO_OPERATOR_I18N_DOMAIN ); ?>" />
						<input type="submit" name="cancel" id="cancel" class="button-secondary" value="<?php _e( 'Cancel' ); ?>" />
					</p>
					<?php
					
					return false;
					
				// perform actual add to album
				case 'do_add_to_album':
					if ( ! isset( $_POST['narnoo_album_id'] ) ) {
						return true;
					}
					$album_id = $_POST['narnoo_album_id'];
					$album_name =  isset( $_POST['narnoo_album_name'] ) ? stripslashes( $_POST['narnoo_album_name'] ) : '';
					$album_page = isset( $_POST['narnoo_album_page'] ) ? $_POST['narnoo_album_page'] : '';
					
					?>
					<h3><?php _e( 'Add to album', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<p><?php echo sprintf( __( "Adding the following %s image(s) to album '%s' (ID %d):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids, $album_name, $album_id ); ?></p>
					<input type="hidden" name="view_album" value="view_album" />
					<input type="hidden" name="narnoo_album_id" value="<?php echo $album_id; ?>" />
					<input type="hidden" name="narnoo_album_name" value="<?php echo esc_attr( $album_name ); ?>" />
					<input type="hidden" name="narnoo_album_page" value="<?php echo $album_page; ?>" />
					<ol>
					<?php
					foreach( $image_ids as $id ) {
						Narnoo_Operator_Helper::print_ajax_script_body( $id, 'addToAlbum', array( $id, $album_id ) );
					}
					?>
					</ol>
					<?php 
					Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to images', NARNOO_OPERATOR_I18N_DOMAIN ), __( 'View album', NARNOO_OPERATOR_I18N_DOMAIN ) );

					return false;

				// confirm deletion
				case 'delete':
					?>
					<h3><?php _e( 'Confirm deletion', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<p><?php echo sprintf( __( 'Please confirm deletion of the following %d image(s):', NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?></p>
					<ol>
					<?php 
					foreach ( $image_ids as $id ) { 
						?>
						<input type="hidden" name="images[]" value="<?php echo $id; ?>" />
						<li><span>Image ID: <?php echo $id; ?></span><span><img style="vertical-align: middle; padding-left: 20px;" src="<?php echo isset( $_REQUEST[ 'url' . $id ] ) ? $_REQUEST[ 'url' . $id ] : ''; ?>" /></span></li>
						<?php 
					} 
					?>
					</ol>
					<input type="hidden" name="action" value="do_delete" />
					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button-secondary" value="<?php _e( 'Confirm Deletion' ); ?>" />
						<input type="submit" name="cancel" id="cancel" class="button-secondary" value="<?php _e( 'Cancel' ); ?>" />
					</p>
					<?php
					
					return false;
					
				// perform actual delete
				case 'do_delete':
					?>
					<h3><?php _e( 'Delete' ); ?></h3>
					<p><?php echo sprintf( __( "Deleting the following %s image(s):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?></p>
					<ol>
					<?php
					foreach( $image_ids as $id ) {
						Narnoo_Operator_Helper::print_ajax_script_body( $id, 'deleteImage', array( $id ) );
					}
					?>
					</ol>
					<?php 
					Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to images', NARNOO_OPERATOR_I18N_DOMAIN ) );

					return false;
					
				// perform download
				case 'download':					
					?>
					<h3><?php _e( 'Download', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<p><?php echo sprintf( __( "Requesting download links for the following %s image(s):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?></p>
					<ol>
					<?php
					foreach( $image_ids as $id ) {
						Narnoo_Operator_Helper::print_ajax_script_body( $id, 'downloadImage', array( $id ) );
					}
					?>
					</ol>
					<?php 
					Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to images', NARNOO_OPERATOR_I18N_DOMAIN ) );

					return false;
					
			} 	// end switch( $action )
		}	// endif ( false !== $action )
		
		return true;
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
				$data['items'][] = $item;
			}
		}

		return $data;
	}

	/**
	 * Process any actions (displaying forms for the actions as well).
	 * If the table SHOULD be rendered after processing (or no processing occurs), prepares the data for display and returns true. 
	 * Otherwise, returns false.
	 **/
	function prepare_items() {		
		if ( ! $this->process_action() ) {
			return false;
		}
		
		$this->_column_headers = $this->get_column_info();
			
		$data = $this->get_current_page_data();
		$this->items = $data['items'];
		
		$this->set_pagination_args( array(
			'total_items'	=> count( $data['items'] ),
			'total_pages'	=> $data['total_pages']
		) );  
		
		return true;
	}
	
	/**
	 * Add screen options for images page.
	 **/
	static function add_screen_options() {
		global $narnoo_operator_images_table;
		$narnoo_operator_images_table = new Narnoo_Operator_Images_Table();
	}
}    