CREATE TABLE IF NOT EXISTS f_locale (
  id char(255) NOT NULL,
  lang char(2) NOT NULL,
  content TEXT /* CHARACTER SET utf8 COLLATE utf8_general_ci */ NULL DEFAULT NULL,
  originalcontent TEXT /* CHARACTER SET utf8 COLLATE utf8_general_ci */ NULL DEFAULT NULL,
  package varchar(255),
  overridden int,
  overridable int,
  useredited INT( 11 ) NULL, 
  PRIMARY KEY (id, lang)
) TYPE=MyISAM CHARACTER SET utf8 COLLATE utf8_bin;