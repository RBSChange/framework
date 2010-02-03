<?php
class framework_patch_0300 extends patch_BasePatch
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
		// Remove the following line and implement the patch here.
		parent::execute();
		
		$this->log('Supression des anciens formulaire ...');
		$moduleService = ModuleService::getInstance();
		foreach ($moduleService->getModules() as $packageName)
		{
			$moduleName = $moduleService->getShortModuleName($packageName);
			$pathWebAppForm = f_util_FileUtils::buildWebappPath('modules', $moduleName, 'forms');
			
			$filePath = f_util_FileUtils::buildPath($pathWebAppForm, "permission_layout.all.all.xul");
			if (file_exists($filePath))
			{
				unlink($filePath);
			}
			
			$filePath = f_util_FileUtils::buildPath($pathWebAppForm, 'permission_impl.js');
			if (file_exists($filePath))
			{
				unlink($filePath);
			}
			
			if ($this->isEmptyDir($pathWebAppForm))
			{
				$this->log('Suppression du dossier : ' . $pathWebAppForm);
				$this->rmdir($pathWebAppForm);
			}
			
			$pathWebAppModule = f_util_FileUtils::buildWebappPath('modules', $moduleName);
			if ($this->isEmptyDir($pathWebAppModule))
			{
				$this->log('Suppression du dossier de module : ' . $pathWebAppModule);
				$this->rmdir($pathWebAppModule);
			}			
			
		}
		
		$this->log('Compilation des roles ...');
		exec('change.php compile-roles');
	
	}
	
	private function isEmptyDir($dirname)
	{
		if (!is_dir($dirname)) {return false;}
		foreach (scandir($dirname) as $file) 
		{
			if ($file !== '.' && $file !== '..' && $file !== '.svn')
			{
				return false;
			}
		}
		return true;
	}
	
	private function rmdir($dirName)
	{
		exec("rm -rf " . $dirName);
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
		return '0300';
	}

}