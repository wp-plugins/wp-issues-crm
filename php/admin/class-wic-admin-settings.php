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
		add_action( 'admin_init', array ( $this, 'settings_setup') );
		$this->plugin_options = get_option( 'wp_issues_crm_plugin_options_array' );
	}	
	
	// define setting
	public function settings_setup() {
		
		// registering only one setting, which will be an array -- will set up nonces when called
		register_setting(
			'wp_issues_crm_plugin_options', // Option Group (have only one option)
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
            'Activity Issue Assignment Settings', // Title
            array( $this, 'preference_settings_legend' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

		// naming of the callback with array elements (in the callbacks) is what ties the option array together 		
      add_settings_field(
            'disallow_activity_issue_search', // field id
            'Disallow instant issue search', // field label
            array( $this, 'disallow_activity_issue_search_callback' ), // field call back 
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

      add_settings_field(
            'do_zip_code_format_check', // field id
            'Verify USPS zip format', // field label
            array( $this, 'do_zip_code_format_check_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'postal_address_interface' // settings section within page
       ); 

		// financial transactions settings 
      add_settings_section(
            'enable_financial_activities', // setting ID
            'Enable Financial Activities', // Title
            array( $this, 'enable_financial_activities_legend_callback' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

      add_settings_field(
            'financial_activity_types', // field id
            'Financial Activity Type Codes', // field label
            array( $this, 'financial_activity_types_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'enable_financial_activities' // settings section within page
       ); 
 
 		// financial transactions settings 
      add_settings_section(
            'freeze_older_activities', // setting ID
            'Freeze Older Activities', // Title
            array( $this, 'freeze_older_activities_legend_callback' ), // Callback
            'wp_issues_crm_settings_page' // page ID ( a group of settings sections)
        ); 

      add_settings_field(
            'freeze_older_activities', // field id
            'Activity Freeze Cutoff', // field label
            array( $this, 'freeze_older_activities_callback' ), // field call back 
            'wp_issues_crm_settings_page', // page 
            'freeze_older_activities' // settings section within page
       ); 
    
    
		// Uninstall Settings (legend only)
      add_settings_section(
            'uninstall', // setting ID
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
			'hidden'				=> 0,
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
			'hidden'				=> '',
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
		echo '<p>' . __( 'On constituent screens, when activities are added or updated, the activity type and associated issue must be identified -- 
		for example, an activity could be of type "EMail" about an issue titled "Education".', 'wp-issues-crm' ). '</p>' .
		'<p>' . __( 'By default, the Activity Issue field will instantly retrieve issues matching what the user types (for example, typing "edu" would 
		show issues with titles	including the word "education").  The user must select one of the retrieved issues.  The retrieval will prioritize issues 
		that have been set in the Activity Tracking section (on the issue screen) as "Always appear in issue drop down" and will exclude issues set 
		to "never appear".  Issues that have not been set either way will be retrieved but as lower priority.', 'wp-issues-crm') . '</p>' .  
		'<p>' . __('By checking the box below, you can disable this built-in search feature and force users to select from a defined drop down including only 
		issues set as "Always appear in issue dropdown" in the Activity Tracking section on the Issue form.  This approach controls user choices as
		might be necessary in a larger office.', 'wp-issues-crm' ) .  '</p>' . 
		'<p>' . __( 'Note that even if you allow searching for issues, users will have the option to choose the simpler non-searchable select dropdown by setting preferences.', 'wp-issues-crm' ) . '</p>';
	}

	// setting field call back	
	public function disallow_activity_issue_search_callback() {
		printf( '<input type="checkbox" id="disallow_activity_issue_search" name="wp_issues_crm_plugin_options_array[disallow_activity_issue_search]" value="%s" %s />',
            1, checked( '1', isset ( $this->plugin_options['disallow_activity_issue_search'] ), false ) );
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
		$value = isset( $this->plugin_options['user_name_for_postal_address_interface'] ) ? $this->plugin_options['user_name_for_postal_address_interface']: '';
		printf( '<input type="text" id="user_name_for_postal_address_interface" name="wp_issues_crm_plugin_options_array[user_name_for_postal_address_interface]"
				value ="%s" />', $value );
		}

	public function do_zip_code_format_check_callback() {
		printf( '<input type="checkbox" id="do_zip_code_format_check" name="wp_issues_crm_plugin_options_array[do_zip_code_format_check]" value="%s" %s />',
            1, checked( '1', isset ( $this->plugin_options['do_zip_code_format_check'] ), false ) );
	}
	/*
	*
	* Financial Activity Types Callback
	*
	*/
	// section legend call back
	public function enable_financial_activities_legend_callback() {
		echo 
		'<p>' . __( 'WP Issues CRM can be used to track financial transactions. Simply enter below the Activity Type codes <em>separated by commas</em> for which amounts should be recorded.' , 'wp-issues-crm' ) . '</p>' . 
		'<p>' . __( 'For example, if you defined <a href="' . site_url() . '/wp-admin/admin.php?page=wp-issues-crm-options">Activity Type Options</a> <code>Check Contribution</code> coded as <code>CH</code> and <code>Online Contribution</code> coded as <code>OC</code>, you would enter them below like so:' , 'wp-issues-crm' ) . ' <code>CH,OC</code>' . '</p>' . 
		'<p>' . __( 'Activities of these types will then be stored and displayed with an amount field formatted with two decimal points.' , 'wp-issues-crm' ) .  '</p>' .
		'<p>' . __( 'Tip: The matching of activity type codes is case sensitive -- "CH" as an Activity Type code will <em>not</em> match "ch" as a financial activity setting.' , 'wp-issues-crm' ) .  '</p>' ;
	}
	
	// setting field call back
	public function financial_activity_types_callback() { 
		$value = isset ( $this->plugin_options['financial_activity_types'] ) ? $this->plugin_options['financial_activity_types'] : '';
		printf( '<input type="text" id="financial_activity_types" name="wp_issues_crm_plugin_options_array[financial_activity_types]"
				value ="%s" />', $value );
	}

	/*
	*
	* Freeze Older Activities Callback
	*
	*/
	// section legend call back
	public function freeze_older_activities_legend_callback() {
		
		// get and parse date value if available
		$wic_option_array = get_option('wp_issues_crm_plugin_options_array'); 
		if ( isset ( $wic_option_array['freeze_older_activities'] ) ) {
			$date_value = $wic_option_array['freeze_older_activities'] > '' ? 
				WIC_Control_Date::sanitize_date( $wic_option_array['freeze_older_activities'] ) :
				'';
		} else {
			$date_value = '';		
		}
		// if date_value is unset, blank or unparseable, so report
		$show_date  = ( '' < $date_value ) ? $date_value : __( 'Blank -- not set or not parseable', 'wp-issues-crm' );
		
		echo 
		'<p>' . __( 'WP Issues CRM allows you to freeze older activities, leaving them viewable, but not updateable, online. You might
					especially wish to do this if you have closed a financial records period, but also just to limit the possibility
					of data entry errors.' , 'wp-issues-crm' ) . '</p>' . 
		'<p>' . __( 'Activities dated earlier than the cutoff date below cannot be updated.   Also, you will not be able to set dates
					 earlier than the cutoff date when adding new activities.  Note that you can still mark a constituent as deleted
					 even if they have activities dated before the cutoff.  Also, you can always change the cutoff date, or eliminate it, 
					 if for some reason you need to go back to update older activities.', 'wp-issues-crm' ) . '</p>' .
		'<p>' . __( 'You can enter the cutoff date in almost any English language format, including variable formats like <code>3 days ago</code>.
					 This example would freeze activities more than 3 days old on a rolling basis. Enter a phrase and save it to test it.' , 'wp-issues-crm' ) .  '</p>' .
		'<p><strong><em>' . __( 'As of today, the last saved cutoff value evaluates to: ' , 'wp-issues-crm' ) . '</em></strong><code>' . $show_date . '.</code></p>' ;
	}
	
	// setting field call back
	public function freeze_older_activities_callback() { 
		$value = isset ( $this->plugin_options['freeze_older_activities'] ) ? $this->plugin_options['freeze_older_activities'] : '';
		printf( '<input type="text" id="freeze_older_activities" name="wp_issues_crm_plugin_options_array[freeze_older_activities]"
				value ="%s" />', $value );
	}

	/*
	*
	* Uninstall Legend
	*
	*/
	// section legend call back
	public function uninstall_legend() {
		echo '<div id="uninstall">' .
			'<p>' . __( 'If you simply wish to refresh original options settings, you can safely deactivate and delete WP Issues CRM and then reinstall it on
			the <a href="' . site_url() . '/wp-admin/plugins.php">plugins menu</a>.  WP Issues CRM will come back up	with all of your data.', 'wp-issues-crm' ) . '</p>' 
			. '<p>' . __( 'WP Issues CRM does a partial uninstall of its own data if you "delete" it through the <a href="' . site_url() . '/wp-admin/plugins.php">plugins menu</a>.  It removes its entries
			in the Wordpress options table -- which include the plugin options, database version and cached search histories. 
			It also removes entries in the Wordpress user meta table for individual preference settings for the plugin.
			Finally, it removes its control and audit trail tables, with the exception of the dictionary (which may include user configured fields).', 'wp-issues-crm') . '</p><p>' .  
			__( 'However, for safety, it does not automatically remove the core user built tables -- the risk of data loss in a busy office is just too great. 
			To completely deinstall WP Issues CRM, access the Wordpress database through phpmyadmin or through the mysql console and delete the following tables (usually prefixed with "wp_wic_" ) :', 'wp-issues-crm' ) . '</p>' . 
		'<ol>' . 
			'<li>activity</li>' .
			'<li>address</li>' .
			'<li>constituent</li>' .
			'<li>data_dictionary</li>' .
			'<li>email</li>' .
			'<li>phone</li>' . 
		'</ol>' .		
		'<p>' . __( 'Finally, run this command to delete post_meta data created by WP Issues CRM (this will not affect issue posts themselves):', 'wp-issues-crm' ) . '</p>' .
		'<pre>DELETE FROM wp_postmeta WHERE meta_key LIKE \'wic_data_%\'</pre></div>' .
		'<p>' . __( 'Take note: These steps should all be taken AFTER the plugin is deactivated -- otherwise it will automatically restore missing tables. ', 'wp-issues-crm' ) . '</p>';
				
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
  		if( isset( $input['disallow_activity_issue_search'] ) ) {
            $new_input['disallow_activity_issue_search'] = absint( $input['disallow_activity_issue_search'] );
      } 
		if( isset( $input['use_postal_address_interface'] ) ) {
            $new_input['use_postal_address_interface'] = absint( $input['use_postal_address_interface'] );
      } 
		if( isset( $input['user_name_for_postal_address_interface'] ) ) {
            $new_input['user_name_for_postal_address_interface'] = sanitize_text_field( $input['user_name_for_postal_address_interface'] );
      } 
    	if( isset( $input['do_zip_code_format_check'] ) ) {
            $new_input['do_zip_code_format_check'] = absint( $input['do_zip_code_format_check'] );
      } 
  		if( isset( $input['access_level_required'] ) ) {
            $new_input['access_level_required'] = sanitize_text_field( $input['access_level_required'] );
      }
		if( isset( $input['access_level_required_downloads'] ) ) {
            $new_input['access_level_required_downloads'] = sanitize_text_field( $input['access_level_required_downloads'] );
      }    
      if( isset( $input['financial_activity_types'] ) ) {
      	   $type_array = explode ( ',', $input['financial_activity_types'] );
      	   $clean_type_array = array();
      	   foreach ( $type_array as $type ) {
      	   	$clean_type = sanitize_text_field( $type );
					if ( $clean_type > '' ) { 
						$clean_type_array[] = $clean_type;
					}     	   
      	   } 
      	   $new_input['financial_activity_types'] = implode (',', $clean_type_array );
      } 
      
      if( isset( $input['freeze_older_activities'] ) ) {
      		// accept only values that can be processed to a date by php, but store the value, not the date
      		$date_value = WIC_Control_Date::sanitize_date( $input['freeze_older_activities'] );
            $new_input['freeze_older_activities'] = $date_value > '' ? sanitize_text_field( $input['freeze_older_activities'] ) : '';
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