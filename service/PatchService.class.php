<?php
/**
 * @package framework.service
 */
class PatchService extends BaseService
{
	/**
	 * @var PatchService
	 */
	private static $instance;
	
	/**
	 * All patch into the project
	 * @var array
	 */
	private $allPatch = null;
	
	private $initialDbPatchList = null;
	
	/**
	 * Constructor of PatchService
	 */
	protected function __construct()
	{
	}
	
	/**
	 * @return PatchService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new PatchService();
		}
		return self::$instance;
	}
	
	/**
	 * Return All available patch
	 * @return array<pakageName => array<patchname>>
	 */
	public function getAllPatch()
	{
		if ($this->allPatch === null)
		{
			$this->allPatch = array();
			$packageList = $this->getInstalledPackage();
			foreach ($packageList as $packageName)
			{
				$this->allPatch[$packageName] = $this->getPatchList($packageName);
			}
		}
		return $this->allPatch;
	}
	
	private function getPatchList($packageName)
	{
		$result = array();
		$dir = FileResolver::getInstance()->setPackageName($packageName)->getPath('patch');
		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if ((strlen($file) == 4) && is_numeric($file))
					{
						$result[] = $file;
					}
				}
				closedir($dh);
			}
		}
		sort($result);
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
		
		$initalPatchList = $this->getInitialDbPatchList();
		if (count($initalPatchList) == 0)
		{
			$this->updateRepository();
			$initalPatchList = $this->getInitialDbPatchList();
		}
		
		foreach ($allPatchList as $packageName => $patchNameList)
		{
			foreach ($patchNameList as $checkPatchName) 
			{
				if ($this->isNewPatch($packageName, $checkPatchName))
				{
					if (!isset($result[$packageName]))
					{
						$result[$packageName] = array();
					}
					$result[$packageName][] = $checkPatchName;
				}
			}
		}
		
		return $result;
	}
	
	private function getInstalledPackage()
	{
		$packageList = array_merge(array('framework', 'webapp'), ModuleService::getInstance()->getModules());
		return $packageList;
	}
	
	private function getInitialDbPatchList()
	{
		if ($this->initialDbPatchList === null)
		{
			$packageList = $this->getInstalledPackage();
			$this->initialDbPatchList = array();
			foreach ($packageList as $packageName)
			{
				$patchName = $this->getLastPatch($packageName);
				if ($patchName !== null)
				{
					$this->initialDbPatchList[$packageName] = $patchName;
				}
			}			
		}
		return $this->initialDbPatchList;
	}
	
	public function isInstalled($moduleName, $patchName)
	{
		if (empty($moduleName) || empty($patchName))
		{
			return false;
		}
		
		if ($moduleName != 'framework' && $moduleName != 'webapp')
		{
			$packageName = 'modules_' . $moduleName;
		}
		else
		{
			$packageName = $moduleName;
		}
		return !$this->isNewPatch($packageName, $patchName);	
	}
	
	
	/**
	 * @param String $packageName
	 * @param String $checkPatchName
	 * @return Boolean
	 */
	private function isNewPatch($packageName, $checkPatchName)
	{
		$initalPatchList = $this->getInitialDbPatchList();
		if (!array_key_exists($packageName, $initalPatchList))
		{
			$initalPatchName = null;
		}
		else
		{
			$initalPatchName = $initalPatchList[$packageName];
		}		
		
		if ($initalPatchName !== null && $checkPatchName <= $initalPatchName) 
		{
			return false;
		}
		$date = $this->getCodePatch($packageName, $checkPatchName);
		if ($date !== null)
		{
			return false;
		}
		$date = $this->getDBPatch($packageName, $checkPatchName);
		if ($date !== null)
		{
			return false;
		}
		return true;
	}
		
	private function buildVersion($module, $numero)
	{
		return $module . '-' . $this->buildBaseVersion($numero);
	}
	
	private function buildBaseVersion($numero)
	{
		return CHANGE_RELEASE . $numero;
	}
	
	private function getReleaseList($dir)
	{
		$result = array();
		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					preg_match_all('/^([a-z_]+)-' . CHANGE_RELEASE . '([0-9]+)$/', $file, $matchs);
					if (count($matchs[0]) == 1)
					{
						$module = $matchs[1][0];
						$version = intval($matchs[2][0]);
						if (! array_key_exists($module, $result))
						{
							$result[$module] = 0;
						}
						if ($version > $result[$module])
						{
							$result[$module] = $version;
						}
					}
				}
				closedir($dh);
			}
		}
		return $result;
	}
	
	/**
	 * Update patch repository
	 * @param String $moduleName
	 * @param String $patchName
	 * @param Boolean $isCodePatch
	 */
	public function patchApply($moduleName, $patchName, $isCodePatch)
	{
		
		if ($moduleName != 'framework' && $moduleName != 'webapp')
		{
			$packageName = 'modules_' . $moduleName;
		}
		else
		{
			$packageName = $moduleName;
		}
		
		if ($isCodePatch)
		{
			$this->setCodePatch($packageName, $patchName);
		}
		else
		{
			$this->setDBPatch($packageName, $patchName);
		}
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
		if ($moduleName != 'framework' && $moduleName != 'webapp')
		{
			$packagename = 'modules_' . $moduleName;
		}
		else
		{
			$packagename = $moduleName;
		}
		$path = FileResolver::getInstance()->setPackageName($packagename)->getPath(f_util_FileUtils::buildPath('patch', $patchName, 'README'));
		if ($path && is_readable($path))
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
	 *
	 */
	public function updateRepository()
	{
		$patchs = $this->getAllPatch();
		$this->initialDbPatchList = null;
		
		$tm = $this->getTransactionManager();
		$result = array();
		try
		{
			$tm->beginTransaction();
			foreach ($patchs as $packageName => $patchNames)
			{
				if (count($patchNames) > 0)
				{
					$patchName = end($patchNames);
					$result[$packageName] = $patchName;
					$this->setLastPatch($packageName, $patchName);
				}
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
	}
	
	private function setLastPatch($packagename, $patchName)
	{
		$pp = $this->getPersistentProvider();
		if ($patchName === null)
		{
			$patchName = '----';
		}
		$pp->setSettingValue($packagename, 'lastpatch', $patchName);
	}
	
	private function getLastPatch($packagename)
	{
		$pp = $this->getPersistentProvider();
		$patchName = $pp->getSettingValue($packagename, 'lastpatch');
		if ($patchName == '----')
		{
			$patchName = null;
		}
		return $patchName;
	}
	
	private function setDBPatch($packagename, $patchName)
	{
		$pp = $this->getPersistentProvider();
		$pp->setSettingValue($packagename, 'patch_' . $patchName, date_Calendar::now()->toString());
	}
	
	private function getDBPatch($packagename, $patchName)
	{
		$pp = $this->getPersistentProvider();
		return $pp->getSettingValue($packagename, 'patch_' . $patchName);
	}
	
	private function setCodePatch($packagename, $patchName)
	{
		$path = f_util_FileUtils::buildWebeditPath('installedpatch');
		f_util_FileUtils::mkdir($path);
		$fileName = f_util_FileUtils::buildPath($path, $packagename . '_' . $patchName . '.txt');
		f_util_FileUtils::write($fileName, date_Calendar::now()->toString());
	}
	
	private function getCodePatch($packagename, $patchName)
	{
		$fileName = f_util_FileUtils::buildWebeditPath('installedpatch', $packagename . '_' . $patchName . '.txt');
		if (is_readable($fileName))
		{
			return f_util_FileUtils::read($fileName);
		}
		return null;
	}

}