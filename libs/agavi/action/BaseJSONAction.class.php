<?php
/**
 * @package framework.libs.agavi.action
 */
abstract class f_action_BaseJSONAction extends f_action_BaseAction
{
	
	/**
	 * @param Request $request
	 * @param Exception $e
	 * @param Boolean $popupAlert
	 * @return String
	 */
	protected function onBackOfficeException($request, $e, $popupAlert)
	{
		return $this->sendJSONException($e, $popupAlert);
	}
	
	/**
	 * @param array $content
	 * @return null
	 */
	protected function sendJSON($content)
	{
		if (!headers_sent())
		{
			controller_ChangeController::setNoCache();
			header('Content-Type: application/json; charset=utf-8');
		}
		$message = array('status' => 'OK', 
						'module' => $this->getModuleName(null),
						'action' => $this->getActionName(null),
						'contents' => $content);
		echo JsonService::getInstance()->encode($message);
		return View::NONE;			
	}
	
	/**
	 * @param String $message
	 * @return unknown
	 */
	protected function sendJSONError($message, $popupAlert = true)
	{
		if (!headers_sent())
		{
			controller_ChangeController::setNoCache();
			header('Content-Type: application/json; charset=utf-8');
		}
		$message = array('status' => 'ERROR', 
						'module' => $this->getModuleName(null),
						'action' => $this->getActionName(null),
						'contents' => array('popupAlert' => $popupAlert,
											'errorMessage' => $message));
		echo JsonService::getInstance()->encode($message);
		return View::NONE;			
	}
	
	/**
	 * @param Exception $e
	 * @return null
	 */
	protected function sendJSONException($e, $popupAlert = true)
	{
		if (!headers_sent())
		{
			controller_ChangeController::setNoCache();
			header('Content-Type: application/json; charset=utf-8');
		}
		$message = array('status' => 'ERROR', 
						'module' => $this->getModuleName(null),
						'action' => $this->getActionName(null),
						'contents' => array('popupAlert' => $popupAlert,
											'errorMessage' => $e->getMessage(), 
											'errorStackTrace' => $e->getTraceAsString()));
		
		if ($e instanceof BaseException ) 
		{
			$message['contents']['errorLocalKey'] = $e->getKey();
			$message['contents']['errorOriginalMessage'] = $message['contents']['errorMessage'];
			$message['contents']['errorMessage'] = $e->getLocaleMessage();
		}
		
		echo JsonService::getInstance()->encode($message);
		return View::NONE;			
	}
	
	/**
	 * @see f_action_BaseAction::getActionName()
	 *
	 * @return String
	 */
	protected function getActionName()
	{
		return str_replace('JSON', '', parent::getActionName());
	}

	
	
}