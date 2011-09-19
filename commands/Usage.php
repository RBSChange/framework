<?php
class commands_Usage extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[--dev] [--prod]";
	}
	
	/**
	 * @see c_ChangescriptCommand::isHidden()
	 */
	public function isHidden()
	{
		return true;
	}
	
	function getOptions()
	{
		return array('dev', 'prod');
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		if (isset($options['dev']) || isset($options['prod']))
		{
			$devCmds = isset($options['dev']);
			$prodCmds = isset($options['prod']);
		}
		else
		{
			$devCmds = true;
			$prodCmds = true;
		}
		
		$cmdName = isset($params[0]) ? $params[0] : '';
		if ($this->httpOutput())
		{
			switch ($cmdName) 
			{
				case 'getUsage':
					$this->executeCommandUsageHTTP($params);
					break;
				case 'getCommands':
					$this->executeGetCommandsHTTP();
					break;	
				case 'getParameters':
					$this->executeGetParametersHTTP($params);
					break;
				case 'getOptions':
					$this->executeGetOptions($params);
					break;		
				default:
					$this->executeUsageHTTP($prodCmds, $devCmds);
					break;
			}
		}
		else
		{
			
			switch ($cmdName) 
			{
				case 'getUsage':
					$this->executeCommandUsage($params);
					break;
				case 'getCommands':
					$this->executeGetCommands();
					break;	
				case 'getParameters':
					$this->executeGetParameters($params);
					break;
				case 'getOptions':
					$this->executeGetOptions($params);
					break;		
				default:
					$this->executeUsage($prodCmds, $devCmds);
					break;
			}
		}
	}
	
	protected function executeUsage($prodCmds, $devCmds)
	{	
		$this->log("Usage: ".$this->getChangeCmdName()." <commandName> [-h]");
		$this->log(" where <commandName> in: ");
		$commands = $this->getBootStrap()->getCommands();
		usort($commands, array($this, 'compareCommand'));
		$sectionName = '';
		$devMode = false;
		
		foreach ($commands as $command)
		{
			/* @var $command c_ChangescriptCommand */
			if ($command->isHidden() || ($command->devMode() && !$devCmds) || (!$command->devMode() && !$prodCmds)) {continue;}
			if ($command->devMode() !== $devMode)
			{
				$devMode = $command->devMode();
				$this->log(PHP_EOL . PHP_EOL. "=== Developer commands ===");
			}
			
			if ($sectionName != $command->getSectionName())
			{
				$sectionName = $command->getSectionName();
				$sectionLabel = $sectionName == 'framework' ? "== Default ==" : "== Module $sectionName ==";
				$this->log(PHP_EOL . $sectionLabel);
			}

			$msg = array(" - ". $command->getCallName());
			$description = $command->getDescription();
			if ($description !== null)
			{
				$msg[] =  ": ".$description."";
			}
			$alias = $command->getAlias();
			if ($alias !== null)
			{
				$msg[] = " ($alias)";
			}
			$this->log(implode('', $msg));
		}
	}
	
	/**
	 * @see c_Changescript::usage()
	 *
	 */
	protected function executeUsageHTTP($prodCmds, $devCmds)
	{
		$this->message("Commands list:");
		$commands = $this->getBootStrap()->getCommands();
		usort($commands, array($this, 'compareCommand'));
		$sectionName = '';
		$devMode = false;
		
		foreach ($commands as $command)
		{
			/* @var $command c_ChangescriptCommand */
			if ($command->isHidden() || ($command->devMode() && !$devCmds) || (!$command->devMode() && !$prodCmds)) {continue;}
			if ($command->devMode() !== $devMode)
			{
				$devMode = $command->devMode();
				$this->log(PHP_EOL . PHP_EOL. "=== Developer commands ===");
			}
			
			if ($sectionName != $command->getSectionName())
			{
				$sectionName = $command->getSectionName();
				$sectionLabel = $sectionName == 'framework' ? "== Default ==" : "== Module $sectionName ==";
				$this->log(PHP_EOL . $sectionLabel);
			}
			
			$this->rawMessage(" - <a href=\"javascript:selectWebCommand('" . $command->getCallName() . "')\">" . $command->getCallName() . "</a> " . htmlspecialchars($command->getDescription()) . "<br />");
		}		
	}
	/**
	 * 
	 * @param c_ChangescriptCommand $c1
	 * @param c_ChangescriptCommand $c2
	 */
	function compareCommand($c1, $c2)
	{
		if ($c1->devMode() == $c2->devMode())
		{
			if ($c1->getSectionName() === $c2->getSectionName())
			{
				return $c1->getCallName() < $c2->getCallName() ? -1 : 1;
			}
			elseif ($c1->getSectionName() === 'framework')
			{
				return -1;
			}
			elseif ($c2->getSectionName() === 'framework')
			{
				return 1;
			}
			return  $c1->getSectionName() < $c2->getSectionName() ? -1 : 1;
		}
		return $c1->devMode() ? 1 : -1;
	}
	
	protected function executeGetCommands()
	{
		$commands = $this->getBootStrap()->getCommands();
		foreach ($commands as $command)
		{
			/* @var $command c_ChangescriptCommand */
			if ($command->isHidden()) {continue;}
			echo $command->getCallName()." ";
		}
		echo "\n";
	}
	
	/**
	 */
	protected function executeGetCommandsHTTP()
	{
		$response = new DOMDocument('1.0', 'utf-8');
		$response->loadXML('<cmds />');
		$commands = $this->getBootStrap()->getCommands();
		foreach ($commands as $command)
		{
			/* @var $command c_ChangescriptCommand */
			if ($command->isHidden()) {continue;}
			$elem = $response->documentElement->appendChild($response->createElement('cmd'));
			$elem->setAttribute('name', $command->getCallName());
			if ($command->getAlias())
			{
				$elem->setAttribute('alias', $command->getAlias());
			}
			$elem->setAttribute('tt', $command->getDescription());
		}		
		$this->rawMessage($response->saveXML());
	}
	
	
	protected function executeGetOptions($args)
	{	
		$cmdNameParam = $args[1];
		$command = $this->getBootStrap()->getCommand($cmdNameParam);
		$options = array("-h", "--help");
		$cmdOptions = $command->getOptions();
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
		$command = $this->getBootStrap()->getCommand($cmdNameParam);
		$completeParamCount = isset($args[2]) ? $args[2] : 0;
		$current = isset($args[3]) ? $args[3] : '';
		$parsedArgs =  $this->getBootStrap()->parseArgs(array_slice($args, 6));
		$parameters = $command->getParameters($completeParamCount, $parsedArgs['params'], $parsedArgs['options'], $current);

		if ($parameters !== null)
		{
			echo join(" ", $parameters);
			asort($parameters);
		}
	}
	
	/**
	 * @param array $args
	 */
	protected function executeGetParametersHTTP($args)
	{
		$cmdNameParam = $args[1];
		$command = $this->getBootStrap()->getCommand($cmdNameParam);
		$completeParamCount = count($args) - 2;
		$parsedArgs = $this->getBootStrap()->parseArgs(array_slice($args, 2));
		$parameters = $command->getParameters($completeParamCount, $parsedArgs['params'], $parsedArgs['options'], '');
		if ($parameters !== null)
		{
			$this->message($cmdNameParam . " parameter values:");
			foreach ($parameters as $parameter) 
			{
				$p = implode(' ', array_merge($parsedArgs['params'], array($parameter)));
				$this->rawMessage(" - <a href=\"javascript:onSelectParam('$p')\">$p</a><br />");
			}
		}
		else
		{
			$this->message($cmdNameParam . " has no parameter value.");
		}
	}
	
	/**
	 * @return string
	 */
	public function commandUsage()
	{
		$this->log("Usage: ".$this->getChangeCmdName()." ".$this->getUsage());
	}
	

	/**
	 * @param string[] $args
	 */
	protected function executeCommandUsage($args)
	{
		$cmdNameParam = $args[1];
		if ($cmdNameParam === '-h' || $cmdNameParam === '-help')
		{
			$command = $this;
		}
		else
		{
			$command = $this->getBootStrap()->getCommand($cmdNameParam);
		}
		$command->commandUsage();
	}
	
	/**
	 * @param string[] $args
	 */
	protected function executeCommandUsageHTTP($args)
	{
		$cmdNameParam = $args[1];
		if ($cmdNameParam === '-h' || $cmdNameParam === '-help')
		{
			$command = $this;
		}
		else
		{
			$command = $this->getBootStrap()->getCommand($cmdNameParam);
		}
		
		$description = $command->getDescription();
		if ($description !== null)
		{
			$this->okMessage(ucfirst($command->getCallName()).": ".$description);
		}
		$usage = $command->getUsage();
		if ($usage)
		{
			$usageLines = explode("\n", $usage);
			$this->message("Usage: ".$command->getCallName()." ".$usageLines[0]);
			$parameters = $command->getParameters(0, array(), array(), '');
			if ($parameters !== null)
			{
				$this->message($command->getCallName() . " parameter values:");
				foreach ($parameters as $parameter) 
				{
					echo " - <a href=\"javascript:onSelectParam('$parameter')\">$parameter</a><br />";
				}
			}
			else
			{
				$this->message($command->getCallName() . " has no parameter value.");
			}
		}
	}
}