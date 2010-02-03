<?php
abstract class commands_AbstractChangeCommand extends c_ChangescriptCommand
{
	protected function getAuthor()
	{
		return getenv("USER");
	}

	protected function getUser()
	{
		return $this->getAuthor();
	}

	protected function getApacheGroup()
	{
		$cdeps = $this->getComputedDeps();
		return $cdeps["WWW_GROUP"];
	}

	protected function getComputedDeps()
	{
		return $this->getEnvVar("computedDeps");
	}

	protected function loadFramework()
	{
		if (!file_exists("framework"))
		{
			$this->createFrameworkLink();
			$this->getParent()->executeCommand("compile-config");
		}
		
		$bootStrapAutoload = array ($this->getParent()->getBootStrap(), 'autoload');
		// We unregister cli autoload before to load Framework because of possible AOP
		// interception that only the Framework autoload is aware of
		spl_autoload_unregister($bootStrapAutoload);
		require_once(realpath(WEBEDIT_HOME."/framework/Framework.php"));
		spl_autoload_register($bootStrapAutoload);
		
		if (!class_exists("Controller", false))
		{
			require_once(WEBEDIT_HOME.'/libs/agavi/controller/Controller.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/view/View.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/exception/AgaviException.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/exception/ControllerException.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/controller/WebController.class.php');
			require_once(FRAMEWORK_HOME.'/libs/agavi/controller/HttpController.class.php');
			require_once(FRAMEWORK_HOME.'/libs/agavi/controller/ChangeController.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/core/Context.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/action/ActionStack.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/request/Request.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/request/WebRequest.class.php');
			require_once(FRAMEWORK_HOME.'/libs/agavi/request/ChangeRequest.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/storage/Storage.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/storage/SessionStorage.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/user/User.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/user/SecurityUser.class.php');
			require_once(FRAMEWORK_HOME.'/libs/agavi/user/FrameworkSecurityUser.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/filter/Filter.class.php');
			require_once(WEBEDIT_HOME.'/libs/agavi/filter/SecurityFilter.class.php');
			require_once(FRAMEWORK_HOME.'/libs/agavi/filter/FrameworkSecurityFilter.class.php');
		}

		try
		{
			$controller = Controller::getInstance();
		}
		catch (ControllerException $e)
		{
			$controller = Controller::newInstance("controller_ChangeController");
		}
	}

	/**
	 * @return boolean true if framework link was created or updated
	 */
	protected function createFrameworkLink()
	{
		$computedDeps = $this->getComputedDeps();

		// Create framework link.
		$frameworkInfo = $computedDeps["change-lib"]["framework"];
		$this->message("Symlink framework-".$frameworkInfo["version"]);
		return f_util_FileUtils::symlink($frameworkInfo["path"], "framework", f_util_FileUtils::OVERRIDE);
	}

	protected function getProfile()
	{
		if (file_exists("profile"))
		{
			return trim(f_util_FileUtils::read("profile"));
		}
		// Define profile
		$profile = trim($this->getAuthor());
		echo "No profile file, using user name as profile (".$profile.")\n";
		f_util_FileUtils::write("profile", $profile);
		return $profile;
	}

	/**
	 * @return util_Properties
	 */
	protected function getProperties()
	{
		return $this->getParent()->getBootStrap()->getProperties("change");
	}
}