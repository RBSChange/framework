<?php
class c_Changescript
{
	/**
	 * @var array<String, String[]>
	 */
	private $commandSections = array();

	/**
	 * @var array<String, String[]>
	 */
	private $ghostCommandSections = array();

	/**
	 * @var String
	 */
	private $baseDir;

	/**
	 * @var String
	 */
	private $scriptName;

	/**
	 * @var String
	 */
	private $name;

	/**
	 * @var String[]
	 */
	private $commands;

	/**
	 * @var String[]
	 */
	private $aliases;

	/**
	 * @var array<String, mixed>
	 */
	private $env;

	/**
	 * @var c_ChangeBootStrap
	 */
	private $bootStrap;

	/**
	 * @param String $scriptName
	 * @param String $baseDir
	 * @param String $confKey
	 */
	function __construct($scriptName, $baseDir, $confKey = null)
	{
		$this->baseDir = $baseDir;
		if ($confKey === null)
		{
			if (preg_match('/^([\/]{0,1}[^\/]+\/)*([a-zA-Z0-9]+)(.php){0,1}$/', $scriptName, $matches))
			{
				$confKey = $matches[2];
			}
			else
			{
				throw new Exception("Can not determine configuration key using $scriptName");
			}
		}
		$this->name = $confKey;
		$this->scriptName = $scriptName;
	}

	/**
	 * @param c_ChangeBootStrap $bootStrap
	 */
	function setBootStrap($bootStrap)
	{
		$this->bootStrap = $bootStrap;
	}
	
	/**
	 * @return c_ChangeBootStrap
	 */
	function getBootStrap()
	{
		return $this->bootStrap;
	}

	function getName()
	{
		return $this->name;
	}

	/**
	 * @return String
	 */
	function getBaseDir()
	{
		return $this->baseDir;
	}

	/**
	 * @return String
	 */
	function getScriptName()
	{
		return $this->scriptName;
	}

	/**
	 * @param String[] $args
	 */
	function execute($args)
	{
		try
		{
			if (count($args) == 0 || $args[0][0] == "-")
			{
				$this->usage();
			}
			else
			{
				$cmdName = $args[0];
				switch ($cmdName)
				{
					case "getCommands":
						$this->executeGetCommands($args);
						break;
					case "getOptions":
						$this->executeGetOptions($args);
						break;
					case "getParameters":
						$this->executeGetParameters($args);
						break;
					default:
						$this->_executeCommand($cmdName, array_slice($args, 1));
						break;
				}
			}
		}
		catch (Exception $e)
		{
			$this->exitOnError($e);
		}
		$this->exitOnSucces();
	}
	
	protected function executeGetCommands($args)
	{
		foreach ($this->getCommands() as $sectionLabel => $sectionCommands)
		{
			if ($sectionLabel == "_hidden_") {continue;}
			foreach (array_keys($sectionCommands) as $commandName)
			{
				echo $commandName." ";
			}
		}
		echo "\n";
	}

	protected function executeGetOptions($args)
	{	
		$cmdNameParam = $args[1];
		$cmdParam = $this->getCommand($cmdNameParam);
		$options = array("-h", "--help");
		$cmdOptions = $cmdParam->getOptions();
		if ($cmdOptions !== null)
		{
			$options = array_merge($cmdOptions, $options);
		}
		asort($options);
		echo join(" ",$options);
	}
	
	protected function executeGetParameters($args)
	{	
		$cmdNameParam = $args[1];
		$cmdParam = $this->getCommand($cmdNameParam);
		$completeParamCount = isset($args[2]) ? $args[2] : 0;
		$current = isset($args[3]) ? $args[3] : '';
		$parsedArgs = $this->parseArgs(array_slice($args, 6));
		$parameters = $cmdParam->getParameters($completeParamCount, $parsedArgs['params'], $parsedArgs['options'], $current);
		if ($parameters !== null)
		{
			echo join(" ", $parameters);
			asort($parameters);
		}
	}
	
	/**
	 * @return void
	 */
	public function loadFramework()
	{
		if (!class_exists("Framework", false))
		{
			foreach (spl_autoload_functions() as $fct) 
			{
				if (is_array($fct) && ($fct[0] instanceof cboot_ClassDirAnalyzer))
				{
					spl_autoload_unregister($fct);
				}
			}
			require_once(realpath(PROJECT_HOME."/framework/Framework.php"));
		}
	}
	
	/**
	 * Public executeCommand for other commands using
	 * @param String $cmdName
	 * @param String[] $args
	 */
	function executeCommand($cmdName, $args = array())
	{
	    $this->loadFramework();
	    echo f_util_System::execChangeCommand($cmdName, $args);
	}

