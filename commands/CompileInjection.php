<?php
class commands_CompileInjection extends c_ChangescriptCommand
{
	/**
	 * @return boolean
	 */
	public function isHidden()
	{
		return true;
	}
	
	/**
	 * @return string
	 */
	public function getUsage()
	{
		return '@deprecated use compile-config';
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->quitWarn("== Use compile-config ==");
	}
}