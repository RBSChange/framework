<?php
/**
 * @package framework.config
 * Project parser is used to converted project.XX.xml file in php file useable by the framework
 */
class config_ProjectParser
{
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
		$configDir = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'config';
		if (!is_dir($configDir))
		{
			return;
		}

		// Cache config dir.
		$cacheConfigDir = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config';
		
		$currentProfile = (defined("PROFILE") ? PROFILE : trim(file_get_contents(WEBEDIT_HOME."/profile")));
		$cacheFile = $cacheConfigDir."/project.".$currentProfile.".xml.php";
		$oldConfig = null;
		$oldDefines = array();
		if (is_file($cacheFile))
		{
			$lines = file($cacheFile, FILE_IGNORE_NEW_LINES);
			if ($lines !== false)
			{
				unset($lines[0]);
				foreach ($lines as $lineIndex => $line)
				{
					$matches = null;
					if (preg_match('/^define\(\'(.*)\', (.*)\);$/', $line, $matches))
					{
						$lines[$lineIndex] = '$oldDefines[\'' .$matches[1] .'\'] = \''.str_replace("'", "\\'", $matches[2]).'\';';
					}
					elseif (substr($line, 0, 20) == 'Framework::$config =') 
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
		$this->loadXmlConfigFile(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.xml', $configArray);
		
		
		
		// -- Global constants.
		if (isset($computedDeps["PEAR_DIR"]))
		{
			$this->addConstant($configArray['defines'], "PEAR_DIR", $computedDeps["PEAR_DIR"]);
		}
		if (isset($computedDeps["LOCAL_REPOSITORY"]))
		{
			$this->addConstant($configArray['defines'], "LOCAL_REPOSITORY", $computedDeps["LOCAL_REPOSITORY"]);
		}
		if (isset($computedDeps["WWW_GROUP"]))
		{
			$this->addConstant($configArray['defines'], "WWW_GROUP", $computedDeps["WWW_GROUP"]);
		}
		if (isset($computedDeps["OUTGOING_HTTP_PROXY_HOST"]))
		{
			$this->addConstant($configArray['defines'], "OUTGOING_HTTP_PROXY_HOST", $computedDeps["OUTGOING_HTTP_PROXY_HOST"]);
			$this->addConstant($configArray['defines'], "OUTGOING_HTTP_PROXY_PORT", $computedDeps["OUTGOING_HTTP_PROXY_PORT"]);
		}
		$fDeps = $computedDeps['change-lib']['framework'];
		$this->addConstant($configArray['defines'], "FRAMEWORK_VERSION", $fDeps['version']);

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
		$profilFile = $configDir . DIRECTORY_SEPARATOR . "project.".$currentProfile.".xml";
		if (is_readable($profilFile))
		{
			$this->loadXmlConfigFile($profilFile, $configArray);
		}
		else
		{
			die($profilFile);
		}
		
		if (!isset($configArray['defines']['TMP_PATH']))
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
			$configArray['defines']['TMP_PATH'] = $TMP_PATH;					
		}
	
		if (isset($computedDeps["DEVELOPMENT_MODE"]) && $computedDeps["DEVELOPMENT_MODE"])
		{
			$configArray['defines']['AG_DEVELOPMENT_MODE'] = true;
		}
		
		$configArray['defines']['PHP_CLI_PATH'] = (isset($computedDeps["PHP_CLI_PATH"])) ?  $computedDeps["PHP_CLI_PATH"] : '';
		
		if (!isset($configArray['config']['browsers']['frontoffice']) || !is_array($configArray['config']['browsers']['frontoffice']))
		{
			$configArray['config']['browsers']['frontoffice'] = array();
		}
				
		$defineLines = array();
		foreach ($configArray['defines'] as $constName => $value) 
		{
			$defineLines[] = $this->buildDefine($constName, $value);
		}		
		unset($configArray['defines']);
		
		
		// Write in file
		$content = "<?php // File auto generated by ProjectParser.";
		$content .= "\n // DEFINE PART // \n";
		$content .= implode("\n", $defineLines);
		
		$content .= "\n // Framework::\$config PART // \n";
		$content .= "Framework::\$config = " . var_export($configArray['config'], true) . ';';
		
		if ($oldConfig !== null)
		{
			$currentDefines = array();
			foreach ($defineLines as $defineLine)
			{
				$matches = null;
				if (preg_match('/^define\(\'(.*)\', (.*)\);$/', $defineLine, $matches))
				{
					$currentDefines[$matches[1]] = $matches[2];
				}
			}
			$currentConfig = array("config" => $configArray['config'], "defines" => $currentDefines);
		}
		
		$this->writeFile($cacheConfigDir."/project.".$currentProfile.".xml.php", $content);

		return ($oldConfig !== null) ? array("old" => array("defines" => $oldDefines, "config" => $oldConfig), "current" => $currentConfig) : null;
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
			return 'define(\'' . $name . '\', true);';
		}
		else if ($value === false || $value === 'false')
		{
			return 'define(\'' . $name . '\', false);';
		}
		else if (!is_numeric($value))
		{
			$quoteCount = substr_count($value, "'");
			// For strings, I quote the ones that contain an odd number of single quotes.
			// This generally means that the developper really wanted to use a single quote.
			// For example:
			// <define name="AG_WEBAPP_NAME">Fred's Change</define>
			if ($quoteCount == 0 || ($quoteCount & 1))
			{
				$value = var_export($value, true);
			}
		}
		return 'define(\'' . $name . '\', ' . $value . ');';
	}

	/**
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
				$version =null;
				
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
	private function compileModulesConfig(&$configArray, $computedModulesDeps)
	{
		$constants = array();
		$moduleXmlFiles = array();		
		$files = glob(WEBEDIT_HOME . '/modules/*/config/module.xml');
		if (is_array($files) || count($files) > 0)
		{
			foreach ($files as $moduleXmlFile)
			{
				if (!is_readable($moduleXmlFile)) {continue;}
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
					$pname = 'modules_'.$moduleName;
					$version = isset($configArray['packageversion'][$pname]) ? $configArray['packageversion'][$pname] : null;	
					$configArray['packageversion'][$pname] = array('ENABLED' => true, 'VISIBLE' => true, 'CATEGORY' => null, 
						'ICON' => 'package', 'USETOPIC' => false, 'VERSION' => $version);
								
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
				$injections[$matches[1]."/".$matches[2]] = $moduleName."/".$docName;
			}
		}
		return $injections;
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
			if (trim($name) != '') {
				$sections[] = trim($name);
			}
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
			if (trim($name) != '') {
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
}
