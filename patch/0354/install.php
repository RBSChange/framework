<?php
/**
 * framework_patch_0354
 * @package modules.framework
 */
class framework_patch_0354 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeSQLQuery("ALTER TABLE `f_indexing` ADD INDEX ( `indexing_status` ) ");
	}
}