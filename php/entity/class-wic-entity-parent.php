<?php
/*
*
* class-wic-entity-parent.php
*
* base class for wp issues crm tables/entities -- instantiates particular object in the constructor
*
*
* 
*/

abstract class WIC_Entity_Parent {
	
	protected $entity		= ''; 						// e.g., constituent, activity, issue
	protected $entity_instance = '';					// relevant where entity is a row of multivalue array as in emails for a constituent
	protected $fields = array(); 						// will be initialized as field_slug => type array from wp_wic_data_dictionary
	protected $data_object_array = array(); 		// will be initialized as field_slug => control object 
	protected $outcome = '';							// results of latest request 
	protected $outcome_dups = false;					// supplementary outcome information -- dups among error causes
	protected $outcome_dups_query_object;			// results of dup query for listing	
	protected $explanation	= '';						// explanation for outcome
	protected $made_changes = false;					// after a save_update, were changes made?
	
		
	abstract protected function set_entity_parms ( $args ); // must be included in child to set entity and possibly instance
	
	/*
	*
	* constructor just initializes minimal blank structure and passes control to named action requested
	* 
	* note that the current class is an abstract parent class WIC_Entity_Parent
	* 	-- entity is chosen in the wp-issues-crm which initializes the corresponding child class  -- e.g. WIC_Constituent
	*  
	* args is an associative array, which MAY be populated as follows:
	*		'id_requested'			=>	$control_array[2] passed by wp-issues-crm from form button for an ID search
	*		'instance'				=> passed in the case of the object being initialized as a row in multi-value field:	
	*		'id_array'				=> array of id's -- used when passed from issue form to comment entity for conversion to constituent listing
	*		'search_id'				=> pass through of issue search log id that will be used to reconstruct constituent search 
	*
	*/
	public function __construct ( $action_requested, $args ) {
		$this->set_entity_parms( $args );
		$this->$action_requested( $args );
	}

	/*************************************************************************************
	*
	*  METHODS FOR SETTING UP AND POPULATING THE DATA_OBJECT_ARRAY
	*
	**************************************************************************************
	*
	* The major entities retain their logical properties in a single data_object_array of control objects
	*	Some of these controls are multivalue controls, which in turn are arrays of entity objects each with their own array of controls
	*	Have to handle the multivalue controls as arrays.
	*
	* To setup up the entity object (this sequence is built in to each populate function): 
	*  (1) get the entity fields/properties from the data dictionary ( calling $wic_db_dictionary->get_form_fields)
	*  (2) initialize the data object array by instantiating controls for each (each dictionary control type having a corresponding control class )
	*  (3) populate the control objects -- from a submitted form, from a found record, with search parameters or just initialize
	*
	*/
	protected function initialize_data_object_array()  {
		// get fields for the entity
		global $wic_db_dictionary;
		$this->fields = $wic_db_dictionary->get_form_fields( $this->entity );
		// initialize_data_object_array as field_slug => control object 
		foreach ( $this->fields as $field ) { 
			$this->data_object_array[$field->field_slug] = WIC_Control_Factory::make_a_control( $field->field_type );
			$this->data_object_array[$field->field_slug]->initialize_default_values(  $this->entity, $field->field_slug, $this->entity_instance );
		}		
	}

	protected function populate_data_object_array_from_submitted_form() {

		$this->initialize_data_object_array();

		foreach ( $this->fields as $field ) {  	
			if ( isset ( $_POST[$field->field_slug] ) ) {		
				$this->data_object_array[$field->field_slug]->set_value( $_POST[$field->field_slug] );
			}	
		} 
	}	

	protected function populate_data_object_array_from_found_record( &$wic_query, $offset=0 ) { 

		$this->initialize_data_object_array();

		foreach ( $this->data_object_array as $field_slug => $control ) { 
			if ( ! $control->is_multivalue() && ! $control->is_transient()  ) { 
				$control->set_value ( $wic_query->result[$offset]->{$field_slug} );
			} elseif ( $control->is_multivalue() ) { // for multivalue fields, set_value wants array of row arrays -- 
						// query results don't have that form or even an appropriate field slug, 
						// so have to search by parent ID  
				$control->set_value_by_parent_pointer( $wic_query->result[$offset]->ID );
			}
		} 
	}	

