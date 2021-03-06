<?php
/*
* class-wic-form-upload-validate.php
*
*	NOTE: This subfamily form extends the generic parent, so has available the whole data_array available, but does not follow the group
*		display logic built into most other forms and does not do submits -- forms include embedded logic, work with javascript to handle
*		user transactions via AJAX
*
*/

class WIC_Form_Upload_Validate extends WIC_Form_Parent  {

	protected function format_tab_titles( &$data_array ) {
		return ( WIC_Entity_Upload::format_tab_titles( $data_array['ID']->get_value() ) );	
	}

	// associate form with entity in data dictionary
	protected function get_the_entity() {
		return ( 'upload' );	
	}

	// no main form buttons in this form -- action = ajax
	protected function get_the_buttons ( &$data_array ) {}
	
	// define the form message (return a message) 
	protected function format_message ( &$data_array, $message ) {
		// function bypassed in this class because have major conditional on upload_status
	}
	
	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) {
		
		global $wic_db_dictionary;		
		
		echo $this->format_tab_titles( $data_array );		
		
		?><div id='wic-forms'> 
		
		<form id = "<?php echo $this->get_the_form_id(); ?>" <?php $this->supplemental_attributes(); ?> class="wic-post-form" method="POST" autocomplete = "on">

			<?php // form layout driven by upload status
			$upload_status = $data_array['upload_status']->get_value();
			// file has not been mapped or has been remapped		
			if ( 'staged' == $upload_status ) {			
				$message = __( 'You must map fields before you can validate data.', 'wp-issues-crm' );
				$message_level = 'wic-form-errors-found';
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				return; // don't show the rest of the form if fields not mapped
			// file has been mapped or remapped and needs validation
			} elseif ( 'mapped' == $upload_status ) { 
				// show message inviting validation
				$message =  sprintf ( __( 'Validate data in mapped fields for %s. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() )  . $message;
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				// show validation start button (not a submit -- will drive AJAX)
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Validate', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'validate-button'
				);	
				$button = $this->create_wic_form_button ( $button_args_main );
				echo $button;			
				// place for progress bar -- ajax controlled; initial display none
				echo '<div id = "wic-upload-progress-bar"></div>'; 	
	  			// place show validation results (populate by ajax) in table.
	  			echo '<div id = "upload-results-table-wrapper"></div>';
	  		// file has already been validated -- just displaying saved validation results
			} else {
				$message =  sprintf ( __( 'Data previously validated for mapped fields for %s. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() )  . $message;
				if ( 'completed' == $upload_status || 'started' == $upload_status || 'reversed' == $upload_status ) {
					$message_level = 'error';
					$message .= sprintf( __( 'Upload %s.','wp-issues-crm' ), $upload_status ) ;				
				}
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Validated', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'validate-button',
					'disabled'						=> true,
				);	
				$button = $this->create_wic_form_button ( $button_args_main );
				echo $button;
	  			echo '<div id = "upload-results-table-wrapper">' . 
					WIC_Entity_Upload::prepare_validation_results( json_decode ( $data_array['serialized_column_map']->get_value() ) ) .	  			
	  			'</div>';			
			}
		   // in all cases, echo ID and progress field
			echo $data_array['ID']->update_control();	
			echo $data_array['serialized_upload_parameters']->update_control();		
		 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ); 
			echo $this->get_the_legends( $sql ); ?>
			</div>								
		</form>
		<?php // child class may insert messaging here 
		$this->post_form_hook( $data_array ); ?>
		</div>
		
		<?php 
		
	}
	// choose update controls for form
	protected function get_the_formatted_control ( $control ) {}

	// legends
	protected function get_the_legends( $sql = '' ) {
		$legend = '<p>' .  __( 'For tips on field mapping and validation, go to ', 'wp-issues-crm' ) . 
			'<a href="http://wp-issues-crm.com/?page_id=51" target = "_blank">WPissuesCRM.com</a>.' . '</p>';
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';		
		return $legend;
		
	}
	
	// functions not implemented.
	protected function group_screen( $group ) {}
	protected function group_special ( $group ) {}
	protected function group_special_upload_parameters ( &$doa ) { }
	protected function group_special_save_options ( &$doa ) {}
	protected function supplemental_attributes() {}
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {}
	 	
}