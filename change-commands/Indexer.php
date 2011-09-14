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
		return '<' . implode('|', $this->actions) . '>';
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
			return indexer_IndexService::getInstance()->getIndexableModelsName();
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
		if ($ok && $params[0] === 'import-model' && count($params) > 1)
		{
			$ok = count($params) == 2 && indexer_IndexService::getInstance()->isModelNameIndexable($params[1]);
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
				indexer_IndexService::getInstance()->clearIndex();
				break;
			case 'clear-frontofficee':
				indexer_IndexService::getInstance()->clearIndex();
				break;
			case 'reset':
				indexer_IndexService::getInstance()->clearIndex();
				$this->indexDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();				
				break;
			case 'reset-backoffice':
				indexer_IndexService::getInstance()->clearIndex();
				$this->indexDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();				
				break;
			case 'reset-frontoffice':
				indexer_IndexService::getInstance()->clearIndex();
				$this->indexDocuments();
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
				$this->indexDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();		
				break;
			case 'import-frontoffice':
				$this->indexDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();		
				break;
			case 'import-backoffice':
				$this->indexDocuments();
				$this->rebuildSpell();
				$this->optimizeIndex();		
				break;
			case 'import-model':
				if (isset($params[1]))
				{
					$this->reIndexModelName($params[1]);
				}
				else
				{
					foreach (indexer_IndexService::getInstance()->getIndexableModelsName() as $modelName) 
					{
						$this->reIndexModelName($modelName);
					}
				}
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

	private function indexDocuments()
	{
		$is = indexer_IndexService::getInstance();
		$modelsName = $is->getIndexableModelsName();
		$this->message("* Number of Document models: " . count($modelsName));
		$this->processModels($modelsName);
	}
		
	private function processModels($modelsName)
	{
		$totalDocumentCount = 0;
		$scriptPath = 'framework/indexer/chunkDocumentIndexer.php';
		$indexerLogPath = f_util_FileUtils::buildLogPath('indexer.log');
		$chunkSize = 100;
		$chunkInfo = "* Start indexing documents.";
		error_log("\n". gmdate('Y-m-d H:i:s')."\t".$chunkInfo, 3, $indexerLogPath);
		$this->message($chunkInfo);
		
		foreach ($modelsName as $modelName) 
		{
			$documentIndex = 0;
			$progres = true;
			while ($progres) 
			{
				$output = f_util_System::execScript($scriptPath, array($modelName, $documentIndex, $chunkSize, 0));
				if (!is_numeric($output))
				{
					$progres = false;
					$chunkInfo = "* Error on processsing $modelName at index $documentIndex. ($output)";
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
			$output = f_util_System::execScript($scriptPath, array($modelName, $documentIndex, $chunkSize));
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