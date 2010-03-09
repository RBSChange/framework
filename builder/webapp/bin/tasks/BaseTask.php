<?php
/**
 * Standalone class (ie.: with no dependency), base for tasks,
 * designed for a PHP cli call
 */
abstract class f_tasks_BaseTask
{
	/**
	 * @var String
	 */
	private $name;

	/**
	 * @var Boolean
	 */
	private $runIfSiteDisabled;

	/**
	 * @param String $name
	 * @param Boolean $runIfSiteDisabled
	 */
	function __construct($name, $runIfSiteDisabled = false)
	{
		$this->name = $name;
		$this->runIfSiteDisabled = $runIfSiteDisabled;
	}

	/**
	 * Launch the task
	 */
	function start()
	{
		if (!defined('WEBEDIT_HOME'))
		{
			// Install path is webapp/bin/tasks/<myTaskPath>.php
			if (!isset($_SERVER["PWD"]) || !isset($_SERVER["SCRIPT_FILENAME"]))
			{
				throw new Exception("Could not discover WEBEDIT_HOME: PWD = ".var_export($_SERVER["PWD"]).", SCRIPT_FILENAME = ".var_export($_SERVER["SCRIPT_FILENAME"]));
			}
			if ($_SERVER["SCRIPT_FILENAME"][0] == '/')
			{
				$thisPath = $_SERVER["SCRIPT_FILENAME"];
			}
			else
			{
				$thisPath = $_SERVER["PWD"].DIRECTORY_SEPARATOR.$_SERVER["SCRIPT_FILENAME"];
			}
			define('WEBEDIT_HOME', realpath(dirname($thisPath) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR  . '..'));
		}

		if (!$this->runIfSiteDisabled && $this->isSiteDisabled())
		{
			echo "WARNING: task ".$this->name." skipped: ".time()." (site disabled)\n";
			return;
		}

		chdir(WEBEDIT_HOME);

		$startFlag = $this->getStartFlagFile();
		$endFlag = $this->getEndFlagFile();
		$runningFlag = $this->getRunningFlagFile();

		if (file_exists($startFlag) && (!file_exists($endFlag) || $this->getMTime($startFlag) > $this->getMTime($endFlag)))
		{
			echo "WARNING: last ".$this->name." process is in error or still running\n";
			if (file_exists($runningFlag))
			{
				echo "Check ".file_get_contents($runningFlag)." pid\n";
			}
			return;
		}
		$this->touch($startFlag);
		if (!file_put_contents($runningFlag, getmypid()))
		{
			throw new Exception("Could not write $runningFlag");
		}
		$this->execute($this->getPreviousRunTime());
		if (!@unlink($runningFlag))
		{
			throw new Exception("Could not unlink $runningFlag");
		}
		$this->touch($endFlag);
	}

	/**
	 * @param Integer|null $previousRunTime UNIX timestamp of last launch time of task
	 */
	abstract protected function execute($previousRunTime);

	protected function loadFramework()
	{
		require_once WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'Framework.php';
		$rq = RequestContext::getInstance();
		$rq->setLang($rq->getDefaultLang());
	}

	/**
	 * @return Boolean
	 */
	protected function isSiteDisabled()
	{
		return file_exists(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'webapp' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'site_is_disabled');
	}

	// private content
	private function touch($file)
	{
		if (!file_put_contents($file, time()))
		{
			throw new Exception("Could not touch $file");
		}
	}
	
	private function getMTime($file)
	{
		$contents = file_get_contents($file);
		if ($contents === false)
		{
			throw new Exception("Could not read $file contents");
		}
		$contents = trim($contents);
		if (strlen($contents) > 0)
		{
			$mtime = intval($contents);
			if ($mtime == 0)
			{
				throw new Exception("Could not parse $file contents ($contents)");
			}
			return $mtime;
		}
		else
		{
			return filemtime($file);
		}
	}

	/**
	 * @return Integer|null UNIX timestamp
	 */
	private function getPreviousRunTime()
	{
		$startFlag = $this->getStartFlagFile();
		if (!file_exists($startFlag))
		{
			return null;
		}
		return filemtime($startFlag);
	}

	private function getTaskLogDir()
	{
		return WEBEDIT_HOME."/webapp/bin/tasks/flags";
	}

	private function getStartFlagFile()
	{
		return $this->getTaskLogDir()."/".$this->name.".start";
	}

	private function getEndFlagFile()
	{
		return $this->getTaskLogDir()."/".$this->name.".end";
	}

	private function getRunningFlagFile()
	{
		return $this->getTaskLogDir()."/".$this->name.".pid";
	}
}
