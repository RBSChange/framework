<?php
/**
 * @deprecated use \Change\Mvc\AbstractAction
 */
abstract class change_Action extends \Change\Mvc\AbstractAction
{
	
	/**
	 * @deprecated
	 */
	const EXCEPTION_KEY = "BaseAction.Exception";
	
	/**
	 * @deprecated
	 */
	protected $ds = null;
	
	/**
	 * Returns the current lang.
	 * @return string
	 */
	public final function getLang()
	{
		return $this->getApplicationServices()->getI18nManager()->getLangByLCID($this->getLCID());
	}
	
	/**
	 * @deprecated
	 */
	protected final function getDocumentService()
	{
		if ($this->ds === null)
		{
			$this->ds = f_persistentdocument_DocumentService::getInstance();
		}
		return $this->ds;
	}
	
	/**
	 * @param Exception $e
	 * @return string|null
	 */
	protected function setExecuteException($e)
	{
		Framework::exception($e);
		/* @var $request change_Request */
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
		return null;
	}
	
	/**
	 * @deprecated
	 */
	protected function onBackOfficeException($request, $e, $popupAlert)
	{
		$this->setException($request, $e, $popupAlert);
		return null;
	}
	
	/**
	 * @param change_Request $request
	 * @param Exception $e
	 * @param boolean $popupAlert
	 */
	protected final function setException($request, $e, $popupAlert = false)
	{
		$xmlrenderer = new exception_XmlRenderer();
		if ($e instanceof BaseException)
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
	 * @deprecated
	 */
	protected function getTransactionManager()
	{
		return f_persistentdocument_TransactionManager::getInstance();
	}
	
	/**
	 * @deprecated
	 */
	protected function getPersistentProvider()
	{
		return f_persistentdocument_PersistentProvider::getInstance();
	}
}