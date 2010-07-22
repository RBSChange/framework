<?php
class commands_Indexer extends commands_AbstractChangeCommand
{
	private $actions = array("clear", "clear-backoffice", "clear-frontoffice", "reset",
	"reset-frontoffice", "reset-backoffice", "rebuild-spell", "optimize", "import",
	"import-frontoffice", "import-backoffice");
	
	/**
	 * @return String
	 */
	function getUsage()
	{
		$usage = "<action>\nWhere action in:\n"; 
		foreach ($this->actions as $action)
		{
			$usage.= "- ".$action."\n";
		}
		return $usage; 
	}
	
	function getDescription()
	{
		return "manage the documents index";
	}
	
	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			return $this->actions;
		}
		return null;
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1 && in_array($params[0], $this->actions);
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->loadFramework();
		$action = $params[0];
		$this->message("== Indexer: ".$action." ==");
		
		switch ($action) 
		{
			case 'clear':
				indexer_IndexService::getInstance()->clearIndex();
				break;
			case 'clear-backoffice':
				indexer_IndexService::getInstance()->clearBackofficeIndex();
				break;
			case 'clear-frontofficee':
				indexer_IndexService::getInstance()->clearFrontofficeIndex();
				break;
			case 'reset':
				indexer_IndexService::getInstance()->clearIndex();
				$this->indexFrontofficeDocuments();
				$this->indexBackofficeDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();				
				break;
			case 'reset-backoffice':
				indexer_IndexService::getInstance()->clearBackofficeIndex();
				$this->indexBackofficeDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();				
				break;
			case 'reset-frontoffice':
				indexer_IndexService::getInstance()->clearFrontofficeIndex();
				$this->indexFrontofficeDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();				
				break;
			case 'rebuild-spell':
				$this->rebuildSpell();			
				break;
			case 'optimize':
				$this->optimizeIndex();			
				break;
			case 'import':
				$this->indexFrontofficeDocuments();
				$this->indexBackofficeDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();		
				break;
			case 'import-frontoffice':
				$this->indexFrontofficeDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();		
				break;
			case 'import-backoffice':
				$this->indexBackofficeDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();		
				break;
		}
		$this->quitOk("Indexer: $action OK");
	}
	
	private function optimizeIndex()
	{
		indexer_IndexService::getInstance()->optimizeIndex();
	}
	
	private function rebuildSpell()
	{
		$is = indexer_IndexService::getInstance();
		foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang)
		{
			$is->rebuildSpellCheckIndexForLang($lang);
		}
	}

	private function indexBackofficeDocuments()
	{
		$is = indexer_IndexService::getInstance();
		$is->setAutoCommit(false);
		$indexableDocuments = $is->getBackofficeIndexableDocumentIds();
		$this->message("* Number of documents: " . count($indexableDocuments));
		$this->processIds($indexableDocuments, 'back');
	}
		
	
	private function indexFrontofficeDocuments()
	{
		$is = indexer_IndexService::getInstance();
		$is->setAutoCommit(false);
		$indexableDocuments = $is->getIndexableDocumentIds();
		$this->message("* Number of documents: " . count($indexableDocuments));
		$this->processIds($indexableDocuments, 'front');
	}
	
	private function processIds($ids, $mode)
	{
		$totalDocumentCount = count($ids);
		$currentDocumentCount = 0;
		$indexerLogPath = f_util_FileUtils::buildLogPath('indexer.log');
		$scriptPath = 'framework/indexer/chunkDocumentIndexer.php';
		$modeParam = array($mode);
		foreach (array_chunk($ids, 50) as $chunk)
		{	
			$output = f_util_System::execHTTPScript($scriptPath, array_merge($modeParam, $chunk));
			$currentDocumentCount += count($chunk);
			$chunkInfo = "* Documents processed: " . $currentDocumentCount."/" . $totalDocumentCount;
			$this->message($chunkInfo);	
			file_put_contents($indexerLogPath, $chunkInfo . "\n" . $output . "\n", FILE_APPEND);		
		}
	}
}