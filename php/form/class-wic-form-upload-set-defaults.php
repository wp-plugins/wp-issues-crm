<?php
/*
* class-wic-form-upload-set-defaults.php
*
*
*/

class WIC_Form_Upload_Set_Defaults extends WIC_Form_Upload_Validate  {

	public function layout_form ( &$data_array, $message, $message_level, $sql = '' ) {
		
		global $wic_db_dictionary;		
		
		echo $this->format_tab_titles( $data_array );		
		
		?><div id='wic-forms'> 
		
		<form id = "<?php echo $this->get_the_form_id(); ?>" <?php $this->supplemental_attributes(); ?> class="wic-post-form" method="POST" autocomplete = "on">
			
			<?php // form layout driven by upload status 
			$upload_status = $data_array['upload_status']->get_value();
			
			// file has not been matched or rematched	
			if ( 'staged' == $upload_status || 'mapped' == $upload_status  || 'validated' == $upload_status ) {			
				$message = __( 'You must map fields, validate data and match records before you can set defaults.', 'wp-issues-crm' );
				$message_level = 'error';
				?><div id="post-form-message-box" class = "<?php echo $this->message_level_to_css_convert[$message_level]; ?>" ><?php echo esc_html( $message ); ?></div><?php
			// file has all previous steps done; if started or completed upload show form here, but disable input in js 
			} else {
				if ( 'matched' == $upload_status || 'defaulted' == $upload_status ) { 
					$message = 	sprintf ( __( '%s -- database update settings. ', 'wp-issues-crm' ), $data_array['upload_file']->get_value() );
					// note: manage message class in js
				} else {
					$message =  sprintf ( __( 'Settings used for %2$s.  Upload %1$s. ' , 'wp-issues-crm' ), $upload_status, $data_array['upload_file']->get_value() )  . $message;
				}
				?><div id="post-form-message-box"><span id="post-form-message-base"><?php echo esc_html( $message ); ?></span></div><?php

				// no button -- all AJAX on change
				
				// invoke parent form generation logic to generate controls	
				$group_array = $this->generate_group_content_for_entity( $data_array );
				extract ( $group_array );
			
				// output form
				echo 	'<div id="wic-upload-default-form-body">' . 
							'<div id="wic-form-main-groups">' .  
								$main_groups . 
							'</div>' .
							'<div id="wic-form-sidebar-groups">' . 
								$sidebar_groups . 
								// place for progress bar and results div for lookup of issue titles if used
								'<div id = "wic-issue-lookup-progress-bar"></div>' .
								'<div id = "wic-issue-lookup-results-wrapper"></div>' .
							'</div>' . 
						'</div>';					// wic-upload-default-form-body
			echo '<div class = "horbar-clear-fix"></div>';
					// put the upload status in here for reference (could be anywhere)
			echo '<div id="initial-upload-status">' . $data_array['upload_status']->get_value() . '</div>';
	  		// file has already been completed
			}
	   
		   // in all cases, echo ID, serialized working fields, nonce
			echo $data_array['ID']->update_control();	
			echo $data_array['serialized_upload_parameters']->update_control();
			echo $data_array['serialized_column_map']->update_control();		
			echo $data_array['serialized_match_results']->update_control();	
			echo $data_array['serialized_default_decisions']->update_control();

			$options = get_option ('wp_issues_crm_plugin_options_array');
			if ( isset ( $options['do_zip_code_format_check'] ) ) {
				echo '<div id="do_zip_code_format_check"></div>';			
			} 			
					
		 	wp_nonce_field( 'wp_issues_crm_post', 'wp_issues_crm_post_form_nonce_field', true, true ); 
			echo $this->get_the_legends( $sql ); ?>
			</div>								
		</form>
		<?php // child class may insert messaging here 
		$this->post_form_hook( $data_array ); ?>
		</div>
		
		<?php 
		
	}

	// group screen
	protected function group_screen( $group ) {
		return (	
			// 'summary_results' == $group->group_slug  ||
			'constituent_match' == $group->group_slug  ||
			'constituent' == $group->group_slug  ||
			'address' == $group->group_slug ||
			'phone' == $group->group_slug ||
			'email' == $group->group_slug ||
			'activity' == $group->group_slug ||
			'issue' == $group->group_slug ||
			'new_issue_creation' == $group->group_slug									
		  ) ;	
	}

	protected function get_the_formatted_control ( $control ) {
		return ( $control->update_control() ); 
	}

	protected function get_the_legends( $sql = '' ) {
		$legend = '<p>' .  __( 'WP Issues CRM enforces validation and required field rules at the row and column level. For more understanding of how WP Issues CRM protects data quality, go to ', 'wp-issues-crm' ) . 
			'<a href="http://wp-issues-crm.com/?page_id=74" target = "_blank">WPissuesCRM.com</a>.' . '</p>';
		$legend = '<div class = "wic-upload-legend">' . $legend . '</div>';		
		return $legend;
		
	}
	 	
}