	protected function populate_data_object_array_with_search_parameters ( $search ) { 
	
		$this->initialize_data_object_array();
		// reformat $search_parameters array
		$key_value_array = array();		
		foreach ( $search['unserialized_search_array'] as $search_array ) { 
			if ( $search_array['table'] == $this->entity ) {
				// need to convert range controls that were carried as single values (because only one end entered) back into arrays
				if ( '>' == $search_array['compare'] ) {
					$key_value_array[$search_array['key']] = array ( $search_array['value'], '' );
				} else if ( '<' == $search_array['compare'] ) {
					$key_value_array[$search_array['key']] = array ( '', $search_array['value'] );
				// otherwise, just assign values
				} else { 
					$key_value_array[$search_array['key']] = $search_array['value'];
				}
				// the transient search parameter category_search_mode is pulled directly into the search array as a compare value
				// by WIC_Control_Parent::create_search_clause, so it is not in the search parameter array and has to be put back for the doa
				if ( 'post_category' == $search_array['key'] ) { 
					$key_value_array['category_search_mode'] = $search_array['compare'];				
				} 
			} else {
				// spoof an incoming form array for a multivalue control	
				// (to the top entity multivalue control looks like any other, but its set value function needs an array)			
				if ( ! isset ( $key_value_array[$search_array['table']] ) ) {
					$key_value_array[$search_array['table']] = array();
					$key_value_array[$search_array['table']][0] = array();				
				}
				$key_value_array[$search_array['table']][0][$search_array['key']] = $search_array['value'];		
			}
		}

		// already in key value format
		$combined_form_values = array_merge ( $key_value_array, $search['unserialized_search_parameters']);

		// pass data object array and see if have values
		foreach ( $this->data_object_array as $field_slug => $control ) { 
			if ( isset ( $combined_form_values[$field_slug] ) ) {
					$control->set_value ( $combined_form_values[$field_slug] );
			}
		} 
	}
	
	// initialize data object array for an id, but don't display a form
	protected function initialize_only ( $args ) {
		$this->id_search_generic ( $args['id_requested'], '', '', false, false ); // no form, no sql, no log, no old search ID -- don't display	
	}

	/*************************************************************************************
	*
	*  METHODS FOR SANITIZING VALIDATING THE DATA_OBJECT_ARRAY
	*     Results stored in object properties -- outcome, outcome_dups, explanation
	*
	**************************************************************************************/
	private function update_ready( $save ) { // true is save, false is update
		// runs all four sanitize/validate functions
		$this->sanitize_values();
		$this->validate_values();
		$this->required_check();
		// do dup check last -- required fields are part of dup check
		// outcome starts as empty string, set to false if validation or required errors
		// check that dup checking not overriden
		if ( '' === $this->outcome && ! isset ( $_POST['no_dupcheck'] ) ) {		
			$this->dup_check ( $save );
		}
	}
	
	public function sanitize_values() {
		// have each control sanitize itself
		foreach ( $this->data_object_array as $field => $control ) {
			$control->sanitize();
		}
	}
	
	// check for dups -- $save is true/false for save/update
	protected function dup_check ( $save ) {
		global $wic_db_dictionary;
		$dup_check = false;
		// first check whether any fields are set for dup checking
		foreach ( $this->data_object_array as $field_slug => $control ) {
			if	( $control->dup_check() ) {
				$dup_check = true;
			}	
		}
		
		// if there are some dup check fields defined, proceed to do do dupcheck	
		if ( $dup_check ) {
			$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
			$search_parameters = array(
				'select_mode' => 'id',
				'show_deleted' => false,
				'log_search' => false,		
			);
			$search_clause_args = array(
				'match_level' => '0',
				'dup_check' => true,
				'category_search_mode' => '',
			);
			// assembling meta_query with strict match and dedup requested
			$wic_query->search ( $this->assemble_meta_query_array( $search_clause_args ), $search_parameters );  // true indicates a dedup search
			if ( $wic_query->found_count > 1 || ( ( 1 == $wic_query->found_count ) && 
						( $wic_query->result[0]->ID != $this->data_object_array['ID']->get_value() ) )
						// for update, 1 group OK iff same record
					|| ( $save && $wic_query->found_count > 0 ) ) {
						// for save, dups are never OK
				$this->outcome = false;
				$dup_check_string = $wic_db_dictionary->get_dup_check_string ( $this->entity );
				$this->explanation .= sprintf ( __( 'Other records found with same combination of %s.' , 'wp-issues-crm' ), $dup_check_string );
				$this->outcome_dups = true;
				$this->outcome_dups_query_object = $wic_query;		
			}
		}		 
	}

