<?php
/*
* class-wic-list-trend.php
*
*
*/ 

class WIC_List_Activity extends WIC_List_Parent {
	/*
	* No message header
	*
	*/
	
	protected function list_entity_field_filter ( $fields, &$wic_query ) {
		$filtered_fields = array();
		if ( false === $wic_query->financial_activities_in_results ) {
			foreach ( $fields as $field ) {
				if ( 'activity_amount' != $field->field_slug ) {
					$filtered_fields[] = $field;				
				}			
			}
			return ( $filtered_fields );		
		} else {
			return ( $fields );		
		}
			
	}	
	
	public function format_entity_list( &$wic_query, $header ) { 

		$wic_query->entity = 'activity'; // came in as trend

  		// set up form
		$output = '<div id="wic-post-list"><form id="wic_constituent_list_form" method="POST">';
		$output .= $this->get_the_buttons( $wic_query );	
		$output .= $this->set_up_rows ( $wic_query );
		$output .= 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ) .
		'</form></div>'; 
		
		$output .= 	'<p class = "wic-list-legend">' . __('Search SQL was:', 'wp-issues-crm' )	 .  $wic_query->sql . '</p>';

		return $output;
   } // close function

	
	
	protected function format_rows( &$wic_query, &$fields ) {

		$output = '';
		$line_count = 1;

		// get financial activity types to avoid showing nonsensical zero amounts
		$wic_option_array = get_option('wp_issues_crm_plugin_options_array'); 
		$financial_types_array = explode (',' , isset ( $wic_option_array['financial_activity_types'] ) ? $wic_option_array['financial_activity_types'] : '') ;
		foreach ( $wic_query->result as $row_array ) {

			$row= '';
			$line_count++;
			$row_class = ( 0 == $line_count % 2 ) ? "pl-even" : "pl-odd";
			
			$row .= '<ul class = "wic-post-list-line">';			
				foreach ( $fields as $field ) { 
					// showing fields other than ID with positive listing order ( in left to right listing order )
					if ( 'ID' != $field->field_slug && $field->listing_order > 0 ) {
						$row .= '<li class = "wic-post-list-field pl-' . $wic_query->entity . '-' . $field->field_slug . ' "> ';
							if ( 'constituent_id' == $field->field_slug ) {
								$row .= $row_array->last_name . ', ' . $row_array->first_name;							
							} elseif ( 'issue' == $field->field_slug ) {
								$row .= $row_array->post_title;
							} elseif ( 'activity_amount' == $field->field_slug ) {								
								$row .= in_array ( $row_array->activity_type, $financial_types_array ) ? $row_array->activity_amount : '--'; 							
							} else  {
								$row .=  $this->format_item ( $wic_query->entity, $field->list_formatter, $row_array->{$field->field_slug} ) ;
							}
						$row .= '</li>';			
					}	
				}
			$row .='</ul>';				
			
			$list_button_args = array(
					'entity_requested'	=> 'constituent',
					'action_requested'	=> 'id_search',
					'button_class' 		=> 'wic-post-list-button ' . $row_class,
					'id_requested'			=> $row_array->constituent_id,
					'button_label' 		=> $row,				
			);			
			$output .= '<li>' . WIC_Form_Parent::create_wic_form_button( $list_button_args ) . '</li>';	
		} // close for each row
		return ( $output );		
	} // close function 

   
   // the top row of buttons over the list -- down load button alone in this case, since search-again is main button
  	protected function get_the_buttons( &$wic_query ) { 

		$buttons = '';
		
		// wic-activity-export-button
		$button_args = array (
				'name' => 'wic-activity-export-button',
				'entity_requested'	=> 'trend',
				'action_requested'	=> 'activities', // will display form with search criteria
				'id_requested'	=> $wic_query->search_id,
				'button_class'	=> 'button button-primary wic-form-button ',
				'button_label'	=>	__( 'Download Activities', 'wp-issues-crm' ),
				'title'	=>	__( 'Download all activities meeting search criteria together with related constituent information', 'wp-issues-crm' ),
			);
		$buttons .= WIC_Form_Parent::create_wic_form_button( $button_args );
		
		return ( $buttons );
	}

	// format message -- used by entities showing forms above the list ( see, e.g., entity_trend )
	public function format_message( &$wic_query, $header='' ) {
	
		$financial_total = $wic_query->financial_activities_in_results ? sprintf ( __( ' Total amount for found activities is %1$s.', 'wp-issues-crm'), $wic_query->amount_total ) : '';	
	
		if ( $wic_query->found_count <= $wic_query->retrieve_limit ) {
			$header_message = $header . sprintf ( __( 'Found %1$s activities.', 'wp-issues-crm'), $wic_query->showing_count ) . $financial_total;		
		} else {
			$header_message = $header . sprintf ( __( 'Found total of %1$s activities, showing search maximum -- %2$s.', 'wp-issues-crm'),
				 $wic_query->found_count, $wic_query->showing_count ) . $financial_total; 		
		}
		return $header_message;
	}

}	

