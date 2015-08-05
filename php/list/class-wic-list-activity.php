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

		// check current user so can highlight assigned cases
		$current_user_id = get_current_user_id();

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
							} else {
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



	protected function format_message( &$wic_query, $header='' ) {}

}	

