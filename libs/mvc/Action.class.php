<?php

abstract class change_Action
{
	
	const EXCEPTION_KEY = "BaseAction.Exception";

	/**
	 * @var change_Context
	 */
	private $context = null;
	
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
	 * @param change_Context $context.
	 * @return bool true, if initialization completes successfully, otherwise false.
	 */
	public function initialize ($context)
	{
		$this->context = $context;
		$this->m_moduleName =  $context->getModuleName();
		$this->m_actionName =  $context->getActionName();	
		return true;
	}
	
	/**
	 * @return change_Context
	 */
	public final function getContext()
	{
		return $this->context;
	}
	
	
	/**
	 * @return string
	 */
	public function getDefaultView()
	{
		return change_View::INPUT;
	}
		
	/**
	 * @return string
	 */
	public function handleError()
	{
		return change_View::ERROR;
	}
	
	/**
	 * @return boolean
	 */
	public function validate()
	{	
		return true;
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
				return $result;
			}
			else
			{
				return change_View::NONE;
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
	 * Please use this method for the action body instead of execute() (without
	 * the underscore): it is called by execute and directly receives f_Context
	 * and Request objects.
	 *
	 * @param change_Context $context
	 * @param change_Request $request
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
		return change_View::NONE;
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
	 * @param change_Request $request
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
	 * @param change_Request $request
	 * @return array<integer>
	 */
	protected function getDocumentIdArrayFromRequest($request)
	{
		$docIds = $request->getParameter(change_Request::DOCUMENT_ID, array());
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
	 * @param change_Request $request
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
	 * @param change_Request $request
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected final function getDocumentInstanceFromRequest($request)
	{
		return $this->getDocumentService()->getDocumentInstance($this->getDocumentIdFromRequest($request));
	}


	/**
	 * Returns an array of document instances from the IDs received in the request.
	 *
	 * @param change_Request $request
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
	 * Returns the HTTP methods available for this action.
	 *
	 * @return string
	 */
	public function getRequestMethods()
	{
		return change_Request::POST | change_Request::GET;
	}


	/**
	 * All generic actions are secured: they can't be executed from a
	 * non-authenticated user.
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
			$permissionService = f_permission_PermissionService::getInstance();	
			$nodeIds = $this->getSecureNodeIds();
			if (count($nodeIds) == 0)
			{
				$defaultNodeId = ModuleService::getInstance()->getRootFolderId($moduleName);
				$nodeIds[] = $defaultNodeId;
			}
			
			$user = false;
			foreach ($nodeIds as $nodeId)
			{
				$action  = $this->getSecureActionName($nodeId);
				if ($roleService->hasAction($action))
				{
					$permissions = $roleService->getPermissionsByAction($action);
					foreach ($permissions as $permission)
					{
						if ($user === false) 
						{
							$user = users_UserService::getInstance()->getCurrentUser();
						}
						$ok = $permissionService->hasPermission($user, $permission, $nodeId);
						if (!$ok)
						{
							$this->onMissingPermission($user === null ? null :  $user->getLogin(), $permission, $nodeId);
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
	 * @example modules_website.RewriteUrl
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
		return false;
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
	 * @param change_Request $request
	 * @param Exception $e
	 * @param Boolean $popupAlert
	 * @return String
	 */
	protected function onBackOfficeException($request, $e, $popupAlert)
	{
		$this->setException($request, $e, $popupAlert);
		return change_View::NONE;
	}
	
	/**
	 * @deprecated
	 */
	protected final static function getSuccessView()
	{
		throw new Exception('Depecated');
	}


	/**
	 * @deprecated
	 */
	protected final static function getErrorView()
	{
		throw new Exception('Depecated');
	}
}