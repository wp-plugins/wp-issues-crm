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
					$message = sprintf ( __( 'Backout %1$s NEW input records from %2$s.' , 'wp-issues-crm' ), $new_constituents_saved, $upload_file );
					$backout_button_legend = sprintf ( __( 'Backout includes all activities, emails, phones and addresses for constituents.
						 ', 'wp-issues-crm' ), $new_constituents_saved );
				} else {
					$message = sprintf ( __( 'No NEW constituent records created from %s. Matched updates cannot be backed out.' , 'wp-issues-crm' ), $data_array['upload_file']->get_value() );				
				}					
			} elseif ( 'reversed' == $upload_status ) {
				$message_level = 'error';
				$message = 	sprintf ( __( 'Upload of %s already backed out.' , 'wp-issues-crm' ), $upload_file );  		
			} else {
				$message_level = 'error';
				$message = 	sprintf ( __( 'Upload of %s not started or completed -- cannot be backed out.' , 'wp-issues-crm' ), $upload_file );  		
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
				'disabled'						=> ( 'completed' != $upload_status && 'started' != $upload_status) || 0 == $new_constituents_saved,
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
						__( 'This backout function only removes newly added constituents. ', 'wp-issues-crm' ) .
					'</li>' .
					'<li>' .
						__( 'Updates to existing constituents generally cannot be reversed except by restoration from a Wordpress database backup.', 'wp-issues-crm' ) .
					'</li>' .
					'<li>' .
						 __( 'In the absence of a good backup, you may be able to surgically remove erroneously added activity records using phpmyadmin or direct SQL.
						 		The database structure of WP Issues CRM is transparent and intuitive if you have SQL experience.
						 		However, there is no good way to undo erroneous updates to core constituent data without a good backup.', 'wp-issues-crm' ).
					'</li><ul>' .
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