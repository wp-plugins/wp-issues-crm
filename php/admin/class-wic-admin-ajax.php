<?php
/*
*
* Handles AJAX Calls -- checks call parameters, nonce and capabilities required and routes them to appropriate call
*
*/

class WIC_Admin_Ajax {
	
	public function route_ajax() { 
		
		// first make sure that action and subaction are present as expected
		if ( ! isset ( $_POST['action'] ) || ! isset ( $_POST['sub_action'] ) ) {
			die ( __( 'Bad call to WIC_Admin_Ajax', 'wp-issues-crm' ) );		
		}		

		// now check nonce -- nonce set in script localization, see WIC_Admin_Setup::add_wic_scripts
		// not updating nonce -- relying on availability for some hours, long enough for user to probably refresh screen
		$nonce = $_POST['wic_ajax_nonce'];
		if ( ! wp_verify_nonce ( $nonce, 'wic_ajax_nonce' ) ) {
			 die ( __( 'Bad nonce in AJAX call to WIC_Admin_Ajax', 'wp_issues_crm' ) );		
		}
		/**
		* now, for each WP_Issues_CRM Ajax Call type, check security and route
		*	
		* note: choosing note to consistently instantiate an entity for AJAX calls -- keep as light as possible
		*
		* on client side, sending:
		*	var postData = {
		*		action: 'wp_issues_crm', 
		*		wic_ajax_nonce: wic_ajax_object.wic_ajax_nonce,
		*		entity: entity,
		*		sub_action: action,
		*		id_requested: idRequested,
		*		wic_data: JSON.stringify( data )
		*		};
		*		 
		*/	
		if ( 'remap_columns' == $_POST['sub_action'] ) {
				self::ajax_check_capability( '' ); // use access setting for general WP Issues CRM access 
				WIC_Entity_Upload::remap_columns( $_POST['wic_data'] );	
		} elseif ( 'get_column_map' == $_POST['sub_action'] ) {
				self::ajax_check_capability( '' );  
				WIC_Entity_Upload::get_column_map( $_POST['id_requested'] );	
		} elseif ( 'update_column_map' == $_POST['sub_action'] ) {
				self::ajax_check_capability( '' );  
				WIC_Entity_Upload::update_column_map( $_POST['id_requested'], $_POST['wic_data'] );	
		}
	}	

	private static function ajax_check_capability ( $required_capability ) {

		if ( 'activate_plugins' == $required_capability && ! current_user_can ( $required_capability ) ) { 
			wp_die( __( 'Administrator level security failure on Ajax access.', 'wp-issues-crm' ) );	
		// otherwise, check whether user has the required capability level 
		} 	else {
			// get option setting
			$wic_plugin_options = get_option( 'wp_issues_crm_plugin_options_array' ); 
			// if not set, limit access to administrators
			$main_security_setting = isset( $wic_plugin_options['access_level_required'] ) ? $wic_plugin_options['access_level_required'] : 'activate_plugins';
			if ( ! current_user_can ( $main_security_setting ) ) {
				wp_die( __( 'Security failure on Ajax access.', 'wp-issues-crm' ) );	
			}			
		}
	}







}