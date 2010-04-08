<?php
/**
 * framework_patch_0309
 * @package modules.framework
 */
class framework_patch_0309 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
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
		$source = f_util_FileUtils::buildFrameworkPath('builder', 'home', 'bin', 'tasks', 'BaseTask.php');
		$destination = f_util_FileUtils::buildWebeditPath('bin', 'tasks', 'BaseTask.php');
		f_util_FileUtils::cp($source, $destination, f_util_FileUtils::OVERRIDE);
		
		$source = f_util_FileUtils::buildFrameworkPath('builder', 'home', 'bin', 'tasks', 'dayChange.php');
		$destination = f_util_FileUtils::buildWebeditPath('bin', 'tasks', 'dayChange.php');
		f_util_FileUtils::cp($source, $destination, f_util_FileUtils::OVERRIDE);
		
		$source = f_util_FileUtils::buildFrameworkPath('builder', 'home', 'bin', 'tasks', 'hourChange.php');
		$destination = f_util_FileUtils::buildWebeditPath('bin', 'tasks', 'hourChange.php');
		f_util_FileUtils::cp($source, $destination, f_util_FileUtils::OVERRIDE);
		
		$source = f_util_FileUtils::buildFrameworkPath('builder', 'home', 'bin', 'tasks', 'batchMailer.php');
		$destination = f_util_FileUtils::buildWebeditPath('bin', 'tasks', 'batchMailer.php');
		f_util_FileUtils::cp($source, $destination, f_util_FileUtils::OVERRIDE);
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
		return '0309';
	}
}