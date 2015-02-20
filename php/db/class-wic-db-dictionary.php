<?php
/*
* class-wic-db-dictionary.php
*
*
*
*  This script provides access to the WIC data dictionary.
* 
*  The first two methods are used in assembling the data_object_array for entities.  They load all field
*		rules into the controls in the array.
*
*	Outside those controls, no routines access the dictionary for information about individual fields.  There are, however, select occasions where
*		it is convenient to query across all fields for certain properties.  These are limited to:
*			+ like_search_enabled, dup_check and required solely for the purpose of formatting of form legend creation or of error messages
*			+ sort_clause_order soley for the purpose of creating a sort string
*			+ sort_list_fields soley for the purpose supporting a shortened data object array for displaying a list
*			+ form fields only for the purposes of grouping
*
*		NB: all field properties are private to the control objects, but certain properties are disclosed in processing -- see wic-control classes. 
*
*	Conversion to using field_rules_cache, instead of sql queries for the repetitive queries did cut page assembly times.
*
*/

class WIC_DB_Dictionary {
	
	/*
	*	dictionary is initialized on start up by plug in
	*	construct initializes the rules and options caches
	*	almost all rules lookups are to these caches
	*
	*/
	private $field_rules_cache;
	private $option_values_cache;	
	
	public function __construct() {
		$this->initialize_field_rules_cache();
		$this->initialize_option_values_cache();
	}	

	// read field rules into class property that functions as cache
	private function initialize_field_rules_cache () {
		global $wpdb;

		$table = $wpdb->prefix . 'wic_data_dictionary';
		$this->field_rules_cache = $wpdb->get_results( 
				"
				SELECT * 
				FROM $table
				where enabled
				"				
			, OBJECT );
	}	
	
	// read option values into class property that functions as cache
	private function initialize_option_values_cache() {
		global $wpdb;
		
		$this->option_values_cache = array();		
		
		$table1 = $wpdb->prefix . 'wic_option_group';
		$table2 = $wpdb->prefix . 'wic_option_value';
		$option_groups = $wpdb->get_results( 
			"
			SELECT option_group_slug, 
				group_concat( option_value ORDER BY value_order DESC SEPARATOR '<!!>' ) AS option_values,
				group_concat( option_label ORDER BY value_order DESC SEPARATOR '<!!>' ) AS option_labels
			FROM $table1 g inner join $table2 v on g.id = v.option_group_id
			WHERE v.enabled and g.enabled
			GROUP BY option_group_slug
			ORDER BY option_group_slug
			"				
		, ARRAY_A );

		foreach( $option_groups as $option_group ) {
			$values = explode( '<!!>', $option_group['option_values'] );
			$labels = explode( '<!!>', $option_group['option_labels'] );
			$this->option_values_cache[$option_group['option_group_slug']] = array();
			while ( count( $values ) > 0 ) {
				$value = array_pop ( $values );
				$label = array_pop ( $labels );
				array_push ( $this->option_values_cache[$option_group['option_group_slug']], 
					array ( 'value' => $value,
							  'label' => $label
					)  
				);			
			}
		}
	}	
	
	/***********************************************************************
	*
	* method supporting option value groups -- return an array of option values
	*
	************************************************************************/	
	public function lookup_option_values ( $option_group ) {
		if ( isset ( $this->option_values_cache[$option_group] ) ) {
			return ( $this->option_values_cache[$option_group] );
		} else {
			return ( '' );		
		}	
	}	
	
	/*************************************************************************
	*	
	* Basic methods supporting setup of data object array for entities
	*
	**************************************************************************/	
	
	// assemble fields for an entity -- n.b. as rewritten, limits the assembly to fields assigned to form field groups
	// does not force groups to be implemented though, since not joining to groups table -- assignment to a 
	// 	non-existent group could lead to data corruption
	public  function get_form_fields ( $entity ) {
		// returns array of row objects, one for each field 
	
		$fields_array = array();
		foreach ( $this->field_rules_cache as $field_rule ) {
			if ( $entity == $field_rule->entity_slug && $field_rule->group_slug > '' ) {
				$fields_array[] = ( new WIC_DB_Field_List_Object ( $field_rule->field_slug, $field_rule->field_type, $field_rule->field_label, $field_rule->listing_order, $field_rule->list_formatter ) );			
			}		
		}
		
		return ( $fields_array );		
		
	}	

