<?php
abstract class f_mvc_Action
{
	/**
	 * @var block_BlockConfiguration
	 */
	private $configuration;

	/**
	 * @var String
	 */
	private $lang;

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{

	}

	protected function getConfigurationClassname()
	{
		return 'block_BlockConfiguration';
	}

	/**
	 * @return block_BlockConfiguration
	 */
	public function getConfiguration()
	{
		if ($this->configuration === null)
		{
			$className = $this->getConfigurationClassname();
			$this->configuration = new $className();
		}
		return $this->configuration;
	}

	/**
	 * @return array
	 */
	public final function getConfigurationParameters()
	{
		return $this->getConfiguration()->getConfigurationParameters();
	}


	/**
	 * @param string $name
	 * @param string $value
	 */
	public final function setConfigurationParameter($name, $value)
	{
		$this->getConfiguration()->setConfigurationParameter($name, $value);
	}

	/**
	 * @param String $parameterName
	 * @param Mixed $defaultValue
	 * @return Mixed
	 */
	public final function getConfigurationParameter($parameterName, $defaultValue = null)
	{
		return $this->getConfiguration()->getConfigurationParameter($parameterName, $defaultValue);
	}

	/**
	 * @param String $parameterName
	 * @return Boolean
	 */
	public final function hasConfigurationParameter($parameterName)
	{
		return $this->getConfiguration()->hasConfigurationParameter($parameterName);
	}

	/**
	 * @param String $parameterName
	 * @return Boolean
	 */
	public final function hasNonEmptyConfigurationParameter($parameterName)
	{
		return $this->getConfiguration()->hasNonEmptyConfigurationParameter($parameterName);
	}

	/**
	 * @return String
	 */
	public final function getLang()
	{
		return $this->lang;
	}

	/**
	 * @param String $lang
	 */
	public final function setLang($lang)
	{
		$this->lang = $lang;
	}
	
	protected $cacheEnabled;

	/**
	 * @return Boolean
	 */
	public function isCacheEnabled()
	{
		if ($this->cacheEnabled === null)
		{
			$this->cacheEnabled = $this->getConfiguration()->isCacheEnabled() 
				&& $this->getConfiguration()->getCusecache();
		}
		return $this->cacheEnabled;
	}
	
	/**
	 * @return int
	 */
	public function getCacheTtl()
	{
		return $this->getConfiguration()->getCacheTtl();
	}

	/**
	 * @return array
	 */
	public function getCacheDependencies()
	{
		return null;
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	public function getCacheKeyParameters($request)
	{
		return null;
	}
	
	/**
	 * @return array
	 */
	public function getConfiguredCacheKeys()
	{
		return null;
	}
	
	/**
	 * @return array
	 */
	public function getConfiguredCacheDeps()
	{
		return null;
	}

	/**
	 * @param f_mvc_Request $request
	 * @return array
	 */
	abstract function getInputValidationRules($request, $bean);

	/**
	 * @param f_mvc_Request $request
	 * @return boolean
	 */
	abstract function validateInput($request, $bean);

	/**
	 * @return void
	 */
	abstract function onValidateInputFailed($request);

	/**
	 * @return String
	 */
	abstract function getModuleName();

	/**
	 * @return String
	 */
	abstract function getName();

	/**
	 * @return String
	 */
	abstract function getInputViewName();
	
	/**
	 * @return f_mvc_Context
	 */
	abstract public function getContext();

	/**
	 * @param String $moduleName
	 * @param String $actionName
	 */
	abstract public function forward($moduleName, $actionName);

	/**
	 * @param String $moduleName
	 * @param String $actionName
	 * @param Array $moduleParams
	 * @param Array $absParams
	 */
	abstract public function redirect($moduleName, $actionName, $moduleParams = null, $absParams = null);

	/**
	 * @param $url
	 * @return void
	 */
	public function redirectToUrl($url)
	{
		f_web_http_Header::setStatus(302);
		header("Location: $url");
	}

	/**
	 * @param String $parameterName
	 */
	abstract protected function findParameterValue($parameterName);
}