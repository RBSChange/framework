<?php
abstract class change_JSONAction extends change_Action
{
	
	/**
	 * @param change_Request $request
	 * @param Exception $e
	 * @param boolean $popupAlert
	 * @return string
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
			change_Controller::setNoCache();
			header('Content-Type: application/json; charset=utf-8');
		}
		$message = array('status' => 'OK', 
						'module' => $this->getModuleName(null),
						'action' => $this->getActionName(null),
						'contents' => $content);
		echo JsonService::getInstance()->encode($message);
		return change_View::NONE;			
	}
	
	/**
	 * @param string $message
	 * @return unknown
	 */
	protected function sendJSONError($message, $popupAlert = true)
	{
		if (!headers_sent())
		{
			change_Controller::setNoCache();
			header('Content-Type: application/json; charset=utf-8');
		}
		$message = array('status' => 'ERROR', 
						'module' => $this->getModuleName(null),
						'action' => $this->getActionName(null),
						'contents' => array('popupAlert' => $popupAlert,
											'errorMessage' => $message));
		echo JsonService::getInstance()->encode($message);
		return change_View::NONE;			
	}
	
	/**
	 * @param Exception $e
	 * @return null
	 */
	protected function sendJSONException($e, $popupAlert = true)
	{
		if (!headers_sent())
		{
			change_Controller::setNoCache();
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
		return change_View::NONE;			
	}
	
	/**
	 * @see f_action_BaseAction::getActionName()
	 * @return string
	 */
	protected function getActionName()
	{
		return str_replace('JSON', '', parent::getActionName());
	}
}