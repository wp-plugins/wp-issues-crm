INSERT INTO wp_wic_option_group ( option_group_slug, option_group_desc, enabled, last_updated_time, last_updated_by, mark_deleted, is_system_reserved) VALUES
( 'advanced_search_checked_substitute', 'Option Set for Checked Field Substitute', 1, '0000-00-00 00:00:00', 0, '', 1);
INSERT INTO wp_wic_option_value ( parent_option_group_slug, option_value, option_label, value_order, enabled, last_updated_time, last_updated_by) VALUES
( 'advanced_search_checked_substitute', '0', 'Is NOT', 10, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_checked_substitute', '1', 'IS', 20, 1, '0000-00-00 00:00:00', 0);