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
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
	}
	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentUpdated ($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentUpdated");}
		$document = $params['document'];
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}
	
	/**
	 * @param TagService $sender
	 * @param array $params
	 */
	public function onTagAdded ($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: ontagAdded ".$params["tag"]);}
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getTagPattern($params["tag"]));
	}
	/**
	 * @param TagService $sender
	 * @param array $params
	 */
	public function onTagRemoved ($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: ontagRemoved ".$params["tag"]);}
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getTagPattern($params["tag"]));
	}
	/**
	 * @param TagService $sender
	 * @param array $params
	 */
	public function onTagMoved ($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: ontagMoved ".$params["tag"]);}
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getTagPattern($params["tag"]));
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentDeleted($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentDeleted");}
		$document = $params['document'];
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentPublished($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentPublished");}
		$document = $params['document'];
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentDeactivated ($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentDeactivated");}
		$document = $params['document'];
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentUnpublished ($sender, $params)
	{
		if (Framework::isDebugEnabled()) Framework::debug("[". __CLASS__ . "]: onPersistentDocumentUnpublished");
		$document = $params['document'];
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentFiled ($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentFiled");}
		$document = $params['document'];
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}

	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentInTrash ($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentDocumentInTrash");}
		$document = $params['document'];
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}

	/**
	 * @param Object $sender
	 * @param array $params
	 */
	public function onChildrenOrdered ($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onChildrenOrdered");}
		$document = $params['parent'];
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}
	
	public function onPersistentTreeNodeOrdered($sender, $params)
	{
		if (Framework::isDebugEnabled()) {Framework::debug("[". __CLASS__ . "]: onPersistentTreeNodeOrdered");}
		$parentNode = $params['parentNode'];
		$document = DocumentHelper::getDocumentInstance($parentNode->getId());
		f_DataCacheService::getInstance()->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($document->getDocumentModelName()));
		f_DataCacheService::getInstance()->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($document->getId()));
	}
		
	/**
	 * @param Object $sender
	 * @param Array $params
	 */	
	public function onDayChange($sender, $params)
	{
		f_DataCacheService::getInstance()->cleanExpiredCache();
	}
}
