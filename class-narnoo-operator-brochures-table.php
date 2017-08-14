<?php
/**
 * Narnoo Operator - Brochures table.
 **/
class Narnoo_Operator_Brochures_Table extends WP_List_Table {
	function column_default( $item, $column_name ) {
		switch( $column_name ) { 
			case 'caption':
			case 'entry_date':
			case 'brochure_id':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	function column_thumbnail_image( $item ) {    
		$actions = array(
			'delete'    	=> sprintf( 
									'<a href="?%s">%s</a>', 
									build_query( 
										array(
											'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '',
											'paged' => $this->get_pagenum(),
											'action' => 'delete', 
											'brochures[]' => $item['brochure_id'], 
											'url' . $item['brochure_id'] => urlencode( str_replace( '.', '%2E', $item['thumbnail_image'] ) )
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
											'brochures[]' => $item['brochure_id'], 
										)
									),
									__( 'Download', NARNOO_OPERATOR_I18N_DOMAIN ) 
								),
		);
		return sprintf( 
			'<input type="hidden" name="url%1$s" value="%2$s" /> %3$s <br /> %4$s', 
			$item['brochure_id'],
			$item['thumbnail_image'],
			"<img src='" . $item['thumbnail_image'] . "' />", 
			$this->row_actions($actions) 
		);
	}
	
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="brochures[]" value="%s" />', $item['brochure_id']
		);    
	}

	function get_columns() {
		return array(
			'cb'				=> '<input type="checkbox" />',
			'thumbnail_image'	=> __( 'Thumbnail', NARNOO_OPERATOR_I18N_DOMAIN ),
			'caption'			=> __( 'Caption', NARNOO_OPERATOR_I18N_DOMAIN ),
			'entry_date'		=> __( 'Entry Date', NARNOO_OPERATOR_I18N_DOMAIN ),
			'brochure_id'		=> __( 'Brochure ID', NARNOO_OPERATOR_I18N_DOMAIN )
		);
	}

	function get_bulk_actions() {
		$actions = array(
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
		
		$action = $this->current_action();
		if ( false !== $action ) {
			$brochure_ids = isset( $_REQUEST['brochures'] ) ? $_REQUEST['brochures'] : array();
			$num_ids = count( $brochure_ids );
			if ( empty( $brochure_ids ) || ! is_array( $brochure_ids ) || $num_ids === 0 ) {
				return true;				
			}
			
			switch ( $action ) {

				// confirm deletion
				case 'delete':
					?>
					<h3><?php _e( 'Confirm deletion', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<p><?php echo sprintf( __( 'Please confirm deletion of the following %d brochure(s):', NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?></p>
					<ol>
					<?php 
					foreach ( $brochure_ids as $id ) { 
						?>
						<input type="hidden" name="brochures[]" value="<?php echo $id; ?>" />
						<li><span>Brochure ID: <?php echo $id; ?></span><span><img style="vertical-align: middle; padding-left: 20px;" src="<?php echo ( isset( $_REQUEST[ 'url' . $id ] ) ? $_REQUEST[ 'url' . $id ] : '' ); ?>" /></span></li>
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
					<p><?php echo sprintf( __( "Deleting the following %s brochure(s):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?></p>
					<ol>
					<?php
					foreach( $brochure_ids as $id ) {
						Narnoo_Operator_Helper::print_ajax_script_body( $id, 'deleteBrochure', array( $id ) );
					}
					?>
					</ol>
					<?php 
					Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to brochures', NARNOO_OPERATOR_I18N_DOMAIN ) );

					return false;
					
				// perform download
				case 'download':					
					?>
					<h3><?php _e( 'Download', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<p><?php echo sprintf( __( "Requesting download links for the following %s brochure(s):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?></p>
					<ol>
					<?php
					foreach( $brochure_ids as $id ) {
						Narnoo_Operator_Helper::print_ajax_script_body( $id, 'downloadBrochure', array( $id ) );
					}
					?>
					</ol>
					<?php 
					Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to brochures', NARNOO_OPERATOR_I18N_DOMAIN ) );

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
				$list = $request->getBrochures( $current_page );
				if ( ! is_array( $list->operator_brochures ) ) {
					throw new Exception( sprintf( __( "Error retrieving brochures. Unexpected format in response page #%d.", NARNOO_OPERATOR_I18N_DOMAIN ), $current_page ) );
				}
			} catch ( Exception $ex ) {
				Narnoo_Operator_Helper::show_api_error( $ex );
			} 
		}
		
		if ( ! is_null( $list ) ) {
			$data['total_pages'] = max( 1, intval( $list->total_pages ) );
			foreach ( $list->operator_brochures as $brochure ) {
				$item['thumbnail_image'] = $brochure->thumb_image_path;
				$item['caption'] = $brochure->brochure_caption;
				$item['entry_date'] = $brochure->entry_date;
				$item['brochure_id'] = $brochure->brochure_id;
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
	 * Add screen options for brochures page.
	 **/
	static function add_screen_options() {
		global $narnoo_operator_brochures_table;
		$narnoo_operator_brochures_table = new Narnoo_Operator_Brochures_Table();
	}
}    