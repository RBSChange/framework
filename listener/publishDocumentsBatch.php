<?php
$tm = f_persistentdocument_TransactionManager::getInstance();
$rc = RequestContext::getInstance();
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
echo 'OK';