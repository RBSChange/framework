CREATE TABLE "f_locale" (
  "id" VARCHAR2(255) NOT NULL,
  "lang" char(2) NOT NULL,
  "content" CLOB DEFAULT(EMPTY_CLOB()),
  "originalcontent" CLOB DEFAULT(EMPTY_CLOB()),
  "package" VARCHAR2(255),
  "overridden" NUMBER(1) default(0),
  "overridable" NUMBER(1) default(0),
  "useredited" NUMBER(1) NULL,
  constraint "f_locale_PK" PRIMARY KEY ("id", "lang")
)
