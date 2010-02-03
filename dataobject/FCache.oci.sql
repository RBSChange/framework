CREATE TABLE "f_cache" (
	"cache_key" NUMBER(11) NOT NULL,
	"text_value" CLOB DEFAULT(EMPTY_CLOB()),
	constraint "f_cache_PK" primary key ("cache_key")
)