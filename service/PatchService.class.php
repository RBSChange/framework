<?php
/**
 * @method PatchService getInstance()
 */
class PatchService extends change_BaseService
{
	/**
	 * All patch into the project
	 * @var array
	 */
	private $allPatch = null;
	
	
	private $initialDbPatchList = null;
	
	
	/**
	 * @return PatchService
	 */
	public static function resetInstance()
	{
		self::clearInstanceByClassName(get_called_class());
		return self::getInstanceByClassName(get_called_class());
	}
	
	/**
	 * @param string $componentName
	 * @return string
	 */
	public function createCodePatch($componentName)
	{
		// Get the patch patch.
		$patchNumber = $this->getNextPatchFolderName($componentName);
		$patchPath = f_util_FileUtils::buildRelativePath($this->getPatchFolder($componentName), $patchNumber);

		// Create the directory of new patch
		f_util_FileUtils::mkdir($patchPath);

		// Instance a new object generator based on smarty
		$generator = new builder_Generator('patch');

		// Assign all necessary variable
		$generator->assign('moduleName', $componentName);
		$generator->assign('patchNumber', $patchNumber);
		$generator->assign('release', Framework::getVersion());
		$generator->assign('codepatch', 'true');
		$generator->assign('executionOrderKey', date_Calendar::getInstance()->toString());

		// Execute the template for the README file
		f_util_FileUtils::write($patchPath . DIRECTORY_SEPARATOR . 'README', $generator->fetch('README.tpl'));

		// Execute the template for the install.php file
		f_util_FileUtils::write($patchPath . DIRECTORY_SEPARATOR . 'install.php', $generator->fetch('install.tpl'));


		$lastPatchPath = f_util_FileUtils::buildRelativePath($this->getPatchFolder($componentName), 'lastpatch');
		file_put_contents($lastPatchPath, $patchNumber);
		return $patchPath;
	}
	
	/**
	 * @param string $componentName
	 * @return string
	 */
	public function createDBPatch($componentName)
	{
		// Get the patch patch.
		$patchNumber = $this->getNextPatchFolderName($componentName);
		$patchPath = f_util_FileUtils::buildRelativePath($this->getPatchFolder($componentName), $patchNumber);

		// Create the directory of new patch
		f_util_FileUtils::mkdir($patchPath);

		// Instance a new object generator based on smarty
		$generator = new builder_Generator('patch');

		// Assign all necessary variable
		$generator->assign('moduleName', $componentName);
		$generator->assign('patchNumber', $patchNumber);
		$generator->assign('release', Framework::getVersion());
		$generator->assign('codepatch', 'false');
		$generator->assign('executionOrderKey', date_Calendar::getInstance()->toString());

		// Execute the template for the README file
		f_util_FileUtils::write($patchPath . DIRECTORY_SEPARATOR . 'README', $generator->fetch('README.tpl'));

		// Execute the template for the install.php file
		f_util_FileUtils::write($patchPath . DIRECTORY_SEPARATOR . 'install.php', $generator->fetch('install.tpl'));

		$lastPatchPath = f_util_FileUtils::buildRelativePath($this->getPatchFolder($componentName), 'lastpatch');
		file_put_contents($lastPatchPath, $patchNumber);
		return $patchNumber;
	}
	
	/**
	 * @param string $componentName
	 * @param string $patchName
	 * @return string or null;
	 */
	public function getPHPClassPatch($componentName, $patchName)
	{
		$className = $componentName . '_patch_' . $patchName;
		$classFilePath = f_util_FileUtils::buildPath($this->getPatchFolder($componentName), $patchName, 'install.php');
		if (is_readable($classFilePath))
		{
			require_once($classFilePath);
			if (class_exists($className, false))
			{
				return $className;
			}
		}
		return null;
	}
	
	/**
	 * @param string $componentName
	 * @return integer
	 */
	public function getLastPatchNumber($componentName)
	{
		$parts = explode('.', Framework::getVersion());
		$lastPatchNumber = intval($parts[0] . $parts[1] . '0') - 1;
		
		$patchPath = $this->getPatchFolder($componentName);
		if (is_dir($patchPath))
		{
			foreach (scandir($patchPath, 1) as $value) 
			{
				if ($value === 'lastpatch')
				{
					$number = intval(file_get_contents(f_util_FileUtils::buildPath($patchPath, $value)));
				}
				else
				{
					$number = intval($value);
				}
				if ($number > $lastPatchNumber)
				{
					$lastPatchNumber = $number;
				}
			} 
		}
		return $lastPatchNumber;
	}
	
