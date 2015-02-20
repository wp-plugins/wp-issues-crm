<?php
/*
*
*  class-wic-form-issue-save.php
*
*/

class WIC_Form_Issue_Save extends WIC_Form_Issue_Update  {
	
	protected function get_the_buttons ( &$data_array ) {
		$button_args_main = array(
			'entity_requested'			=> 'issue',
			'action_requested'			=> 'form_save',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __('Save', 'wp-issues-crm')
		);	
		$buttons = $this->create_wic_form_button ( $button_args_main );
		
		$buttons .= '<a href="/wp-admin/post-new.php">' .
								__( 'New Post in Wordpress editor.', 'wp-issues-crm' ) .
							'</a>';		

		return ( $buttons ) ;
	}
	
	protected function format_message ( &$data_array, $message ) {
		return ( __('Save new issue. ' , 'wp-issues-crm') . $message );
	}

	protected function get_the_formatted_control ( $control ) {
		return ( $control->save_control() ); 
	}

	
}