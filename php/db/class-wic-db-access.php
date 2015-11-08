<?php
/*
*
* class-wic-db-access.php
*
*		Parent for direct db access objects (implemented as extensions to this.)
*			Two major extension _WP for Issue access and _WIC for access to the constituent and subsidiary tables
* 
*		Directly maintains search log (which includes saves) 
*		
*		Mapping of object type to appropriate extension of this class occurs at instantiation via WIC_DB_Access_Factory 
*
*/



abstract class WIC_DB_Access {
	
	// these properties contain  the results of the db access
	public $entity;		// top level entity searched for or acted on ( e.g., constituents or issues )
	public $parent;		// parent entity in form context (not parent class) -- e.g., activity, phone, email, address have constituent as parent
	public $sql; 			// for search, the query executed;
	public $result; 		// entity_object_array -- as saved, update or found( possibly multiple ) (each row as object with field_names properties)
	public $outcome; 		// true or false if error
	public $explanation; // reason for outcome
	public $found_count; // integer save/update/search # records found or acted on
	public $insert_id;	// ID of newly saved entity
	public $search_id;   // ID of search completed -- search log
	public $made_changes; // whether there were any changed values and an update was actually applied ($outcome is true, even if this is false)
								 // note: maintained by the parent entity save_update but only actually used here as maintained by the multivalue child class
								 // used to bubble change_made fact up from child entity to parent and trigger time stamp	

	// extensions must include these functions
	abstract public function db_get_time_stamp ( $id );
	abstract protected function db_do_time_stamp ( $table, $id );
	abstract protected function db_search ( $meta_query_array, $search_parameters );
	abstract protected function db_save ( &$meta_query_array );
	abstract protected function db_update ( &$meta_query_array );
	abstract protected function db_delete_by_id ( $id );
	abstract protected function db_get_option_value_counts ( $field_slug );