	// expose the rules for all fields for an entity -- only called in control initialization;
	// rules are passed to each control that is in the data object array directly -- no processing;
	// the set retrieved by this method is not limited to group members and might support a data dump function, 
	// 	but in the online system, only the fields selected by get_form_fields are actually set up as controls  
	public  function get_field_rules ( $entity, $field_slug ) {
		// this is only called in the control object -- only the control object knows field details

		foreach ( $this->field_rules_cache as $field_rule ) {
			if ( $entity == $field_rule->entity_slug && $field_slug == $field_rule->field_slug ) {
				return ( $field_rule );			
			}		
		}
		WIC_Function_Utilities::wic_error ( sprintf( ' Field rule table inconsistency -- entity (%1$s), field_slug (%2$s).',  $entity, $field_slug  ), __FILE__, __LINE__, __METHOD__, true );
	}

	/*************************************************************************
	*
	* Method supporting wp db interface
	*
	**************************************************************************/
	public  function get_field_list_with_wp_query_parameters( $entity ) {

		$entity_fields = array();
		foreach ( $this->field_rules_cache as $field_rule ) {
			if ( $entity == $field_rule->entity_slug ) {
				$entity_fields[$field_rule->field_slug] = $field_rule->wp_query_parameter;	
			}
		}	
		
		return $entity_fields;
	}	
	
	/*************************************************************************
	*	
	* Methods supporting list display -- sort order and shortened field list 
	*
	**************************************************************************/	

	// return string of fields for inclusion in sort clause for lists
	public  function get_sort_order_for_entity ( $entity ) {

		$sort_string = array();
		foreach ( $this->field_rules_cache as $field_rule ) {
			if ( $entity == $field_rule->entity_slug && $field_rule->sort_clause_order > 0 )  {
				$sort_string[$field_rule->sort_clause_order] = $field_rule->field_slug;
			}		
		}
		// note that ksort drops elements with identical sort_clause_order
		ksort( $sort_string );
		$sort_string_scalar = implode ( ',', $sort_string );
		
		return ( $sort_string_scalar );

	}

	// return short list of fields for inclusion in display in lists (always include id) 
	// also used in assembly of shortened data object array for lists
	public  function get_list_fields_for_entity ( $entity ) {
		
		// note: negative values for listing order will be included in field list for data retrieval and will be available for formatting
		// but will not be displayed in list
		$list_fields = array();
		foreach ( $this->field_rules_cache as $field_rule ) {
			if ( $entity == $field_rule->entity_slug && ( $field_rule->listing_order != 0 || 'ID' == $field_rule->field_slug ) ) {
				$list_fields[$field_rule->listing_order] = $field_rule;
			}		
		}
		
		// note that ksort drops elements with identical listing_order
		ksort ( $list_fields );
		
		$list_fields_sorted = array();
		foreach ( $list_fields as $key=>$field_rule ) {
			$list_fields_sorted[] = new WIC_DB_Field_List_Object ( $field_rule->field_slug, $field_rule->field_type, $field_rule->field_label, $field_rule->listing_order, $field_rule->list_formatter );  		
		} 

		return ( $list_fields_sorted );
		
	}	

	public function get_option_label ( $entity_slug, $field_slug, $value ) {
		// used in search log to display labels
		$option_group = '';

		foreach ( $this->field_rules_cache as $field_rule ) {
			if ( $entity_slug == $field_rule->entity_slug && $field_slug == $field_rule->field_slug ) {
				$option_group = $field_rule->option_group;
				break;			
			}		
		}
		
		$option_values = $this->lookup_option_values ( $option_group );
		if ( is_array ( $option_values ) ) {
			// could be empty if $option_group value is actually the string name of a lookup method or function
			return WIC_Function_Utilities::value_label_lookup( $value, $option_values );
		} else {
			return ( '' );		
		}
		 
	}
	
	
	
	

