<?php
/*
*
*  class-wic-form-advanced-search-activity-update.php
*
*
*/

class WIC_Form_Advanced_Search_Activity_Update extends WIC_Form_Multivalue_Update  {
	
	// handling case of activity value was set as array after issue control substitution in js
	// need to create multivalue control for activity value -- doing surgery to parallel js surgery on control creation
	// only case where activity_value is array is when control was issue and search comparison was category 
	// see wic-main.js, wicAdvancedSearchReplaceControl	
	protected function get_the_formatted_control ( $control ) {
		if ( is_array ( $control->get_value() ) && 'activity_value' == $control->get_field_slug() ) {  
			$override_control = WIC_Control_Factory::make_a_control ( 'multiselect' );
			$override_control->initialize_default_values (  'issue', 'post_category', $this->entity_instance );
			$override_control->set_value( $control->get_value() );
			$override_control_html = $override_control->update_control(); 			
			// can't change entity and field slug in control before do control set up, otherwise, won't look up right values . . .
			// so, do surgery afterwards
			// discard label
			$override_control_html = substr( $override_control_html, strpos( $override_control_html, '</label>') + 8 );			
			// define ids for replacement
			$default_field_id = 'issue[' . $this->entity_instance . '][post_category]';
			$correct_field_id = 'advanced_search_activity[' . $this->entity_instance . '][activity_value]';
			// replace the individual field ids (also for attributes in labels)
			$override_control_html = str_replace( $default_field_id, $correct_field_id, $override_control_html );		
			$override_control_html = str_replace( 
				'<div class = "wic_multi_select"', 
				'<div id="' . $correct_field_id . '" class="wic_multi_select activity-value issue" name="' . $correct_field_id . '"', 				
				$override_control_html 
			);
	
			return ( $override_control_html );	
		}  
		return ( $control->update_control() ); 
	}
		
}