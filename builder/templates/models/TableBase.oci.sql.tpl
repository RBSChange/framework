CREATE TABLE "<{$model->getTableName()}>" (
	"document_id" NUMBER(11) NOT NULL,
	"document_model" VARCHAR2(50) NOT NULL,
<{foreach from=$model->getTableField() item=property}>
	<{$property->generateSql('oci')}>,
<{/foreach}>
	constraint "<{$model->getTableName()}>pk" primary key ("document_id")
)