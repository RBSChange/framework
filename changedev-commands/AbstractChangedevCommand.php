<?php
abstract class commands_AbstractChangedevCommand extends commands_AbstractChangeCommand
{
	/**
	 * @param string $cmdName
	 * @param array $params
	 */
	protected function changecmd($cmdName, $params = array())
	{
		$this->loadFramework();
		$this->log("Execute: $cmdName...");
		f_util_System::execChangeCommand($cmdName, $params);
	}
}