	/**
	 * @param String $cmdName
	 * @param String[] $args
	 */
	protected function _executeCommand($cmdName, $args = array(), $embeded = false)
	{
		$command = $this->getCommand($cmdName);
		$command->setParent($this);
		$command->setEmbeded($embeded);
		if (in_array("-h", $args) || in_array("--help", $args))
		{
			$this->commandUsage($command);
			$this->exitOnSucces();
		}
		$command->setListeners($this->getListeners($command->getFullName()));
		$parsedArgs = $this->parseArgs($args);
		if (!$command->execute($parsedArgs['params'], $parsedArgs['options']))
		{
			if (!$embeded)
			{
				$this->message("== HELP ==");
				$this->commandUsage($command);
			}
			$this->exitOnError();
		}
	}
	
	protected function exitOnError($e = null, $exitCode = 1)
	{
		if ($e !== null)
		{
			$this->errorMessage("Error line ".$e->getLine()." (".$e->getFile()."): ".$e->getMessage());
			if (defined('CHANGE_DEV_MODE'))
			{
				$this->debugMessage($e->getTraceAsString());
			}
		}	
		exit($exitCode);
	}
	
	protected function exitOnSucces($exitCode = 0)
	{
		exit($exitCode);
	}
	
	/**
	 * Execute an external command
	 * @param String $cmd the command
	 * @return String the output of the command
	 * @throws Exception if the command did not runned correctly (exit code != 0)
	 */
	public function systemExec($cmd, $msg = null)
	{	
	   return f_util_System::exec($cmd, $msg);
	}
	
	/**
	 * @param unknown_type $commandName
	 * @return array<String, String[]> pointcut => commandNames
	 */
	private function getListeners($commandName)
	{
		$listeners = array();
		$this->getListenersForSections($commandName, $this->commandSections, $listeners);
		$this->getListenersForSections($commandName, $this->ghostCommandSections, $listeners);
		return $listeners;
	}
	
	/**
	 * @param array<String, String[]> $commandSections
	 * @param array<String, String[]> $listeners
	 * @return unknown_type
	 */
	private function getListenersForSections($commandName, $commandSections, &$listeners)
	{
		foreach ($commandSections as $sectionName => $paths)
		{
			foreach ($paths as $path)
			{
				$configFile = $path."/".$commandName."-listeners.xml";
				//echo "Test $configFile\n";
				if (file_exists($configFile))
				{
					$doc = new DOMDocument();
					if (!$doc->load($configFile))
					{
						throw new Exception("Could not load $configFile");
					}
					foreach ($doc->documentElement->getElementsByTagName("listener") as $listenerElem)
					{
						$pointcut = $listenerElem->getAttribute("pointcut");
						if (!isset($listeners[$pointcut]))
						{
							$listeners[$pointcut] = array();
						}
						$listeners[$pointcut][] = $listenerElem->getAttribute("command");
					}
				}
			}	
		}
	}

	/**
	 * @param String $path
	 * @param String $sectionName
	 */
	function addCommandDir($path, $sectionName = "default")
	{
		$this->resetCommands();
		if (!isset($this->commandSections[$sectionName]))
		{
			$this->commandSections[$sectionName] = array();
		}
		$this->commandSections[$sectionName][] = $path;
		if (is_dir($path."/default"))
		{
			$this->addCommandDir($path."/default");
		}
	}

	private function resetCommands()
	{
		$this->commands = null;
		$this->aliases = null;
	}
	
	function appendToAutoload($path, $followDeps = false)
	{
		$this->bootStrap->appendToAutoload(realpath($path));
	}
	
	function getProperties($propFileName)
	{
		return $this->bootStrap->getProperties($propFileName);
	}

	/**
	 * Commands that you can cal but that not appears in usage()
	 * @param String $path
	 * @param String $sectionName
	 */
	function addGhostCommandDir($path, $sectionName = "default")
	{
		$this->resetCommands();
		if (!isset($this->ghostCommandSections[$sectionName]))
		{
			$this->ghostCommandSections[$sectionName] = array();
		}
		$this->ghostCommandSections[$sectionName][] = $path;
		if (is_dir($path."/default"))
		{
			$this->addGhostCommandDir($path."/default");
		}
	}

	/**
	 * @param String $name
	 * @param mixed $value
	 */
	function setEnvVar($name, $value)
	{
		$this->env[$name] = $value;
	}

	/**
	 * @param String $name
	 * @return mixed
	 */
	function getEnvVar($name)
	{
		return $this->env[$name];
	}

	// private methods

