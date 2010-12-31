<?php
abstract class View
{
	
	const ALERT = 'Alert';
	const ERROR = 'Error';
	const INPUT = 'Input';
	const NONE = null;
	const SUCCESS = 'Success';
	
	const RENDER_CLIENT = 2;
	const RENDER_NONE = 1;
	const RENDER_VAR = 4;
	
	private $context;
	
	abstract function clearAttributes();
	
	abstract function execute();
	
	abstract function getAttribute($name);
	
	abstract function getAttributeNames();
	
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
	
	abstract function removeAttribute($name);
	
	abstract function setAttribute($name, $value);
	
	abstract function setAttributeByRef($name, &$value);
	
	abstract function setAttributes($values);
	
	abstract function setAttributesByRef(&$values);
	
	abstract function getEngine();
	
	abstract function render();
	
	
	// Deprecated
	private $template;
	
	public function setTemplate($template)
	{
		$this->template = $template;
	}
	
	public function getTemplate()
	{
		return $this->template;
	}
}

abstract class f_view_BaseView extends View
{
	const STATUS_OK    = 'OK';
	const STATUS_ERROR = 'ERROR';

	private $attributes = array();
	
	private $mimeContentType = null;
	
	/**
	 * @return TemplateObject
	 */
	private $engine = null;

	private $forceModuleName = null;

	private $nullref = null;

	/**
	 * PLEASE USE THIS METHOD for the action body instead of execute() (without
	 * the underscore): it is called by execute() and directly receives Context
	 * and Request objects.
	 * @param Context $context
	 * @param Request $request
	 */
	abstract protected function _execute($context, $request);

	public function setMimeContentType($mimeContentType)
	{
		if(empty($mimeContentType))
		{
			throw new IllegalArgumentException('mimeContentType', 'string');
		}
		RequestContext::getInstance()->setMimeContentType($mimeContentType);
		$this->mimeContentType = $mimeContentType;
	}

	public function setTemplateName($templateName, $mimeType = K::HTML)
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
			$this->engine = $templateLoader->setPackageName('modules_' . K::GENERIC_MODULE_NAME)->load($templateName);
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

	public function hasAttribute($name)
	{
		return array_key_exists($name, $this->attributes);
	}

	public function getAttribute($name)
	{
		if ($this->hasAttribute($name))
		{
			return $this->attributes[$name];
		}
		return null;
	}

	public function removeAttribute($name)
	{
		if ($this->hasAttribute($name))
		{
			unset($this->attributes[$name]);
		}
		return null;
	}

	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}

	public function setAttributes($attributes)
	{
		foreach ($attributes as $name => $value)
		{
			$this->setAttribute($name, $value);
		}
	}

	public function getAttributeNames()
	{
		return array_keys($this->attributes);
	}

	public function setAttributeByRef($name, &$value)
	{
		$this->attributes[$name] = $value;
	}

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
	 * Please do not override this method, but _execute() instead!
	 *
	 * @return string View name.
	 */
	public function execute()
	{
		$context = $this->getContext();
		$this->sendHttpHeaders();
		return $this->_execute($context, $context->getRequest());
	}

	/**
	 * @param Request $request
	 * @return String
	 */
	protected final function getModuleName()
	{
		$request = $this->getContext()->getRequest();
		$moduleName = $request->getParameter(K::WEBEDIT_MODULE_ACCESSOR);
		if (empty($moduleName))
		{
			$moduleName = $request->getParameter('module');
		}
		return $moduleName;
	}

	protected function sendHttpHeaders()
	{
		controller_ChangeController::setNoCache();
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
	 * Returns the StyleService instance to use within this view.
	 *
	 * @return StyleService
	 */
	public final function getStyleService()
	{
		return StyleService::getInstance();
	}

	/**
	 * Returns the JsService instance to use within this view.
	 *
	 * @return JsService
	 */
	public final function getJsService()
	{
		return JsService::getInstance();
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
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) Use setTemplateName
	 */
	public function setTemplate($template)
	{
		parent::setTemplate($template);
	}
}
