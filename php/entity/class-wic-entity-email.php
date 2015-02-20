<?php
/*
*
*	wic-entity-email.php
*
*/



class WIC_Entity_Email extends WIC_Entity_Multivalue {

	protected function set_entity_parms( $args ) {
		extract ( $args );
		$this->entity = 'email';
		$this->entity_instance = $instance;
	} 

	public function update_row() {

		$message = '<a id = "wic-email-icon-link" title = "' . __( 'Send email to email address.', 'wp-issues-crm' ) . 
			 '" href="mailto:'. $this->get_email_address() .'"> ' .
				'<span class="dashicons dashicons-email-alt"></span>' .			
				 '</a>';
		$new_update_row_object = new WIC_Form_Email_Update ( $this->entity, $this->entity_instance );
		$new_update_row = $new_update_row_object->layout_form( $this->data_object_array, $message, null );
		return $new_update_row;
	}
		
	public static function email_address_validator ( $email ) { 
		$error = '';
		if ( $email > '' ) {	
			$error = filter_var( $email, FILTER_VALIDATE_EMAIL ) ? '' : __( 'Email address appears to be not valid. ', 'wp-issues-crm' );
		}
		return $error;	
	}	

	public function get_email_address() {
		return ( $this->data_object_array['email_address']->get_value() );	
	}
}