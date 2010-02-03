<?php

define("WEBEDIT_HOME", realpath('.'));
require_once WEBEDIT_HOME . "/framework/Framework.php";


$tm = f_persistentdocument_TransactionManager::getInstance();
$rc = RequestContext::getInstance();
$is = indexer_IndexService::getInstance();
$is->setAutocommit(false);
try
{
	$updatedDocumentCount = 0;
	$tm->beginTransaction();
	for ($i = 1; $i < (int)$_SERVER['argc']; $i++)
	{
		$documentIdAndLang = $_SERVER['argv'][$i];
		list($id, $lang) = explode("/", $documentIdAndLang);
		try
		{
			$rc->beginI18nWork($lang);
			$document = DocumentHelper::getDocumentInstance($id);
			$ds = $document->getDocumentService();
			$ds->publishDocument($document);
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
	
	}
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
}
$is->commit();