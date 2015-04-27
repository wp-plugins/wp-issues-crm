<?php
/*
*
*	uninstall.php deletes factory defaults, wp_options and preferences, but not core data in the main wp_issues_crm tables
* 	 -- the risk of data loss in larger offices is too high.
*
*	To completely uninstall wp_issues_crm, after deleting the plugin, go to phpmyadmin or the mysql console and:
*		(1) delete the tables in the wordpress database with names wp_wic_ . . .
*				- activity
*				- address
*				- constituent
*				- email
*				- phone

*		(2) delete post meta data created by wp_issues_crm by running . . .
*				DELETE FROM wp_postmeta WHERE meta_key LIKE 'wic_data_%'
*			
*
*/


//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) || !defined( 'ABSPATH' ) ) 
    exit();

global $wpdb;
// clean up wp_issues_crm options
$sql = "DELETE FROM {$wpdb->prefix}options  WHERE option_name LIKE 'wp_issues_crm%' or option_name LIKE '_wp_issues_crm%' ";
$wpdb->query ($sql);

// clean up wp_issues_crm user meta entries
$sql = "DELETE FROM {$wpdb->prefix}usermeta  WHERE meta_key = 'wic_data_user_preferences' ";
$wpdb->query ($sql);

// delete tables that include minimal constituent data
// not include dictionary because user may have set up custom fields
$easily_reinstalled_tables_array = array(
	'form_field_groups',
	'option_group',
	'option_value',
	'interface',
	'search_log',
	'upload'
);

foreach ( $easily_reinstalled_tables_array as $table ) {
	$table = $wpdb->prefix . 'WIC_' . $table;
	$sql = "DROP TABLE IF EXISTS $table";
	$wpdb->query ($sql);
} 