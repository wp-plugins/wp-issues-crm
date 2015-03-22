<?php
/*
*
*	wic-entity-upload.php
*
*/



class WIC_Entity_Upload extends WIC_Entity_Parent {

	protected function set_entity_parms( $args ) {
		$this->entity = 'upload';
		$this->entity_instance = '';
	} 
	
	public function __construct() {
		
		$this->set_entity_parms( '' );
		// default action for page is to show the list
		if ( ! isset ( $_POST['wic_form_button'] ) && ! isset( $_GET[ 'action' ] )  ) {
			 $this->list_uploads();
		// if have a button or an action requested, doing something other than list
		} else { 
			// first, do we have a button press? if so, take the requested action			
			if ( isset ( $_POST['wic_form_button'] ) ) {
				$control_array = explode( ',', $_POST['wic_form_button'] ); 		
				$args = array (
					'id_requested'			=>	$control_array[2],
					'instance'				=> '', // unnecessary in this context, absence will not create an error but here for consistency about arguments;
				);
	 			// if we have are doing the preliminary upload, will just do that -- show no tabs, otherwise show tabs before taking action
				// note that control[0] is superfluous in admin context since page only serves a single entity class
				$this->{$control_array[1]}( $args );
			// if no button, then there better be a get string
			}	else {		
 				$action = $_GET['action'];
				$args = array (
					'id_requested'			=>	$_GET['upload_id'],
					'instance'				=> '', // unnecessary in this context, absence will not create an error but here for consistency about arguments;
				);
				$this->$action( $args );
			}
			
		}
	}	

	public static function format_tab_titles ( $upload_id ) {
		
		$tab_titles_array = array (
			'Upload Raw' 	=> 'details', 		
			'Map Fields'		=> 'map',
			'Validate Data'		=> 'validate',
			'Define Matching'	=>	'match',
			'Complete Upload'	=>	'complete',
		);			
		
		$active_tab = isset( $_GET[ 'action' ] )  ? $_GET[ 'action' ] : 'details';		
		
		$output = '<div id = "upload-tabs-headers"><ul class = "upload-tabs-headers">';
		    	foreach ( $tab_titles_array as $tab_title => $tab_link ) {
		    		$nav_tab_active = ( $active_tab == $tab_link ) ? 'nav-tab-active' : 'nav-tab-inactive';
					$output .= '<li class="' . $nav_tab_active . '">
							<a href="/wp-admin/admin.php?page=wp-issues-crm-uploads&action=' . $tab_link . '&upload_id=' . $upload_id . '"> '. 
						esc_html( trim( __( $tab_title, 'wp-issues-crm' ) ) )  .'</a></li>';
   			
				}  
      $output .= '</ul></div><div class = "horbar-clear-fix" ></div>';
	
		return ( $output ); 
	
	}


	
	protected function list_uploads () {
		// table entry in the access factory will make this a standard WIC DB object
		$wic_query = 	WIC_DB_Access_Factory::make_a_db_access_object( $this->entity );
		// pass a blank array to retrieve all uploads
		$wic_query->search (  
				array(),	
				array( 'retrieve_limit' => 9999, 'show_deleted' => true, 'log_search' => false, 'sort_order' => true ) 
			);
		$lister_class = 'WIC_List_' . $this->entity ;
		$lister = new $lister_class;
		$list = $lister->format_entity_list( $wic_query, '' ); 
		echo $list;
	}	
	
	// handle a request for a blank new upload form
	protected function new_blank_form() {
		$this->new_form_generic ( 'WIC_Form_Upload_Save' );	
	}
	
	//handle a save (upload) request coming from a save form
	protected function form_save () {
		$this->form_save_update_generic ( true, 'WIC_Form_Upload_Save', 'WIC_Form_Upload_Update' );
	return;
	}	
	
	//handle an update request coming from an update form
	protected function form_update () {
		$this->form_save_update_generic ( false, 'WIC_Form_Upload_Update', 'WIC_Form_Upload_Update' );
		return;
	}	
	
	// handle a search request for an ID coming from anywhere
	protected function id_search ( $args ) {
		$id = $args['id_requested']; 
		$this->id_search_generic ( $id, 'WIC_Form_Upload_Update', '' , false, false ); 
		return;		
	}	
	
