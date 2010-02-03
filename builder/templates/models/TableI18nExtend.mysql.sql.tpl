<{foreach from=$model->getTableI18nField() item=property}>
ALTER TABLE `<{$model->getTableName()}>_i18n` ADD <{$property->generateSql('mysql', true)}>;

<{/foreach}>