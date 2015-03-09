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
			// if no button, then there is a get string
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
			'Upload Details' 	=> 'details', 		
			'Map Fields'		=> 'map',
			'Define Matching'	=>	'match',
			'Complete Upload'	=>	'complete',
		);			
		
		$active_tab = isset( $_GET[ 'action' ] )  ? $_GET[ 'action' ] : 'details';		
		
		$output = '<div id = "upload-tabs-headers"><ul class = "upload-tabs-headers">';
		    	foreach ( $tab_titles_array as $tab_title => $tab_link ) {
		    		$nav_tab_active = ( $active_tab == $tab_link ) ? 'nav-tab-active' : 'nav-tab-inactive';
					$output .= '<li class="' . $nav_tab_active . '">
							<a href="/wp-admin/admin.php?page=wp-issues-crm-uploads&action=' . $tab_link . '&upload_id=' . $upload_id . '"> '. 
						esc_html( trim( $tab_title ) )  .'</a></li>';
   			
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
		$this->id_search_generic ( $id, 'WIC_Form_Upload_Update', '' , false, false ); // no sql, but do log search as individual, no old search
		return;		
	}	
	
	protected function map ( $args ) {
		$id = $args['id_requested']; 
		$this->id_search_generic ( $id, 'WIC_Form_Upload_Map', '' , false, false ); // no sql, but do log search as individual, no old search
		$map_form = new WIC_Form_Upload_Map( $args ); 
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
		
		// if no form errors and basic file tests are OK, test readability of upload file
		if ( '' == $validation_errors && isset ( $_FILES[$file_name] ) ) { // do additional validation only if passed basic and have file

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
		}		
	}	
	
	protected function details ( $args ) {
		$this->id_search( $args );
	}	
	
	protected function match ( $args ) {
		echo self::format_tab_titles( $_GET['upload_id'] );
		echo '<h3>here goes the match stuff</h3>';	
	}
	
	protected function complete ( $args ) {
		echo self::format_tab_titles( $_GET['upload_id'] );
		echo '<h3>here goes the complete stuff</h3>';	
	}
	
	public static function get_column_map( $upload_id ) {
		$column_map = json_encode ( unserialize ( WIC_DB_Access_Upload::get_column_map( $upload_id ) ) ) ;
		echo $column_map;
		wp_die();
	}

	public static function update_column_map( $upload_id, $column_map  ) {
		// strip slashes, decode and serialize the column map and save it in the database
		$outcome = WIC_DB_Access_Upload::update_column_map( $upload_id , serialize( json_decode ( stripslashes ( $column_map ) ) ) );
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
	
	
	// response to json post	
	public static function remap_columns( $wic_data ) {
		
		$wic_data = json_decode ( stripslashes ( $wic_data ) ) ;

		$product = 1;
		foreach ( $wic_data as $datum ) {
       $product = $datum * $product;
      }
		echo json_encode ( $product );		
		wp_die();
	}
	
}
