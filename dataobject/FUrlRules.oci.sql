CREATE table "f_url_rules" (
    "rule_id"    		NUMBER(11) NOT NULL,
    "document_id"    	NUMBER(11) NOT NULL,
    "document_lang" 	VARCHAR2(2) DEFAULT('fr'),
    "website_id"    	NUMBER(11) NOT NULL,
    "from_url" 			VARCHAR2(255) NOT NULL,
    "to_url" 			VARCHAR2(255) DEFAULT(NULL), 
    "redirect_type"    	NUMBER(11) DEFAULT(200),
    constraint "f_url_rules_PK" primary key ("rule_id"),
    constraint "f_url_rules_UK" UNIQUE ("website_id", "from_url")
)
/
CREATE sequence "f_url_rules_seq" START WITH 10000 INCREMENT BY 1