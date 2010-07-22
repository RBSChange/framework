<?php
/**
 * framework_patch_0306
 * @package modules.framework
 */
class framework_patch_0306 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		$this->executeSQLQuery("ALTER TABLE `m_website_urlrewriting_rules` DROP `document_model`");
		$this->executeSQLQuery("ALTER TABLE `m_website_urlrewriting_rules` ADD `website_id` INT( 11 ) NOT NULL DEFAULT '0'");
		$this->executeSQLQuery("ALTER TABLE `m_website_urlrewriting_rules` DROP INDEX `document_url`");
		$this->executeSQLQuery("ALTER TABLE `m_website_urlrewriting_rules` ADD UNIQUE `document_url` ( `website_id` , `document_url` )");
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'framework';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0306';
	}
}