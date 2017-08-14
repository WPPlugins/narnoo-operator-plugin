<?php
$error_msg_prefix = __( 'Narnoo operator video shortcode error: ', NARNOO_OPERATOR_I18N_DOMAIN );

if ( isset( $_POST['narnoo_video_shortcode_count'] ) ) {
	// handle AJAX request
	$narnoo_video_shortcode_count = $_POST['narnoo_video_shortcode_count'];
	$id = $_POST['video_id'];
	$width = $_POST['width'];
	$height = $_POST['height'];

	// init Narnoo API request object
	$request = Narnoo_Operator_Helper::init_api();			
	if ( is_null( $request ) ) {
		echo $error_msg_prefix . __( 'Need API keys to request links', NARNOO_OPERATOR_I18N_DOMAIN );
		return;
	} 
		
	// request video details from API
	try {
		$video = $request->getVideoDetails( $id );
	} catch ( Exception $ex ) {
		echo $error_msg_prefix . $ex->getMessage();
		return;
	} 				
	
	$pause_image_rel_dir = 'tmp_pause_img/';   // relative directory containing temporary pause images
	$pause_image_dir = NARNOO_OPERATOR_PLUGIN_PATH . $pause_image_rel_dir;
	$pause_image_url = NARNOO_OPERATOR_PLUGIN_URL . $pause_image_rel_dir;
	
	// need to download pause image to local server, in order to pass to flashvars
	// first we delete all previously downloaded pause image files older than 5 minutes
	if ( $narnoo_video_shortcode_count === 0) {   // do this only once per video shortcode on current page
		foreach ( glob( $pause_image_dir . '*' ) as $file ) {
			if ( filemtime( $file ) < current_time( 'timestamp' ) - 300 ) {
				@unlink( $file );
			}
		}
	}
	
	// now download the pause image, provided autoplay is not set to 'yes'
	$pause_image_pathinfo = pathinfo( parse_url( $video->video_pause_image_path, PHP_URL_PATH ) );
	$pause_image_filename = $pause_image_pathinfo['basename'];
	$pause_image_filepath = $pause_image_dir . $pause_image_filename;
	$pause_image_fileurl = $pause_image_url . $pause_image_filename;
	$ch = curl_init( $video->video_pause_image_path );
	$fp = fopen( $pause_image_filepath, 'wb' );
	curl_setopt( $ch, CURLOPT_FILE, $fp );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_exec( $ch );
	curl_close( $ch );
	fclose( $fp );

	?>
	<script type="text/javascript">
	narnoo_video.flashvars[<?php echo $narnoo_video_shortcode_count; ?>].swfMovie = encodeURIComponent("<?php echo htmlspecialchars_decode( uncdata( $video->video_stream_path ) ); ?>");
	narnoo_video.flashvars[<?php echo $narnoo_video_shortcode_count; ?>].swfMovieHQ = encodeURIComponent("<?php echo htmlspecialchars_decode( uncdata( $video->video_hqstream_path ) ); ?>");
	narnoo_video.flashvars[<?php echo $narnoo_video_shortcode_count; ?>].swfThumb = encodeURIComponent("<?php echo $pause_image_fileurl; ?>");	
	</script>
	<video width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" controls="controls" poster="<?php echo esc_attr( $pause_image_fileurl ); ?>">
		<source src="<?php echo uncdata( $video->video_stream_path ); ?>" type="video/mp4" />
        <source src="<?php echo uncdata( $video->video_webm_path ); ?>" type="video/webm" />
		Your browser does not support the html5 video tag.
	</video>
	<?php
	
	return;
}

// number of video shortcodes on current page so far; used as unique id for the fallback div of each embedded video on page
global $narnoo_video_shortcode_count;	

if ( ! isset( $narnoo_video_shortcode_count ) ) {
	$narnoo_video_shortcode_count = 0;
}

$shortcode_defaults = array(
	'id' => 0,			// the only required attribute
	'width' => '640',
	'height' => '360',
);

$flash_vars_defaults = array(
	'autoplay' => null,			// acceptable value: 'yes'
	'play_btn_scale' => null,	// all remaining attributes accept hexadecimal color e.g. 'FFFFFF'
	'text_color' => null,
	'play_color' => null,
	'playover_color' => null,
	'playbk_color' => null,
	'playbk_alpha' => null,
	'playbkover_color' => null,
	'rollover_color' => null,
	'bar_color' => null,
	'clocktotal_color' => null,
	'timesplit_color' => null,
	'clock_color' => null,
	'loaded_color' => null,
	'playhead_color' => null,
	'progress_color' => null
);

extract( shortcode_atts( array_merge( $shortcode_defaults, $flash_vars_defaults ), $atts ) );

// load Javascripts that should only be loaded once per page using wp_enqueue_script (Wordpress 3.3+ only)
if ( Narnoo_Operator_Helper::wp_supports_enqueue_script_in_body() ) {
	Narnoo_Operator::load_scripts_for_video();
}
?>			
<div style="height: <?php echo esc_attr( $height ); ?>px; width: <?php echo esc_attr( $width ); ?>px;">
	<div style="height: <?php echo esc_attr( $height ); ?>px; width: <?php echo esc_attr( $width ); ?>px;" class="narnoo_video" data-id="<?php echo $id; ?>" data-count="<?php echo $narnoo_video_shortcode_count; ?>" data-width="<?php echo esc_attr( $width ); ?>" data-height="<?php echo esc_attr( $height ); ?>" id="narnooVideoFallback<?php echo $narnoo_video_shortcode_count; ?>">
		<div style="height: <?php echo esc_attr( $height ); ?>px; width: <?php echo esc_attr( $width ); ?>px; background: #ffffff url(<?php echo plugin_dir_url( __FILE__ ) . 'images/loader.gif'; ?>) no-repeat center center;">
		</div>
	</div>
</div>

<script type="text/javascript">
	if (typeof narnoo_video === 'undefined') {
		narnoo_video = { count: 0, widths: [], heights: [], flashvars: [] };
		narnoo_video_url = '<?php echo plugin_dir_url( __FILE__ ); ?>';
		narnoo_video_file_url = '<?php echo __FILE__; ?>';
		narnoo_video_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	}
	flashvars = {};
	<?php 
	foreach ( $flash_vars_defaults as $flash_var_name => $flash_var_default_value ) {
		$flash_var_value = ${$flash_var_name};
		if ( ! is_null( $flash_var_value ) ) {
			echo "	flashvars.$flash_var_name = '$flash_var_value'; \n";
		}
	}
	?>
	narnoo_video.heights.push("<?php echo intval( $height ); ?>");
	narnoo_video.widths.push("<?php echo intval( $width ); ?>");
	narnoo_video.flashvars.push(flashvars);
	narnoo_video.count++;	
</script>
<?php
$narnoo_video_shortcode_count++;
