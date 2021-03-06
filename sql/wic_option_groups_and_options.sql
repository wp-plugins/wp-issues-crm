INSERT INTO wp_wic_option_group (ID, option_group_slug, option_group_desc, enabled, last_updated_time, last_updated_by, mark_deleted, is_system_reserved) VALUES
(1, 'activity_type_options', 'Activity Types', 1, '2015-02-02 11:55:19', 15, '', 0),
(2, 'address_type_options', 'Address Types', 1, '2015-01-26 00:00:00', 15, '', 0),
(3, 'case_status_options', 'Issue/Case Status Options', 1, '2015-01-28 00:00:00', 15, '', 0),
(4, 'category_search_mode_options', 'Category Search Mode Options', 1, '2015-01-24 00:00:00', 15, '', 1),
(5, 'email_type_options', 'Email Types', 1, '2015-01-24 00:00:00', 15, '', 0),
(6, 'follow_up_status_options', 'Follow up status options', 1, '2015-02-09 09:31:10', 15, '', 0),
(7, 'gender_options', 'Gender Codes', 1, '0000-00-00 00:00:00', 0, '', 0),
(8, 'match_level_options', 'match_level_options', 1, '0000-00-00 00:00:00', 0, '', 1),
(9, 'party_options', 'Political Party', 1, '2015-01-24 00:00:00', 15, '', 0),
(10, 'phone_type_options', 'Phone Types', 1, '2015-02-02 14:36:25', 15, '', 0),
(11, 'post_status_options', 'post_status_options', 1, '0000-00-00 00:00:00', 0, '', 1),
(12, 'pro_con_options', 'Pro/Con Options', 1, '0000-00-00 00:00:00', 0, '', 0),
(13, 'retrieve_limit_options', 'retrieve_limit_options', 1, '0000-00-00 00:00:00', 0, '', 1),
(14, 'state_options', 'State Options', 1, '2015-02-09 15:46:09', 15, '', 0),
(15, 'voter_status_options', 'Voter Status Options', 1, '0000-00-00 00:00:00', 0, '', 0),
(20, 'customizable_groups', 'Groups suitable for custom fields', 1, '2015-01-24 00:00:00', 15, '', 1),
(21, 'custom_field_types', 'Field Types', 1, '2015-01-25 00:00:00', 15, '', 1),
(23, 'like_search', 'Like Search Enabled', 1, '2015-01-25 00:00:00', 15, '', 1),
(24, 'enabled_disabled_array', 'Enabled/Disabled', 1, '2015-01-26 00:00:00', 15, '', 1),
(25, 'wic_live_issue_options', 'Live Issue Options', 1, '2015-01-26 00:00:00', 15, '', 1),
(27, 'show_latest_issues_options', 'Options for Activity Issues Dropdown', 1, '0000-00-00 00:00:00', 0, '', 1),
(28, 'count_to_ten', 'Number of issues to retrieve', 1, '0000-00-00 00:00:00', 0, '', 1),
(29, 'trend_search_modes', 'Trend Search Modes', 1, '2015-02-10 12:00:00', 15, '0', 1),
(30, 'capability_levels', 'Capability Levels', 1, '2015-02-11 16:38:00', 15, '', 1),
(31, 'first_form', 'Form to show on startup', 1, '0000-00-00 00:00:00', 0, '', 1);
INSERT INTO wp_wic_option_value ( option_group_id, option_value, option_label, value_order, enabled, last_updated_time, last_updated_by) VALUES
('3', '', '', 30, 1, '2015-01-28 00:00:00', 15),
('3', '0', 'Closed', 10, 1, '2015-01-28 00:00:00', 15),
('3', '1', 'Open', 20, 1, '2015-01-28 00:00:00', 15),
('7', '', '', 30, 1, '0000-00-00 00:00:00', 0),
('7', 'm', 'Male', 10, 1, '0000-00-00 00:00:00', 0),
('7', 'f', 'Female', 20, 1, '0000-00-00 00:00:00', 0),
('9', '', '', 90, 1, '2015-01-24 00:00:00', 15),
('9', 'd', 'Democrat', 10, 1, '2015-01-24 00:00:00', 15),
('9', 'r', 'Republican', 20, 1, '2015-01-24 00:00:00', 15),
('9', 'u', 'Unenrolled', 30, 1, '2015-01-24 00:00:00', 15),
('9', 'l', 'Libertarian', 40, 1, '2015-01-24 00:00:00', 15),
('9', 'j', 'Green-Rainbow', 50, 1, '2015-01-24 00:00:00', 15),
('9', 'g', 'Green-Party USA', 60, 1, '2015-01-24 00:00:00', 15),
('9', 's', 'Socialist', 70, 1, '2015-01-24 00:00:00', 15),
('9', 'o', 'Other', 80, 1, '2015-01-24 00:00:00', 15),
('13', '50', 'Up to 50', 0, 1, '0000-00-00 00:00:00', 0),
('13', '100', 'Up to 100', 10, 1, '0000-00-00 00:00:00', 0),
('13', '500', 'Up to 500', 20, 1, '0000-00-00 00:00:00', 0),
('8', '1', 'Right wild card', 0, 1, '0000-00-00 00:00:00', 0),
('8', '2', 'Soundex', 10, 1, '0000-00-00 00:00:00', 0),
('8', '0', 'Strict', 20, 1, '0000-00-00 00:00:00', 0),
('15', '', '', 40, 1, '0000-00-00 00:00:00', 0),
('15', 'a', 'Active', 10, 1, '0000-00-00 00:00:00', 0),
('15', 'i', 'Inactive', 20, 1, '0000-00-00 00:00:00', 0),
('15', 'x', 'Not Registered', 30, 1, '0000-00-00 00:00:00', 0),
('12', '', 'Pro/Con?', 30, 1, '0000-00-00 00:00:00', 0),
('12', '0', 'Pro', 10, 1, '0000-00-00 00:00:00', 0),
('12', '1', 'Con', 20, 1, '0000-00-00 00:00:00', 0),
('1', '0', 'eMail', 10, 1, '2015-02-02 11:55:19', 15),
('1', '1', 'Call', 20, 1, '2015-01-28 00:00:00', 15),
('1', '2', 'Petition', 30, 1, '2015-01-28 00:00:00', 15),
('1', '3', 'Meeting', 40, 1, '2015-01-28 00:00:00', 15),
('1', '4', 'Letter', 50, 1, '2015-01-28 00:00:00', 15),
('1', '6', 'Conversion', 75, 1, '2015-01-28 00:00:00', 15),
('1', '5', 'Web Contact', 70, 1, '2015-01-28 00:00:00', 15),
('2', '', 'Type?', 50, 1, '2015-01-26 00:00:00', 15),
('2', '0', 'Home', 10, 1, '2015-01-26 00:00:00', 15),
('2', '1', 'Work', 20, 1, '2015-01-26 00:00:00', 15),
('2', '2', 'Mail', 30, 1, '2015-01-26 00:00:00', 15),
('2', '3', 'Other', 40, 1, '2015-01-26 00:00:00', 15),
('14', 'MA', 'MA', 10, 1, '2015-01-26 00:00:00', 15),
('5', '', 'Type?', 40, 1, '2015-01-24 00:00:00', 15),
('5', '0', 'Personal', 10, 1, '2015-01-24 00:00:00', 15),
('5', '1', 'Work', 20, 1, '2015-01-24 00:00:00', 15),
('5', '2', 'Other', 30, 1, '2015-01-24 00:00:00', 15),
('4', 'cat', 'Post must have ANY of selected categories and child categories will be included.', 1, 1, '2015-01-24 00:00:00', 15),
('4', 'category__in', 'Post must have ANY of selected categories and child categories will NOT be included.', 10, 1, '2015-01-24 00:00:00', 15),
('4', 'category__and', 'Post must have ALL selected categories.', 20, 1, '2015-01-24 00:00:00', 15),
('4', 'category__not_in', 'Post must have NONE of selected categories.', 30, 1, '2015-01-24 00:00:00', 15),
('6', 'closed', 'Closed', 10, 1, '2015-01-28 00:00:00', 15),
('6', 'open', 'Open', 20, 1, '2015-01-28 00:00:00', 15),
('11', '', '', 30, 1, '0000-00-00 00:00:00', 0),
('11', 'publish', 'Public', 10, 1, '0000-00-00 00:00:00', 0),
('11', 'private', 'Private', 20, 1, '0000-00-00 00:00:00', 0),
('10', '0', 'Home', 5, 1, '2015-01-23 00:00:00', 15),
('10', '1', 'Cell', 10, 1, '2015-01-23 00:00:00', 15),
('10', '2', 'Work', 20, 1, '2015-01-23 00:00:00', 15),
('10', '3', 'Fax', 30, 1, '2015-01-23 00:00:00', 15),
('10', '4', 'Other', 40, 1, '2015-02-02 14:36:25', 15),
('10', '', 'Type?', 50, 1, '2015-01-23 00:00:00', 15),
('1', '', 'Type?', 0, 1, '2015-02-02 11:55:19', 15),
('20', '', 'N/A', 5, 1, '2015-01-24 00:00:00', 15),
('20', 'personal', 'Personal Info', 40, 1, '2015-01-24 00:00:00', 15),
('20', 'registration', 'Codes', 30, 1, '2015-01-24 00:00:00', 15),
('20', 'case', 'Case Management', 20, 1, '2015-01-24 00:00:00', 15),
('20', 'contact', 'Main Contact Group', 10, 1, '2015-01-24 00:00:00', 15),
('21', 'date', 'Date (text as yyyy-mm-dd )', 30, 1, '2015-01-25 00:00:00', 15),
('21', 'select', 'Drop Down', 20, 1, '2015-01-25 00:00:00', 15),
('21', 'text', 'Text (255 max characters)', 10, 1, '2015-01-25 00:00:00', 15),
('23', '1', 'Search with right wild card by default', 10, 1, '2015-01-25 00:00:00', 15),
('23', '0', 'Always search exact match (or range)', 20, 1, '2015-01-25 00:00:00', 15),
('24', '0', 'Disabled', 20, 1, '2015-01-26 00:00:00', 15),
('24', '1', 'Enabled', 10, 1, '2015-01-26 00:00:00', 15),
('25', 'closed', 'Never appear in issue dropdown', 30, 1, '2015-01-26 00:00:00', 15),
('25', 'open', 'Always appear in issue dropdown', 20, 1, '2015-01-26 00:00:00', 15),
('25', '', 'Appear if recent per user preferences', 10, 1, '2015-01-26 00:00:00', 15),
( '27', 'x', 'Show only the open issues', 10, 1, '0000-00-00 00:00:00', 0),
('27', 'l', 'Show open and also recently used issues', 20, 1, '0000-00-00 00:00:00', 0),
('27', 'f', 'Show open issues and also frequently used issues', 30, 1, '0000-00-00 00:00:00', 0),
('28', '1', '1', 1, 1, '0000-00-00 00:00:00', 0),
('28', '2', '2', 2, 1, '0000-00-00 00:00:00', 0),
('28', '3', '3', 3, 1, '0000-00-00 00:00:00', 0),
('28', '5', '5', 5, 1, '0000-00-00 00:00:00', 0),
('28', '7', '7', 7, 1, '0000-00-00 00:00:00', 0),
('28', '10', '10', 10, 1, '0000-00-00 00:00:00', 0),
('6', '', '', 0, 1, '2015-02-09 09:31:10', 15),
('14', '', '', 0, 1, '2015-02-09 15:46:09', 15),
('29', 'cats', 'List categories with activities for constituent download', 20, 1, '2015-02-10 13:00:00', 0),
('29', 'issues', 'List issues with activity totals', 10, 1, '2015-02-10 13:00:00', 15),
('30', 'activate_plugins', 'Administrator (activate_plugins)', 10, 1, '2015-02-10 12:00:00', 15),
('30', 'edit_others_posts', 'Editors and above (edit_others_posts)', 20, 1, '2015-02-10 00:00:00', 15),
('30', 'edit_posts', 'Authors and above (edit_posts)', 30, 1, '0000-00-00 00:00:00', 15),
('30', 'manage_wic_constituents', 'Only Constituent Managers and Administrators', 40, 1, '0000-00-00 00:00:00', 0),
('31', 'my_cases', 'Cases assigned to me', 10, 1, '0000-00-00 00:00:00', 0),
('31', 'my_issues', 'Issues assigned to me', 20, 1, '0000-00-00 00:00:00', 0),
('31', 'search_history', 'My recent activity', 30, 1, '0000-00-00 00:00:00', 0);