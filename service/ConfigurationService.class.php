<?php
/**
 * @package framework.service
 */
class change_ConfigurationService
{
	/**
	 * The project config compiled
	 */
	private $config = null;
	
	private $defines = null;
	
	/**
	 *
	 * @var change_ConfigurationService
	 */
	private static $instance;
	
	/**
	 *
	 * @return change_ConfigurationService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new change_ConfigurationService();
		}
		return self::$instance;
	}
	
	/**
	 * @return boolean
	 */
	public function isCompiled()
	{
		$configFileDir = implode(DIRECTORY_SEPARATOR, array(WEBEDIT_HOME, 'build', 'config', PROFILE . '.php'));
		return is_file($configFileDir);
	}
	
	/**
	 * Return an array with part of configuration of Framework
	 * or throw a Exception if the $path configuration does not exist
	 *
	 * @param String $path        	
	 * @param Boolean $strict        	
	 * @throws Exception if the $path configuration does not exist
	 * @return String | false if the path was not founded and strict value if
	 *         false
	 */
	public function getConfiguration($path, $strict = true)
	{
		$current = $this->config;
		foreach (explode('/', $path) as $part)
		{
			if (!isset($current[$part]))
			{
				if ($strict)
				{
					throw new Exception('Part of configuration ' . $part . ' not found.');
				}
				return false;
			}
			$current = $current[$part];
		}
		return $current;
	}
	
	/**
	 * Return an array with part of configuration of Framework
	 * or null if the $path configuration does not exist
	 *
	 * @param String $path        	
	 * @param String $defaultValue        	
	 * @return mixed | null
	 */
	public function getConfigurationValue($path, $defaultValue = null)
	{
		$value = $this->getConfiguration($path, false);
		if ($value === false || (is_string($value) && f_util_StringUtils::isEmpty($value)) || (is_array($value) && f_util_ArrayUtils::isEmpty($value)))
		{
			return $defaultValue;
		}
		return $value;
	}
	
	/**
	 * Return an array with configuration of Framework
	 */
	public function getAllConfiguration()
	{
		return $this->config;
	}
	
	/**
	 * Return true if the $path configuration exist
	 *
	 * @param String $path        	
	 */
	public function hasConfiguration($path)
	{
		$current = $this->config;
		foreach (explode('/', $path) as $part)
		{
			if (!isset($current[$part]))
			{
				return false;
			}
			$current = $current[$part];
		}
		return true;
	}
	
	/**
	 * Load the framework configuration.
	 * Use the file php auto generated in cache/config
	 * You can specify an environnement to load a particular config file
	 *
	 * @param string $env        	
	 * @param Boolean $onlyConfig        	
	 */
	public function loadConfiguration($profile = 'default')
	{
		// If specific environnement add a dot to complet in path file
		$this->config = array();

		$configFileDir = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;	
		$configFile = $configFileDir . $profile . '.php';
		if (!is_file($configFile))
		{
			throw new Exception("Could not find $configFile. You must compile your configuration.");
		}			
		include $configFile;
		
		$this->defines = array();
		
		$defineFile = $configFileDir . $profile . '.define.php';
		include $defineFile;
		$this->applyDefine();

	}
		
	public function setConfigArray($config)
	{
		$this->config = $config;
	}
	
	public function setDefineArray($defines)
	{
		$this->defines = $defines;
	}
	
	protected function applyDefine()
	{
		foreach ($this->defines as $name => $value)
		{
			if (!defined($name))
			{
				if (is_string($value))
				{
					if (strpos($value, 'return ') === 0 && substr($value, -1) === ';')
					{
						$value = eval($value);
					}
				}
				define($name, $value);
			}
		}
	}
	
	// ProjectParser
	private function loadXmlConfigFile($xmlPath, &$configArray)
	{
		if (is_readable($xmlPath))
		{
			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->load($xmlPath);
			if ($dom->documentElement)
			{
				foreach ($dom->documentElement->childNodes as $node)
				{
					if ($node->nodeType !== XML_ELEMENT_NODE)
					{
						continue;
					}
					$this->populateConfigArray($node, $configArray);
				}
			}
		}
	}
	