	protected function usage()
	{
		echo "Usage: ".basename($this->scriptName)." <commandName> [-h]\n";
		echo " where <commandName> in: \n";
		foreach ($this->getCommands() as $sectionLabel => $sectionCommands)
		{	
			if ($sectionLabel == "_hidden_") {continue;}
								
			if ($sectionLabel != "Default" && f_util_ArrayUtils::isNotEmpty($sectionCommands))
			{
				echo "\n== $sectionLabel ==\n";
			}
			foreach ($sectionCommands as $cmdName => $command)
			{
				$byCategory = array();
				ob_start();
				echo " - ".$cmdName;
				$description = $command->getDescription();
				if ($description !== null)
				{
					echo ": ".$description."";
				}
				$alias = $command->getAlias();
				if ($alias !== null)
				{
					echo " ($alias)";
				}
				echo "\n";
				$cat = $command->getCategory();
				if (!isset($byCategory[$cat]))
				{
					$byCategory[$cat] = array();
				}
				$byCategory[$cat][] = ob_get_clean();
				ksort($byCategory);
				foreach ($byCategory as $cat => $texts)
				{
					if ($cat != null)
					{
						echo "=== $cat ===\n";
					}
					foreach ($texts as $text)
					{
						echo $text;
					}
				}
			}
		}
	}

	/**
	 * Get the value of options (--<optionName>[=value])
	 * @param String[] $args
	 * @return array("options" => array<String, String>, "params" => String[]) where the option array key is the option name, the potential option value or true
	 */
	protected final function parseArgs($args)
	{
		$options = array();
		$params = array();

		foreach ($args as $key => $arg)
		{
			if (preg_match("/^--([^=]*)(=(.*)){0,1}$/", $arg, $matches) > 0)
			{
				if (isset($matches[3]))
				{
					$optValue = $matches[3];
				}
				else
				{
					$optValue = true;
				}
				$options[$matches[1]] = $optValue;
			}
			else
			{
				$params[] = $arg;
			}
		}

		return array("options" => $options, "params" => $params);
	}

	/**
	 * @param c_ChangescriptCommand $command
	 */
	protected function commandUsage($command)
	{
		$description = $command->getDescription();
		if ($description !== null)
		{
			echo ucfirst($command->getCallName()).": ".$description."\n";
		}
		echo "Usage: ".basename($this->getScriptName())." ".$command->getCallName()." ".$command->getUsage()."\n";
	}

	/**
	 * @param String $commandName
	 * @return c_ChangescriptCommand
	 */
	public function getCommand($commandName)
	{
		$this->getCommands();
		foreach ($this->commands as $sectionCommands)
		{
			if (isset($sectionCommands[$commandName]))
			{
				$cmd = $sectionCommands[$commandName];
				$cmd->setCallName($commandName);
				return $cmd;
			}
		}
		foreach ($this->aliases as $sectionAliases)
		{
			if (isset($sectionAliases[$commandName]))
			{
				$cmd = $sectionAliases[$commandName];
				$cmd->setCallName($commandName);
				return $cmd;
			}
		}
		throw new Exception("Unable to find command $commandName");
	}

	public function getCommandByClassName($className)
	{
		$commandClassName = "commands_".$className;
		$command = new $commandClassName;
		if (!($command instanceof c_ChangescriptCommand))
		{
			throw new Exception("$commandClassName is not a c_ChangescriptCommand class");
		}
		return $command;
	}

	private static $commandPattern = '/^([a-zA-Z0-9_]+)\.php$/';

	private function _getCommands($commandDirs, $sectionName)
	{
		if ($sectionName == "default")
		{
			$sectionLabel = "Default";
			$cmdNamePrefix = "";
		}
		else
		{
			$sectionInfo = explode("|", $sectionName);
			$cmdNamePrefix = $sectionInfo[0].".";
			$sectionLabel = $sectionInfo[1];
		}

		$commands = array();
		$ghostCommands = array();
		$aliases = array();
		foreach ($commandDirs as $cmdDir)
		{
			foreach (scandir($cmdDir) as $file)
			{
				$matches = array();
				if (!preg_match(self::$commandPattern, $file, $matches) || preg_match('/^Abstract.*\.php$/', $file))
				{
					continue;
				}

				$commandName = $matches[1];
				$command = $this->getCommandByClassName($commandName);
				$commandParts = explode("_", $commandName);
				$commandName = end($commandParts);
				$alias = strtolower($commandName[0]).substr($commandName, 1);
				if (isset($aliases[$alias]))
				{
					$otherCommand = $aliases[$alias];
					throw new Exception("Duplicate command alias $alias, used by ".$command->getName()." and ".$otherCommand->getName());
				}
				$aliases[$cmdNamePrefix.$alias] = $command;

				if ($command->isHidden())
				{
					$ghostCommands[$cmdNamePrefix.$command->getName()] = $command;
				}
				else
				{
					$commands[$cmdNamePrefix.$command->getName()] = $command;
				}
				$alias = $command->getAlias();
				if ($alias !== null)
				{
					$alias = $cmdNamePrefix.$alias;
					if (isset($aliases[$alias]))
					{
						$otherCommand = $aliases[$alias];
						throw new Exception("Duplicate command alias $alias, used by ".$command->getName()." and ".$otherCommand->getName());
					}
					$aliases[$alias] = $command;
				}
			}
		}
		ksort($commands);
		$this->commands["_hidden_"] = array_merge($ghostCommands, $this->commands["_hidden_"]);
		return array($commands, $aliases, $sectionLabel);
	}

