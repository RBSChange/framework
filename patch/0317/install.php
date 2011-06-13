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
		
		// FIX #35684
		$this->executeSQLQuery("ALTER TABLE `f_url_rules` DROP INDEX `website_id`");
		$this->executeSQLQuery("ALTER TABLE `f_url_rules` ADD UNIQUE `website_id` ( `website_id` , `from_url` , `document_lang` )");
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