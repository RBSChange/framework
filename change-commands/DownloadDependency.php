<?php
class commands_DownloadDependency extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return 'framework|modules [name]|libs [name]|pearlibs [name] [--version=VERSION --url="URL"]';
	}
	
	function getAlias()
	{
		return "dd";
	}
	
	/**
	 * @return String[]
	 */
	function getOptions()
	{
		return array('verbose');
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Download project dependency";
	}
	
	/**
	 * @param array $params
	 * @param array $options
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) === 1)
		{
			if ($params[0] === 'framework')
			{
				return true;
			}
		}
		elseif (count($params) === 2)
		{
			if (in_array($params[0], array('modules', 'libs', 'pearlibs', 'themes')))
			{
				if (isset($options['url']) && !isset($options['version']))
				{
					return false;
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$bootstrap = $this->getParent()->getBootStrap();
		$releaseName = $bootstrap->getCurrentReleaseName();
		$this->message("== Download dependency for release: ".$releaseName." ==");
		
		$debType = $params[0];
		$debName = (count($params) === 1) ? $debType : $params[1];
		$version = isset($options['version']) ? $options['version'] : null;
		$url = isset($options['url']) ? $options['url'] : null;
		
		try
		{
			$path = $bootstrap->downloadDependency($debType, $debName, $version, $url);
			return $this->quitOk('Download dependency successfully in: ' . $path);
		} 
		catch (Exception $e) 
		{
			return $this->quitError('Download dependency error: ' . $e->getMessage());
		}
	}
}