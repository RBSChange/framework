<?php
abstract class commands_AbstractChangedevCommand extends commands_AbstractChangeCommand
{
	protected function changecmd($cmdName, $params)
	{
		$this->loadFramework();
		c_System::exec("php ".FRAMEWORK_HOME."/bin/change.php $cmdName ".join(" ", $params), $cmdName);
	}
}