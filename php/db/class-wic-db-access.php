<?php
/*
*
* class-wic-db-access.php
*		intended as wraparound for direct db access objects (implemented as extensions to this.) 
*		maintains search log (which includes saves) and does all search log data access directly
*
*/



abstract class WIC_DB_Access {
	
	// these properties contain  the results of the db access
	public $entity;		// top level entity searched for or acted on ( e.g., constituents or issues )
	public $sql; 			// for search, the query executed;
	public $result; 		// entity_object_array -- as saved, update or found( possibly multiple ) (each row as object with field_names properties)
	public $outcome; 		// true or false if error
	public $explanation; // reason for outcome
	public $found_count; // integer save/update/search # records found or acted on
	public $insert_id;	// ID of newly saved entity
	public $search_id;  // ID of search completed -- search log
	public $made_changes; // whether there were any changed values and an update was actually applied ($outcome is true, even if this is false)
								 // note: maintained by the parent entity save_update but  only actually used here as maintained by the multivalue child class
	public $last_updated_time; // for pass back to screen
	public $last_updated_by; // for pass back to screen

	public function __construct ( $entity ) { 
		$this->entity = $entity;
	}		

	/**********************************************************************************************
	*
	*	SEARCH LOG FUNCTIONS 
	*
	*
	*	Notes on search log usage -- intended scope is only: Form Searches and ID Searches off of Lists
	*		Limited to constituents and issues. 
	*		The search_log method is private, so invoked only in this class, and, in fact, only by the primary search method.
	*
	*	NOTE: Also log saves as if id searches so can go back to constituent or issue from search	
	*
	*	Searches are invoked in the following basic ways:
	*		From a form (Entity_Parent, Always Logged)
	*		From a single ID (Entity_Parent, Not Logged except requested as in instances below)
	*			NOT Logged if coming from search form handling (redundant)
	*			Logged if coming from id_search method in Entity_Constituent (from List Constituent ) 
	*			Also logged if coming through ID search method in Entity_Issue, which is used in all of the following places:
	*				List Trend (logged)
	*				List Issue (logged)
	*				Metabox (logged)
	*				Entity Comment ( logged )
	*				Activity Link to Issue Button (logged)
	*		From a search array retrieved from the log (Entity_Parent (redo search and latest) and Export, Never Logged)
	*
	*	Additional specialized searches include:
	*		In dup checking (Entity_Parent, Never Logged)
	*		From specialized search arrays constructed as in Dashboard (Never Logged)
	*		For specialized searches in multivalue controls, options and field listings (Never Logged)	
	*	
	***********************************************************************************************/
	public function search_log ( $meta_query_array, $search_parameters ) {
		// would like this to be private but make it public 
		// since want to log saves as searches and 
		// have to do that from top entity form request
		$entity = $this->entity;
		if ( isset ( $search_parameters['log_search'] ) ) {	
			if ( $search_parameters['log_search'] ) { 
				global $wpdb;
				$user_id = get_current_user_id();
	
				$search = serialize( $meta_query_array );
				$parameters = serialize ( $search_parameters );
				
				$sql = $wpdb->prepare(
					"
					INSERT INTO wp_wic_search_log
					( user_id, search_time, entity, serialized_search_array,  serialized_search_parameters, result_count  )
					VALUES ( $user_id, NOW(), %s, %s, %s, %s)
					", 
					array ( $entity, $search, $parameters, $this->found_count ) ); 
	
				$save_result = $wpdb->query( $sql );
				
				if ( 1 == $save_result ) {
					$this->search_id = $wpdb->insert_id;
					 WIC_DB_Search_History::update_search_history( $this->search_id );	
				} else {
					WIC_Function_Utilities::wic_error ( 'Unknown database error in search_log.', __FILE__, __LINE__, __METHOD__, true ); 		
				}
			} elseif ( isset ( $search_parameters['old_search_id'] ) ) {
				$this->search_id = $search_parameters['old_search_id'];
				/* this branch allows a search that is just a reconstruction of a search log search to retain its
				*  original search ID -- list_parent needs search ID to show download and redo buttons.
				*  See WIC_Entity_Parent::redo_search_from_meta_query.  Want to bring user back to exact previous search position.
				*  Only place this parameter is set is in that redo_search function and any redone search goes through there.
				*  If only redisplay search form, don't want to maintain the original identity and don't actually redo search
				*  Just show form
				*/
			}
		} else {
			wic_generate_call_trace();
			WIC_Function_Utilities::wic_error ( 'Unset value -- $search_parameters[\'log_search\']', __FILE__, __LINE__, __METHOD__, false );
		}
	}
	
