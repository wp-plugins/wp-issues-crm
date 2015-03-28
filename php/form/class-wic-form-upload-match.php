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
			} elseif ( 'validated' == $upload_status || 'matched' == $upload_status || 'defaulted' == $upload_status ) { 
				// show message inviting match/rematch
				$message =  sprintf ( __( 'Match records from %s to your previously saved constituents in WP Issues CRM. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				// show validation start button (not a submit -- will drive AJAX)
				$disabled = ( 'matched' == $upload_status || 'defaulted' == $upload_status );
				$button_args_main = array(
					'entity_requested'			=> 'upload',
					'action_requested'			=> 'form_update',
					'button_class'					=> 'button button-primary wic-form-button',
					'button_label'					=> __('Save/Test Match', 'wp-issues-crm'),
					'type'							=> 'button',
					'id'								=> 'match-button',
					'disabled'						=> $disabled,
				);	
				$button = $this->create_wic_form_button ( $button_args_main );

				echo $button;			
				// place for progress bar -- ajax controlled; initial display none; results wrapper also filled by ajax
				echo '<div id = "wic-upload-progress-bar"></div>';

				$match_results_table = '';
				if ( 'matched' == $upload_status || 'defaulted' == $upload_status ) { 
					$upload_parameters = json_decode ( $data_array['serialized_upload_parameters']->get_value() ); 
					$match_results_table = '<h3>' . sprintf( __( 'Previous test match results for %s records saved in staging table.', 'wp-issues-crm' ),
						$upload_parameters->insert_count) . 
						'</h3>';
					$match_results_table .= 
						WIC_Entity_Upload::prepare_match_results ( json_decode ( $data_array['serialized_match_results']->get_value() ) );
				}	   
				echo '<div id = "upload-results-table-wrapper">' . $match_results_table . '</div>'; 	
				
				echo '<div id = "upload-match-wrap">';
				// get the match strategy lists
				$match_strategies = new WIC_Entity_Upload_Match_Strategies ();
				
				// note that must make the decision as to whether or not to reset that match result array here
				// depends on upload status (mapped/validated/matched/completed), not on content of array
				// if status is validated, then first match since a remap, so start match counts over (true)
				if ( 'validated' == $upload_status ) {
					echo $match_strategies->layout_sortable_match_options( $data_array['ID']->get_value(), true );
				// if status is matched, then have a match result array that the user has defined and it has not //been remapped, so use it (false)				
				} elseif ( 'matched' == $upload_status || 'defaulted' == $upload_status )  {
					echo $match_strategies->layout_sortable_match_options( $data_array['ID']->get_value(), false );
				}

	  			echo '</div><div class = "horbar-clear-fix"></div>';
	  		
	  		// file has already been completed
			} elseif ( 'completed' == $upload_status) {
				$message =  sprintf ( __( 'Records previouly matched for %s. ' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() )  . $message;
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
				
				// don't show button just results 
 
				$upload_parameters = json_decode ( $data_array['serialized_upload_parameters']->get_value() ); 
				$match_results_table = '<h3>' . sprintf( __( 'Match implemented for completed upload of %s records saved in staging table.', 'wp-issues-crm' ),
					$upload_parameters->insert_count) . 
				'</h3>';
				$match_results_table .= 
					WIC_Entity_Upload::prepare_match_results ( json_decode ( $data_array['serialized_match_results']->get_value() ) );
				echo '<div id = "upload-results-table-wrapper">' . $match_results_table . '</div>';   			
			
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

	protected function get_the_legends( $sql = '' ) {
		// report configuration settings related to upload capacity;
		$legend = '<p>' . __( 'WP Issues CRM attempts matches between the upload file and your existing database records in multiple passes 
									in the order that you prioritize the match criteria. You should usually proceed from most specific to least specific,
									but what is most specific ( i.e., most likely to be unique ) may vary depending on your data. You can test alternative match sequences.
									The match options shown include only those for which you have fields mapped.', 'wp-issues-crm' ) . '</p><p>' . 
								__( 'If records are unmatched after all passes but possess the matching fields you prioritized for at least one match pass, 
									they will be flagged as possible additions as new constituents. They will be grouped with records containing the 
									same values for the matching fields in the first pass in which they were unmatched.  Constituents will not be added unless at least
									one of the input records so grouped contains a core identifier -- last name, first name or email -- making the new constituent viable.',
									'wp-issues-crm' ) .
					'</p>';
			
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';		
		return $legend;
		
	}
	 	
}