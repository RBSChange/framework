<{foreach from=$model->getTableField() item=property}>
ALTER TABLE `<{$model->getTableName()}>` ADD <{$property->generateSql('mysql')}>;

<{/foreach}>
