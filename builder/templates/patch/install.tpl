<?php
/**
 * <{$moduleName}>_patch_<{$patchNumber}>
 * @package modules.<{$moduleName}>
 */
class <{$moduleName}>_patch_<{$patchNumber}> extends change_Patch
{

	/**
	 * @return array
	 */
	public function getPreCommandList()
	{
		return array(
			array('disable-site'),
		);
	}
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Implement your patch here.
	}
	
	
	/**
	 * @return array
	 */
	public function getPostCommandList()
	{
		return array(
			array('clear-documentscache'),
			array('enable-site'),
		);
	}
	
	
	/**
	 * @return string
	 */
	public function getExecutionOrderKey()
	{
		return '<{$executionOrderKey}>';
	}
		
	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return dirname(__FILE__);
	}
	
    /**
     * @return <{$codepatch}>
     */
	public function isCodePatch()
	{
		return <{$codepatch}>;
	}
	
}