	/*************************************************************************
	*	
	* Basic methods supporting forms  
	*
	**************************************************************************/	
	
	// retrieve the groups for a form with their properties	
	public  function get_form_field_groups ( $entity ) {
		// this lists the form groups
		global $wpdb;
		$table = $wpdb->prefix . 'wic_form_field_groups';
		$groups = $wpdb->get_results( 
			$wpdb->prepare (
				"
				SELECT group_slug, group_label, group_legend, initial_open, sidebar_location
				FROM $table
				WHERE entity_slug = %s
				ORDER BY group_order
				"				
				, array ( $entity )
				)
			, OBJECT_K );
			
		return ( $groups );
	}

	// this just retrieves the list of fields in a form group 
	public  function get_fields_for_group ( $entity, $group ) {

		$fields = array();
		
		foreach ( $this->field_rules_cache as $field_rule ) {
			
			if ( $entity == $field_rule->entity_slug && $group == $field_rule->group_slug ) {
				$fields[$field_rule->field_order] = $field_rule->field_slug;			
			}
		}

		ksort( $fields, SORT_NUMERIC );
		
		return ( $fields );
	}

	
	/*************************************************************************
	*	
	* Special methods for assembling generic message strings across groups
	*   	-- these functions play no role in validation or any processing, 
	*		-- they only format info
	*
	**************************************************************************/
		
	// report presence of fields requiring legend display 
	public  function get_field_suffix_elements ( $entity ) {
		// this tabulates required and like properties across fields to 
		//	support determination of whether to display legends
		global $wpdb;
		$table1 = $wpdb->prefix . 'wic_data_dictionary';
		$table2 = $wpdb->prefix . 'wic_form_field_groups';
		$elements = $wpdb->get_results( 
		$wpdb->prepare (
				"
				SELECT max( like_search_enabled ) as like_search_enabled,
					max( if ( required = 'group', 1, 0 ) ) as required_group , 
					max( if ( required = 'individual', 1, 0 ) ) as required_individual
				FROM $table1 t1 inner join $table2 t2 on t1.entity_slug = t2.entity_slug and t1.group_slug = t2.group_slug
				WHERE t1.entity_slug = %s and t1.enabled
				ORDER BY field_order
				"				
				, array ( $entity )
				)
			, OBJECT_K );
		return ( $elements );
	}

	// return string of dup check fields for inclusion in error message
	public  function get_dup_check_string ( $entity ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wic_data_dictionary';
		$dup_check_string = $wpdb->get_row( 
			$wpdb->prepare (
					"
					SELECT group_concat( field_label SEPARATOR ', ' ) AS dup_check_string
					FROM $table 
					WHERE entity_slug = %s and dedup = 1 and enabled
					"				
					, array ( $entity )
					)
				, OBJECT );
	
		return ( trim( $dup_check_string->dup_check_string, "," ) ); 
	}

	// return string of required fields for required error message
	public  function get_required_string ( $entity, $type ) {
		
		$required_string = array();
		foreach ( $this->field_rules_cache as $field_rule ) {
			if ( $entity == $field_rule->entity_slug && $type == $field_rule->required )  {
				$required_string[] = $field_rule->field_label;
			}		
		}
		$required_string_scalar = implode ( ', ', $required_string );
		
		return ( $required_string_scalar );	
		
	}	
	
	public  function get_match_type_string ( $entity, $type ) {
		
		$match_type_string = array();
		foreach ( $this->field_rules_cache as $field_rule ) {
			if ( $entity == $field_rule->entity_slug && $type == $field_rule->like_search_enabled )  {
				$match_type_string[] = $field_rule->field_label;
			}	
			if ( $entity == $field_rule->entity_slug && 'multivalue' == $field_rule->field_type )  {
				foreach ( $this->field_rules_cache as $field_rule2 ) {
					if ( $field_rule->field_slug == $field_rule2->entity_slug && $type == $field_rule2->like_search_enabled )  {
						$match_type_string[] = $field_rule2->field_label;
					}
				}
			}	
		}
		$match_type_string_scalar = implode ( ', ', $match_type_string );
		
		return ( $match_type_string_scalar );	
		
	}	

}