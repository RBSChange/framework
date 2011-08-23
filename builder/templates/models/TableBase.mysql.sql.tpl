CREATE TABLE IF NOT EXISTS `<{$model->getTableName()}>` (
	`document_id` int(11) NOT NULL default '0',
	`document_model` varchar(50) NOT NULL default '',
<{foreach from=$model->getTableField() item=property}>
	<{$property->generateSql('mysql')}>,
<{/foreach}>
PRIMARY KEY  (`document_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;