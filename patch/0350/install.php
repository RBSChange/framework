<?php
/**
 * framework_patch_0350
 * @package modules.framework
 */
class framework_patch_0350 extends patch_BasePatch
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
		try 
		{
			$this->executeSQLQuery("RENAME TABLE `f_locale` TO `f_locale_old`");
			$this->executeSQLQuery("DELETE FROM `f_locale_old` WHERE `useredited` != 1");
			$filePath = f_util_FileUtils::buildFrameworkPath('dataobject', 'FrameworkLocale.mysql.sql');
			$sql = file_get_contents($filePath);
			
			foreach(explode(";",$sql) as $query)
			{
				$query = trim($query);
				if (empty($query))
				{
					continue;
				}
				try
				{
					$this->executeSQLQuery($query);
				}
				catch (Exception $e)
				{
					$this->logError($e->getMessage());
				}
			}
		}
		catch (Exception $e)
		{
			$this->log('f_locale already converted');
		}
		$this->log('convert locale folder in i18n folder...');
		$this->execChangeCommand('i18n.convert');
		
		$this->log('compile locales...');
		$this->execChangeCommand('compile-locales');		
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
		return '0350';
	}
}