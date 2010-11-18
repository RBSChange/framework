<?php
class ChangeProject
{
	/**
	 * @var ChangeProject
	 */
	private static $instance;
	protected function __construct()
	{
		// empty
	}

	/**
	 * @return ChangeProject
	 */
	static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new ChangeProject();
		}
		return self::$instance;
	}

	function clearWebappCache()
	{
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildWebCachePath('binding'));
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildWebCachePath('js'));
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildWebCachePath('css'));
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildWebCachePath('htmlpreview'));

		$this->clearTemplateCache();
	}
	
	function compileTags()
	{
		TagService::getInstance()->regenerateTags();
	}

	function clearTemplateCache()
	{
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildCachePath("template"));
	}

	/**
	 * @return String
	 */
	function getProfile()
	{
		return trim(file_get_contents(WEBEDIT_HOME."/profile"));
	}

	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use f_util_System::execChangeCommand
	 */
	function executeTask($task, $args = array())
	{
		return f_util_System::execChangeCommand($task, $args)
	}
}