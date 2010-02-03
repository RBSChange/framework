CREATE TABLE "f_tree" (
  "tree_id" NUMBER(11) default(0) NOT NULL,
  "tree_left" NUMBER(11) default(0) NOT NULL,
  "tree_right" NUMBER(11) default(0) NOT NULL,
  "tree_level" NUMBER(11) default(0) NOT NULL,
  "document_id" NUMBER(11) NOT NULL,
  constraint "f_tree_PK" primary key ("document_id"),
  constraint "f_tree_uK" UNIQUE ("tree_id", "tree_left", "tree_right")
)
