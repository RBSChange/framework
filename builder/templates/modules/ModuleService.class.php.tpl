<?php
/**
 * @package modules.<{$name}>.lib.services
 * @method <{$name}>_ModuleService getInstance()
 */
class <{$name}>_ModuleService extends ModuleBaseService
{	
	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
//	public function getParentNodeForPermissions($documentId)
//	{
//		// Define this method to handle permissions on a virtual tree node. Example available in list module.
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null
	 */
//	public function getVirtualParentForBackoffice($document)
//	{
//		return null;
//	}
	
	/**
	 * @param website_UrlRewritingService $wsurs
	 * @param string $moduleName
	 * @param string $actionName
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return string or null
	 */
//	public function generateActionRewritePath($wsurs, $moduleName, $actionName, $website, $lang, &$parameters)
//	{
//		return $wsurs->getActionRulePath($moduleName, $actionName, $website, $lang, $parameters);
//	}
}