<?php
if (defined("SOLR_INDEXER_DISABLE_DOCUMENTCACHE") && SOLR_INDEXER_DISABLE_DOCUMENTCACHE == true)
{
	f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(false);
}

Controller::newInstance("controller_ChangeController");
$arguments = $_POST['argv'];

if (count($arguments) != 4)
{
	Framework::error(__FILE__ . " invalid arguments " . var_export($arguments, true));
	echo 'ERROR';
}
else
{
	list($mode, $modelName, $documentIndex, $chunkSize) = $arguments;
	echo indexer_IndexService::getInstance()->indexDocumentChunk($mode, $modelName, $documentIndex, $chunkSize);
}