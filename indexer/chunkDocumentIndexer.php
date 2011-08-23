<?php
if (count($arguments) == 4)
{
	list ($modelName, $documentIndex, $chunkSize, $delayed) = $arguments;
	echo indexer_IndexService::getInstance()->indexDocumentChunk($modelName, $documentIndex, $chunkSize, $delayed);
}
else
{
	Framework::error(__FILE__ . " invalid arguments " . var_export($arguments, true));
	echo 'ERROR';
}