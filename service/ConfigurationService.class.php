<?php
/**
 * @package framework.service
 * @method change_ConfigurationService getInstance()
 */
class change_ConfigurationService extends change_Singleton
{
	/**
	 * The compiled project config.
	 * 
	 * @var array
	 */
	private $config = null;
	
	/**
	 * @var array
	 */
	private $defines = null;
	
	/**
	 * @return boolean
	 */
	public function isCompiled()
	{
		$configFileDir = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'build', 'config', 'project.php'));
		return is_file($configFileDir);
	}
	
	/**
	 * Return an array with part of project configuration.
	 * 
	 * @param string $path			
	 * @param boolean $strict			
	 * @throws Exception if the $path configuration does not exist and $strict is set to true
	 * @return string | false if the path was not found and strict value is false
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
	 * or null if the $path configuration does not exist.
	 *
	 * @param string $path			
	 * @param string $defaultValue			
	 * @return mixed | null
	 */
	public function getConfigurationValue($path, $defaultValue = null)
	{
		$value = $this->getConfiguration($path, false);
		if ($value === false || (is_string($value) && (trim($value) == '') || (is_array($value) && (count($value) == 0))))
		{
			return $defaultValue;
		}
		return $value;
	}
	
	/**
	 * Return an array with configuration of Framework.
	 * 
	 * @return array
	 */
	public function getAllConfiguration()
	{
		return $this->config;
	}
	
	/**
	 * Return true if the $path configuration exist.
	 * 
	 * @param string $path 
	 * @return boolean	  	
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
	 * Use the php file auto compiled in cache/config.
	 * You can specify an environnement to load a particular config file.
	 */
	public function loadConfiguration()
	{
		// If specific environnement add a dot to complet in path file
		$this->config = array();

		$configFileDir = PROJECT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;	
		$configFile = $configFileDir . 'project.php';
		if (!is_file($configFile))
		{
			f_util_ProcessUtils::printBackTrace();
			throw new Exception("Could not find $configFile. You must compile your configuration.");
		}			
		include $configFile;
		
		$this->defines = array();
		
		$defineFile = $configFileDir . 'project.defines.php';
		include $defineFile;
		$this->applyDefine();
	}

	/**
	 * @param array $config
	 */
	public function setConfigArray($config)
	{
		$this->config = $config;
	}
	
	/**
	 * @param array $defines
	 */
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
	
	/**
	 * @param string $xmlPath
	 * @param array $configArray
	 */
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
	 * Merge specific config file of project with default config file and save it in cache/config.
	 * 
	 * @param  array $computedDeps
	 * @return array old and current configuration
	 */
	public function compile($computedDeps)
	{
		// Config dir.
		$configDir = PROJECT_HOME . DIRECTORY_SEPARATOR . 'config';
		if (!is_dir($configDir))
		{
			return;
		}
		
		// Cache config dir.
		$cacheConfigDir = PROJECT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
		$currentProfile = $this->getCurrentProfile();
		$cacheFile = $cacheConfigDir . 'project.php';
		$cacheDefinesFile = $cacheConfigDir . 'project.defines.php';
		
		$this->compiling = true;
		
		$oldConfig = null;
		if (is_file($cacheFile))
		{
			include $cacheFile;
			$oldConfig = $this->config;
		}
		
		$oldDefines = null;
		if (is_file($cacheDefinesFile))
		{
			include $cacheDefinesFile;
			$oldDefines = $this->defines;
		}
		
		// Config Dir for over write.
		$fileList = scandir($configDir);
		
		$configDefineArray = array();
		$configArray = array();
		$this->loadXmlConfigFile(PROJECT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.xml', $configArray);
		
		// -- Global constants.
						
		if (isset($computedDeps["OUTGOING_HTTP_PROXY_HOST"]))
		{
			$this->addConstant($configDefineArray, "OUTGOING_HTTP_PROXY_HOST", $computedDeps["OUTGOING_HTTP_PROXY_HOST"]);
			$this->addConstant($configDefineArray, "OUTGOING_HTTP_PROXY_PORT", $computedDeps["OUTGOING_HTTP_PROXY_PORT"]);
			if ($configArray['config']['http']['adapter'] == '\Zend\Http\Client\Adapter\Curl')
			{
				$configArray['config']['http']['curloptions'][CURLOPT_PROXY] = $computedDeps["OUTGOING_HTTP_PROXY_HOST"].':'.$computedDeps["OUTGOING_HTTP_PROXY_PORT"];
			}
			else if ($configArray['config']['http']['adapter'] == '\Zend\Http\Client\Adapter\Proxy' || $configArray['config']['http']['adapter'] == '\Zend\Http\Client\Adapter\Socket')
			{
				$configArray['config']['http']['adapter'] = '\Zend\Http\Client\Adapter\Proxy';
				$configArray['config']['http']['proxy_host'] = $computedDeps["OUTGOING_HTTP_PROXY_HOST"];
				$configArray['config']['http']['proxy_port'] = $computedDeps["OUTGOING_HTTP_PROXY_PORT"];
			}
		}
		
		foreach ($computedDeps['dependencies'] as $package) 
		{
			/* @var $package c_Package */
			if ($package->isFramework())
			{
				$this->addConstant($configArray['defines'], "FRAMEWORK_VERSION", $package->getVersion());
			}
			elseif ($package->isModule())
			{
				// -- Modules informations.
				$configArray['packageversion']['modules_' . $package->getName()] = array('VERSION' => $package->getVersion());
			}
		}
		
		switch ($configArray['defines']['LOGGING_LEVEL'])
		{
			case 'EXCEPTION':
				$configArray['defines']['LOGGING_LEVEL'] = 'ALERT';
			case 'ALERT':
				$configArray['defines']['LOGGING_PRIORITY'] = 1;
				break;
		
			case 'ERROR':
				$configArray['defines']['LOGGING_LEVEL'] = 'ERR';
			case 'ERR':
				$configArray['defines']['LOGGING_PRIORITY'] = 3;
				break;
			case 'NOTICE':
				$configArray['defines']['LOGGING_PRIORITY'] = 5;
				break;
			case 'DEBUG':
				$configArray['defines']['LOGGING_PRIORITY'] = 7;
				break;
			case 'INFO':
				$configArray['defines']['LOGGING_PRIORITY'] = 6;
				break;
			default:
				$configArray['defines']['LOGGING_LEVEL'] = 'WARN';
				$configArray['defines']['LOGGING_PRIORITY'] = 4;
				break;
		}
		
		$this->compileModulesConfig($configArray);
		$configArray['config']['packageversion'] = $configArray['packageversion'];
		unset($configArray['packageversion']);
		
		$this->loadXmlConfigFile(PROJECT_HOME . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.xml', $configArray);
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
		
		foreach (array('TMP_PATH' => true, 'DEFAULT_HOST' => true, 'PROJECT_ID' => true,
			'CHANGE_COMMAND' => false, 'DOCUMENT_ROOT' => false, 'PROJECT_LICENSE' => false, 'FAKE_EMAIL' => false,
			'PHP_CLI_PATH'  => true, 'DEVELOPMENT_MODE' => false) as $constName => $required)
		{
			if (isset($computedDeps[$constName]))
			{
				$configDefineArray[$constName] = $computedDeps[$constName];
			}
			else if ($required)
			{
				throw new Exception('Please define ' . $constName . ' in your change.properties  file');
			}
		}
		
		
		$configDefineArray['PHP_CLI_PATH'] = (isset($computedDeps["PHP_CLI_PATH"])) ? $computedDeps["PHP_CLI_PATH"] : '';
		
		if (!isset($configArray['config']['browsers']['frontoffice']) || !is_array($configArray['config']['browsers']['frontoffice']))
		{
			$configArray['config']['browsers']['frontoffice'] = array();
		}
		
		$content = "<?php // change_ConfigurationService::setDefineArray PART // \n";
		$configDefineArray = $this->prepareToExportDefineArray($configDefineArray);
		$content .= "change_ConfigurationService::getInstance()->setDefineArray(" . var_export($configDefineArray, true) . ');';		
		$this->writeFile($cacheDefinesFile, $content);
		$this->defines = $configDefineArray;
		$this->applyDefine();
		if ($configDefineArray['DEVELOPMENT_MODE'])
		{
			$this->buildDevelopmentDefineFile($configDefineArray);
		}
		
		$content = "<?php // change_ConfigurationService::setConfigArray PART // \n";
		$content .= "change_ConfigurationService::getInstance()->setConfigArray(" . var_export($configArray['config'], true) . ');';
		$this->writeFile($cacheFile, $content);
		$this->config = $configArray['config'];

		return ($oldConfig !== null) ? array("old" => array("defines" => $oldDefines, "config" => $oldConfig), 
				"current" => array("config" => $configArray['config'], "defines" => $configDefineArray)) : null;
	}
	
	/**
	 * @throws Exception
	 * @return string
	 */
	private function evaluateTmpPath()
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
			else  if (DIRECTORY_SEPARATOR === '\\')
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
				$TMP_PATH ='/tmp';
			}
		}
		return $TMP_PATH;
	}
	
	/**
	 * @param string $path
	 * @param string $content
	 * @throws Exception
	 */
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
	
	/**
	 * @param array $globalConstants
	 * @param string $name
	 * @param mixed $value
	 */
	private function addConstant(&$globalConstants, $name, $value)
	{
		$globalConstants[$name] = $value;
	}
	
	/**
	 * @param array $configDefineArray
	 * @return array
	 */
	private function prepareToExportDefineArray($configDefineArray)
	{
		foreach ($configDefineArray as $name => $value)
		{
			if (is_string($value))
			{
				//Match PROJECT_HOME . DIRECTORY_SEPARATOR . 'config'
				//Or CHANGE_CONFIG_DIR . 'toto'
				//But not Fred's Directory
				if (preg_match('/^(([A-Z][A-Z_0-9]+)|(\'[^\']*\'))(\s*\.\s*(([A-Z][A-Z_0-9]+)|(\'[^\']*\')))+$/', $value))
				{
					$configDefineArray[$name] = 'return ' . $value . ';';
				}
				elseif ($value === 'true')
				{
					$configDefineArray[$name] = true;
				}
				elseif ($value === 'false')
				{
					$configDefineArray[$name] = false;
				}
				elseif (is_numeric($value))
				{
					$configDefineArray[$name] = floatval($value);
				}
			}
		}
		return $configDefineArray;
	}
	
	/**
	 * @return Array<String, String>
	 */
	private function compilePackageVersion()
	{
		$packagesVersion = array();
		$files = glob(PROJECT_HOME . '/modules/*/change.xml');
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
	 * @param array $configArray
	 */
	private function compileModulesConfig(&$configArray)
	{
		$constants = array();
		$moduleXmlFiles = array();
		$files = glob(PROJECT_HOME . '/modules/*/config/module.xml');
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
				
				$moduleXmlOverrideFile = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'override', 'modules', $moduleName, 'config', 'module.xml'));
				if (is_readable($moduleXmlOverrideFile))
				{
					$this->loadXmlConfigFile($moduleXmlOverrideFile, $moduleConfig);
				}
				
				if (isset($moduleConfig['module']))
				{
					$pname = 'modules_' . $moduleName;
					$version = isset($configArray['packageversion'][$pname]['VERSION']) ? $configArray['packageversion'][$pname]['VERSION'] : null;
					$configArray['packageversion'][$pname] = array('VERSION' => $version, 'VISIBLE' => true, 'CATEGORY' => null, 'ICON' => 'package', 'USETOPIC' => false);
					
					foreach ($moduleConfig['module'] as $key => $value)
					{
						$key = strtoupper($key);
						switch ($key)
						{
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
		$configProjectPath = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'config', 'project.xml'));
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
	 * Function code found at: http://fr.php.net/manual/en/function.array-merge-recursive.php
	 * 
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
	
	/**
	 * @param array $defineArray
	 */
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
		$filename = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'build', 'project', 'dev_defines.php'));
		file_put_contents($filename, $content);
	}
	
	/**
	 * @return string
	 */
	public function getCurrentProfile()
	{
		if (file_exists(PROJECT_HOME.'/profile'))
		{
			$currentProfile = trim(file_get_contents(PROJECT_HOME.'/profile'));
		}
		else
		{
			$currentProfile = 'default';
		}
		return $currentProfile;
	}
}