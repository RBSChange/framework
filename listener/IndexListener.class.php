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
		if ($document->getPersistentModel()->isIndexable())
		{
			$document = $params['document'];
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[" . __CLASS__ . "]: Indexing Published IndexableDocument (id =" . $document->getId() . ")");
			}
			$this->getIndexService()->update($document);
		}
	}
	
	public function onPersistentDocumentUpdated($sender, $params)
	{
		$document = $params['document'];
		if ($document->getPersistentModel()->isIndexable())
		{
			$rc = RequestContext::getInstance();
			foreach ($document->getI18nInfo()->getLangs() as $lang)
			{
				try
				{
					$rc->beginI18nWork($lang);
					if ($document->isPublished())
					{
						if (Framework::isDebugEnabled())
						{
							Framework::debug("[" . __CLASS__ . "]: Indexing Modified IndexableDocument (id =" . $document->getId() . ")");
						}
						$this->getIndexService()->update($document);
					}
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentUnpublished($sender, $params)
	{
		$document = $params['document'];
		if ($document->getPersistentModel()->isIndexable())
		{
			if (array_key_exists('oldPublicationStatus', $params))
			{
				if ('PUBLICATED' == $params['oldPublicationStatus'])
				{
					$this->getIndexService()->delete($document);
				}
			}
		}
	}
}
