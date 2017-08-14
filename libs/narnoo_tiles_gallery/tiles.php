<?php
$error_msg_prefix = __( 'Narnoo operator tiles gallery shortcode error: ', NARNOO_OPERATOR_I18N_DOMAIN );

if ( isset( $_POST['narnoo_tiles_shortcode_count'] ) ) {
	// handle AJAX request
	$narnoo_tiles_shortcode_count = $_POST['narnoo_tiles_shortcode_count'];
	$album_name = $_POST['album_name'];
	
	// init Narnoo API request object
	$request = Narnoo_Operator_Helper::init_api();			
	if ( is_null( $request ) ) {
		echo $error_msg_prefix . __( 'Need API keys to request links', NARNOO_OPERATOR_I18N_DOMAIN );
		return;
	} 
	
	// request album images from API
	try {
		$list = $request->getAlbumImages( $album_name );
	} catch ( Exception $ex ) {
		echo $error_msg_prefix . $ex->getMessage();
		return;
	}
	
	foreach ( $list->operator_albums_images as $album ) {
		echo '<a href="'.$album->large_image_path.'">
			<img alt="' . $album->image_caption . '" src="'.$album->preview_image_path.'" />
		</a>';
	}

	return;
}

// number of tiles gallery shortcodes on current page so far; used as unique id for the gallery
global $narnoo_tiles_shortcode_count;	

if ( ! isset( $narnoo_tiles_shortcode_count ) ) {
	$narnoo_tiles_shortcode_count = 0;
}

extract( shortcode_atts( array(
	'album_name' => '',			// the only required attribute
	'width' => '850',			// optional width
	'height' => '650',			// optional height
	'border_color' => '#000'	// optional border color
), $atts ) );

// load Javascripts that should only be loaded once per page using wp_enqueue_script (Wordpress 3.3+ only)
if ( Narnoo_Operator_Helper::wp_supports_enqueue_script_in_body() ) {
	Narnoo_Operator::load_scripts_for_tiles_gallery();
}
?>
<div class="narnoo_tiles" data-width="<?php echo esc_attr( $width ); ?>" data-height="<?php echo esc_attr( $height ); ?>" data-count="<?php echo $narnoo_tiles_shortcode_count; ?>" data-album-name="<?php echo esc_attr( $album_name ); ?>" id="narnoo_tiles_<?php echo $narnoo_tiles_shortcode_count; ?>" style="width: <?php echo esc_attr( $width ); ?>px; height: <?php echo esc_attr( $height ); ?>px; background-color: <?php echo esc_attr( $border_color ); ?>;">
	<div style="height: <?php echo esc_attr( $height ); ?>px; width: <?php echo esc_attr( $width ); ?>px; background: #ffffff url(<?php echo plugin_dir_url( __FILE__ ) . 'img/loading.gif'; ?>) no-repeat center center;">
	</div>
</div>

<script type="text/javascript">
	if (typeof narnoo_tiles === 'undefined') {
		narnoo_tiles = { count: 0, heights: [], widths: [] };
		narnoo_tiles_url = '<?php echo plugin_dir_url( __FILE__ ); ?>';
		narnoo_tiles_file_url = '<?php echo __FILE__; ?>';
		narnoo_tiles_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	}
	narnoo_tiles.widths.push('<?php echo intval( $width ); ?>');
	narnoo_tiles.heights.push('<?php echo intval( $height ); ?>');
	narnoo_tiles.count++;	
</script>
<?php
$narnoo_tiles_shortcode_count++;
