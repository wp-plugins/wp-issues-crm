<?php
/*
*
*	class-admin-statistics.php
*
*
*/


class WIC_Admin_Statistics {

	// display function on settings page
	public static function generate_storage_statistics() {
		
		global $wpdb;

		$filter = $wpdb->prefix . 'wic_%';
		echo '<div class="wrap"><h2>' . __( 'Storage Statistics', 'wp-issues-crm' ) . '</h2>';
		$table = $wpdb->get_results (
			"
			SHOW TABLE STATUS like '$filter'		
			",
			ARRAY_A );
		
		echo '<table id="wp-issues-crm-stats"><tr>' .
					'<th class = "wic-statistic-text">' . __( 'Table Name', 'wp-issues-crm' ) . '</th>' .
					'<th class = "wic-statistic">' . __( 'Row Count', 'wp-issues-crm' ) . '</th>' .					
					'<th class = "wic-statistic">' . __( 'Data Storage', 'wp-issues-crm' ) . '</th>' .
					'<th class = "wic-statistic">' . __( 'Index Storage', 'wp-issues-crm' ) . '</th>' .
					'<th class = "wic-statistic">' . __( 'Total Storage', 'wp-issues-crm' ) . '</th>' .
					'<th class = "wic-statistic-text">' . __( 'Created', 'wp-issues-crm' ) . '</th>'	.	
					'<th class = "wic-statistic-text">' . __( 'Last Updated', 'wp-issues-crm' ) . '</th>'	.								
				'</tr>';
		
		$total_data_storage = 0;
		$total_index_storage = 0;
		
		foreach ( $table as $row ) { 
			echo '<tr>' .
			'<td class = "wic-statistic-table-name">' . $row['Name'] . '</td>' .
			'<td class = "wic-statistic" >' . $row['Rows'] . '</td>' .
			'<td class = "wic-statistic" >' . sprintf("%01.1f", $row['Data_length'] / 1024  )  . ' Kb' . '</td>' .
			'<td class = "wic-statistic" >' . sprintf("%01.1f",$row['Index_length'] / 1024 ) . ' Kb' . '</td>' .
			'<td class = "wic-statistic" >' . sprintf("%01.1f", ( $row['Index_length'] + $row['Data_length'] ) / 1024 )  . ' Kb' . '</td>' .
			'<td>' . $row['Create_time'] . '</td>' .	 	
			'<td>' . $row['Update_time'] . '</td>' .	
			'</tr>';
			$total_data_storage += $row['Data_length'];
			$total_index_storage += $row['Index_length'];

		} 
			echo '<tr>' .
			'<td class = "wic-statistic-table-name">' . __( 'Total for WP_Issues_CRM', 'wp-issues-crm') . '</td>' .
			'<td>' . '--'. '</td>' .
			'<td class = "wic-statistic" >' . sprintf("%01.1f", $total_data_storage / 1024  )  . ' Kb' . '</td>' .
			'<td class = "wic-statistic" >' . sprintf("%01.1f", $total_index_storage / 1024 ) . ' Kb' . '</td>' .
			'<td class = "wic-statistic" >' . sprintf("%01.1f", ( $total_data_storage + $total_index_storage ) / 1024 )  . ' Kb' . '</td>' .
			'<td>' . '--'. '</td>' .	 	
			'<td>' . '--' . '</td>' .	
			'</tr>';


		echo '</table></div>';
	}
}