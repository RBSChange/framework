CREATE TABLE "f_relation" (
  "relation_id1" NUMBER(11) NOT NULL,
  "relation_id2" NUMBER(11) NOT NULL,
  "relation_order" NUMBER(11) NOT NULL,
  "relation_name" VARCHAR2(50) NOT NULL,
  "document_model_id1" VARCHAR2(50) NOT NULL,
  "document_model_id2" VARCHAR2(50) NOT NULL,
  "relation_id" NUMBER(11) NOT NULL,
  constraint "f_relation_PK" primary key  ("relation_id1" , "relation_id" , "relation_order")
)