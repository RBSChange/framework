<?php
class commands_Indexer extends commands_AbstractChangeCommand
{
	private $actions = array("clear", "clear-backoffice", "clear-frontoffice", "reset",
	"reset-frontoffice", "reset-backoffice", "rebuild-spell", "optimize", "import",
	"import-frontoffice", "import-backoffice");
	
	/**
	 * @return String
	 */
	function getUsage()
	{
		$usage = "<action>\nWhere action in:\n"; 
		foreach ($this->actions as $action)
		{
			$usage.= "- ".$action."\n";
		}
		return $usage; 
	}
	
	function getDescription()
	{
		return "manage the documents index";
	}
	
	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options)
	{
		if ($completeParamCount == 0)
		{
			return $this->actions;
		}
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1 && in_array($params[0], $this->actions);
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		// TODO: no bash intermediate
		$action = $params[0];
		$this->message("== Indexer: ".$action." ==");
		$this->loadFramework();
		$binPath = f_util_FileUtils::buildFrameworkPath('bin', 'solr', 'indexadmin.sh');
		$cmd = 'bash ' . $binPath . ' --' . $action;
		f_util_System::exec($cmd, null, false);
		$this->quitOk("Indexer: $action OK");
	}
}