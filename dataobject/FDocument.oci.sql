CREATE table "f_document" (
    "document_id"    NUMBER(11) NOT NULL,
    "document_model" VARCHAR2(50) NOT NULL,
    "lang_vo" VARCHAR2(2) DEFAULT(''),
    constraint "f_document_PK" primary key ("document_id")
)
/
CREATE sequence "f_document_seq" START WITH 10000 INCREMENT BY 1