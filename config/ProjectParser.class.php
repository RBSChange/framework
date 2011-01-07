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
			/*
			if ($configArray[$tagName] === 'true')
			{
				$configArray[$tagName] = true;
			} 
			elseif ($configArray[$tagName] === 'false')
			{
				$configArray[$tagName] = false;
			}
			*/
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
		$this->loadXmlConfigFile(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.xml', $configArray);
		
		
		
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
	

		// -- Modules informations.
		$configArray['packageversion'] = $this->compilePackageVersion();
		$this->compileModulesConfig($configArray);
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
		
		$this->constructBrowsersList($configArray);
				
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
	 * @param array $array
	 */
	private function constructBrowsersList(&$array)
	{
		if (!isset($array['browsers']))
		{
			$array['config']['browsers'] = array('frontoffice' => array(), 'backoffice' => array());
		}
		else
		{
			if (!isset($array['browsers']['frontoffice']))
			{
				$array['browsers']['frontoffice'] = array();
			}
			if (!isset($array['browsers']['backoffice']))
			{
				$array['browsers']['backoffice'] = array();
			}
			$array['config']['browsers'] = $array['browsers'];
			unset($array['browsers']);
		}
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
				$name = null; $version =null;
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
					$configArray['packageversion'][$pname] = array('ENABLED' => true, 'VISIBLE' => true, 'CATEGORY' => null, 'ICON' => 'package', 'USETOPIC' => false, 'VERSION' => $version);
					
					foreach ($moduleConfig['module'] as $key => $value)
					{
						$key = strtoupper($key);
						switch ($key) 
						{
							case 'ENABLED':
							case 'VISIBLE':
							case 'CATEGORY':
							case 'ICON':
							case 'USETOPIC':
								if ($value === 'true')
								{
									$value = true;
								}
								else if ($value === 'false')
								{
									$value = false;
								}
								$configArray['packageversion'][$pname][$key] = $value;
								break;
							default:	
								$constantName = 'MOD_' . strtoupper($moduleName) . '_' . $key;
								$configArray['defines'][$constantName] = $value;
							break;
						}
					}
				}
				if (isset($moduleConfig['project']))
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
}
