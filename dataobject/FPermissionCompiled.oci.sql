CREATE TABLE "f_permission_compiled" (
  "accessor_id" NUMBER(11) NOT NULL,
  "permission" VARCHAR2(100) NOT NULL,
  "node_id" NUMBER(11) NOT NULL,
  constraint "f_permission_compiled_PK" PRIMARY KEY ("accessor_id","permission","node_id")
)
