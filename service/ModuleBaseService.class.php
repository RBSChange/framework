<?php
/**
 * @package framework.service
 */
class ModuleBaseService extends BaseService
{
	/**
	 * @var ModuleBaseService
	 */
	private static $instance;
	
	/**
	 * @return ModuleBaseService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();	
		}
		return self::$instance;
	}
	
	/**
	 * @param String $moduleName
	 * @return ModuleBaseService
	 */
	public static function getInstanceByModuleName($moduleName)
	{
		if (class_exists($moduleName.'_ModuleService'))
		{
			return f_util_ClassUtils::callMethod($moduleName.'_ModuleService', 'getInstance');
		}
		return null;
	}
	
	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function getParentNodeForPermissions($documentId)
	{
		return null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	public function getVirtualParentForBackoffice($document)
	{
		return null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $moduleName
	 * @return Integer[] id of documents that define the path of the document
	 */
	public function getPathTo($document, $moduleName = null)
	{
		$documents = array();
		
		$ds = $document->getDocumentService();
		$ts = TreeService::getInstance();

		// Handle corrections.
		if ($document->getPersistentModel()->useCorrection() && $document->getCorrectionofid())
		{
			$pathDocument = DocumentHelper::getDocumentInstance($document->getCorrectionofid());
		}
		else 
		{
			$pathDocument = $document;
		}
		
		// Handle virtual tree nodes.
		if ($moduleName === null)
		{
			$moduleName = $document->getPersistentModel()->getModuleName();
		}
		$ms = ModuleBaseService::getInstanceByModuleName($moduleName);
		if ($ms !== null)
		{
			while ($pathDocument !== null && $ts->getInstanceByDocument($pathDocument) === null)
			{
				$pathDocument = $ms->getVirtualParentForBackoffice($pathDocument);
				if ($pathDocument !== null)
				{
					$documents[] = $pathDocument;
				}
			}
		}
		
		if ($pathDocument === null)
		{	
			return array();
		}
		
		$result = array();
		$documents = array_merge($ds->getAncestorsOf($pathDocument), array_reverse($documents));
		
		if (count($documents) > 0)
		{
			$rootFolderId = ModuleService::getInstance()->getRootFolderId($moduleName);
			$ancestorsIds = DocumentHelper::getIdArrayFromDocumentArray($documents);
			$startIndex = 1;
			if ($documents[0]->getId() != $rootFolderId)
			{
				$rootFolder = DocumentHelper::getDocumentInstance($rootFolderId);
				foreach ($rootFolder->getTopicsArray() as $topic)
				{
					$keys = array_keys($ancestorsIds, $topic->getId());					
					if (count($keys) > 0)
					{
						$startIndex = $keys[0];
						break;
					}
				}
			}
			$result = array_slice($ancestorsIds, $startIndex);
		}
		
		$result[] = $document->getId();
		return $result;
	}
	
	/**
	 * @param website_UrlRewritingService $wsurs
	 * @param string $moduleName
	 * @param string $actionName
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return string or null
	 */
	public function generateActionRewritePath($wsurs, $moduleName, $actionName, $website, $lang, &$parameters)
	{
		return $wsurs->getActionRulePath($moduleName, $actionName, $website, $lang, $parameters);
	}
}
