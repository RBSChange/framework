<?php
/**
<{if $module == 'framework'}>
 * @package framework.command
 */
class commands_<{$name}> extends c_ChangescriptCommand
<{else}>
 * @package modules.<{$module}>
 */
class commands_<{$module}>_<{$name}> extends c_ChangescriptCommand
<{/if}>
{
	/**
	 * @return String
	 */
	public function getUsage()
	{
		return "<describe usage here>";
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	public function _execute($params, $options)
	{
		$this->message("== <{$commandTitle}> ==");

		// Put your code here!

		$this->quitOk("Command successfully executed");
	}

	/**
	 * @return String
	 */
//	public function getDescription()
//	{
//		return "<describe your command here>";
//	}
	
	/**
	 * @return String[]
	 */
//	public function getOptions()
//	{
//		return array('option1');
//	}
	
	/**
	 * This method is used to handle auto-completion for this command.
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
//	public function getParameters($completeParamCount, $params, $options, $current)
//	{
//		$components = array();
//		
//		// Generate options in $components.
//		
//		return $components;
//	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
//	protected function validateArgs($params, $options)
//	{
//	}
}