	public function validate_values() {
		// have each control validate itself and report
		$validation_errors = '';		
		foreach ( $this->data_object_array as $field => $control ) {
			$validation_errors .= $control->validate();
		}
		if ( $validation_errors > '' ) {
			$this->outcome = false;		
			$this->explanation .= $validation_errors;
			return ( $validation_errors . sprintf( __( ' ( Message from %1$s object, instance %2$s. ) ', 'wp-issues-crm' ), 
				$this->entity, $this->entity_instance + 1 ) );		
		} else {
			return ('');		
		}
	}

	public function required_check () {
		global $wic_db_dictionary;
		// have each control see if it is present as required 
		$required_errors = '';
		$there_is_a_required_group = false;
		$a_required_group_member_is_present = false;		
		foreach ( $this->data_object_array as $field_slug => $control ) {
			$required_errors .= $control->required_check();	
			if ( $control->is_group_required() ) {
				$there_is_a_required_group = true;			
				$a_required_group_member_is_present = $control->is_present() ? true : $a_required_group_member_is_present ;
			}
		}
		// report cross-control group required result
		if ( $there_is_a_required_group && ! $a_required_group_member_is_present ) {	
			$required_errors .= sprintf ( __( ' At least one among %s is required. ', 'wp-issues-crm' ), $wic_db_dictionary->get_required_string( $this->entity, "group" ) );
		}
		if ( $required_errors > '' ) {
			$this->outcome = false;
			$this->explanation .= $required_errors;		
		}
		
		return ( $required_errors );
   }

	/*************************************************************************************
	*
	*  METHODS FOR COMPILING SEARCH REQUESTS FROM CONTROLS 
	*	This function lives in this class so that it can be called publicly by a multivalue control
	*	   which may be assembling query conditions from entities within it to contribute to a 
	*     search involving a multi-table join.  The corresponding update assembly lives in the database access
	*		layer which, although it will support multi-entity searches, only updates one entity.
	*
	*  It is called in this class by dedup, id search and form search functions and also in the multivalue control class
	*
	*	It pass through to controls the dedup, match level and category search_clause_args, using only the dedup arg itself 
	*
	**************************************************************************************/
	public function assemble_meta_query_array ( $search_clause_args ) {
		extract ( $search_clause_args, EXTR_OVERWRITE );
		$meta_query_array = array ();
		foreach ( $this->data_object_array as $field => $control ) {
			$query_clause = '';
			if ( ! $dup_check || $control->dup_check() ) { // all fields if not dupchecking, otw, only dup_check fields
				$query_clause = $control->create_search_clause( $search_clause_args );
				if ( is_array ( $query_clause ) ) { // not making array elements unless field returned a query clause
					$meta_query_array = array_merge ( $meta_query_array, $query_clause ); // will do append since the arrays of arrays are not keyed arrays 
				}
			}
		}	
		return $meta_query_array;
	}	

	/*************************************************************************************
	*
	*  SIMPLE FORM REQUEST HANDLERS: new form, search_form_from_search_array save form from search
	*     Child class functions are wrap arounds to choose next forms
	*
	**************************************************************************************/

	protected function new_form_generic( $form, $guidance = '' ) {
		global $wic_db_dictionary;
		$this->fields = $wic_db_dictionary->get_form_fields( $this->entity );
		$this->initialize_data_object_array();
		$new_search = new $form;
		$new_search->layout_form( $this->data_object_array, $guidance, 'guidance' );
	}	
	
	// used to recreate form when changing search parameters
	protected function search_form_from_search_array ( $form, $guidance, $serialized_search_array ) {
		$this->populate_data_object_array_with_search_parameters ( $serialized_search_array ); 
		$new_search = new $form;
		$new_search->layout_form( $this->data_object_array, $guidance, 'guidance' );
	}

	// handle a save request coming from a search -- 
	// need to lose readonly fields from search form, so show save form, rather than proceeding directly to do a save from the search form
	protected function save_from_search ( $entity_save_form, $message = '', $message_level = 'good_news', $sql = ''  ) {
		$this->populate_data_object_array_from_submitted_form();
		$save_form = new $entity_save_form;
		$save_form->layout_form ( $this->data_object_array, $message, $message_level, $sql );		
	}