	/*
	*
	* retrieve the latest search for an individual instance of this entity from the search log 
	*
	*/
	public function search_log_last ( $user_id ) {
		
		global $wpdb;		
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		$entity = $this->entity;		
		
		$sql = 			
			"
			SELECT ID, serialized_search_array, serialized_search_parameters, search_time
			FROM $search_log_table
			WHERE user_id = $user_id
				AND entity = '$entity'
				AND result_count = 1
			ORDER	BY search_time DESC
			LIMIT 0, 1
			";

		// get ID of latest entity instance searched for by user
		$latest_search = $wpdb->get_results ( $sql );
		$latest_searched_for = self::extract_id_search_from_array ( $latest_search[0]->serialized_search_array );

		// if array was not an id search, $latest_searched_for will be empty
		// have to get ID by actually executing the search
		if ( '' == $latest_searched_for ) { 
			// set up search array same as in WIC_Entity_Search_Log::ID_retrieval for call that 
			// ends up in same place -- WIC_Entity_Parent::redo_search_from_meta_query (although will do nothing with search_id)
			$search = array();
			$search['search_id'] = $latest_search[0]->ID; 
			$search['entity'] = $entity;
			$search['unserialized_search_array'] = unserialize ( $latest_search[0]->serialized_search_array );
			$search['unserialized_search_parameters'] = unserialize ( $latest_search[0]->serialized_search_parameters );
			$class_name = 'WIC_Entity_' . $this->entity; 
			$searching_entity = new $class_name( 'redo_search_from_meta_query_no_form', $search ); // initialize the data_object_array with the latest
			$latest_searched_for = $searching_entity->get_the_current_ID();		
		}
	
		return ( array (
			'latest_searched' => $latest_searched_for,
			'latest_searched_time'  =>$latest_search[0]->search_time,
			'latest_search_ID' => $latest_search[0]->ID,  
			)		
		);
	
	} 	 

	// find an ID search in a serialized array and return the id searched for
	private static function extract_id_search_from_array( $serialized_search_array ) {
		$latest_search_array = unserialize ( $serialized_search_array );
		$latest_searched_for = '';
		foreach ( $latest_search_array as $search_clause ) {
			if ( 'ID' == $search_clause['key']  ) {
				$latest_searched_for = $search_clause['value'];	
			}		
		} 
		return ( $latest_searched_for );
	}
	
	/*
	*
	* retrieve last NON individual retrieval ( i.e., not containing 'ID' as search key)
	* note that not looking at zero results -- these bounce to the user anyway
	*
	*
	public function search_log_last_general ( $user_id ) { 
		
		global $wpdb;		
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		$entity = $this->entity;
		
		$sql = 			
			"
			SELECT ID
			FROM $search_log_table
			WHERE user_id = $user_id
				AND entity = '$entity'
				AND result_count > 1
			ORDER	BY search_time DESC
			LIMIT 0, 1
			";
		
		$latest_search = $wpdb->get_results ( $sql );

		return ( $latest_search[0]->ID );

	} 	 	*/
	
