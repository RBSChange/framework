<?php
/**
 * @package framework.libs.agavi.view
 */
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
			$moduleName = $this->getContext()->getRequest()->getParameter(AG_MODULE_ACCESSOR);
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
	public function &getEngine()
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

	public function &getAttribute($name)
	{
		if ($this->hasAttribute($name))
		{
			return $this->attributes[$name];
		}
		return $this->nullref;
	}

	public function &removeAttribute($name)
	{
		if ($this->hasAttribute($name))
		{
			unset($this->attributes[$name]);
		}
		return $this->nullref;
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
		$this->setAttribute($name, $value);
	}

	public function setAttributesByRef(&$attributes)
	{
		foreach ($attributes as $name => $value)
		{
			$this->setAttributeByRef($name, $value);
		}
	}

	/**
     * Render the presentation.
     *
     * When the controller render mode is View::RENDER_CLIENT, this method will
     * render the presentation directly to the client and null will be returned.
     *
     * @return string A string representing the rendered presentation, if
     *                the controller render mode is View::RENDER_VAR, otherwise
     *                null.
     * TODO intbonjf 2007-01-26: clean this!
     */
	public function &render ()
	{
		$request = $this->getContext()->getRequest();

		// intbonjf - 2006-03-29 :
		// module and action are now automatically passed to the template.
		$this->setAttribute(AG_MODULE_ACCESSOR, $request->getParameter(AG_MODULE_ACCESSOR));
		$this->setAttribute(AG_ACTION_ACCESSOR, $request->getParameter(AG_ACTION_ACCESSOR));

		$this->getEngine()->importAttributes($this->attributes);

		if ($request->getParameter('signedView') == 1)
		{
			$this->getContext()->getRequest()->setParameter('signedView', 0);
		}

		echo $this->getEngine()->execute();

		return $this->nullref;
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
			$moduleName = $request->getParameter(AG_MODULE_ACCESSOR);
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