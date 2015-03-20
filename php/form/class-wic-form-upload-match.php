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
				$message = __( 'You must map fields and also validate data before you can match records.', 'wp-issues-crm' );
				$message_level = 'error';
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
			// file has been mapped and validated and ready for test matching -- needs to be matched/rematched
			} elseif ( 'validated' == $upload_status || 'matched' == $upload_status ) { 
				// show message inviting match/rematch
				$message =  sprintf ( __( 'Match records from %s to your previously saved constituents in WP Issues CRM. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				// show validation start button (not a submit -- will drive AJAX)
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Save/Test Match', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'match-button'
				);	
				$button = $this->create_wic_form_button ( $button_args_main );

				echo $button;			
				// place for progress bar -- ajax controlled; initial display none
				echo '<div id = "wic-upload-progress-bar"></div>'; 	
				
				// get the match strategy lists
				$match_strategies = new WIC_Entity_Upload_Match_Strategies ();
				
				// note that must make the decision as to whether or not to reset that match result array here
				// depends on upload status (mapped/validated/matched/completed), not on content of array
				// if status is validated, then first match since a remap, so start match counts over (true)
				if ( 'validated' == $upload_status ) {
					echo $match_strategies->layout_sortable_match_options( $data_array['ID']->get_value(), true );
				// if status is matched, then have a match result array that the user has defined and it has not //been remapped, so use it (false)				
				} elseif ( 'matched' == $upload_status )  {
					echo $match_strategies->layout_sortable_match_options( $data_array['ID']->get_value(), false );
				}

	  			echo '<div id = "upload-results-table-wrapper"></div>';
	  		
	  		// file has already been completed
			} elseif ( 'completed' == $upload_status) {
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