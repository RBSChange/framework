<?php
/**
 * Auto-generated doc comment
 * @package framework.bin.solr
 */
define("WEBEDIT_HOME", realpath('.' . DIRECTORY_SEPARATOR));
require_once WEBEDIT_HOME . "/framework/Framework.php";

function listIndexableModels()
{
	
	$idx = array();
	foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModels() as $name => $inst)
	{
		if ($inst->isIndexable() || $inst->isBackofficeIndexable())
		{
			$idx[] = $name;
		}
	}
	echo join(' ', $idx);
}

function clear()
{
	indexer_IndexService::getInstance()->clearIndex();
}

function optimizeIndex()
{
	indexer_IndexService::getInstance()->optimizeIndex();
}

function rebuildSpell()
{
	$is = indexer_IndexService::getInstance();
	foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang)
	{
		$is->rebuildSpellCheckIndexForLang($lang);
	}
}

function processIds($ids, $mode)
{
	$chunks = array_chunk($ids, 500);
	$i = 0;
	$totalDocumentCount = count($ids);
	$currentDocumentCount = 0;
	$indexerLogPath = f_util_FileUtils::buildLogPath('indexererror.log');
	foreach ($chunks as $chunk)
	{
		$cmd = "php " .  f_util_FileUtils::buildFrameworkPath('indexer', 'batchIndexer.php') . " $mode " . implode(" ", $chunk) . " >> " . $indexerLogPath;
		$output = f_util_System::exec($cmd, "* Processing chunk " .++$i ."/" . count($chunks));
		$currentDocumentCount += count($chunk);
		echo "* Documents processed: " . $currentDocumentCount."/" . $totalDocumentCount . "\n";
	}
	if (f_util_FileUtils::read($indexerLogPath) == '')
	{
		f_util_FileUtils::unlink($indexerLogPath);
	}
}

function indexBackofficeDocuments()
{
	$rc = RequestContext::getInstance();
	$is = indexer_IndexService::getInstance();
	$is->setAutoCommit(false);
	$indexableDocuments = $is->getBackofficeIndexableDocumentIds();
	echo "* Number of documents: " . count($indexableDocuments) . "\n";
	processIds($indexableDocuments, 'back');
}
	

function indexFrontofficeDocuments()
{
	$rc = RequestContext::getInstance();
	$is = indexer_IndexService::getInstance();
	$is->setAutoCommit(false);
	$indexableDocuments = $is->getIndexableDocumentIds();
	echo "* Number of documents: " . count($indexableDocuments) . "\n";
	processIds($indexableDocuments, 'front');
}


function main()
{
	$argv = $_SERVER['argv'];
	$argc = $_SERVER['argc'];
	$rc = RequestContext::getInstance();
	$rc->setLang($rc->getDefaultLang());
	
	if (count($_SERVER['argv']) > 0)
	{
		
		if ($argc > 1)
		{
			if ($argv[1] == '--index')
			{
				indexBackofficeDocuments();
				indexFrontofficeDocuments();
			}
			else if ($argv[1] == '--indexallbackoffice')
			{
				indexBackofficeDocuments();
			}
			else if ($argv[1] == '--indexallfrontoffice')
			{
				indexFrontofficeDocuments();
			}
			else if ($argv[1] == '--clear-backoffice')
			{
				indexer_IndexService::getInstance()->clearBackofficeIndex();
			}
			else if ($argv[1] == '--clear-frontoffice')
			{
				indexer_IndexService::getInstance()->clearFrontofficeIndex();
			}
			else if ($argv[1] == '--rebuildspell')
			{
				rebuildSpell();
			
			}
			else if ($argv[1] == '--optimize')
			{
				optimizeIndex();
			}
		}
	
	}
}

Controller::newInstance("controller_ChangeController");
main();