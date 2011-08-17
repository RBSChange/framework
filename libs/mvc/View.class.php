<?php
abstract class change_View
{
	
	const ALERT = 'Alert';
	const ERROR = 'Error';
	const INPUT = 'Input';
	const NONE = null;
	const SUCCESS = 'Success';

	const STATUS_OK    = 'OK';
	const STATUS_ERROR = 'ERROR';
	
	/**
	 * @var change_Context
	 */
	private $context;
	
	/**
	 * @var array
	 */
	private $attributes = array();
	
	/**
	 * @var string
	 */
	private $mimeContentType = null;

	
	/**
	 * @return TemplateObject
	 */
	private $engine = null;

	/**
	 * @var string
	 */	
	private $forceModuleName = null;

	/**
	 * @var string
	 */
	private $nullref = null;
	
	/**
	 * @return change_Context
	 */
	public final function getContext()
	{
		return $this->context;
	}
		
	public function initialize($context)
	{
		$this->context = $context;
		$module = $context->getModuleName();
		return true;
	}
	
	/**
	 * Please do not override this method, but _execute() instead!
	 * @return string View name.
	 */
	public function execute()
	{
		$context = $this->getContext();
		$this->sendHttpHeaders();
		return $this->_execute($context, $context->getRequest());
	}

	/**
	 * PLEASE USE THIS METHOD for the action body instead of execute() (without
	 * the underscore): it is called by execute() and directly receives f_Context
	 * and Request objects.
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	abstract protected function _execute($context, $request);

	
	/**
	 * @param string $mimeContentType
	 * @throws IllegalArgumentException
	 */
	public function setMimeContentType($mimeContentType)
	{
		if(empty($mimeContentType))
		{
			throw new IllegalArgumentException('mimeContentType', 'string');
		}
		RequestContext::getInstance()->setMimeContentType($mimeContentType);
		$this->mimeContentType = $mimeContentType;
	}

	/**
	 * @param string $templateName
	 * @param string $mimeType
	 */
	public function setTemplateName($templateName, $mimeType = 'html')
	{
		if (is_null($this->forceModuleName))
		{
			$moduleName = $this->getContext()->getRequest()->getParameter('module');
		}
		else
		{
			$moduleName = $this->forceModuleName;
		}
		$templateLoader = TemplateLoader::getInstance('template')->setMimeContentType($mimeType);
		$templateLoader->setDirectory('templates');
		try
		{
			$this->engine = $templateLoader->setPackageName('modules_' . $moduleName)->load($templateName);
		}
		catch (TemplateNotFoundException $e)
		{
			$this->engine = $templateLoader->setPackageName('modules_' . 'generic')->load($templateName);
		}
	}

	/**
	 * @return TemplateObject
	 */
	public function getEngine()
	{
		return $this->engine;
	}

	public function clearAttributes()
	{
		$this->attributes = array();
	}

	/**
	 * @param string $name
	 */
	public function hasAttribute($name)
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getAttribute($name)
	{
		if ($this->hasAttribute($name))
		{
			return $this->attributes[$name];
		}
		return null;
	}

	/**
	 * @param string $name
	 */
	public function removeAttribute($name)
	{
		if ($this->hasAttribute($name))
		{
			unset($this->attributes[$name]);
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}

	/**
	 * @param array $attributes
	 */
	public function setAttributes($attributes)
	{
		foreach ($attributes as $name => $value)
		{
			$this->setAttribute($name, $value);
		}
	}

	/**
	 * @return string[]
	 */
	public function getAttributeNames()
	{
		return array_keys($this->attributes);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setAttributeByRef($name, &$value)
	{
		$this->attributes[$name] = $value;
	}

	/**
	 * @param array $attributes
	 */
	public function setAttributesByRef(&$attributes)
	{
		foreach ($attributes as $name => $value)
		{
			$this->setAttributeByRef($name, $value);
		}
	}

	public function render ()
	{
		$request = $this->getContext()->getRequest();
		$this->setAttribute('module', $request->getParameter('module'));
		$this->setAttribute('action', $request->getParameter('action'));
		$this->getEngine()->importAttributes($this->attributes);
		echo $this->getEngine()->execute();
		return null;
	}

	/**
	 * @return string
	 */
	protected final function getModuleName()
	{
		$request = $this->getContext()->getRequest();
		$moduleName = $request->getParameter('wemod');
		if (empty($moduleName))
		{
			$moduleName = $request->getParameter('module');
		}
		return $moduleName;
	}

	protected function sendHttpHeaders()
	{
		change_Controller::setNoCache();
	}

	/**
	 * Sets the action status (STATUS_OK ou STATUS_ERROR).
	 *
	 * @param string $status The status to set to the response.
	 */
	protected final function setStatus($status)
	{
		$this->setAttribute('status', $status);
	}

	/**
	 * Returns the current lang.
	 *
	 * @return string
	 */
	public function getLang()
	{
		return RequestContext::getInstance()->getLang();
	}

	/**
	 * @return f_persistentdocument_DocumentService
	 */
	public final function getDocumentService()
	{
		return f_persistentdocument_DocumentService::getInstance();
	}

	/**
	 * @param string $moduleName
	 */
	protected final function forceModuleName($moduleName)
	{
		$this->forceModuleName = $moduleName;
	}
	
	/**
	 * @param string $name
	 * @param array $arguments
	 * @deprecated
	 */
	public function __call($name, $arguments)
	{
		switch ($name)
		{
			case 'getJsService': 
				Framework::error('Call to deleted ' . get_class($this) . '->getJsService method');
				return website_JsService::getInstance();
				
			case 'getStyleService': 
				Framework::error('Call to deleted ' . get_class($this) . '->getStyleService method');
				return website_StyleService::getInstance();
			
			default: 
				throw new Exception('No method ' . get_class($this) . '->' . $name);
		}
	}
}