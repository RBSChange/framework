CREATE TABLE "f_settings" (
  "name" varchar2(50) NOT NULL,
  "package" varchar2(255) NOT NULL,
  "userid" NUMBER(11) default(0) NOT NULL,
  "value"  CLOB DEFAULT(EMPTY_CLOB()),
  constraint "f_settings_PK" PRIMARY KEY  ("name", "package", "userid")
)