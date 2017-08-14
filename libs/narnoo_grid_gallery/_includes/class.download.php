<?php
/* Class to download media content.
* Date: 23-11-12
* Discription: Pass Image ID, and force download the HR version of that media.
* Author: James Wells
* Version: v1.0
*
*/


class DownloadOperatorMedia {
	
	private $request = null;
	
	function __construct( $p_request ) {
		$this->request = $p_request;
	}
	
	/* Description: function to determine the media type.
	*
	*@ public function
	*@ param: $op_id + $media_id + $media_type 
	*
	*/
	public function downloadMedia($media_id){
		
	//find out media type
	
	if(isset($media_id)){
		
		//get image link
		$imageLink = $this->getImageLink($media_id);
		//download file
		$file = $this->processDownload($imageLink,$media_type);
		
		
	} else {
		exit('Media type not provided');		
	}
		
	}
	
	
	/* Discription: to retrieve the image download link from Narnoo API.
	*
	*@ private function
	*@ param: $media_id
	*
	*/
	
	public function getImageLink($media_id){
	
		$request = $this->request;
	
		//call the SDK downloadImage function
		try {
			$item = $request->downloadImage ( $media_id );
		} catch ( Exception $ex ) {
			$error = $ex;
		}
		//return the Narnoo Response
		if (isset ( $error )) {
			return $error->getMessage ();
		} else {
			//return the image download link
			return uncdata ( $item->download_image_link );
		}
			
	}
	
	
	
	/* Class to download file.
	*
	*@ private function
	*@ param: $link - from Narnoo API + media_type so we know the mime (if needed)
	*
	*/
	
	private function processDownload($link){
		//create a correct link
		$file_name = str_replace('&amp;','&',$link);
		//create download headers	
		header('Content-Description: File Transfer');
		header('Content Type: application/force-download');
		header('Content-Transfer-Encoding: binary');
		header("Content-Disposition: attachment; filename=\"Image.jpg\";");
		
		//download bytes from remote server, and flush into current response.
		//$ch = curl_init();
		//curl_setopt ( $ch, CURLOPT_URL, $file_name );
		//curl_setopt($ch, CURLOPT_HEADER, 0);
		//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
		//curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //not necessary unless the file redirects (like the PHP example we're using here)
		//$content = curl_exec($ch);
		//curl_close($ch);
		
		echo wp_remote_retrieve_body( wp_remote_get( $file_name ) );

		exit;	

	}
	
}


?>