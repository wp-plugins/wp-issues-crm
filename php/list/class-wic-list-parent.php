<?php
/*
* class-wic-list-parent.php
*
* lists entities (posts or WIC entities) passed as query 
*
*/ 

abstract class WIC_List_Parent {


	// header message, e.g., for count	
	protected abstract function format_message( &$wic_query, $header = '' ); // $header is text that will lead the message.
	// actual row content
	protected abstract function format_rows ( &$wic_query, &$fields );
	
	/*
	*
	* main function -- takes query result and sets up a list each row of which is a button
	*
	*/	
	public function format_entity_list( &$wic_query, $header ) { 

		global $wic_db_dictionary;

  		// set up form
		$output = '<div id="wic-post-list"><form method="POST">' . 
			'<div class = "wic-post-field-group wic-group-odd">';

		$message = $this->format_message ( $wic_query, $header ); 
		$output .= '<div id="post-form-message-box" class = "wic-form-routine-guidance" >' . esc_html( $message ) . '</div>';
		$output .=  $this->get_the_buttons( $wic_query );	

		// set up args for use in row buttons -- each row is a button
  		$list_button_args = array(
			'entity_requested'		=> $wic_query->entity,
			'action_requested'		=> 'id_search',
		);	

		// prepare the list fields for header set up and list formatting
  		$fields =  $wic_db_dictionary->get_list_fields_for_entity( $wic_query->entity );
	
		$output .= '<ul class = "wic-post-list">' .  // open ul for the whole list
			'<li class = "pl-odd">' .							// header is a list item with a ul within it
				'<ul class = "wic-post-list-headers">';				
					foreach ( $fields as $field ) {
						if ( $field->field_slug != 'ID' && $field->listing_order > 0 ) {
							$output .= '<li class = "wic-post-list-header pl-' . $wic_query->entity . '-' . $field->field_slug . '">' . $field->field_label . '</li>';
						}			
					}
			$output .= '</ul></li>'; // header complete
		$output .= $this->format_rows( $wic_query, $fields ); // format list item rows from child class	
		$output .= '</ul>'; // close ul for the whole list
		$output .= 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ) .
		'</form></div>'; 
		
		$output .= 	'<p class = "wic-list-legend">' . __('Search SQL was:', 'wp-issues-crm' )	 .  $wic_query->sql . '</p>';	

		return $output;
   } // close function
   
   // defines standard lookup hierarchy for formats (mirrors look up for dropdowns)
   protected function format_item ( $entity, $list_formatter, $value ) {
   	
		global $wic_db_dictionary;
   	
		// prepare to look for format in a sequence of possible sources
   	$class_name = 'WIC_Entity_' . $entity;
   	$function_class = 'WIC_Function_Utilities';

		// first point to an option array with list_formatter, in which case, just lookup and return the formatted value
		$option_array = $wic_db_dictionary->lookup_option_values( $list_formatter );

		if ( $option_array > '' ) {
			$display_value = WIC_Function_Utilities::value_label_lookup ( $value, $option_array );
	  	// second look for a method in the entity class (method must do own escaping of html b/c might add legit html)
		} elseif ( method_exists ( $class_name, $list_formatter ) ) { 	
			$display_value = $class_name::$list_formatter ( $value ) ;
		// third look for method in in the utility class 
		} elseif ( method_exists ( $function_class, $list_formatter ) ) {
			$display_value = $function_class::$list_formatter( $value );			
		// fourth look for a function in the global name space 
		} elseif ( function_exists ( $list_formatter ) ) {
			$display_value = $list_formatter( $value );
		// otherwise just display the value after esc_html 
		} else { 
			$display_value =  $value ;		
		}   
		return ( $display_value );
   }
   
   // the top row of buttons over the list -- down load button and change search criteria button
  	protected function get_the_buttons( &$wic_query ) { 
		$user_id = get_current_user_id();
		$buttons = '';

		if ( isset ( $wic_query->search_id ) ) { 
			
			$button_args_main = array(
				'button_class'					=> 'button button-primary wic-form-button',
				'button_label'					=> __( 'Export All', 'wp-issues-crm' ),
				'id'								=> 'wic-post-export-button',
				'name'							=> 'wic-post-export-button',
				'value' 							=>  $wic_query->search_id, 
				'title'						=>	__( 'Download constituents', 'wp-issues-crm' ),
			);	
			$buttons = WIC_Form_Parent::create_wic_form_button ( $button_args_main );	
			
			// show search form with parameters  
			$button_args = array (
					'entity_requested'	=> 'search_log',
					'action_requested'	=> 'id_search_to_form', // will display form with search criteria
					'id_requested'	=> $wic_query->search_id,
					'button_class'	=> 'button button-primary wic-top-menu-button ',
					'button_label'	=>	'<span class="dashicons dashicons-search"></span><span class="dashicons dashicons-update"></span></span>',
					'title'	=>	__( 'Change search criteria', 'wp-issues-crm' ),
				);
			$buttons .= WIC_Form_Parent::create_wic_form_button( $button_args );
		}
		
		return ( $buttons );
	}
}	