	/**
	 * @param String $module
	 * @return String
	 */
	public function getNextPatchFolderName($componentName)
	{
		$nextPatchNumber = strval($this->getLastPatchNumber($componentName) + 1);
		return  str_pad($nextPatchNumber, 4, '0', STR_PAD_LEFT);
	}
	
	/**
	 * Return All available patch
	 * @return array<moduleName => array<patchname>>
	 */
	public function getAllPatch()
	{
		if ($this->allPatch === null)
		{
			$this->allPatch = array();
			$packageList = array_merge(array('framework'), ModuleService::getInstance()->getPackageNames());	
			foreach ($packageList as $packageName)
			{
				$moduleName = ModuleService::getInstance()->getShortModuleName($packageName);	
				$patchList = $this->getPatchList($moduleName);
				if (count($patchList))
				{
					$this->allPatch[$moduleName] = $this->getPatchList($moduleName);
				}
			}
		}
		return $this->allPatch;
	}
		
	/**
	 * @param string $moduleName
	 * @return string[]
	 */
	public function getPatchList($moduleName)
	{
		$result = array();
		$dir = $this->getPatchFolder($moduleName);
		if (is_dir($dir))
		{
			foreach (scandir($dir) as $file) 
			{
				if ((strlen($file) == 4) && is_numeric($file))
				{
					$result[] = $file;
				}
			}
			sort($result);
		}
		return $result;
	}
	
	/**
	 * Return applicable patch
	 * @return array<pakageName => array<patchname>>
	 */
	public function check()
	{
		$result = array();
		$allPatchList = $this->getAllPatch();
		$initDBPatchList = $this->getInitialDbPatchList();
		
		foreach ($allPatchList as $moduleName => $patchNameList)
		{
			$packageName = $this->buildPackageName($moduleName);
			foreach ($patchNameList as $patchName) 
			{
				if (isset($initDBPatchList[$moduleName]) && $initDBPatchList[$moduleName] >= $patchName)
				{
					continue;
				}
				
				if ($this->getCodePatch($packageName, $patchName) !== null)
				{
					continue;
				}
				
				if ($this->getDBPatch($packageName, $patchName) !== null)
				{
					continue;
				}
				
				if (!isset($result[$moduleName]))
				{
					$result[$moduleName] = array();
				}
				
				$result[$moduleName][] = $patchName;
			}
		}
		
		return $result;
	}
	
	/**
	 * @param change_Patch $patch1
	 * @param change_Patch $patch2
	 */
	public function sortPatchForExecution($patch1, $patch2)
	{
		if ($patch1->getExecutionOrderKey() === $patch2->getExecutionOrderKey())
		{
			return 0;
		}
		return $patch1->getExecutionOrderKey() > $patch2->getExecutionOrderKey() ? 1 : -1;
	}
	/**
	 * @param change_Patch $patch
	 * @return boolean
	 */
	public function isInstalled($patch)
	{
		$patchName = $patch->getNumber();	
		$initalPatchList = $this->getInitialDbPatchList();
		if (isset($initalPatchList[$patch->getModuleName()]) && $initalPatchList[$patch->getModuleName()] < $patchName)
		{
			return true;
		}
		
		$packageName = $this->buildPackageName($patch->getModuleName());
		if ($patch->isCodePatch())
		{
			return ($this->getCodePatch($packageName, $patch->getNumber()) !== null);
		}
		return ($this->getDBPatch($packageName, $patch->getNumber()) !== null);
	}
	
	/**
	 * 
	 * @param change_Patch $patch
	 * @return string or null
	 */
	public function getInstallationDate($patch)
	{
		$packageName = $this->buildPackageName($patch->getModuleName());
		if ($patch->isCodePatch())
		{
			return $this->getCodePatch($packageName, $patch->getNumber());
		}
		return $this->getDBPatch($packageName, $patch->getNumber());
	}
		