	/*************************************************************************************
	*
	* REQUEST HANDLER FOR SAVE UPDATE REQUESTS
	*
	*************************************************************************************/
	//handle an update request coming from a form ( $save is true or false )
	protected function form_save_update_generic ( $save, $fail_form, $success_form ) {
		// populate the array from the submitted form
		$this->populate_data_object_array_from_submitted_form();
		// validate the array
		$this->update_ready( $save ); // false, not a save
		// if issues, show the fail form with messages; if dups, show the list of dups 
		if ( false === $this->outcome ) {
			$message = __( 'Not successful: ', 'wp-issues-crm' ) . $this->explanation;
			$message_level = 'error';
			$form = new $fail_form;
			$form->layout_form ( $this->data_object_array, $message, $message_level );
			if ( $this->outcome_dups ) {	
				$lister_class = 'WIC_List_' . $this->entity;
				$lister = new $lister_class;
				$list = $lister->format_entity_list( $this->outcome_dups_query_object, '' );
				echo $list;
			}	
			return;
		}
		// form was good so do save/update
		$wic_access_object = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		$wic_access_object->save_update( $this->data_object_array );
		// handle failed save/update 
		if ( false === $wic_access_object->outcome ) {
			$message =  $wic_access_object->explanation;
			$message_level = 'error';
			$form = new $fail_form;
			$form->layout_form ( $this->data_object_array, $message, $message_level );
		// proceed on successful save/update
		} else {
			if ( $save ) { 
				// retrieve the new ID from the save process
				$this->data_object_array['ID']->set_value( $wic_access_object->insert_id );
				// in this branch, i.e., case of a save, want to spoof a search log entry as an id search so can return to saved entity
				// note that in the case of an update, the search log already has an entry pointing to the entity, since found it for update
				// so . . . .  prepare search log items ( as in WIC_Entity_Parent->id_search_generic )
				$search_clause_args = array(
					'match_level' => '0',
					'dup_check' => false,
					'category_search_mode' => '',
					);
				// invoke the search_clause creator from ID control (comes assembled with own wrapper array)
				$meta_query_array = $this->data_object_array['ID']->create_search_clause( $search_clause_args );
				$search_parameters = array (
					'select_mode' 		=> '*',
					'show_deleted' 	=> true,	
					'log_search' 		=> true,
					'old_search_id' 	=> false,
				);
				$wic_access_object->found_count = 1; // as if had done a search
				$wic_access_object->search_log ( $meta_query_array, $search_parameters );						
			}
			// check if changes were made
			$this->made_changes = $wic_access_object->were_changes_made();
			// populate any other values like ID which come from the update process
			$this->special_entity_value_hook( $wic_access_object ); // done on both save and updates, but hook may test values
			// show success form
			$message = __( 'Successful.  You can update further. ', 'wp-issues-crm' );
			$message_level = 'good_news';
			$form = new $success_form;
			$form->layout_form ( $this->data_object_array, $message, $message_level );					
		}
	}

	/*************************************************************************************
	*
	* REQUEST HANDLER FOR SEARCH REQUESTS -- BY ID, FROM FORM, FROM SAVED SEARCH ARRAY 
	*
	*************************************************************************************/
	
	// handle a search request for an ID coming from anywhere
	// passing a blank success form just leaves the array instantiated, but no action taken
	// no fail form because fail is an error
	// carrying primary search ID supports the export and redo buttons for issues forms which have secondary constituent searches
	protected function id_search_generic ( $id, $success_form, $sql, $log_search, $primary_search_id ) { 
		// primary search is search from handler that has already done a form search 
		// initialize data array with only the ID 
		$this->data_object_array['ID'] = WIC_Control_Factory::make_a_control( 'text' );
		$this->data_object_array['ID']->initialize_default_values(  $this->entity, 'ID', $this->entity_instance );	
		$this->data_object_array['ID']->set_value( $id );
		// set up search object
		$wic_query = 	WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		// set up search arguments and parameters
		$search_parameters = array(
			'select_mode' => '*',
			'show_deleted' => true,		
			'log_search' => $log_search,
			'old_search_id' => $primary_search_id, // will be disregarded if log search is true
		);
		$search_clause_args = array(
			'match_level' => '0',
			'dup_check' => false,
			'category_search_mode' => '',
			);
		// assemble metaquery with match_level = 0 (strict match) and dup check set to false
		$wic_query->search ( $this->assemble_meta_query_array( $search_clause_args ), $search_parameters ); 
		// retrieve record if found, otherwise error
		if ( 1 == $wic_query->found_count ) { 
			$message = __( '', 'wp-issues-crm' );
			$message_level =  'guidance';
			$this->populate_data_object_array_from_found_record ( $wic_query );			
			if ( $success_form > '' ) {
				$update_form = new $success_form; 
				$update_form->layout_form ( $this->data_object_array, $message, $message_level, $sql );	
				$this->list_after_form ( $wic_query );
			}
		} else {
			WIC_Function_Utilities::wic_error ( sprintf ( 'Data base corrupted for record ID: %1$s in id_search_generic.' , $id ), __FILE__, __LINE__, __METHOD__, true );		
		} 
	}
	
