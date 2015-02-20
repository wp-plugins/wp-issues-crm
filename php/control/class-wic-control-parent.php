<?php
/*
* class-wic-control-parent.php
*
* WIC_Control_Parent is extended by classes for each of the field types  
* 
* Multivalue is the most significant extension -- from the perspective of the top form,
* a multivalue field like address (which includes multiple rows with multiple fields in each)
* is just another control like first name.  
*
*
*
*/

/************************************************************************************
*
*  WIC Control Parent
*
************************************************************************************/
abstract class WIC_Control_Parent {
	protected $field;
	protected $default_control_args = array();
	protected $value = '';	


	/***
	*
	*	The control create functions are a little promiscuous in that they gather their control arguments from multiple places.
	*		Field rules from database on initialization  
	*		Rules specified in the named control function (search_control, update_control) 
	*		In child controls, may allow direct passage of arguments -- see checked and multivalue.
	*		Note that have potential to get css specified to them based on their field slug
	*		Any special validation, sanitization, formatting and default values ( as opposed to default rule values ) are supplied from the relevant object and the dictionary
	*/


	public function initialize_default_values ( $entity, $field_slug, $instance ) {

		global $wic_db_dictionary;

	// initialize the default values of field RULES  
		$this->field = $wic_db_dictionary->get_field_rules( $entity, $field_slug );
		$this->default_control_args =  array_merge( $this->default_control_args, get_object_vars ( $this->field ) );
		$this->default_control_args['input_class'] 			= 'wic-input';
		$this->default_control_args['label_class'] 			= 'wic-label';
		$this->default_control_args['field_slug_css'] 		= str_replace( '_', '-', $field_slug );
		$this->default_control_args['field_slug_stable'] 	= $field_slug; 
		// retain this value arg so don't need to parse out instance in static create control function where don't have $this->field->field_slug to refer to
		$this->default_control_args['field_slug'] = ( '' == $instance ) ? // ternary
				// if no instance supplied, this is just a field in a main form, and use field slug for field name and field id
				$field_slug :
				// if an instance is supplied prepare to output the field as an array element, i.e., a row in a multivalue field 
				// note that the entity name for a row object in a multivalue field is the same as the field_slug for the multivalue field
				// this is a trap for the unwary in setting up the dictionary table 
				$entity . '[' . $instance . ']['. $field_slug . ']';
		// initialize the value of the control
		$this->reset_value();		
	}

	/*********************************************************************************
	*
	* methods for control creation for different types of forms -- new, search, save, update
	*
	***********************************************************************************/
	
	public function set_value ( $value ) {
		$this->value = $value;	
	}
	
	public function get_value () {
		return $this->value;	
	}
	
	public function reset_value() {
		$this->value = '';	
	}

	public function get_wp_query_parameter() {
		return ( $this->field->wp_query_parameter );	
	}

	/**********************************************************************************
	*
	* get default value for field itself
	*
	***********************************************************************************/
	protected function get_default_value() {
		$default = $this->value;
		$field_default = $this->field->field_default; // move string to single variable to allow execution as function
		// if there is a non-empty field_default value for the field in the data dictionary
		if ( $field_default > '' ) {
			// first look for a wp-issues-crm function
			if ( method_exists ( 'WIC_Function_Utilities', $field_default ) ) { 
				$default = WIC_Function_Utilities::$field_default ();			
			// second look for a function in global name space  ( could be in theme or child theme's function.php )
			} elseif ( function_exists ( $field_default ) ) {
				$default = $field_default(); 
			// if not a method or function, take it to be a string
			} else {
				$default = $field_default;
			}
		// if no field_default value, will be returning just the initialized value of the control 
		}
		return ( $default );
	}

	/*********************************************************************************
	*
	* methods for control creation for different types of forms -- new, search, save, update
	*
	***********************************************************************************/
	
	public static function new_control () {
		$this->search_control();
	}

	public function search_control () {
		$final_control_args = $this->default_control_args;
		$final_control_args['readonly'] = false;
		$final_control_args['value'] = $this->value;
		$control =  static::create_control( $final_control_args ) ;
		return ( $control ) ;
	}
	
