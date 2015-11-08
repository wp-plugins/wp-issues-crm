<?php
/*
*
*	wic-manage-storage.php
*	does not correspond to a database entity, but supports transient dictionary entries
*
*/

class WIC_Entity_Manage_Storage extends WIC_Entity_Parent {
	
	public function __construct() { 

		$this->set_entity_parms( '' );
		if ( ! isset ( $_POST['wic_form_button'] ) ) {
			$this->form_manage_storage();
		} else { 
			$control_array = explode( ',', $_POST['wic_form_button'] ); 		
			$args = array (
				'id_requested'			=>	$control_array[2],
				'instance'				=> '', // unnecessary in this context, absence will not create an error but here for consistency about arguments;
			);
			// only value acceptable is purge_storage, but do want to have and nonce-check this value
			$this->{$control_array[1]}( $args );
		}
	}
	
	/*
	*
	* Request handlers
	*
	*/

	protected function set_entity_parms( $args ) { // 
		// accepts args to comply with abstract function definition, but as a parent does not process them -- no instance
		$this->entity = 'manage_storage';
	} 


	// handle a request for the form -- form always just shows defaults
	protected function form_manage_storage() {
		$this->new_form_generic ( 'WIC_Form_Manage_Storage' );	
	}

	protected function purge_storage() {
		// read form values and do purge;
		$this->populate_data_object_array_from_submitted_form();
		
		// purge staging tables if so chosen
		if ( 0 == $this->data_object_array['keep_staging']->get_value() ) {
			self::delete_staging_tables();
		}
		// purge search log if so chosen
		if ( 0 == $this->data_object_array['keep_search']->get_value() ) {
			$this->truncate_search_log();
			self::purge_individul_search_histories();
		}		
		// purge constituents if fully authorized
		if ( 0 == $this->data_object_array['keep_all']->get_value() &&
			'PURGE CONSTITUENT DATA' == trim ( $this->data_object_array['confirm']->get_value() ) 		
			) {
			$this->purge_constituent_data();
		}
		// then show form
		$this->new_form_generic ( 'WIC_Form_Manage_Storage', __( 'Previous purge completed. Results below.', 'wp-issues-crm' ) );
	}	
	
	public static function delete_staging_tables() {

		global $wpdb;
		$staging_stub = $wpdb->prefix . 'wic_staging%';
		$sql = "SHOW TABLES LIKE '$staging_stub'";
		$staging_tables = $wpdb->get_results( $sql, ARRAY_A );

		// run through list of staging tables and delete all
		if ( is_array ( $staging_tables ) ) {
			foreach ( $staging_tables as $staging_table ) {
				foreach ( $staging_table as $key => $value ) {
					$sql = "DROP TABLE IF EXISTS $value";
					$wpdb->query( $sql );			
				}
			}
		}
		
		// truncate history table
		$upload_table = $wpdb->prefix . 'wic_upload';
		$sql =  "update $upload_table set purged = 1 where purged = 0 ";
		$wpdb->query ( $sql );
		
	}	

	private function truncate_search_log () {
		global $wpdb;
		$search_log = $wpdb->prefix . 'wic_search_log';
		$sql = "TRUNCATE $search_log";
		$wpdb->query( $sql );
	
	}
	
	public static function purge_individul_search_histories() {
		global $wpdb;
		$wp_options = $wpdb->options;
		$sql = " DELETE FROM $wp_options WHERE option_name LIKE '_wp_issues_crm_individual_search_history_%' ";
		$wpdb->query($sql);
	}	
	
	private function purge_constituent_data() {
		
		global $wpdb;
		
		$constituent= $wpdb->prefix . 'wic_constituent';
		$activity	= $wpdb->prefix . 'wic_activity';
		$phone 		= $wpdb->prefix . 'wic_phone';
		$email 		= $wpdb->prefix . 'wic_email';
		$address 	= $wpdb->prefix . 'wic_address';	
	
		$having = '';

		if ( 1 == $this->data_object_array['keep_activity']->get_value() ) {
			$having .= " max( if ( a.constituent_id is not null, 1, 0 ) ) = 0 ";
		}
		if ( 1 == $this->data_object_array['keep_phone']->get_value() ) {
			$having = ( '' < $having ) ? $having . ' AND ' : $having;
			$having .= " max( if ( p.constituent_id is not null, 1, 0 ) ) = 0 ";
		}
		if ( 1 == $this->data_object_array['keep_email']->get_value() ) {
			$having = ( '' < $having ) ? $having . ' AND ' : $having;
			$having .= " max( if ( e.constituent_id is not null, 1, 0 ) ) = 0 ";
		}
		if ( 1 == $this->data_object_array['keep_address']->get_value() ) {
			$having = ( '' < $having ) ? $having . ' AND ' : $having;
			$having .= " max( if ( ad.constituent_id is not null, 1, 0 ) ) = 0 ";
		}

		$having = ( '' < $having ) ? 'HAVING ' . $having : '' ;

		$purge_temp = $wpdb->prefix . 'wic_purge_temp';	
	
		$sql = 	" CREATE TEMPORARY TABLE $purge_temp 
					SELECT c.ID FROM 
					$constituent c LEFT JOIN 
					$activity a 	on a.constituent_id = c.id LEFT JOIN
					$phone p 		on p.constituent_id = c.id LEFT JOIN
					$email e 		on e.constituent_id = c.id LEFT JOIN  
					$address ad 	on ad.constituent_id = c.id
					GROUP BY c.id
					$having
					";
		$wpdb->query ( $sql );

		$wpdb->query ( "DELETE c from $purge_temp t LEFT JOIN $constituent c on c.ID = t.ID" );
		$wpdb->query ( "DELETE a from $purge_temp t LEFT JOIN $activity a on a.constituent_id = t.ID" );
		$wpdb->query ( "DELETE p from $purge_temp t LEFT JOIN $phone p on p.constituent_id = t.ID" );
		$wpdb->query ( "DELETE e from $purge_temp t LEFT JOIN $email e on e.constituent_id = t.ID" );
		$wpdb->query ( "DELETE ad from $purge_temp t LEFT JOIN $address ad on ad.constituent_id = t.ID" );
		
		$wpdb->query ( "OPTIMIZE TABLE $constituent" );
		$wpdb->query ( "OPTIMIZE TABLE $activity" );
		$wpdb->query ( "OPTIMIZE TABLE $phone" );
		$wpdb->query ( "OPTIMIZE TABLE $email" );
		$wpdb->query ( "OPTIMIZE TABLE $address" );
	}	
	
}