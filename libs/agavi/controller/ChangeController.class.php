<?php
/**
 * @package framework.libs.agavi.controller
 */
class controller_ChangeController extends HttpController
{
	/**
	 * @return Request
	 */
	private function getRequest()
	{
		return $this->getContext()->getRequest();
	}

	/**
	 * Set the context lang
	 */
	private function setLangFromRequest()
	{
		$request = $this->getRequest();
		$lang = $request->getParameter(K::LANG_ACCESSOR);
		if (!f_util_StringUtils::isEmpty($lang))
		{
			$requestContext = RequestContext::getInstance();
			$requestContext->setLang($lang);
		}
	}
	
	protected function setRequestContextMode()
	{
	    RequestContext::getInstance()->setMode(RequestContext::FRONTOFFICE_MODE);
	}

	public function dispatch()
	{
		$this->setLangFromRequest();
		$this->setRequestContextMode();
		parent::dispatch();
	}

	public static function setNoCache()
	{
		if (headers_sent())
		{
            return;
        }

        header('Last-Modified: '. gmdate('D, d M Y H:i:s') . ' GMT');
        header('Expires: '. 'Mon, 26 Jul 1997 05:00:00 GMT');
        header('Cache-Control: '. 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: '. 'no-cache');
        if (defined('NODE_NAME'))
        {
        	header('X-Change-WebNode: '. NODE_NAME);
        }
	}

	/**
	 * "Final" forward to the action
	 *
	 * @param String $moduleName
	 * @param String $actionName
	 */
	private function doForward($moduleName, $actionName)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("[" . get_class($this) . "]: Forward to $moduleName/$actionName.");
		}
		$this->_forward($moduleName, $actionName);
		parent::forward($moduleName, $actionName);
	}

	/**
	 * @param String $moduleName
	 * @param String $actionName
	 */
	protected function _forward($moduleName, $actionName)
	{
	}

	/**
	 * Forward to $action, where the permissions are checked in the general case
	 *
	 * @param String $moduleName
	 * @param String $actionName
	 */
	public function forward ($moduleName, $actionName)
	{
		$this->doForward($moduleName, $actionName);
	}
	
	/**
	 * @param array $urlParams
	 */
	protected function generateRedirectUrl($urlParams)
	{
	    return LinkHelper::getParametrizedLink($urlParams)->getUrl();	    
	}
}