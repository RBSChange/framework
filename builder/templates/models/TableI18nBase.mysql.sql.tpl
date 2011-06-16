CREATE TABLE IF NOT EXISTS `<{$model->getTableName()}>_i18n` (
	`document_id` int(11) NOT NULL default '0',
	`lang_i18n` varchar(2) NOT NULL default 'fr',
<{foreach from=$model->getTableI18nField() item=property}>
	<{$property->generateSql('mysql', true)}>,
<{/foreach}>
PRIMARY KEY  (`document_id`, `lang_i18n`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;
