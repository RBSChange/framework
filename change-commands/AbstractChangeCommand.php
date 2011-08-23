<?php
abstract class commands_AbstractChangeCommand extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	protected function getAuthor()
	{
		$user = getenv("USER");
		if (empty($user))
		{
			return null;
		}
		return $user;
	}

	/**
	 * @return String
	 */
	protected function getUser()
	{
		return $this->getAuthor();
	}

	/**
	 * @return String
	 */
	protected function getApacheGroup()
	{
		$cdeps = $this->getComputedDeps();
		return $cdeps["WWW_GROUP"];
	}

	/**
	 * @return array
	 */
	protected function getComputedDeps()
	{
		return $this->getParent()->getBootStrap()->getComputedDependencies();
	}

	/**
	 * @return void
	 */
	protected function loadFramework()
	{
	    $this->getParent()->loadFramework();
	}

	/**
	 * @return boolean true if framework link was created or updated
	 */
	protected function createFrameworkLink()
	{
		//TODO framework already exist
		return true;
	}

	/**
	 * @return String
	 */
	protected function getProfile()
	{
		if (file_exists("profile"))
		{
			return trim(f_util_FileUtils::read("profile"));
		}
		// Define profile
		$profile = trim($this->getAuthor());
		$this->warnMessage("No profile file, using user name as profile (".$profile.")");
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