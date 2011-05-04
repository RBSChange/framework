<?php
/**
 * framework_patch_0351
 * @package modules.framework
 */
class framework_patch_0351 extends patch_BasePatch
{ 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$path = f_util_FileUtils::buildFrameworkPath('dataobject','FIndexing.mysql.sql');
		$sql = file_get_contents($path);
		$this->executeSQLQuery($sql);
	}
}