	// show the upload map fields form
	protected function map ( $args ) {
		$id = $args['id_requested']; 
		$this->id_search_generic ( $id, 'WIC_Form_Upload_Map', '' , false, false );
	}
	
	// show the validate form -- this is individual field data validation doable only after mapping complete		
	protected function validate ( $args ) {
		$id = $args['id_requested']; 
		$this->id_search_generic ( $id, 'WIC_Form_Upload_Validate', '' , false, false );
	}			
	
	// show the validate form -- this is individual field data validation doable only after mapping complete		
	protected function match ( $args ) {
		$id = $args['id_requested']; 
		$this->id_search_generic ( $id, 'WIC_Form_Upload_Match', '' , false, false );
	}			

	protected function complete ( $args ) {
		echo self::format_tab_titles( $_GET['upload_id'] );
		echo '<h3>here goes the complete stuff</h3>';	
	}
			
	
	// function from parent needs to be overridden to set name value from $_FILES array
	protected function populate_data_object_array_from_submitted_form() {

		$this->initialize_data_object_array();

		foreach ( $this->fields as $field ) {  	
			if ( isset ( $_POST[$field->field_slug] ) ) {		
				$this->data_object_array[$field->field_slug]->set_value( $_POST[$field->field_slug] );
			} elseif ( isset ( $_FILES[$field->field_slug]['name'] ) ) {
				$this->data_object_array[$field->field_slug]->set_value( $_FILES[$field->field_slug]['name'] );			
			}	
		}
		 
	}	
	
	/************************************************************************************************
	*
	* sanitizor functions invoked by the control sanitizor if present
	*
	************************************************************************************************/
	// sanitize the file name	
	public static function upload_file_sanitizor ( $upload_file ) {
		return ( sanitize_file_name ( $upload_file ) );			
	}
	
	public static function delimiter_sanitizor ( $delimiter ) {
	
		// sanitize the delimiter by translating to valid delimiter and enforcing default
		$decode_delimiter = array (
			'comma' 	=> ',',
			'semi'	=>	';',
			'tab'		=>	'\t',
			'space'	=>	' ',
			'colon'	=>	':',
			'hyphen'	=>	'-',		
		);
		$delimiter = isset ( $decode_delimiter[$delimiter] ) ? $decode_delimiter[$delimiter] : ',';

		return ( $delimiter );
	}	
	
	public static function enclosure_sanitizor ( $enclosure ) {
	
	// sanitize the enclosure by translating to valid enclosure and enforcing default
		$decode_enclosure = array (
			'1'		=>	'\'',
			'2'		=>	'"',
			'b'		=> '`',
		); 		
		$enclosure = isset ( $decode_enclosure[$enclosure] ) ? $decode_enclosure[$enclosure] : '"';

		return ( $enclosure );
		
	}	
	
