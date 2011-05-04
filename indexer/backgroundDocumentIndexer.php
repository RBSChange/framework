<?php
if (defined("SOLR_INDEXER_DISABLE_DOCUMENTCACHE") && SOLR_INDEXER_DISABLE_DOCUMENTCACHE == true)
{
	f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(false);
}

Controller::newInstance("controller_ChangeController");
$arguments = $_POST['argv'];
if (count($arguments) != 3)
{
	Framework::error(__FILE__ . " invalid arguments " . var_export($arguments, true));
	echo 'ERROR';
}
else
{
	list($indexingMode, $maxDocumentId, $chunkSize) = $arguments;
	if (is_numeric($indexingMode))
	{
		echo indexer_IndexService::getInstance()->backgroundIndex(intval($indexingMode), intval($maxDocumentId), intval($chunkSize));
	}
	else
	{
		echo indexer_IndexService::getInstance()->reIndexModelName($indexingMode, intval($maxDocumentId), intval($chunkSize));
	}
}