<?php
/**
 * @package framework.libs.agavi.action
 */
abstract class f_action_BaseAction extends Action
{
	const EXCEPTION_KEY = "BaseAction.Exception";
		
	/**
	 * Original Module name
	 * @var String
	 */
	private $m_moduleName;

	/**
	 * Action Name
	 * @var String
	 */
	private $m_actionName;

	/**
	 * The DocumentService instance to use within this action.
	 *
	 * @var f_persistentdocument_DocumentService
	 */
	protected $ds = null;

	/**
	 * Initialize this action.
	 *
	 * @param Context The current application context.
	 * @return bool true, if initialization completes successfully, otherwise false.
	 */
	public function initialize ($context)
	{
		$result = parent::initialize($context);

		//Calculate Module name
		$request = $context->getRequest();
		$this->m_moduleName =  f_util_ArrayUtils::lastElement($request->getAttribute(K::EFFECTIVE_MODULE_NAME));

		$classParts = explode('_', get_class($this));
		//Caluclate Action name xxxxx_[name]Action
		$className = end($classParts);
		$this->m_actionName = substr($className, 0 , strlen($className) - 6);

		if (Framework::isDebugEnabled())
		{
			Framework::debug(get_class($this) . '->initialize('.$this->m_moduleName .', ' . $this->m_actionName.')');
		}
		return $result;
	}
	
	/**
	 * Please use this method for the action body instead of execute() (without
	 * the underscore): it is called by execute and directly receives Context
	 * and Request objects.
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
		throw new Exception('f_action_BaseAction_execute($context, $request) is not implemented');
	}


	/**
	 * Sets the HTTP "Content-type" header value for the response of this action.
	 *
	 * @param string $contentType
	 */
	protected final function setContentType($contentType)
	{
		header('Content-Type: '.$contentType);
	}
	
	protected final function setContentLength($contentLength)
	{
		header('Content-Length: '.$contentLength);
	}
	
	protected final function outputBinary($content, $contentType)
	{
		$this->setContentType($contentType);
		$this->setContentLength(strlen($content));
		echo $content;
		return View::NONE;
	}

	/**
	 * Returns the DocumentService instance to use within the action.
	 *
	 * @return f_persistentdocument_DocumentService
	 */
	protected final function getDocumentService()
	{
		if (is_null($this->ds))
		{
			$this->ds = f_persistentdocument_DocumentService::getInstance();
			$this->ds->setPersistentProvider($this->getPersistentProvider());
			$this->ds->setTransactionManager($this->getTransactionManager());
		}
		return $this->ds;
	}


	/**
	 * Return the document service for the given doc Id.
	 *
	 * @param integer $docId
	 * @return f_persistentdocument_DocumentService
	 */
	protected final function getDocumentServiceByDocId($docId)
	{
		$ds = f_persistentdocument_DocumentService::getInstance();
		$document = $ds->getDocumentInstance($docId);
		return $document->getDocumentService();
	}


	/**
	 * Returns the TransactionManager instance to use within the action.
	 *
	 * @return f_persistentdocument_TransactionManager
	 */
	protected final function getTransactionManager()
	{
		return f_persistentdocument_TransactionManager::getInstance();
	}


	/**
	 * Returns the PersistentProvider instance to use within the action.
	 *
	 * @return f_persistentdocument_PersistentProvider
	 */
	protected final function getPersistentProvider()
	{
		return f_persistentdocument_PersistentProvider::getInstance();
	}


	/**
	 * Returns the name of the module that was originally requested.
	 *
	 * This is because actions that do not exist in modules are forwarded
	 * to the 'generic' module, with the K::WEBEDIT_MODULE_ACCESSOR parameter
	 * telling 'generic' the original module name.
	 * @return String
	 */
	protected final function getModuleName()
	{
		return $this->m_moduleName;
	}

	/**
	 * Returns the name of the action.
	 * @return String
	 */
	protected function getActionName()
	{
		return $this->m_actionName;
	}

	/**
	 * @return array<string> generic/BackofficeSuccess
	 */
	protected final static function getSuccessView()
	{
		return array(K::GENERIC_MODULE_NAME , 'BackofficeSuccess');
	}


	/**
	 * @return array<string> generic/BackofficeError
	 */
	protected final static function getErrorView()
	{
		return array(K::GENERIC_MODULE_NAME , 'BackofficeError');
	}
	
