<?php
/**
 * framework_patch_0314
 * @package modules.framework
 */
class framework_patch_0314 extends patch_BasePatch
{
	/**
	 * Returns true if the patch modify code that is versionned.
	 * If your patch modify code that is versionned AND database structure or content,
	 * you must split it into two different patches.
	 * @return Boolean true if the patch modify code that is versionned.
	 */
	public function isCodePatch()
	{
		return false;
	}
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$computedPath = f_util_FileUtils::buildWebeditPath(".change", "autoload", ".computedChangeComponents.ser");
		$computedDeps = unserialize(f_util_FileUtils::read($computedPath));
	
		if (isset($computedDeps["PEAR_DIR"]) && !isset($computedDeps["lib-pear"]))
		{
			echo "Symlink ".$computedDeps["PEAR_DIR"]." to libs/pear\n";
			f_util_FileUtils::symlink($computedDeps["PEAR_DIR"], "libs/pear");
			f_util_System::execChangeCommand("update-autoload", array("libs/pear"));
		}
		else
		{
			echo "You use pearlibs: nothing to do\n"; 
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
		return '0314';
	}
}