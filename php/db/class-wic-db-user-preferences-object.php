<?php
/*
* class-wic-db-user-preferences-object.php
*	interface object for user preferences
*
*/

class WIC_DB_User_Preferences_Object {
	
	public $ID;
	public $display_name;
	public $show_viewed_issue;
	public $show_latest_issues;
	public $max_issues_to_show;
	public $first_form;
	
	public function __construct ( $ID, $display_name, $show_viewed_issue, $show_latest_issues, $max_issues_to_show, $first_form ) {
		$this->ID = $ID;
		$this->display_name = $display_name;
		$this->show_viewed_issue = $show_viewed_issue;
		$this->show_latest_issues = $show_latest_issues;	
		$this->max_issues_to_show = $max_issues_to_show;
		$this->first_form = $first_form;
	}

}