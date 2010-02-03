<?php
/**
 * @package framework.listener
 */
class f_listener_BackofficeIndexListener
{
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentPublished($sender, $params)
	{
		$document = $params['document'];
		if ($document->getPersistentModel()->isBackofficeIndexable())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[" . __CLASS__ . "]: Re-Indexing Backoffice IndexableDocument (id =" . $document->getId() . ")");
			}
			$this->update($document);
		}
	}
	
	public function onPersistentDocumentUpdated($sender, $params)
	{
		$document = $params['document'];
		if ($document->getPersistentModel()->isBackofficeIndexable())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[" . __CLASS__ . "]: Indexing Modified Backoffice IndexableDocument (id =" . $document->getId() . ")");
			}
			$this->add($document);
		}
	
	}
	
	public function onPersistentDocumentCreated($sender, $params)
	{
		$document = $params['document'];
		if ($document->getPersistentModel()->isBackofficeIndexable())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[" . __CLASS__ . "]: Indexing New Backoffice IndexableDocument (id =" . $document->getId() . ")");
			}
			$this->add($document);
		}
	}
	
	public function onPersistentDocumentCorrectionCreated($sender, $params)
	{
		$document = $params['document'];
		if ($document->getPersistentModel()->isBackofficeIndexable())
		{
			if ($document->getPersistentModel()->useCorrection())
			{
				$this->delete($document);
			}
		}
	}
	
	public function onPersistentDocumentDeleted($sender, $params)
	{
		$document = $params['document'];
		if ($document->getPersistentModel()->isBackofficeIndexable())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[" . __CLASS__ . "]: De-Indexing deleted Backoffice IndexableDocument (id =" . $document->getId() . ")");
			}
			$this->delete($document);
		}
	}
	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentUnpublished($sender, $params)
	{
		$document = $params['document'];
		if ($document->getPersistentModel()->isBackofficeIndexable())
		{
			$this->update($document);
		}
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
	
	private function update($document)
	{
		indexer_IndexService::getInstance()->updateBackoffice($document);
	}
	
	private function add($document)
	{
		indexer_IndexService::getInstance()->addBackoffice($document);
	}
	
	private function delete($document)
	{
		indexer_IndexService::getInstance()->deleteBackoffice($document);	
	}	
}