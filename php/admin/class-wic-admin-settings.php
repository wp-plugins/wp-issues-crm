<?php
/**
*
* class-wic-admin-settings.php
*
*/


class WIC_Admin_Settings {
	/* 
	*
	*/

	// for wp admin settings (not the main fields and field options)
	private $plugin_options;

	// sets up WP settings interface	
	public function __construct() { // class instantiated in plugin main 
		add_action('admin_init', array ( $this, 'settings_setup') );
		$this->plugin_options = get_option( 'wp_issues_crm_plugin_options_array' );
	}	
	
	// define setting
	public function settings_setup() {
		
		// registering only one setting, which will be an array -- will set up nonces when called
		register_setting(
			'wp_issues_crm_plugin_options', // Option Group
			'wp_issues_crm_plugin_options_array', // Option Name
			array ( $this, 'sanitize' ) // Sanitize call back
		);

		// settings sections and fields dictate what is output when do_settings_sections is called passing the page ID
		// here 'page' is collection of settings, and can, but need not, equal a menu registered page (but needs to be invoked on one)	

       // Menu Position Setting
      add_settings_section(
            'menu_position', // setting ID
            'Menu Position', // Title
            array( $this, 'menu_position_legend' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

		// naming of the callback with array elements (in the callbacks) is what ties the option array together 		
      add_settings_field(
            'menu_position_top', // field id
            'High on Admin Menu', // field label
            array( $this, 'menu_position_top_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'menu_position' // settings section within page
       ); 


       // Security Settings
      add_settings_section(
            'security_settings', // setting ID
            'Security Settings', // Title
            array( $this, 'security_settings_legend' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

		// naming of the callback with array elements (in the callbacks) is what ties the option array together 		
      add_settings_field(
            'access_level_required', // field id
            'WP Issues CRM', // field label
            array( $this, 'access_level_required_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'security_settings' // settings section within page
       ); 
			
      add_settings_field(
            'access_level_required_downloads', // field id
            'WP Issues CRM Downloads', // field label
            array( $this, 'access_level_required_downloads_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'security_settings' // settings section within page
       ); 

       // Privacy Settings
      add_settings_section(
            'privacy_settings', // setting ID
            'Privacy Settings', // Title
            array( $this, 'privacy_settings_legend' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

		// naming of the callback with array elements (in the callbacks) is what ties the option array together 		
      add_settings_field(
            'all_posts_private', // field id
            'Make "Private" the default', // field label
            array( $this, 'all_posts_private_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'privacy_settings' // settings section within page
       ); 
			
      add_settings_field(
            'hide_private_posts', // field id
            'Always hide private posts.', // field label
            array( $this, 'hide_private_posts_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'privacy_settings' // settings section within page
       ); 

       // Preference Settings
      add_settings_section(
            'preference_settings', // setting ID
            'Preference Settings', // Title
            array( $this, 'preference_settings_legend' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

		// naming of the callback with array elements (in the callbacks) is what ties the option array together 		
      add_settings_field(
            'allow_issue_dropdown_preferences', // field id
            'Allow issue preferences', // field label
            array( $this, 'allow_issue_dropdown_preferences_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'preference_settings' // settings section within page
       ); 

		// Postal Interface Settings
      add_settings_section(
            'postal_address_interface', // setting ID
            'Postal Zip Code Lookup Settings', // Title
            array( $this, 'postal_address_interface_legend' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

		// naming of the callback with array elements (in the callbacks) is what ties the option array together 		
      add_settings_field(
            'use_postal_address_interface', // field id
            'Enable USPS Web Interface', // field label
            array( $this, 'use_postal_address_interface_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'postal_address_interface' // settings section within page
       ); 
			
      add_settings_field(
            'user_name_for_postal_address_interface', // field id
            'USPS Web Tools User Name', // field label
            array( $this, 'user_name_for_postal_address_interface_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'postal_address_interface' // settings section within page
       ); 
    
	// Uninstall Settings (legend only)
      add_settings_section(
            'uninstal', // setting ID
            'Uninstalling WP Issues CRM', // Title
            array( $this, 'uninstall_legend' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

	}

	/*
	*
	* Menu Position Callbacks
	*
	*/
	// section legend call back
	public function menu_position_legend() {
		echo '<p>' . __('By default WP Issues CRM will appear at the bottom of the left sidebar menu in the Wordpress Admin Screen.  Use this setting
		to promote it to the position just above Posts.', 'wp-issues-crm' ) . '</p>';
	}

	// setting field call back	
	public function menu_position_top_callback() {
		printf( '<input type="checkbox" id="menu_position_tope" name="wp_issues_crm_plugin_options_array[menu_position_top]" value="%s" %s />',
            1, checked( '1', isset ( $this->plugin_options['menu_position_top'] ), false ) );
	}

	/*
	*
	* Security Callbacks
	*
	*/
	// section legend call back
	public function security_settings_legend() {
		echo '<p>' . __( 'You can decide who among your user community can access (a) basic WP Issues CRM functions; (b) downloads.
			Administrators always have access to both.  You can more broadly grant access to Editors and to Authors. ', 'wp-issues-crm' ) .  
			__( 'You can assign to some users the role "Constituent Managers" (via their User Profile).   
			They will have no Wordpress editing capabilities and will have access to WP Issues CRM only if you choose
			"Only Constituent Managers and Administrators" here.', 'wp-issues-crm' ).   '</p>';
	}

	// setting field call back	
	public function access_level_required_callback() { 
		global $wic_db_dictionary; 
		$option_array = $wic_db_dictionary->lookup_option_values( 'capability_levels' );		
		
		$value = isset ( $this->plugin_options['access_level_required'] ) ? $this->plugin_options['access_level_required'] : '';
	
		$args = array (
			'field_label'	 	=> '',
			'option_array'    => $option_array,
			'input_class' 	   => '',
			'field_slug_css'	=> '',
			'onchange' 			=> '',
			'field_slug'		=> 'wp_issues_crm_plugin_options_array[access_level_required]',
			'value'				=> $value ,		
		);		
		echo WIC_Control_Select::create_control( $args );
	}

	// setting field call back	
	public function access_level_required_downloads_callback() {
		global $wic_db_dictionary; 
		$option_array = $wic_db_dictionary->lookup_option_values( 'capability_levels' );		
		
		$value = isset ( $this->plugin_options['access_level_required_downloads'] ) ? $this->plugin_options['access_level_required_downloads'] : '';
	
		$args = array (
			'field_label'	 	=> '',
			'option_array'    => $option_array,
			'input_class' 	   => '',
			'field_slug_css'	=> '',
			'onchange' 			=> '',
			'field_slug'		=> 'wp_issues_crm_plugin_options_array[access_level_required_downloads]',
			'value'				=> $value ,		
		);		
		echo WIC_Control_Select::create_control( $args );
	}	


	/*
	*
	* Privacy Callbacks
	*
	*/
	// section legend call back
	public function privacy_settings_legend() {
		echo '<p>' . __('The "Issues" created within WP Issues CRM are just Wordpress posts that are automatically created as private. 
		Public posts cannot be created, nor their content altered, in WP_Issues_CRM. (Public posts
		can, however, be searched for as issues and viewed through WP_Issues_CRM.  
		Additionally, one can change the title and categories of pubic posts through WP Issues CRM.)', 'wp-issues-crm' ) . '</p>' .
		'<p>' . __( 'From time to time, you may prefer to use the main Wordpress post editor, which has more features, to create or edit private issues.  
		To minimize risk of accidentally publicizing private issues through the Wordpress post editor, check the box below to 
		make "private" the default setting for all Wordpress posts.  Either way, you an always override the default visibility 
		setting in the "Publish" metabox in the Wordpress post editor.', 'wp-issues-crm' ) . '</p>' .
		'<p>' . __('Private issues and posts are not visible on the front end of your website except 
		to administrators and possibly the post authors.  So, there is no risk of disclosing private issues/posts,
		but if they are cluttering the administrator view of the front end, you can exclude them from front end queries using the setting here.', 'wp-issues-crm' ) . '</p>';
	}

	// setting field call back	
	public function all_posts_private_callback() {
		printf( '<input type="checkbox" id="all_posts_private" name="wp_issues_crm_plugin_options_array[all_posts_private]" value="%s" %s />',
            1, checked( '1', isset ( $this->plugin_options['all_posts_private'] ), false ) );
	}

	// setting field call back	
	public function hide_private_posts_callback() {
		printf( '<input type="checkbox" id="hide_private_posts" name="wp_issues_crm_plugin_options_array[hide_private_posts]" value="%s" %s />',
            1, checked( '1', isset( $this->plugin_options['hide_private_posts'] ), false ) );
	}	
	
	
	/*
	*
	* Preference Setting Callbacks
	*
	*/
	// section legend call back
	public function preference_settings_legend() {
		echo '<p>' . __( 'By default, when users add new activities for a constituent, the drop down for "Activity Issue?" includes only those set as 
		"Open for WP Issues CRM" in the Activity Tracking box on the Issue form.', 'wp-issues-crm' ) .  '</p>' . 
		'<p>' . __( 'If the setting below is checked, then users can choose preferences to see additional issues in the drop down: (a) the issue that 
		they have most recently edited; and/or (b) the most recent or most frequent issues that they have added to activities.  These additional issues
		will appear whether or not they are affirmatively "Open for WP Issues CRM", but will not appear if they are "Closed for WP Issues CRM".', 'wp-issues-crm' ) . '</p>';
	}

	// setting field call back	
	public function allow_issue_dropdown_preferences_callback() {
		printf( '<input type="checkbox" id="allow_issue_dropdown_preferences" name="wp_issues_crm_plugin_options_array[allow_issue_dropdown_preferences]" value="%s" %s />',
            1, checked( '1', isset ( $this->plugin_options['allow_issue_dropdown_preferences'] ), false ) );
	}

	/*
	*
	* Postal Address Interface Callbacks
	*
	*/
	// section legend call back
	public function postal_address_interface_legend() {
		echo '<div id="usps"><p>' . __( 'WP Issues CRM includes an interface to the ', 'wp-issues-crm' ) . '<a href="https://www.usps.com/business/web-tools-apis/address-information.htm">United States Postal Service Address Information API.</a>' .  
		__( ' This service will standardize and add zip codes to addresses entered for constituents.', 'wp-issues-crm' ) . '</p>  <p>' . __(' To use it, you need to get a User Name from the USPS:', 'wp-issues-crm' ) . '</p>' .
		'<ol><li>' . __('Register for USPS Web Tools by filling out', 'wp-issues-crm' ) . ' <a href="https://registration.shippingapis.com/">' . __( 'an online form.', 'wp-issues-crm' ) . '</a></li>' .
			'<li>' . __( 'After completing this form, you will receive an email from the USPS.  Forward that email back to ', 'wp-issues-crm' ) . '
			<a href="mailto:uspstechnicalsupport@mailps.custhelp.com">uspstechnicalsupport@mailps.custhelp.com</a> ' . __( 'with the subject line "Web Tools API Access"
			and content simply asking for access.', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'The USPS will reply seeking confirmation essentially that the access is not for bulk processing and will promptly grant you access.', 'wp-issues-crm' ) . '</li>' .
			'<li>' . __( 'Once they have sent an email granting access to the API, enter Username that they give you below and enable the Interface.  Note that you do not need to
			enter the password that they give you.', 'wp-issues-crm' ) . '</li>' .
		'</ol></div>';
				
	}

	// setting field call back	
	public function use_postal_address_interface_callback() {
		printf( '<input type="checkbox" id="use_postal_address_interface" name="wp_issues_crm_plugin_options_array[use_postal_address_interface]" value="%s" %s />',
            1, checked( '1', isset ( $this->plugin_options['use_postal_address_interface'] ), false ) );
	}

	// setting field call back
	public function user_name_for_postal_address_interface_callback() {
		printf( '<input type="text" id="user_name_for_postal_address_interface" name="wp_issues_crm_plugin_options_array[user_name_for_postal_address_interface]"
				value ="%s" />', $this->plugin_options['user_name_for_postal_address_interface'] );
	
	}



	/*
	*
	* Uninstal Legend
	*
	*/
	// section legend call back
	public function uninstall_legend() {
		echo '<div id="uninstall"><p>' . __( 'WP Issues CRM does a partial uninstall of its own data if you "delete" it through the <a href="/wp-admin/plugins.php">plugins menu</a>.  It removes its entries
			in the Wordpress options table -- which include the plugin options, database version, dictionary version and cached search histories. 
			It also removes entries in the Wordpress user meta table for individual preference settings for the plugin.', 'wp-issues-crm') . '</p><p>' .  
			__( 'However, for safety, it does not automatically remove its core tables -- the risk of data loss in a busy office is just too great. 
			To completely deinstall WP Issues CRM, access the Wordpress database through phpmyadmin or through the mysql console and delete the following tables (usually prefixed with wp_wic_) :', 'wp-issues-crm' ) . '</p>' . 
		'<ol><li>activity</li>' .
		'<li>address</li>' .
		'<li>constituent</li>' .
		'<li>data_dictionary</li>' .
		'<li>email</li>' .
		'<li>form_field_groups</li>' .
		'<li>option_group</li>' .
		'<li>option_value</li>' .
		'<li>phone</li>' .												
		'<li>search_log</li></ol>' .		
		'<p>' . __( 'Finally, run this command to delete post_meta data created by WP Issues CRM (this will not affect issue posts themselves):', 'wp-issues-crm' ) . '</p>' .
		'<pre>DELETE FROM wp_postmeta WHERE meta_key LIKE \'wic_data_%\'</pre></div>' .
		'<p>' . __( 'Take note: These steps should all be taken AFTER the plugin is deactivated -- otherwise it will automatically restore missing tables. ', 'wp-issues-crm' ) . '</p>' ;
				
	}
	// call back for the option array (used by options.php in handling the form on return)
	public function sanitize ( $input ) {
		$new_input = array();
		if( isset( $input['menu_position_top'] ) ) {
            $new_input['menu_position_top'] = absint( $input['menu_position_top'] );
      } 
		if( isset( $input['all_posts_private'] ) ) {
            $new_input['all_posts_private'] = absint( $input['all_posts_private'] );
      } 
  		if( isset( $input['hide_private_posts'] ) ) {
            $new_input['hide_private_posts'] = absint( $input['hide_private_posts'] );
      } 
  		if( isset( $input['allow_issue_dropdown_preferences'] ) ) {
            $new_input['allow_issue_dropdown_preferences'] = absint( $input['allow_issue_dropdown_preferences'] );
      } 
		if( isset( $input['use_postal_address_interface'] ) ) {
            $new_input['use_postal_address_interface'] = absint( $input['use_postal_address_interface'] );
      } 
		if( isset( $input['user_name_for_postal_address_interface'] ) ) {
            $new_input['user_name_for_postal_address_interface'] = sanitize_text_field( $input['user_name_for_postal_address_interface'] );
      } 
  		if( isset( $input['access_level_required'] ) ) {
            $new_input['access_level_required'] = sanitize_text_field( $input['access_level_required'] );
      }
		if( isset( $input['access_level_required_downloads'] ) ) {
            $new_input['access_level_required_downloads'] = sanitize_text_field( $input['access_level_required_downloads'] );
      }        
      return ( $new_input );      
	}


	// menu page with form
	public function wp_issues_crm_settings () {
		?>
      <div class="wrap">
	     	<h2><?php _e( 'WP Issues CRM Settings', 'wp-issues-crm' ); ?></h2>
			<?php settings_errors(); ?>
         <form method="post" action="options.php">
         <?php
				submit_button( __( 'Save All Settings', 'wp-issues-crm' ) );         	
         	// set up nonce-checking for the single field we have put in this option group
				settings_fields ( 'wp_issues_crm_plugin_options') ;   
				// display fields, with names (in their callback definitions) which are elements of the single option array
				do_settings_sections( 'wp_issues_crm_settings_page' );
            submit_button( __( 'Save All Settings', 'wp-issues-crm' ) ); 
          ?>
          </form>
          
      </div>
 		<?php
	}

}