	/**
	 *
	 * @param DOMElement $xmlElement        	
	 * @param array $configArray        	
	 */
	private function populateConfigArray($xmlElement, &$configArray)
	{
		$tagName = $xmlElement->hasAttribute('name') ? $xmlElement->getAttribute('name') : $xmlElement->nodeName;
		foreach ($xmlElement->childNodes as $node)
		{
			if ($node->nodeType !== XML_ELEMENT_NODE)
			{
				continue;
			}
			if (!isset($configArray[$tagName]))
			{
				$configArray[$tagName] = array();
			}
			$this->populateConfigArray($node, $configArray[$tagName]);
		}
		
		if (!isset($configArray[$tagName]) || is_string($configArray[$tagName]) || count($configArray[$tagName]) == 0)
		{
			$configArray[$tagName] = trim($xmlElement->textContent);
		}
	}
	
	/**
	 * Merge specific config file of project with default config file and save
	 * config file in cache/config
	 * @param  array $computedDeps
	 * @return array old and current configuration
	 */
	public function compile($computedDeps)
	{
		// Config dir.
		$configDir = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'config';
		if (!is_dir($configDir))
		{
			return;
		}
		
		// Cache config dir.
		$cacheConfigDir = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
		
		$currentProfile = (defined("PROFILE") ? PROFILE : trim(file_get_contents(WEBEDIT_HOME . "/profile")));
		$cacheFile = $cacheConfigDir .  $currentProfile . ".php";
		$cacheDefineFile = $cacheConfigDir . $currentProfile . ".define.php";
		
		$this->compiling = true;
		
		$oldConfig = null;
		$oldDefines = null;
		if (is_file($cacheFile))
		{
			include $cacheFile;
			$oldConfig = $this->config;
		}
		
		if (is_file($cacheDefineFile))
		{
			include $cacheDefineFile;
			$oldDefines = $this->defines;
		}
		
		
		// Config Dir for over write.
		$fileList = scandir($configDir);
		
		$configDefineArray = array();
		$configArray = array();
		$this->loadXmlConfigFile(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.xml', $configArray);
		
		// -- Global constants.
		if (isset($computedDeps["PEAR_DIR"]))
		{
			$this->addConstant($configDefineArray, "PEAR_DIR", $computedDeps["PEAR_DIR"]);
		}
		if (isset($computedDeps["LOCAL_REPOSITORY"]))
		{
			$this->addConstant($configDefineArray, "LOCAL_REPOSITORY", $computedDeps["LOCAL_REPOSITORY"]);
		}
		if (isset($computedDeps["WWW_GROUP"]))
		{
			$this->addConstant($configDefineArray, "WWW_GROUP", $computedDeps["WWW_GROUP"]);
		}
		if (isset($computedDeps["OUTGOING_HTTP_PROXY_HOST"]))
		{
			$this->addConstant($configDefineArray, "OUTGOING_HTTP_PROXY_HOST", $computedDeps["OUTGOING_HTTP_PROXY_HOST"]);
			$this->addConstant($configDefineArray, "OUTGOING_HTTP_PROXY_PORT", $computedDeps["OUTGOING_HTTP_PROXY_PORT"]);
		}
		$fDeps = $computedDeps['change-lib']['framework'];
		$this->addConstant($configDefineArray, "FRAMEWORK_VERSION", $fDeps['version']);
		
		// -- Modules informations.
		$configArray['packageversion'] = $this->compilePackageVersion();
		$this->compileModulesConfig($configArray, $computedDeps['module']);
		$configArray['config']['packageversion'] = $configArray['packageversion'];
		unset($configArray['packageversion']);
		
		// Injections
		$docInjections = $this->searchForDocInjections();
		if (count($docInjections) > 0)
		{
			if (!isset($configArray['config']['injection']))
			{
				$configArray['config']['injection'] = array();
			}
			if (!isset($configArray['config']['injection']['document']))
			{
				$configArray['config']['injection']['document'] = array();
			}
			$configArray['config']['injection']['document'] = $docInjections;
		}
		
		$this->loadXmlConfigFile(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.xml', $configArray);
		$profilFile = $configDir . DIRECTORY_SEPARATOR . "project." . $currentProfile . ".xml";
		if (is_readable($profilFile))
		{
			$this->loadXmlConfigFile($profilFile, $configArray);
			$configDefineArray = array_merge($configDefineArray, $configArray['defines']);
		}
		else
		{
			die($profilFile);
		}
		
		if (!isset($configDefineArray['TMP_PATH']))
		{
			if (function_exists('sys_get_temp_dir'))
			{
				$TMP_PATH = sys_get_temp_dir();
			}
			else
			{
				$tmpfile = @tempnam(null, 'loc_');
				if ($tmpfile)
				{
					$TMP_PATH = dirname($tmpfile);
					@unlink($tmpfile);
				}
				else if (DIRECTORY_SEPARATOR === '\\')
				{
					if (isset($_ENV['TMP']))
					{
						$TMP_PATH = $_ENV['TMP'];
					}
					else if (isset($_ENV['TEMP']))
					{
						$TMP_PATH = $_ENV['TEMP'];
					}
					else
					{
						throw new Exception('Please define TMP_PATH in project.xml config file');
					}
				}
				else
				{
					$TMP_PATH = '/tmp';
				}
			}
			$configDefineArray['TMP_PATH'] = $TMP_PATH;
		}
		
		if (isset($computedDeps["DEVELOPMENT_MODE"]) && $computedDeps["DEVELOPMENT_MODE"])
		{
			$configDefineArray['AG_DEVELOPMENT_MODE'] = true;
		}
		
		$configDefineArray['PHP_CLI_PATH'] = (isset($computedDeps["PHP_CLI_PATH"])) ? $computedDeps["PHP_CLI_PATH"] : '';
		
		if (!isset($configArray['config']['browsers']['frontoffice']) || !is_array($configArray['config']['browsers']['frontoffice']))
		{
			$configArray['config']['browsers']['frontoffice'] = array();
		}
		
		$content = "<?php // change_ConfigurationService::setDefineArray PART // \n";
		$configDefineArray = $this->prepareToExportDefineArray($configDefineArray);
		$content .= "change_ConfigurationService::getInstance()->setDefineArray(" . var_export($configDefineArray, true) . ');';		
		$this->writeFile($cacheConfigDir . $currentProfile . ".define.php", $content);
		$this->defines = $configDefineArray;
		$this->applyDefine();
		if (Framework::inDevelopmentMode())
		{
			$this->buildDevelopmentDefineFile($configDefineArray);
		}
		
		$content = "<?php // change_ConfigurationService::setConfigArray PART // \n";
		$content .= "change_ConfigurationService::getInstance()->setConfigArray(" . var_export($configArray['config'], true) . ');';
		$this->writeFile($cacheConfigDir . $currentProfile . ".php", $content);
		$this->config = $configArray['config'];

		return ($oldConfig !== null) ? array("old" => array("defines" => $oldDefines, "config" => $oldConfig), 
				"current" => array("config" => $configArray['config'], "defines" => $configDefineArray)) : null;
	}
	
	
	
	private function writeFile($path, $content)
	{
		$dir = dirname($path);
		if (!is_dir($dir))
		{
			if (mkdir($dir, 0777, true) === false)
			{
				throw new Exception("Could not create directory $dir");
			}
		}
		if (file_put_contents($path, $content) === false)
		{
			throw new Exception("Could not write file $path");
		}
	}
	
	private function addConstant(&$globalConstants, $name, $value)
	{
		$globalConstants[$name] = $value;
	}
	
	/**
	 * 
	 * @param array $configDefineArray
	 * @return array
	 */
	private function prepareToExportDefineArray($configDefineArray)
	{
		foreach ($configDefineArray as $name => $value)
		{
			if (is_string($value))
			{
				//Match WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'config'
				//Or CHANGE_CONFIG_DIR . 'toto'
				//But not Fred's Directory
				if (preg_match('/^(([A-Z][A-Z_0-9]+)|(\'[^\']*\'))(\s*\.\s*(([A-Z][A-Z_0-9]+)|(\'[^\']*\')))+$/', $value))
				{
					$configDefineArray[$name] = 'return ' . $value . ';';
				}
			}
		}
		return $configDefineArray;
	}
	/**
	 *
	 * @return Array<String, String>
	 */
	private function compilePackageVersion()
	{
		$packagesVersion = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/change.xml');
		if (!is_array($files) || count($files) == 0)
		{
			return $packagesVersion;
		}
		foreach ($files as $changeXmlFile)
		{
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->load($changeXmlFile);
			if ($doc->documentElement)
			{
				$name = null;
				$version = null;
				
				foreach ($doc->documentElement->childNodes as $node)
				{
					if ($node->nodeName == 'name')
					{
						$name = trim($node->textContent);
					}
					elseif ($node->nodeName == 'version')
					{
						$version = trim($node->textContent);
					}
				}
				if ($name && $version)
				{
					$packagesVersion['modules_' . $name] = $version;
				}
				continue;
			}
			$packagesVersion['modules_' . basename(dirname($changeXmlFile))] = null;
		}
		return $packagesVersion;
	}
	
	/**
	 *
	 * @param array $configArray        	
	 */
	private function compileModulesConfig(&$configArray, $computedModulesDeps)
	{
		$constants = array();
		$moduleXmlFiles = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/config/module.xml');
		if (is_array($files) || count($files) > 0)
		{
			foreach ($files as $moduleXmlFile)
			{
				if (!is_readable($moduleXmlFile))
				{
					continue;
				}
				$moduleConfig = array();
				$this->loadXmlConfigFile($moduleXmlFile, $moduleConfig);
				$moduleName = basename(dirname(dirname($moduleXmlFile)));
				
				$moduleXmlOverrideFile = implode(DIRECTORY_SEPARATOR, array(WEBEDIT_HOME, 'override', 'modules', $moduleName, 'config', 'module.xml'));
				if (is_readable($moduleXmlOverrideFile))
				{
					$this->loadXmlConfigFile($moduleXmlOverrideFile, $moduleConfig);
				}
				
				if (isset($moduleConfig['module']))
				{
					$pname = 'modules_' . $moduleName;
					$version = isset($configArray['packageversion'][$pname]) ? $configArray['packageversion'][$pname] : null;
					$configArray['packageversion'][$pname] = array('ENABLED' => true, 'VISIBLE' => true, 'CATEGORY' => null, 'ICON' => 'package', 
						'USETOPIC' => false, 'VERSION' => $version);
					
					foreach ($moduleConfig['module'] as $key => $value)
					{
						$key = strtoupper($key);
						switch ($key)
						{
							case 'ENABLED' :
							case 'VISIBLE' :
							case 'CATEGORY' :
							case 'ICON' :
							case 'USETOPIC' :
								if ($value === 'true')
								{
									$value = true;
								}
								elseif ($value === 'false')
								{
									$value = false;
								}
								$configArray['packageversion'][$pname][$key] = $value;
								break;
							default :
								$constantName = 'MOD_' . strtoupper($moduleName) . '_' . $key;
								$configArray['defines'][$constantName] = $value;
								break;
						}
					}
				}
				if (isset($moduleConfig['project']) && is_array($moduleConfig['project']))
				{
					if (!isset($configArray['config']['modules'][$moduleName]))
					{
						$configArray['config']['modules'][$moduleName] = array();
					}
					$configArray['config']['modules'][$moduleName] = array_merge_recursive($configArray['config']['modules'][$moduleName], $moduleConfig['project']);
				}
				
				if (isset($moduleConfig['modules']))
				{
					foreach ($moduleConfig['modules'] as $moduleName => $data)
					{
						if (!isset($configArray['config']['modules'][$moduleName]))
						{
							$configArray['config']['modules'][$moduleName] = array();
						}
						$configArray['config']['modules'][$moduleName] = array_merge_recursive($configArray['config']['modules'][$moduleName], $data);
					}
				}
			}
		}
	}
	
	private function searchForDocInjections()
	{
		$injections = array();
		foreach (glob(WEBEDIT_HOME . '/modules/*/persistentdocument/*.xml') as $docFile)
		{
			$doc = f_util_DOMUtils::fromPath($docFile);
			$root = $doc->documentElement;
			if ($root->hasAttribute("inject") && $root->getAttribute("inject") == "true")
			{
				$extend = $root->getAttribute("extend");
				$matches = null;
				if (!preg_match('/^modules_(.*)\/(.*)$/', $extend, $matches))
				{
					echo "Warn: bad attribute extend for $docFile\n";
					continue;
				}
				$docName = basename($docFile, ".xml");
				$moduleName = basename(dirname(dirname($docFile)));
				$injections[$matches[1] . "/" . $matches[2]] = $moduleName . "/" . $docName;
			}
		}
		return $injections;
	}
	
	/**
	 * @param string $path
	 * @param string $value
	 * @return boolean
	 */
	public function addVolatileProjectConfigurationNamedEntry($path, $value)
	{
		$sections = array();
		foreach (explode('/', $path) as $name)
		{
			if (trim($name) != '')
			{
				$sections[] = trim($name);
			}
		}
		if (count($sections) < 2)
		{
			return false;
		}
		
		$config = array();
		$sections = array_reverse($sections);
		foreach ($sections as $section)
		{
			if ($section === reset($sections))
			{
				$config = $value;
			}
			$config = array($section => $config);
		}
		
		$this->config = $this->array_merge_configuration($this->config, $config);
		return true;
	}
	
	/**
	 *
	 * @param string $path        	
	 * @param string $value        	
	 * @return string old value
	 */
	public function addProjectConfigurationEntry($path, $value)
	{
		$sections = array();
		foreach (explode('/', $path) as $name)
		{
			if (trim($name) != '')
			{
				$sections[] = trim($name);
			}
		}
		if (count($sections) < 2 && $this->addVolatileProjectConfigurationNamedEntry($path, $value))
		{
			return false;
		}
		$entryName = array_pop($sections);
		return self::addProjectConfigurationNamedEntry(implode('/', $sections), $entryName, $value);
	}
	
	/**
	 *
	 * @param string $path        	
	 * @param string $value        	
	 * @return string old value
	 */
	public function addProjectConfigurationNamedEntry($path, $entryName, $value)
	{
		if (empty($entryName) || ($value !== null && !is_string($value)))
		{
			return false;
		}
		$sections = array('config');
		foreach (explode('/', $path) as $name)
		{
			if (trim($name) != '')
			{
				$sections[] = trim($name);
			}
		}
		if (count($sections) < 2)
		{
			return false;
		}
		
		$oldValue = null;
		$configProjectPath = implode(DIRECTORY_SEPARATOR, array(WEBEDIT_HOME, 'config', 'project.xml'));
		if (!is_readable($configProjectPath))
		{
			return false;
		}
		
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = true;
		$dom->preserveWhiteSpace = false;
		$dom->load($configProjectPath);
		$dom->formatOutput = true;
		if ($dom->documentElement == null)
		{
			return false;
		}
		$sectionNode = $dom->documentElement;
		
		foreach ($sections as $sectionName)
		{
			$childSectionNode = null;
			foreach ($sectionNode->childNodes as $entryNode)
			{
				if ($entryNode->nodeType === XML_ELEMENT_NODE && $entryNode->nodeName === $sectionName)
				{
					$childSectionNode = $entryNode;
					break;
				}
			}
			if ($childSectionNode === null)
			{
				$childSectionNode = $sectionNode->appendChild($dom->createElement($sectionName));
			}
			$sectionNode = $childSectionNode;
		}
		
		foreach ($sectionNode->childNodes as $entryNode)
		{
			if ($entryNode->nodeType === XML_ELEMENT_NODE && $entryNode->getAttribute('name') === $entryName)
			{
				$oldValue = $entryNode->textContent;
				break;
			}
		}
		if ($oldValue !== $value)
		{
			if ($value === null)
			{
				$sectionNode->removeChild($entryNode);
				
				while (!$sectionNode->hasChildNodes() && $sectionNode->nodeName !== 'config')
				{
					$pnode = $sectionNode->parentNode;
					$pnode->removeChild($sectionNode);
					$sectionNode = $pnode;
				}
			}
			elseif ($oldValue === null)
			{
				$entryNode = $sectionNode->appendChild($dom->createElement('entry'));
				$entryNode->setAttribute('name', $entryName);
				$entryNode->appendChild($dom->createTextNode($value));
			}
			else
			{
				while ($entryNode->hasChildNodes())
				{
					$entryNode->removeChild($entryNode->firstChild);
				}
				$entryNode->appendChild($dom->createTextNode($value));
			}
			$dom->save($configProjectPath);
		}
		
		return $oldValue;
	}
	
	/**
	 * Merge two configuration array
	 * Function code find at: http://fr.php.net/manual/en/function.array-merge-recursive.php
	 * @param mixed array $configArray1
	 * @param mixed array $configArray2
	 * @return array
	 */
	private function array_merge_configuration($configArray1, $configArray2)
	{
		foreach($configArray2 as $key => $value)
		{
			if(array_key_exists($key, $configArray1) && is_array($value))
			{
				$configArray1[$key] = $this->array_merge_configuration($configArray1[$key], $configArray2[$key]);
			}	
			else
			{
				$configArray1[$key] = $value;
			}	
		}	
		return $configArray1;
	}
	
	private function buildDevelopmentDefineFile($defineArray)
	{
		$content = "<?php // For development only //" . PHP_EOL;
		$content .= "throw new Exception('Do not include this file');" . PHP_EOL;
		foreach($defineArray as $key => $value)
		{
			$defval = var_export($value, true);
			if (is_string($value))
			{
				if (strpos($value, 'return ') === 0 && substr($value, -1) === ';')
				{
					$defval = substr($value, 7, strlen($value) - 8);
				}
			}
			$content .= "define('".$key. "', " .  $defval . ");" . PHP_EOL;
		}
		$filename = implode(DIRECTORY_SEPARATOR, array(WEBEDIT_HOME, 'build', PROFILE, 'dev_defines.php'));
		file_put_contents($filename, $content);
	}
	
}