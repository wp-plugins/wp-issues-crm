<?php
/*
* class-wic-list-issue.php
*
*
*/ 

class WIC_List_Issue extends WIC_List_Parent {
	/*
	* return from wp_query actually has the full post content already, so not two-stepping through lists
	*
	*/
protected function format_rows( &$wic_query, &$fields ) {

		$output = '';
		$line_count = 1;

		// check current user so can highlight assigned cases
		$current_user_id = get_current_user_id();

		foreach ( $wic_query->result as $row_array ) {

			$row= '';
			$line_count++;
			$row_class = ( 0 == $line_count % 2 ) ? "pl-even" : "pl-odd";
			
			// add special row class to reflect case assigned status
			if ( $current_user_id == $row_array->issue_staff ) {
				$row_class .= " case-assigned ";
				if ( 'open' == $row_array->follow_up_status ) {
					$row_class .= " case-open ";
					if ( '' == $row_array->review_date ) {	
						$review_date = new DateTime ( '1900-01-01' );
					} else {
						$review_date = new DateTime ( $row_array->review_date );					
					}
					$today = new DateTime( current_time ( 'Y-m-d') );
					$interval = date_diff ( $review_date, $today );
					if ( 0 == $interval->invert ) {
						$row_class .= " overdue ";				
						if ( 7 < $interval->days ) {
							$row_class .= " overdue long-overdue ";				
						}
					}
				} elseif ( 0 == $row_array->follow_up_status ) {			
					$row_class .= " case-closed ";
				}	
			}		

			$row .= '<ul class = "wic-post-list-line">';			
				foreach ( $fields as $field ) { 
					if ( 'ID' != $field->field_slug && 0 < $field->listing_order ) {
						if ( 'post_category' == $field->field_slug ) {
							$display_value =  esc_html( WIC_Entity_Issue::get_post_categories( $row_array->ID ) );		
						} else {
							$display_value = $this->format_item ( $wic_query->entity, $field->list_formatter, $row_array->{$field->field_slug} ) ;		
						}
						$row .= '<li class = "wic-post-list-field pl-' . $wic_query->entity . '-' . $field->field_slug . ' "> ';
							$row .=  $display_value ;
						$row .= '</li>';			
					}	
				}
			$row .='</ul>';				
			
			$list_button_args = array(
					'entity_requested'	=> $wic_query->entity,
					'action_requested'	=> 'id_search',
					'button_class' 		=> 'wic-post-list-button ' . $row_class,
					'id_requested'			=> $row_array->ID,
					'button_label' 		=> $row,				
			);			
			$output .= '<li>' . WIC_Form_Parent::create_wic_form_button( $list_button_args ) . '</li>';	
			}
		return ( $output );		
	} // close function 

	protected function format_message( &$wic_query, $header='' ) {

		if ( $wic_query->found_count < $wic_query->retrieve_limit ) {
			$header_message = $header . sprintf ( __( 'Found %1$s issues. 
				Export, if available, will select all constituents with activities for any of these issues.', 'wp-issues-crm'), 
					$wic_query->found_count );		
		} else {
			$header_message = $header . sprintf ( __( 'Found total of %1$s issues, showing selected search maximum -- %2$s.  
				Export, if available, will select all constituents with activities for any of the total %1$s issues.', 'wp-issues-crm'),
					$wic_query->found_count, $wic_query->showing_count ); 		
		}
		return $header_message;

	}
 
 }	