	// make a search request based on form input
	protected function form_search_generic ( $not_found_form, $found_form ) { 

		$this->populate_data_object_array_from_submitted_form();
		$this->sanitize_values();
		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		$search_parameters= array(
			'sort_order' 		=> isset ( $this->data_object_array['sort_order'] ) ? $this->data_object_array['sort_order']->get_value() : '',
			'compute_total' 	=> isset ( $this->data_object_array['compute_total'] ) ? $this->data_object_array['compute_total']->get_value() : '',
			'retrieve_limit' 	=> isset ( $this->data_object_array['retrieve_limit'] ) ? $this->data_object_array['retrieve_limit']->get_value() : '',
			'show_deleted' 	=> isset ( $this->data_object_array['show_deleted'] ) ? $this->data_object_array['show_deleted']->get_value() : '',
			'select_mode'		=> 'id',
			'log_search'		=> true
			);
		$search_clause_args = array(
			'match_level' =>  isset ( $this->data_object_array['match_level'] ) ? $this->data_object_array['match_level']->get_value() : '',
			'dup_check' => false,
			'category_search_mode' => isset ( $this->data_object_array['category_search_mode'] ) ? $this->data_object_array['category_search_mode']->get_value() : '',
			);
		// note that the transient search parameter 'match_level' is needed by individual controls in create_search_clause()
				
		$wic_query->search ( $this->assemble_meta_query_array( $search_clause_args ), $search_parameters ); // get a list of id's meeting search criteria
		$this->handle_search_results ( $wic_query, $not_found_form, $found_form );
	}

	// takes action depending on outcome of search
	// the first form passed will be a save form, the second an update or may display neither and a list instead if multiple found
	protected function handle_search_results ( $wic_query, $not_found_form, $found_form ) { 
		$sql = $wic_query->sql;
		if ( 0 == $wic_query->found_count ) {
			$message = __( 'No matching record found -- search again, save new or start over.', 'wp-issues-crm' );
			$message_level =  'error';
			$form = new $not_found_form;
			$form->layout_form ( $this->data_object_array, $message, $message_level, $sql );			
		} elseif ( 1 == $wic_query->found_count) { 
			$this->data_object_array = array(); // discard possibly soft matching array values before doing straight id retrieval
			$this->id_search_generic ( $wic_query->result[0]-> ID, $found_form, $sql, false, $wic_query->search_id );	// do not log second search; use original ID
		} else {
			$lister_class = 'WIC_List_' . $this->entity ;
			$lister = new $lister_class;
			$list = $lister->format_entity_list( $wic_query, '' );
			echo $list;	
		}
	}

	// used in reconstructing searches from the search log ( CAN TERMINATE WITHOUT A FORM IF NO FORMAT GIVEN )
	// in which case, just retrieve ID, not full record
	public function redo_search_from_meta_query ( $search, $save_form, $update_form ) { 
		global $wic_db_dictionary;
		$this->fields = $wic_db_dictionary->get_form_fields( $this->entity );
		$this->initialize_data_object_array();
		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		// don't want to log previously logged search again, but do want to know ID for down load and redo search purposes
		// talking to search function as if a new search, but with two previous parameters set
		$search['unserialized_search_parameters']['log_search'] = false;
		$search['unserialized_search_parameters']['old_search_id'] = $search['search_id'];
		$wic_query->search ( $search['unserialized_search_array'], $search['unserialized_search_parameters'] ); //
		if ( '' < $save_form ) {
			$this->handle_search_results ( $wic_query, $save_form, $update_form ); // show form or list
		} else { 
			$this->data_object_array['ID']->set_value( $wic_query->result[0]->ID ); 
			// take the first found if for some reason multiple 
		}	// sit tight with array having just ID to report found ID 
			// (no other variables available at this stage, have not searched by the ID)
	}

