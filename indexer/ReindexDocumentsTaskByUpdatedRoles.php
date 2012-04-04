<?php

class f_tasks_ReindexDocumentsByUpdatedRolesTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 *
	 */
	protected function execute()
	{
		
		$frontEndModels = array();
		$backEndModels = array();
		
		foreach ($this->getParameter("updatedRoles") as $roleName)
		{
			$frontEndModels =  array_merge($frontEndModels, indexer_IndexService::getInstance()->getIndexableDocumentModelsForModifiedRole($roleName));
			$backEndModels = array_merge($backEndModels, indexer_IndexService::getInstance()->getBackofficeIndexableDocumentModelsForModifiedRole($roleName));
		}
		$errors = array();
		
		$this->processModels(array_unique($frontEndModels), 'front', $errors);
		$this->processModels(array_unique($backEndModels), 'back', $errors);
		
		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}
	}
	
	private function processModels($modelsName, $mode, &$errors)
	{
		if (count($modelsName) == 0) {return ;}
		
		$totalDocumentCount = 0;
		$scriptPath = 'framework/indexer/chunkDocumentIndexer.php';
		$indexerLogPath = f_util_FileUtils::buildLogPath('indexer.log');
		$chunkSize = 500;
				
		error_log(gmdate('Y-m-d H:i:s')."\t".__METHOD__ . "\t START" . PHP_EOL, 3, $indexerLogPath);
		foreach ($modelsName as $modelName) 
		{
			$modeParam = array($mode);
			$documentIndex = 0;
			$progres = true;
			error_log(gmdate('Y-m-d H:i:s')."\t Processing $modelName" . PHP_EOL, 3, $indexerLogPath);
			while ($progres) 
			{
				$this->plannedTask->ping();
				$output = f_util_System::execHTTPScript($scriptPath, array($mode, $modelName, $documentIndex, $chunkSize, 1));
				if (!is_numeric($output))
				{
					$progres = false;
					$chunkInfo = " Error on processsing $modelName at index $documentIndex.";
					$errors[] = $chunkInfo;
				}
				if (intval($output) == $chunkSize)
				{
					$documentIndex += $chunkSize; 
					$totalDocumentCount += $chunkSize; 
					$chunkInfo = " $modelName processed: " . $documentIndex;
				}
				else
				{
					$documentIndex += intval($output); 
					$totalDocumentCount += intval($output);	
					$progres = false;
					$chunkInfo = " $modelName processed Total: $documentIndex";
				}
				error_log(gmdate('Y-m-d H:i:s')."\t".$chunkInfo . PHP_EOL, 3, $indexerLogPath);
			} 	
		}
		error_log(gmdate('Y-m-d H:i:s')."\t".__METHOD__ . "\t END TOTAL $totalDocumentCount" . PHP_EOL, 3, $indexerLogPath);
	}
}