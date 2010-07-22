<?php
/**
 * @package framework.libs.agavi.controller
 */
class controller_XulController extends controller_ChangeController 
{
    
	protected function setRequestContextMode()
	{
	    RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);
	    $this->getContext()->getUser()->setUserNamespace(FrameworkSecurityUser::BACKEND_NAMESPACE);
	}
	
	/**
	 * @param array $urlParams
	 */
	protected function generateRedirectUrl($urlParams)
	{
	    return LinkHelper::getUIParametrizedLink($urlParams)->getUrl();	    
	}
}