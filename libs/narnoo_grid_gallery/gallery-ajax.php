<?php
global $error_msg_prefix;
$error_msg_prefix = __( 'Narnoo operator grid gallery shortcode error: ', NARNOO_OPERATOR_I18N_DOMAIN );

if ( isset( $_GET['narnoo_grid_shortcode_count'] ) ) {
	// handle AJAX request

	// retrieve gallery items on specified page
	require_once dirname( __FILE__ ) . '/_includes/ajax/getGallery.php';

	return;
}

// number of grid gallery shortcodes on current page so far; used as unique id for the gallery
global $narnoo_grid_shortcode_count;	

if ( ! isset( $narnoo_grid_shortcode_count ) ) {
	$narnoo_grid_shortcode_count = 0;
}

extract( shortcode_atts( array(
	'album_name' => '',			// the only required attribute
	'width' => '600',			// optional width
	'height' => '975',			// optional height
	'downloadable' => 'true',	// optional, indicates whether download button should appear on thumbnails
	'bandwidth' => 'false',     // optional, only applicable when 'downloadable' is true; indicates whether image should be downloaded or simply output to new browser window to save bandwidth
), $atts ) );

// load Javascripts that should only be loaded once per page using wp_enqueue_script (Wordpress 3.3+ only)
if ( Narnoo_Operator_Helper::wp_supports_enqueue_script_in_body() ) {
	Narnoo_Operator::load_scripts_for_grid_gallery();
}
?>
<div class="narnoo_grid" data-width="<?php echo esc_attr( $width ); ?>" data-downloadable="<?php echo $downloadable == 'true' ? '1' : '0'; ?>" data-bandwidth="<?php echo $bandwidth == 'true' ? '1' : '0'; ?>" data-height="<?php echo esc_attr( $height ); ?>" data-count="<?php echo $narnoo_grid_shortcode_count; ?>" data-album-name="<?php echo esc_attr( $album_name ); ?>" id="narnoo_grid_<?php echo $narnoo_grid_shortcode_count; ?>">
	<?php if ( $narnoo_grid_shortcode_count === 0 ) { ?>
	<iframe name="narnoo-grid-download" frameborder="0" style="display:none"></iframe>
	<?php } ?>
	<div class="narnoo-grid-gallery-loader-container" id="narnoo-grid-gallery-loader-container-<?php echo $narnoo_grid_shortcode_count; ?>" style="width:<?php echo esc_attr( $width ); ?>px;height:<?php echo esc_attr( $height ); ?>px;display:none">
		<img src="<?php echo plugin_dir_url( __FILE__ ) . '_assets/images/ajax-gallery-loader.gif'; ?>" width="54" height="54" alt="Gallery Loader" class="narnoo-gallery-loader" /> 
	</div>
	<div class="narnoo-grid-gallery" id="narnoo-grid-gallery-<?php echo $narnoo_grid_shortcode_count; ?>" style="width:<?php echo esc_attr( $width ); ?>px; height:<?php echo esc_attr( $height ); ?>px; overflow: auto;">
	</div>               
</div>

<script type="text/javascript">
	if (typeof narnoo_grid === 'undefined') {
		narnoo_grid = { count: 0, heights: [], widths: [] };
		narnoo_grid_url = '<?php echo plugin_dir_url( __FILE__ ); ?>';
		narnoo_grid_file_url = '<?php echo __FILE__; ?>';
		narnoo_grid_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	}
	narnoo_grid.widths.push('<?php echo intval( $width ); ?>');
	narnoo_grid.heights.push('<?php echo intval( $height ); ?>');
	narnoo_grid.count++;	
</script>
<?php
$narnoo_grid_shortcode_count++;
