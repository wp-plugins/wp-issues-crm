<?php
/**
*
* class-wic-admin-navigation.php
*
*/


class WIC_Admin_Navigation {
	/* 
	*  This is just a router from the top WP menu level 
	*  (routing for buttons within WP_Issues_CRM happens in WIC_Admin_Dashboard and within fields/options pages)
	*
	*/

	// sets up menu
	public function __construct() { // class instantiated in plugin main 
		add_action( 'admin_menu', array ( $this, 'menu_setup' ) ); // precedes admin_init
	}	

	// add menu links to wp admin
	public function menu_setup () {  
	
		// get menu positioning and required security level		
		$wic_plugin_options = get_option( 'wp_issues_crm_plugin_options_array' ); 
		$menu_position = isset( $wic_plugin_options['menu_position_top'] ) ? '4.19544595294' : null; // pick arbitrary decimal number to avoid possibility of conflicts
		$main_security_setting = isset( $wic_plugin_options['access_level_required'] ) ? $wic_plugin_options['access_level_required'] : 'activate_plugins';

		// add menu and submenu pages
		add_menu_page( 'WP Issues CRM', 'WP Issues CRM', $main_security_setting, 'wp-issues-crm-main', array ( $this, 'do_dashboard' ), 'dashicons-smiley', $menu_position ); 		
		add_submenu_page ( 'wp-issues-crm-main', 'WIC Preferences', 'User Preferences', $main_security_setting, 'wp-issues-crm-preferences', array ( $this, 'do_preferences') );
		// show settings, fields and options pages only to administrators 
		add_submenu_page( 'wp-issues-crm-main', 'Options', 'Options', 'activate_plugins', 'wp-issues-crm-options', array ( $this, 'do_options' ) );
		add_submenu_page( 'wp-issues-crm-main', 'Fields', 'Fields', 'activate_plugins', 'wp-issues-crm-fields', array ( $this, 'do_fields' ) );
		// need to run add setting  before add page -- too late to register if try not to do the work until on the page 		
		$wic_admin_settings = new WIC_Admin_Settings; 
		add_submenu_page( 'wp-issues-crm-main', 'WIC Settings', 'Settings', 'activate_plugins', 'wp-issues-crm-settings', array ( $wic_admin_settings, 'wp_issues_crm_settings' ) ); 
		add_submenu_page( 'wp-issues-crm-main', 'WIC Statistics', 'Statistics', $main_security_setting, 'wp-issues-crm-statistics', array ( $this, 'do_statistics' ) );	
	}


	/*
	*
	* the following five functions, which invoke the main working classes of the plugin, are not activated until navigation to them is known
	*
	*/

	public function do_dashboard (){
		self::admin_check_security( '' );
		echo '<div class="wrap"><h2 id="wic-main-header">' . __( 'WP Issues CRM', 'wp-issues-crm' ) . '</h2>';	
		$wic_admin_dashboard = new WIC_Admin_Dashboard;
		echo '<div>';
	}
	
	public function do_fields () {
		self::admin_check_security( 'activate_plugins' );
		echo '<div class="wrap"><h2>' . __( 'Customize Fields', 'wp-issues-crm' ) . '</h2>';
			$wic_admin_field = new WIC_Entity_Data_Dictionary; 
				// not to be confused with the data dictionary cache itself, this class is the editor of the dictionary
		echo '<div>';
	}
	
	public function do_options () {
		self::admin_check_security( 'activate_plugins' );
		echo '<div class="wrap"><h2>'  .__( 'Manage Option Groups', 'wp-issues-crm' ) . '</h2>';		
			$wic_admin_option = new WIC_Entity_Option_Group;
		echo '<div>';
	}
	
	public function do_statistics () {
		self::admin_check_security( '' );
	 	WIC_Admin_Statistics::generate_storage_statistics(); 
	}
	
	public function do_preferences (){ 
		self::admin_check_security( '' );  
		echo '<div class="wrap"><h2>' . __( 'User Preferences', 'wp-issues-crm' ) . '</h2>';	
		$wic_entity_user = new WIC_Entity_User;
		echo '<div>';
	}		

	private static function admin_check_security ( $required_capability ) {
		// is seeking access to administrator settings, must be administrator;
		if ( 'activate_plugins' == $required_capability && ! current_user_can ( $required_capability ) ) { 
			WIC_Function_Utilities::wic_error ( __( 'Administrative permissions inadequate.', 'wp-issues-crm' ), __FILE__, __LINE__, __METHOD__, true );	
		// otherwise, check whether user has the required capability level 
		} 	else {
			// get option setting
			$wic_plugin_options = get_option( 'wp_issues_crm_plugin_options_array' ); 
			// if not set, limit access to administrators
			$main_security_setting = isset( $wic_plugin_options['access_level_required'] ) ? $wic_plugin_options['access_level_required'] : 'activate_plugins';
			if ( ! current_user_can ( $main_security_setting ) ) {
				WIC_Function_Utilities::wic_error ( __( 'Please consult your administrator for access to these functions', 'wp-issues-crm' ), __FILE__, __LINE__, __METHOD__, true );	
			}			
		}
		// is the logged in user purporting to submit a previous form; if so, have a nonce?
		// note: no update action taken by subordinate classes without a button, 
		//   so either no button so no action, or have a button and a nonce, or die
		if ( isset ( $_POST['wic_form_button'] ) ) {
			// check nonces and die if not OK			
			if ( isset($_POST['wp_issues_crm_post_form_nonce_field']) &&
				check_admin_referer( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field')) { // if OK, do nothing
			} else { 
				WIC_Function_Utilities::wic_error ( __( 'Apparent cross-site scripting or configuration error.', 'wp-issues-crm' ), __FILE__, __LINE__, __METHOD__, true );
				}	
		}	
	}

}