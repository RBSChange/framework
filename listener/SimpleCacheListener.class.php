<?php
/**
 * @package framework.listener
 */
class listener_SimpleCacheListener
{
	public function onPersistentDocumentCreated($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentCreated");}
		$document = $params['document'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}
	
	public function onPermissionsUpdated($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPermissionsUpdated");}
		$id = $params['nodeId'];
		// For the moment, only a change in the front end permission changes
		if (!is_null($id) && array_search('modules_website.AuthenticatedFrontUser', $params['updatedRoles']) !== false )
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[". __CLASS__ . "]: (Re-)Indexing  Permissioned descendents for treenode (id =" . $id . ")");
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentUpdated ($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentUpdated");}
		$document = $params['document'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}
	
	/**
	 * @param TagService $sender
	 * @param array $params
	 */
	public function onTagAdded ($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: ontagAdded ".$params["tag"]);}
		f_SimpleCache::clearCacheByTag($params["tag"]);
	}
	/**
	 * @param TagService $sender
	 * @param array $params
	 */
	public function onTagRemoved ($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: ontagRemoved ".$params["tag"]);}
		f_SimpleCache::clearCacheByTag($params["tag"]);
	}
	/**
	 * @param TagService $sender
	 * @param array $params
	 */
	public function onTagMoved ($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: ontagMoved ".$params["tag"]);}
		f_SimpleCache::clearCacheByTag($params["tag"]);
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentDeleted($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentDeleted");}
		$document = $params['document'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentPublished($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentPublished");}
		$document = $params['document'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentDeactivated ($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentDeactivated");}
		$document = $params['document'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentUnPublished ($sender, $params)
	{
		Framework::debug("[". __CLASS__ . "]: onPersistentDocumentUnPublished");
		$document = $params['document'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentFiled ($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentFiled");}
		$document = $params['document'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentInTrash ($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentInTrash");}
		$document = $params['document'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}

	/**
	 * @param Object $sender
	 * @param array $params
	 */
	public function onChildrenOrdered ($sender, $params)
	{
	    if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onChildrenOrdered");}
		$document = $params['parent'];
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}
	
	public function onPersistentTreeNodeOrdered($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentTreeNodeOrdered");}
		$parentNode = $params['parentNode'];
		$document = DocumentHelper::getDocumentInstance($parentNode->getId());
		f_SimpleCache::clearCacheByModel($document->getPersistentModel());
	}
	
    public function onSimpleCacheClearedNode($sender, $params)
    {
        if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onSimpleCacheClearedNode");}
		if (is_array($params) && isset($params['ids']))  
		{
		    f_SimpleCache::commitClearDispatched($params['ids']);
		}
		else
		{
		    f_SimpleCache::commitClearDispatched();
		}
    }
    
	/**
	 * @param Object $sender
	 * @param Array $params
	 */    
    public function onDayChange($sender, $params)
    {
    	f_SimpleCache::cleanExpiredCache();
    }
}
