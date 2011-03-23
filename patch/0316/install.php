<?php
/**
 * framework_patch_0316
 * @package modules.framework
 */
class framework_patch_0316 extends patch_BasePatch
{
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
	public function isCodePatch()
	{
		return true;
	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$path = f_util_FileUtils::buildCachePath('cache_browscap.ini.php');
		$this->log('Delete file: ' . $path);
		if (file_exists($path))
		{
			@unlink($path);
		}
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
		return '0316';
	}
}