<?php
/**
<{if $module == 'framework'}>
 * commands_<{$name}>
 * @package framework.command<{$type}>
 */
class commands_<{$name}> extends commands_AbstractChange<{$type}>Command
<{else}>
 * commands_<{$module}>_<{$name}>
 * @package modules.<{$module}>.command<{$type}>
 */
class commands_<{$module}>_<{$name}> extends commands_AbstractChange<{$type}>Command
<{/if}>
{
	/**
	 * @return String
	 * @example "<moduleName> <name>"
	 */
	function getUsage()
	{
		return "<describe usage here>";
	}

	/**
	 * @return String
	 * @example "initialize a document"
	 */
	function getDescription()
	{
		return "<describe your command here>";
	}
	
	/**
	 * This method is used to handle auto-completion for this command.
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
//	function getParameters($completeParamCount, $params, $options, $current)
//	{
//		$components = array();
//		
//		// Generate options in $components.		
//		
//		return array_diff($components, $params);
//	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
//	protected function validateArgs($params, $options)
//	{
//	}

	/**
	 * @return String[]
	 */
//	function getOptions()
//	{
//	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== <{$name}> ==");

		// Put your code here!

		$this->quitOk("Command successfully executed");
	}
}