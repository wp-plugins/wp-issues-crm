INSERT INTO wp_wic_option_group ( option_group_slug, option_group_desc, enabled, last_updated_time, last_updated_by, mark_deleted, is_system_reserved) VALUES
( 'activity_or_constituent', 'Advanced Search Row Retrieval Options', 1, '0000-00-00 00:00:00', 0, '', 1),
( 'activity_and_or_constituent', 'Advanced Search Criterion Combination Options', 1, '0000-00-00 00:00:00', 0, '', 1),
( 'constituent_and_or', 'Advanced Search Constituent Criterion Combination Options', 1, '0000-00-00 00:00:00', 0, '', 1),
( 'activity_and_or', 'Advanced Search Activity Criterion Combination Options', 1, '0000-00-00 00:00:00', 0, '', 1),
( 'constituent_having_and_or', 'Advanced Search Having Criterion Combination Options', 1, '0000-00-00 00:00:00', 0, '', 1),
( 'advanced_search_comparisons', 'Advanced Search Comparison Operators', 1, '0000-00-00 00:00:00', 0, '', 1),
( 'advanced_search_having_aggregators', 'Advanced Search Aggregation Operators', 1, '0000-00-00 00:00:00', 0, '', 1);
INSERT INTO wp_wic_option_value ( parent_option_group_slug, option_value, option_label, value_order, enabled, last_updated_time, last_updated_by) VALUES
( 'activity_or_constituent', 'constituent', 'Retrieve constituents', 10, 1, '0000-00-00 00:00:00', 0),
( 'activity_or_constituent', 'activity', 'Retrieve activities', 20, 1, '0000-00-00 00:00:00', 0),
( 'activity_and_or_constituent', 'and', 'Rows retrieved must meet both constituent and activity criteria', 10, 1, '0000-00-00 00:00:00', 0),
( 'activity_and_or_constituent', 'or', 'Rows retrieved may meet either constituent or activity criteria', 20, 1, '0000-00-00 00:00:00', 0),
( 'constituent_and_or', 'and', 'Require all constituent criteria', 10, 1, '0000-00-00 00:00:00', 0),
( 'constituent_and_or', 'or', 'Require any constituent criterion', 20, 1, '0000-00-00 00:00:00', 0),
( 'constituent_and_or', 'and NOT', 'All constituent criteria FALSE', 30, 1, '0000-00-00 00:00:00', 0),
( 'constituent_and_or', 'or NOT', 'Any constituent criterion FALSE', 40, 1, '0000-00-00 00:00:00', 0),
( 'activity_and_or', 'and', 'Require all activity criteria', 10, 1, '0000-00-00 00:00:00', 0),
( 'activity_and_or', 'or', 'Require any activity criteria', 20, 1, '0000-00-00 00:00:00', 0),
( 'activity_and_or', 'and NOT', 'All activity criteria FALSE', 30, 1, '0000-00-00 00:00:00', 0),
( 'activity_and_or', 'or NOT', 'Any activity criteria FALSE', 40, 1, '0000-00-00 00:00:00', 0),
( 'constituent_having_and_or', 'and', 'Require all constitute aggregate criteria', 10, 1, '0000-00-00 00:00:00', 0),
( 'constituent_having_and_or', 'or', 'Require any constitute aggregate criteria', 20, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', '=', 'Equals', 10, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', '>=', 'Is greater than or equal to', 20, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', '<=', 'Is less than or equal to', 30, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'LIKE', 'Begins with', 40, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'SCAN', 'Contains (caution: slow search)', 40, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'BLANK', 'Is blank', 50, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'NOT_BLANK', 'Is not blank', 60, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'IS_NULL', 'Does not exist (IS NULL)', 70, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'CATEGORY_ANY_KIDS', 'Any selected and descendants', 80, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'CATEGORY_ANY_NO_KIDS', 'Any selected but not descendants', 83, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'CATEGORY_ALL', 'All selected ', 87, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_comparisons', 'CATEGORY_NONE', 'None of selected', 90, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_having_aggregators', 'AVG', 'Average', 10, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_having_aggregators', 'COUNT', 'Count', 20, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_having_aggregators', 'MAX', 'Maximum', 30, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_having_aggregators', 'MIN', 'Maximum', 40, 1, '0000-00-00 00:00:00', 0),
( 'advanced_search_having_aggregators', 'SUM', 'Sum', 50, 1, '0000-00-00 00:00:00', 0);