	public function save_control () {
		$final_control_args = $this->default_control_args;
		if( ! $final_control_args['readonly'] ) {
			$final_control_args['value'] = $this->get_default_value();
			return  ( static::create_control( $final_control_args ) );	
		}
	}
	
	public function update_control () { 
		$final_control_args = $this->default_control_args;
		$final_control_args['value'] = $this->value;
		return ( static::create_control( $final_control_args )  );	
	}

	protected static function create_control ( $control_args ) { // basic create text control, accessed through control methodsabove

		extract ( $control_args, EXTR_OVERWRITE );  
		
		$value = ( '0000-00-00' == $value ) ? '' : $value; // don't show date fields with non values; 
		
     	$class_name = 'WIC_Entity_' . $entity_slug; 
		$formatter = $list_formatter; // ( field slug has instance args in it )
		if ( method_exists ( $class_name, $formatter ) ) { 
			$value = $class_name::$formatter ( $value );
		} elseif ( function_exists ( $formatter ) ) {
			$value = $formatter ( $value );		
		}

		$readonly = $readonly ? 'readonly' : '';
		$type = ( 1 == $hidden ) ? 'hidden' : 'text';
		 
		$control = ( $field_label > '' && ! ( 1 == $hidden ) ) ? '<label class="' . esc_attr ( $label_class ) .
				 ' ' . esc_attr( $field_slug_css ) . '" for="' . esc_attr( $field_slug ) . '">' . esc_html( $field_label ) . '</label>' : '' ;
		$control .= '<input class="' . esc_attr( $input_class ) . ' ' .  esc_attr( $field_slug_css ) . '" id="' . esc_attr( $field_slug )  . 
			'" name="' . esc_attr( $field_slug ) . '" type="' . $type . '" placeholder = "' .
			 esc_attr( $placeholder ) . '" value="' . esc_attr ( $value ) . '" ' . $readonly  . '/>'; 
			
		return ( $control );

	}


	/*********************************************************************************
	*
	* control sanitize -- will handle all including multiple values -- generic case is string
	*
	*********************************************************************************/

	public function sanitize() {  
		$class_name = 'WIC_Entity_' . $this->field->entity_slug;
		$sanitizor = $this->field->field_slug . '_sanitizor';
		if ( method_exists ( $class_name, $sanitizor ) ) { 
			$this->value = $class_name::$sanitizor ( $this->value );
		} else { 
			$this->value = sanitize_text_field ( stripslashes ( $this->value ) );		
		} 
	}

	/*********************************************************************************
	*
	* control validate -- will handle all including multiple values -- generic case is string
	*
	*********************************************************************************/

	public function validate() { 
		$validation_error = '';
		$class_name = 'WIC_Entity_' . $this->field->entity_slug;
		$validator = $this->field->field_slug . '_validator';
		if ( method_exists ( $class_name, $validator ) ) { 
			$validation_error = $class_name::$validator ( $this->value );
		}
		return $validation_error;
	}

	/*********************************************************************************
	*
	* report whether field should be included in deduping.
	*
	*********************************************************************************/


	public function dup_check() {
		return $this->field->dedup;	
	}
	/*********************************************************************************
	*
	* report whether field is transient
	*
	*********************************************************************************/


	public function is_transient() {
		return ( $this->field->transient );	
	}

	
	
	
	/*********************************************************************************
	*
	* report whether field is multivalue
	*
	*********************************************************************************/


	public function is_multivalue() {
		return ( $this->field->field_type == 'multivalue' );	
	}

	/*********************************************************************************
	*
	* report whether field fails individual requirement
	*
	*********************************************************************************/
	public function required_check() { 
		if ( "individual" == $this->field->required && ! $this->is_present() ) {
			return ( sprintf ( __( ' %s is required. ', 'wp-issues-crm' ), $this->field->field_label ) ) ;		
		} else {
			return '';		
		}	
	}

	/*********************************************************************************
	*
	* report whether field is present as possibly required -- note that is not trivial for multivalued
	*
	*********************************************************************************/
	public function is_present() {
		$present = ( '' < $this->value ); 
		return $present;		
	}
	