	/**
	 * @param change_Patch $patch
	 */
	public function patchApply($patch)
	{
		$packageName = $this->buildPackageName($patch->getModuleName());
		$date = date_Calendar::getInstance()->toString();
		if ($patch->isCodePatch())
		{
			$this->setCodePatch($packageName, $patch->getNumber(), $date);
		}
		else
		{
			$this->setDBPatch($packageName, $patch->getNumber(), $date);
		}
	}
	
	/**
	 * @param string $moduleName
	 * @param string $patchName
	 * @return boolean
	 */
	public function patchExist($moduleName, $patchName)
	{
		$path = f_util_FileUtils::buildPath($this->getPatchFolder($moduleName), $patchName, 'install.php');
		return is_readable($path);
	}
	
	/**
	 * Return the content of the README file patch
	 *
	 * @param String $moduleName
	 * @param String $patchName
	 * @return String
	 */
	public function patchInfo($moduleName, $patchName)
	{
		$path = f_util_FileUtils::buildPath($this->getPatchFolder($moduleName), $patchName, 'README');
		if (is_readable($path))
		{
			return f_util_FileUtils::read($path);
		}
		else
		{
			return 'No available information.';
		}
	}
	
	/**
	 * Update patch repository with the last available patch
	 * @param string $componentName
	 */
	public function updateRepository($componentName = null)
	{
		$patchs = $this->getAllPatch();
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			
			foreach ($patchs as $moduleName => $patchNames)
			{
				if (count($patchNames) > 0 && ($componentName === null || $moduleName == $componentName))
				{
					$patchName = end($patchNames);
					$this->setLastPatch($this->buildPackageName($moduleName), $patchName);
				}
			}
			
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
	}
	
	
	/**
	 * @param string $componentName
	 * @return string
	 */
	private function buildPackageName($componentName)
	{
		if ($componentName === 'framework')
		{
			return 'framework';
		}
		return 'modules_' . $componentName;
	}
	
	/**
	 * @param String $shortName
	 * @return String
	 */
	private function getPatchFolder($componentName)
	{
		if ($componentName === 'framework')
		{
			return f_util_FileUtils::buildFrameworkPath('patch');
		}
		else
		{
			return f_util_FileUtils::buildModulesPath($componentName, 'patch');
		}
	}
	
	/**
	 * @return <moduleName => lastPatchNumber>
	 */
	private function getInitialDbPatchList()
	{
		$initialDbPatchList = array();
		$moduleNames = array_keys($this->getAllPatch());
		foreach ($moduleNames as $moduleName)
		{
			$patchName = $this->getLastPatch($this->buildPackageName($moduleName));
			if ($patchName !== null)
			{
				$initialDbPatchList[$moduleName] = $patchName;
			}
		}			
		return $initialDbPatchList;
	}
	
	private function setLastPatch($packagename, $patchName)
	{
		$pp = $this->getPersistentProvider();
		$pp->setSettingValue($packagename, 'lastpatch', $patchName);
	}
	
	private function getLastPatch($packagename)
	{
		$pp = $this->getPersistentProvider();
		$patchName = $pp->getSettingValue($packagename, 'lastpatch');
		return $patchName;
	}
	
	private function setDBPatch($packagename, $patchName, $date)
	{
		$pp = $this->getPersistentProvider();
		$pp->setSettingValue($packagename, 'patch_' . $patchName, $date);
	}
	
	private function getDBPatch($packagename, $patchName)
	{
		$pp = $this->getPersistentProvider();
		return $pp->getSettingValue($packagename, 'patch_' . $patchName);
	}
	
	private function setCodePatch($packagename, $patchName, $date)
	{
		$path = f_util_FileUtils::buildProjectPath('installedpatch');
		f_util_FileUtils::mkdir($path);
		$fileName = f_util_FileUtils::buildPath($path, $packagename . '_' . $patchName . '.txt');
		if ($date === null)
		{
			if (file_exists($fileName))
			{
				@unlink($fileName);
			}
		}
		else
		{
			f_util_FileUtils::write($fileName, $date, f_util_FileUtils::OVERRIDE);
		}
	}
	
	private function getCodePatch($packagename, $patchName)
	{
		$fileName = f_util_FileUtils::buildProjectPath('installedpatch', $packagename . '_' . $patchName . '.txt');
		if (is_readable($fileName))
		{
			return f_util_FileUtils::read($fileName);
		}
		return null;
	}

}