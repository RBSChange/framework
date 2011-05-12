<?php
if (defined("SOLR_INDEXER_DISABLE_DOCUMENTCACHE") && SOLR_INDEXER_DISABLE_DOCUMENTCACHE == true)
{
	f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(false);
}
$arguments = $_POST['argv'];
if (count($arguments) != 5)
{
	Framework::error(__FILE__ . " invalid arguments " . var_export($arguments, true));
	echo 'ERROR';
}
else
{
	list($mode, $modelName, $documentIndex, $chunkSize, $delayed) = $arguments;
	echo indexer_IndexService::getInstance()->indexDocumentChunk($mode, $modelName, $documentIndex, $chunkSize, $delayed);
}