	/*********************************************************************************
	*
	* report whether field is required in a group 
	*
	*********************************************************************************/
	public function is_group_required() {
		$group_required = ( 'group' ==  $this->field->required ); 
		return $group_required;		
	}


	/*********************************************************************************
	*
	* create where/join clause components for control elements in generic wp form 
	*
	*********************************************************************************/

	public function create_search_clause ( $search_clause_args ) {
		
		// expecting $match_level and $dup_check, but want errors if not supplied, so no defaults
		// match level is 0 for strict, 1 for like, 2 for soundex like
		// dedup is true or false	
		// added $category_search_mode == '' if not set on screen, but see WIC_DB_Access_WP for allowed values	
		
		extract ( $search_clause_args, EXTR_OVERWRITE );
		
		if ( ! isset( $match_level ) || ! isset ( $dup_check ) ) {
			WIC_Function_Utilities::wic_error ( sprintf ( 'Missing parameters for WIC_Control_Parent::create_search_clause() for %1$s.', $this->field->field_slug ) , __FILE__, __LINE__, __METHOD__, false );
		}
		
		if ( '' == $this->value || 1 == $this->field->transient ) {
			return ('');		
		}
		if ( 0 == $match_level || $dup_check || 0 == $this->field->like_search_enabled )  {
			$compare = '=';
			$key = $this->field->field_slug;							
		} elseif ( 1 == $match_level || ( 1 == $this->field->like_search_enabled )) {
			// at this test $match_level and like_search_enabled both are either 1 or 2 (since 0's in either fall in to prior test)
			// selecting three out of the 4 possibilities in that 2x2 possibility table (1-1, 1-2, 2-1)
			$compare = 'like';
			$key 	= $this->field->field_slug;	
		} elseif ( 2 == $match_level && 2 == $this->field->like_search_enabled ) {
			// handles only the remaining possibility 
			$compare = 'sound';
			$key 	= $this->field->field_slug . '_soundex';	
		} else {
			// cannot reach here unless there are bad values in the data dictionary
			WIC_Function_Utilities::wic_error ( sprintf ( 'Incorrect match_level settings for field %1$s.', $this->field->field_slug ) , __FILE__, __LINE__, __METHOD__, false );		
		}	
		
		if ( 'cat' == $this->field->wp_query_parameter && '' < $category_search_mode ) {
			$compare = $category_search_mode; // this will actually be parsed in as a $query argument with = as compare 		
		} 			
 
		$query_clause =  array ( // double layer array to standardize a return that allows multivalue fields
				array (
					'table'	=> $this->field->entity_slug,
					'key' 	=> $key,
					'value'	=> $this->value,
					'compare'=> $compare,
					'wp_query_parameter' => $this->field->wp_query_parameter,
				)
			);
		
		// filter to alter search for particular field types	
		$query_clause = $this->special_search_filter( $query_clause );	
		
		return ( $query_clause );
	}
	
	/*********************************************************************************
	*
	* create set array or sql statements for saves/updates 
	*
	*********************************************************************************/
	public function create_update_clause () {
		if ( ( ( ! $this->field->transient ) && ( ! $this->field->readonly ) ) || 'ID' == $this->field->field_slug ) {
			// exclude transient and readonly fields.   ID as readonly ( to allow search by ID), but need to pass it anyway.
			// ID is a where condition on an update in WIC_DB_Access_WIC::db_update
			$update_clause = array (
					'key' 	=> $this->field->field_slug,
					'value'	=> $this->value,
					'wp_query_parameter' => $this->field->wp_query_parameter,
					'soundex_enabled' => ( 2 == $this->field->like_search_enabled ),
			);
			
			// filter to alter search for particular field types	
			$update_clause = $this->special_update_filter( $update_clause );				
		
			return ( $update_clause );
		}
	}
	
	// blank filter functions to be overlaid in extensions
	protected function special_search_filter ( $search_clause ) {
		return ( $search_clause );	
	}

	protected function special_update_filter ( $update_clause ) {
		return ( $update_clause );	
	}

}
