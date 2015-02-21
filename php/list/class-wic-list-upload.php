<?php
/*
* class-wic-list-upload.php
*
*
*/ 

class WIC_List_Upload extends WIC_List_Constituent {
	/*
	*
	*
	*
	*/

	protected function format_message( &$wic_query, $header = '' ) {
		$header_message = sprintf ( __( 'Found %1$s Files Uploaded.', 'wp-issues-crm'), $wic_query->found_count );		
		return $header_message;
	}

	protected function get_the_buttons ( &$wic_query ) {

		$buttons =  '<div id = "wic-list-button-row">'; 
		
			$button_args_main = array(
				'entity_requested'			=> 'upload', // entity_requested is not processed, since whole page is for option_group
				'action_requested'			=> 'new_blank_form',
				'button_class'					=> 'button button-primary wic-form-button',
				'button_label'					=> __('Upload File', 'wp-issues-crm')
			);	
			$buttons .= WIC_Form_Parent::create_wic_form_button ( $button_args_main );

		$buttons .= '</div>';

		return $buttons;
		
	}
 }	

