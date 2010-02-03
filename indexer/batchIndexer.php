<?php
define("WEBEDIT_HOME", realpath('.' . DIRECTORY_SEPARATOR));
require_once WEBEDIT_HOME . "/framework/Framework.php";
if (defined("SOLR_INDEXER_DISABLE_DOCUMENTCACHE") && SOLR_INDEXER_DISABLE_DOCUMENTCACHE == true)
{
	f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(false);
}
Controller::newInstance("controller_ChangeController");

$rc = RequestContext::getInstance();
$is = indexer_IndexService::getInstance();
if ($_SERVER['argc'] > 1)
{
	$mode = $_SERVER['argv'][1];
	if ($mode === "back")
	{
		$backIndexing = true;
	}
	else if ($mode === "front")
	{
		$backIndexing = false;
	}
	else
	{
		Framework::error(__FILE__ . " $mode is not a valid indexation mode (must be front or back)");
	}
}

try
{
	foreach (array_slice($_SERVER['argv'], 2) as $documentId)
	{
		$document = DocumentHelper::getDocumentInstance($documentId);
		if ($backIndexing && !$document->getPersistentModel()->isBackofficeIndexable())
		{
			Framework::error(__FILE__ . " model " . $document->getPersistentModel()->getName() . " is not backoffice indexable");
			return;
		}
		if (!$backIndexing && !$document->getPersistentModel()->isIndexable())
		{
			Framework::error(__FILE__ . " model " . $document->getPersistentModel()->getName() . " is not frontoffice indexable");
			return;
		}

		foreach ($document->getI18nInfo()->getLangs() as $lang)
		{
			$rc->beginI18nWork($lang);
			if (!$document->isContextLangAvailable())
			{
				continue;
			}
			if ($backIndexing === true)
			{
				if ($document->getPublicationstatus() !== "DEPRECATED")
				{
					$is->addBackoffice($document);
				}
			}
			else
			{
				if ($document->isPublished())
				{
					$is->add($document);
				}
			}
			$rc->endI18nWork();
		}
	}
}
catch (Exception $e)
{
	echo $e->getMessage();
	Framework::exception($e);
	exit(1);
}