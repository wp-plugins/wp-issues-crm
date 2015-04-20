<?php
/*
*
* class-wic-entity-upload-match-strategies.php
*
*
* 
*/

 class WIC_Entity_Upload_Match_Strategies {
	
	public function __construct() {}	
	
	// note that these options effectively define required fields for constituent entry when doing uploads
	// can also do with custom fields though
	private $recommended_match_array = array (

		'id' =>	array(
			'label'			=>	'WP Issues CRM Constituent ID',
			'link_fields'	=> array(
				array( 'constituent', 'ID', 0 ) // table, field, number of positions to match, 0 = all		
			),
		),
		'lnfndobaddr' => array(
			'label'			=>	'Last Name, First Name, Date of Birth, Street Address',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 0 ),
				array( 'constituent', 'first_name', 0 ),
				array( 'constituent', 'date_of_birth', 0 ),					
				array( 'address', 'address_line', 0 ),			
			),
		),
		'lnfndobcity' => array(
			'label'			=>	'Last Name, First Name, Date of Birth, City/Town',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 0 ),
				array( 'constituent', 'first_name', 0 ),
				array( 'constituent', 'date_of_birth', 0 ),					
				array( 'address', 'city', 0 ),			
			),
		),
		'lnfndob' => array(
			'label'			=>	'Last Name, First Name, Date of Birth',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 0 ),
				array( 'constituent', 'first_name', 0 ),
				array( 'constituent', 'date_of_birth', 0 ),					
			),
		),
		'emailfn' => array( 
			'label'			=>	'eMail Address, First Name',
			'link_fields'	=> array(
				array( 'email', 'email_address', 0 ),
			),
		),
		'email' => array( 
			'label'			=>	'eMail Address',
			'link_fields'	=> array(
				array( 'email', 'email_address', 0 ),
			),
		),
		'lnfnaddr'  => array( 
			'label'			=>	'Last Name, First Name, Street Address',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 0 ),
				array( 'constituent', 'first_name', 0 ),
				array( 'address', 'address_line', 0 ),					
			),
		),
		'lnfnaddr5'  => array( 
			'label'			=>	'Last Name, First Name, Street Address (First 5 Characters Only)',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 5 ),
				array( 'constituent', 'first_name', 5 ),
				array( 'address', 'address_line', 5 ),					
			),
		),	
		'lnfnzip'  => array( 
			'label'			=>	'Last Name, First Name, Zip',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 0 ),
				array( 'constituent', 'first_name', 0 ),
				array( 'address', 'zip', 0 ),					
			),
		),
		'lnfncity'  => array( 
			'label'			=>	'Last Name, First Name, City',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 0 ),
				array( 'constituent', 'first_name', 0 ),
				array( 'address', 'city', 0 ),					
			),
		),
		'lnfn'  => array( 
			'label'			=>	'Last Name, First Name',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 0 ),
				array( 'constituent', 'first_name', 0 ),
			),
		),			
		'lnfi' => array( 
			'label'			=>	'Last Name, First Initial',
			'link_fields'	=> array(
				array( 'constituent', 'last_name', 0 ),
				array( 'constituent', 'first_name', 1 ),
			),
		),			

	);
		
	
	function assemble_starting_match_array ( $upload_id ) { // this function is only used on match initialization -- i.e., status of upload is validation 

		global $wic_db_dictionary;

		// get array of custom fields		
		$custom_fields_match_array = $wic_db_dictionary->custom_fields_match_array(); 

		// merge with recommended_match		
		$all_fields_match_array = array_merge( $this->recommended_match_array, $custom_fields_match_array );
		 
		// get column map for this upload 
		$column_map =  json_decode( WIC_DB_Access_Upload::get_column_map( $upload_id ) ) ;		
		
		// invert column map to give array of database fields that are mapped to from the staging table
		// note that if constituent ID is among mapped fields, invert_column_map will only return ID
		// if ID is in the staging table, match only by that -- no need to handle a mixed case for that
		$targeted_database_fields = self::invert_column_map ( $column_map );
				
		// filter match array by available columns
		$doable_match_array = array();

		$count_used = 0; // counter for match array -- 
		foreach ( $all_fields_match_array as $slug => $match ) {
			$match_doable = true;
			foreach ( $match['link_fields'] as $link_field ) {
				$test_array = array ( $link_field[0], $link_field[1] );
				if ( ! in_array ( $test_array, $targeted_database_fields ) ) {
					$match_doable = false;
					break;				
				}			
			}
			// slot the doable items in with order of display indicator
			if ( $match_doable ) {
				$count_used++;
			 	 // arbitrarily set max used to 3 fields initially -- 0 values will end up aside
				$order = ( $count_used < 4 ) ? $count_used : 0;				
				$doable_match_array[$slug] = new WIC_DB_Upload_Match_Object ( $match['label'], $match['link_fields'], $order );
			}
		}	

		// note that the match strategy array is saved to the upload table, but is not in the dictionary -- never accessed in a form
		$result = WIC_DB_Access_Upload::update_match_results ( $upload_id, json_encode ( $doable_match_array ) );
		if ( false === $result ) {
			WIC_Function_Utilities::wic_error ( __( 'Unable to update match results for upload.' , 'wp_issues_crm' ), __FILE__, __LINE__, __METHOD__, true );
		} 		
		
		// returns an associative array of objects which will become an object of objects on json_decode since js does not support associative array
		return ( $doable_match_array );		

	}

	
	function layout_sortable_match_options( $upload_id, $new ) { // new is true or false -- creating new object or note.

		// get the doable match array/object	
		if ( $new ) {	
			$doable_match_object = $this->assemble_starting_match_array( $upload_id ); // array of objects $slug => match object
			if ( 0 == count ( $doable_match_object ) ) {
				return ( '' );			
			} 
		} else {
			$match_result_string = WIC_DB_Access_Upload::get_match_results ( $upload_id );
			if ( '[]' == $match_result_string ||  '{}' == $match_result_string ) { // empty array or object in json  
				return ( '' );
			} else {
				$doable_match_object = json_decode ( $match_result_string );		// object with properties $slug->match object
			}	
		}	
		
		// split array/object into two sets of li's
		$primary_items_array = array();
		$additional_items = ''; 
		foreach ( $doable_match_object as $slug => $match ) { // loop works same whether associative array (as on new) or object (as on returning)
			if ( 0 < $match->order ) {
				$primary_items_array[$match->order] = '<li class = "wic-match wic-sortable-item" id = "' . $slug . '">' . $match->label . '</li>';
			} else {
				$additional_items .= '<li class = "wic-match wic-sortable-item" id = "' . $slug . '">' . $match->label . '</li>';
			}
		}
		
		// sort the primary items li's by match order and convert them back to a string
		ksort ( $primary_items_array );
		$primary_items = implode ( '', $primary_items_array );					

		// set up ul's for each set
		$output =	'<div = "horbar-clear-fix"></div>';
		$output .=  '<div id = "wic-match-list"><h3>' . __( 'Prioritize combinations to match records with here.', 'wp-issues-crm' ) . '</h3>';
		$output .=	'<ul  class = "wic-sortable" >';
			$output .= $primary_items;
		$output .= '</ul></div>';
		
		$output .= '<div  id = "wic-unused-match-list"><h3>' . __( 'Place unused combinations here.', 'wp-issues-crm' ) . '</h3>';
		$output .= '<ul class = "wic-sortable" >';
				$output .= $additional_items;
		$output .= '</ul></div>';
		$output .=	'<div = "horbar-clear-fix"></div>';
		
		return ( $output );
	}

		/* 
		* 	function to invert column map
		*  also tests for mapping of ID
		*/
		public static function invert_column_map ( $column_map ) {

			$targeted_database_fields = array();

			$id_mapped = false;			
			foreach ( $column_map as $column => $entity_field_object ) {
				if ( '' < $entity_field_object ) { // unmapped columns have an empty entity_field_object
					$targeted_database_fields[] = array( $entity_field_object->entity, $entity_field_object->field );
					if ( 'constituent' == $entity_field_object->entity && 'ID' == $entity_field_object->field ) {
						$id_mapped = true;
						break;					
					}
				}		
			}

			// if ID is mapped ignore all other fields for matching purposes -- only present that match option
			if ( $id_mapped ) {
				$targeted_database_fields = array ( array ( 'constituent', 'ID' ) );			
			}	
			
			return ( $targeted_database_fields );	

		}

}

