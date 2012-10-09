<?php
abstract class c_ChangescriptCommand
{
	/**
	 * @var c_ChangeBootStrap
	 */	
	private $bootStrap;
	
	/**
	 * @var Boolean
	 */
	private $devMode = false;
	
	/**
	 * @var String
	 */
	private $callName;
	
	/**
	 * @var String
	 */
	private $sectionName;	
	
	/**
	 * @var Boolean
	 */
	private $httpOutput  = false;
	
	/**
	 * @param c_ChangeBootStrap $bootStrap
	 * @param string $sectionName
	 * @param boolean $devMode
	 */
	public function __construct($bootStrap, $sectionName, $devMode)
	{
		$this->httpOutput = defined('HTTP_MODE');
		$this->bootStrap = $bootStrap;
		$this->sectionName = $sectionName;
		$cmdNamePrefix = ($sectionName === 'framework') ? '' : $sectionName . '.'; 
		$this->callName = $cmdNamePrefix . $this->getName();
		$this->devMode = $devMode;
	}
		
	/**
	 * @return string
	 */
	function getName()
	{
		$shortClassName = preg_replace('/^commands_(?:[^_]*_){0,1}(.*)$/', '${1}', get_class($this));
		return strtolower($shortClassName[0].preg_replace('/([A-Z])/', '-${0}', substr($shortClassName, 1)));
	}
	
	/**
	 * @return boolean default false
	 */
	function httpOutput($httpOutput = null)
	{
		$result = $this->httpOutput;
		if ($httpOutput !== null)
		{
			$this->httpOutput = ($httpOutput == true);
		}
		return $result;
	}
		
	/**
	 * @return boolean default false
	 */
	function isHidden()
	{
		return false;
	}
	
	/**
	 * @return string
	 */
	function getCallName()
	{
		return $this->callName;
	}
	
	/**
	 * @return string
	 */
	public function getSectionName()
	{
		return $this->sectionName;
	}

	/**
	 * @return string
	 */
	function getAlias()
	{
		return null;
	}
	
	
	/**
	 * @param boolean $devMode
	 * @return boolean
	 */
	function devMode($devMode = null)
	{
		$result = $this->devMode;
		if ($devMode !== null)
		{
			$this->devMode = ($devMode == true);
		}
		return $result;
	}
	
	/**
	 * @return c_ChangeBootStrap
	 */
	protected function getBootStrap()
	{
		return $this->bootStrap;
	}
	
	/**
	 * @return boolean
	 */
	protected function	inReleaseDevelopement()
	{
		return $this->bootStrap->inReleaseDevelopement();
	}
	
	/**
	 * @return boolean
	 */
	public function	inDevelopement()
	{
		return $this->bootStrap->inDevelopement();
	}
	
