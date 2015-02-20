<?php
/*
* 
* class-wic-control-alpha.php
* exists to support address_line search by street name without full text scanning
* field must be setup with additional alpha field in database as is address_line
*
*/

class WIC_Control_Alpha extends WIC_Control_Text {
	// named just for consistency
	
	protected function special_search_filter ( $search_clause ) {
		if ( ! is_numeric ( $this->value[0] ) ) {
			// $search_clause is a single array within a single array for an alpha field
			$search_clause[0]['key'] = $this->field->field_slug . '_alpha'; 			
		}
		return ( $search_clause );	
	}

	protected function special_update_filter ( $update_clause ) {
		$update_clause['secondary_alpha_search'] = $this->field->field_slug . '_alpha'; 
		return ( $update_clause );	
	}
	
	
	
}

