<?php
interface f_mvc_Controller
{
	/**
	 * @return f_mvc_Controller;
	 */
	static function getInstance();
	
	/**
	 * @param f_mvc_Action $action
	 * @param f_mvc_Request $request
	 */
	function process($action, $request);
	
	/**
	 * @param String $moduleName
	 * @param String $actionName
	 */
	function forward($moduleName, $actionName);
	
	/**
	 * @param String $moduleName
	 * @param String $actionName
	 * @param array $moduleParams
	 * @param array $actionParams
	 */
	function redirect($moduleName, $actionName, $moduleParams = null, $actionParams = null);
	
	/**
	 * @return f_mvc_Response
	 */
	function getResponse();
	
	/**
	 * @return f_mvc_Request
	 */
	function getRequest();
	
	/**
	 * @return f_mvc_Context
	 */
	function getContext();
	
	/**
	 * @return f_mvc_Action
	 */
	function getProcessedAction();
}

