<?php
/**
 * framework_patch_0320
 * @package modules.framework
 */
class framework_patch_0320 extends patch_BasePatch
{ 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeSQLQuery("ALTER TABLE `f_indexing` ADD INDEX ( `indexing_status` ) ");
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
		return '0320';
	}
}