	public function __construct ( $entity ) { 
		$this->entity = $entity;
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

	/**********************************************************************************************
	*
	*	SEARCH LOG FUNCTIONS 
	*
	*
	*	Notes on search log usage -- intended scope is only: Form Searches and ID Searches off of Lists
	*		Limited to constituents and issues. 
	*		The search_log method is intended to be invoked only in this class, and, in fact, only by the primary search method.
	*			However, note exception: class-wic-entity-parent.php does need to access the log to spoof search in case of new item saves
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
	*	Advanced searches are logged like regular searches, but with invariant notation used for the search 
	*	In the form, it is driven off of data_dictionary ID, but ID may change with successive upgrades
	*		
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
				$search_log_table = $wpdb->prefix . 'wic_search_log';
				$user_id = get_current_user_id();

				// needed to make search log data invariant across dictionary replacements in upgrades
				if ( 'advanced_search' == $entity ) {
					$meta_query_array = $this->replace_field_id_with_entity_and_field_slugs ( $meta_query_array );
				}
	
				$search = serialize( $meta_query_array );
				$parameters = serialize ( $search_parameters );
				
				$sql = $wpdb->prepare(
					"
					INSERT INTO $search_log_table
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
	
	private function replace_field_id_with_entity_and_field_slugs ( $meta_query_array ) { 
		global $wic_db_dictionary;
		$invariant_meta_query_array = array();
		foreach ( $meta_query_array as $search_term ){
			if ( isset ( $search_term[0] ) ) {
				if ( 'row' == $search_term[0] ) { 
					$invariant_row = array();
					foreach ( $search_term[1] as $query_clause ) { 
						if (isset ( $query_clause['key'] ) ) { 
							if ( '_field' == substr ( $query_clause['key'], -6 ) ) { 
								$field_rules = $wic_db_dictionary->get_field_rules_by_id( $query_clause['value'] ); 
								$invariant_field_id = array ( 
									'entity_slug' 	=> $field_rules['entity_slug'],
									'field_slug' 	=> $field_rules['field_slug'],
								);
								$query_clause['value'] = $invariant_field_id;
							}
						}
						$invariant_row[] = $query_clause; 
					}
					$search_term[1] = $invariant_row;
				}
			}
			$invariant_meta_query_array[] = $search_term;
		};
		return $invariant_meta_query_array;
	}
	
	public static function update_search_name ( $id, $name ) {
		global $wpdb;
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		$user_id = get_current_user_id();
		$user_id_phrase = current_user_can ( 'activate_plugins' ) ? '' : "and user_id = $user_id";
		$sanitized_name = sanitize_text_field ( $name );
		// favorite named searches, but don't unfavorite unnamed searches
		if ( $sanitized_name  > '' ) {
			$is_named = 1;			
			$favorite_phrase = ", favorite = 1 ";
		} else {
			$is_named = 0;
			$favorite_phrase = '';
		}

		$sql = $wpdb->prepare( 
			"UPDATE $search_log_table SET share_name = %s, is_named = $is_named $favorite_phrase where ID = %s $user_id_phrase ",
			array ( $sanitized_name, $id ) 
		);
		$result = $wpdb->query ( $sql );
		return ( $result );
	}	
	
	// used in search log history retrieval -- spoofs a return from a search -- too complex to run through standard option 
	public function retrieve_search_log_latest () {
		global $wpdb;
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		$user_id = get_current_user_id();
		$sql = "SELECT ID from $search_log_table where user_id = $user_id or is_named = 1
				ORDER BY is_named DESC, share_name, favorite desc, search_time DESC
				LIMIT 0, 100";
		$this->sql = $sql; 
		$this->result = $wpdb->get_results ( $sql );
		$this->found_count = count ( $this->result );
	}		
	/*
	*
	* retrieve the latest search for an individual instance of this entity from the search log 
	* return false if non-found.  
	*
	* will not support entity = advanced_search . . . not necessary; used only for entity = issue;
	* 	also will not recognize single issue found through advanced search -- this is a limitation
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
		// handle condition of startup or purged search log		
		if ( 0 == $wpdb->num_rows ) {
			return ( false );	
		}
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
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		
		$search_object = $wpdb->get_row ( "SELECT * from $search_log_table where id = $id " );
		
		$unserialized_search_array = unserialize ( $search_object->serialized_search_array );
		if ( 'advanced_search' == $search_object->entity ) {
			$unserialized_search_array = self::replace_entity_and_field_slugs_with_id ( $unserialized_search_array );  	
		} 
		
		$return = array (
			'search_id' => $id,
			'user_id' => $search_object->user_id,
			'entity' =>  $search_object->entity, 
			'unserialized_search_array' =>  $unserialized_search_array,
			'unserialized_search_parameters' => unserialize( $search_object->serialized_search_parameters ),
			'result_count' =>$search_object->result_count
			);

		return ( $return );		
	}
	
	private static function replace_entity_and_field_slugs_with_id ( $invariant_meta_query_array ) {
		global $wic_db_dictionary;
		$meta_query_array = array();
		foreach ( $invariant_meta_query_array as $search_term ){
			if ( isset ( $search_term[0] ) ) {
				if ( 'row' == $search_term[0] ) { 
					$standard_row = array();
					foreach ( $search_term[1] as $query_clause ) {  
						if (isset ( $query_clause['key'] ) ) { 
							if ( '_field' == substr ( $query_clause['key'], -6 ) ) { 
								$field_rules = $wic_db_dictionary->get_field_rules( $query_clause['value']['entity_slug'], $query_clause['value']['field_slug'] );
								$query_clause['value'] = $field_rules->ID;
							}
						}
						$standard_row[] = $query_clause;
					}
					$search_term[1] = $standard_row;
				}
			}
			$meta_query_array[] = $search_term;
		};
		return $meta_query_array;
	}

	/*
	*
	* mark a search as having been downloaded
	*
	*/
	public static function mark_search_as_downloaded ( $id ) {
		global $wpdb;
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		
		$sql = $wpdb->prepare (
			"
			UPDATE $search_log_table
			SET download_time = %s
			WHERE id = %s
			",
			array( current_time( 'Y-m-d-H-i-s' ), $id ) );
		
		$update_result = $wpdb->query( $sql );
			
		if ( 1 != $update_result ) {
			WIC_Function_Utilities::wic_error ( 'Unknown database error in posting download event to search log.', __FILE__, __LINE__, __METHOD__, true );
		}	
		// mark any downloaded search as favorite
		self::set_search_favorite( $id, 1 );
	}		

	// update favorite bit -- only used for search log
	public static function set_search_favorite ( $id, $favorite ) {
		global $wpdb;
		$search_log_table = $wpdb->prefix . 'wic_search_log';
		$is_named_phrase = ( 1 == $favorite ) ? '' : ' and is_named = 0 ';
		// don't unfavorite a named search -- named searches are always favorited
		$sql = "UPDATE $search_log_table SET favorite = $favorite WHERE ID = $id $is_named_phrase";
		$result = $wpdb->query( $sql );			
		return ( $result);
	} 

	/**********************************************************************************************************
	*
	*	Pass through for delete function -- this is the only delete function and its usage differs from other 
	*		db access functions.  Most are invoked by entity classes.  However, constituents are only soft deleted
	*		by marking them with the DELETED value.  Issues are not deletable except through  Wordpress admin.
	*  
	*		The hard database delete function is used for sub-entities ( e.g. email or activity) and is invoked in 
	*		WIC_Control_Multivalue -- when a form is received with deleted/hidden elements in it, those are discarded
	*		as the control object is loaded in the data_object_array.  If they have an ID, they are also physically deleted
	*		by a call to this function.
	*
	**********************************************************************************************************/

	public function delete_by_id ( $id ) {
		$this->db_delete_by_id ( $id );	
	}


	/**********************************************************************************************************
	*
	*	Main save/update process for WP Issues CRM -- runs across multiple layers, but is controlled by the process below 
	*	Note that this process is invoked by a parent entity after the data_object_array has been assembled; deletes occur in the array 
	*	assembly process.  
	*	(1) Top level entity contains an array of controls -- see wic-entity-parent
	*			+ Basic controls each contain a value which is information about the top level entity like name (not an object property technically, but logically so)
	*			+ Multivalue controls contain arrays of entities that have a data relationship to the top level entity, like addresses for a constituent 
	*  (2) Each multivalue entity, e.g., each address is an entity with the same logical structure as the parent entity -- as a class, their entity is
	*		an extension of the parent entity.
	*  (3) So when update is submitted for the parent entity . . .
	*		 (1) The parent entity (e.g. constituent) creates a new instance of this class (actually always an extension of this class ) 
	*				and passes it a pointer to its array of controls 
	*      (2) Second this object->save_update asks each of the basic controls to produce a set clause 
	*		 (3) The set clauses are applied to the database by this object's WIC extension WIC_DB_Access_WIC 
	*				(straightforward insert update for a single entity)
	*		 (4) This object->save_update then asks each of the multivalue controls in turn to do an update
	*		 (5) Each multivalue control object in turn asks each of the row entities in its row array to do a save_update 
	*		 (6) Each multivalue entities (e.g. address) issues a save update request which comes back through an new instance of this object 
	*				and does only steps (1) through (3) for that object ( no multivalue controls within multivalue controls supported)
	*	(4) Note that deletes of WIC multivalues happens before step 1 as the array is populated from the form.  The delete function timestamps
	*		 the parent entity at that stage.  . . .
	*  (5) Timestamping ( last_updated_time, last_updated_by )is handled as follows.
	*		 - Save/update of parent (e.g., constituent ) timestamps parent, but not any child 
	*		 - Save/update of child (e.g., email) timestamps that child and also the parent
	*		 -	Hard delete of child timestamps the parent
	*		 The first two kinds of time stamp occur through the save_update function; the last through the delete in WIC_Control_Multivalue
	*
	*	Note that the assembly of the save update array occurs in this top level database access class because
	*  updates are handled for particular entities (and this object serves a particular entity).
	*
	*	By contrast, the search array assembly is handled at the entity level because it needs to be able to report up to
	*  a multivalue control and contribute to a join across multiple entities.   
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
		// if main update OK, pass the array again and do multivalue ( child ) updates
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
			
			// am in the multivalue control branch and may have done multivalue updates
			// if so, and top entity hasn't already been marked updated, do so (in the object and the database). 	
			if ( false === $this->made_changes && true === $multivalue_fields_have_changes ) {
				$this->made_changes = true;
				$this->do_time_stamp ( $this->entity, $id ); 
			}						
		}
		return;	
	}

	
	// abbreviated version of save update for use in the upload context
	// no multi value processing necessary, since each entity handled as a top entity in the upload process
	// allow decision as to whether to protect blanks from overwrite.
	public function upload_save_update ( &$doa, $protect_blank_overwrite ) { 
		if ( $protect_blank_overwrite ) {
			$save_update_array = $this->upload_assemble_save_update_array( $doa ); // drops blank values from the update array
		} else {
			$save_update_array = $this->assemble_save_update_array( $doa );
		}

		if ( count ( $save_update_array ) > 0 ) {
			if ( $doa['ID']->get_value() > 0 ) {
				$this->db_update ( $save_update_array );		
			} else { 
				$this->db_save ( $save_update_array );
			}
		}
		// don't bother to set the insert id -- no further processing.
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

	// special version for uploads where want to skip blank values in update process
	protected function upload_assemble_save_update_array ( &$doa ) {
		$save_update_array = array();
		foreach ( $doa as $field => $control ) {
			if ( ! $control->is_multivalue() ) {
				if ( $control->is_present() ) { // only do save-updates when control value non-blank
					$update_clause = $control->create_update_clause();
					if ( '' < $update_clause ) {
						$save_update_array[] = $update_clause;
					}
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

	public function list_by_id ( $id_string ) { 
		$this->db_list_by_id ( $id_string );   
	}

	/*
	*
	* miscellaneous functions
	*
	*/
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

	public static function table_count ( $table_name ) {
		global $wpdb;
		// expects fully qualified table name with prefix
		$result = $wpdb->get_results ( "
			SELECT COUNT(*) as table_count from $table_name
			"
			);
		return ( $result[0]->table_count );	
	}


	/* time stamp -- do time stamp on table */ 
	protected function do_time_stamp ( $table, $id ) {
		$this->db_do_time_stamp ( $table, $id );
	}

	/* time stamp -- get time stamp from entity table for requested id */ 
	public function get_time_stamp ( $id ) {
		return ( $this->db_get_time_stamp ( $id ) );  		 
	}

}