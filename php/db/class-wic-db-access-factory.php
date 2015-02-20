<?php
/*
*
* class-wic-db-access-factory.php
*
* supports multiple approaches to data access to be further implemented in extensions of WP_DB_Access
*
* all extensions support an access model that looks to the entity seeking access like the Wordpress $wpdb object
* this allows compatibility across forms accessing wordpress posts and forms accessing wp_issues_crm custom tables
*
* 
*/

class WIC_DB_Access_Factory {

	static private $entity_model_array = array (
		'activity' 		=> 'WIC_DB_Access_WIC',
		'address'		=> 'WIC_DB_Access_WIC',
		'comment' 		=> 'WIC_DB_Access_WIC',
		'constituent' 	=> 'WIC_DB_Access_WIC',	
		'data_dictionary' => 'WIC_DB_Access_Dictionary',
		'email'			=> 'WIC_DB_Access_WIC',
		'email'			=> 'WIC_DB_Access_WIC',
		'issue' 			=> 'WIC_DB_Access_WP',
		'option_group'	=> 'WIC_DB_Access_WIC',
		'option_value'	=> 'WIC_DB_Access_WIC',
		'phone'			=> 'WIC_DB_Access_WIC',
		'search_log'	=> 'WIC_DB_Access_WIC',	
		'trend' 			=> 'WIC_DB_Access_Trend',
		'user'			=> 'WIC_DB_Access_WP_User'
	);

	public static function make_a_db_access_object ( $entity ) {
		$right_db_class = self::$entity_model_array[$entity];
		$new_db_access_object = new $right_db_class ( $entity );
		return ( $new_db_access_object );	
	}
	
}

