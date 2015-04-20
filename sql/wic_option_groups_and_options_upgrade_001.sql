INSERT INTO wp_wic_option_group ( option_group_slug, option_group_desc, enabled, last_updated_time, last_updated_by, mark_deleted, is_system_reserved) VALUES
( 'delimiter_options', 'Upload file columns delimited by', 1, '0000-00-00 00:00:00', 0, '', 1),
( 'enclosure_options', 'Upload file columns enclosed by', 1, '0000-00-00 00:00:00', 0, '', 1),
( 'upload_status', 'Upload Status', 1, '0000-00-00 00:00:00', 0, '', 1);
INSERT INTO wp_wic_option_value ( parent_option_group_slug, option_value, option_label, value_order, enabled, last_updated_time, last_updated_by) VALUES
( 'delimiter_options', 'comma', 'Comma (common in .csv files)', 10, 1, '0000-00-00 00:00:00', 0),
( 'delimiter_options', 'semi', 'Semi-Colon (sometimes used in .csv files)', 20, 1, '0000-00-00 00:00:00', 0),
( 'delimiter_options', 'tab', 'Tab (common in .txt files)', 30, 1, '0000-00-00 00:00:00', 0),
( 'delimiter_options', 'space', 'Space', 40, 1, '0000-00-00 00:00:00', 0),
( 'delimiter_options', 'colon', 'Colon', 50, 1, '0000-00-00 00:00:00', 0),
( 'delimiter_options', 'hyphen', 'Hyphen (-)', 60, 1, '0000-00-00 00:00:00', 0),
( 'enclosure_options', '1', 'Single Quote (\')', 20, 1, '0000-00-00 00:00:00', 0),
( 'enclosure_options', '2', 'Double Quote (\")', 10, 1, '0000-00-00 00:00:00', 0),
( 'enclosure_options', 'b', 'Back Tick (\`)', 30, 1, '0000-00-00 00:00:00', 0),
( 'upload_status', 'staged', 'Staging Table Loaded', 10, 1, '0000-00-00 00:00:00', 0),
( 'upload_status', 'mapped', 'Fields Mapped', 20, 1, '0000-00-00 00:00:00', 0),
( 'upload_status', 'validated', 'Data Validated', 30, 1, '0000-00-00 00:00:00', 0),
( 'upload_status', 'matched', 'Records Matched', 40, 1, '0000-00-00 00:00:00', 0),
( 'upload_status', 'defaulted', 'Valid default decisions', 50, 1, '0000-00-00 00:00:00', 0),
( 'upload_status', 'started', 'Upload Started, not completed', 60, 1, '0000-00-00 00:00:00', 0),
( 'upload_status', 'completed', 'Upload Completed', 70, 1, '0000-00-00 00:00:00', 0),
( 'upload_status', 'reversed', 'Upload Backed Out', 80, 1, '0000-00-00 00:00:00', 0);