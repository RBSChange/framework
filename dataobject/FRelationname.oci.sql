CREATE TABLE "f_relationname" (
"relation_id" NUMBER(11) NOT NULL,
"property_name" VARCHAR2(50) NOT NULL,
constraint "f_relationname_PK" primary key ("relation_id")
)
/
CREATE sequence "f_relationname_seq" START WITH 10000 INCREMENT BY 1