	private $logged = false;
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	protected function logAction($document, $info = array())
	{
		$this->logged = true;
		$moduleName = $this->getModuleName();
		$actionName = strtolower($this->getActionName());
		if ($document instanceof f_persistentdocument_PersistentDocument)
		{
			$actionName .= '.' . strtolower($document->getPersistentModel()->getDocumentName());
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__."($moduleName, $actionName)");
		}		
		UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry($actionName, $document, $info, $moduleName);
	}
	
	/**
	 * @param Request $request
	 * @param Exception $e
	 * @param boolean $popupAlert
	 */
	protected final function setException($request, $e, $popupAlert = false)
	{
		$xmlrenderer = new exception_XmlRenderer();
		if ($e instanceof BaseException ) 
		{
			$request->setAttribute('message', htmlspecialchars($e->getLocaleMessage()));
		}		
		else
		{	
			$request->setAttribute('message', htmlspecialchars($e->getMessage()));
		}
		if ($popupAlert)
		{
			$request->setAttribute('alert', 'true');
		}
		$request->setAttribute('contents', $xmlrenderer->getStackTraceContents($e));
	}


	/**
	 * Returns an array of the documents IDs received by this action.
	 *
	 * All the IDs contained in the resulting array are REAL integer values, not
	 * strings.
	 *
	 * @param Request $request
	 * @return array<integer>
	 */
	protected function getDocumentIdArrayFromRequest($request)
	{
		$docIds = $request->getParameter(K::COMPONENT_ID_ACCESSOR, array());
		if (is_string($docIds) && intval($docIds) == $docIds)
		{
			$docIds = array(intval($docIds));
		}		
		elseif (is_int($docIds)) 
		{
			$docIds = array($docIds);
		}
		else if (is_array($docIds))
		{
			foreach ($docIds as $index => $docId)
			{
				if (strval(intval($docId)) === $docId)
				{
					$docIds[$index] = intval($docId);
				}
				else if (!is_int($docId))
				{
					unset($docIds[$index]);
				}
			}
		}
		return $docIds;
	}


	/**
	 * Returns the document ID received by this action.
	 *
	 * If the request holds more than one document ID (ie. an array), only
	 * the first one is returned. Returned value is a REAL integer value, not
	 * a string.
	 *
	 * @param Request $request
	 * @return integer
	 */
	protected final function getDocumentIdFromRequest($request)
	{
		$docIds = $this->getDocumentIdArrayFromRequest($request);
		if (count($docIds) != 0)
		{
			return $docIds[0];
		}
		return null;
	}


	/**
	 * Returns the document instance received by this action.
	 *
	 * If the request holds more than one document ID (ie. an array), only
	 * the first one is returned. Returned value is a PersistentDocument instance.
	 *
	 * @param Request $request
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected final function getDocumentInstanceFromRequest($request)
	{
		return $this->getDocumentService()->getDocumentInstance($this->getDocumentIdFromRequest($request));
	}


	/**
	 * Returns an array of document instances from the IDs received in the request.
	 *
	 * @param Request $request
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	protected final function getDocumentInstanceArrayFromRequest($request)
	{
		$docs = array();
		$docIds = $this->getDocumentIdArrayFromRequest($request);
		foreach ($docIds as $docId)
		{
			$docs[] = DocumentHelper::getDocumentInstance($docId);
		}
		return $docs;
	}

	/**
	 * Returns the current lang.
	 *
	 * @return string
	 */
	public final function getLang()
	{
		return RequestContext::getInstance()->getLang();
	}

	/**
	 * @return string View name.
	 */
	public final function execute()
	{
		try
		{
			if ($this->checkPermissions())
			{
				$context = $this->getContext();
				$result = $this->_execute($context, $context->getRequest());
				/*
				if (!$this->logged && $this->isSecure())
				{
					$id = $this->getDocumentIdFromRequest($context->getRequest());
					if (intval($id) > 0)
					{
						try 
						{
							$this->logAction(DocumentHelper::getDocumentInstance($id));
						}
						catch (Exception $e)
						{
							Framework::exception($e);
						}
					}
				}
				*/
				return $result;
			}
			else
			{
				return View::NONE;
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			$request = $this->getContext()->getRequest();
			if (RequestContext::getInstance()->getMode() == RequestContext::BACKOFFICE_MODE)
			{
				return $this->onBackOfficeException($request, $e, true);
			}
			else
			{
				// set an attribute so the page can display the exception
				$request->setAttribute(self::EXCEPTION_KEY, $e);
				$this->getContext()->getController()->forward('website', 'Error500');
			}
		}
	}

	/**
	 * Returns the HTTP methods available for this action.
	 *
	 * @return string
	 */
	public function getRequestMethods()
	{
		return Request::POST | Request::GET;
	}


	/**
	 * All generic actions are secured: they can't be executed from a
	 * non-authenticated user.
	 *
	 * Please override this only if you know exactly what you are doing.
	 *
	 * @return boolean Always true.
	 */
	public function isSecure()
	{
		return true;
	}

	/**
	 * @return boolean
	 */
	private function checkPermissions()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__);
		}
		$moduleName = $this->getModuleName();
		$roleService = f_permission_PermissionService::getRoleServiceByModuleName($moduleName);
		if ($roleService !== null)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . " Security Enabled for module " . $moduleName);
			}

			$permissionService = f_permission_PermissionService::getInstance();
			$user = users_UserService::getInstance()->getCurrentUser();
			
			if ($user !== null)
			{
				$login = $user->getLogin();
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' for user : ' . $user->__toString());
				}
			}
			else
			{
				$login = null;
			}
			
			$nodeIds = $this->getSecureNodeIds();
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . " Check user '$login'' for documentIds (" . implode(', ', $nodeIds) .")");
			}

			if (count($nodeIds) == 0)
			{
				$defaultNodeId = ModuleService::getInstance()->getRootFolderId($moduleName);
				$nodeIds[] = $defaultNodeId;
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . " Check user '$login'' for default documentId ($defaultNodeId)");
				}
			}

			foreach ($nodeIds as $nodeId)
			{
				$action  = $this->getSecureActionName($nodeId);
				if ($roleService->hasAction($action))
				{
					$permissions = $roleService->getPermissionsByAction($action);
					foreach ($permissions as $permission)
					{
						$ok = $permissionService->hasPermission($user, $permission, $nodeId);
						if (!$ok)
						{
							$this->onMissingPermission($login, $permission, $nodeId);
							return false;
						}

					}
				}
			}
		}
		return true;
	}
	
	/**
	 * Retourne le nom de l'action permissionnée
	 * @param Integer $documentId
	 * @param Boolean $addDocumentName
	 * @return String
	 */
	protected function getSecureActionName($documentId)
	{
		$secureAction = "modules_" . $this->getModuleName() . "." . $this->getActionName();
		if ($this->isDocumentAction())
		{
			$secureAction .= '.' . DocumentHelper::getDocumentInstance($documentId)->getPersistentModel()->getDocumentName();
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . "($documentId) -> $secureAction");
		}
		return $secureAction;
	}

	/**
	 * Tell the permission system this action is a document action ie. the permission
	 * depends on the document the action acts on.
	 * @return Boolean by default false
	 */
	protected function isDocumentAction()
	{
		return $this->suffixSecureActionByDocument();
	}

	protected function getSecureNodeIds()
	{
		return $this->getDocumentIdArrayFromRequest($this->getContext()->getRequest());
	}

	/**
	 * Traitement absence de permission
	 * @param String $login
	 * @param String $permission
	 * @param Integer $nodeId
	 * @throw BaseExeption(modules.[MODULENAME].errors.[ESCAPEDPERMISSION ex : modules-photoalbum-move-topic])
	 */
	protected function onMissingPermission($login, $permission, $nodeId)
	{
		$message = str_replace(array('_', '.'), '-', $permission);
		throw new BaseException($message, 'modules.'. $this->getModuleName() . '.errors.' . ucfirst($message));
	}
	
	/**
	 * @param Request $request
	 * @param Exception $e
	 * @param Boolean $popupAlert
	 * @return String
	 */
	protected function onBackOfficeException($request, $e, $popupAlert)
	{
		$this->setException($request, $e, $popupAlert);
		return self::getErrorView();
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) override isDocumentAction
	 */
	protected function suffixSecureActionByDocument()
	{
		return false;
	}
}