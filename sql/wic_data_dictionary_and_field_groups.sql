INSERT INTO `wp_wic_data_dictionary` (`entity_slug`, `group_slug`, `field_slug`, `field_type`, `field_label`, `field_order`, `listing_order`, `sort_clause_order`, `required`, `dedup`, `readonly`, `hidden`, `field_default`, `like_search_enabled`, `transient`, `wp_query_parameter`, `placeholder`, `option_group`, `onchange`, `list_formatter`, `reverse_sort`, `customizable`, `enabled`, `uploadable`, `upload_dedup`, `mark_deleted`, `last_updated_by`, `last_updated_time`) VALUES
( 'activity', 'activity', 'screen_deleted', 'deleted', 'x', 1, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'registration', 'ID', 'text', 'Internal Id', 420, 0, 0, '', 0, 1, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 10, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'contact', 'first_name', 'text', 'Name', 10, 10, 30, 'group', 1, 0, 0, '', 2, 0, '', 'First', '', '', '', 0, 0, 1, 20, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'contact', 'middle_name', 'text', 'Middle Name', 20, 20, 40, '', 1, 0, 0, '', 2, 0, '', 'Middle ', '', '', '', 0, 0, 1, 30, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'contact', 'last_name', 'text', 'Last Name', 30, 30, 20, 'group', 1, 0, 0, '', 2, 0, '', 'Last', '', '', '', 0, 0, 1, 40, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'contact', 'phone', 'multivalue', 'Phones', 40, 40, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', 'phone_formatter', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'contact', 'email', 'multivalue', 'Emails', 50, 50, 0, 'group', 1, 0, 0, '', 0, 0, '', '', '', '', 'email_formatter', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'contact', 'address', 'multivalue', 'Addresses', 60, 60, 0, '', 1, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'contact', 'activity', 'multivalue', 'Activities', 80, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 1, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'case', 'case_assigned', 'select', 'Staff', 110, -3, 0, '', 0, 0, 0, '', 0, 0, '', '', 'get_administrator_array', 'changeCaseStatus()', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'case', 'case_status', 'select', 'Status', 120, -2, 0, '', 0, 0, 0, '', 0, 0, '', '', 'case_status_options', '', '', 0, 0, 1, 160, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'case', 'case_review_date', 'date', 'Review Date', 130, -1, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 170, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'personal', 'date_of_birth', 'date', 'Date of Birth', 85, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 110, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'personal', 'gender', 'select', 'Gender', 90, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', 'gender_options', '', '', 0, 0, 1, 120, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'personal', 'is_deceased', 'checked', 'Deceased?', 95, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 130, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'registration', 'last_updated_time', 'date', 'Last Updated Time', 430, 0, 0, '', 0, 1, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'registration', 'last_updated_by', 'select', 'Last Updated User', 440, 0, 0, '', 0, 1, 0, '', 0, 0, '', '', 'constituent_last_updated_by', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'activity', 'activity', 'ID', 'text', 'Internal ID for Activity', 400, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'activity', 'activity', 'constituent_id', 'text', 'Constituent ID for Activity', 10, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 1, '', 0, '0000-00-00 00:00:00'),
( 'activity', 'activity', 'activity_date', 'date', 'Date', 30, 0, 10, 'individual', 0, 0, 0, 'get_today', 0, 0, '', 'Date', '', '', '', 1, 0, 1, 1005, 1, '', 0, '0000-00-00 00:00:00'),
( 'activity', 'activity', 'activity_type', 'select', 'Type', 20, 0, 0, 'individual', 0, 0, 0, '', 0, 0, '', 'Type', 'activity_type_options', '', '', 0, 0, 1, 1000, 1, '', 0, '0000-00-00 00:00:00'),
( 'activity', 'activity_amount', 'activity_amount', 'range', 'Amount', 35, 0, 0, '', 0, 0, 0, '', 0, 0, '', 'Amount', '', '', '', 0, 0, 1, 1002, 1, '', 0, '0000-00-00 00:00:00'),
( 'activity', 'activity_issue', 'issue', 'select', 'Issue', 40, 0, 0, 'individual', 0, 0, 0, '', 0, 0, '', 'Issue', 'get_issue_options', 'changeActivityIssueButtonDestination()', '', 0, 0, 1, 1020, 1, '', 0, '0000-00-00 00:00:00'),
( 'activity', 'activity_issue', 'pro_con', 'select', 'Pro or Con', 50, 0, 0, '', 0, 0, 0, '', 0, 0, '', 'Pro/Con', 'pro_con_options', '', '', 0, 0, 1, 1070, 0, '', 0, '0000-00-00 00:00:00'),
( 'activity', 'activity_note', 'activity_note', 'textarea', 'Note', 60, 0, 0, '', 0, 0, 0, '', 0, 0, '', ' . . . notes . . .', '', '', '', 0, 0, 1, 1010, 0, '', 0, '0000-00-00 00:00:00'),
( 'address', 'address_line_1', 'ID', 'text', 'Internal ID for Address', 0, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'address', 'address_line_1', 'constituent_id', 'text', 'Constituent ID for Address', 20, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 1, '', 0, '0000-00-00 00:00:00'),
( 'address', 'address_line_1', 'address_type', 'select', 'Address Type', 30, 0, 0, 'individual', 0, 0, 0, '', 0, 0, '', 'Type', 'address_type_options', '', '', 0, 0, 1, 140, 1, '', 0, '0000-00-00 00:00:00'),
( 'address', 'address_line_1', 'address_line', 'alpha', 'Street Address', 40, 0, 0, '', 1, 0, 0, '', 1, 0, '', '123 Main St', '', '', '', 0, 0, 1, 50, 0, '', 0, '0000-00-00 00:00:00'),
( 'address', 'address_line_2', 'city', 'text', 'City', 80, 100, 0, 'individual', 1, 0, 0, '', 0, 0, '', 'City', '', '', '', 0, 0, 1, 60, 0, '', 0, '0000-00-00 00:00:00'),
( 'address', 'address_line_2', 'state', 'select', 'State', 90, 0, 0, '', 1, 0, 0, '', 0, 0, '', 'State', 'state_options', '', '', 0, 0, 1, 70, 0, '', 0, '0000-00-00 00:00:00'),
( 'address', 'address_line_2', 'zip', 'text', 'Postal Code', 100, 0, 0, '', 1, 0, 0, '', 0, 0, '', 'Postal Code', '', '', '', 0, 0, 1, 80, 0, '', 0, '0000-00-00 00:00:00'),
( 'address', 'address_line_1', 'screen_deleted', 'deleted', 'x', 1, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'email', 'email_row', 'ID', 'text', 'Internal ID for Email', 0, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'email', 'email_row', 'constituent_id', 'text', 'Constituent ID for Email', 10, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 1, '', 0, '0000-00-00 00:00:00'),
( 'email', 'email_row', 'email_type', 'select', 'Email Type', 20, 0, 0, 'individual', 0, 0, 0, '', 0, 0, '', 'Type', 'email_type_options', '', '', 0, 0, 1, 145, 1, '', 0, '0000-00-00 00:00:00'),
( 'email', 'email_row', 'email_address', 'text', 'Email Address', 30, 100, 0, 'individual', 1, 0, 0, '', 1, 0, '', '', '', '', '', 0, 0, 1, 90, 0, '', 0, '0000-00-00 00:00:00'),
( 'email', 'email_row', 'screen_deleted', 'deleted', 'x', 1, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'phone', 'phone_row', 'ID', 'text', 'Internal ID for Phone', 0, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'phone', 'phone_row', 'constituent_id', 'text', 'Constituent ID for Phone', 10, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 1, '', 0, '0000-00-00 00:00:00'),
( 'phone', 'phone_row', 'phone_type', 'select', 'Phone Type', 20, 0, 0, 'individual', 0, 0, 0, '', 0, 0, '', 'Type', 'phone_type_options', '', '', 0, 0, 1, 150, 1, '', 0, '0000-00-00 00:00:00'),
( 'phone', 'phone_row', 'phone_number', 'text', 'Phone Number', 30, 100, 0, 'individual', 0, 0, 0, '', 0, 0, '', '', '', '', 'phone_number_formatter', 0, 0, 1, 100, 0, '', 0, '0000-00-00 00:00:00'),
( 'phone', 'phone_row', 'extension', 'text', 'Phone Extension', 40, 0, 0, '', 0, 0, 0, '', 0, 0, '', 'Ext.', '', '', '', 0, 0, 1, 105, 0, '', 0, '0000-00-00 00:00:00'),
( 'phone', 'phone_row', 'screen_deleted', 'deleted', 'x', 1, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'search_parms', 'retrieve_limit', 'select', '# of Constituents to Show', 10, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', 'retrieve_limit_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'search_parms', 'compute_total', 'checked', 'Show Total Count', 30, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'search_parms', 'sort_order', 'checked', 'Sort records before retrieval', 40, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'search_parms', 'match_level', 'select', 'Name Match', 20, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', 'match_level_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'contact', 'mark_deleted', 'text', 'Mark Deleted', 999, 0, 0, '', 0, 0, 0, '', 0, 0, '', 'DELETED', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'search_parms', 'show_deleted', 'checked', 'Include Deleted', 50, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_content', 'post_content', 'textarea', 'Issue Content', 20, 0, 0, '', 0, 0, 0, '', 0, 0, 'post_content', ' . . . issue content . . .', '', '', '', 0, 0, 1, 1040, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_classification', 'tags_input', 'text', 'Tags', 10, 0, 0, '', 0, 0, 0, '', 0, 0, 'tag', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_classification', 'post_category', 'multiselect', 'Categories', 20, 50, 0, 'individual', 0, 0, 0, '', 0, 0, 'cat', '', 'get_post_category_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_content', 'post_title', 'text', 'Issue Title', 10, 40, 0, 'individual', 0, 0, 0, '', 0, 0, 'post_title', '', '', '', '', 0, 0, 1, 1030, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_management', 'issue_staff', 'select', 'Staff', 10, 10, 0, '', 0, 0, 0, '', 0, 0, '', '', 'get_administrator_array', 'changeFollowUpStatus()', 'issue_staff_formatter', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'activity_open', 'wic_live_issue', 'select', 'Activity Assignment', 20, 20, 0, '', 0, 0, 0, '', 0, 0, '', '', 'wic_live_issue_options', '', 'wic_live_issue_options', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_management', 'follow_up_status', 'select', 'Status', 30, 30, 0, '', 0, 0, 0, '', 0, 0, '', '', 'follow_up_status_options', '', 'follow_up_status_options', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_management', 'review_date', 'date', 'Review Date', 40, -1, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_creation', 'post_author', 'select', 'Created By', 10, 0, 0, '', 0, 1, 0, '', 0, 0, 'author', '', 'get_post_author_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_creation', 'post_date', 'date', 'Created Date', 20, 0, 10, '', 0, 1, 0, '', 0, 0, 'date', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_creation', 'post_status', 'select', 'Visibility', 30, 60, 0, '', 0, 1, 0, '', 0, 0, 'post_status', '', 'post_status_options', '', 'post_status_options', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'issue_creation', 'ID', 'text', 'Post ID', 40, 1, 0, '', 0, 1, 0, '', 0, 0, 'p', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'ID', 'text', 'ID', 10, -1, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'user_id', 'text', 'User ID', 15, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', 'user_id_formatter', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'favorite', 'text', 'Favorite', 18, 10, 10, '', 0, 0, 0, '', 0, 0, '', '', '', '', 'favorite_formatter', 1, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'search_time', 'text', 'Search Time', 20, 20, 20, '', 0, 0, 0, '', 0, 0, '', '', '', '', 'time_formatter', 1, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'entity', 'text', 'Entity', 30, 30, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'serialized_search_array', 'text', 'Search Details', 40, 40, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', 'serialized_search_array_formatter', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'download_time', 'text', 'Last Download', 50, 50, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', 'download_time_formatter', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'search_parms', 'category_search_mode', 'radio', 'Category Search Mode', 10, 0, 0, '', 0, 0, 0, 'cat', 0, 1, '', '', 'category_search_mode_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'issue', 'search_parms', 'retrieve_limit', 'select', '# of Issues to Show', 20, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', 'retrieve_limit_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'constituent', 'save_options', 'no_dupcheck', 'checked', 'Suppress Dup Checking', 10, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'trend', 'trend', 'activity_date', 'date', 'Trend Period', 10, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'trend', 'trend', 'activity_type', 'select', 'Activity Type', 20, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', 'activity_type_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'trend', 'trend', 'last_updated_by', 'select', 'Entered By', 30, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', 'activity_last_updated_by', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_group', 'option_group', 'option_group_slug', 'text', 'Group Name', 10, 0, 10, 'individual', 1, 0, 0, '', 1, 0, '', 'no_spaces_allowed', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_group', 'option_group', 'option_group_desc', 'text', 'Description', 20, 20, 0, '', 0, 0, 0, '', 1, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_group', 'option_group', 'enabled', 'select', 'Enabled', 30, 30, 0, '', 1, 0, 0, '1', 0, 0, '', '', 'enabled_disabled_array', '', 'enabled_disabled_array', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_value', 'option_value', 'screen_deleted', 'deleted', 'x', 1, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_value', 'option_value', 'ID', 'text', 'Internal ID for Option Value', 400, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_value', 'option_value', 'option_group_id', 'text', 'Option Group ID', 10, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_value', 'option_value', 'option_value', 'text', 'Database', 20, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_value', 'option_value', 'option_label', 'text', 'Visible', 30, 10, 0, '', 0, 0, 0, '', 0, 0, '', 'Visible in drop down.', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_value', 'option_value', 'value_order', 'text', 'Order', 40, 0, 10, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_value', 'option_value', 'enabled', 'select', 'Enabled', 50, 0, 0, '', 0, 0, 0, '1', 0, 0, '', '', 'enabled_disabled_array', '', 'enabled_disabled_array', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_group', 'option_group', 'ID', 'text', 'Internal ID for OptionGroup', 5, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_group', 'option_group', 'option_value', 'multivalue', 'Option Values', 40, 40, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', 'option_label_list_formatter', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_group', 'option_group', 'last_updated_by', 'select', 'Last Updater', 60, 0, 0, '', 0, 1, 0, '', 0, 0, '', '', 'constituent_last_updated_by', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'option_group', 'option_group', 'last_updated_time', 'text', 'Last Updated', 70, 0, 0, '', 0, 1, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'entity_slug', 'text', 'entity_slug', 1, 0, 0, '', 0, 1, 1, 'constituent', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'group_slug', 'select', 'Screen Group ', 30, 10, 10, 'individual', 0, 0, 0, '', 0, 0, '', '', 'customizable_groups', '', 'customizable_groups', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'field_slug', 'text', 'System Name', 10, 0, 0, '', 0, 1, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'field_type', 'select', 'Field Type', 60, 40, 0, 'individual', 0, 0, 0, '', 0, 0, '', '', 'custom_field_types', '', 'custom_field_types', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'field_label', 'text', 'Visible Name', 20, 30, 20, 'individual', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'field_default', 'text', 'Default', 50, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'readonly', 'checked', 'Read Only', 90, 0, 0, '', 0, 0, 0, '0', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'option_group', 'select', 'Option Group ', 70, 50, 0, '', 0, 0, 0, '', 0, 0, '', '', 'list_option_groups', '', 'decode_option_groups', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'list_formatter', 'text', 'List Formatter', 80, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'enabled', 'select', 'Enabled', 100, 80, 0, '', 0, 0, 0, '1', 0, 0, '', '', 'enabled_disabled_array', '', 'enabled_disabled_array', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'field_order', 'text', 'Screen Order', 40, 0, 0, 'individual', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'ID', 'text', 'Internal ID for Field', 2, 0, 0, '', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'data_dictionary', 'data_dictionary', 'like_search_enabled', 'select', 'Search', 84, 0, 0, '', 0, 0, 0, '1', 0, 0, '', '', 'like_search', '', 'like_search', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'result_count', 'text', 'Result Count', 110, 45, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'user', 'user', 'max_issues_to_show', 'select', 'How many?', 50, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', 'count_to_ten', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'user', 'userid', 'ID', 'text', 'Wordpress User ID', 10, 0, 0, 'individual', 0, 0, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'user', 'user', 'display_name', 'text', 'User ', 20, 0, 0, '', 0, 1, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'user', 'user', 'show_viewed_issue', 'checked', 'Last viewed', 30, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'user', 'user', 'show_latest_issues', 'select', 'Last Used', 40, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', 'show_latest_issues_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'search_log', 'search_log', 'serialized_search_parameters', 'text', 'Serialized Search Parameters', 100, 100, 0, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'trend', 'trend', 'trend_search_mode', 'select', 'Search Mode', 50, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', 'trend_search_modes', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'user', 'startup', 'first_form', 'select', 'Startup Form', 60, 0, 0, '', 0, 0, 0, 'search_history', 0, 0, '', '', 'first_form', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'ID', 'text', 'Internal Id for Upload', 0, 0, 0, '', 0, 1, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'upload_time', 'text', 'Upload Time', 10, 10, 10, '', 0, 1, 0, '', 0, 0, '', '', '', '', '', 1, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'upload_by', 'select', 'Upload User', 20, 20, 20, '', 0, 1, 0, '', 0, 0, '', '', 'get_administrator_array', '', 'issue_staff_formatter', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'upload_description', 'textarea', 'Description', 30, 30, 30, '', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'upload_file', 'file', 'File', 25, 25, 25, 'individual', 0, 0, 0, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'upload_status', 'select', 'Status', 35, 35, 35, '', 0, 1, 0, '', 0, 0, '', '', 'upload_status', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'serialized_upload_parameters', 'textarea', '', 40, 0, 0, '', 0, 1, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'serialized_column_map', 'textarea', '', 50, 0, 0, '', 0, 1, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'serialized_match_results', 'textarea', '', 60, 0, 0, '', 0, 1, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'serialized_default_decisions', 'textarea', '', 70, 0, 0, '', 0, 1, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'initial', 'serialized_final_results', 'textarea', '', 80, 0, 0, '', 0, 1, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'save_options', 'includes_column_headers', 'checked', 'Has column headers', 5, 0, 0, '', 0, 0, 0, '1', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'save_options', 'max_execution_time', 'text', 'Max execution time for this upload', 100, 0, 0, '', 0, 0, 0, '1200', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'save_options', 'delimiter', 'radio', 'Delimiter (character between fields)', 20, 0, 0, '', 0, 0, 0, 'comma', 0, 1, '', '', 'delimiter_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'save_options', 'enclosure', 'radio', 'Enclosure (character enclosing fields that might include the delimiter)', 30, 0, 0, '', 0, 0, 0, '2', 0, 1, '', '', 'enclosure_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'save_options', 'escape', 'text', 'Escape (character indicating that next delimiter or enclosure character should be read literally)', 40, 0, 0, '', 0, 0, 0, '\\', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'save_options', 'max_line_length', 'text', 'Max line length (sum of lengths of data in all fields in the input file row)', 50, 0, 0, '', 0, 0, 0, '5000', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'activity', 'activity_date', 'date', 'Activity Date', 10, 0, 0, '', 0, 0, 0, 'get_today', 0, 1, '', 'Date', '', '', '', 1, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'activity', 'activity_type', 'select', 'Activity Type', 20, 0, 0, '', 0, 0, 0, '', 0, 1, '', 'Type', 'activity_type_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'activity', 'issue', 'select', 'Activity Issue', 40, 0, 0, '', 0, 0, 0, '', 0, 1, '', 'Issue', 'get_issue_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'activity', 'pro_con', 'select', 'Activity Pro/Con', 30, 0, 0, '', 0, 0, 0, '', 0, 1, '', 'Pro/Con', 'pro_con_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'constituent_match', 'update_matched', 'checked', 'Update matched constituents (uncheck to skip matched):', 20, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'constituent_match', 'add_unmatched', 'checked', 'Add unmatched constituents (uncheck to skip unmatched):', 10, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'constituent_match', 'protect_identity', 'checked', 'Protect primary constituent data and address (leave checked when soft matching):', 30, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'constituent_match', 'protect_blank_overwrite', 'checked', 'Protect all fields from being overwritten by blank input (leave checked for most uploads):', 35, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'address', 'address_type', 'select', 'Address type ', 30, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', 'address_type_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'address', 'city', 'text', 'City/town ', 40, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'address', 'state', 'select', 'State ', 50, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', 'state_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'address', 'zip', 'text', 'Postal code ', 60, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'email', 'email_type', 'select', 'Email type ', 70, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', 'email_type_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'phone', 'phone_type', 'select', 'Phone type ', 80, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', 'phone_type_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'upload', 'new_issue_creation', 'create_issues', 'checked', 'Create new issues from unmatched non-blank titles (check to accept or go back and unmap titles):', 60, 0, 0, '', 0, 0, 0, '', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'manage_storage', 'staging', 'keep_staging', 'checked', 'Keep temporary upload files', 20, 0, 0, '', 0, 0, 0, '1', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'manage_storage', 'search', 'keep_search', 'checked', 'Keep search log', 10, 0, 0, '', 0, 0, 0, '1', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'manage_storage', 'constituent', 'keep_activity', 'checked', 'Keep if activity', 30, 0, 0, '', 0, 0, 0, '1', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'manage_storage', 'constituent', 'keep_email', 'checked', 'Keep if email address', 40, 0, 0, '', 0, 0, 0, '1', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'manage_storage', 'constituent', 'keep_phone', 'checked', 'Keep if phone number', 50, 0, 0, '', 0, 0, 0, '1', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'manage_storage', 'constituent', 'keep_address', 'checked', 'Keep if physical address', 60, 0, 0, '', 0, 0, 0, '1', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'manage_storage', 'constituent', 'keep_all', 'checked', 'Keep all constituents', 65, 0, 0, '', 0, 0, 0, '1', 0, 1, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'manage_storage', 'constituent', 'confirm', 'text', 'Type PURGE CONSTITUENT DATA in all caps to confirm constituent purge.', 70, 0, 0, '', 0, 0, 0, '', 0, 1, '', 'enter exactly as shown', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'list', 'list', 'search_id', 'text', '', 0, 0, 0, '', 0, 1, 1, '', 0, 0, '', '', '', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00'),
( 'list', 'list', 'wic-post-export-button', 'select', 'Type', 10, 0, 0, '', 0, 0, 0, '', 0, 0, '', '', 'download_options', '', '', 0, 0, 1, 0, 0, '', 0, '0000-00-00 00:00:00');
INSERT INTO wp_wic_form_field_groups ( entity_slug, group_slug, group_label, group_legend, group_order, initial_open, sidebar_location, last_updated_time, last_updated_by) VALUES
( 'constituent', 'contact', 'Contact', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'constituent', 'case', 'Case Management', '', 30, 1, 1, '0000-00-00 00:00:00', 0),
( 'constituent', 'personal', 'Personal Info', '', 40, 0, 1, '0000-00-00 00:00:00', 0),
( 'constituent', 'registration', 'Codes', 'These fields are read only -- searchable, but not updateable.', 50, 0, 1, '0000-00-00 00:00:00', 0),
( 'activity', 'activity_note', 'Activity Note', '', 20, 0, 0, '0000-00-00 00:00:00', 0),
( 'address', 'address_line_1', 'Address Line 1', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'address', 'address_line_2', 'Address Line 2', '', 20, 0, 0, '0000-00-00 00:00:00', 0),
( 'email', 'email_row', 'Email Row', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'phone', 'phone_row', 'Phone Row', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'constituent', 'search_parms', 'Search Options', '\n\n', 25, 1, 1, '0000-00-00 00:00:00', 0),
( 'activity', 'activity', '', '', 10, 0, 0, '0000-00-00 00:00:00', 0),
( 'activity', 'activity_amount', '', '', 12, 0, 0, '0000-00-00 00:00:00', 0),
( 'activity', 'activity_issue', '', '', 15, 0, 0, '0000-00-00 00:00:00', 0),
( 'issue', 'issue_content', 'Issue Content', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'issue', 'issue_classification', 'Classification', '', 20, 1, 0, '0000-00-00 00:00:00', 0),
( 'issue', 'issue_management', 'Issue Management', '', 30, 1, 1, '0000-00-00 00:00:00', 0),
( 'issue', 'issue_creation', 'Codes', 'These fields are not updateable except through the regular Wordpress admin screens.', 40, 1, 1, '0000-00-00 00:00:00', 0),
( 'constituent', 'comment', 'Latest Online Comments', 'Note: If the online user''s email is not in WP-Issues-CRM, the online activity will not be shown here.  Online activity shown here can only be altered through the WP backend.  ', 20, 0, 0, '0000-00-00 00:00:00', 0),
( 'comment', 'comment', 'Online Comments', '', 10, 0, 0, '0000-00-00 00:00:00', 0),
( 'issue', 'search_parms', 'Search Options', 'You can select options for the categories search. Note: Tags are always joined by OR. Conditions collectively are always joined by ''AND''.', 25, 10, 1, '0000-00-00 00:00:00', 0),
( 'constituent', 'save_options', 'Save Options', '', 27, 1, 1, '0000-00-00 00:00:00', 0),
( 'issue', 'activity_open', 'Activity Tracking', 'When should issue appear in dropdown for entering activities', 25, 1, 1, '0000-00-00 00:00:00', 0),
( 'trend', 'trend', 'Activity Trends', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'option_group', 'option_group', 'Option Groups', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'option_value', 'option_value', 'Option Values', '', 0, 0, 0, '0000-00-00 00:00:00', 0),
( 'data_dictionary', 'data_dictionary', 'Customizable Fields', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'user', 'user', 'Activity Dropdown', 'Select what issues will show as options when adding new activities.  ', 5, 1, 0, '0000-00-00 00:00:00', 0),
( 'data_dictionary', 'current_field_config', 'Existing Groups and Fields', 'Refer to the list of groups and enabled fields on the constituent form to choose where to position your custom field.', 20, 1, 0, '0000-00-00 00:00:00', 0),
( 'option_group', 'current_option_group_usage', 'Existing Database Values for this Option', 'Refer to the listing below of actually used option values for this option group when modifying the option value list.', 90, 1, 0, '0000-00-00 00:00:00', 0),
( 'user', 'startup', 'Startup Screen', 'Select screen to start on.', 3, 1, 0, '0000-00-00 00:00:00', 0),
( 'user', 'userid', '', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'initial', 'Initial Upload', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'upload_tips', 'Upload Tips', '', 20, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'upload_settings', 'System Settings', 'The system settings below can be adjusted with the assistance of your hosting provider.
  				To upload	successfully, file_uploads must be "on" and size parameters must exceed your file size.
				Input time relates to your connection speed.  Execution time relates to the work done in storing
				the uploaded file to a temporary staging table within your database. WP Issues
				CRM is able to alter max_execution_time dynamically in many installations. Generally, you should not have 
				problems with your memory_limit while uploading files with WP Issues CRM.', 40, 1, 1, '0000-00-00 00:00:00', 0),
( 'upload', 'upload_parameters', 'Upload Parameters', '', 20, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'save_options', 'File/Upload Settings', '', 15, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'mappable', 'Mappable Fields', '', 30, 1, 1, '0000-00-00 00:00:00', 0),
( 'upload', 'summary_results', 'Constituent matching results', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'constituent_match', 'Constituent add/update choices', '', 20, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'constituent', 'Constituent default values', 'The constituent fields below have not been mapped. You can set defaults for them.', 30, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'address', '', '', 32, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'phone', '', '', 34, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'email', '', '', 36, 1, 0, '0000-00-00 00:00:00', 0),
( 'upload', 'activity', 'Activity default values ', 'The activity fields below have not been mapped. You can set default values for them.  
If any activity fields are mapped or defaulted, an activity record will be created from each record in the input file.', 
40, 1, 1, '0000-00-00 00:00:00', 0),
( 'upload', 'issue', '', '', 45, 1, 1, '0000-00-00 00:00:00', 0),
( 'upload', 'new_issue_creation', 'New Issue Titles ', 'You have mapped an issue title field and you have not mapped an Activity Issue field or set a default Activity Issue. 
If you wish, WP Issues CRM will create new posts/issues, using the titles and content that you have mapped.  Each constituent record will also
get an activity created under the corresponding new issue. You must either check to accept this or change other mapping or default settings
to make the use of the titles to create issues unnecessary.', 50, 1, 1, '0000-00-00 00:00:00', 0),
( 'manage_storage', 'statistics', 'Storage Statistics', '', 10, 1, 0, '0000-00-00 00:00:00', 0),
( 'manage_storage', 'staging', 'Purge Upload Files', 'Purge temporary upload files -- interim staging tables and history for uploads.  Will NOT backout uploaded data.', 20, 1, 1, '0000-00-00 00:00:00', 0),
( 'manage_storage', 'search', 'Purge Search Log', 'Purge records of previous search activity.', 25, 1, 1, '0000-00-00 00:00:00', 0),
( 'manage_storage', 'constituent', 'Purge Constituents', 'Purge constituent data -- check to keep constituents with specified data categories. 
	All other constituents will be purged.  For example, if you check \"Keep if activity\" and \"Keep if email address\", then only constituents that have either an activity or an email address will be retained.
	All lacking both activity or email address be purged.  Uncheck \"Keep all constituents\" and enter confirmation phrase to confirm constituent purge.
	Note that the entire search log is always purged when any constituents are purged.', 30, 1, 1, '0000-00-00 00:00:00', 0),
( 'list', 'list', 'List Transients', '', 10, 0, 0, '0000-00-00 00:00:00', 0);