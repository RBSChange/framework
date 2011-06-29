<?php
/**
 * framework_patch_0353
 * @package modules.framework
 */
class framework_patch_0353 extends patch_BasePatch
{

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeSQLQuery("ALTER TABLE `f_cache` ADD `insert_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
		$this->executeSQLQuery("truncate TABLE `f_cache`");
	}
}