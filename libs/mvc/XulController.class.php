<?php
/**
 * @method change_XulController getInstance()
 */
class change_XulController extends change_Controller
{

	protected function setRequestContextMode()
	{
		RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);
		$this->getContext()->getUser()->setUserNamespace(change_User::BACKEND_NAMESPACE);
	}

	/**
	 * @param array $urlParams
	 */
	protected function generateRedirectUrl($urlParams)
	{
		return LinkHelper::getUIParametrizedLink($urlParams)->getUrl();
	}
}