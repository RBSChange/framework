<?php
/**
 * framework_patch_0317
 * @package modules.framework
 */
class framework_patch_0317 extends patch_BasePatch
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
	
	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'framework';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0317';
	}
}