<?php
abstract class  c_ChangescriptCommand
{

	/**
	 * @var Boolean
	 */
	private $autoYes = false;

	/**
	 * @var c_ChangeScript
	 */
	private $parent;

	/**
	 * @var Boolean
	 */
	private $embeded;
	
	/**
	 * @var String
	 */
	private $callName;

	/**
	 * @return String
	 */
	function getName()
	{
		$shortClassName = preg_replace('/^commands_(?:[^_]*_){0,1}(.*)$/', '${1}', get_class($this));
		return strtolower($shortClassName[0].preg_replace('/([A-Z])/', '-${0}', substr($shortClassName, 1)));
	}
	
	function getFullName()
	{
		$class = new ReflectionClass($this);
		$fileName = $class->getFileName();
		$defineDir = dirname($fileName);
		if (basename($defineDir) == "default")
		{
			$dir = $defineDir."/../..";
			return $this->getName();
		}
		else
		{
			$dir = $defineDir."/..";
		}
		$prefixDir = basename(realpath($dir));
		$matches = null;
		if (preg_match('/^(.*?)-[0-9].*$/', $prefixDir, $matches))
		{
			$prefixDir = $matches[1]; 
		}
		if ($prefixDir == "framework")
		{
			return $this->getName();
		}
		return $prefixDir.".".$this->getName();
	}
	
	/**
	 * @return Boolean default false
	 */
	function isHidden()
	{
		return false;
	}
	
	/**
	 * @return String
	 */
	function getCallName()
	{
		if ($this->callName === null)
		{
			return $this->getName();
		}
		return $this->callName;
	}
	
	/**
	 * @param String $callName
	 */
	function setCallName($callName)
	{
		$this->callName = $callName;
	}

	/**
	 * @return String
	 */
	function getAlias()
	{
		return null;
	}
	
	function getCategory()
	{
		return null;
	}

	/**
	 * @return String
	 */
	abstract function getUsage();

	/**
	 * @return String
	 */
	function getDescription()
	{
		return null;
	}

