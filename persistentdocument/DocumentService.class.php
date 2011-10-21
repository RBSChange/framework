<?php
/**
 * @package framework.persistentdocument
 * f_persistentdocument_DocumentService
 * Evenenent déclanché sur les documents :
 *  - persistentDocumentCreated
 *  - persistentDocumentUpdated
 *  - persistentDocumentDeleted
 *  - persistentDocumentPublished
 *  - persistentDocumentUnpublished
 *  - persistentDocumentActivated
 *  - persistentDocumentDeactivated
 *  - persistentDocumentFiled
 *  - persistentDocumentInTrash
 *  - persistentDocumentCanceled
 *  - persistentDocumentCorrectionCreated
 *  - persistentDocumentWorkflowInstanceCreated
 *  - persistentDocumentMoved
 *  - persistentDocumentDeprecated
 */
class f_persistentdocument_DocumentService extends BaseService
{

	/**
	 * @var f_persistentdocument_DocumentService
	 */
	private static $instance;

	/**
	 * @return f_persistentdocument_DocumentService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function __get ($name)
	{
		switch ($name)
		{
			case 'pp':
				return $this->getPersistentProvider();
				break;
			case 'tm':
				return $this->getTransactionManager();
				break;
		}
		return null;
	}
	
	public function __call ($name, $args)
	{
		switch ($name)
		{
			case 'getProvider':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . 'method');
				return $this->getPersistentProvider();
			case 'setPersistentProvider':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . 'method');
				return;
			case 'setTransactionManager':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . 'method');
				return;
			default:
				throw new Exception('No method ' . get_class($this) . '->' . $name);
		}
	}
	
	protected function __construct()
	{
	}
	
	/**
	 * @return f_persistentdocument_DocumentService
	 */
	public static function getInstanceByDocumentModelName($modelName)
	{
		list($package, $documentName) = explode('/', $modelName);
		$moduleName = str_replace('modules_', '', $package);
		$serviceClassName = $moduleName.'_'.ucfirst($documentName).'Service';
		return  f_util_ClassUtils::callMethod($serviceClassName, 'getInstance');
	}

	/**
	 * Create a query based on 'modules_xxxx/yyyy' model.
	 * Return documents that are instance of modules_xxxx/yyyy,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		throw new IllegalOperationException('createQuery is not implemented in service ' . get_class($this));
	}

	/**
	 * Create a query based on 'modules_xxxx/yyyy' model.
	 * Only documents that are strictly instance of 'modules_xxxx/yyyy'
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		throw new IllegalOperationException('createStrictQuery is not implemented in service ' . get_class($this));
	}

	/**
	 * Saves a document with the use of a database transaction, and optionnally inserts
	 * the document in the tree (during the same transaction) if $parentNodeId is given.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document to save.
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 *
	 * @throws IllegalArgumentException
	 * @throws ValidationException
	 * @throws Exception
	 */
	public final function save($document, $parentNodeId = null)
	{
		if ($this !== $document->getDocumentService())
		{
			$document->getDocumentService()->save($document, $parentNodeId);
			return;
		}


		$document->setParentNodeId($parentNodeId);
		if ($document->isNew())
		{
			$this->insert($document, $parentNodeId);
		}
		else
		{
			$this->update($document, $parentNodeId);
		}
	}

	/**
	 * persist only metastring field in database
	 * @return Boolean true if something was modified
	 */
	public final function saveMeta($document)
	{
		$document->applyMetas();
		if ($document->isPropertyModified("metastring"))
		{
		    $tm = $this->getTransactionManager();
		    $pp = $this->getPersistentProvider();
			try
			{
				$tm->beginTransaction();
				$pp->updateDocument($document);
				$tm->commit();
				return true;
			}
			catch (Exception $e)
			{
				$tm->rollBack($e);
				throw $e;
			}
		}
		return false;
	}

	/**
	 * @param Integer $parentId
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getByParentId($parentId)
	{
		return $this->createQuery()
		->add(Restrictions::published())
		->add(Restrictions::childOf($parentId))
		->find();
	}

	/**
	 * Get the documents that use a document
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function getContainers($document)
	{
		return $this->getPersistentProvider()->getChildRelationBySlaveDocumentId($document->getId());
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function validateDocument($document)
	{
		if (!$document->isValid())
		{
			$message = '';
			$errors = $document->getValidationErrors();
			$first = true;
			foreach ($errors as $error)
			{
				if ($first)
				{
					$message .= $error;
					$first = false;
				}
				else
				{
					$message .= "; ".$error;
				}
			}
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Document ".$document->__toString()." does not validate: ".$message);
			}
			throw new ValidationException($document->__toString().": " . $message);
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function setAuthor($document)
	{
		if (is_null($document->getAuthor()))
		{
			$user = users_UserService::getInstance()->getCurrentUser();
			if (is_null($user))
			{
				$document->setAuthor('system');
			}
			else
			{
				$document->setAuthor($user->getLogin());
				$document->setAuthorid($user->getId());
			}
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document The document to insert.
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 *
	 * @throws IllegalArgumentException
	 * @throws ValidationException
	 * @throws Exception
	 */
	private function insert($document, $parentNodeId = null)
	{
	    $tm = $this->getTransactionManager();
		$pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();
			$this->preSave($document, $parentNodeId);
			$this->preInsert($document, $parentNodeId);

			$modifiedPropertyNames = $document->getModifiedPropertyNames();
						
			if (Framework::isDebugEnabled())
			{
				Framework::debug("insertDocument ". get_class($document).' parentNodeId = '.$parentNodeId);
			}

			$this->setAuthor($document);

			if (is_null($document->getCreationdate()))
			{
				$document->setCreationdate(gmdate('Y-m-d H:i:s'));
			}
			if (is_null($document->getModificationdate()))
			{
				$document->setModificationdate(gmdate('Y-m-d H:i:s'));
			}

			$document->setDocumentversion(0);

			if ($parentNodeId === null && $document->getPersistentModel()->isLinkedToRootFolder())
			{
				$parentNodeId = ModuleService::getInstance()->getRootFolderId($document->getPersistentModel()->getModuleName());
			}

			if ($parentNodeId !== null)
			{
				$parentDocument = $this->getDocumentInstance($parentNodeId);
				$parentModel = $parentDocument->getPersistentModel();
				$candidateComponentNames = $parentModel->findTreePropertiesNamesByType($document->getDocumentModelName());
				$countCandidate = count($candidateComponentNames);
				if ($countCandidate > 1)
				{
					throw new Exception('Can not resolve property candidate to add '.$document->getId().' on '.$parentNodeId);
				}
				elseif ($countCandidate == 1 && $parentModel->hasInverseProperty($candidateComponentNames[0]))
				{
					$propertyName = $candidateComponentNames[0];
					if ($parentModel->getInverseProperty($propertyName)->isArray())
					{
						$parentDocument->{'add' . ucfirst($propertyName).'Inverse'}($document);
					}
					else
					{
						$parentDocument->{'set' . ucfirst($propertyName).'Inverse'}($document);
					}
				}
			}
			else
			{
				$parentDocument = null;
				$candidateComponentNames = array();
			}

			// apply metas if some of the setMetaXX() methods was called
			$document->applyMetas();

			$this->validateDocument($document);

			$pp->insertDocument($document);
			$document->saveDocumentsInverse();

			if ($parentDocument !== null)
			{
				if ($document->canInsertInTree())
				{
					$this->insertDocumentInTree($document, $parentDocument, $candidateComponentNames);
				}
			}

			$document->setModifiedPropertyNames($modifiedPropertyNames);

			$this->postInsert($document, $parentNodeId);
			$this->postSave($document, $parentNodeId);
			
			$this->setToIndexIfNeeded($document, 'Insert');
			$document->setModifiedPropertyNames();
						
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}

