<?php
/**
 * @package framework.config
 * Project parser is used to converted project.XX.xml file in php file useable by the framework
 */
class config_ProjectParser
{
	
	/**
	 * @return boolean
	 */
	public static function isCompiled()
	{
		$cacheConfigDir = PROJECT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config';
		$cacheFile = $cacheConfigDir."/project.php";
		return is_readable($cacheFile);
	}

	/**
	 * @param string $path
	 * @param string $value
	 * @return string old value
	 */
	public static function addProjectConfigurationEntry($path, $value)
	{
		$sections = array();
		foreach (explode('/', $path) as $name) 
		{
			if (trim($name) != '') {$sections[] = trim($name);}
		}		
		if (count($sections) < 2) 
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
	public static function addProjectConfigurationNamedEntry($path, $entryName, $value)
	{
		if (empty($entryName) || ($value !== null && !is_string($value)))
		{
			return false;
		}	
		$sections = array('config');
		foreach (explode('/', $path) as $name) 
		{
			if (trim($name) != '') {$sections[] = trim($name);}
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
					if ($node->nodeType !== XML_ELEMENT_NODE) {continue;}
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
			if ($node->nodeType !== XML_ELEMENT_NODE) {continue;}
			if (!isset($configArray[$tagName])) {$configArray[$tagName] = array();}
			$this->populateConfigArray($node, $configArray[$tagName]);
		}
		
		if (!isset($configArray[$tagName]) || is_string($configArray[$tagName]) || count($configArray[$tagName]) == 0)
		{
			$configArray[$tagName] = trim($xmlElement->textContent);
		}
	}
	
	/**
	 * Merge specific config file of project with defulat config file and save config file in cache/config
	 * @return array old and current configuration
	 */
	public function execute($computedDeps)
	{	
		// Config dir.
		$configDir = PROJECT_HOME . DIRECTORY_SEPARATOR . 'config';
		if (!is_dir($configDir))
		{
			return;
		}

		// Cache config dir.
		$cacheConfigDir = PROJECT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config';
		if (file_exists(PROJECT_HOME.'/profile'))
		{
			$currentProfile = trim(file_get_contents(PROJECT_HOME.'/profile'));
		}
		else
		{
			$currentProfile = 'default';
		}	
		
		
		$cacheFile = $cacheConfigDir."/project.php";
		$cacheDefinesFile = $cacheConfigDir."/project.defines.php";
	
		$oldDefines = array();
		if (is_file($cacheDefinesFile))
		{
			$lines = file($cacheDefinesFile, FILE_IGNORE_NEW_LINES);
			if ($lines !== false)
			{
				unset($lines[0]);
				foreach ($lines as $lineIndex => $line)
				{
					$matches = null;
					if (preg_match('/define\(\'(.*)\', (.*)\);\}?$/', $line, $matches))
					{
						$oldDefines[$matches[1]] = $matches[2];
					}
				}
			}
		}
		
		$oldConfig = null;
		if (is_file($cacheFile))
		{
			$lines = file($cacheFile, FILE_IGNORE_NEW_LINES);
			if ($lines !== false)
			{
				unset($lines[0]);
				foreach ($lines as $lineIndex => $line)
				{
					$matches = null;
					if (substr($line, 0, 20) === 'Framework::$config =') 
					{
						$lines[$lineIndex] = '$oldConfig ='.substr($line, 20);
					}
				}
				$oldConfigCode = join("\n", $lines);
				eval($oldConfigCode);
			}
		}

		// Config Dir for over write.
		$fileList = scandir($configDir);
		
		$configArray = array();
		$this->loadXmlConfigFile(PROJECT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.xml', $configArray);
				
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
		
		$this->compileModulesConfig($configArray);
		$configArray['config']['packageversion'] = $configArray['packageversion'];
		unset($configArray['packageversion']);
							
		$this->loadXmlConfigFile(PROJECT_HOME . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.xml', $configArray);
		$profilFile = $configDir . DIRECTORY_SEPARATOR . "project.".$currentProfile.".xml";
		if (is_readable($profilFile))
		{
			$this->loadXmlConfigFile($profilFile, $configArray);
		}
		else
		{
			die($profilFile);
		}
		
		// -- Global constants.
		foreach (array('INCLUDE_PATH', 'ZEND_FRAMEWORK_PATH', 'WWW_GROUP', 'TMP_PATH', 
			'CHANGE_COMMAND', 'DOCUMENT_ROOT', 'PROJECT_LICENSE', 'FAKE_EMAIL', 
			'PHP_CLI_PATH', 'DEVELOPMENT_MODE') as $constName) 
		{
			if (isset($computedDeps[$constName]))
			{
				$this->addConstant($configArray['defines'], $constName, $computedDeps[$constName]);
			}
			elseif ($constName === 'TMP_PATH' && !isset($configArray['defines']['TMP_PATH']))
			{
				$this->addConstant($configArray['defines'], $constName, $this->evaluateTmpPath());			
			}
		}
		
		if (isset($computedDeps["OUTGOING_HTTP_PROXY_HOST"]))
		{
			$this->addConstant($configArray['defines'], "OUTGOING_HTTP_PROXY_HOST", $computedDeps["OUTGOING_HTTP_PROXY_HOST"]);
			$this->addConstant($configArray['defines'], "OUTGOING_HTTP_PROXY_PORT", $computedDeps["OUTGOING_HTTP_PROXY_PORT"]);
			if ($configArray['config']['http']['adapter'] == 'Zend_Http_Client_Adapter_Curl')
			{
				$configArray['config']['http']['curloptions'][CURLOPT_PROXY] = $computedDeps["OUTGOING_HTTP_PROXY_HOST"].':'.$computedDeps["OUTGOING_HTTP_PROXY_PORT"];
			}
			else if ($configArray['config']['http']['adapter'] == 'Zend_Http_Client_Adapter_Proxy' || $configArray['config']['http']['adapter'] == 'Zend_Http_Client_Adapter_Socket')
			{
				$configArray['config']['http']['adapter'] = 'Zend_Http_Client_Adapter_Proxy';
				$configArray['config']['http']['proxy_host'] = $computedDeps["OUTGOING_HTTP_PROXY_HOST"];
				$configArray['config']['http']['proxy_port'] = $computedDeps["OUTGOING_HTTP_PROXY_PORT"];
			}
		}
		
		if (!isset($configArray['config']['browsers']['frontoffice']) || !is_array($configArray['config']['browsers']['frontoffice']))
		{
			$configArray['config']['browsers']['frontoffice'] = array();
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
				
		$defineLines = array();
		foreach ($configArray['defines'] as $constName => $value) 
		{
			$defineLines[] = $this->buildDefine($constName, $value);
		}		
		unset($configArray['defines']);
		
		// Write in file
		$content = "<?php // DEFINE PART auto generated by ProjectParser.\n";
		$content .= implode("\n", $defineLines);
		$this->writeFile($cacheDefinesFile, $content);
		
		$content = "<?php // Config PART generated by ProjectParser.\n";
		$content .= 'Framework::$config = ' . var_export($configArray['config'], true) . ';';
		$this->writeFile($cacheFile, $content);
		
		if ($oldConfig !== null)
		{
			$currentDefines = array();
			foreach ($defineLines as $defineLine)
			{
				$matches = null;
				if (preg_match('/define\(\'(.*)\', (.*)\);\}?$/', $defineLine, $matches))
				{
					$currentDefines[$matches[1]] = $matches[2];
				}
			}
			$currentConfig = array("config" => $configArray['config'], "defines" => $currentDefines);
		}
		
		return ($oldConfig !== null) ? array("old" => array("defines" => $oldDefines, "config" => $oldConfig), "current" => $currentConfig) : null;
	}
	
	public function evaluateTmpPath()
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
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	private function buildDefine($name, $value)
	{
		if ($value === true || $value === 'true')
		{
			return 'if (!defined(\'' . $name . '\')){define(\'' . $name . '\', true);}';
		}
		else if ($value === false || $value === 'false')
		{
			return 'if (!defined(\'' . $name . '\')){define(\'' . $name . '\', false);}';
		}
		else if (!is_numeric($value))
		{
			$quoteCount = substr_count($value, "'");
			if ($quoteCount == 0 || ($quoteCount & 1))
			{
				$value = var_export($value, true);
			}
		}
		return 'if (!defined(\'' . $name . '\')){define(\'' . $name . '\', ' . $value . ');}';
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
				if (!is_readable($moduleXmlFile)) {continue;}
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
					$pname = 'modules_'.$moduleName;
					$configArray['packageversion'][$pname] = array_merge($configArray['packageversion'][$pname], 
						array('VISIBLE' => true, 'CATEGORY' => null, 'ICON' => 'package', 'USETOPIC' => false));	
									
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
	
	
	
	private function searchForDocInjections()
	{
		$injections = array();
		foreach (glob(PROJECT_HOME . '/modules/*/persistentdocument/*.xml') as $docFile)
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
				$injections[$matches[1]."/".$matches[2]] = $moduleName."/".$docName;
			}
		}
		return $injections;
	}
}
