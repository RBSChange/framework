<?php

class f_tasks_ReindexDocumentsByUpdatedRolesTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 *
	 */
	protected function execute()
	{
		
		$frontIds = array();
		$backIds = array();
		
		foreach ($this->getParameter("updatedRoles") as $roleName)
		{
			$frontIds =  array_merge($frontIds, indexer_IndexService::getInstance()->getIndexableDocumentIdsForModifiedRole($roleName));
			$backIds = array_merge($backIds, indexer_IndexService::getInstance()->getBackofficeIndexableDocumentIdsForModifiedRole($roleName));
		}
		
		$this->processIds(array_unique($frontIds), 'front');
		$this->processIds(array_unique($backIds), 'back');
	}
	
	private function processIds($ids, $mode)
	{
		$scriptPath = 'framework/indexer/chunkDocumentIndexer.php';
		$modeParam = array($mode);
		foreach (array_chunk($ids, 100) as $chunk)
		{	
			f_util_System::execHTTPScript($scriptPath, array_merge($modeParam, $chunk));
		}
	}
}