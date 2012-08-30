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
			$maxId = intval($row['max_id']);
			while ($maxId > 0) 
			{
				$this->plannedTask->ping();
				$maxId = $this->backgroundIndex($maxId, 100, $errors);
			}
		}
		
		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}
	}
	
	private function backgroundIndex($maxId, $chunkSize = 100, &$errors)
	{
		$logs = LoggingService::getInstance();
		$scriptPath = 'framework/indexer/backgroundDocumentIndexer.php';
		$logs->namedLog(__METHOD__ . "\t" . $maxId, 'indexer');
		$output = f_util_System::execScript($scriptPath, array($maxId, $chunkSize));
		if (!is_numeric($output))
		{
			$chunkInfo = " Error on index processsing at index $maxId. ($output)";
			$errors[] = $chunkInfo;
			$logs->namedLog($chunkInfo, 'indexer');
			$output = -1;
		}
		else if (intval($output) <= 0)
		{
			$chunkInfo = " End on index processing.";
			$logs->namedLog($chunkInfo, 'indexer');
		}
		
		return intval($output);
	}
}