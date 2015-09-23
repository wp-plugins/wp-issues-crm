<?php
/**
 * Plugin Name: WP Issues CRM
 * Plugin URI: http://wp-issues-crm.com 
 * Description: Constituent Relationship Management for organizations that respond to constituents.  Organizes constituent contacts ( calls, etc. ) around Wordpress posts and categories. 
 * Version: 2.3.2
 * Author: Will Brownsberger
 * Author URI: http://willbrownsberger.com
 * Text Domain: wp-issues-crm
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html 
 *	Text Domain: wp-issues-crm
 *
 *  Copyright 2015  WILL BROWNSBERGER  (email : willbrownsberger@gmail.com)

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

/*
*
*	This file invokes WIC_Admin_Setup, the main setup class, after registering an autoloader for WP_Issues_CRM classes.
*
*	WP_Issues_CRM classes are organized under subdirectories within the plugin directory like so: 
*			<path to plugin>/php/class-category/class-identifier -- for example WIC_Entity_Issues is in /php/entity/class-wic-entity-issue.php
*
*  This module also includes keep_private_posts_off_front_end_even_for_administrators
*  (the only function created in the public name space by this plugin).
*/

// set database version global;
global $wp_issues_crm_db_version;
$wp_issues_crm_db_version = '2.3'; 


// check for database install or updates -- note that the 'plugins_loaded' hook fires before is_admin is set, so too late if put in admin_setup
include_once dirname( __FILE__ ) . '/php/db/class-wic-db-setup.php';
register_activation_hook ( __FILE__, 'WIC_DB_Setup::update_db_check' ); // check will trigger install
add_action( 'plugins_loaded', 'WIC_DB_Setup::update_db_check' ); // here because upgrade does not trigger activation hook

// set up access framework . . note that if alter this scheme will need to also add 'plugins_loaded' hook for 
// since activation_hook not automatically triggered on upgrades
register_activation_hook ( __FILE__, array ( 'WIC_Admin_Setup', 'wic_set_up_roles_and_capabilities' ) );


// if is_admin, load necessary ( and only necessary ) components in admin
$plugin_options = get_option( 'wp_issues_crm_plugin_options_array' );
if ( is_admin() ) { 
	if ( ! spl_autoload_register('wp_issues_crm_autoloader' ) ) { // omit parameters --- no need to prepend which would force PHP Version 5.3.0
		die ( __( 'Fatal Error: Unable to register wp_issues_crm_autoloader in wp-issues-crm.php', 'wp-issues-crm' ) );	
	};
	$wic_admin_setup = new WIC_Admin_Setup;
} else { 
	// if hiding all private posts, even for admin, hook for front end main queries
	if ( isset ( $plugin_options['hide_private_posts'] ) ) { 
		// optionally control display of private posts
		add_action( 'pre_get_posts', 'keep_private_posts_off_front_end_even_for_administrators' );
	}
}

// ajax version of post hider is added whether or not is_admin, but only executes in theme widgets and ajax add-ons corresponding to main queries 
if ( isset ( $plugin_options['hide_private_posts'] ) ) { 
	// special hook for compatibility with responsive tabs theme and other infinite scroll themes-- pass query args, not the query
	add_filter( 'responsive_tabs_ajax_pre_get_posts', 'keep_private_posts_off_front_end_even_for_administrators_ajax', 10, 1 );
}

// functions placed here so will be accessible on front end.
function keep_private_posts_off_front_end_even_for_administrators( $query ) { 
	if ( ! is_admin() ) { 
		// note that this does not prevent this plugin or widgets from showing private posts to which logged in user has access
   	$query->set( 'post_status', array( 'publish' ) );			
	}
}
function keep_private_posts_off_front_end_even_for_administrators_ajax( $query_args ) {  
		// note that this does not prevent this plugin or widgets from showing private posts to which logged in user has access
   	$query_args['post_status'] = 'publish';
   	return ( $query_args ); 			
}

// class autoloader is case insensitive, except that it requires WIC_ (sic) as a prefix.
function wp_issues_crm_autoloader( $class ) {
	if ( 'WIC_' == substr ($class, 0, 4 ) ) {
		$subdirectory = 'php'. DIRECTORY_SEPARATOR . strtolower( substr( $class, 4, ( strpos ( $class, '_', 4  ) - 4 )  ) ) . DIRECTORY_SEPARATOR ;
		$class = strtolower( str_replace( '_', '-', $class ) );
		$class_file = plugin_dir_path( __FILE__ ) . $subdirectory .  'class-' . str_replace ( '_', '-', $class ) . '.php';
		if ( file_exists ( $class_file ) ) {  
   		require_once $class_file;
   	} else {
	   	wic_generate_call_trace();
			die ( '<h3>' . sprintf(  __( 'Fatal configuration error -- missing file %s; failed in autoload in wp-issues-crm.php, line 43.', 'wp_issues_crm' ), $class_file ) . '</h3>' );   
	   } 
	}	
} // close class wic_admin_setup


// stack trace function for locating bad class definitions; 
function wic_generate_call_trace() { // from http://php.net/manual/en/function.debug-backtrace.php

	$e = new Exception();
	$trace = explode("\n", $e->getTraceAsString());
	// reverse array to make steps line up chronologically
	$trace = array_reverse($trace);
	array_shift($trace); // remove {main}
	array_pop($trace); // remove call to this method
	$length = count($trace);
	$result = array();
	for ($i = 0; $i < $length; $i++) {
		$result[] = ($i + 1) . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
	}
	
	echo "\t" . implode("<br/>\n\t", $result);
}



