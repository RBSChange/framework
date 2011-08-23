<?php
if (count($arguments) == 2)
{
	list($maxDocumentId, $chunkSize) = $arguments;
	echo indexer_IndexService::getInstance()->backgroundIndex(intval($maxDocumentId), intval($chunkSize));
}
elseif  (count($arguments) == 3)	
{
	list($documentModelName, $maxDocumentId, $chunkSize) = $arguments;
	echo indexer_IndexService::getInstance()->reIndexModelName($documentModelName, intval($maxDocumentId), intval($chunkSize));
}
else
{
	Framework::error(__FILE__ . " invalid arguments " . var_export($arguments, true));
	echo 'ERROR';
}