CREATE TABLE "f_simplecache_registration" (
  "pattern" VARCHAR2(255) NOT NULL,
  "cache_id" VARCHAR2(255) NOT NULL,
  constraint "f_simplecache_registration_PK" primary key ("pattern", "cache_id")
)