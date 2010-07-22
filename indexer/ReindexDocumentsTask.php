<?php

class f_tasks_ReindexDocumentsTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 *
	 */
	protected function execute()
	{
		f_util_System::execChangeCommand('indexer', array('import'));
	}
}