<?php
$error_msg_prefix = __( 'Narnoo operator slider gallery shortcode error: ', NARNOO_OPERATOR_I18N_DOMAIN );
	
if ( isset( $_POST['narnoo_slider_shortcode_count'] ) ) {
	// handle AJAX request
	$narnoo_slider_shortcode_count = $_POST['narnoo_slider_shortcode_count'];
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

	?>
	<ul>
		<?php		  		
		foreach ( $list->operator_albums_images as $album ) {
			echo '<li>
				<img rel="' . $album->image_caption . '" alt="' . $album->image_caption . '" src="'.$album->large_image_path.'" />
			</li>';
		}
		?>
	</ul>
	<?php

	return;
}

// number of slider gallery shortcodes on current page so far; used as unique id for the gallery
global $narnoo_slider_shortcode_count;	

if ( ! isset( $narnoo_slider_shortcode_count ) ) {
	$narnoo_slider_shortcode_count = 0;
}

extract( shortcode_atts( array(
	'album_name' => '',			// the only required attribute
	'width' => '600',			// optional width
	'timer' => '5000',			// optional timer (in milliseconds)
	'circular' => 'false'		// optional 'true' or 'false'
), $atts ) );

// load Javascripts that should only be loaded once per page using wp_enqueue_script (Wordpress 3.3+ only)
if ( Narnoo_Operator_Helper::wp_supports_enqueue_script_in_body() ) {
	Narnoo_Operator::load_scripts_for_slider_gallery();
}
?>
<div style="height: 250px; width: <?php echo esc_attr( $width ); ?>px;" class="narnoo_slider" data-count='<?php echo $narnoo_slider_shortcode_count; ?>' data-album-name='<?php echo esc_attr( $album_name ); ?>' data-width='<?php echo esc_attr( $width ); ?>' data-circular='<?php echo esc_attr( $circular ); ?>' data-timer='<?php echo esc_attr( $timer ); ?>' id="narnoo_slider_<?php echo $narnoo_slider_shortcode_count; ?>">
	<div style="height: 250px; width: <?php echo esc_attr( $width ); ?>px; background: #ffffff url(<?php echo plugin_dir_url( __FILE__ ) . 'images/loader.gif'; ?>) no-repeat center center;">
	</div>
</div>

<script type="text/javascript">
	if (typeof narnoo_sliders === 'undefined') {
		narnoo_sliders = { count: 0 };
		narnoo_sliders_url = '<?php echo plugin_dir_url( __FILE__ ); ?>';
		narnoo_sliders_file_url = '<?php echo __FILE__; ?>';
		narnoo_sliders_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	}
	narnoo_sliders.count++;	
</script>
<?php
$narnoo_slider_shortcode_count++;
