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
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 2;
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
			if (is_dir("framework"))
			{
				$components[] = "framework";
			}
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
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
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add new command ==");
		
		$this->loadFramework();
		
		$type = ($options['dev'] ? 'dev' : '');
		$componentName = $params[0];
		$commandName = ucfirst($params[1]);
		if ($componentName === "framework")
		{
			$commandFolder = f_util_FileUtils::buildWebeditPath('framework', 'change' . $type . '-commands');
			$commandFile = $commandFolder . DIRECTORY_SEPARATOR . $commandName . '.php';
			$class = 'command_' . $commandName;
		}
		else if (!ModuleService::getInstance()->moduleExists($componentName))
		{
			return $this->quitError("Component $componentName does not exits");
		}
		else
		{
			$commandFolder = f_util_FileUtils::buildWebeditPath('modules', $componentName, 'change' . $type . '-commands');
			$commandFile = $commandFolder . DIRECTORY_SEPARATOR . $componentName . '_' . $commandName . '.php';
			$class = 'command_' . $componentName . '_' . $commandName;
		}
		
		if (file_exists($commandFile))
		{
			$this->warnMessage('Command "' . $commandFile . '" already exists in ' . $componentName . '".');
		}
		else
		{
			f_util_FileUtils::mkdir($commandFolder);
		}
		
		$generator = new builder_Generator('command');
		$generator->assign_by_ref('author', $this->getAuthor());
		$generator->assign_by_ref('name', $commandName);
		$generator->assign_by_ref('module', $componentName);
		$generator->assign_by_ref('date', date('r'));
		$result = $generator->fetch('command.tpl');
		
		f_util_FileUtils::write($commandFile, $result);
		ClassResolver::getInstance()->appendToAutoloadFile($class, realpath($commandFile));
		ChangeProject::getInstance()->executeTask('--refresh-cli-autoload');
		
		return $this->quitOk("Command $commandName successfully created!\nPlease now edit $commandFile.php");
	}
	
	/**
	 * @return String[]
	 */
	function getOptions()
	{
		return array("--dev");
	}
}