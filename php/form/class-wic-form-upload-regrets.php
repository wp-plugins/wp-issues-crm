<?php
/*
* class-wic-form-upload-regrets.php
*
*
*/

class WIC_Form_Upload_Regrets extends WIC_Form_Upload_Validate  {  			
	
	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) { 
		global $wic_db_dictionary;		
		
		echo $this->format_tab_titles( $data_array );		
		
		?><div id='wic-forms'> 
		
		<form id = "<?php echo $this->get_the_form_id(); ?>" <?php $this->supplemental_attributes(); ?> class="wic-post-form" method="POST" autocomplete = "on">

			<?php 
			// set up decision/message variables
			$upload_status 				= $data_array['upload_status']->get_value();
			$upload_parameters			= json_decode ( $data_array['serialized_upload_parameters']->get_value() );
			$default_decisions			= json_decode ( $data_array['serialized_default_decisions']->get_value() );
			$new_constituents_saved		= 0; // initialized as zero -- makes sense until completed
			$upload_file 					= $data_array['upload_file']->get_value();
			$backout_button_legend		= '';

			// compose appropriate message depending on status
			if ( 'completed' == $upload_status || 'started' == $upload_status ) {
				// final_results is an object only after completion  
				$final_results 				= json_decode ( $data_array['serialized_final_results']->get_value() );
				$new_constituents_saved 	= $final_results->new_constituents_saved;
				if ( $new_constituents_saved > 0 ) {
					$message = sprintf ( __( 'Reverse upload of %1$s NEW input records from %2$s.  Also reverse all activities added for any constituent.' , 'wp-issues-crm' ), $new_constituents_saved, $upload_file );
					$backout_button_legend = sprintf ( __( 'Reversal includes all activities, emails, phones and addresses for constituents.
						 ', 'wp-issues-crm' ), $new_constituents_saved );
				} else {
					$message = sprintf ( __( 'No NEW constituent records created from %s. Backout will only affect activities added (if any).' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );				
				}					
			} elseif ( 'reversed' == $upload_status ) {
				$message_level = 'error';
				$message = 	sprintf ( __( 'Upload of new constituents from %s already reversed.' , 'wp-issues-crm' ), $upload_file );  		
			} else {
				$message_level = 'error';
				$message = 	sprintf ( __( 'Upload of %s not started or completed -- cannot be reversed.' , 'wp-issues-crm' ), $upload_file );  		
			}
		
			// show message
			?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php			
			
			// show button	
			$button_args_main = array(
				'entity_requested'			=> $upload_parameters->staging_table_name,
				'action_requested'			=> 'backout_new',
				'button_class'					=> 'button button-primary wic-form-button',
				'button_label'					=> __( 'Backout New', 'wp-issues-crm' ) ,
				'type'							=> 'button',
				'id'								=> 'wic-backout-button',
				'name'							=> 'wic-backout-button',
				// enable button consistently with message above button
				'disabled'						=> ( 'completed' != $upload_status && 'started' != $upload_status),
			);	
			$button = $this->create_wic_form_button ( $button_args_main );
			echo $button;
			// show button legend
			echo '<div class = "backout-button-legend" >' . 
				$backout_button_legend .
			'</div>';
			// slot for progress bar
			echo '<div id = "wic-upload-progress-bar"></div>';
			
			// offer some explanation about backout issues
			echo '<div id = "backout_legend">' .
					'<h3>' . __( 'Backing out updates:', 'wp-issues-crm' ) . '</h3>' .
					'<ul class = "upload-status-summary" >' .
					'<li>' .
						__( 'This backout function deletes newly added activities and newly added constituents. ', 'wp-issues-crm' ) .
					'</li>' .
					'<li>' .
						__( 'Updates to address, phone or email of existing constituents generally cannot be reversed except by restoration from a Wordpress database backup.', 'wp-issues-crm' ) .
					'</li>' .
					'<ul>' .
				'</div>';			
			
			echo $data_array['ID']->update_control();	
			echo $data_array['serialized_upload_parameters']->update_control();
		 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ); 
			echo $this->get_the_legends( $sql ); ?>							
		</form>
		<?php // child class may insert messaging here 
		$this->post_form_hook( $data_array ); ?>
		</div>
		
		<?php 
		
	}

	protected function get_the_legends( $sql = '' ) {
		// fine print legend area
		$legend = '<p>' .
					'</p>';
			
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';		
		return $legend;
		
	}

	// group screen
	protected function group_screen( $group ) { }
	protected function get_the_formatted_control ( $control ) { return ( false ); }
	protected function group_special ( $group ) {}

	
	 	
}