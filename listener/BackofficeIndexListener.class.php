<?php
/**
 * @package framework.listener
 */
class f_listener_BackofficeIndexListener
{

	public function onPersistentDocumentPublished($sender, $params)
	{
		$this->update($params['document']);
	}
	
	public function onPersistentDocumentUpdated($sender, $params)
	{
		$this->add($params['document']);
	}
	
	public function onPersistentDocumentCreated($sender, $params)
	{
		$this->add($params['document']);
	}
	
	public function onPersistentDocumentDeleted($sender, $params)
	{
		$this->delete($params['document']);
	}
	
	public function onPersistentDocumentUnpublished($sender, $params)
	{
		$this->update($params['document']);
	}
	
	public function onPersistentDocumentActivated($sender, $params)
	{
		if (isset($params['correctionId']))
		{
			$documentId = $params['correctionId'];
			$deprecatedCorrection = DocumentHelper::getDocumentInstance($documentId);
			$this->delete($deprecatedCorrection);
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function update($document)
	{
		if (($document instanceof f_persistentdocument_PersistentDocument) && 
			$document->getPersistentModel()->isBackofficeIndexable())
			{
				indexer_IndexService::getInstance()->updateBackoffice($document);
			}
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function add($document)
	{
		if (($document instanceof f_persistentdocument_PersistentDocument) && 
		$document->getPersistentModel()->isBackofficeIndexable())
		{
			indexer_IndexService::getInstance()->addBackoffice($document);
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */	
	private function delete($document)
	{
		if (($document instanceof f_persistentdocument_PersistentDocument) && 
		$document->getPersistentModel()->isBackofficeIndexable())
		{
			indexer_IndexService::getInstance()->deleteBackoffice($document);
		}
	}	
}