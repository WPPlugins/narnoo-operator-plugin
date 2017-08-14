<?php
$error_msg_prefix = __( 'Narnoo operator single_link gallery shortcode error: ', NARNOO_OPERATOR_I18N_DOMAIN );

if ( isset( $_POST['narnoo_slg_shortcode_count'] ) ) {
	// handle AJAX request
	$narnoo_slg_shortcode_count = $_POST['narnoo_slg_shortcode_count'];
	$album_name = $_POST['album_name'];
	$width = $_POST['width'];
	$height = $_POST['height'];
	
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
	<a href="javascript:imagebox.open(document.getElementById('narnoo_single_link_gallery<?php echo $narnoo_slg_shortcode_count; ?>'));" class="narnoo_single_link_gallery_thumbnail">
		<?php   //create an array with the preview images so we can randomly select one.
		$noo_images = array();
		
		foreach ( $list->operator_albums_images as $album ) {
			array_push( $noo_images, $album->preview_image_path );	
		}
		//a random number for the image selection
		//count array
		$tImg = count($noo_images);

		if ( $tImg == 0 ) {
			echo "No Images";
			return;
		}

		$i = rand( 0, $tImg - 1 );
		$master_img = $noo_images[$i];
			
		?>               
		<img src="<?php echo esc_attr( $master_img ); ?>" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" alt="" />
		<span class="narnoo_single_link_gallery_cover"></span>
	</a>
	<?php	  
	foreach ( $list->operator_albums_images as $album ) {
		echo '<a href="'.$album->large_image_path.'" rel="imagebox[narnoo_slg'.$narnoo_slg_shortcode_count.']" id="narnoo_single_link_gallery'.$narnoo_slg_shortcode_count.'" title="' . $album->image_caption . '"></a>';
	}
	
	return;	
}

// number of single link gallery shortcodes on current page so far; used as unique id for the gallery
global $narnoo_slg_shortcode_count;	

if ( ! isset( $narnoo_slg_shortcode_count ) ) {
	$narnoo_slg_shortcode_count = 0;
}

extract( shortcode_atts( array(
	'album_name' => '',			// the only required attribute
	'width' => '200',			// optional width
	'height' => '150'			// optional height
), $atts ) );

// load Javascripts that should only be loaded once per page using wp_enqueue_script (Wordpress 3.3+ only)
if ( Narnoo_Operator_Helper::wp_supports_enqueue_script_in_body() ) {
	Narnoo_Operator::load_scripts_for_single_link_gallery();
}

?>
<div style="height: <?php echo $height; ?>px; width: <?php echo $width; ?>px;" class="narnoo_slg" data-count="<?php echo $narnoo_slg_shortcode_count; ?>" data-width="<?php echo esc_attr( $width ); ?>" data-height="<?php echo esc_attr( $height ); ?>" data-album-name="<?php echo esc_attr( $album_name ); ?>">
	<div style="height: <?php echo $height; ?>px; width: <?php echo $width; ?>px; background: #ffffff url(<?php echo plugin_dir_url( __FILE__ ) . 'images/loader.gif'; ?>) no-repeat center center;">
	</div>
</div>

<script type="text/javascript">
	if (typeof narnoo_slideshow === 'undefined') {
		narnoo_slideshow = { count: 0, album_names: [] };
		narnoo_slideshow_url = '<?php echo plugin_dir_url( __FILE__ ); ?>';
		narnoo_slideshow_file_url = '<?php echo __FILE__; ?>';
		narnoo_slideshow_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	}
	narnoo_slideshow.album_names.push('<?php echo $album_name; ?>');
	narnoo_slideshow.count++;	
</script>
<?php
$narnoo_slg_shortcode_count++;