	/*
	*
	* retrieve last logged event
	*
	*/
	public static function search_log_last_entry ( $user_id ) { 
		
		global $wpdb;		
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		
		$sql = 			
			"
			SELECT ID, search_time
			FROM $search_log_table
			WHERE user_id = $user_id
			ORDER	BY search_time DESC
			LIMIT 0, 1
			";
		
		$latest_search = $wpdb->get_results ( $sql );

		$return =  isset ( $latest_search[0]->ID ) ?  
				array ( 
					'search_log_last_entry' => $latest_search[0]->ID, 
					'search_log_last_entry_time' => $latest_search[0]->search_time 
				) 
				: 
				false;

		return ( $return );

	} 	 	
	
	
		
	
	/*
	*
	* pull the specified search off the search log by search id 
	* (for constituent export, does not pass search parameters, only search terms)
	*
	*/
	 
	public static function get_search_from_search_log ( $id ) {
		
		global $wpdb;
		
		$search_object = $wpdb->get_row ( "SELECT * from wp_wic_search_log where id = $id " );
		
		$return = array (
			'user_id' => $search_object->user_id,
			'entity' =>  $search_object->entity, 
			'meta_query_array' =>  unserialize ( $search_object->serialized_search_array )
		);

		return ( $return );		
	}

	/*
	*
	* mark a search as having been downloaded
	*
	*/
	public static function mark_search_as_downloaded ( $id ) {
		global $wpdb;
		$sql = $wpdb->prepare (
			"
			UPDATE wp_wic_search_log
			SET download_time = %s
			WHERE id = %s
			",
			array( current_time( 'Y-m-d-H-i-s' ), $id ) );
		
		$update_result = $wpdb->query( $sql );
			
		if ( 1 != $update_result ) {
			WIC_Function_Utilities::wic_error ( 'Unknown database error in posting download event to search log.', __FILE__, __LINE__, __METHOD__, true );
		}	
	}		


	/****************************************************************************************
	*
	*	log search request and pass through to database specific object search functions
	*
	******************************************************************************************/
	public function search ( $meta_query_array, $search_parameters ) { // receives pre-assembled meta_query_array
		$this->db_search( $meta_query_array, $search_parameters );
		$this->search_log( $meta_query_array, $search_parameters );
		return;
	}

	public function delete_by_id ( $id ) {
		$this->db_delete_by_id ( $id );	
	}


