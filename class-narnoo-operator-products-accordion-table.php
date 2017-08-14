<?php
/**
 * Narnoo Operator - Products accordion table.
 **/
class Narnoo_Operator_Products_Accordion_Table extends WP_List_Table {
	function column_default( $item, $column_name ) {
		switch( $column_name ) { 
			case 'product_title':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}
	
	function get_columns() {
		return array( 'product_title' => '' );
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
				$list = $request->getProductText( $current_page );
				if ( ! is_array( $list->operator_products ) ) {
					throw new Exception( sprintf( __( "Error retrieving product titles. Unexpected format in response page #%d.", NARNOO_OPERATOR_I18N_DOMAIN ), $current_page ) );
				}
			} catch ( Exception $ex ) {
				Narnoo_Operator_Helper::show_api_error( $ex );
			} 
		}
		
		if ( ! is_null( $list ) ) {
			$data['total_pages'] = max( 1, intval( $list->total_pages ) );
			foreach ( $list->operator_products as $product ) {
				$item['product_title'] = $product->product_title;
				$data['items'][] = $item;
			}
		}

		return $data;
	}

	/**
	 * Prepare the table data for display.
	 **/
	function prepare_items() {		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
			
		$data = $this->get_current_page_data();
		$this->items = $data['items'];
		
		$this->set_pagination_args( array(
			'total_items'	=> count( $data['items'] ),
			'total_pages'	=> $data['total_pages']
		) );  			
	}
	
	/**
	 * Display accordion rows instead of table rows.
	 **/
	function display_rows() {
		echo '<div id="narnoo-text-accordion" style="display:none;">';
		
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	
		echo '</div>';
	}
	
	/**
	 * Display accordion row instead of table row.
	 **/
	function single_row( $item ) {
		?>
		<h3>
			<a title="<?php _e( 'Click to toggle', NARNOO_OPERATOR_I18N_DOMAIN ); ?>" href="#">
				<img style="display:none;" class="narnoo-header-icon-process" src="<?php echo admin_url(); ?>images/wpspin_light.gif" />
				<img style="display:none;" class="narnoo-header-icon-success" src="<?php echo admin_url(); ?>images/yes.png" />
				<img style="display:none;" class="narnoo-header-icon-fail" src="<?php echo admin_url(); ?>images/no.png" />
				<?php echo esc_html( $item['product_title'] ); ?>
			</a>
		</h3>
		<div data-loaded="no" data-product-title="<?php echo $item['product_title']; ?>">
			<div class="narnoo-product-text"></div>
		</div>
		<?php
	}
	
	/**
	 * Javascript to display accordion rows and load data when clicked on.
	 **/
	function display() {
		$this->display_tablenav( 'top' );
		$this->display_rows();
		if ( count( $this->items ) > 0 ) {
			$this->display_tablenav( 'bottom' );
		}
		
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {				
				$('#narnoo-text-accordion').accordion({ collapsible: true, active: false, autoHeight: false }).show().bind('accordionchange', function(e, ui) {
					if ($(ui.newContent).length == 0 || $(ui.newContent).data("loaded") === 'yes' || $(ui.newContent).data("loaded") === 'loading') {
						return;
					}
					
					var $contentElem = $(ui.newContent);
					var $process = $contentElem.find('.narnoo-process');
					var $productText = $contentElem.find('.narnoo-product-text');
					
					var $headerElem = $(ui.newHeader);
					var $iconProcess = $headerElem.find('.narnoo-header-icon-process');
					var $iconSuccess = $headerElem.find('.narnoo-header-icon-success');
					var $iconFail = $headerElem.find('.narnoo-header-icon-fail');
					
					$productText.html("<?php _e( 'Processing...', NARNOO_OPERATOR_I18N_DOMAIN ); ?>");
					$iconProcess.show();
					$iconSuccess.hide();
					$iconFail.hide();
					$contentElem.data("loaded", "loading");
					
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: { action: 'narnoo_operator_api_request', 
								func_name: 'getProductTextWords', 
								param_array: [ $contentElem.data('product-title') ] },
						timeout: 60000,
						dataType: "json",
						success: 
							function(response, textStatus, jqXHR) {     
								$iconProcess.hide();
								
								if (response['success'] === 'success' && response['msg']) {						
									$iconSuccess.show();
									$contentElem.data("loaded", "yes");
									$productText.html(response['msg']);	
								} else {
									$iconFail.show();
									$contentElem.data("loaded", "no");
									$productText.html('<?php _e( 'AJAX error: Unexpected response', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');										
								}
							},
						error: 
							function(jqXHR, textStatus, errorThrown) {
								$iconProcess.hide();
								$iconFail.show();
								$contentElem.data("loaded", "no");
					
								if (textStatus === 'timeout') {   // server timeout
									$productText.html('<?php _e( 'AJAX error: Server timeout', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');
								} else {                  // other error
									$productText.html(jqXHR.responseText);
								}
							}
					});
				});
			});					
		</script>		
		<?php
	}
	
	/**
	 * Enqueue scripts for loading jquery UI accordion (not included in Wordpress 3.2).
	 **/
	function load_scripts( $hook ) {
		global $narnoo_operator_text_page;
		
		if ( $narnoo_operator_text_page !== $hook ) {	// ensure scripts are only loaded on the Narnoo->Text Page
			return;
		}
		
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-widget',
							plugins_url( '/js/jquery.ui.widget.min.js', __FILE__ ), array( 'jquery-ui-core' ) );
		wp_enqueue_script( 'jquery-ui-accordion',
							plugins_url( '/js/jquery.ui.accordion.min.js', __FILE__ ), array( 'jquery-ui-widget' ) );
							
		wp_enqueue_style( 'jquery-ui', 
							'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/base/jquery-ui.css' );		
	}	
}    