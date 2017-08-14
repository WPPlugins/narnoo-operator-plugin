<?php
if ( isset( $_GET['mediaid'] ) ) {
	// force download
	require_once dirname( __FILE__ ) . '/../class.download.php';

	$request = Narnoo_Operator_Helper::init_api();			
	if ( is_null( $request ) ) {
		echo __( 'Need API keys to request links', NARNOO_OPERATOR_I18N_DOMAIN );
		return;
	} 

	$dwn = new DownloadOperatorMedia( $request );
	$file = $dwn->downloadMedia( $_GET['mediaid'] );
}
?>