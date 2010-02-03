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
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildWebappPath("www", "cache", "binding"));
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildWebappPath("www", "cache", "js"));
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildWebappPath("www", "cache", "css"));
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildWebappPath("www", "cache", "htmlpreview"));

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
	 * // TODO : make private when no more necessary
	 * @param String $task
	 * @param String[] $args
	 * @return String[] the output of the command
	 */
	function executeTask($task, $args = array())
	{
		$cmd = "change.php $task ".join(" ", $args);
		echo "$cmd...";
		$output = array();
		exec($cmd, $output, $retVal);
		if ("0" != $retVal)
		{
			throw new Exception("Could not execute $cmd (exit code $retVal):\n".join("", $output));
		}
		echo " done\n";
		return $output;
	}

	/**
	 * @return String
	 */
	function getProfile()
	{
		return trim(file_get_contents(WEBEDIT_HOME."/profile"));
	}
}
