<?php
class commands_Indexer extends commands_AbstractChangeCommand
{
	private $actions = array("clear", "clear-backoffice", "clear-frontoffice", "reset",
	"reset-frontoffice", "reset-backoffice", "rebuild-spell", "optimize", "import",
	"import-frontoffice", "import-backoffice", "import-model");
	
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
		else if ($completeParamCount == 1 && $params[0] === 'import-model')
		{
			return array_unique(
				array_merge(indexer_IndexService::getInstance()->getBackOfficeModelsName(), 
					indexer_IndexService::getInstance()->getFrontOfficeModelsName()));
		}
		return null;
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		$ok = count($params) >= 1 && in_array($params[0], $this->actions);
		if ($ok && $params[0] === 'import-model')
		{
			$models =  array_unique(
				array_merge(indexer_IndexService::getInstance()->getBackOfficeModelsName(), 
					indexer_IndexService::getInstance()->getFrontOfficeModelsName()));
			$ok = count($params) == 2 && in_array($params[1], $models);
		} 
		else if (count($params) != 1)
		{
			$ok = false;
		}
		return $ok;
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
			case 'import-model':
				$this->reIndexModelName($params[1]);	
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
		$modelsName = $is->getBackOfficeModelsName();
		$this->message("* Number of Document models: " . count($modelsName));
		$this->processModels($modelsName, 'back');
	}
		
	
	private function indexFrontofficeDocuments()
	{
		$is = indexer_IndexService::getInstance();
		$modelsName = $is->getFrontOfficeModelsName();
		$this->message("* Number of Document models: " . count($modelsName));
		$this->processModels($modelsName, 'front');
	}
	
	private function processModels($modelsName, $mode)
	{
		$totalDocumentCount = 0;
		$scriptPath = 'framework/indexer/chunkDocumentIndexer.php';
		$indexerLogPath = f_util_FileUtils::buildLogPath('indexer.log');
		$chunkSize = 100;
		$chunkInfo = "* Start indexing $mode documents.";
		error_log("\n". gmdate('Y-m-d H:i:s')."\t".$chunkInfo, 3, $indexerLogPath);
		$this->message($chunkInfo);
		
		foreach ($modelsName as $modelName) 
		{
			$modeParam = array($mode);
			$documentIndex = 0;
			$progres = true;
			while ($progres) 
			{
				$output = f_util_System::execHTTPScript($scriptPath, array($mode, $modelName, $documentIndex, $chunkSize));
				if (!is_numeric($output))
				{
					$progres = false;
					$chunkInfo = "* Error on processsing $modelName at index $documentIndex.";
				}
				if (intval($output) == $chunkSize)
				{
					$documentIndex += $chunkSize; 
					$totalDocumentCount += $chunkSize; 
					$chunkInfo = "* $modelName processed: " . $documentIndex;
				}
				else
				{
					$totalDocumentCount += intval($output);
					$documentIndex += intval($output); 
					$progres = false;
					$chunkInfo = "* $modelName processed Total: $documentIndex";
				}
				error_log("\n". gmdate('Y-m-d H:i:s')."\t".$chunkInfo, 3, $indexerLogPath);
				$this->message($chunkInfo);
			} 	
		}
		error_log("\n". gmdate('Y-m-d H:i:s')."\tTotal of indexed documents: $totalDocumentCount", 3, $indexerLogPath);
		$this->message("Total of indexed documents: $totalDocumentCount");
	}
	
	private function reIndexModelName($modelName)
	{
		$indexerLogPath = f_util_FileUtils::buildLogPath('indexer.log');
		$scriptPath = 'framework/indexer/backgroundDocumentIndexer.php';
		$documentIndex = 0;
		$progres = true;
		$chunkSize = 500;
		while ($progres) 
		{
			$output = f_util_System::execHTTPScript($scriptPath, array($modelName, $documentIndex, $chunkSize));
			if (!is_numeric($output))
			{
				$progres = false;
				$chunkInfo = "* Error on processsing $modelName at index $documentIndex.";
			}
			if (intval($output) == $chunkSize)
			{
				$documentIndex += $chunkSize; 
				$chunkInfo = "* $modelName processed: " . $documentIndex;
			}
			else
			{
				$documentIndex += intval($output); 
				$progres = false;
				$chunkInfo = "* $modelName processed Total: $documentIndex";
			}
			error_log("\n". gmdate('Y-m-d H:i:s')."\t". $chunkInfo, 3, $indexerLogPath);
			$this->message($chunkInfo);
		}		
	}
}