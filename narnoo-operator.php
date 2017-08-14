<?php
/*
Plugin Name: Narnoo Operator
Plugin URI: http://narnoo.com/
Description: Allows Wordpress users to manage and include their Narnoo media into their Wordpress site. You will need a Narnoo API key pair to include your Narnoo media. You can find this by logging into your account at Narnoo.com and going to Account -> View APPS. 
Version: 1.0.3
Author: Narnoo Wordpress developer
Author URI: http://www.narnoo.com/
License: GPL2 or later
*/

/*  Copyright 2012  Narnoo.com  (email : info@narnoo.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// plugin definitions
define( 'NARNOO_OPERATOR_PLUGIN_NAME', 'Narnoo Operator' );
define( 'NARNOO_OPERATOR_CURRENT_VERSION', '1.0.0' );
define( 'NARNOO_OPERATOR_I18N_DOMAIN', 'narnoo-operator' );

define( 'NARNOO_OPERATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NARNOO_OPERATOR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'NARNOO_OPERATOR_SETTINGS_PAGE', 'options-general.php?page=narnoo-operator-api-settings' );

// include files
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-helper.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-followers-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-images-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-brochures-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-videos-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-albums-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-products-accordion-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-library-images-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo/class-narnoo-request.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo/class-operator-narnoo-request.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo/utilities.php' );

// begin!
new Narnoo_Operator();

class Narnoo_Operator {

	/**
	 * Plugin's main entry point.
	 **/
	function __construct() {
		register_uninstall_hook( __FILE__, array( 'NarnooOperator', 'uninstall' ) );
				
		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( &$this, 'load_language_file' ) );
			add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );
			
			add_action( 'admin_notices', array( &$this, 'display_reminders' ) );
			add_action( 'admin_menu', array( &$this, 'create_menus' ) );
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_enqueue_scripts', array( 'Narnoo_Operator_Products_Accordion_Table', 'load_scripts' ) );
			
			add_filter( 'media_upload_tabs', array( &$this, 'add_narnoo_library_menu_tab' ) );
			add_action( 'media_upload_narnoo_library', array( &$this, 'media_narnoo_library_menu_handle') );

			add_action( 'wp_ajax_narnoo_operator_api_request', array( 'Narnoo_Operator_Helper', 'ajax_api_request' ) );			
		} else {
			add_shortcode( 'narnoo_operator_brochure', array( &$this, 'narnoo_operator_brochure_shortcode' ) );
			add_shortcode( 'narnoo_operator_video', array( &$this, 'narnoo_operator_video_shortcode' ) );
			add_shortcode( 'narnoo_operator_tiles_gallery', array( &$this, 'narnoo_operator_tiles_gallery_shortcode' ) );
			add_shortcode( 'narnoo_operator_single_link_gallery', array( &$this, 'narnoo_operator_single_link_gallery_shortcode' ) );
			add_shortcode( 'narnoo_operator_slider_gallery', array( &$this, 'narnoo_operator_slider_gallery_shortcode' ) );
			add_shortcode( 'narnoo_operator_grid_gallery', array( &$this, 'narnoo_operator_grid_gallery_shortcode' ) );
			
			add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts' ) );
			add_action( 'init', array( &$this, 'check_request' ) );
			
			add_filter( 'widget_text', 'do_shortcode' );
		}
		
		add_action( 'wp_ajax_narnoo_operator_lib_request', array( &$this, 'narnoo_operator_ajax_lib_request' ) );			
		add_action( 'wp_ajax_nopriv_narnoo_operator_lib_request', array( &$this, 'narnoo_operator_ajax_lib_request' ) );			
	}

	/**
	 * Add Narnoo Library tab to Wordpress media upload menu.
	 **/
	function add_narnoo_library_menu_tab( $tabs ) {
		$newTab = array( 'narnoo_library' => __( 'Narnoo Library', NARNOO_OPERATOR_I18N_DOMAIN ) );
		return array_merge($tabs, $newTab);
	}	
	
	/**
	 * Handle display of Narnoo library in Wordpress media upload menu.
	 **/
	function media_narnoo_library_menu_handle() {
		return wp_iframe( array( &$this, 'media_narnoo_library_menu_display' ) );
	}
	
	function media_narnoo_library_menu_display() {
		media_upload_header();
		$narnoo_operator_library_images_table = new Narnoo_Operator_Library_Images_Table();
		?>
			<form id="narnoo-images-form" class="media-upload-form" method="post" action="">
				<?php
				$narnoo_operator_library_images_table->prepare_items();
				$narnoo_operator_library_images_table->display();
				?>
			</form>			
		<?php
	}

	/**
	 * Clean up upon plugin uninstall.
	 **/
	static function uninstall() {
		unregister_setting( 'narnoo_operator_settings', 'narnoo_operator_settings', array( &$this, 'settings_sanitize' ) );
	}
		
	/**
	 * Add settings link for this plugin to Wordpress 'Installed plugins' page.
	 **/
	function plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( dirname(__FILE__) . '/narnoo-operator.php' ) ) {
			$links[] = '<a href="' . NARNOO_OPERATOR_SETTINGS_PAGE . '">' . __('Settings') . '</a>';
		}

		return $links;
	}

	/**
	 * Load language file upon plugin init (for future extension, if any)
	 **/
	function load_language_file() {
		load_plugin_textdomain( NARNOO_OPERATOR_I18N_DOMAIN, false, NARNOO_OPERATOR_PLUGIN_PATH . 'languages/' ); 
	}
	
	/**
	 * Display reminder to key in API keys in admin backend.
	 **/
	function display_reminders() {
		$options = get_option( 'narnoo_operator_settings' );
		
		if ( empty( $options['access_key'] ) || empty( $options['secret_key'] ) ) {
			Narnoo_Operator_Helper::show_notification(
				sprintf( 
					__( '<strong>Reminder:</strong> Please key in your Narnoo API settings in the <strong><a href="%s">Settings->Narnoo API</a></strong> page.', NARNOO_OPERATOR_I18N_DOMAIN ), 
					NARNOO_OPERATOR_SETTINGS_PAGE 
				)
			);
		}
	}
	
	/**
	 * Add admin menus and submenus to backend.
	 **/
	function create_menus() {
		// add Narnoo API to settings menu
		add_options_page( 
			__( 'Narnoo API Settings', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Narnoo API', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'narnoo-operator-api-settings', 
			array( &$this, 'api_settings_page' )
		); 
		
		// add main Narnoo Media menu
		add_menu_page(
			__( 'Narnoo Media', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Narnoo', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'narnoo-operator-followers', 
			array( &$this, 'followers_page' ),   
			NARNOO_OPERATOR_PLUGIN_URL . 'images/icon-16.png', 
			11
		);
		
		// add submenus to Narnoo Media menu
		$page = add_submenu_page( 
			'narnoo-operator-followers',
			__( 'Narnoo Media - Followers', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Followers', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'narnoo-operator-followers', 
			array( &$this, 'followers_page' )
		); 
		add_action( "load-$page", array( 'Narnoo_Operator_Followers_Table', 'add_screen_options' ) );

		$page = add_submenu_page( 
			'narnoo-operator-followers',
			__( 'Narnoo Media - Albums', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Albums', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'narnoo-operator-albums', 
			array( &$this, 'albums_page' )
		); 
		add_action( "load-$page", array( 'Narnoo_Operator_Albums_Table', 'add_screen_options' ) );

		$page = add_submenu_page( 
			'narnoo-operator-followers',
			__( 'Narnoo Media - Images', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Images', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'narnoo-operator-images', 
			array( &$this, 'images_page' ) 
		); 
		add_action( "load-$page", array( 'Narnoo_Operator_Images_Table', 'add_screen_options' ) );

		$page = add_submenu_page( 
			'narnoo-operator-followers',
			__( 'Narnoo Media - Brochures', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Brochures', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'narnoo-operator-brochures', 
			array( &$this, 'brochures_page' ) 
		); 
		add_action( "load-$page", array( 'Narnoo_Operator_Brochures_Table', 'add_screen_options' ) );

		$page = add_submenu_page( 
			'narnoo-operator-followers',
			__( 'Narnoo Media - Videos', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Videos', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'narnoo-operator-videos', 
			array( &$this, 'videos_page' )
		); 
		add_action( "load-$page", array( 'Narnoo_Operator_Videos_Table', 'add_screen_options' ) );

		global $narnoo_operator_text_page;
		$narnoo_operator_text_page = add_submenu_page( 
			'narnoo-operator-followers',
			__( 'Narnoo Media - Text', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Text', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'narnoo-operator-text', 
			array( &$this, 'text_page' )
		); 
	}

	/**
	 * Upon admin init, register plugin settings and Narnoo shortcodes button, and define input fields for API settings.
	 **/
	function admin_init() {
		register_setting( 'narnoo_operator_settings', 'narnoo_operator_settings', array( &$this, 'settings_sanitize' ) );
		
		add_settings_section( 
			'api_settings_section', 
			__( 'API Settings', NARNOO_OPERATOR_I18N_DOMAIN ), 
			array( &$this, 'settings_api_section' ), 
			'narnoo_operator_api_settings' 
		);
		
		add_settings_field( 
			'access_key', 
			__( 'Acesss key', NARNOO_OPERATOR_I18N_DOMAIN ), 
			array( &$this, 'settings_access_key' ), 
			'narnoo_operator_api_settings', 
			'api_settings_section' 
		);
		
		add_settings_field( 
			'secret_key', 
			__( 'Secret key', NARNOO_OPERATOR_I18N_DOMAIN ), 
			array( &$this, 'settings_secret_key' ), 
			'narnoo_operator_api_settings', 
			'api_settings_section' 
		);
		
		// register Narnoo shortcode button and MCE plugin
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		
		if ( get_user_option('rich_editing') == 'true' ) {
			add_filter( 'mce_external_plugins', array( &$this, 'add_shortcode_plugin' ) );
			add_filter( 'mce_buttons', array( &$this, 'register_shortcode_button' ) );
		}
	}
	
	function settings_api_section() {
		echo '<p>' . __( 'You can edit your Narnoo API settings below.', NARNOO_OPERATOR_I18N_DOMAIN ) . '</p>';
	}
	
	function settings_access_key() {
		$options = get_option( 'narnoo_operator_settings' );
		echo "<input id='access_key' name='narnoo_operator_settings[access_key]' size='40' type='text' value='" . esc_attr($options['access_key']). "' />";
	}
	
	function settings_secret_key() {
		$options = get_option( 'narnoo_operator_settings' );
		echo "<input id='secret_key' name='narnoo_operator_settings[secret_key]' size='40' type='text' value='" . esc_attr($options['secret_key']). "' />";
	}
	
	/**
	 * Sanitize input settings.
	 **/
	function settings_sanitize( $input ) {
		$new_input['access_key'] = trim( $input['access_key'] );
		$new_input['secret_key'] = trim( $input['secret_key'] );
		return $new_input;
	}
	
	/**
	 * Display API settings page.
	 **/
	function api_settings_page() {
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo API Settings', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form action="options.php" method="post">
				<?php settings_fields( 'narnoo_operator_settings' ); ?>
				<?php do_settings_sections( 'narnoo_operator_api_settings' ); ?>
		
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</form>
			<?php
			$request = Narnoo_Operator_Helper::init_api();
			
			$operator = null;
			if ( ! is_null( $request ) ) {
				try {
					$operator = $request->getDetails();
				} catch ( Exception $ex ) {
					$operator = null;
					Narnoo_Operator_Helper::show_api_error( $ex );
				}
			}
			
			if ( ! is_null( $operator ) ) {
				?>
				<h3><?php _e( 'Operator Details', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h3>
				<table class="form-table">
					<tr><th><?php _e( 'ID', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_id; ?></td></tr>
					<tr><th><?php _e( 'UserName', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_username; ?></td></tr>
					<tr><th><?php _e( 'Email', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->email; ?></td></tr>
					<tr><th><?php _e( 'Business Name', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_businessname; ?></td></tr>
					<tr><th><?php _e( 'Contact Name', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_contactname; ?></td></tr>
					<tr><th><?php _e( 'Country', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->country_name; ?></td></tr>
					<tr><th><?php _e( 'Post Code', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->postcode; ?></td></tr>
					<tr><th><?php _e( 'Suburb', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->suburb; ?></td></tr>
					<tr><th><?php _e( 'State', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->state; ?></td></tr>
					<tr><th><?php _e( 'Phone', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->phone; ?></td></tr>
					<tr><th><?php _e( 'URL', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_url; ?></td></tr>
					<tr><th><?php _e( 'Total Images', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->total_images; ?></td></tr>
					<tr><th><?php _e( 'Total Brochures', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->total_brochures; ?></td></tr>
					<tr><th><?php _e( 'Total Videos', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->total_videos; ?></td></tr>
					<tr><th><?php _e( 'Total Products', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->total_products; ?></td></tr>					
				</table>
				<?php
			}
			?>		
		</div>		
		<?php
	}
	
	/**
	 * Display Narnoo Followers page.
	 **/
	function followers_page() {
		global $narnoo_operator_followers_table;		
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Followers', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-followers-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_followers_table->get_pagenum() ) ) ); ?>">
				<?php 
				$narnoo_operator_followers_table->prepare_items();
				$narnoo_operator_followers_table->display(); 
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display Narnoo Albums page.
	 **/
	function albums_page() {		
		global $narnoo_operator_albums_table;		
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Albums', NARNOO_OPERATOR_I18N_DOMAIN ) ?>	
				<a href="?<?php echo build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_albums_table->get_pagenum(), 'action' => 'create' ) ); ?>" class="add-new-h2"><?php echo esc_html_x( 'Create New', NARNOO_OPERATOR_I18N_DOMAIN ); ?></a></h2>
			<form id="narnoo-albums-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_albums_table->get_pagenum(), 'album_page' => $narnoo_operator_albums_table->current_album_page, 'album' => $narnoo_operator_albums_table->current_album_id, 'album_name' => urlencode( $narnoo_operator_albums_table->current_album_name ) ) ) ); ?>">
			<?php
			if ( $narnoo_operator_albums_table->prepare_items() ) {
				?><h3>Currently viewing album: <?php echo $narnoo_operator_albums_table->current_album_name; ?></h3><?php
				_e( 'Select album:', NARNOO_OPERATOR_I18N_DOMAIN );
				echo $narnoo_operator_albums_table->select_album_html_script;
				submit_button( __( 'Go', NARNOO_OPERATOR_I18N_DOMAIN ), 'button-secondary action', false, false, array( 'id' => "album_select_button" ) );
				
				$narnoo_operator_albums_table->views();
				$narnoo_operator_albums_table->display();
			}
			?>
			</form>			
		</div>
		<?php
	}


	/**
	 * Display Narnoo Images page.
	 **/
	function images_page() {
		global $narnoo_operator_images_table;		
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Images', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-images-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_images_table->get_pagenum() ) ) ); ?>">
				<?php
				if ( $narnoo_operator_images_table->prepare_items() ) {
					$narnoo_operator_images_table->display();
				}
				?>
			</form>			
		</div>
		<?php
	}

	/**
	 * Display Narnoo Brochures page.
	 **/
	function brochures_page() {
		global $narnoo_operator_brochures_table;		
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Brochures', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-brochures-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_brochures_table->get_pagenum() ) ) ); ?>">
				<?php
				if ( $narnoo_operator_brochures_table->prepare_items() ) {
					$narnoo_operator_brochures_table->display();
				}
				?>
			</form>			
		</div>
		<?php
	}

	/**
	 * Display Narnoo Videos page.
	 **/
	function videos_page() {
		global $narnoo_operator_videos_table;		
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Videos', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-videos-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_videos_table->get_pagenum() ) ) ); ?>">
				<?php
				if ( $narnoo_operator_videos_table->prepare_items() ) {
					$narnoo_operator_videos_table->display();
				}
				?>
			</form>			
		</div>
		<?php
	}

	/**
	 * Display Narnoo Text page.
	 **/
	function text_page() {
		$narnoo_operator_products_accordion_table = new Narnoo_Operator_Products_Accordion_Table();
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Text', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-text-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_products_accordion_table->get_pagenum() ) ) ); ?>">
				<?php
				$narnoo_operator_products_accordion_table->prepare_items();
				$narnoo_operator_products_accordion_table->display();
				?>
			</form>			
		</div>
		<?php
	}
	
	/** 
	 * Register button for Narnoo shortcodes.
	 **/
	function register_shortcode_button( $buttons ) {
		array_push( $buttons, "|", "narnoo_operator_shortcodes" );
		return $buttons;
	}

	/**
	 * Add new MCE plugin for Narnoo shortcodes.
	 **/
	function add_shortcode_plugin( $plugin_array ) {
		$plugin_array['narnoo_operator_shortcodes'] = NARNOO_OPERATOR_PLUGIN_URL . 'tinymce-custom/mce/narnoo_operator_shortcodes/narnoo_operator_shortcodes_plugin.js';
		return $plugin_array;
	}
	
	/**
	 * Process frontend AJAX requests triggered by shortcodes.
	 **/
	function narnoo_operator_ajax_lib_request() {
		ob_start();
		require( $_REQUEST['lib_path'] );
		echo json_encode( array( 'response' => ob_get_clean() ) );
		die();
	}

	/**
	 * Display specified brochure with thumbnail or preview image, link to PDF file and caption.
	 **/
	function narnoo_operator_brochure_shortcode( $atts ) {
		ob_start();
		require( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo_brochure/brochure.php' );
		return ob_get_clean();
	}
	
	/**
	 * Display embedded video player.
	 **/
	function narnoo_operator_video_shortcode( $atts ) {
		ob_start();
		require( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo_video/video.php' );
		return ob_get_clean();
	}
	
	/**
	 * Display tiles gallery.
	 **/
	function narnoo_operator_tiles_gallery_shortcode( $atts ) {
		ob_start();
		require( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo_tiles_gallery/tiles.php' );
		return ob_get_clean();
	}
	

	/**
	 * Display grid gallery.
	 **/
	function narnoo_operator_grid_gallery_shortcode( $atts ) {
		ob_start();
		require( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo_grid_gallery/gallery-ajax.php' );
		return ob_get_clean();
	}
	

	/**
	 * Display slider gallery.
	 **/
	function narnoo_operator_slider_gallery_shortcode( $atts ) {
		ob_start();
		require( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo_slider_gallery/gallery2.php' );
		return ob_get_clean();
	}


	/**
	 * Display single link gallery.
	 **/
	function narnoo_operator_single_link_gallery_shortcode( $atts ) {
		ob_start();
		require( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo_single_link_gallery/slideshow.php' );
		return ob_get_clean();
	}
	
	/**
	 * Loads Javascript files for tiles gallery shortcode.
	 **/
	static function load_scripts_for_tiles_gallery() {
		// register custom names and dependencies for the common scripts which are to be loaded only once per page with shortcode(s)
		wp_register_script( 'narnoo.jquery.tilesgallery', plugins_url( 'libs/narnoo_tiles_gallery/js/jquery.tilesgallery.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'narnoo.jquery.lightbox', plugins_url( 'libs/narnoo_tiles_gallery/js/jquery.lightbox-0.5.js', __FILE__ ), array( 'jquery' ) );

		// load the common scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'narnoo.jquery.tilesgallery' );
		wp_enqueue_script( 'narnoo.jquery.lightbox' );
		
		// pass full images URL path to lightbox script
		wp_localize_script( 'narnoo.jquery.lightbox', 'NarnooTilesParams', array( 'lightboxImagesUrl' => NARNOO_OPERATOR_PLUGIN_URL . 'libs/narnoo_tiles_gallery/img/' ) );

		// load individual shortcode Javascript in footer
		wp_enqueue_script( 'narnoo.tiles_gallery', plugins_url( 'libs/narnoo_tiles_gallery/tiles.js', __FILE__ ), array( 'jquery', 'narnoo.jquery.tilesgallery', 'narnoo.jquery.lightbox' ), false, true );
	}
	
	/**
	 * Loads Javascript files for slider gallery shortcode.
	 **/
	static function load_scripts_for_slider_gallery() {
		// register custom names and dependencies for the common scripts which are to be loaded only once per page with shortcode(s)
		wp_register_script( 'jquery.imagesloaded', plugins_url( 'libs/narnoo_slider_gallery/js/jquery.imagesloaded.min.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'narnoo.gallerify', plugins_url( 'libs/narnoo_slider_gallery/js/gallerify.min.js', __FILE__ ), array( 'jquery', 'jquery.imagesloaded' ) );

		// load the common scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery.imagesloaded' );
		wp_enqueue_script( 'narnoo.gallerify' );

		// pass full images URL path to lightbox script
		wp_localize_script( 'narnoo.gallerify', 'NarnooSliderParams', array( 'gallerifyImagesUrl' => NARNOO_OPERATOR_PLUGIN_URL . 'libs/narnoo_slider_gallery/images/' ) );

		// load individual shortcode Javascript in footer
		wp_enqueue_script( 'narnoo.slider', plugins_url( 'libs/narnoo_slider_gallery/gallery2.js', __FILE__ ), array( 'jquery', 'jquery.imagesloaded', 'narnoo.gallerify' ), false, true );
	}

	/**
	 * Loads Javascript files for single link gallery shortcode.
	 **/
	static function load_scripts_for_single_link_gallery() {
		// register custom names and dependencies for the common scripts which are to be loaded only once per page with shortcode(s)
		wp_register_script( 'narnoo.imagebox', plugins_url( 'libs/narnoo_single_link_gallery/imagebox/imagebox.min.js', __FILE__ ) );
		
		// load the common scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'narnoo.imagebox' );

		// load individual shortcode Javascript in footer
		wp_enqueue_script( 'narnoo.single_link_gallery', plugins_url( 'libs/narnoo_single_link_gallery/slideshow.js', __FILE__ ), array( 'jquery', 'narnoo.imagebox' ), false, true );
	}

	/**
	 * Loads Javascript files for video shortcode.
	 **/
	static function load_scripts_for_video() {
		// load the common scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'swfobject' );

		// load individual shortcode Javascript in footer
		wp_enqueue_script( 'narnoo.video', plugins_url( 'libs/narnoo_video/video.js', __FILE__ ), array( 'jquery', 'swfobject' ), false, true );
	}

	/**
	 * Loads Javascript files for brochure shortcode.
	 **/
	static function load_scripts_for_brochure() {
		// load the common scripts
		wp_enqueue_script( 'jquery' );

		// load individual shortcode Javascript in footer
		wp_enqueue_script( 'narnoo.brochure', plugins_url( 'libs/narnoo_brochure/brochure.js', __FILE__ ), array( 'jquery' ), false, true );
	}

	/**
	 * Loads Javascript files for grid gallery shortcode.
	 **/
	static function load_scripts_for_grid_gallery() {
		// register custom names and dependencies for the common scripts which are to be loaded only once per page with shortcode(s)
		wp_register_script( 'narnoo.jquery.fancybox', plugins_url( 'libs/narnoo_grid_gallery/_assets/js/fancybox/jquery.fancybox-1.3.4.pack.js', __FILE__ ), array( 'jquery' ) );

		// load the common scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'narnoo.jquery.fancybox' );
		
		// load individual shortcode Javascript in footer
		wp_enqueue_script( 'narnoo.grid_gallery', plugins_url( 'libs/narnoo_grid_gallery/_assets/js/gallery-ajax.js', __FILE__ ), array( 'jquery', 'narnoo.jquery.fancybox' ), false, true );
	}
	
	/**
	 * Loads Javascripts for all shortcodes in <head> element (will be loaded on every single page, even if shortcode is not used!).
	 * This is necessary only for Wordpress earlier than 3.3, as late loading in body is not supported.
	 **/
	function load_scripts() {								
		if ( ! Narnoo_Operator_Helper::wp_supports_enqueue_script_in_body() ) {
			Narnoo_Operator::load_scripts_for_tiles_gallery();
			Narnoo_Operator::load_scripts_for_single_link_gallery();
			Narnoo_Operator::load_scripts_for_slider_gallery();
			Narnoo_Operator::load_scripts_for_video();
			Narnoo_Operator::load_scripts_for_brochure();
			Narnoo_Operator::load_scripts_for_grid_gallery();
		}
	}
	
	
	/**
	 * Intercept any special $_GET or $_POST handling.
	 **/
	function check_request() {
		if ( isset( $_GET['narnoo_grid_download_image'] ) ) {
			// force download image
			require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo_grid_gallery/_includes/ajax/download.php' );
			die();
		}
	}
	
}
