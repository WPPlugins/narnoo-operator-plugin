<?php

require_once dirname( __FILE__ ) . '/../class.mobiledetect.php';
require_once dirname( __FILE__ ) . '/../class.download.php';
//detect if mobile
$detect = new Mobile_Detect();


if(!isset($_GET['narnoo_page'])){
$page = 1;	
} else {
$page = $_GET['narnoo_page'];		
}
$i=1;

if(isset($_GET['album_name'])){
	
$album_name = $_GET['album_name'];
$downloadable = isset( $_GET['downloadable'] ) ? $_GET['downloadable'] : '1';
$bandwidth = isset( $_GET['bandwidth'] ) ? $_GET['bandwidth'] : '0';
$narnoo_grid_shortcode_count = isset( $_GET['narnoo_grid_shortcode_count'] ) ? $_GET['narnoo_grid_shortcode_count'] : '';

// Make Narnoo API Request	
global $error_msg_prefix;
$request = Narnoo_Operator_Helper::init_api();			
if ( is_null( $request ) ) {
	echo $error_msg_prefix . __( 'Need API keys to request links', NARNOO_OPERATOR_I18N_DOMAIN );
	return;
} 
	
try {
	$list = $request->getAlbumImages ( $album_name, $page );
} catch ( Exception $ex ) {
	$error = $ex;
}
	
$dwnload = new DownloadOperatorMedia( $request );

//OUTPUT THE GALLERY
 echo '<div class="content narnoo-gallery">
				<div class="narnoo-gallery-wrap">
					<div class="narnoo-gallery-pager" >';
					
					if (isset ( $error )) {
							echo $error_msg_prefix . $error->getMessage ();
						} else {
					foreach ( $list->operator_albums_images as $image ) {
						
				echo 	'<div class="narnoo-gallery-item">
							<a title="'.$image->image_caption.'" rel="gallery_group" class="fancybox" href="'. $image->large_image_path .'"><img src="'. $image->thumb_image_path .'" alt="Passions Of Paradise '.$album_name.' album" /></a>';
						if( $downloadable == '1' ){
							echo '<div class="item-options">';
							
                        	if ( $bandwidth == '1' ) {
							
								echo '<a target="_blank" class="icon-button download" title="Open in new window" href="' . $dwnload->getImageLink($image->image_id) . '">Open in new window</a>';

							} else if ($detect->isMobile() && $detect->isTablet()){
							
								echo '<a class="icon-button download" title="Download" onClick="narnoo_alertMobile(\''.$dwnload->getImageLink($image->image_id).'\')" >Download</a>';
							
							} else {

								echo '<a class="icon-button download" href="?narnoo_grid_download_image=1&mediaid='.$image->image_id.'&type=image" target="narnoo-grid-download" title="Download" >Download</a>';
						
							}
						echo '</div>';
						
						}
						
						echo '</div>';
								}
                        }
                        
						echo '</div>
                     </div>';
						//The gallery pagination/options area.
                         $pages = $list->total_pages;
                         if($pages > 1){ 
                        
                            echo '<div class="pager">';
                                     while($i <= $pages){
                                   			echo '<a class="gallery-grid-btn gallery-grid-btn-primary gallery-grid-btn-small" onClick="narnoo_getGallery(\''.$narnoo_grid_shortcode_count.'\',\''.$album_name.'\',\''.$i.'\',\''.$downloadable.'\',\''.$bandwidth.'\')" href="javascript:void(0)" style="text-decoration:none">'.$i.'</a>';
									$i++;
									}
									
                           echo '</div>';
                         
                        } 
echo '</div>';
}


?>