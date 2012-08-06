<?php
class commands_AddCommand extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		$usage = "<moduleName> <commandName> [options]\n";
		$usage .= "where options in:";
		$usage .= "  --dev: create a dev command\n";
		return $usage;
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "adds a new command";
	}
	
	/**
	 * @return String[]
	 */
	function getOptions()
	{
		return array("--dev");
	}
		
	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components[] = "framework";
			foreach (glob(f_util_FileUtils::buildWebeditPath('modules', '*') , GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
		return null;
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) == 2)
		{
			$moduleName = strtolower($params[0]);
			if (!file_exists(f_util_FileUtils::buildWebeditPath('modules', $moduleName)))
			{
				$this->errorMessage('Module "' . $moduleName . '" not found.');
				return false;
			}
			
			$cmdName = $this->buildCommandName($params[1]);
			echo 'CMD: ',  $params[1], ', ', $cmdName	, PHP_EOL;
			if ($cmdName === null)
			{
				$this->errorMessage('Name "' . $params[1] . '" is not valid for a command');
				return false;
			}
			
			$filePath = $this->getCommandFilePath($moduleName, $cmdName, isset($options['dev']) ? ($options['dev'] == true) : false);	
			if (file_exists($filePath))
			{
				$this->errorMessage('Command "' . $moduleName . ' ' .$cmdName. '" already exist.');
				return false;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param string $param
	 * @return string|null
	 */
	protected function buildCommandName($param)
	{
		if (strpos($param, '-') === false)
		{
			$cmdName = preg_replace_callback('/[A-Z]/', array($this, 'toMinus'), ucfirst($param));
			if ($cmdName[0] === '-')
			{
				$cmdName = substr($cmdName, 1);
			}
		}
		else
		{
			$cmdName = $param;
		}
		
		$cmdp = array();
		foreach (explode('-', strtolower($cmdName)) as $cp)
		{
			$cmdp[] = ($cp === '') ? '_' : $cp;
		}
		$cmdName = implode('-', $cmdp);
		
		if (preg_match('/^[a-z0-9-]+$/', $cmdName))
		{
			return $cmdName;
		}
		return null;
	}
	
	/**
	 * @param string[] $match
	 * @return string
	 */
	protected function toMinus($match)
	{
		return '-' . strtolower($match[0]);
	}
	
	/**
	 * @param string $moduleName
	 * @param string $cmdName
	 * @return string
	 */
	protected function buildClassName($moduleName, $cmdName)
	{
		$commandName = implode('', array_map('ucfirst', explode('-', $cmdName)));
		return ($moduleName === "framework") ? 'commands_' . $commandName : 'commands_' . $moduleName . '_' . $commandName;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $cmdName
	 * @param boolean $dev
	 */
	protected function getCommandFilePath($moduleName, $cmdName, $dev = false)
	{
		$type = $dev ? 'dev' : '';
		$commandName = implode('', array_map('ucfirst', explode('-', $cmdName)));
		if ($moduleName === "framework")
		{
			return f_util_FileUtils::buildWebeditPath('framework', 'change' . $type . '-commands', $commandName . '.php');
		}
		else
		{
			return f_util_FileUtils::buildWebeditPath('modules', $moduleName, 'change' . $type . '-commands', $moduleName . '_' . $commandName . '.php');
		}
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add new command ==");
		$this->loadFramework();
		$dev = isset($options['dev']) ? ($options['dev'] == true) : false;
		$moduleName = $params[0];
		$commandName = $this->buildCommandName($params[1]);
		$commandFile = $this->getCommandFilePath($moduleName, $commandName, $dev);
		$className = $this->buildClassName($moduleName, $commandName);		 
		$commandFolder = dirname($commandFile);
		

		f_util_FileUtils::mkdir($commandFolder);
		
		$generator = new builder_Generator('command');
		$generator->assign('name', $commandName);
		$generator->assign('className', $className);
		$generator->assign('extend', $dev ? 'commands_AbstractChangedevCommand' : 'commands_AbstractChangeCommand');

		$result = $generator->fetch('command.tpl');
		
		f_util_FileUtils::write($commandFile, $result);
		
		ClassResolver::getInstance()->appendToAutoloadFile($className, realpath($commandFile));
		$cmdPrefix = $moduleName === 'framework' ? '' : $moduleName.'.';
		$this->log('Command ' .  $cmdPrefix . $commandName . ' successfully created.');
		return $this->quitOk('Please now edit: ' . $commandFile);
	}
}