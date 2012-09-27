<?php
/**
 * @deprecated use \Change\Mvc\Controller
 */
class change_XulController extends change_Controller
{
	/**
	 * @return boolean
	 */
	public function allowAutoLogin()
	{
		return false;
	}

	/**
	 * @return void
	 */
	public function dispatch()
	{
		$this->getContext()->setMode(\Change\Mvc\Context::BACKEND);
		$this->getUser()->setUserNamespace(change_User::BACKEND_NAMESPACE);
		parent::dispatch();
	}

	/**
	 * @param array $urlParams
	 */
	protected function generateRedirectUrl($urlParams)
	{
		return LinkHelper::getUIParametrizedLink($urlParams)->getUrl();
	}
}