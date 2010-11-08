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
	 * @deprecated use f_util_System::execChangeCommand
	 * @param String $task
	 * @param String[] $args
	 * @return String[] the output of the command
	 */
	function executeTask($task, $args = array())
	{
		return f_util_System::execChangeCommand($task, $args)
	}

	/**
	 * @return String
	 */
	function getProfile()
	{
		return trim(file_get_contents(WEBEDIT_HOME."/profile"));
	}
}
