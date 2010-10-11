<?php
$controller = Controller::newInstance("controller_ChangeController");

$tm = f_persistentdocument_TransactionManager::getInstance();
$rc = RequestContext::getInstance();
$is = indexer_IndexService::getInstance();
$is->setAutocommit(false);
try
{
	$tm->beginTransaction();
	foreach ($_POST['argv'] as $documentIdAndLang) 
	{
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
echo 'OK';