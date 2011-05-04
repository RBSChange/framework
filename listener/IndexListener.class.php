<?php
/**
 * @date Thu May 10 09:11:34 CEST 2007
 * @author franck.stauffer
 */
class f_listener_IndexListener
{
	/**
	 * @return indexer_IndexService
	 */
	private function getIndexService()
	{
		return indexer_IndexService::getInstance();
	}
	
	public function onPermissionsUpdated($sender, $params)
	{
		indexer_IndexService::getInstance()->scheduleReindexingByUpdatedRoles($params['updatedRoles']);
	}
	
	/**
	 * Index PUBLICATED documents.
	 *
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentPublished($sender, $params)
	{
		$document = $params['document'];
		if (($document instanceof f_persistentdocument_PersistentDocument) && 
			$document->getPersistentModel()->isIndexable())
		{
			$this->getIndexService()->update($document);
		}
	}
	
	public function onPersistentDocumentUpdated($sender, $params)
	{
		$document = $params['document'];
		if (($document instanceof f_persistentdocument_PersistentDocument) && 
			$document->getPersistentModel()->isIndexable())
		{
			$this->getIndexService()->update($document);
		}
	}
	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentUnpublished($sender, $params)
	{
		$document = $params['document'];
		if (($document instanceof f_persistentdocument_PersistentDocument) && 
			$document->getPersistentModel()->isIndexable())
		{
			if ('PUBLICATED' == $params['oldPublicationStatus'])
			{
				$this->getIndexService()->delete($document);
			}
		}
	}
}
