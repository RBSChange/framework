<?php
class commands_AddCommand extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "<moduleName> <commandName> [--dev]";
	}
	
	/**
	 * @return string
	 */
	function getDescription()
	{
		return "adds a new command";
	}
	
	/**
	 * @return string[]
	 */
	function getOptions()
	{
		return array('--dev');
	}
		
	
	/**
	 * @param integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return string[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach ($this->getBootStrap()->getProjectDependencies() as $package)
			{
				/* @var $package c_Package */
				if ($package->isModule() || $package->isFramework())
				{
					$components[] = $package->getName();
				}
			}
			return $components;
		}
		return null;
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) == 2)
		{
			$package = $this->getPackageByName($params[0]);
			if (($package->isModule() || $package->isFramework()) && $package->isInProject())
			{
				if (preg_match('/^[a-z][a-z0-9]{1,50}+$/i', $params[1]))
				{
					return true;
				}
				else
				{
					$this->errorMessage('Invalid command name: ' . $params[1]);
				}
			}
			else
			{
				$this->errorMessage('Invalid module name: ' . $params[0]);
			}
		}
		return false;
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add new command ==");
		
		$this->loadFramework();
		$package = $this->getPackageByName($params[0]);
		
		$type = (isset($options['dev'])  && $options['dev']) ? 'dev' : '';
		$componentName = $package->getName();
		
		$commandName = ucfirst($params[1]);
		
		
		if ($package->isFramework())
		{
			if ($type == 'dev')
			{
				$commandFile = f_util_FileUtils::buildFrameworkPath('commands', 'dev', $commandName . '.php');
			}
			else
			{
				$commandFile = f_util_FileUtils::buildFrameworkPath('commands', $commandName . '.php');
			}
			$className = 'commands_' . $commandName;
			$commandCallName = $this->fixCommandName($commandName);
		}
		else
		{
			if ($type == 'dev')
			{
				$commandFile = f_util_FileUtils::buildModulesPath($componentName, 'commands', 'dev', $componentName . '_' . $commandName . '.php');
			}
			else
			{
				$commandFile = f_util_FileUtils::buildModulesPath($componentName, 'commands', $componentName . '_' . $commandName . '.php');
			}
			$className = 'commands_' . $componentName . '_' . $commandName;
			$commandCallName = $componentName . '.' .$this->fixCommandName($commandName);
		}
		
		if ($this->getBootStrap()->getCommand($commandCallName, false) !== null)
		{
			return $this->quitError('Command "' . $commandCallName . '" already exists in ' . $componentName . '".');
		}
		
		$generator = new builder_Generator('command');
		$generator->assign('name', $commandCallName);
		$generator->assign('commandTitle', $commandName[0].preg_replace('/([A-Z])/', ' ${0}', substr($commandName, 1)));
		$generator->assign('module', $componentName);
		$generator->assign('className', $className);

		$result = $generator->fetch('command.tpl');
		
		f_util_FileUtils::writeAndCreateContainer($commandFile, $result);
		change_AutoloadBuilder::getInstance()->appendFile($commandFile);
					
		return $this->quitOk("Command $commandCallName successfully created!\nPlease now edit $commandFile.php");
	}
}