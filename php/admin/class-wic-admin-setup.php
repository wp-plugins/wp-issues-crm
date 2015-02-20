<?php
/**
*
* class-wic-admin-setup.php
*
*
*
*
* main set up class -- constructor adds basic actions, instantiates dictionary, and instantiates main navigation
*
* includes functions involved in setup/configuration
*
*/
class WIC_Admin_Setup {

	// does all registration and action adds ( except activation and settings )	
	public function __construct() {  
	 	
	 	// load dictionary and navigation
	 	// use the _admin_menu hook which fires just before admin_menu -- 
	 	// which is where the navigation needs to be fired 
		add_action ( '_admin_menu', array( $this, 'basic_setup' ) ); 
		
		// add metabox -- take care: this fires on all save/updates of posts; 
		$wic_issue_open_metabox = new WIC_Entity_Issue_Open_Metabox;
		
		//	enqueue styles and scripts	
		add_action( 'admin_enqueue_scripts', array( $this, 'add_wic_scripts' ) );

		// set up download hook -- admin init is early enough to intercept button for download
		add_action( 'admin_init', array( $this, 'do_download' ) );	

		// optionally set default display of posts to private 		
		$plugin_options = get_option( 'wp_issues_crm_plugin_options_array' );		
		if ( isset( $plugin_options['all_posts_private'] ) ) {
	 		add_action( 'post_submitbox_misc_actions' , array ( $this, 'default_post_visibility' ) );
	 	}

	}

	// load dictionary and navigation
	public function basic_setup() { 
	 	// set up dictionary
	 	global $wic_db_dictionary;
	 	$wic_db_dictionary = new WIC_DB_Dictionary; // needed for metabox, so load always on admin (two quick db calls)
	 	
	 	// load navigation (which includes menu set up)
		$wp_issues_crm_admin = new WIC_Admin_Navigation;  // small pass through, so minimal load on non-WIC pages	
	
	}


	// load scripts and styles only for this plugin's pages
	public function add_wic_scripts ( $hook ) {
	
		if ( -1 < strpos( $hook, 'wp-issues-crm' ) ) { 
	
			wp_register_script(
				'wic-utilities',
				plugins_url( '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'wic-utilities.js' , __FILE__ ) 
			);
			wp_enqueue_script('wic-utilities');

			wp_register_script(
				'wic-changed-page',
				plugins_url( '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'wic-changed-page.js' , __FILE__ ) 
			);
			wp_enqueue_script('wic-changed-page');


			
			wp_register_style(
				'wp-issues-crm-styles',
				plugins_url( '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'wp-issues-crm.css' , __FILE__ )
				);
			wp_enqueue_style('wp-issues-crm-styles');
			
		}					
			
	}

	// add action to intercept press of download button before any headers sent 
	public function do_download () { 
		if ( isset( $_POST['wic-post-export-button'] ) ) { 
			WIC_List_Constituent_Export::do_constituent_download( $_POST['wic-post-export-button'] );	
		} elseif ( isset( $_POST['wic-category-export-button'] ) ) { 
			WIC_List_Constituent_Export::do_constituent_category_download( $_POST['wic-category-export-button'] );	
		}		
 	}

	public function wic_set_up_roles_and_capabilities() {

		// give administrators the manage constituents capacity	
	   $role = get_role( 'administrator' );
	   $role->add_cap( 'manage_wic_constituents' ); 
	   // deny/remove it from editors 
	   // ( cleaning up legacy entries in database; may grant editors access through Settings panel )	
	   $role = get_role( 'editor' );
	   $role->remove_cap( 'manage_wic_constituents' );	

		// define a role that has limited author privileges and access to the plugin
		
		// first remove the role in case the capabilities array below has been revised  	
		remove_role('wic_constituent_manager');
	
		// now add the role
		$result = add_role(
	   	'wic_constituent_manager',
	    	__( 'Constituent Manager', 'wp-issues-crm' ),
		   array(
		   		// capacities to add
				  'manage_wic_constituents' 	=> true, // grants access to plugin and all constituent functions
		        'read_private_posts' 			=> true, // necessary for viewing (and so editing) individual private issues through wic interface
		        'upload_files'					=> true,
		        // capacities explicitly (and perhaps unnecessarily) denied
		        'read'								=> false, // denies access to dashboard
	           'edit_posts'  					=> false, // limits wp backend access -- can still edit private issues through the wic interface
		        'edit_others_posts'  			=> false, // limits wp backend access -- can still edit private issues through the wic interface 
		        'delete_posts'					=> false,
	           'delete_published_posts' 	=> false,
		        'edit_published_posts' 		=> false,
		        'publish_posts'					=> false,
		        'read_private_pages' 			=> false,
		        'edit_private_posts' 			=> false,
		        'edit_private_pages' 			=> false, 
		    )
		);
		
	}
	  
	/**
	 * https://wordpress.org/support/topic/how-to-set-new-post-visibility-to-private-by-default?replies=14#post-2074408 
	 *
	 * It reverses the role of public and private in the logic of what visibility is assigned in the misc publishing metabox.
	 * Compare /wp-admin/includes/meta-boxes.php, lines 121-133.   
	 * It then includes jquery script to write the correct values in after the fact.
	 * Since core functions are doing the output as they go, there is no good pre or post hook, so client side jquery is only surgical solution 
	 * 
	*/
	 function default_post_visibility(){
		global $post;
		
		if ( 'publish' == $post->post_status ) {
			$visibility = 'public';
			$visibility_trans = __('Public');
		} elseif ( !empty( $post->post_password ) ) {
			$visibility = 'password';
			$visibility_trans = __('Password protected');
		} elseif ( $post->post_type == 'post' && is_sticky( $post->ID ) ) {
			$visibility = 'public';
			$visibility_trans = __('Public, Sticky');
		} else {
			$post->post_password = '';
			$visibility = 'private';
			$visibility_trans = __('Private');
		} ?>
		
	 	<script type="text/javascript">
	 		(function($){
	 			try {
	 				$('#post-visibility-display').text('<?php echo $visibility_trans; ?>');
	 				$('#hidden-post-visibility').val('<?php echo $visibility; ?>');
	 				$('#visibility-radio-<?php echo $visibility; ?>').attr('checked', true);
	 			} catch(err){}
	 		}) (jQuery);
	 	</script>
	 	<?php
	 }
// close class WIC_Admin_Setup	 
}