	/**
	 * @param c_Changescript $parent
	 */
	function setParent($parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return c_Changescript
	 */
	protected final function getParent()
	{
		return $this->parent;
	}

	/**
	 * @param String $env
	 * @return mixed
	 */
	protected final function getEnvVar($name)
	{
		return $this->getParent()->getEnvVar($name);
	}

	/**
	 * executes a command (getParent()->executeCommand()) and returns the command output
	 * @param String $cmdName
	 * @param String[] $args
	 * @return String the command output
	 */
	protected final function forward($cmdName, $args)
	{
		ob_start();
		$this->getParent()->executeCommand($cmdName, $args);
		return trim(ob_get_clean());
	}
	
	private $listeners;
	
	/**
	 * @param array<String, String[]> $listeners pointcut => commandName[]
	 */
	function setListeners($listeners)
	{
		$this->listeners = $listeners;	
	}
	
	/**
	 * @var array<String, Boolean>
	 */
	private $reachedPointCuts;
	
	/**
	 * @param String $pointcut
	 */
	protected function startPointCut($pointcut)
	{
		if (isset($this->listeners[$pointcut]) && !isset($this->reachedPointCuts[$pointcut]))
		{
			$this->reachedPointCuts[$pointcut] = true;
			$parent = $this->getParent();
			foreach ($this->listeners[$pointcut] as $commandName)
			{
				$parent->executeCommand($commandName);
			}
		}
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_Changescript::parseArgs($args)
	 */
	abstract function _execute($params, $options);

	/**
	 * @return String[]
	 */
	function getOptions()
	{
		return null;
	}

	/**
	 * @param Boolean $embeded
	 */
	function setEmbeded($embeded)
	{
		$this->embeded = $embeded;
	}

	/**
	 * @return Boolean
	 */
	protected function isEmbeded()
	{
		return $this->embeded;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @param String $current current parameter that is being completed (can be empty)
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		return null;
	}

	function log($message, $type = "info")
	{
		switch ($type)
		{
			case "error":
				$this->errorMessage($message);
				break;
			default:
				$this->message($message);
		}
	}

	/**
	 * @param String $message
	 * @param const $color one of the FG_XXX constants, optional
	 */
	protected function message($message, $color = null)
	{
		$this->getParent()->message($message, $color);
	}
	
	private $errorCount = 0;

	/**
	 * print colorized message (red)
	 * @param String $message
	 */
	protected function errorMessage($message, $increment = true)
	{
		if ($increment)
		{
			$this->errorCount = $this->errorCount +1;
		}
		$this->getParent()->errorMessage($message);
	}
	
	protected function debugMessage($message)
	{
		$this->getParent()->debugMessage($message);
	}
	
	/**
	 * print colorized message (magenta)
	 * @param String $message
	 */
	protected function warnMessage($message)
	{
		$this->getParent()->warnMessage($message);
	}
	
	/**
	 * print colorized message (green)
	 * @param String $message
	 */
	protected function okMessage($message)
	{
		$this->getParent()->okMessage($message);
	}
	
	/**
	 * print colorized message (green)
	 * @param String $message
	 */
	protected function rawMessage($message)
	{
		$this->getParent()->okMessage($message);
	}
	
	protected function hasError()
	{
		return $this->errorCount > 0;
	}

	protected function getErrorCount()
	{
		return $this->errorCount;
	}

	/**
	 * @param String $relativePath
	 * @return String
	 */
	protected function getResourcePath($relativePath)
	{
		return realpath($this->getParent()->getBaseDir()."/resources/".$relativePath);
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		return true;
	}

	/**
	 * @param String $question
	 * @param String $defaultValue
	 * @param Boolean $toLowerCase
	 * @return String the answer the user typed in, lowercase or the defaultValue
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
	 * @param Boolean $auto
	 * @return c_ChangescriptCommand
	 */
	function setAutoYes($auto)
	{
		$this->autoYes = $auto;
		return $this;
	}

	/**
	 * @param String $question
	 * @return Boolean true if setAutoY or if the user entered y
	 */
	protected final function yesNo($question)
	{
		return $this->autoYes || $this->question($question." (y/N)", "n") == "y";
	}

	/**
	 * @param String[] $args
	 * @return Boolean true if the command runned correctly
	 */
	final function execute($params, $options)
	{
		if (!$this->validateArgs($params, $options))
		{
			return false;
		}
		$this->reachedPointCuts = array();
		$this->startPointCut("before");
		$ret = $this->_execute($params, $options);
		$res = ($ret === null || $ret === true);
		if ($res)
		{
			$this->startPointCut("after");
		}
		return $res;
	}

	/**
	 * Execute an external command
	 * @param String $cmd the command
	 * @return String the output of the command
	 * @throws Exception if the command did not runned correctly (exit code != 0)
	 */
	protected function systemExec($cmd, $msg = null)
	{
		return $this->getParent()->systemExec($cmd, $msg);
	}

	/**
	 * @return null
	 */
	protected function quit($msg = "Exiting...")
	{
		if (!$this->embeded)
		{
			$msg = "=> ".$msg;
		}
		$this->message("$msg\n");
		return null;
	}

	/**
	 * @return null
	 */
	protected final function quitOk($msg = "Exiting...")
	{
		$this->startPointCut("after");
		if (!$this->embeded)
		{
			$msg = "=> ".$msg;
		}
		$this->okMessage("$msg\n");
		return null;
	}

	/**
	 * @return false
	 */
	protected final function quitError($msg)
	{
		if (!$this->embeded)
		{
			$msg = "=> ".$msg;
		}
		$this->errorMessage("$msg\n");
		return false;
	}

	/**
	 * @return false
	 */
	protected final function quitWarn($msg)
	{
		if (!$this->embeded)
		{
			$msg = "=> ".$msg;
		}
		$this->warnMessage("$msg\n");
		return null;
	}
}