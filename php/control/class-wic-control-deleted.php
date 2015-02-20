<?php
/*
* wic-control-deleted.php
*
* supports fields of type deleted, which should be configured in dictionary with transient = true (1) 
*		-- it is possible that other form fields may later be needed as transient (i.e., not saved in database),
* 				also possible that the term is redundant and there are never any other transient fields, but 
*				have given it a separate term for stability of the code (transient fields are bypassed in several places)
*		-- every repeating group should be configured with a deleted type field with field_slug = screen_deleted
*				( if it is desired to be able to delete rows from the form )
*		-- the screen_deleted field should be assigned to a group within the entity which will determine its positioning
*				( it can have any field_label, x is just one idea, and can be styled further in css  
*					class = wic-input-deleted-label ) 
*
*/
class WIC_Control_Deleted extends WIC_Control_Parent {

	public function initialize_default_values ( $entity, $field_slug, $instance ) {
		parent::initialize_default_values ( $entity, $field_slug, $instance );
		$this->default_control_args['onclick_delete'] =  'hideSelf(\'' . esc_attr( $entity . '[' . $instance . ']'  ) . '\')';		
	}
	
	// hidden control do nothing on save or update	
	public function create_search_clause ( $dup_check ) {}
	public function create_update_clauses () {}	

	public function search_control () {
		// do not display a deleted control in search forms -- it is for deleting among multivalue rows	
	}	
	
	// on create control, taking args passed in parent, which include an $onclick_delete reflecting $entity and $instance
	protected static function create_control ( $control_args ) {

		$input_class = 'wic-input-deleted';
		$label_class = 'wic-input-deleted-label';
		extract ( $control_args, EXTR_SKIP ); 
	
		$readonly = $readonly ? 'readonly' : '';
		 
		$control = ( $field_label > '' ) ?  '<label title = "' . __( 'Permanently remove this row.', 'wp-issues-crm' ) . '" class="' . $label_class . '" for="' . 
				esc_attr( $field_slug ) . '"><span class="dashicons dashicons-dismiss"></span></span></label>' : '';
		$control .= '<input class="' . $input_class . '"  id="' . esc_attr( $field_slug ) . '" name="' . esc_attr( $field_slug ) . 
			'" type="checkbox"  value="1"' . checked( $value, 1, false) . $readonly  .' onclick = "' . $onclick_delete . '"/>' ;	

		return ( $control );

	}	

}	
