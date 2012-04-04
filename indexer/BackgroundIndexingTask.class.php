<?php
class f_tasks_BackgroundIndexingTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$stats = f_persistentdocument_PersistentProvider::getInstance()->getIndexingPendingEntries();
		$errors = array();	
		foreach ($stats as $row) 
		{
			$mode = intval($row['indexing_mode']);
			$maxId = intval($row['max_id']);
			while ($maxId > 0) 
			{
				$this->plannedTask->ping();
				$maxId = $this->backgroundIndex($mode, $maxId, 100, $errors);
			}
		}
		
		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}
	}
	
	private function backgroundIndex($indexingMode, $maxId, $chunkSize = 100, &$errors)
	{
		$scriptPath = 'framework/indexer/backgroundDocumentIndexer.php';
		$indexerLogPath = f_util_FileUtils::buildLogPath('indexer.log');
		$modeLabel = $indexingMode == indexer_IndexService::INDEXER_MODE_BACKOFFICE ? 'BO' : 'FO';
		error_log(gmdate('Y-m-d H:i:s')."\t".__METHOD__ . "\t $modeLabel \t $maxId" . PHP_EOL, 3, $indexerLogPath);				
		$output = f_util_System::execHTTPScript($scriptPath, array($indexingMode, $maxId, $chunkSize));
		if (!is_numeric($output))
		{
			$chunkInfo = " Error on processsing $modeLabel at index $maxId.";
			$errors[] = $chunkInfo;
			error_log(gmdate('Y-m-d H:i:s')."\t".$chunkInfo . PHP_EOL, 3, $indexerLogPath);
			$output = -1;
		}
		else if (intval($output) <= 0)
		{
			$chunkInfo = " End on processing $modeLabel.";
			error_log(gmdate('Y-m-d H:i:s')."\t".$chunkInfo . PHP_EOL, 3, $indexerLogPath);
		}
		
		return intval($output);
	}
}