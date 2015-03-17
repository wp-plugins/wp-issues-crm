<?php
/*
* class-wic-form-upload-match.php
*
*
*/

class WIC_Form_Upload_Match extends WIC_Form_Upload_Validate  {

	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) {
		
		global $wic_db_dictionary;		
		
		echo $this->format_tab_titles( $data_array );		
		
		?><div id='wic-forms'> 
		
		<form id = "<?php echo $this->get_the_form_id(); ?>" <?php $this->supplemental_attributes(); ?> class="wic-post-form" method="POST" autocomplete = "on">

			<?php // form layout driven by upload status
			$upload_status = $data_array['upload_status']->get_value();
			
			// file has not been mapped or has been remapped but not validated -- show error message		
			if ( 'staged' == $upload_status || 'mapped' == $upload_status  ) {			
				$message = __( 'You must map fields and validate data before you can match records.', 'wp-issues-crm' );
				$message_level = 'wic-form-errors-found';
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				return; // don't show the rest of the form if fields not mapped
				
			// file has been mapped and validated and ready for test matching -- needs to be matched/rematched
			} elseif ( 'validated' == $upload_status ) { 
				// show message inviting validation
				$message =  sprintf ( __( 'Match records from %s to your previously saved records. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				// show validation start button (not a submit -- will drive AJAX)
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Match', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'match-button'
				);	
				$button = $this->create_wic_form_button ( $button_args_main );

				echo $button;			
				// place for progress bar -- ajax controlled; initial display none
				echo '<div id = "wic-upload-progress-bar"></div>'; 	
				
				// get the match strategy lists -- true says save the doable match strategy array ( based on latest column mapping )
				$match_strategies = new WIC_Entity_Upload_Match_Strategies (  );
				echo $match_strategies->layout_sortable_match_options( $data_array['ID']->get_value(), true );
	  			// echo '<div id = "upload-results-table-wrapper"></div>';
	  		
	  		// file has already been matched or even completed
			} else {
				$message =  sprintf ( __( 'Records previouly matched for %s. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() )  . $message;
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				
				// show button as disabled				
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Matched', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'match-button',
					'disabled'						=> true,
				);	
				$button = $this->create_wic_form_button ( $button_args_main );
				echo $button;
	  			echo '<div id = "upload-results-table-wrapper">' . 
					// 	  			
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
	 	
}