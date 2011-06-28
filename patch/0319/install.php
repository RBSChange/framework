<?php
/**
 * framework_patch_0319
 * @package modules.framework
 */
class framework_patch_0319 extends patch_BasePatch
{ 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('clear-webapp-cache');
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
		return '0319';
	}
}