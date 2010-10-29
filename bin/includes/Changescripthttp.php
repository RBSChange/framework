<?php
class c_Changescripthttp extends c_Changescript
{
	

	protected function echoMessage($message, $color = null)
	{
		$class = ($color === null) ? "row_std" : "row_" . $color;
		echo "<span class=\"$class\">";
		echo nl2br(htmlspecialchars($message));
		echo "</span>";
	}
	
	/**
	 * @see c_Changescript::usage()
	 *
	 */
	protected function usage()
	{
		$this->message("Commands list:");
		foreach ($this->getCommands() as $sectionLabel => $sectionCommands)
		{
			if ($sectionLabel == "_ghost_")
			{
				continue;
			}
			if ($sectionLabel != "Default" && f_util_ArrayUtils::isNotEmpty($sectionCommands))
			{
				$this->message("== $sectionLabel ==");
			}
			foreach ($sectionCommands as $cmdName => $command)
			{
				$byCategory = array();
				$description = $command->getDescription();
				$cat = $command->getCategory();
				if (!isset($byCategory[$cat]))
				{
					$byCategory[$cat] = array();
				}
				$byCategory[$cat][] = array($cmdName, $description);			
				ksort($byCategory);
				foreach ($byCategory as $cat => $cmdInfos)
				{
					if ($cat != null)
					{
						$this->message("=== $cat ===");
					}
					foreach ($cmdInfos as $cmd)
					{
						echo " - <a href=\"javascript:selectWebCommand('" . $cmd[0] . "')\">" . $cmd[0] . "</a> " . htmlspecialchars($cmd[1]) . "<br />";
					}
				}
			}
		}		
	}
	
	/**
	 * @param c_ChangescriptCommand $command
	 */
	protected function commandUsage($command)
	{
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
	
	/**
	 * @see c_Changescript::executeGetParameters()
	 *
	 * @param array $args
	 */
	protected function executeGetParameters($args)
	{
		$cmdNameParam = $args[1];
		$cmdParam = $this->getCommand($cmdNameParam);
		$completeParamCount = count($args) - 2;
		$parsedArgs = $this->parseArgs(array_slice($args, 2));
		$parameters = $cmdParam->getParameters($completeParamCount, $parsedArgs['params'], $parsedArgs['options'], '');
		if ($parameters !== null)
		{
			$this->message($cmdNameParam . " parameter values:");
			foreach ($parameters as $parameter) 
			{
				$p = implode(' ', array_merge($parsedArgs['params'], array($parameter)));
				echo " - <a href=\"javascript:onSelectParam('$p')\">$p</a><br />";
			}
		}
		else
		{
				$this->message($cmdNameParam . " has no parameter value.");
		}
	}
	
	/**
	 * @see c_Changescript::executeGetCommands()
	 *
	 * @param unknown_type $args
	 */
	protected function executeGetCommands($args)
	{
		$response = new DOMDocument('1.0', 'utf-8');
		$response->loadXML('<cmds />');
		foreach ($this->getCommands() as $sectionLabel => $sectionCommands)
		{
			if ($sectionLabel == "_ghost_")
			{
				continue;
			}
			foreach ($sectionCommands as $commandName => $command)
			{
				$elem = $response->documentElement->appendChild($response->createElement('cmd'));
				$elem->setAttribute('name', $commandName);
				if ($command->getAlias())
				{
					$elem->setAttribute('alias', $command->getAlias());
				}
				$elem->setAttribute('tt', $command->getDescription());
			}
		}		
		echo $response->saveXML();
	}

}