	/**********************************************************************************************************
	*
	*	Main save/update process for WP Issues CRM -- runs across multiple layers, but is controlled by the process below   
	*	(1) Top level entity contains an array of controls -- see wic-entity-parent
	*			+ Basic controls each contain a value which is information about the top level entity like name (not an object property technically, but logically so)
	*			+ Multivalue controls contain arrays of entities that have a data relationship to the top level entity, like addresses for a constituent 
	*  (2) Each multivalue entity, e.g., each address is an entity with the same logical structure as the parent entity -- as a class, their entity is
	*		an extension of the parent entity.
	*  (3) So when update is submitted for the parent entity . . .
	*		 (1) The parent entity (e.g. constituent) creates a new instance of this class (actually the _WIC extension of this class ) 
	*				and passes it a pointer to its array of controls 
	*      (2) Second this object->save_update asks each of the basic controls to produce a set clause 
	*		 (3) The set clauses are applied to the database by this object's WIC extension WIC_DB_Access_WIC 
	*				(straightforward insert update for a single entity)
	*		 (4) This object->save_update then asks each of the multivalue controls in turn to do an update
	*		 (5) Each multivalue control object in turn asks each of the row entities in its row array to do a save_update 
	*		 (6) Each multivalue entities (e.g. address) issues a save update request which comes back through an new instance of this object 
	*				and does only steps (1) through (3) for that object (assuming no multivalue controls within multivalue controls, not attempted so far in this implementation. 
	*
	*
	*	note that the assembly of the save update array occurs in this database access class because
	*  updates are handled for particular entities (and this object serves a particular entity)
	*	by contrast, the search array assembly is handled at the entity level because it needs to be able to report up to
	*  a multivalue control and contribute to a join across multiple entities in addition to the primary object entity  
	*
	**********************************************************************************************************/	
	public function save_update ( &$doa ) { 
		$save_update_array = $this->assemble_save_update_array( $doa );
		// each non-multivalue control reports an update clause into the assembly
		// next do a straight update or insert for the entity in question 
		if ( count ( $save_update_array ) > 0 ) {
			if ( $doa['ID']->get_value() > 0 ) {
				$this->db_update ( $save_update_array );		
			} else {
				$this->db_save ( $save_update_array );
			}
		}
		// at this stage, the main entity update has occurred and return values for the top level entity have been set (preliminary) 
		// if main update OK, do multivalue ( child ) updates
		if ( $this->outcome ) {
			// set the parent entity ID in case of an insert 
			$id = ( $doa['ID']->get_value() > 0 ) ? $doa['ID']->get_value() : $this->insert_id;
			$errors = '';
			// now ask each multivalue control to do its save_updates 			
			$multivalue_fields_have_changes = false;			
			foreach ( $doa as $field => $control ) {
				if ( $control->is_multivalue() ) {
					// id for the parent (e.g., constituent) becomes constituent_id for the rows in the multivalue control
					$errors .= $control->do_save_updates( $id );
					// in multi value control, do_save_updates will ask each row entity within it to do its own do_save_updates
					// do_save_updates for each row will come back through this same save_update method 
					// report up that changes were made (control has aggregated whether changes made in update of any row)
					$multivalue_fields_have_changes = $control->were_changes_made() ? true : $multivalue_fields_have_changes;
				}			
			}
			if ( $errors > '' ) {
				$this->outcome = false;
				$this->explanation .= $errors;
			}
			// am in the multivalue control branch and have done updates -- update the top db object's made_changes property 
			// and time stamp the calling entity's record with a last updated mark 	
			if ( false === $this->made_changes && true === $multivalue_fields_have_changes ) {
				$this->made_changes = true;
				// passing a single ID save update array will just do a time stamp
				$second_save_update_array = array ( 
					array ( 	'key' 	=> 'ID',
								'value'	=> $id,
								'wp_query_parameter' => '',
								'soundex_enabled' => false,
							),
					);
				// when doing a timestamp, WIC db_update will just do the update and not set other object properties
				$this->db_update ( $second_save_update_array );
				// note that this call may generate an error if there were a multivalue field created for an issue; 
				// non-fatal:  all other updates are already done; no flags set; no tracking of last_updated_by in WP anyway
			}						
				
		}

		return;	
	}


	/*
	*
	*	Assemble save_update array from controls.  
	*		-- each control creates the appropriate save update clause which is added to array
	*		-- multivalue fields are excluded at this stage
	*		-- when do_save_update is called, it is acting only on individual entities, no multivalue
	*
	*/
	protected function assemble_save_update_array ( &$doa ) {
		$save_update_array = array();
		foreach ( $doa as $field => $control ) {
			if ( ! $control->is_multivalue() ) {
				$update_clause = $control->create_update_clause();
				if ( '' < $update_clause ) {
					$save_update_array[] = $update_clause;
				}
			}
		}	
		return ( $save_update_array );
	}


	/*
	*
	* pass through for lister functions
	*
	*/

	public function list_by_id ( $id_string,  $sort_direction = 'ASC' ) {
		$this->db_list_by_id ( $id_string, $sort_direction ); 
	}

	public static function get_mysql_time() {
		global $wpdb;
		$now_object = $wpdb->get_results ( "SELECT NOW() AS now " );
		$now = $now_object[0]->now;
		return ( $now );	
	}

	public function were_changes_made () {
		return ( $this->made_changes );	
	}

	public function get_option_value_counts ( $field_slug ) {
		return ( $this->db_get_option_value_counts ( $field_slug ) );	
	}

	/*
	*
	* pass through for updated_last functions
	*
	*/
	public function updated_last ( $user_id ) {
		return ( $this->db_updated_last ( $user_id ) );
	}

	abstract protected function db_search ( $meta_query_array, $search_parameters );
	abstract protected function db_save ( &$meta_query_array );
	abstract protected function db_update ( &$meta_query_array );
	abstract protected function db_delete_by_id ( $id );
	abstract protected function db_updated_last ( $user_id ); 
	abstract protected function db_get_option_value_counts ( $field_slug );

	
}