	// sanitize the escape value
	public static function escape_sanitizor ( $escape ) {	
		// override unset, empty, blank or over-escaped escape character
		if ( ! isset ( $escape ) ) {
			$escape = "\\";		
		} elseif ( '' == $escape || ' ' == $escape || '\\\\' == $escape ) {
			$escape = "\\";		
		}
		return ( $escape );
	
	}
	
	
	// primary validation function for an incoming file is in control_file, but additional validation requires specifics of the upload request
	// these are present in the data_object_array, not visible to the control file, so do this here.
	public function validate_values() {
					
		$validation_errors = parent::validate_values(); 
		$file_name = $this->data_object_array['upload_file']->get_value();
		
		 // do additional validation only if passed basic and have file ( don't have file on updates )
		if ( '' == $validation_errors && isset ( $_FILES['upload_file'] ) ) {

			// does this at least purport to be a csv file ?
			if ( 'csv' != pathinfo( $file_name, PATHINFO_EXTENSION) && 'txt' != pathinfo( $file_name, PATHINFO_EXTENSION)) {
				$validation_errors .= __( 'This upload function only accepts .csv and .txt files.', 'wp-issues-crm' );
			}			

			if  ( '' == $validation_errors ) {	
				// open the upload file
				$handle = fopen( $_FILES['upload_file']['tmp_name'], 'rb' );
				// error if can't open the file
				if ( ! $handle ) {
					$validation_errors .= __( 'Error opening uploaded file', 'wp-issues-crm' );		
				}
			}

			if  ( '' == $validation_errors ) {		
				// does it really act like a csv file?
		  	   $data = fgetcsv( $handle, 
		  	   			$this->data_object_array['max_line_length']->get_value(),
		  	   			$this->data_object_array['delimiter']->get_value(),
		  	   			$this->data_object_array['enclosure']->get_value(),
		  	   			$this->data_object_array['escape']->get_value()
		  	   			); 
		      if ( false === $data ) {
					$validation_errors .= __( 'File uploaded and opened, but unable to read file as csv.  Check upload parameters. ', 'wp-issues-crm' );		
				} elseif (  count( $data ) < 2 ) {      	
					$validation_errors .= __( 'File appears to have zero or one columns, possible error in delimiter definition.', 'wp-issues-crm' );		
		      }
	      }

	      if  ( '' == $validation_errors ) {
		      //start over 
		      rewind( $handle );
		      // check for consistent column count
		      $count = count ( $data );
		      $row_count = 1;
		      while ( ( $data = fgetcsv($handle, 
				  			$this->data_object_array['max_line_length']->get_value(),
				  			$this->data_object_array['delimiter']->get_value(),
				 			$this->data_object_array['enclosure']->get_value(),
				 			$this->data_object_array['escape']->get_value()
			         ) ) !== FALSE) {	
		      	$row_count++;	
					if ( count ( $data ) != $count ) {
						$validation_errors .= sprintf ( __( 'File appears to have inconsistent column count.  
										First row had %1$d columns, but row %2$d had %3$d columns.', 'wp-issues-crm' ), 
										$count, $row_count, count ( $data ) );
						break;
					} 
		      }
		
				// reject singleton row count
				if ( 1 == $row_count ) {
					$validation_errors .= __( 'File appears to have only one row, possible error in file creation or upload parameters.', 'wp-issues-crm' );					
				}
			}
		}	

		if ( $validation_errors > '' ) {
			$this->outcome = false;		
			$this->explanation .= $validation_errors;
			return ( $validation_errors );		
		} else {
			return ('');		
		}
	} 

	// set values from initial save process to be visible on update form after save; on update, do nothing
	protected function special_entity_value_hook ( &$wic_access_object ) {
		if ( isset ( $wic_access_object->upload_time ) ) { // if one set, both set -- only set in access object on initial save		
			$this->data_object_array['upload_time']->set_value( $wic_access_object->upload_time );
			$this->data_object_array['upload_by']->set_value( $wic_access_object->upload_by );
			$this->data_object_array['serialized_upload_parameters']->set_value( $wic_access_object->serialized_upload_parameters );
			$this->data_object_array['serialized_column_map']->set_value( $wic_access_object->serialized_column_map );
			$this->data_object_array['upload_status']->set_value( $wic_access_object->upload_status );
		}		
	}	
	
	protected function details ( $args ) {
		$this->id_search( $args );
	}	
	
	/*
	*
	* Functions to support column mapping
	*
	*/

	public static function get_column_map( $upload_id ) {
		$column_map =  WIC_DB_Access_Upload::get_column_map( $upload_id ) ;
		echo $column_map;
		wp_die();
	}

	public static function update_column_map( $upload_id, $column_map  ) {
		$column_map = stripslashes ( $column_map ) ;
		// strip slashes and save it in the database
		$outcome = WIC_DB_Access_Upload::update_column_map( $upload_id , $column_map ) ;
		// check whether any columns mapped after latest changes
		// upload status is degraded to staged if nothing mapped; set to mapped if something mapped (which may be an upgrade from staged or a downgrade from later step)
		$upload_status = self::are_any_columns_mapped ( json_decode( $column_map ) ) ? 'mapped' : 'staged'; 
	
		WIC_DB_Access_Upload::update_upload_status( $upload_id, $upload_status );		
				
		if ( false !== $outcome ) {
			echo json_encode ( array ( 'ajax response' => __( 'AJAX update_column_map successful on server side.', 'wp_issues_crm') ) );
		} else {
			echo json_encode ( __( 'AJAX update_column_map ERROR on server side.', 'wp_issues_crm') );
		}
		wp_die();
	}
	
	public static function update_interface_table ( $upload_field, $entity_field_object ) {
		$outcome = WIC_DB_Access_Upload::update_interface_table ( $upload_field,  json_decode( stripslashes ( $entity_field_object ) ) );
		if ( false !== $outcome ) {
			echo json_encode ( array ( 'ajax response' => __( 'AJAX update_interface_table successful on server side.', 'wp_issues_crm') ) );
		} else {
			echo json_encode ( __( 'AJAX update_interface_table ERROR on server side.', 'wp_issues_crm') );
		}		
		wp_die();	
	}	
	
	
	public static function are_any_columns_mapped ( $column_map ) {
		$columns_mapped = false;
		
		foreach ( $column_map as $column => $entity_field_array ) {
			if ( $entity_field_array  > '' ) { 
				if ( $entity_field_array->entity > '' && $entity_field_array->field > '' ) {
					$columns_mapped = true;
					break;			
				}
			}
		}
		return $columns_mapped;
	} 	

	/*
	*
	* functions to support validation
	*
	*/


	public static function reset_validation ( $upload_id, $json_encoded_staging_table_name  ) {
		
		// reset counts in column map
		$column_map = json_decode ( WIC_DB_Access_Upload::get_column_map ( $upload_id ) );
		foreach ( $column_map as $column=>$entity_field_object ) {
			if ( $entity_field_object > '' ) { 
				$entity_field_object->non_empty_count = 0;		
				$entity_field_object->valid_count = 0;
			}
		}
		WIC_DB_Access_Upload::update_column_map ( $upload_id, json_encode ( $column_map ) );
		
		// reset validation indicators on staging table
		$table = json_decode ( stripslashes( $json_encoded_staging_table_name ) );
		
		$result = WIC_DB_Access_Upload::reset_staging_table_validation_indicators( $table );
		if ( $result ) {
			wp_die( json_encode ( __( 'Staging table validation indicators reset.', 'wp-issues-crm' ) ) );
		} else {
			// send errors not encoded, so will generate alert on return
			wp_die ( __( 'Error resetting staging table validation indicators', 'wp-issues-crm' ) );		
		}
		
		
	}

	
	public static function validate_upload ( $upload_id, $validation_parameters ) {
				
		// get the column to database field map for this upload
		$column_map = json_decode ( WIC_DB_Access_Upload::get_column_map ( $upload_id ) );
	
		// construct data object array analogous to form, but with only the controls for the matched fields 
		// no multivalue construct in this context -- no multivalue fields are uploadable -- support multivalues as separate lines of input
		$data_object_array = array();
		$valid_values = array(); 

		// set up dictionary for use here and by controls		
		global $wic_db_dictionary;
		$wic_db_dictionary = new WIC_DB_Dictionary;

		foreach ( $column_map as $column => $entity_field_object ) {
			// including those columns which have been mapped			
			if ( '' < $entity_field_object ) {
				$field_rule = $wic_db_dictionary->get_field_rules ( $entity_field_object->entity, $entity_field_object->field );
				$data_object_array[$column] = WIC_Control_Factory::make_a_control( $field_rule->field_type ); 	
				$data_object_array[$column]->initialize_default_values(  $entity_field_object->entity, $entity_field_object->field,  '' );
				// for select fields, set up an array of valid values items for validation loop (avoiding repetitive access)
				if ( method_exists ( $data_object_array[$column], 'valid_values' ) ) { 	// select/multiselect fields			
					$valid_values[$column] = $data_object_array[$column]->valid_values();
				}  
			}
		}		

		// get a chunk of records to validate
		$validation_parameters = json_decode ( stripslashes( $validation_parameters ) ) ;
		$record_object_array = WIC_DB_Access_Upload::get_staging_table_records(  
			$validation_parameters->staging_table, 
			$validation_parameters->offset ,  
			$validation_parameters->chunk_size,
			'*' 
		);		
		
		// loop through records, use the controls to sanitize and validate each and update each with results
		foreach( $record_object_array as $record ) {
			$errors = '';
			$update_clause_array = array();
			foreach ( $data_object_array as $column => $control ) {
				// ignore empty columns on any record
				if ( $record->$column > '' ) { 				
					$control->set_value ( $record->$column );
					// since will be testing for specified valid values and have already escaped,
					// content not to sanitize multiselect ( which is expecting an array value for sanitization )
					if ( 'multiselect' != $control->get_control_type() ) {
						$control->sanitize();
					}
					$error = $control->validate();
					// do additional validation for sanitization (e.g., date) that reduces input to empty
					if ( '' < $record->$column && '' == $control->get_value() ) {
						$error = sprintf ( __( 'Invalid entry for %s -- %s.', 'wp-issues-crm' ), $column_map->$column->field, $record->$column ); 					
					}
					// validate select fields -- assure that value in options set (whether in options table or function generated per control logic)
					if ( method_exists ( $control, 'valid_values' ) ) { 
						if ( ! in_array ( $record->$column, $valid_values[$column] ) ) {
							$error = sprintf ( __( 'Invalid entry for %s -- %s.', 'wp-issues-crm' ), $column_map->$column->field, $record->$column ); 						
						}					
					}
					$column_map->$column->non_empty_count++;
					// validate based on individual column's error or lack of
					if ( '' == $error ) {
						$column_map->$column->valid_count++;
						// if no error and valid, update staging table with sanitized value
						$update_clause_array[] = array (
							'column' => $column,
							'value'	=> $control->get_value(),						
						);					
					}
					// accumulate all errors across columns for record; note that empty is not an error
					$errors .= $error;
				}
			}
			// this parallels update process for forms, but is distinct since only updating the staging table -- can't use same functions
			$result = WIC_DB_Access_Upload::record_validation_results( $update_clause_array, $validation_parameters->staging_table, $record->STAGING_TABLE_ID, $errors );
			if ( ! $result ) {
				wp_die( sprintf( __( 'Error recording validation results for record %s', 'wp-issues-crm' ), $record->STAGING_TABLE_ID ) );
			}
		}
		// update the column map with the counts
		WIC_DB_Access_Upload::update_column_map ( $upload_id, json_encode ( $column_map ) );

		$table = self::prepare_validation_results ( $column_map );
		echo  json_encode ( $table );
		wp_die();	
	}

	public static function update_upload_status( $upload_id, $status ) { 
		$result = WIC_DB_Access_Upload::update_upload_status ( $upload_id, json_decode ( stripslashes( $status ) ) ) ; 
		if ( 1 == $result ) {
			$msg = __( 'Status update OK.', 'wp-issues-crm' );		
		} else {
			$msg = __( 'Error setting upload status.', 'wp-issues-crm' );	
		}
		return ( $result = json_encode ( $msg ) );
	}
	
	public static function prepare_validation_results ( $column_map ) {
				
		$table =  '<table id="wp-issues-crm-stats"><tr>' .
			'<th class = "wic-statistic-text">' . __( 'File Column', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic-text">' . __( 'Mapped to Entity', 'wp-issues-crm' ) . '</th>' .					
			'<th class = "wic-statistic-text">' . __( 'Mapped to Field', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'Non-empty Count', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'Valid Count', 'wp-issues-crm' ) . '</th>' .
		'</tr>';

		foreach ( $column_map as $column => $entity_field_object ) { 
			if ( $entity_field_object > '' ) {
				$table .= '<tr>' .
					'<td class = "wic-statistic-table-name">' . $column . '</td>' .
					'<td class = "wic-statistic-text" >' . $entity_field_object->entity . '</td>' .
					'<td class = "wic-statistic-text" >' . $entity_field_object->field . '</td>' .
					'<td class = "wic-statistic" >' . $entity_field_object->non_empty_count . '</td>' .
					'<td class = "wic-statistic" >' . $entity_field_object->valid_count  . '</td>' .
				'</tr>';
			}
		}
		
		$table .= '</table>';	
	
		return ( $table );

	}	
	
	/*
	*
	* functions to support matching
	*
	*/	

	// reset_match also initializes match_results if not previously matched 
	public static function reset_match ( $upload_id, $data  ) {
		
		$data = json_decode ( stripslashes( $data ) );
		// reset counts in column map
		$match_results = json_decode ( WIC_DB_Access_Upload::get_match_results ( $upload_id ) );
		foreach ( $match_results as $slug=>$match ) {
			$match->order = 0;
			$match->total_count = 0;
			$match->have_components_count = 0;
			$match->have_components_and_valid_count = 0;
			$match->have_components_not_previously_matched = 0;
			$match->matched_with_these_components = 0;
			$match->not_found = 0;
			$match->not_unique = 0;						
			$match->unmatched_unique_values_of_components = '?';
		}
		
		// capture user decisions about which match strategies to use and in what order
		$order_counter = 0;
		foreach ( $data->usedMatch as $slug ) {
			$order_counter++; // don't start at 0 is 0 means not used
			$match_results->$slug->order = $order_counter;		
		}		

		// save fresh array ready to get started		
		WIC_DB_Access_Upload::update_match_results ( $upload_id, json_encode ( $match_results ) );

		// status has to be validated or matched to start.  
		// in case already matched, bust back to validated so that if match fails midstream, completion routines won't accept it
		WIC_DB_Access_Upload::update_upload_status ( $upload_id, 'validated' );

		// reset validation indicators on staging table
		$table = $data->table;
		$result = WIC_DB_Access_Upload::reset_staging_table_match_indicators( $table );
		if ( $result ) {
			wp_die( json_encode ( __( 'Staging table match indicators reset.', 'wp-issues-crm' ) ) );
		} else {
			// send errors not encoded, so will generate alert on return
			wp_die ( __( 'Error resetting staging table match indicators', 'wp-issues-crm' ) );		
		}
		
	}

	/*
	*
	* match_upload answers AJAX call to test match a chunk of staging table records
	* does database lookups and records interim results
	* returns updated result table
	*
	*
	*/
	public static function match_upload ( $upload_id, $match_parameters ) {
	
		// get the current staging row		
		$match_results = json_decode (  WIC_DB_Access_Upload::get_match_results ( $upload_id ) ) ;
		$match_parameters = json_decode ( stripslashes( $match_parameters ) );
		$match_rule = $match_results->{$match_parameters->working_pass};
		$match_fields_array = $match_rule->link_fields;

		// get the column to database field map for this upload
		$column_map = json_decode ( WIC_DB_Access_Upload::get_column_map ( $upload_id ) );		

		// set up an array of the columns being used to create staging table retrieval sql and minimize size of retrieval array
		$column_list_array = array();		
		// look up match fields in column array to get back to column, add to match field array
		foreach ( $match_fields_array as &$match_field ) { // passing by pointer so directly modify array element
			foreach ( $column_map as $column => $entity_field_object ) {
				if ( '' < $entity_field_object ) { // unmapped columns have an empty entity_field_object
					if ( $match_field[0] == $entity_field_object->entity && $match_field[1] ==  $entity_field_object->field ) {
						$match_field[3] = $column;
						$column_list_array[] = $column;	
					}
				}		
			}
		}  	

		unset ( $match_field ); // this is critical -- surprising results results in for loop further below if not done; 
										// see http://php.net/manual/en/control-structures.foreach.php -- reference remains after loop
		$column_list = implode ( ',', $column_list_array );		
		// get a chunk of records to validate
		$record_object_array = WIC_DB_Access_Upload::get_staging_table_records(  
			$match_parameters->staging_table, 
			$match_parameters->offset,  
			$match_parameters->chunk_size, 
			$column_list
		);		
		
		// create a constituent db object for repetitive use in the look up loop
		$wic_query = WIC_DB_Access_Factory::make_a_db_access_object( 'constituent' ); 

		// define consistent search parameters for use with lookups
		$search_parameters = array (
			'select_mode' 		=> 'id', 	// only want id back
			'sort_order' 		=> false, 	// don't care for sort
			'compute_total' 	=> false,	// no need to find total of all dups	
			'retrieve_limit'	=> 2,			// one dup is too many
			'show_deleted'		=> true, 	// match deleted records
			'log_search' 		=> false,	// don't log the searches
		);


		// loop through records, if have necessary fields, look for match using the standard query construct
		foreach( $record_object_array as $record ) {
			// reinitialize meta query array
			$meta_query_array = array();
			// keep overall tally -- should be same in all passes
			$match_rule->total_count++;
			// necessary values present (otherwise store temporarily in position 4)
			$missing = false;

			foreach ( $match_fields_array as $match_field ) { 	
				if ( ''   ==  $record->$match_field[3] ) {
					$missing = true;
					break;
				} else {
					$meta_query_array[] = array (
						'table'	=> $match_field[0],
						'key' 	=> $match_field[1],
						'value'	=> 0 == $match_field[2] ? $record->$match_field[3] : substr ( $record->$match_field[3], 0, $match_field[2] ),
						'compare'=> 0 == $match_field[2] ? '=' : 'like',
						'wp_query_parameter' => '',
					);					
				}			
			}	
	
			if ( ! $missing ) {
				$match_rule->have_components_count++; // $match_field[4] fully populated for all match fields
			} else {
				continue;			
			}			
			// valid?
			if ( 'y' == $record->VALIDATION_STATUS ) {
				$match_rule->have_components_and_valid_count++;			
			} else {
				continue;			
			}	
			// not already matched?
			if ( '' == $record->MATCH_PASS ) {
				$match_rule->have_components_not_previously_matched++;			
			} else {
				continue;			
			}	 		
			// construct sql from link fields, do lookup field 
			$wic_query->search ( $meta_query_array, $search_parameters );
			// now, maintain pass tallies and record outcome on staging table
			// first, initialize match_pass found variables -- populated below only in case of found match
			// and not previously populated (don't reach these lines at all if previously populated)			
			$match_pass = '';
			$matched_constituent_id = 0;
			// initialize $not_found_match_pass recording variables -- these are populated below only in case of
			// not_found match and not already populated -- so, it holds the first (should be most unique) pass in which
			// all necessary variables were present for matching and the values of those variables -- 
			// a subsequent less unique pass may result in a match in which case these won't be used, but if no match in any 
			// pass, these will be used to create constituent stub for insertion in final upload completion stage
			$first_not_found_match_pass 	= '';
			$not_found_values					= ''; 	
			// not found case -- populate not found pass and values
			if ( 1 > $wic_query->found_count ) { // i.e, found count = 0
				$match_rule->not_found++;	
				if ( '' == $record->FIRST_NOT_FOUND_MATCH_PASS  ) {
					$first_not_found_match_pass = $match_parameters->working_pass;
					foreach ( $match_fields_array as $match_field ) {
						$not_found_values .= ( 0 == $match_field[2] ) ? 
							$record->$match_field[3] : substr ( $record->$match_field[3], 0, $match_field[2] );
					}	 				
				} 
			// found multi case -- populate match pass, but leave matched_id as 0; 
			} elseif ( 1 < $wic_query->found_count ) {
				$match_rule->not_unique++;
				$match_pass = $match_parameters->working_pass;
				$matched_constituent_id = 0;
			// found unique case -- populate match pass (which means no matching will be attempted on future passes) and set matched id
			} elseif ( 1 == $wic_query->found_count ) {
				$match_rule->matched_with_these_components++;
				$match_pass = $match_parameters->working_pass;
				$matched_constituent_id = $wic_query->result[0]->ID;
			}
			// mark staging table with outcome if there was match in this pass; 
			// stamping with match_pass indicates that there was a match; 
			// stamping the match_id indicates that the match was unique
			if ( '' < $match_pass || '' < $first_not_found_match_pass ) {
				// mark staging table record to record match outcome
				// returns error unless exactly one of $match_pass and $first_not_found_match_pass > ''
				$result = WIC_DB_Access_Upload::record_match_results( 
					$match_parameters->staging_table, 
					$record->STAGING_TABLE_ID,
					$match_pass,
					$matched_constituent_id,
					$first_not_found_match_pass,
					$not_found_values 
					);
				if ( false === $result ) {
					wp_die( sprintf( __( 'Error recording match results for record %s', 'wp-issues-crm' ), $record->STAGING_TABLE_ID ) );
				}
			}
		}
		// update the match_results array with the counts
		$match_results->{$match_parameters->working_pass} = $match_rule;
		// save the match results array
		WIC_DB_Access_Upload::update_match_results ( $upload_id, json_encode ( $match_results ) );

		$table = self::prepare_match_results ( $match_results );
		echo  json_encode ( $table );
		wp_die();
			
	}

	public static function create_unique_unmatched_table ( $upload_id, $match_parameters ) {
		$match_parameters = json_decode ( stripslashes( $match_parameters ) );
		$staging_table = $match_parameters->staging_table;
		$result = WIC_DB_Access_Upload::create_unique_unmatched_table ( $upload_id, $staging_table );
		if ( false === $result ) {
			wp_die( __( 'Error creating unique values table.', 'wp-issues-crm' ) );
		} else {
			echo json_encode ( self::prepare_match_results ( $result ) ); // updated table
			wp_die(); 
		} 
		
	}

	public static function prepare_match_results ( $match_results ) {
		
		// extract the active match rules
		$active_match_rules = array();
		foreach ( $match_results as $slug => $match_object ) {
			if ( 0 < $match_object->order ) {
				$active_match_rules[$match_object->order]	= $match_object;		
			}		
		}
		ksort ( $active_match_rules );
						
		$table =  '<table id="wp-issues-crm-stats">' .
		'<tr><td></td>	<th class = "wic-statistic-text" colspan="4">' . __( 'Pass input', 'wp-issues-crm' ) . '</th>' .
							'<th class = "wic-statistic-text" colspan="3">' . __( 'Pass results', 'wp-issues-crm' ) . '</th>' .	
							'<th class = "wic-statistic-text" >Unmatched</th></tr>' .
		'<tr>' .
			'<th class = "wic-statistic-text wic-statistic-long">' . __( 'Match Pass', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'Records', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'With data', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( ' ... also valid', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( ' ... also not matched', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'Matched unique', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'Not found', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'Not unique', 'wp-issues-crm' ) . '</th>' .
			'<th class = "wic-statistic">' . __( 'Unique values', 'wp-issues-crm' ) . '</th>' .
		'</tr>';

		$total_matched = 0;
		$total_not_unique = 0;
		$total_unique_unmatched_values = 0;

		foreach ( $active_match_rules as $order => $upload_match_object ) { 
			$table .= '<tr>' .
				'<td class = "wic-statistic-table-name">' . $upload_match_object->label . '</td>' .
				'<td class = "wic-statistic" >' . $upload_match_object->total_count  . '</td>' .
				'<td class = "wic-statistic" >' . $upload_match_object->have_components_count  . '</td>' .
				'<td class = "wic-statistic" >' . $upload_match_object->have_components_and_valid_count  . '</td>' .			
				'<td class = "wic-statistic" >' . $upload_match_object->have_components_not_previously_matched  . '</td>' .
				'<td class = "wic-statistic" >' . $upload_match_object->matched_with_these_components  . '</td>' .
				'<td class = "wic-statistic" >' . $upload_match_object->not_found  . '</td>' .
				'<td class = "wic-statistic" >' . $upload_match_object->not_unique  . '</td>' .
				'<td class = "wic-statistic" >' . $upload_match_object->unmatched_unique_values_of_components  . '</td>' .
			'</tr>';
			
			$total_matched += $upload_match_object->matched_with_these_components;
			$total_not_unique += $upload_match_object->not_unique;
			if ( '?' !== $upload_match_object->unmatched_unique_values_of_components ) {
				$total_unique_unmatched_values += $upload_match_object->unmatched_unique_values_of_components;
			} else {
				$total_unique_unmatched_values = '<em>?</em>';
			}
		}
		$table .= 	'<tr>' .
						'<td class = "wic-statistic-table-name">' . __( 'Total, all passes:', 'wp-issues-crm' ) . '</td>' .
						'<td  colspan="4"></td>' .
						'<td class = "wic-statistic" >' . $total_matched . '</td>' . 
						'<td class = "wic-statistic" ></td>' .
						'<td class = "wic-statistic" >' . $total_not_unique . '</td>' .
						'<td class = "wic-statistic" >' . $total_unique_unmatched_values . '</td>' . 
						'<tr>' ;						
		$table .= '</table>';	
	
		return ( $table );

	}	


	
}
