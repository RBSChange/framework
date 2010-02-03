CREATE TABLE "<{$model->getTableName()}>_i18n" (
	"document_id" NUMBER(11) NOT NULL,
	"lang_i18n" CHAR(2) default('fr') NOT NULL ,
<{foreach from=$model->getTableI18nField() item=property}>
	<{$property->generateSql('oci', true)}>,
<{/foreach}>
	constraint "<{$model->getTableName()}>_i18npk" primary key ("document_id", "lang_i18n")
)