	/**
	 * @return c_ChangescriptCommand[]
	 */
	protected final function getCommands()
	{
		if ($this->commands === null)
		{
			$this->commands = array();
			$this->commands["_hidden_"] = array();
			
			foreach ($this->commandSections as $sectionName => $commandDirs)
			{
				list($commands, $aliases, $sectionLabel) = $this->_getCommands($commandDirs, $sectionName);
				ksort($commands);
				$this->commands[$sectionLabel] = $commands;
				$this->aliases[$sectionLabel]= $aliases;
			}
			
			foreach ($this->ghostCommandSections as $sectionName => $commandDirs)
			{
				list($commands, $aliases, $sectionLabel) = $this->_getCommands($commandDirs, $sectionName);
				$sectionLabel = ($sectionLabel === "Default")? "Developper" : "Developper $sectionLabel";
				$this->commands[$sectionLabel] = $commands;
				$this->aliases[$sectionLabel]= $aliases;
			}
		}
		return $this->commands;
	}
	
	public final function debugMessage($message)
	{
		if (defined("C_DEBUG") && C_DEBUG == true)
		{
			$this->message($message);
		}
	}

	/**
	 * print colorized message (green)
	 * @param String $message
	 */
	public final function okMessage($message)
	{
		$this->message($message, self::FG_GREEN);
	}
	
	/**
	 * print colorized message (magenta)
	 * @param String $message
	 */
	public final function warnMessage($message)
	{
		$this->message($message, self::FG_MAGENTA);
	}

	/**
	 * print colorized message (red)
	 * @param String $message
	 */
	public final function errorMessage($message)
	{
		$this->message($message, self::FG_RED);
	}
	
	/**
	 * @param String $message
	 * @param const $color one of the FG_XXX constants, optional
	 */
	public final function message($message, $color = null)
	{
		$this->echoMessage($message."\n", $color);
	}
	
	/**
	 * @param String $message
	 */
	public final function rawMessage($message)
	{
		$this->echoMessage($message);
	}
	
	/**
	 * (taken from phing AnsiColorLogger)
	 * To change these colors use the following chart:
	 *
	 * <B>ANSI COLOR LOGGER CONFIGURATION</B>
	 *
	 * Format for AnsiColorLogger.*=
	 *  Attribute;Foreground;Background
	 *
	 *  Attribute is one of the following:
	 *  0 -> Reset All Attributes (return to normal mode)
	 *  1 -> Bright (Usually turns on BOLD)
	 *  2 -> Dim
	 *  3 -> Underline
	 *  5 -> link
	 *  7 -> Reverse
	 *  8 -> Hidden
	 *
	 *  Foreground is one of the following:
	 *  30 -> Black
	 *  31 -> Red
	 *  32 -> Green
	 *  33 -> Yellow
	 *  34 -> Blue
	 *  35 -> Magenta
	 *  36 -> Cyan
	 *  37 -> White
	 *
	 *  Background is one of the following:
	 *  40 -> Black
	 *  41 -> Red
	 *  42 -> Green
	 *  43 -> Yellow
	 *  44 -> Blue
	 *  45 -> Magenta
	 *  46 -> Cyan
	 *  47 -> White
	 */
	const ATTR_NORMAL = 0;
	const ATTR_BRIGHT = 1;
	const ATTR_DIM = 2;
	const ATTR_UNDERLINE = 3;
	const ATTR_BLINK = 5;
	const ATTR_REVERSE = 7;
	const ATTR_HIDDEN = 8;

	const FG_BLACK = 30;
	const FG_RED = 31;
	const FG_GREEN = 32;
	const FG_YELLOW = 33;
	const FG_BLUE = 34;
	const FG_MAGENTA = 35;
	const FG_CYAN = 36;
	const FG_WHITE = 37;

	const BG_BLACK = 40;
	const BG_RED = 41;
	const BG_GREEN = 42;
	const BG_YELLOW = 44;
	const BG_BLUE = 44;
	const BG_MAGENTA = 45;
	const BG_CYAN = 46;
	const BG_WHITE = 47;

	const PREFIX = "\x1b[";
	const SUFFIX = "m";
	const SEPARATOR = ';';
	const END_COLOR = "\x1b[m";
	
	
	protected function echoMessage($message, $color = null)
	{
		if ($color === null)
		{
			echo $message;
		}
		else
		{
			echo self::PREFIX . self::ATTR_DIM . self::SEPARATOR.$color. self::SUFFIX . $message. self::END_COLOR;
		}
	}
}