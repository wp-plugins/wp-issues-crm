

CREATE TABLE wp_wic_activity (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  constituent_id bigint(20) unsigned NOT NULL,
  activity_date varchar(10) NOT NULL,
  activity_type varchar(255) DEFAULT NULL,
  issue bigint(20) NOT NULL COMMENT 'post_id for associated issue',
  pro_con varchar(255) NOT NULL,
  activity_note text NOT NULL,
  last_updated_time datetime NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  PRIMARY KEY (ID),
  KEY constituent_id (constituent_id),
  KEY activity_type (activity_type),
  KEY activity_date (activity_date),
  KEY last_updated_time (last_updated_time),
  KEY last_updated_by (last_updated_by)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_address (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  constituent_id bigint(20) unsigned NOT NULL,
  address_type varchar(255) DEFAULT NULL,
  address_line varchar(50) NOT NULL,
  address_line_alpha varchar(50) NOT NULL,
  city varchar(20) NOT NULL,
  state varchar(20) NOT NULL,
  zip varchar(10) NOT NULL,
  last_updated_time datetime NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  PRIMARY KEY (ID),
  KEY street_name (address_line_alpha),
  KEY zip (zip),
  KEY address_line (address_line),
  KEY constituent_id (constituent_id),
  KEY city (city),
  KEY state (state),
  KEY last_updated_time (last_updated_time),
  KEY last_updated_by (last_updated_by)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_constituent (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  last_name varchar(30) NOT NULL,
  last_name_soundex varchar(15) NOT NULL,
  first_name varchar(20) NOT NULL,
  first_name_soundex varchar(10) NOT NULL,
  middle_name varchar(20) NOT NULL,
  middle_name_soundex varchar(10) NOT NULL,
  date_of_birth varchar(10) NOT NULL,
  is_deceased tinyint(1) NOT NULL,
  mark_deleted varchar(7) NOT NULL,
  case_assigned varchar(10) NOT NULL,
  case_review_date varchar(10) NOT NULL,
  case_status varchar(255) NOT NULL,
  gender varchar(255) NOT NULL,
  last_updated_time datetime NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  PRIMARY KEY (ID),
  KEY last_name (last_name),
  KEY middle_name (middle_name),
  KEY dob (date_of_birth),
  KEY gender (gender),
  KEY first_name (first_name),
  KEY is_deceased (is_deceased),
  KEY is_deleted (mark_deleted),
  KEY assigned (case_assigned),
  KEY case_review_date (case_review_date),
  KEY case_status (case_status),
  KEY fnln (last_name,first_name),
  KEY first_name_soundex (first_name_soundex),
  KEY last_name_soundex (last_name_soundex),
  KEY middle_name_soundex (middle_name_soundex),
  KEY soundex (mark_deleted,last_name_soundex,first_name_soundex),
  KEY last_updated_time (last_updated_time),
  KEY last_updated_by (last_updated_by),
  KEY last_updated_time_2 (last_updated_time),
  KEY last_updated_by_2 (last_updated_by)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_data_dictionary (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  entity_slug varchar(20) NOT NULL,
  group_slug varchar(30) NOT NULL,
  field_slug varchar(30) NOT NULL,
  field_type varchar(30) NOT NULL,
  field_label varchar(60) NOT NULL,
  field_order mediumint(9) NOT NULL,
  listing_order int(11) NOT NULL,
  sort_clause_order mediumint(11) NOT NULL,
  required varchar(10) NOT NULL,
  dedup tinyint(1) NOT NULL,
  readonly tinyint(1) NOT NULL,
  hidden tinyint(1) NOT NULL,
  field_default varchar(30) NOT NULL,
  like_search_enabled tinyint(1) NOT NULL,
  transient tinyint(1) NOT NULL,
  wp_query_parameter varchar(30) NOT NULL,
  placeholder varchar(50) NOT NULL,
  option_group varchar(50) NOT NULL,
  onchange varchar(40) NOT NULL,
  list_formatter varchar(50) NOT NULL,
  reverse_sort tinyint(1) NOT NULL DEFAULT '0',
  customizable tinyint(1) NOT NULL DEFAULT '0',
  enabled tinyint(1) NOT NULL DEFAULT '1',
  mark_deleted varchar(10) NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  last_updated_time datetime NOT NULL,
  PRIMARY KEY (ID),
  KEY entity_slug (entity_slug),
  KEY field_group (group_slug)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_email (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  constituent_id bigint(20) unsigned NOT NULL,
  email_type varchar(255) DEFAULT NULL,
  email_address varchar(254) DEFAULT NULL COMMENT 'Email address',
  last_updated_time datetime NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  PRIMARY KEY (ID),
  KEY constituent_id (constituent_id),
  KEY email_address (email_address),
  KEY email_type (email_type),
  KEY last_updated_time (last_updated_time),
  KEY last_updated_by (last_updated_by)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_form_field_groups (
  ID bigint(20) NOT NULL AUTO_INCREMENT,
  entity_slug varchar(30) NOT NULL,
  group_slug varchar(30) NOT NULL,
  group_label varchar(40) NOT NULL,
  group_legend text NOT NULL,
  group_order smallint(6) NOT NULL DEFAULT '0',
  initial_open tinyint(1) NOT NULL,
  sidebar_location tinyint(1) NOT NULL,
  last_updated_time datetime NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  PRIMARY KEY (ID)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_option_group (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  option_group_slug varchar(30) NOT NULL,
  option_group_desc varchar(100) NOT NULL,
  enabled tinyint(1) NOT NULL DEFAULT '1',
  last_updated_time datetime NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  mark_deleted varchar(10) NOT NULL,
  is_system_reserved tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (ID)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_option_value (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  option_group_id varchar(50) NOT NULL,
  option_value varchar(50) NOT NULL,
  option_label varchar(200) NOT NULL,
  value_order smallint(11) NOT NULL,
  enabled tinyint(1) NOT NULL,
  last_updated_time datetime NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  PRIMARY KEY (ID),
  KEY enabled (enabled,option_group_id,value_order)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_phone (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  constituent_id bigint(20) unsigned NOT NULL,
  phone_type varchar(255) DEFAULT NULL,
  phone_number varchar(15) DEFAULT NULL,
  extension varchar(10) NOT NULL,
  last_updated_time datetime NOT NULL,
  last_updated_by bigint(20) NOT NULL,
  PRIMARY KEY (ID),
  KEY constituent_id (constituent_id),
  KEY email_address (phone_number),
  KEY email_type (phone_type),
  KEY last_updated_time (last_updated_time),
  KEY last_updated_by (last_updated_by)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE wp_wic_search_log (
  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) NOT NULL,
  search_time varchar(20) NOT NULL,
  entity varchar(30) NOT NULL,
  serialized_search_array text NOT NULL,
  download_time varchar(20) NOT NULL,
  serialized_search_parameters text NOT NULL,
  result_count bigint(20) NOT NULL,
  PRIMARY KEY (ID),
  KEY user_entity_time (user_id,entity,search_time),
  KEY user_time (user_id,search_time)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