	/**
	 * @return string
	 */
	abstract function getUsage();

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return null;
	}
	
	/**
	 * @return array<array<name, target>>
	 */
	public function getEvents()
	{
		return null;
	}
	

	private $listeners;
	
	
	public function addListeners($name, $commandName, $args = array())
	{
		if (!is_array($this->listeners)) {$this->listeners = array();}
		$this->listeners[$name][] = array('command' => $commandName, 'args' => $args);
	}
	
	
	/**
	 * @param string $name
	 * @param array $params
	 * @param array $options
	 */
	protected function startPointCut($name, $params, $options)
	{
		if (is_array($this->listeners) && isset($this->listeners[$name]))
		{
			foreach ($this->listeners[$name] as $commandInfo)
			{
			//	$this->executeCommand($commandInfo['command'], $commandInfo['args']);
			}
		}
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_Changescript::parseArgs($args)
	 */
	abstract function _execute($params, $options);

	/**
	 * @return string[]
	 */
	function getOptions()
	{
		return null;
	}

	/**
	 * @return string
	 */
	protected function getChangeCmdName()
	{
		if ($this->httpOutput)
		{
			return '';
		}
		return $this->getBootStrap()->getProperties()->getProperty("CHANGE_COMMAND", 'change.php');
	}

	
	/**
	 * @param integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @param string $current current parameter that is being completed (can be empty)
	 * @return string[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		return null;
	}
	
	
	/**
	 * @param string $message
	 * @param integer $color validation = 32 warn = 35  error = 31;
	 */
	protected function echoMessage($message, $color = null)
	{
		if ($this->httpOutput)
		{
			if ($color === -1)
			{
				echo $message;				
			}
			else
			{
				$class = ($color === null) ? "row_std" : "row_" . $color;
				echo "<span class=\"$class\">", nl2br(htmlspecialchars($message)), "</span>";	
			}			
		}
		else
		{
			if ($color === 31) 
			{
				echo 'ERROR: ';	
			}
			elseif ($color === 35)
			{
				echo 'WARNING: ';	
			}
			echo $message;
		}
	}
	
	/**
	 * @param string $message
	 * @param string $type
	 */
	public function log($message, $type = "info")
	{
		switch ($type)
		{
			case "error":
				$this->echoMessage($message . PHP_EOL, 31);
				break;
			case "warn":
				$this->echoMessage($message . PHP_EOL, 35);
				break;
			case "validation":
				$this->echoMessage($message . PHP_EOL, 32);
				break;
			case "raw":
				$this->echoMessage($message, -1);
				break;
			default:
				$this->echoMessage($message . PHP_EOL);
		}
	}
	
	/**
	 * @return string
	 */
	public function commandUsage()
	{
		$description = $this->getDescription();
		if ($description !== null)
		{
			$this->log(ucfirst($this->getCallName()).": ".$description);
		}
		$this->log("Usage: ".$this->getChangeCmdName()." ".$this->getCallName()." ".$this->getUsage());
	}

	/**
	 * @param string $message
	 * @param const $color one of the constants, optional
	 */
	protected function message($message, $color = null)
	{
		switch ($color) 
		{

			case 31:
			case 32:
			case 35:
				$this->echoMessage($message . PHP_EOL, $color);
				break;
			case -1:
				$this->echoMessage($message, $color);
				break;	
			default:
				$this->echoMessage($message . PHP_EOL);
				break;
		}
	}
	
	/**
	 * @param string $componentName
	 * @return c_Package
	 */
	protected function getPackageByName($componentName)
	{
		$parts = explode('/', $componentName);
		if (count($parts) === 1)
		{
			if ($parts[0] === 'framework')
			{
				$parts = array(null, 'framework');
			}
			else
			{
				$parts = array('modules', $parts[0]);
			}
		}
		elseif (count($parts) !== 2 || !in_array($parts[0], array('modules', 'themes', 'libs')))
		{
			return c_Package::getNewInstance(null, $componentName, PROJECT_HOME);
		}
		
		$package = c_Package::getNewInstance($parts[0], $parts[1], PROJECT_HOME);
		$projectDependencies = $this->getBootStrap()->getProjectDependencies();
		if (isset($projectDependencies[$package->getKey()]))
		{
			return $projectDependencies[$package->getKey()];
		}
		return $package;
	}
	
	private $errorCount = 0;
	
	protected function hasError()
	{
		return $this->errorCount > 0;
	}

	protected function getErrorCount()
	{
		return $this->errorCount;
	}
	
	/**
	 * print colorized message (red)
	 * @param string $message
	 */
	protected function errorMessage($message, $increment = true)
	{
		if ($increment)
		{
			$this->errorCount = $this->errorCount +1;
		}
		$this->log($message, 'error');
	}
	
	protected function debugMessage($message)
	{
		$this->log($message);
	}
	
	/**
	 * print colorized message (magenta)
	 * @param string $message
	 */
	protected function warnMessage($message)
	{
		$this->log($message, 'warn');
	}
	
	/**
	 * print colorized message (green)
	 * @param string $message
	 */
	protected function okMessage($message)
	{
		$this->log($message, 'validation');
	}
	
	/**
	 * print colorized message (green)
	 * @param string $message
	 */
	protected function rawMessage($message)
	{
		$this->log($message, 'raw');
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		return true;
	}

	/**
	 * @param string $question
	 * @param string $defaultValue
	 * @param boolean $toLowerCase
	 * @return string the answer the user typed in, lowercase or the defaultValue
	 */
	protected final function question($question, $defaultValue = null, $toLowerCase = true)
	{
		$this->rawMessage($question." ");
		$answer = trim(fgets(STDIN));
		if ($answer == "")
		{
			return ($toLowerCase ? strtolower($defaultValue) : $defaultValue);
		}
		return ($toLowerCase ? strtolower($answer) : $answer);
	}

	/**
	 * @param string $question
	 * @return boolean true if setAutoY or if the user entered y
	 */
	protected final function yesNo($question)
	{
		return $this->question($question." (y/N)", "n") == "y";
	}

	/**
	 * @param string[] $args
	 * @return boolean true if the command runned correctly
	 */
	final function execute($params, $options)
	{
		if (!$this->validateArgs($params, $options))
		{
			$this->log($this->getChangeCmdName() . ' ' . $this->getCallName() . ' ' . $this->getUsage());
			return $this->quitError('Inavlid command argruments');
		}
		
		$this->startPointCut("before", $params, $options);

		$ret = $this->_execute($params, $options);
				
		$res = ($ret === null || $ret === true);
		
		if ($res)
		{
			$this->startPointCut("after", $params, $options);
		}
		return $res;
	}

	/**
	 * @param string $msg
	 * @return null
	 */
	protected function quit($msg = "Exiting...")
	{
		$this->message("=> ".$msg . PHP_EOL);
		return null;
	}

	/**
	 * @param string $msg
	 * @return null
	 */
	protected final function quitOk($msg = "Exiting...")
	{
		$this->okMessage("=> ".$msg . PHP_EOL);
		return null;
	}

	/**
	 * @param string $msg
	 * @return false
	 */
	protected final function quitError($msg)
	{
		$this->errorMessage("=> ".$msg . PHP_EOL);
		return false;
	}

	/**
	 * @param string $msg
	 * @return false
	 */
	protected final function quitWarn($msg)
	{
		$this->warnMessage("=> ".$msg . PHP_EOL);
		return false;
	}

	/**
	 * @return string
	 */
	protected function getAuthor()
	{
		$user = getenv("USER");
		if (empty($user))
		{
			return null;
		}
		return $user;
	}

	/**
	 * @return string
	 */
	protected function getUser()
	{
		return $this->getAuthor();
	}

	/**
	 * @deprecated
	 * @return string
	 */
	protected function getApacheGroup()
	{
		Framework::deprecated("no more implemented");
		return '';
	}

	/**
	 * @return array
	 */
	protected function getComputedDeps()
	{
		return $this->getBootStrap()->getComputedDependencies();
	}

	/**
	 * @return void
	 */
	protected function loadFramework()
	{
		if (!class_exists("Framework", false))
		{			
			foreach (spl_autoload_functions() as $fct) 
			{
				if (is_array($fct) && ($fct[0] instanceof c_ChangeBootStrap))
				{
					spl_autoload_unregister($fct);
				}
			}
			require_once PROJECT_HOME . '/Change/Application.php';
			\Change\Application::getInstance()->start();
		}
	}

	/**
	 * @return string
	 */
	protected function getProfile()
	{
		if (file_exists("profile"))
		{
			return trim(f_util_FileUtils::read("profile"));
		}
		// Define profile
		$profile = trim($this->getAuthor());
		$this->warnMessage("No profile file, using user name as profile (".$profile.")");
		f_util_FileUtils::write("profile", $profile);
		return $profile;
	}

	/**
	 * @return util_Properties
	 */
	protected function getProperties()
	{
		return $this->getBootStrap()->getProperties("change");
	}
	
	/**
	 * @param string $cmdName
	 * @return string
	 */
	protected function fixCommandName($cmdName)
	{
		return strtolower($cmdName[0].preg_replace('/([A-Z])/', '-${0}', substr($cmdName, 1)));
	}
	
	/**
	 * @param string $cmdName
	 * @param string[] $args
	 */
	protected function executeCommand($cmdName, $args = array())
	{	
		$this->message('Execute Command: ' . $cmdName . '...');
		if (!class_exists('Framework', false))
		{
			$this->executeCommandInProcess($cmdName, $args);
			return;
		}
		
		ob_start();
		$fixedCommandName = $this->fixCommandName($cmdName);
	   	echo f_util_System::execChangeCommand($fixedCommandName, $args);
		$this->rawMessage(trim(ob_get_clean()) . PHP_EOL);
	}

	/**
	 * @param string $cmdName
	 * @param string[] $args
	 */
	protected function executeCommandInProcess($cmdName, $args = array())
	{
		ob_start();
		$fixedCommandName = $this->fixCommandName($cmdName);
		if (!is_array($args)) {$args = array();}
		array_unshift($args, $fixedCommandName);
		$this->getBootStrap()->execute($args);
		$this->rawMessage(trim(ob_get_clean()) . PHP_EOL);		
	}
	
	/**
	 * @deprecated use $this
	 */
	protected final function getParent()
	{
		return $this;
	}
	
	/**
	 * @deprecated use executeCommand
	 */
	protected final function forward($cmdName, $args)
	{
		$this->executeCommand($cmdName, $args);
	}
	
	/**
	 * @deprecated
	 */
	protected function systemExec($cmd, $msg = null)
	{
		ob_start();
		echo f_util_System::exec($cmd, $msg);
		$this->rawMessage(trim(ob_get_clean()));
	}
}