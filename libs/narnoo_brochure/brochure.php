<?php
$error_msg_prefix = __( 'Narnoo operator brochure shortcode error: ', NARNOO_OPERATOR_I18N_DOMAIN );

if ( isset( $_POST['narnoo_brochure_shortcode_count'] ) ) {
	// handle AJAX request
	$narnoo_brochure_shortcode_count = $_POST['narnoo_brochure_shortcode_count'];
	$id = $_POST['brochure_id'];
	$image = $_POST['image'];
	$align = $_POST['align'];
	$img_title = $_POST['img_title'];
	$img_alt = $_POST['img_alt'];
	$brochure_width = $_POST['brochure_width'];
	$brochure_height = $_POST['brochure_height'];

	// init Narnoo API request object
	$request = Narnoo_Operator_Helper::init_api();			
	if ( is_null( $request ) ) {
		echo $error_msg_prefix . __( 'Need API keys to request links', NARNOO_OPERATOR_I18N_DOMAIN );
		return;
	} 
	
	// request album images from API
	try {
		$brochure = $request->getSingleBrochure( $id );
	} catch ( Exception $ex ) {
		echo $error_msg_prefix . $ex->getMessage();
		return;
	}
		
	$brochure_pdf = uncdata( $brochure->file_path_to_pdf );
	$brochure_images['thumbnail'] = $brochure->thumb_image_path;
	$brochure_images['preview'] = $brochure->preview_image_path;
	$brochure_image = $brochure_images[ $image ];
	
	// return the html for displaying brochure PDF link and thumbnail/preview image
	
	// prepare <img> tag
	$img_align_class = ' class="align' . $align . '"';  // set alignment in img tag
	$brochure_html = '<img src="' . $brochure_image. '" title="' . esc_attr( $img_title ) . '" alt="' . esc_attr( $img_alt ) . '" width="' . $brochure_width . '" height="' . $brochure_height . '"' . $img_align_class . ' style="margin-bottom: 0; padding: 0; border: none; outline: none; max-width: ' . $brochure_width . 'px; max-height: ' . $brochure_height . 'px;" />';
	
	// enclose with <a> tag
	$brochure_html = '<a target="_blank" href="' . $brochure_pdf . '">' . $brochure_html . '</a>';
	
	echo do_shortcode( $brochure_html );
	
	return;
}

// number of brochure shortcodes on current page so far
global $narnoo_brochure_shortcode_count;	

if ( ! isset( $narnoo_brochure_shortcode_count ) ) {
	$narnoo_brochure_shortcode_count = 0;
}

extract( shortcode_atts( array(
	'id' => 0,			// the only required attribute
	'width' => '200',	// optional width
	'height' => '150',	// optional height
	'image' => 'thumbnail',	// specify which image to use; valid values: 'thumbnail' (default) or 'preview'
	'align' => 'none',	// image alignment; valid values: 'none' (default), 'left', 'right', 'center'
	'img_title' => '',	// title attribute of image (default: "Brochure #id")
	'img_alt' => '',	// alt attribute of image (default: "Brochure #id")
), $atts ) );

// set default values for img title and alt attributes
$default_title = __( 'Brochure #', NARNOO_OPERATOR_I18N_DOMAIN ) . $id;
if ( empty( $img_title ) ) {
	$img_title = $default_title;
}
if ( empty( $img_alt ) ) {
	$img_alt = $default_title;
}

// load Javascripts that should only be loaded once per page using wp_enqueue_script (Wordpress 3.3+ only)
if ( Narnoo_Operator_Helper::wp_supports_enqueue_script_in_body() ) {
	Narnoo_Operator::load_scripts_for_brochure();
}

?>
<div style="height: <?php echo $height; ?>px;" class="narnoo_brochure" data-width="<?php echo esc_attr( $width ); ?>" data-height="<?php echo esc_attr( $height ); ?>" data-id="<?php echo esc_attr( $id ); ?>" data-count="<?php echo $narnoo_brochure_shortcode_count; ?>" data-image="<?php echo esc_attr( $image ); ?>" data-align="<?php echo esc_attr( $align ); ?>" data-img-alt="<?php echo esc_attr( $img_alt ); ?>" data-img-title="<?php echo esc_attr( $img_title ); ?>">
	<div style="height: <?php echo $height; ?>px; background: #ffffff url(<?php echo plugin_dir_url( __FILE__ ) . 'images/loader.gif'; ?>) no-repeat center center;">
	</div>
</div>

<script type="text/javascript">
	if (typeof narnoo_brochure === 'undefined') {
		narnoo_brochure = { count: 0, album_names: [] };
		narnoo_brochure_url = '<?php echo plugin_dir_url( __FILE__ ); ?>';
		narnoo_brochure_file_url = '<?php echo __FILE__; ?>';
		narnoo_brochure_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	}
	narnoo_brochure.count++;	
</script>
<?php
$narnoo_brochure_shortcode_count++;

