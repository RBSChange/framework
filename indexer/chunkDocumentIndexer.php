<?php
if (defined("SOLR_INDEXER_DISABLE_DOCUMENTCACHE") && SOLR_INDEXER_DISABLE_DOCUMENTCACHE == true)
{
	f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(false);
}

Controller::newInstance("controller_ChangeController");
$arguments = $_POST['argv'];

if (count($arguments) > 1)
{
	$mode = $arguments[0];
	if ($mode === "back")
	{
		indexDocumentChunk(array_slice($arguments, 1), true);
	}
	else if ($mode === "front")
	{
		indexDocumentChunk(array_slice($arguments, 1), false);
	}
	else
	{
		Framework::error(__FILE__ . " $mode is not a valid indexation mode (must be front or back)");
	}
}
else
{
	Framework::error(__FILE__ . " invalid arguments count");
}

function indexDocumentChunk($ids, $backIndexing)
{
	if (Framework::isInfoEnabled())
	{
		Framework::info(__METHOD__ . "(" . count($ids) . " docs, $backIndexing)");
	}
	
	$rc = RequestContext::getInstance();
	$is = indexer_IndexService::getInstance();
	
	foreach ($ids as $documentId)
	{
		try
		{
			$document = DocumentHelper::getDocumentInstance($documentId);
			if ($backIndexing && ! $document->getPersistentModel()->isBackofficeIndexable())
			{
				Framework::warn(__FILE__ . " model " . $document->getPersistentModel()->getName() . " is not backoffice indexable");
				continue;
			}
			if (! $backIndexing && ! $document->getPersistentModel()->isIndexable())
			{
				Framework::warn(__FILE__ . " model " . $document->getPersistentModel()->getName() . " is not frontoffice indexable");
				continue;
			}
			
			foreach ($document->getI18nInfo()->getLangs() as $lang)
			{
				try
				{
					$rc->beginI18nWork($lang);
					
					if (! $document->isContextLangAvailable())
					{
						continue;
					}
					if ($backIndexing === true)
					{
						if ($document->getPublicationstatus() !== "DEPRECATED")
						{
							if (Framework::isInfoEnabled())
							{
								Framework::info('Index BO ' . $document->__toString());
							}
							$is->addBackoffice($document);
						}
					}
					else
					{
						if ($document->isPublished())
						{
							if (Framework::isInfoEnabled())
							{
								Framework::info('Index FO ' . $document->__toString());
							}
							$is->add($document);
						}
					}
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			echo $e->getMessage();
		}
	}
}