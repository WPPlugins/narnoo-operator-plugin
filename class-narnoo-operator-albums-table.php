<?php
/**
 * Narnoo Operator - Albums table.
 **/
class Narnoo_Operator_Albums_Table extends WP_List_Table {		
	public $current_album_id = '0';
	public $current_album_name = '';
	public $current_album_page = 1;
	
	public $select_album_html_script = '';
	
	function __construct( $args = array() ) {
		parent::__construct( $args );
		
		if ( isset( $_POST['narnoo_album_name'] ) ) {
			if ( isset( $_POST['narnoo_album_name'] ) ) {
				$this->current_album_name = stripslashes( $_POST['narnoo_album_name'] );
			}
			if ( isset( $_POST['narnoo_album_id'] ) ) {
				$this->current_album_id = $_POST['narnoo_album_id'];
			}
			if ( isset( $_POST['narnoo_album_page'] ) ) {
				$this->current_album_page = intval( $_POST['narnoo_album_page'] ); 
			}
		} else {
			if ( isset( $_REQUEST['album_name'] ) ) {
				$this->current_album_name = stripslashes( $_REQUEST['album_name'] );
			}
			if ( isset( $_REQUEST['album'] ) ) {
				$this->current_album_id = $_REQUEST['album'];
			}
			if ( isset( $_REQUEST['album_page'] ) ) {
				$this->current_album_page = intval( $_REQUEST['album_page'] );
			}
		}

		// get the current (or first, if unspecified) page of albums
		$list = null;
		$this->current_album_page = max( 1, $this->current_album_page );
		$current_page = $this->current_album_page;
		$request = Narnoo_Operator_Helper::init_api();
		if ( ! is_null( $request ) ) {
			try {
				$list = $request->getAlbums( $current_page );
				if ( ! is_array( $list->operator_albums ) ) {
					throw new Exception( sprintf( __( "Error retrieving albums. Unexpected format in response page #%d.", NARNOO_OPERATOR_I18N_DOMAIN ), $current_page ) );
				}
			} catch ( Exception $ex ) {
				Narnoo_Operator_Helper::show_api_error( $ex );
			} 				
		}
		
		if ( ! is_null( $list ) ) {
			$total_pages = max( 1, intval( $list->total_pages ) );
		
			// use current specified album name if it exists in current page;
			// otherwise set it to first album name in current page
			$first_album = null;
			$is_current_album_name_valid = false;
			foreach ( $list->operator_albums as $album ) {
				$album_name = stripslashes( $album->album_name );
				if ( is_null( $first_album ) ) {
					$first_album = $album;
				}
				if ( empty( $this->current_album_name ) ) {
					$this->current_album_name = $album_name;
					$this->current_album_id = $album->album_id;
				}
				if ( $this->current_album_name === $album_name ) {
					$is_current_album_name_valid = true;
				}
			}
			
			if ( ! $is_current_album_name_valid ) {
				Narnoo_Operator_Helper::show_error( sprintf( __( "<strong>ERROR:</strong> Unknown album name '%s'.", NARNOO_OPERATOR_I18N_DOMAIN ), $this->current_album_name ) );
				if ( ! is_null( $first_album ) ) {
					$this->current_album_name = stripslashes( $first_album->album_name );
					$this->current_album_id = $first_album->album_id;
				}
			}

			$this->select_album_html_script = Narnoo_Operator_Helper::get_album_select_html_script( $list->operator_albums, $total_pages, $this->current_album_page, $this->current_album_name );
		}		
		
	}

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
			'remove'    	=> sprintf( 
									'<a href="?%s">%s</a>', 
									build_query( 
										array(
											'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '',
											'paged' => $this->get_pagenum(),
											'action' => 'remove', 
											'images[]' => $item['image_id'], 
											'url' . $item['image_id'] => urlencode( str_replace( '.', '%2E', $item['thumbnail_image'] ) ),
											'album_page' => $this->current_album_page, 
											'album' => $this->current_album_id, 
											'album_name' => urlencode( $this->current_album_name )
										)
									),
									__( 'Remove from album', NARNOO_OPERATOR_I18N_DOMAIN ) 
								),
			'download'    	=> sprintf( 
									'<a href="?%s">%s</a>', 
									build_query( 
										array(
											'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '',
											'paged' => $this->get_pagenum(),
											'action' => 'download', 
											'images[]' => $item['image_id'], 
											'album_page' => $this->current_album_page, 
											'album' => $this->current_album_id, 
											'album_name' => urlencode( $this->current_album_name )
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
			'remove'    	=> __( 'Remove from album', NARNOO_OPERATOR_I18N_DOMAIN ),
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
		
		$action = $this->current_action();
		if ( false !== $action ) {
			if ( $action === 'create' ) {
				?>
				<h3><?php _e( 'Create album', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
				<table class="form-table">
					<tr>
						<th><?php _e( "Please key in a new album name:", NARNOO_OPERATOR_I18N_DOMAIN ); ?></th>
						<td><input type="text" class="regular-text" name="new_album_name" id="new_album_name" /></td>
					</tr>
				</table>
				<input type="hidden" name="action" value="do_create" />
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button-secondary" value="<?php _e( 'Create' ); ?>" />
					<input type="submit" name="cancel" id="cancel" class="button-secondary" value="<?php _e( 'Cancel' ); ?>" />
				</p>
				<?php

				return false;
			}
			
			// perform actual creation of new album
			if ( $action === 'do_create' ) {
				$new_album_name = isset( $_REQUEST['new_album_name'] ) ? $_REQUEST['new_album_name'] : '';
				?>
				<h3><?php _e( 'Create album', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
				<p><?php echo sprintf( __( "Creating the following album:", NARNOO_OPERATOR_I18N_DOMAIN ) ) . ' ' . esc_html( $new_album_name ); ?></p>
				<ol>
				<?php
				Narnoo_Operator_Helper::print_ajax_script_body( 'unknown', 'createAlbum', array( $new_album_name ) );
				?>
				</ol>
				<?php 
				Narnoo_Operator_Helper::print_ajax_script_footer( 1, __( 'Back to albums', NARNOO_OPERATOR_I18N_DOMAIN ) );

				return false;
			}
			
			$image_ids = isset( $_REQUEST['images'] ) ? $_REQUEST['images'] : array();
			$num_ids = count( $image_ids );
			if ( empty( $image_ids ) || ! is_array( $image_ids ) || $num_ids === 0 ) {
				return true;				
			}
			
			switch ( $action ) {

				// confirm remove from album
				case 'remove':
					$album_id = $this->current_album_id;
					$album_name = $this->current_album_name;
					if ( empty( $album_name ) ) {
						Narnoo_Operator_Helper::show_error( __( 'Unspecified album name. Action cancelled.' ), NARNOO_OPERATOR_I18N_DOMAIN );
						return true;
					}
					?>
					<h3><?php _e( 'Confirm remove from album', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<p><?php echo sprintf( __( "Please confirm removal of the following %d image(s) from the album '%s' (ID %d):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids, esc_html( $album_name ), $album_id ); ?></p>
					<input type="hidden" name="album" value="<?php echo $album_id; ?>" />
					<input type="hidden" name="album_name" value="<?php echo esc_attr( $album_name ); ?>" />
					<ol>
					<?php 
					foreach ( $image_ids as $id ) { 
						?>
						<input type="hidden" name="images[]" value="<?php echo $id; ?>" />
						<li><span>Image ID: <?php echo $id; ?></span><span><img style="vertical-align: middle; padding-left: 20px;" src="<?php echo ( isset( $_REQUEST[ 'url' . $id ] ) ? $_REQUEST[ 'url' . $id ] : '' ); ?>" /></span></li>
						<?php 
					} 
					?>
					</ol>
					<input type="hidden" name="action" value="do_remove" />
					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button-secondary" value="<?php _e( 'Confirm Removal' ); ?>" />
						<input type="submit" name="cancel" id="cancel" class="button-secondary" value="<?php _e( 'Cancel' ); ?>" />
					</p>
					<?php
					
					return false;
					
				// perform actual removal from album
				case 'do_remove':
					$album_id = $this->current_album_id;
					$album_name = $this->current_album_name;
					if ( empty( $album_name ) ) {
						Narnoo_Operator_Helper::show_error( __( 'Unspecified album name. Action cancelled.' ), NARNOO_OPERATOR_I18N_DOMAIN );
						return true;
					}
					?>
					<h3><?php _e( 'Remove from album' ); ?></h3>
					<p><?php echo sprintf( __( "Removing the following %s image(s) from album '%s' (ID %d):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids, $album_name, $album_id ); ?></p>
					<ol>
					<?php
					foreach( $image_ids as $id ) {
						Narnoo_Operator_Helper::print_ajax_script_body( $id, 'removeFromAlbum', array( $id, $album_id ) );
					}
					?>
					</ol>
					<?php 
					Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to albums', NARNOO_OPERATOR_I18N_DOMAIN ) );

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
					Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to albums', NARNOO_OPERATOR_I18N_DOMAIN ) );

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
		
		// no album name specified; just return empty data
		$current_album_name = $this->current_album_name;
		if ( empty( $current_album_name ) ) {
			return $data;
		}

		$list = null;
		$current_page = $this->get_pagenum();
		$request = Narnoo_Operator_Helper::init_api();
		if ( ! is_null( $request ) ) {
			try {
				$list = $request->getAlbumImages( $current_album_name, $current_page );
				if ( ! is_array( $list->operator_albums_images ) ) {
					throw new Exception( sprintf( __( "Error retrieving album images. Unexpected format in response page #%d.", NARNOO_OPERATOR_I18N_DOMAIN ), $current_page ) );
				}
			} catch ( Exception $ex ) {
				Narnoo_Operator_Helper::show_api_error( $ex );
			} 				
		}
		
		if ( ! is_null( $list ) ) {
			$data['total_pages'] = max( 1, intval( $list->total_pages ) );
			foreach ( $list->operator_albums_images as $image ) {
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
	 * Add screen options for albums page.
	 **/
	static function add_screen_options() {
		global $narnoo_operator_albums_table;
		$narnoo_operator_albums_table = new Narnoo_Operator_Albums_Table();
	}
}    