	// wrap around for preceding function to invoke it without a form
	// used by the latest function to determine what ID was found in a singleton search result
	public function redo_search_from_meta_query_no_form ( $search ) {
		$this->redo_search_from_meta_query( $search, '' , '' );
	}
	
	/***************************************************************************************
	*
	* REQUEST HANDLER FOR "LATEST" REQUESTS -- COMPUTE, SHOW FORM, INSTANTIATE FOR ACCESS TO VALUES
	*
	****************************************************************************************/
	

	// determine the latest of this entity which the user has saved, updated or selected from a list
	/* protected function compute_latest ( $args ) { 
		$user_id = 0;
		if ( isset ( $args['id_requested']) ) {
			$user_id = $args['id_requested'];
		} 
		// test for positive integer user ID
		if ( 1 > $user_id || $user_id != absint( $user_id ) ) {
			wic_generate_call_trace();
			WIC_Function_Utilities::wic_error ( sprintf ( 'Bad User ID passed.' , $id ), __FILE__, __LINE__, __METHOD__, true );		
		}
		$wic_access_object = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		// check last updated from database
		$latest_update_array = $wic_access_object->updated_last ( $user_id );
		// check last singleton found from search_log
		$latest_search_array = $wic_access_object->search_log_last ( $user_id );
		// determine which was later
		$latest = WIC_Function_Utilities::choose_latest_non_blank ( 
			$latest_update_array['latest_updated'], 
			$latest_update_array['latest_updated_time'],
			$latest_search_array['latest_searched'], 
			$latest_search_array['latest_searched_time']
			);
		
		$return_array = array();
		// set latest if found
		if ( $latest > '' ) {
			$return_array['latest_entity'] = $latest;		
		}		
		// set latest search if source of latest
		if ( $latest == $latest_search_array['latest_searched'] ) {
			$return_array['latest_search_ID'] = $latest_search_array['latest_search_ID'];
		}
		
		return ( $return_array );
		
	}	*/
	
/*	// display the latest of this entity for the user in an update form 
	protected function get_latest ( $args ) { // as passed to function, args should contain user ID as 'id_requested'
		$latest_array = $this->compute_latest ( $args  ); 
		if ( isset ( $latest_array['latest_entity'] ) ) {	
			$args2 = array ( 'id_requested' => $latest_array['latest_entity'] );
			if ( isset ( $latest_array['latest_search_ID'] ) ) {
				$args2['old_search_ID'] = $latest_array['latest_search_ID'];
			}		
			$this->id_search_no_log ( $args2 ); 
				// id_search_no_log lives in the instantiated constituent or issue object
				// and includes a form specific to the instantiated entity
				// calls id_search_generic with the class for a constituent or issue
		} else {
			$this->new_blank_form(); 		
		} 
	} */
	
	// just load the latest entity -- used to give title to drop down when grabbing latest entity
	protected function get_latest_no_form ( $args ) {
		// $latest_array = $this->compute_latest ( $args ); 
		// just get it direct form the search log
		$user_id = $args['id_requested']; // assume this is good 
		$wic_access_object = WIC_DB_Access_Factory::make_a_db_access_object( $this->entity ); // bypassing compute_latest
		$latest_array = $wic_access_object->search_log_last ( $user_id ); // bypassing compute_latest

		if ( $latest_array['latest_searched'] > '0' ) {								 	
			$this->id_search_generic ( $latest_array['latest_searched'], '', '', false, false ); // just retrieves the record, if no class forms are passed, search is not logged 	
		}	
	}

	// return the current ID and title for the object
	protected function get_current_ID_and_title () {
		if ( isset ( $this->data_object_array['ID'] ) ) { 
			return ( array (
				 'current' 	=> $this->data_object_array['ID']->get_value(),
				 'title' 	=> $this->get_the_title(),
				)
			);
		} else {
			return ( array (
				 'current' 	=> '',
				 'title' 	=> '',
				)
			);
		}
	} 

	// return the ID of the object
	public function get_the_current_ID () {
		return (  $this->data_object_array['ID']->get_value() );
	}

	protected function special_entity_value_hook ( &$wic_access_object ) {
		// available to bring back values from save/update for entity where a value is created by the save process
		// must have correlated language in the save process -- see wic-entity-issue and wic-entity-data-dictionary
	}
	
	protected function list_after_form ( &$wic_query ) {
		// hook for use with list of constituents after issue display -- see WIC_Entity_Issue 	
	}
	
	public function were_changes_made () {
		return ( $this->made_changes );	
	}
}