		f_event_EventManager::dispatchEvent('persistentDocumentCreated', $this, array("document" => $document));
		$this->publishDocumentIfPossible($document, array("cause" => "insert"));
	}

	/**
	 * Update documentversion property or throw exception
	 * @exception Exception "Version (XXX) of document (YYY) is obsolete."
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function updateDocumentVersion($document)
	{
		if ($document->isPropertyModified('documentversion'))
		{
			throw new Exception("Version (". $document->getDocumentversion() .") of document (".$document->getId().") is obsolete.", $document->getId() . '.' . $document->getDocumentversion());
		}
		$document->setDocumentversion($document->getDocumentversion() + 1);
	}

	/**
	 * Decrement the document version. Used if there is a rollback during a save.
	 * @exception Exception "Version (XXX) of document (YYY) is obsolete."
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function revertDocumentVersion($document)
	{
		$document->setDocumentversion($document->getDocumentversion() - 1);

		// Set back the property as unmodified.
		$modifiedProperties = $document->getModifiedPropertyNames();
		foreach ($modifiedProperties as $index => $name)
		{
			if ($name == 'documentversion')
			{
				unset($modifiedProperties[$index]);
			}
		}
		$document->setModifiedPropertyNames($modifiedProperties);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
	public final function correctionNeeded($document)
	{
		if ($document->getPersistentModel()->useCorrection())
		{
			$currentStatus = $document->getPublicationstatus();
			return ($currentStatus !== 'DRAFT' && $currentStatus !== 'CORRECTION' && $currentStatus !== 'WORKFLOW');
		}
		return false;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document The document to update.
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 *
	 * @throws IllegalArgumentException
	 * @throws ValidationException
	 * @throws Exception
	 */
	private function update($document, $parentNodeId = null)
	{
		if (!$document->isModified())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Document " . $document->__toString() . " not modified update canceled");
			}
			return;
		}

		$documentId = $document->getId();

		if ($document->getPersistentModel()->useCorrection())
		{
			$currentStatus = $document->getPublicationstatus();
			if ($currentStatus != 'DRAFT' && $currentStatus != 'CORRECTION')
			{
				$documentId = $document->getId();
				throw new Exception("Can not update the modified document ($documentId) in status ($currentStatus).");
			}
		}

		$this->updateDocumentVersion($document);

		if (Framework::isDebugEnabled())
		{
			Framework::debug("updateDocument (". $document->getId() .") ". get_class($document));
		}
        $tm = $this->getTransactionManager();
        $pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			$this->preSave($document, $parentNodeId);
			$this->preUpdate($document, $parentNodeId);

			$modifiedProperties = $document->getOldValues();
			$modifiedPropertyNames = $document->getModifiedPropertyNames();

			$document->setModificationdate(gmdate('Y-m-d H:i:s'));

			if ($parentNodeId !== null)
			{
				$parentDocument = $this->getDocumentInstance($parentNodeId);
				$parentModel = $parentDocument->getPersistentModel();
				$candidateComponentNames = $parentModel->findTreePropertiesNamesByType($document->getDocumentModelName());
				$countCandidate = count($candidateComponentNames);
				if ($countCandidate > 1)
				{
					throw new Exception('Can not resolve property candidate to add '.$document->getId().' on '.$parentNodeId);
				}
				elseif ($countCandidate == 1 && $parentModel->hasInverseProperty($candidateComponentNames[0]))
				{
					$propertyName = $candidateComponentNames[0];

					if ($parentModel->getInverseProperty($propertyName)->isArray())
					{
						$parentDocument->{'add' . ucfirst($propertyName).'Inverse'}($document);
					}
					else
					{
						$parentDocument->{'set' . ucfirst($propertyName).'Inverse'}($document);
					}
				}
			}
			else
			{
				$parentDocument = null;
				$candidateComponentNames = array();
			}

			// apply metas if some of the setMetaXX() methods was called
			$document->applyMetas();

			$this->validateDocument($document);

			$pp->updateDocument($document);
			$document->saveDocumentsInverse();

			if ($parentDocument !== null)
			{
				if ($document->canInsertInTree())
				{
					$this->insertDocumentInTree($document, $parentDocument, $candidateComponentNames);
				}
			}

			$document->setOldValues($modifiedProperties);
			$document->setModifiedPropertyNames($modifiedPropertyNames);

			$this->postUpdate($document, $parentNodeId);
			$this->postSave($document, $parentNodeId);
			
			$this->setToIndexIfNeeded($document, 'Update');
			
			$document->setOldValues();
			$document->setModifiedPropertyNames();

			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			$this->revertDocumentVersion($document);
			throw $e;
		}

		f_event_EventManager::dispatchEvent('persistentDocumentUpdated', $this,
		array("document" => $document, "modifiedPropertyNames" => $modifiedPropertyNames, "oldPropertyValues" => $modifiedProperties));
		$this->publishDocumentIfPossible($document,
		array("cause" => "update", "modifiedPropertyNames" => $modifiedPropertyNames, "oldPropertyValues" => $modifiedProperties));
	}

	/**
	 * Deletes the given document with the use of a database transaction.
	 * @param f_persistentdocument_PersistentDocument $document
	 * @throws IllegalArgumentException
	 * @throws Exception
	 */
	public final function delete($document)
	{
		if ($this !== $document->getDocumentService())
		{
			$document->getDocumentService()->delete($document);
			return;
		}

		if ($document->isNew())
		{
			throw new Exception("Can not delete a transient document");
		}

		if ($document->isDeleted())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Document " . $document->__toString() . " already deleted delete canceled");
			}
			return;
		}
        $tm = $this->getTransactionManager();
        $pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			$this->unpublishDocument($document, array("cause" => "delete"));

			$completeDelete = $this->checkDeleteDocument($document);

			if ($completeDelete)
			{
				$this->preDelete($document);

				//Effacements des tags du document
				//Déplacement en tete site a la création des tag fonctionnel (le document doit toujours etre classé dans l'arbre)
				if (Framework::isDebugEnabled())
				{
					Framework::debug('Effacement tags du document '.$document->getId());
				}

				$tagService = TagService::getInstance();
				$tags  = $tagService->getTags($document);
				if (count($tags) > 0)
				{
					foreach ($tags as $tagName)
					{
						$tagService->removeTag($document, $tagName);
					}
				}

				// Effacement des noeuds [@document_id = $document->id]
				if (Framework::isDebugEnabled())
				{
					Framework::debug('Effacement noeud du document '.$document->getId());
				}
				
				$ts = TreeService::getInstance();
				$node = $ts->getInstanceByDocument($document);
				if ($node !== null) {$ts->deleteNode($node);}
				
				// Effacement des relations CHILD[@id2 = $document->id]
				if (Framework::isDebugEnabled())
				{
					Framework::debug('Effacement relations child  du document '.$document->getId());
				}
				$relations = $pp->getChildRelationBySlaveDocumentId($document->getId());
				$rc = RequestContext::getInstance();
				foreach ($relations as $relation)
				{
					$doc = $pp->getDocumentInstance($relation->getDocumentId1());
					if ($doc->isDeleted()) {continue;}
					try 
					{
						$rc->beginI18nWork($doc->getLang());
						if ($doc->getPersistentModel()->isArrayProperty($relation->getName()))
						{
							$index = $doc->{'getIndexof'.ucfirst($relation->getName())}($document);
							if ($index >= 0)
							{
								$doc->{'remove'.ucfirst($relation->getName()).'ByIndex'}($index);
							}
						}
						else
						{
							$doc->{'set'.ucfirst($relation->getName())}(null);
						}
						$doc->save();				
						$rc->endI18nWork();
					}
					catch (Exception $e)
					{
						$rc->endI18nWork($e);
					}
				}
			}
			else
			{
				$this->preDeleteLocalized($document);
			}

			$this->deleteCorrection($document);

			$pp->deleteDocument($document);

			if ($completeDelete)
			{
				$this->postDelete($document);
			}
			else
			{
				$this->postDeleteLocalized($document);
			}
			
			$this->setToIndexIfNeeded($document, 'Delete');

			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			if ($e instanceof TransactionCancelledException)
			{
				throw $e->getSourceException();
			}
			throw $e;
		}

		f_event_EventManager::dispatchEvent('persistentDocumentDeleted', $this, array("document" => $document));
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function purgeDocument($document)
	{
		if ($this !== $document->getDocumentService())
		{
			$document->getDocumentService()->purgeDocument($document);
			return;
		}
		
		if (!$document->getPersistentModel()->isLocalized())
		{
			$this->delete($document);
			return;
		}
		
		$requestContext = RequestContext::getInstance();
		$langs = array_reverse($document->getI18nInfo()->getLangs());
		foreach ($langs as $lang)
		{
			try
			{
				$requestContext->beginI18nWork($lang);
				$this->delete($document);
				$requestContext->endI18nWork();
			}
			catch (Exception $e)
			{
				$requestContext->endI18nWork($e);
			}			
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @return Boolean
	 */
	protected final function checkDeleteDocument($persistentDocument)
	{
		$documentModel = $persistentDocument->getPersistentModel();
		if ($documentModel->isLocalized())
		{
			if (!$persistentDocument->isContextLangAvailable())
			{
				return false;
			}
			if (count($persistentDocument->getI18nInfo()->getLabels()) > 1)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	private function deleteCorrection($persistentDocument)
	{
        $pp = $this->getPersistentProvider();
		$documentModel = $persistentDocument->getPersistentModel();
		if ($documentModel->useCorrection())
		{
			$correctionOfId  = intval($persistentDocument->getCorrectionofid());
			if ($correctionOfId != 0)
			{
				//Effacement d'une correction
				try
				{
					RequestContext::getInstance()->beginI18nWork($persistentDocument->getLang());
					if (Framework::isDebugEnabled())
					{
						Framework::debug("Supression de la référence à la correction ". $persistentDocument->getId() . " du document " . $correctionOfId);
					}
					$original = $this->getDocumentInstance($correctionOfId);
					$original->setCorrectionid(null);
					
					$this->setToIndexIfNeeded($original, 'Update');
					$pp->updateDocument($original);
					RequestContext::getInstance()->endI18nWork();
				}
				catch (Exception $e)
				{
					RequestContext::getInstance()->endI18nWork($e);
				}

			}
			else
			{
				//Effacement du document principal
				$lang = RequestContext::getInstance()->getLang();
				$query = $pp->createQuery($documentModel->getName());
				$query->add(Restrictions::eq('lang', $lang));
				$query->add(Restrictions::eq('correctionofid', $persistentDocument->getId()));
				$results = $query->find();
				foreach ($results as $correction)
				{
					if (Framework::isDebugEnabled())
					{
						Framework::debug("Effacement de la correction ". $correction->getId() . " du document " . $persistentDocument->getId());
					}
					$correction->delete();
				}
			}
		}
	}

	/**
	 * Returns a document instance from its ID.
	 * If you expect a given model name, give the modelName parameter :
	 * you will save queries, except if you only load model or the label
	 * of the document (directly stored in f_document table)
	 * @param integer $documentId
	 * @param String $modelName the expected model name
	 * @return f_persistentdocument_PersistentDocument
	 */
	public final function getDocumentInstance($documentId, $modelName = null)
	{
		return $this->getPersistentProvider()->getDocumentInstance($documentId, $modelName);
	}


	/**
	 * Creates a new instance of a document from its document model name and returns it.
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getNewDocumentInstance()
	{
		throw new Exception("Can not get new document instance for DocumentService : use getNewDocumentInstanceByModelName instead");
	}

	/**
	 * Creates a new instance of a document from its document model name and returns it.
	 *
	 * @param string $documentModelName Example: modules_news/news
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected final function getNewDocumentInstanceByModelName($documentModelName)
	{
		return $this->getPersistentProvider()->getNewDocumentInstance($documentModelName);
	}

	///////////////////////////////////////////////////////////////////////////
	//                                                                       //
	// TAGS MANAGEMENT METHODS                                               //
	//                                                                       //
	///////////////////////////////////////////////////////////////////////////

	/**
	 * @return TagService
	 */
	private function getTagService()
	{
		return TagService::getInstance();
	}

	/**
	 * Gets all the tags of the document.
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array<string> Array with all the tags.
	 *
	 * @throws IllegalArgumentException
	 */
	public function getTags($document)
	{
		return $this->getTagService()->getTags($document);
	}


	/**
	 * Gets all the object_TagObjects of $document.
	 * @param f_persistentdocument_PersistentDocument $document The document to get the tags of.
	 * @return array<object_TagObjects> Array with all the object_TagObjects.
	 *
	 * @throws IllegalArgumentException
	 */
	public function getTagObjects($document)
	{
		return $this->getTagService()->getTagObjects($document);
	}


	/**
	 * Checks wether the document has the specified tag. Returns true if the
	 * document has the tag, otherwise returns false.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which the tag is checked.
	 * @param string $tag The tag to check.
	 * @return boolean true if the document has the tag, false otherwise.
	 *
	 * @throws InvalidTagException
	 * @throws IllegalArgumentException
	 */
	public function hasTag($document, $tag)
	{
		return $this->getTagService()->hasTag($document, $tag);
	}


	/**
	 * Returns true if $document has ALL the listed tags in the specified array $tags.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which the tags are checked.
	 * @param array<string> $tags Array of the tags to check.
	 *
	 * @return boolean true if the document has ALL the listed tags.
	 *
	 * @throws InvalidTagException
	 * @throws IllegalArgumentException
	 */
	public function hasTags($document, $tags)
	{
		return $this->getTagService()->hasTags($document, $tags);
	}


	/**
	 * Returns true if $document has some of the listed tags in the specified array $tags.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which the tags are checked.
	 * @param array<string> $tags Array of the tags to check.
	 *
	 * @return boolean true if the document has some of the listed tags.
	 *
	 * @throws InvalidTagException
	 * @throws IllegalArgumentException
	 */
	public function hasSomeTags($document, $tags)
	{
		return $this->getTagService()->hasSomeTags($document, $tags);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $tag
	 * @return void
	 */
	public function tagAdded($document, $tag)
	{
		return;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $tag
	 * @return void
	 */
	public function tagRemoved($document, $tag)
	{
		return;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
	public function tagMovedFrom($fromDocument, $toDocument, $tag)
	{
		return;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
	public function tagMovedTo($fromDocument, $toDocument, $tag)
	{
		return;
	}

	/**
	 * Sets the exclusive tag $tag on the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which to set the exclusive tag.
	 * @param string $tag The exclusive tag to set.
	 *
	 * @throws InvalidExclusiveTagException
	 * @throws IllegalArgumentException
	 */
	public function setExclusiveTag($document, $tag)
	{
		$this->getTagService()->setExclusiveTag($document, $tag);
	}

	/**
	 * Adds the contextual tag $tag to the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which to set the contextual tag.
	 * @param string $tag The contextual tag to set.
	 *
	 * @throws InvalidContextualTagException
	 * @throws IllegalArgumentException
	 */
	public function setContextualTag($document, $tag)
	{
		$this->getTagService()->setContextualTag($document, $tag);
	}

	/**
	 * Set the function tag $tag on the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $tag
	 *
	 * @throws InvalidFunctionalTagException
	 * @throws IllegalArgumentException
	 */
	public function setFunctionalTag($document, $tag)
	{
		$this->getTagService()->setFunctionalTag($document, $tag);
	}

	///////////////////////////////////////////////////////////////////////////
	//                                                                       //
	// TREE MANAGEMENT METHODS                                               //
	//                                                                       //
	///////////////////////////////////////////////////////////////////////////

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param f_persistentdocument_PersistentDocument $parentDocument
	 * @param array<String> $candidateComponentNames
	 * @param integer $parentNodeId ID of the parent node to insert the document into.
	 */
	protected function insertDocumentInTree($document, $parentDocument, $candidateComponentNames)
	{
		$parentNodeId = $parentDocument->getId();
		$documentId = $document->getId();

		if (count($candidateComponentNames) == 0)
		{
			$ts = TreeService::getInstance();
			$parentTreeNode = $ts->getInstanceByDocument($parentDocument);

			if ($parentTreeNode !== null)
			{
				$currentTreeNode = $ts->getInstanceByDocument($document);
				if ($currentTreeNode === null)
				{
					$ts->newLastChildForNode($parentTreeNode, $document->getId());
					if (Framework::isDebugEnabled())
					{
						Framework::debug("[DocumentService] document ($documentId) has been linked to its parent ($parentNodeId).");
					}
				}
				else if ($currentTreeNode->getParent()->getId() != $parentNodeId)
				{
					$ts->moveToLastChildNode($currentTreeNode, $parentTreeNode);
					if (Framework::isDebugEnabled())
					{
						Framework::debug("[DocumentService] document ($documentId) has been moved to its parent ($parentNodeId).");
					}
				}
			}
			else if (Framework::isDebugEnabled())
			{
				Framework::debug("[DocumentService] the parent node ($parentNodeId) can't exist.");
			}
		}
		elseif (count($candidateComponentNames) == 1)
		{
			$property = $parentDocument->getPersistentModel()->getProperty($candidateComponentNames[0]);
			if ($property !== null)
			{
				if ($property->isArray())
				{
					$parentDocument->{'add' . ucfirst($property->getName())}($document);
				}
				else
				{
					$parentDocument->{'set' . ucfirst($property->getName())}($document);
				}
				$parentDocument->save();
			}
		}
		else
		{
			throw new Exception('Can not resolve property candidate to add '.$documentId.' on '.$parentNodeId);
		}
	}

	/**
	 * Moves $document into the destination node identified by $destId.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document to move.
	 * @param integer $destId ID of the destination node.
	 * @param integer $beforeId
	 * @param integer $afterId
	 */
	public function moveTo($document, $destId, $beforeId = null, $afterId = null)
	{
		$documentId =  $document->getId();
		if ($destId == $documentId || $beforeId == $documentId  || $afterId == $documentId)
		{
			return;
		}
		if ($this !== $document->getDocumentService())
		{
			$document->getDocumentService()->moveTo($document, $destId, $beforeId, $afterId);
			return;
		}

		$newContainerId = null;
		$oldContainerId = null;
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			// Pre move
			$this->onMoveToStart($document, $destId);
			// Move it!
			$destDocument = $this->getDocumentInstance($destId);
			$destModel = $destDocument->getPersistentModel();
			// find tree destDocument properties that could accept the document
			$candidates = $destModel->findTreePropertiesNamesByType($document->getDocumentModelName());
			if (count($candidates) == 0)
			{
				$ts = TreeService::getInstance();
				$destNode = $ts->getInstanceByDocument($destDocument);
				if ($destNode === null || $ts->isAncestor($destNode, $documentId))
				{
					throw new Exception("Invalid parent node : " . $destId);
				}
				$sibling = ($afterId !== null) ? $afterId : $beforeId;
				if ($sibling !== null)
				{
					$siblingNode = $ts->getInstanceByDocumentId($sibling);
					if ($siblingNode === null || $siblingNode->getParentId() !== $destId)
					{
						$afterId = $beforeId = null;
					}
				}
				
				$newContainerId = $destId;
				$node = $ts->getInstanceByDocument($document);
				if ($node === null)
				{
					$documentNode = $ts->newLastChildForNode($destNode, $document->getId());
				}
				else
				{
					$documentNode = $node;
				}
				
				if ($beforeId)
				{
					$ts->moveToPrevSiblingForNode($documentNode, $beforeId);
				}
				else if ($afterId)
				{ 
					$ts->moveToNextSiblingForNode($documentNode, $afterId);
				}
				else if ($node !== null)
				{
					$oldContainerId = $node->getParent()->getId();
					$ts->moveToLastChildNode($node, $destNode);
				}
			}
			else if (count($candidates) == 1)
			{
				// There is one property of type tree-node that accepts the document
				$propertyName = $candidates[0];
				if ($destModel->hasInverseProperty($propertyName))
				{
					if ($destModel->getInverseProperty($propertyName)->isArray())
					{
						$destDocument->{'add' . ucfirst($propertyName).'Inverse'}($document);
					}
					else
					{
						$destDocument->{'set' . ucfirst($propertyName).'Inverse'}($document);
					}
				}
				else
				{
					if ($destModel->isArrayProperty($propertyName))
					{
						if ($beforeId || $afterId)
						{
							$pivotId = ($beforeId) ? $beforeId : $afterId;
							$tmparray = $destDocument->{'get' . ucfirst($propertyName) . 'Array'}();
							$append = true;
							$orderedarray = array();
							foreach ($tmparray as $pivot)
							{
								if ($pivot->getId() == $documentId) {continue;}
								if ($pivot->getId() == $pivotId)
								{
									$append = false;
									if ($beforeId)
									{
										$orderedarray[] = $document;
										$orderedarray[] = $pivot;
									}
									else
									{
										$orderedarray[] = $pivot;
										$orderedarray[] = $document;

									}
								}
								else
								{
									$orderedarray[] = $pivot;
								}
							}
							if ($append)
							{
								$orderedarray[] = $document;
							}
							$destDocument->{'set' . ucfirst($propertyName) . 'Array'}($orderedarray);
						}
						else
						{
							$destDocument->{'add' . ucfirst($propertyName)}($document);
						}
					}
					else
					{
						$destDocument->{'set' . ucfirst($propertyName)}($document);
					}
				}
				$destDocument->save();
			}
			else
			{
				// We can not determine wich tree-node property to use
				throw new Exception("Invalid parent node : " . $destId);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}

		$this->documentMoved($document, $destId, $oldContainerId, $newContainerId);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
	private function documentMoved($document, $destId, $oldContainerId, $newContainerId)
	{
		$this->onDocumentMoved($document, $destId);

		$eventParams = array("document" => $document, 'newparentId' => $destId);
		f_event_EventManager::dispatchEvent('persistentDocumentMoved', $this, $eventParams);
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			if ($oldContainerId !== null)
			{
				// we mark the origin container modified
				$this->touch($oldContainerId);
			}
			if ($newContainerId !== null)
			{
				// we mark the destination container modified
				$this->touch($newContainerId);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
	}

	/**
	 * Mark the document as modified
	 * @param Integer $documentId
	 * @return void
	 */
	function touch($documentId)
	{
		$document = $this->getDocumentInstance($documentId);
		$document->setModificationdate(null);
		$document->save();
	}

	/**
	 * Called upon successful moveTo operation. The method is executed OUTSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
	protected function onDocumentMoved($document, $destId)
	{
		// empty
	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
	protected function onMoveToStart($document, $destId)
	{
		// empty
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $parentDocument
	 * @param Integer $childDocmunentId
	 */
	public function removeDocumentId($parentDocument, $childDocmunentId)
	{
		if ($this !== $parentDocument->getDocumentService())
		{
			$parentDocument->getDocumentService()->removeDocumentId($parentDocument, $childDocmunentId);
			return;
		}
	}

	/**
	 * Returns the child documents from the tree of $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $modelName Restrict to model $modelName.
	 *
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	public function getChildrenOf($document, $modelName = null)
	{
		$docs = array();
		$treeNode = TreeService::getInstance()->getInstanceByDocument($document);
		foreach ($treeNode->getChildren($modelName) as $child)
		{
			$docs[] = $child->getPersistentDocument();
		}
		return $docs;
	}


	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $modelName Restrict to model $modelName.
	 *
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getAncestorsOf($document, $modelName = null)
	{
		if (!$document->getTreeId())
		{
			if ($document->getPersistentModel()->useCorrection() && $document->getCorrectionofid())
			{
				$master = DocumentHelper::getDocumentInstance($document->getCorrectionofid(), $document->getDocumentModelName());
				return $this->getAncestorsOf($master, $modelName);
			}
			else
			{
				return array();
			}
		}
		
		$docs = array();
		$treeNode = TreeService::getInstance()->getInstanceByDocument($document);
		if ($treeNode)
		{
			foreach ($treeNode->getAncestors() as $ancestor)
			{
				if ($modelName === null || $ancestor->getDocumentModelName() === $modelName )
				{
					$docs[] = $ancestor->getPersistentDocument();
				}
			}
		}
		return $docs;
	}

	/**
	 * TODO: move to TreeService
	 * Generate the path string of ancestor document (excluding the rootfolder).
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $separator ' > ' by default
	 * @return string
	 */
	public function getPathOf($document, $separator = ' > ')
	{
		$ms = ModuleBaseService::getInstanceByModuleName($document->getPersistentModel()->getModuleName());
		if ($ms === null)
		{
			$ms = ModuleBaseService::getInstance();
		}
		$ids = $ms->getPathTo($document);
		$labels = array();
		foreach ($ids as $id)
		{
			$labels[] = $this->getDocumentInstance($id)->getLabel();
		}

		return implode($separator, $labels);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getFirstChildOf($document)
	{
		$childDoc = null;
		$treeNode = TreeService::getInstance()->getInstanceByDocument($document);
		$child = $treeNode->getChildAt(0);
		if ( ! is_null($child) )
		{
			$childDoc = $child->getPersistentDocument();
		}
		return $childDoc;
	}


	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null if no parent exists
	 */
	public function getParentOf($document)
	{
		if (!$document->getTreeId())
		{
			if ($document->getPersistentModel()->useCorrection() && $document->getCorrectionofid())
			{
				$master = DocumentHelper::getDocumentInstance($document->getCorrectionofid(), $document->getDocumentModelName());
				return $this->getParentOf($master);
			}
			else
			{
				return null;
			}
		}
		return TreeService::getInstance()->getParentDocument($document);
	}

	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentDocument or null if no parent exists
	 */
	public function getParentOfById($documentId)
	{
		return $this->getParentOf($this->getDocumentInstance($documentId));
	}

	/**
	 * Add custom log informations
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $actionName
	 * @param array $info
	 */
	public function addActionLogInfo($document, $actionName, &$info)
	{
		return;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{

	}


	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return void
	 */
	protected function preDelete($document)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return void
	 */
	protected function preDeleteLocalized($document)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return void
	 */
	protected function postDelete($document)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return void
	 */
	protected function postDeleteLocalized($document)
	{

	}

	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $oldPublicationStatus
	 * @param array $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		// Nothing done by default.
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $oldPublicationStatus
	 * @param String $eventName
	 * @param array $extraEventParams
	 */
	protected final function dispatchPublicationStatusChanged($document, $oldPublicationStatus, $eventName, $extraEventParams = null)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . "(" .$document->__toString().",$oldPublicationStatus, $eventName)");
		}
		$eventParams = array("document" => $document, 'oldPublicationStatus' => $oldPublicationStatus);
		if (!is_null($extraEventParams))
		{
			$eventParams = array_merge($eventParams, $extraEventParams);
		}
		$document->getDocumentService()->publicationStatusChanged($document, $oldPublicationStatus, $eventParams);
		f_event_EventManager::dispatchEvent($eventName, $this, $eventParams);
	}

	/**
	 * Rend actif le document
	 * @exception IllegalTransitionException si le document n'est pas dans l'état DRAFT, CORRECTION ou WORKFLOW
	 * @param Integer $documentId
	 */
	public final function activate($documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("activate($documentId)");
		}

		$document = $this->getDocumentInstance($documentId);
		$documentModel = $document->getPersistentModel();

		if ($this->isPublishTransitionPossible($document))
		{
			$this->publishDocument($document, array("cause" => "activate"));
			return;
		}

		$currentStatus = $document->getPublicationstatus();
		if ($currentStatus != 'DRAFT' && $currentStatus != 'CORRECTION' && $currentStatus != 'WORKFLOW'  && $currentStatus != 'DEACTIVATED')
		{
			throw new IllegalTransitionException($currentStatus, 'ACTIVE');
		}

		$eventName = null;
		$extraEventParams = array();
		$callbacks = array();
		
		$tm = $this->getTransactionManager();
		$pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			if ($documentModel->useCorrection())
			{
				$correctionOfId = intval($document->getCorrectionofid());
				if ($correctionOfId != 0)
				{
					//CORRECTION WORKFLOW  -> ACTIVE
					try
					{
						RequestContext::getInstance()->beginI18nWork($document->getLang());

						$masterDocument = $this->getDocumentInstance($correctionOfId);
						// be careful: do not move this line at the end of this block (...)
						$callbacks[] = array('correctionActivated', $masterDocument, array("correction" => $document));

						$extraEventParams["correctionId"] = $document->getId();
						$swapDocument = $masterDocument->duplicate();
						// Copie des propriétés de la correction vers le document
						$document->copyPropertiesTo($masterDocument, false);
						// Copie du document vers la correction
						$swapDocument->copyPropertiesTo($document);
						// Correction dépréciée
						$document->setCorrectionofid($correctionOfId);
						$document->setCorrectionid(null);
						$document->setPublicationstatus('DEPRECATED');
						$pp->updateDocument($document);
						
						//Correction deprecated
						$this->setToIndexIfNeeded($document, 'UpdateStatus');

						// Effacement de la correction dans l'arbre
						$ts = TreeService::getInstance();
						$node = $ts->getInstanceByDocument($document);
						if ($node !== null) {$ts->deleteNode($node);}

						// Le document n'a plus de correction et on l'active
						$masterDocument->setCorrectionofid(null);
						$masterDocument->setCorrectionid(null);
						$masterDocument->setPublicationstatus('ACTIVE');
						$pp->updateDocument($masterDocument);
						$eventName = 'persistentDocumentActivated';
						$document = $masterDocument;

						RequestContext::getInstance()->endI18nWork();
					}
					catch (Exception $e)
					{
						RequestContext::getInstance()->endI18nWork($e);
					}
				}
				else
				{
					//DRAFT WORKFLOW -> ACTIVE
					$document->setPublicationstatus('ACTIVE');
					$pp->updateDocument($document);
					$eventName = 'persistentDocumentActivated';
				}
			}
			else
			{
				//DRAFT WORKFLOW -> ACTIVE
				$document->setPublicationstatus('ACTIVE');
				$pp->updateDocument($document);
				$eventName = 'persistentDocumentActivated';
			}
			$this->setToIndexIfNeeded($document, 'UpdateStatus');
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}

		foreach ($callbacks as $callback)
		{
			$args = ((isset($callback[2])) ? $callback[2] : null);
			$this->{$callback[0]}($callback[1], $args);
		}

		if (!is_null($eventName))
		{
			$extraEventParams["cause"] = "activate";
			$this->dispatchPublicationStatusChanged($document, $currentStatus, $eventName, $extraEventParams);
			$this->publishDocument($document, $extraEventParams);
		}
	}


	protected final function correctionActivated($document, $args)
	{
		$this->onCorrectionActivated($document, $args);

		$correction = $args['correction'];
		$oldStatus = 'CORRECTION';
		$eventName = 'persistentDocumentDeprecated';
		$extraEventParams = array('masterdocument' => $document);
		$this->dispatchPublicationStatusChanged($correction, $oldStatus, $eventName, $extraEventParams);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
	protected function onCorrectionActivated($document, $args)
	{
		//Empty
	}

	/**
	 * Publie le document
	 * @param Integer $documentId
	 * @see publishDocument
	 * @return mixed true if published, false if unpublished, null if nothing changed
	 * @throws IllegalTransitionException if transition to PUBLISHED status is not permitted
	 */
	public final function publish($documentId)
	{
		return $this->publishDocument($this->getDocumentInstance($documentId));
	}

	/**
	 * Publie le document si possible
	 * @param Integer $documentId
	 * @see publishDocument
	 * @return mixed true if published, false if unpublished, null if nothing changed
	 */
	public final function publishIfPossible($documentId)
	{
		$document = $this->getDocumentInstance($documentId);
		if ($this->isPublishTransitionPossible($document))
		{
			return $this->publishDocument($document);
		}
		return false;
	}

	/**
	 * Publie le document si possible
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $extraEventParams
	 * @see publishDocument
	 * @return mixed true if published, false if unpublished, null if nothing changed
	 */
	protected final function publishDocumentIfPossible($document, $extraEventParams = null)
	{
		if ($this->isPublishTransitionPossible($document))
		{
			return $this->publishDocument($document, $extraEventParams);
		}
		return false;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return Boolean
	 */
	private final function isPublishTransitionPossible($document)
	{
		if ($document->isContextLangAvailable())
		{	
			$currentStatus = $document->getPublicationstatus();
			return $currentStatus === 'PUBLICATED' || $currentStatus === 'ACTIVE';
		}
		return false;
	}

	/**
	 * Publie le document si possible
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $extraEventParams
	 * @return mixed true if published, false if unpublished, null if nothing changed
	 * @throws IllegalTransitionException if transition to PUBLISHED status is not permitted
	 */
	public final function publishDocument($document, $extraEventParams = null)
	{
		if (Framework::isDebugEnabled())
		{
			$documentId = $document->getId();
			Framework::debug("publishDocument($documentId)");
		}

		$currentStatus = $document->getPublicationstatus();
		if (!$this->isPublishTransitionPossible($document))
		{
			throw new IllegalTransitionException($currentStatus, 'PUBLICATED/ACTIVE');
		}

		$eventName = null;
		$published = null;
		$tm = $this->getTransactionManager();
		$pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();
			if ($document->getDocumentService()->isPublishable($document))
			{
				if ($currentStatus == 'ACTIVE')
				{
					$document->setPublicationstatus('PUBLICATED');
					$this->removeActivePublicationStatusInfo($document);
					$pp->updateDocument($document);					
					$this->setToIndexIfNeeded($document, 'UpdateStatus');
					$eventName = 'persistentDocumentPublished';
					$published = true;
				}
			}
			else if ($currentStatus == 'PUBLICATED')
			{
				$document->setPublicationstatus('ACTIVE');
				$pp->updateDocument($document);
				
				$this->setToIndexIfNeeded($document, 'UpdateStatus');
				$eventName = 'persistentDocumentUnpublished';
				$published = false;
			}
			$this->saveMeta($document);
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			$published = null;
		}

		if (!is_null($eventName))
		{
			$this->dispatchPublicationStatusChanged($document, $currentStatus, $eventName, $extraEventParams);
		}
		return $published;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function unpublishDocument($document, $extraEventParams = null)
	{
		$pp = $this->getPersistentProvider();
		if ($document->getPublicationstatus() == 'PUBLICATED')
		{
			$document->setPublicationstatus('ACTIVE');
			$pp->updateDocument($document);
			$this->setToIndexIfNeeded($document, 'UpdateStatus');
			$this->dispatchPublicationStatusChanged($document, 'PUBLICATED', 'persistentDocumentUnpublished', $extraEventParams);
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		if (!$this->isPublishTransitionPossible($document))
		{
			return false;
		}
		
		$date = date("Y-m-d H:i:s");
		$enddate = $document->getEndpublicationdate();
		if (!is_null($enddate))
		{
			if ($date > $enddate)
			{
				$this->setActivePublicationStatusInfo($document, '&framework.persistentdocument.status.active.enddate;');
				return false;
			}
		}

		$startdate = $document->getStartpublicationdate();
		if (!is_null($startdate))
		{
			if ($date < $startdate)
			{
				$this->setActivePublicationStatusInfo($document, '&framework.persistentdocument.status.active.startedate;');
				return false;
			}
		}

		return true;
	}
	
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $operation [Insert, Update, Delete, UpdateStatus]
	 */
	protected function setToIndexIfNeeded($document, $operation)
	{	
		$pm = $document->getPersistentModel();
		if ($pm->isBackofficeIndexable() || $pm->isIndexable())
		{
			
			if ($operation === 'Update')
			{
				$ip = $pm->getIndexedPropertiesInfos();
				$mp = $document->getModifiedPropertyNames();
				$count = count(array_intersect($mp, array_keys($ip)));
				if ($count == 0)
				{
					return;
				}	
			}
			indexer_IndexService::getInstance()->toIndex($document);
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param indexer_IndexService $indexService
	 * @return indexer_IndexedDocument || null
	 */
	public function getIndexedDocument($document, $indexService)
	{
		$pm = $document->getPersistentModel();		
		$indexedDocument = $indexService->getNewIndexedDocument($document);
		if ($indexedDocument !== null)
		{
			$this->updateIndexDocument($indexedDocument, $document, $indexService);
			if ($indexedDocument && !$indexedDocument->foIndexable() && !$indexedDocument->boIndexable())
			{
				$indexedDocument = null;
			}
		}
		return $indexedDocument;	
	}
	
	/**
	 * @param indexer_IndexedDocument $indexedDocument
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param indexer_IndexService $indexService
	 */
	protected function updateIndexDocument($indexedDocument, $document, $indexService)
	{
		// Nothing done by default.
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $statusInfo
	 * @param array $substitutions
	 */
	protected final function setActivePublicationStatusInfo($document, $statusInfo, $substitutions = null)
	{
		$metaName = 'ActPubStatInf'.RequestContext::getInstance()->getLang();
		if ($document->hasMeta($metaName))
		{
			if ($document->getMeta($metaName) !== $statusInfo)
			{
				$document->setMeta($metaName, $statusInfo);
			}
		}
		else if ($statusInfo !== null)
		{
			$document->setMeta($metaName, $statusInfo);
		}
		
		$metaSubstName = 'ActPubStatInfSubst'.RequestContext::getInstance()->getLang();
		if ($statusInfo !== null)
		{
			$subst = f_util_ArrayUtils::isNotEmpty($substitutions) ? serialize($substitutions) : null;
			if ($document->hasMeta($metaSubstName))
			{
				if ($document->getMeta($metaSubstName) !== $subst)
				{
					$document->setMeta($metaSubstName, $subst);
				}
			}
			else if ($subst !== null)
			{
				$document->setMeta($metaSubstName, $subst);
			}
		}
		else
		{
			if ($document->hasMeta($metaSubstName))
			{
				$document->setMeta($metaSubstName, null);
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	protected final function removeActivePublicationStatusInfo($document)
	{
		$metaName = 'ActPubStatInf'.RequestContext::getInstance()->getLang();
		if ($document->hasMeta($metaName))
		{
			$document->setMeta($metaName, null);
		}
		
		$metaSubstName = 'ActPubStatInfSubst'.RequestContext::getInstance()->getLang();
		if ($document->hasMeta($metaSubstName))
		{
			$document->setMeta($metaSubstName, null);
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $lang
	 * @return string
	 */
	public final function getUIActivePublicationStatusInfo($document, $lang)
	{
		$substitutions = null;
		if ($document->hasMeta('ActPubStatInfSubst'.$lang))
		{
			$substitutions = unserialize($document->getMeta('ActPubStatInfSubst'.$lang));
		}
		return f_Locale::translateUI($document->getMeta('ActPubStatInf'.$lang), $substitutions);
	}

	/**
	 * Désactive le document
	 *
	 * @param Integer $documentId
	 */
	public final function deactivate($documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("deactivate($documentId)");
		}

		$document = $this->getDocumentInstance($documentId);

		$currentStatus = $document->getPublicationstatus();
		if ($currentStatus == 'DEACTIVATED')
		{
			return;
		}

		if ($currentStatus != 'PUBLICATED' && $currentStatus != 'ACTIVE')
		{
			throw new IllegalTransitionException($currentStatus, 'DEACTIVATED');
		}

		$eventName = null;
		$tm = $this->getTransactionManager();
		$pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			$this->unpublishDocument($document, array("cause" => "deactivate"));
			$document->setPublicationstatus('DEACTIVATED');
			$this->removeActivePublicationStatusInfo($document);
			$pp->updateDocument($document);
			$this->saveMeta($document);
			$eventName = 'persistentDocumentDeactivated';
			
			$this->setToIndexIfNeeded($document, 'UpdateStatus');
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}

		if (!is_null($eventName))
		{
			$this->dispatchPublicationStatusChanged($document, 'ACTIVE', $eventName);
		}
	}

	/**
	 * Archive le document
	 * @exception IllegalTransitionException si le document n'est pas dans l'état ACTIVE
	 * @param Integer $documentId
	 */
	public final function file($documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("file($documentId)");
		}

		$document = $this->getDocumentInstance($documentId);

		$currentStatus = $document->getPublicationstatus();
		if ($currentStatus == 'FILED')
		{
			return;
		}

		if ($currentStatus != 'PUBLICATED' && $currentStatus != 'ACTIVE' && $currentStatus != 'DEACTIVATED')
		{
			throw new IllegalTransitionException($currentStatus, 'FILED');
		}

		$eventName = null;
		$tm = $this->getTransactionManager();
		$pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			$this->unpublishDocument($document, array("cause" => "file"));
			$document->setPublicationstatus('FILED');
			$this->removeActivePublicationStatusInfo($document);
			$pp->updateDocument($document);
			
			$this->saveMeta($document);
			
			$this->setToIndexIfNeeded($document, 'UpdateStatus');
			$eventName = 'persistentDocumentFiled';

			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}

		if (!is_null($eventName))
		{
			$this->dispatchPublicationStatusChanged($document, $currentStatus, $eventName);
		}
	}


	/**
	 * Met le document à la corbeille
	 * @exception IllegalTransitionException si le document n'est pas dans l'état ACTIVE ou FILED
	 * @param Integer $documentId
	 */
	public final function putInTrash($documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("putInTrash($documentId)");
		}

		$document = $this->getDocumentInstance($documentId);
		$currentStatus = $document->getPublicationstatus();

		if ($currentStatus == 'TRASH')
		{
			return;
		}

		if ($currentStatus != 'DRAFT' && $currentStatus != 'ACTIVE' && $currentStatus != 'PUBLICATED'
		&& $currentStatus != 'FILED' && $currentStatus != 'DEACTIVATED')
		{
			throw new IllegalTransitionException($currentStatus, 'TRASH');
		}

		$eventName = null;
        $tm = $this->getTransactionManager();
        $pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			$this->unpublishDocument($document, array("cause" => "putInTrash"));
			$document->setPublicationstatus('TRASH');
			$this->removeActivePublicationStatusInfo($document);
			$pp->updateDocument($document);
			$this->saveMeta($document);
			$eventName = 'persistentDocumentInTrash';

			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}

		if (!is_null($eventName))
		{
			$this->dispatchPublicationStatusChanged($document, $currentStatus, $eventName);
		}
	}

	/**
	 * Met le document à l'état bouillon, ou correction (si le document est une correction)
	 * @exception IllegalTransitionException si le document n'est pas dans l'état WORKFLOW
	 * @param Integer $documentId
	 */
	public final function cancel($documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("cancel($documentId)");
		}

		$document = $this->getDocumentInstance($documentId);
		$currentStatus = $document->getPublicationstatus();

		if ($currentStatus == 'DRAFT' || $currentStatus == 'CORRECTION')
		{
			return;
		}

		if ($currentStatus != 'WORKFLOW')
		{
			throw new IllegalTransitionException($currentStatus, 'DRAFT');
		}

		$eventName = null;

		$documentModel = $document->getPersistentModel();
        $tm = $this->getTransactionManager();
        $pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			if ($documentModel->useCorrection())
			{
				$correctionOfId = intval($document->getCorrectionofid());
				if ($correctionOfId != 0)
				{

					$document->setPublicationstatus('CORRECTION');
				}
				else
				{
					$document->setPublicationstatus('DRAFT');
				}

			}
			else
			{
				$document->setPublicationstatus('DRAFT');
			}

			$pp->updateDocument($document);
			
			$this->setToIndexIfNeeded($document, 'UpdateStatus');
			
			$eventName = 'persistentDocumentWorkflowCanceled';

			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}

		if (!is_null($eventName))
		{
			$this->dispatchPublicationStatusChanged($document, $currentStatus, $eventName);
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument
	 */
	public final function createDocumentCorrection($document)
	{
		$tm = $this->getTransactionManager();
        $pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			if (!$this->correctionNeeded($document))
			{
				throw new BaseException('no-correction-needed', 'framework.exception.errors.No-correction-needed', array());
			}

			$correctionId = intval($document->getCorrectionid());
			if ($correctionId > 0)
			{
				throw new BaseException('correction-already-exist', 'framework.exception.errors.Correction-already-exist', array());
			}

			$correctionOfId = intval($document->getCorrectionofid());
			if ($correctionOfId > 0)
			{
				throw new BaseException('correction-already-exist', 'framework.exception.errors.Correction-already-exist', array());
			}

			$correction = $document->duplicate();
			$correction->setCorrectionofid($document->getId());

			//Set the correct revision.
			$correction->setDocumentversion($document->getDocumentversion());
			$correction->setPublicationstatus('CORRECTION');
			$pp->insertDocument($correction);
			
			$this->setToIndexIfNeeded($document, 'Insert');
			$correctionId = $correction->getId();

			$folderId = $this->getCorrectionParentNodeId($correction);
			if ($folderId !== null)
			{
				$ts = TreeService::getInstance();
				$parentTreeNode = $ts->getInstanceByDocumentId($folderId);
				$ts->newLastChildForNode($parentTreeNode, $correction->getId());
				if (Framework::isDebugEnabled())
				{
					Framework::debug("[DocumentService] correction document ($correctionId) has been linked to its parent ($folderId).");
				}
			}

			$modifiedProperties = null;
			$i18nModifiedProperties = null;
			$i18nDoc = null;
			if ($document->isModified())
			{
				// Some hack to only update correctionId property
				// even if other properties were modified
				$modifiedProperties = $document->getModifiedPropertyNames();
				$document->setModifiedPropertyNames();
			}
			if ($document->getPersistentModel()->isLocalized())
			{
				//echo "Is internationalized";
				$lang = RequestContext::getInstance()->getLang();
				$i18nDoc = $pp->getI18nDocument($document, $lang, $document->getLang() == $lang);
				if ($i18nDoc->isModified())
				{
					$i18nModifiedProperties = $i18nDoc->getModifiedProperties();
					$i18nDoc->setModifiedProperties();
				}
			}
			$document->setCorrectionid($correctionId);
				
			$pp->updateDocument($document);
			if ($modifiedProperties !== null)
			{
				$document->setModifiedPropertyNames($modifiedProperties);
			}
			if ($i18nModifiedProperties !== null)
			{
				$i18nDoc->setModifiedProperties($i18nModifiedProperties);
			}
			
			$this->setToIndexIfNeeded($document, 'Update');
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
		
		$eventParams = array("document" => $document, "correction" => $correction);
		f_event_EventManager::dispatchEvent('persistentDocumentCorrectionCreated', $this, $eventParams);
			
		return $correction;
	}

	/**
	 * Creer un nouveau document dans l'etat correction
	 * @param Integer $documentId
	 * @return Integer
	 */
	public final function createCorrection($documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("createCorrection($documentId)");
		}
		$document = $this->getDocumentInstance($documentId);
		$correction = $this->createDocumentCorrection($document);
		return $correction->getId();
	}

	/**
	 * Retourne l'identifiant du noeud ou la correction sera ajouter
	 * @param f_persistentdocument_PersistentDocument $correction
	 * @return Integer
	 */
	protected function getCorrectionParentNodeId($correction)
	{
		return null;
	}

	/**
	 * Creer une instance de workflow sur le document
	 * @exception IllegalTransitionException si le document n'est pas dans l'état DRAFT ou CORRECTION
	 * @exception IllegalOperationException si aucun workflow n'a pu etre initializé.
	 * @param Integer $documentId
	 * @param array $startParameters
	 * @return Integer Identifiant de l'instance du workflow null si l'instance n'a pas pu etre créée
	 */
	public final function createWorkflowInstance($documentId, $startParameters = array())
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("createWorkflowInstance($documentId)");
		}

		$document = $this->getDocumentInstance($documentId);
		$documentModel = $document->getPersistentModel();
		$currentStatus = $document->getPublicationstatus();

		if (($currentStatus != 'DRAFT' && $currentStatus != 'CORRECTION') || !$documentModel->hasWorkflow())
		{
			throw new IllegalTransitionException($currentStatus, 'WORKFLOW');
		}

		$eventName = null;
		$caseId = null;
        $tm = $this->getTransactionManager();
        $pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();

			$case =  workflow_WorkflowEngineService::getInstance()->initWorkflowInstance($documentId, $documentModel->getWorkflowStartTask(), $startParameters);
			if ($case !== null)
			{
				$caseId = $case->getId();
				if (Framework::isDebugEnabled())
				{
					Framework::debug("WorkflowInstance ($caseId) created for document ($documentId)");
				}

				if ($document->getPublicationstatus() == $currentStatus)
				{
					$document->setPublicationstatus('WORKFLOW');
					$pp->updateDocument($document);
					$this->setToIndexIfNeeded($document, 'UpdateStatus');
					$eventName = 'persistentDocumentWorkflowInstanceCreated';
				}
			}

			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}

		if ($caseId === null)
		{
			throw new BaseException('no-active-workflow-for-task', 'framework.exception.errors.No-active-workflow-for-task', array('task' => $documentModel->getWorkflowStartTask()));
		}

		if (!is_null($eventName))
		{
			$this->dispatchPublicationStatusChanged($document, $currentStatus, $eventName);
		}

		return $caseId;
	}

	/**
	 * Duplicate a document vo and all localized version
	 *
	 * @param Integer $documentId
	 * @param Integer $parentNodeId
	 */
	public final function duplicate($documentId, $parentNodeId = null)
	{
		$originalDocument = $this->getDocumentInstance($documentId);

		if ($this !== $originalDocument->getDocumentService())
		{
			$originalDocument->getDocumentService()->duplicate($documentId, $parentNodeId);
			return;
		}

		$requestContext = RequestContext::getInstance();

		$newDocument = $this->getNewDocumentInstance();
		$documentModel = $newDocument->getPersistentModel();
		$documentUseCorrection = $documentModel->useCorrection();
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();

			$vo = $originalDocument->getLang();
			$labelSize  = $documentModel->getProperty('label')->getMaxSize();

			//Update VO
			try
			{
				$requestContext->beginI18nWork($vo);
				$originalDocument->copyPropertiesTo($newDocument, true);
				// Clear f_tags meta
				$newDocument->setMeta("f_tags", array());
				$newDocument->setLabel($this->updateDuplicateLabel($newDocument->getLabel(), $parentNodeId, $labelSize));
				$newDocument->setAuthor(null);
				$newDocument->setAuthorid(null);
				$newDocument->setCreationdate(null);
				$newDocument->setModificationdate(null);
				$newDocument->setDocumentversion(0);
				$newDocument->setPublicationstatus($documentModel->getDefaultNewInstanceStatus());

				if ( $documentUseCorrection )
				{
					$newDocument->setCorrectionid(null);
					$newDocument->setCorrectionofid(null);
				}

				$requestContext->endI18nWork();
			}
			catch (Exception $e)
			{
				$requestContext->endI18nWork($e);
			}

			if ($originalDocument->isLocalized())
			{
				//Update localized
				foreach ($requestContext->getSupportedLanguages() as $lang)
				{
					if ($lang == $vo)
					{
						continue;
					}

					try
					{
						$requestContext->beginI18nWork($lang);
						if ($originalDocument->isContextLangAvailable())
						{
							$originalDocument->copyPropertiesTo($newDocument, false);

							$newDocument->setLabel($this->updateDuplicateLabel($newDocument->getLabel(), $parentNodeId, $labelSize));
							$newDocument->setPublicationstatus($documentModel->getDefaultNewInstanceStatus());

							if ( $documentUseCorrection )
							{
								$newDocument->setCorrectionid(null);
								$newDocument->setCorrectionofid(null);
							}
						}
						$requestContext->endI18nWork();
					}
					catch (Exception $e)
					{
						$requestContext->endI18nWork($e);
					}
				}
			}

			$this->preDuplicate($newDocument, $originalDocument, $parentNodeId);

			$this->save($newDocument, $parentNodeId);

			$this->postDuplicate($newDocument, $originalDocument, $parentNodeId);

			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
	}

	/**
	 * @param String $label
	 * @param Integer $parentNodeId
	 * @param Integer $size
	 * @return String
	 */
	private function updateDuplicateLabel($label, $parentNodeId, $size = -1)
	{
		$defaultPrefix = f_Locale::translate('&modules.generic.backoffice.Duplicate-prefix;') . ' ';
		$number = -1;
		$prefix = $defaultPrefix;
		while ($parentNodeId)
		{
			if ($number < 0)
			{
				$prefix = '';
			}
			else
			{
				$prefix = str_replace('{number}', $number == 0 ? '' : ' ('.$number.')', $defaultPrefix);
			}
			if ($prefix == $defaultPrefix) {break;}
			$result = $this->createQuery()
				->add(Restrictions::eq('label', $prefix.$label))
				->add(Restrictions::childOf($parentNodeId))
				->setProjection(Projections::rowCount('count'))->find();

			if ($result[0]['count'] == 0)
			{
				break;
			}
			$number += 1;
		}
		$newLabel = $prefix . $label;

		if ($size != -1 && f_util_StringUtils::strlen($newLabel) > $size)
		{
			$newLabel = f_util_StringUtils::substr($newLabel, 0, $size);
		}

		return $newLabel;
	}

	/**
	 * this method is call before save the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param f_persistentdocument_PersistentDocument $newDocument
	 * @param f_persistentdocument_PersistentDocument $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
	{
		throw new IllegalOperationException('This document cannot be duplicated.');
	}

	/**
	 * this method is call after saving the duplicate document.
	 * $newDocument has an id affected.
	 * Traitment of the children of $originalDocument.
	 *
	 * @param f_persistentdocument_PersistentDocument $newDocument
	 * @param f_persistentdocument_PersistentDocument $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
	{

	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String $destModelName
	 * @param f_persistentdocument_transformer_Transformer $transformer
	 * @return f_persistentdocument_PersistentDocument
	 */
	public final function transform($document, $destModelName, $transformer = null)
	{
		$tm = $this->getTransactionManager();
        $pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();
			$destDocument = $pp->getNewDocumentInstance($destModelName);
			if ($transformer === null)
			{
				$transfomer = Transformers::getInstance($document->getPersistentModel(), $destDocument->getPersistentModel());
				$transfomer->transform($document, $destDocument);
			}
			$pp->mutate($document, $destDocument);
			$tm->commit();
			return $destDocument;
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
		return null;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return Integer or -1 if $document is not a PersistentDocument
	 */
	public final function countReferences($document)
	{
		if ($document instanceof f_persistentdocument_PersistentDocument)
		{
			return count($this->getPersistentProvider()->getChildRelationBySlaveDocumentId($document->getId()));
		}
		return -1;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return integer | null
	 */
	public function getWebsiteId($document)
	{
		if (!$document->getTreeId())
		{
			if ($document->getPersistentModel()->useCorrection() && $document->getCorrectionofid())
			{
				$master = DocumentHelper::getDocumentInstance($document->getCorrectionofid(), $document->getDocumentModelName());
				return $this->getWebsiteId($master);
			}
			else
			{
				return null;
			}
		}

		if ($document->getTreeId() && $document->getTreeId() == ModuleService::getInstance()->getRootFolderId('website'))
		{
			$row = $this->getPersistentProvider()->createQuery('modules_website/website')
				->add(Restrictions::ancestorOf($document->getId()))
				->setProjection(Projections::property('id', 'id'))->find();
			return count($row) == 1 ? $row[0]['id'] : null;
		}
		return null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return integer[] | null
	 */
	public function getWebsiteIds($document)
	{
		$websiteId = $this->getWebsiteId($document);
		if ($websiteId !== null)
		{
			return array($websiteId);
		}
		return null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return website_persistentdocument_page or null
	 */
	public function getDisplayPage($document)
	{
		//Check for original document;
		$document = DocumentHelper::getByCorrection($document);
		$model = $document->getPersistentModel();
		if ($model->hasURL() && $document->isPublished())
		{
			return TagService::getInstance()->getDetailPageForDocument($document);
		}
		return null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param change_Request $request
	 * @return array($module, $action)
	 */
	public function getResolveDetail($document, $request)
	{
		$page = $this->getDisplayPage($document);
		if ($page instanceof website_persistentdocument_page)
		{
			foreach ($document->getPersistentModel()->getAncestorModelNames() as $modelName)
			{
				$parts = f_persistentdocument_PersistentDocumentModel::getModelInfo($modelName);
				$moduleName = $parts['module'];
				if (!$request->hasModuleParameter($moduleName, 'cmpref'))
				{
					$request->setModuleParameter($moduleName, 'cmpref', $document->getId());
				}
			}
		
			// Set pageref parameter into the request.
			$request->setParameter('pageref', $page->getId());
			return array('website', 'Display');
		}
		return array('website', 'Error404');
	}
	
	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
	{
		/**
		 * Compatibility check
		 */
		if (f_util_ClassUtils::methodExists($this, 'generateUrl'))
		{
			$url = $this->generateUrl($document, $lang, $parameters);
			if ($url)
			{
				return LinkHelper::buildLinkFromUrl($url);
			}
		}
		return null;
	}
	
	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return string || null
	 */
	public function generateRewritePath($urlRewritingService, $document, $website, $lang, $parameters)
	{
		return $urlRewritingService->getDocumentRulePath($document, $website, $lang, $parameters);
	}
	

	
	/**
	 * @param Order $order
	 * @return f_persistentdocument_PersistentDocument
	 */
	function getPublished($order = null)
	{
		$query = $this->createQuery()->add(Restrictions::published());
		if ($order !== null)
		{
			$query->addOrder($order);
		}
		return $query->find();
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $forModuleName
	 * @return array
	 */
	private function getPermissions($document, $forModuleName)
	{
		$ps = change_PermissionService::getInstance();
		$defPointId = $ps->getDefinitionPointForPackage($document->getId(), "modules_" . $forModuleName);
		$allPermissions = $ps->getPermissionsForUserByDefPointNodeId(
		users_UserService::getInstance()->getAutenticatedUser(), $defPointId);
		$permissions = array();
		if (count($allPermissions) === 1 && $allPermissions[0] === change_PermissionService::ALL_PERMISSIONS)
		{
			$permissions[change_PermissionService::ALL_PERMISSIONS] = true;
		}
		else
		{
			$documentName = $document->getPersistentModel()->getDocumentName();
			foreach ($allPermissions as $permission)
			{
				$parts = explode('.', $permission);
				if (count($parts) < 3 || $parts[2] === $documentName)
				{
					$permissions[$parts[1]] = true;
				}
			}
		}
		return $permissions;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $forModuleName
	 * @return array
	 */
	public function getDocumentEditorInfos($document, $forModuleName)
	{
		$model = $document->getPersistentModel();
		$contextlang = RequestContext::getInstance()->getLang();
		$usecontextlang = $document->isLangAvailable($contextlang);
		$infos = array('id' => $document->getId(),
				'model' => $model->getName(),
				'contextlang' => $contextlang,
				'vo' => $document->getLang(),
				'label' => $model->isLocalized() ? $document->getVoLabel() :  $document->getLabel(),
				'revision' => $document->getDocumentversion(),				
				'usecorrection' => $model->useCorrection(),
				'useworkflow' => $model->hasWorkflow(),
				'uselocalization' => $model->isLocalized(),
				'usecontextlang' => $document->isLangAvailable($contextlang),
				'permissions' => $this->getPermissions($document, $forModuleName)
		);
		return $infos;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = array();
		$model = $document->getPersistentModel();
		$rc = RequestContext::getInstance();
		$contextlang = $rc->getLang();
		$usecontextlang = $document->isLangAvailable($contextlang);
		$lang = $usecontextlang ? $contextlang : $document->getLang();	
		try
		{
			$rc->beginI18nWork($lang);
			if ($allowedSections === null || isset($allowedSections['properties']))
			{
				$data['properties'] = array(
				'label' => $document->getLabel(), 
				'author' => $document->getAuthor(),
				'creationdate' => date_Formatter::toDefaultDateTimeBO($document->getUICreationdate()));
			}

			if ($allowedSections === null || isset($allowedSections['publication']))
			{
				$data['publication'] = array(
					'status' => LocaleService::getInstance()->transBO(DocumentHelper::getStatusLocaleKey($document)),
					'workflow' => ''
				);
				
				$status = $document->getPublicationstatus();
				if ($status === "WORKFLOW")
				{
					$info = workflow_WorkitemService::getInstance()->createQuery()
					->setProjection(Projections::groupProperty('label', "label"),
					Projections::groupProperty('creationdate', "creationdate"))
					->add(Restrictions::eq('documentid', $document->getId()))
					->add(Restrictions::published())->find();
					if (count($info) > 0)
					{
						$data['publication']['workflow'] = date_Formatter::toDefaultDateTimeBO(date_Converter::convertDateToLocal($info[0]['creationdate'])) . " : " .$info[0]['label'];
					}
				}
				else if ($status === 'ACTIVE' && $document->hasMeta('ActPubStatInf'.$lang))
				{
					$data['publication']['status'] .= ' (' . $this->getUIActivePublicationStatusInfo($document, $lang) . ')';
				}
			}

			if ($allowedSections === null || isset($allowedSections['localization']))
			{
				if ($model->isLocalized())
				{
					if (isset($data['infos']['correctionofid']))
					{
						$original = $this->getPersistentProvider()->getDocumentInstance($data['infos']['correctionofid']);
						$info = $original->getI18nInfo();
					}
					else
					{
						$info = $document->getI18nInfo();
					}

					$data['localization'] = array(
						'languages' => array('vo' => $info->getVo(), 'langs' => $info->getLangs()),
					);
				}
			}

			if ($allowedSections === null || isset($allowedSections['history']))
			{
				$data['history'] = array(
				'modificationdate' => date_Formatter::toDefaultDateTimeBO($document->getUIModificationdate()));
			}
	
			if (($allowedSections === null && $model->hasURL()) || isset($allowedSections['urlrewriting']))
			{
				try 
				{
					$currenturl = LinkHelper::getDocumentUrl($document);
					$data['urlrewriting'] = array('currenturl' => $currenturl);
				}
				catch (Exception $e)
				{
					$data['urlrewriting'] = array('currenturl' => array('label' => $e->getMessage(), 'href' => ''));
				}
			}
			
			if ($allowedSections === null || isset($allowedSections['tags']))
			{
				$tags = TagService::getInstance()->getTags($document);	
				if (f_util_ArrayUtils::isNotEmpty($tags))
				{
					$tagsLabel = join(", ", $tags);
				}
				else
				{
					$tagsLabel = f_Locale::translateUI('&framework.persistentdocument.general.No-tag;');
				}
				$data['tags'] = array('tagsaction' => array('label' => $tagsLabel, 'disablelock' => 'false'),
									  'tags' => $tagsLabel);
			}

			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}

		return $data;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
	{
		// Nothing done by default.
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string[] $propertiesName
	 * @param array $datas
	 * @param integer $parentId
	 */
	public function addFormProperties($document, $propertiesName, &$datas, $parentId = null)
	{
		// Nothing done by default.
	}
	
	/**
	 * @see f_util_HtmlUtils::renderDocumentLink
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $attributes
	 * @param string $content
	 * @param string $lang
	 * @return string
	 */
	public function getXhtmlFragment($document, $attributes, $content, $lang)
	{
		$attributes['href'] = LinkHelper::getDocumentUrl($document, $lang);
		return f_util_HtmlUtils::buildLink($attributes, $content);
	}
	
	
}