<?php
/**
 * framework_patch_0360
 * @package modules.framework
 */
class framework_patch_0360 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$filePath = f_util_FileUtils::buildFrameworkPath('dataobject', 'FI18n.mysql.sql');	
		$sql = file_get_contents($filePath);	
		$this->executeSQLQuery($sql);
	}
}