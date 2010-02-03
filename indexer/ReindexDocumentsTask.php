<?php

class f_tasks_ReindexDocumentsTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 *
	 */
	protected function execute()
	{
		chdir(WEBEDIT_HOME);
		exec('/bin/bash ' . f_util_FileUtils::buildFrameworkPath('bin', 'solr', 'indexadmin.sh'). ' --import');
	}
}