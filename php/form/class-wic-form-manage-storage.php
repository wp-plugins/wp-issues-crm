<?php
/*
*
*  class-wic-form-manage-storage.php
*
*/

class WIC_Form_Manage_Storage extends WIC_Form_Parent  {
	
	
	protected function get_the_entity() {
		return ( 'manage_storage' );	
	}	
	
	// no header tabs
	protected function format_tab_titles( &$data_array ){}
	
	
	// define the top row of buttons (return a row of wic_form_button s)
	protected function get_the_buttons ( &$data_array ) {

		$buttons = '';
		
		$button_args_main = array(
			'id'								=> 'manage_storage_button',
			'entity_requested'			=> 'manage_storage',
			'action_requested'			=> 'purge_storage',
			'button_class'					=> 'button button-primary wic-form-button',
			'button_label'					=> __( 'Purge Data', 'wp-issues-crm' )
		);	
		$buttons .= $this->create_wic_form_button ( $button_args_main );

		return $buttons;
		
	}
	
	// define the form message (return a message)
	protected function format_message ( &$data_array, $message ) {
		return ( __(' Set retention rules and purge data. ', 'wp-issues-crm') . $message );
	}

	// chose search controls
	protected function get_the_formatted_control ( $control ) {
		return ( $control->save_control() ); 
	}

	// screen in all groups (only one)
	protected function group_screen ( $group ) {
		return true;	
	}

	protected function group_special( $group ) {
		return ( 'statistics' == $group );	
	} 

	protected function group_special_statistics () {
		
		global $wpdb;

		$filter = $wpdb->prefix . 'wic_%';
		$table = $wpdb->get_results (
			"
			SHOW TABLE STATUS like '$filter'		
			",
			ARRAY_A );
		
		$output = '<div id = "wic-purge-progress-bar"></div>
					<table id="wp-issues-crm-stats"><tr>' .
					'<th class = "wic-statistic-text">' . __( 'Table Name', 'wp-issues-crm' ) . '</th>' .
					'<th class = "wic-statistic">' . __( 'Row Count', 'wp-issues-crm' ) . '</th>' .					
					'<th class = "wic-statistic">' . __( 'Disk Storage', 'wp-issues-crm' ) . '</th>' .
				'</tr>';
		
		$total_data_storage = 0;
		$total_index_storage = 0;
		
		foreach ( $table as $row ) { 
			$output .= '<tr>' .
			'<td class = "wic-statistic-table-name">' . $row['Name'] . '</td>' .
			'<td class = "wic-statistic" >' . $row['Rows'] . '</td>' .
			'<td class = "wic-statistic" >' . sprintf("%01.1f", ( $row['Index_length'] + $row['Data_length'] ) / 1024 )  . ' Kb' . '</td>' .
			'</tr>';
			$total_data_storage += $row['Data_length'];
			$total_index_storage += $row['Index_length'];

		} 
			$output .= '<tr>' .
			'<td class = "wic-statistic-table-name">' . __( 'Total for WP_Issues_CRM', 'wp-issues-crm') . '</td>' .
			'<td>' . '--'. '</td>' .
			'<td class = "wic-statistic" >' . sprintf("%01.1f", ( $total_data_storage + $total_index_storage ) / 1024 )  . ' Kb' . '</td>' .
			'</tr>';


		$output .= '</table>';
		
		return ( $output );
	}

	// hooks not implemented
	protected function get_the_legends( $sql = '' ) {}	
	protected function pre_button_messaging ( &$data_array ){}
	protected function post_form_hook ( &$data_array ) {} 
	protected function format_table_titles (){}
	protected function supplemental_attributes() {}

}