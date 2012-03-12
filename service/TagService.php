<?php
/**
 * @package framework.service
 * TagService
 */
class TagService extends BaseService
{
	/**
	 * the singleton instance
	 * @var TagService
	 */
	private static $instance = null;

	private $tagsInfo;
	
	private $availableTags;

	const TAG_REGEXP = '/^[0-9a-z\-\._]+$/i';

	/**
	 * @return TagService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @param String $pattern
	 * @return Array<String => Array>
	 */
	public function getAllAvailableTags()
	{
		return $this->getAvailableTagsInfo();
	}

	/**
	 * Find tags by pattern containing * wildcards ( eg: contextual_website_website_menu* )
	 *
	 * @param String $pattern
	 * @return array
	 */
	public function getAvailableTagsByPattern($pattern)
	{
		$tags = array();
		$reg = '/^' . str_replace('*', '.*', $pattern) . '$/';
		foreach ($this->getAvailableTags() as $tag) 
		{
			if (preg_match($reg, $tag)) {$tags[] = $tag;}
		}
		return $tags;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array
	 */
	function getAffectedTagsForDocument($document)
	{
		$tags = array();
		$model = $document->getPersistentModel();
		$modelsName = array();
		while (true)
		{
			if ($model->isInjectedModel()) {$model = $model->getSourceInjectionModel();}
			$modelsName[$model->getName()] = true;
			if (!$model->hasParent()) {break;}
			$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($model->getParentName());
		}
		
		$allTags = $this->getPersistentProvider()->getAllTags();
		$results = array();
		foreach ($this->getAvailableTagsInfo() as $tag => $tagInfo) 
		{
			if (isset($modelsName[$tagInfo['component_type']]))
			{
				$results[] = array('tag' => $tag, 'ids' => isset($allTags[$tag]) ? $allTags[$tag] : array());			
			}
		}
		
		$parent = $document->getDocumentService()->getParentOf($document);	
		$websiteId = $document->getDocumentService()->getWebsiteId($document);
		
		$documentId = $document->getId();
		foreach ($results as $result)
		{
			$tag = $result['tag'];
			if (in_array($documentId, $result['ids']))
			{
				$tags[] = array('tag' => $tag, 'affected' => true, 'selfAffected' => true);
			}
			else if (count($result['ids']) > 0)
			{
				if ($this->isExclusiveTag($tag))
				{
					// Exclusive tags does not need context check.
					$tags[] = array('tag' => $tag, 'affected' => true);
				}
				else if ($this->isContextualTag($tag))
				{
					// Check that the id is a descendent of the same website than the given document.
					if ($websiteId)
					{
						foreach ($result['ids'] as $taggedDocumentId) 
						{
							$taggedDocument = DocumentHelper::getDocumentInstance($taggedDocumentId);
							$taggedWebsiteId = $taggedDocument->getDocumentService()->getWebsiteId($taggedDocument);			
							if ($taggedWebsiteId == $websiteId)
							{
								$tags[] = array('tag' => $tag, 'affected' => true);
								break;
							}
						}
					}
				}
				else if ($this->isFunctionalTag($tag))
				{
					// Check that the id and the given document has the same parent.
					if (isset($parent))
					{
						foreach ($result['ids'] as $taggedDocumentId) 
						{
							$taggedDocument = DocumentHelper::getDocumentInstance($taggedDocumentId);
							$taggedDocumentParent = $taggedDocument->getDocumentService()->getParentOf($taggedDocument);
							if (DocumentHelper::equals($parent, $taggedDocumentParent))
							{
								$tags[] = array('tag' => $tag, 'affected' => true);
								break;
							}							
						}
					}
				}
				else
				{
					// FIXME: What to do for free tags?
				}
			}
		}

		return array('models' => $modelsName, 'tags' => $tags) ;
	}

	/**
	 * This method is used internally to check if a tag is valid or not.
	 * @param string $tag The tag to check.
	 * @return boolean true if $tag is a valid tag, false otherwise.
	 */
	public function isValidTag($tag)
	{
		return ( is_numeric($tag) || ( is_string($tag) && preg_match(self::TAG_REGEXP, $tag) ) );
	}


	/**
	 * This method is used internally to check if a tag is exclusive (can appear
	 * only once in all the tags).
	 * @param string $tag The tag to check.
	 * @return boolean true if $tag is an exclusive tag or false otherwise.
	 */
	public function isExclusiveTag($tag)
	{
		return $this->isValidTag($tag) && substr($tag, 0, 8) === 'default_';
	}

	/**
	 * This method is used internally to check if a tag is functional (can appear
	 * only once in children document).
	 * @param string $tag
	 * @return boolean
	 */
	public function isFunctionalTag($tag)
	{
		return preg_match('/^functional_[a-z0-9]+_[a-z0-9]+\-[a-z0-9]+$/', $tag) ? true : false;
	}

	/**
	 * @param String $tag
	 * @param String $moduleName
	 * @param String $documentName
	 * @param String $functionName
	 */
	private function getFunctionalTagInfo($tag, &$moduleName = null, &$documentName = null, &$functionName = null)
	{
		if (preg_match('/^functional_([a-z0-9]+)_([a-z0-9]+)\-([a-z0-9]+)$/', $tag, $matches))
		{
			$moduleName = $matches[1];
			$documentName = $matches[2];
			$functionName = $matches[3];
		}
	}

	/**
	 * This method is used internally to check if a tag is contextual (can appear
	 * only once in its context - a parent document).
	 * @param string $tag The tag to check.
	 * @return boolean true if $tag is a contextual tag or false otherwise.
	 */
	public function isContextualTag($tag)
	{
		return preg_match('/^contextual_[a-z0-9]+_[a-z0-9]+_[a-z0-9\-_]+$/', $tag) ? true : false;
	}

	/**
	 * This method is used to determine the context of a contextual tag.
	 * @param string $tag The tag to check.
	 * @return string or null
	 */
	public function getTagContext($tag)
	{
		$context = null;
		if ($this->isContextualTag($tag) )
		{
			$p1 = strpos($tag, '_') + 1;
			$p2 = strpos($tag, '_', $p1+1) + 1;
			$p3 = strpos($tag, '_', $p2+1) + 1;
			$module = substr($tag, $p1, $p2-$p1-1);
			$document = substr($tag, $p2, $p3-$p2-1);
			if ($module != 'framework')
			{
				$module = 'modules_'.$module;
			}
			$context = $module.'/'.$document;
		}
		return $context;
	}


	/**
	 * Gets all the tags of $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The documentId to get the tags of.
	 * @return String[] Array with all the tags.
	 *
	 * @throws IllegalArgumentException
	 *
	 * @author  INTbonjF
	 * @date    2007-01-29
	 */
	public function getTags($document)
	{
		if (!$document->hasMeta("f_tags"))
		{
			return array();
		}
		return $document->getMetaMultiple("f_tags");
	}

	/**
	 * Gets all the object_TagObjects of $document.
	 * @param f_persistentdocument_PersistentDocument $document The document to get the tags of.
	 * @return array<object_TagObjects> Array with all the object_TagObjects.
	 */
	public function getTagObjects($document)
	{
		$tags = array();
		$tagNames = $this->getPersistentProvider()->getTags($document->getId());
		if (count($tagNames) > 0)
		{
			foreach ($tagNames as $tagName)
			{
				$to = $this->getTagObject($tagName);
				if ($to !== null)
				{
					$tags[] = $to;
				}
			}
		}
		return $tags;
	}
	
	private function getTagObject($tagName)
	{
		$avaiableTags = $this->getAvailableTagsInfo();
		if (isset($avaiableTags[$tagName]))
		{
			$tagInfo = $avaiableTags[$tagName];
			return new object_TagObject($tagName, $tagInfo['label'], $tagInfo['icon'], $tagInfo['package']);
		}		
		return null;
	}

	/**
	 * Checks wether the document has the specified tag. Returns true if the
	 * document has the tag, otherwise returns false.
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function hasTag($document, $tag)
	{
		return $document->hasMetaValue("f_tags", $tag);
	}

	/**
	 * Returns true if $document has ALL the listed tags in the specified array $tags.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which the tags are checked.
	 * @param array $tags Array of the tags to check.
	 *
	 * @return boolean true if the document has ALL the listed tags.
	 */
	public function hasTags($document, $tags)
	{
		if (!$document->hasMeta("f_tags")) {return false;}
		$affectedTags = $document->getMetaMultiple("f_tags");
		foreach ($tags as $tag)
		{
			if (array_search($tag, $affectedTags) === false)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns true if $document has some of the listed tags in the specified array $tags.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which the tags are checked.
	 * @param array $tags Array of the tags to check.
	 *
	 * @return boolean true if the document has some of the listed tags.
	 */
	public function hasSomeTags($document, $tags)
	{
		if (!$document->hasMeta("f_tags")) {return false;}
		$affectedTags = $document->getMetaMultiple("f_tags");
		foreach ($tags as $tag)
		{
			if (array_search($tag, $affectedTags) !== false) {return true;}
		}
		return false;
	}


	/**
	 * Adds the tag $tag to the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which to add the tag.
	 * @param string $tag The tag to add.
	 * @param boolean $auto
	 *
	 * @throws InvalidTagException
	 * @throws IllegalArgumentException
	 *
	 * @author  INTbonjF
	 * @date    2007-01-25
	 */
	public function addTag($document, $tag, $auto = true)
	{
		if (!$this->isValidTag($tag))
		{
			throw new InvalidTagException($tag);
		}
		if (!$document->isNew() && $this->hasTag($document, $tag))
		{
			return;
		}

		if ($this->isContextualTag($tag))
		{
			$this->setContextualTag($document, $tag, $auto);
		}
		else if ($this->isExclusiveTag($tag))
		{
			$this->setExclusiveTag($document, $tag);
		}
		else if ($this->isFunctionalTag($tag))
		{
			$this->setFunctionalTag($document, $tag);
		}
		else
		{
			$this->setTag($document, $tag);
		}
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
		if (!$this->isFunctionalTag($tag))
		{
			throw new InvalidFunctionalTagException($tag);
		}

		$oldTagedDocuments = $this->getSiblingDocuments($tag, $document);
		if (count($oldTagedDocuments) == 0)
		{
			$this->setTag($document, $tag);
		}
		else if (count($oldTagedDocuments) > 1)
		{
			throw new TagException('Found more than one document with a functional tag in the same parent. Tag="'.$tag.'", Sibling document="'.$document->getId().'".');
		}
		else if ($document->getId() != $oldTagedDocuments[0]->getId())
		{
			$this->moveTag($oldTagedDocuments[0], $document, $tag);
		}

	}

	/**
	 * Sets the exclusive tag $tag on the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which to set the exclusive tag.
	 * @param string $tag The exclusive tag to set.
	 *
	 * @throws InvalidExclusiveTagException
	 * @throws IllegalArgumentException
	 *
	 * @author  INTbonjF
	 * @date    2007-01-25
	 */
	public function setExclusiveTag($document, $tag)
	{
		if (!$this->isExclusiveTag($tag))
		{
			throw new InvalidExclusiveTagException($tag);
		}
		$oldTagedDocument = $this->getDocumentByExclusiveTag($tag, false);
		if ($oldTagedDocument !== null && $document->getId() != $oldTagedDocument->getId())
		{
			$this->moveTag($oldTagedDocument, $document, $tag);
		}
		else
		{
			$this->setTag($document, $tag);
		}
	}

	/**
	 * Adds the contextual tag $tag to the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document on which to set the contextual tag.
	 * @param string $tag The contextual tag to set.
	 * @param Boolean $removeExisting
	 *
	 * @throws InvalidContextualTagException
	 * @throws TagException
	 * @throws IllegalArgumentException
	 * @return Boolean true if the tag was setted (can be false if $removeExisting equals false)
	 */
	public function setContextualTag($document, $tag, $removeExisting = true)
	{
		if (!$this->isContextualTag($tag))
		{
			throw new InvalidContextualTagException($tag);
		}

		// invalid cache
		$this->getDocumentByContextualTagCache = array();

		$contextDocumentId = $this->getContextualDocumentIdByTag($document->getId(), $tag);

		//search for a descendant that already has the contextual tag
		// TODO: change query adding Restrictions::ne("id", $document->getId()) ?
		$query = $this->getPersistentProvider()->createQuery();
		$query->add(Restrictions::descendentOf($contextDocumentId));
		$query->add(Restrictions::hasTag($tag));
		$docArray = $this->getPersistentProvider()->find($query);

		$docArrayCount = count($docArray);
		if ($docArrayCount == 0)
		{
			$this->setTag($document, $tag);
		}
		else if ($docArrayCount > 1)
		{
			throw new TagException('Found more than one document with a contextual tag in the same context. Tag="'.$tag.'", Context document="'.$contextDocumentId.'", Document="'.$document->getId().'".');
		}
		else if ($removeExisting && $docArray[0]->getId() != $document->getId())
		{
			$this->moveTag($docArray[0], $document, $tag);
		}
		else
		{
			return false;
		}
		return true;
	}

	/**
	 * @param Integer $documentId
	 * @param String $contextualTag
	 * @return Integer
	 */
	public function getContextualDocumentIdByTag($documentId, $contextualTag)
	{
		$tagContext = $this->getTagContext($contextualTag);
			
		// get the ancestors from the farthest to the nearest
		$ancestors = $this->getAncestorsOf($documentId, $tagContext);
		if ( empty($ancestors) )
		{
			throw new TagException('Could not set contextual tag "'.$contextualTag.'" to "'.$documentId.'" because it is not a descendant of a "'.$tagContext.'".');
		}
		return $ancestors[0];
	}

	/**
	 * @param Integer $documentId
	 * @param string $modelName Restrict to model $modelName.
	 *
	 * @return array<Integer>
	 */
	private function getAncestorsOf($documentId, $modelName)
	{
		$docs = array();
		$treeNode = TreeService::getInstance()->getInstanceByDocumentId($documentId);
		if (!is_null($treeNode))
		{
			foreach ($treeNode->getAncestors() as $ancestor)
			{
				if ( is_null($modelName) || $ancestor->getDocumentModelName() == $modelName)
				{
					$docs[] = $ancestor->getId();
				}
			}
		}
		return $docs;
	}

	/**
	 * Removes the tag $tag from the given $documentId.
	 * @param f_persistentdocument_PersistentDocument $document The document from which to remove the tag.
	 * @param string $tag The tag to remove.
	 *
	 * @throws InvalidTagException
	 * @throws IllegalArgumentException
	 *
	 * @author  INTbonjF
	 * @date    2007-01-29
	 */
	public function removeTag($document, $tag)
	{
		if (!$this->isValidTag($tag))
		{
			throw new InvalidTagException($tag);
		}

		if ($this->hasTag($document, $tag))
		{
			$tm = f_persistentdocument_TransactionManager::getInstance();
			try
			{
				$tm->beginTransaction();
				$this->getPersistentProvider()->removeTag($document->getId(), $tag);
				$document->removeMetaValue("f_tags", $tag);
				$document->saveMeta();
				$document->getDocumentService()->tagRemoved($document, $tag);
				$tm->commit();
			}
			catch (Exception $e)
			{
				$tm->rollBack($e);
			}
			f_event_EventManager::dispatchEvent("tagRemoved", $this, array("document" => $document, "tag" => $tag));
		}
	}

	/**
	 * Set the tag in database.
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $tag
	 */
	private function setTag($document, $tag)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
			$this->getPersistentProvider()->addTag($document->getId(), $tag);
			$document->addMetaValue("f_tags", $tag);
			$document->saveMeta();
			$document->getDocumentService()->tagAdded($document, $tag);
			$tm->commit();
			$ok = true;
		}
		catch (Exception $e)
		{
			$ok = false;
			$tm->rollBack($e);
		}
		if ($ok)
		{
			f_event_EventManager::dispatchEvent("tagAdded", $this, array("document" => $document, "tag" => $tag));
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 */
	private function moveTag($fromDocument, $toDocument, $tag)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
			$this->getPersistentProvider()->removeTag($fromDocument->getId(), $tag);
			$fromDocument->removeMetaValue("f_tags", $tag);
			$fromDocument->saveMeta();
			$fromDocument->getDocumentService()->tagMovedFrom($fromDocument, $toDocument, $tag);

			$this->getPersistentProvider()->addTag($toDocument->getId(), $tag);
			$toDocument->addMetaValue("f_tags", $tag);
			$toDocument->saveMeta();
			$toDocument->getDocumentService()->tagMovedTo($fromDocument, $toDocument, $tag);
			$tm->commit();
			$ok = true;
		}
		catch (Exception $e)
		{
			$ok = false;
			$tm->rollBack($e);
		}
		if ($ok)
		{
			f_event_EventManager::dispatchEvent("tagMoved", $this, array("fromDocument" => $fromDocument, "toDocument" => $toDocument, "tag" => $tag));
		}
	}

	public function isDetailPageTag($tag, &$moduleName = null, &$documentName = null)
	{
		if (preg_match('/^contextual_[a-z0-9]+_[a-z0-9]+_modules_([a-z0-9]+)_page\-detail$/', $tag, $matches))
		{
			$moduleName = $matches[1];
			$documentName = null;
			return true;
		}

		$functionName = null;
		$this->getFunctionalTagInfo($tag, $moduleName, $documentName, $functionName);
		if ($functionName == 'detail')
		{
			return true;
		}

		$moduleName = null;
		$documentName = null;
		return false;
	}

	public function isListPageTag($tag, &$moduleName = null, &$documentName = null)
	{
		if (preg_match('/^contextual_[a-z0-9]+_[a-z0-9]+_modules_([a-z0-9]+)_page\-list$/', $tag, $matches))
		{
			$moduleName = $matches[1];
			$documentName = null;
			return true;
		}

		$functionName = null;
		$this->getFunctionalTagInfo($tag, $moduleName, $documentName, $functionName);
		if ($functionName == 'list')
		{
			return true;
		}

		$moduleName = null;
		$documentName = null;
		return false;
	}

	public function isArchivePageTag($tag, &$moduleName = null, &$documentName = null)
	{
		$functionName = null;
		$this->getFunctionalTagInfo($tag, $moduleName, $documentName, $functionName);
		if ($functionName == 'archive')
		{
			return true;
		}

		$moduleName = null;
		$documentName = null;
		return false;
	}


	/**
	 * @param string $tag
	 * @param boolean $throwIfNotFound
	 * @return f_persistentdocument_PersistentDocument
	 *
	 * @throws InvalidExclusiveTagException If tag name is not valid
	 * @throws TagException  If not or more one document founded
	 */
	public function getDocumentByExclusiveTag($tag, $throwIfNotFound = true)
	{
		if (!$this->isExclusiveTag($tag))
		{
			throw new InvalidExclusiveTagException($tag);
		}
		$doc = $this->findDocumentByExclusiveTag($tag);
		if ($doc === null && $throwIfNotFound)
		{
			throw new TagException('No document has the requested tag: '.$tag);
		}
		return $doc;
	}


	/**
	 * @param string $tag
	 * @return boolean
	 */
	public function hasDocumentByExclusiveTag($tag)
	{
		if (!$this->isExclusiveTag($tag))
		{
			return false;
		}
		$doc = $this->findDocumentByExclusiveTag($tag);
		return ($doc !== null);
	}
	
	/**
	 * @param string $tag The exclusive tag.
	 * @return f_persistentdocument_PersistentDocument
	 */
	private function findDocumentByExclusiveTag($tag)
	{
		$ids = $this->getPersistentProvider()->getDocumentIdsByTag($tag);
		if (count($ids) === 1)
		{
			return DocumentHelper::getDocumentInstance($ids[0]);
		}
		return null;
	}
	
	/**
	 * Returns the unique Document that has the given contextual $tag.
	 *
	 * @param string $tag
	 * @param f_persistentdocument_PersistentDocument $contextDocument
	 * @param boolean $throwIfNotFound
	 * @return f_persistentdocument_PersistentDocument
	 *
	 * @throws InvalidContextualTagException
	 * @throws TagException
	 */
	public function getDocumentByContextualTag($tag, $contextDocument, $throwIfNotFound = true)
	{
		if (!($contextDocument instanceof f_persistentdocument_PersistentDocument))
		{
			throw new TagException('Invalid contextDocument');
		}
		
		if (!$this->isContextualTag($tag))
		{
			throw new InvalidContextualTagException($tag);
		}
		$doc = $this->findDocumentByContextualTag($tag, $contextDocument);
		if ($doc === null && $throwIfNotFound)
		{
			throw new TagException('No document has the requested tag: '.$tag);
		}
		return $doc;
	}

	/**
	 * @param string $tag
	 * @param f_persistentdocument_PersistentDocument $contextDocument
	 * @return boolean
	 */
	public function hasDocumentByContextualTag($tag, $contextDocument)
	{
		if (!($contextDocument instanceof f_persistentdocument_PersistentDocument) || !$this->isContextualTag($tag))  
		{
			return false;
		}
		$doc = $this->findDocumentByContextualTag($tag, $contextDocument);
		return ($doc !== null);
	}	
	
	/**
	 * @param string $tag
	 * @param f_persistentdocument_PersistentDocument $contextDocument
	 * @return f_persistentdocument_PersistentDocument || null
	 */
	private function findDocumentByContextualTag($tag, $contextDocument)
	{
		$cacheKey = $tag."|".$contextDocument->getId();
		if (isset($this->getDocumentByContextualTagCache[$cacheKey]))
		{
			return $this->getDocumentByContextualTagCache[$cacheKey];
		}

		$query = $this->getPersistentProvider()->createQuery();
		$query->add(Restrictions::descendentOf($contextDocument->getId()));
		$query->add(Restrictions::hasTag($tag));
		$docArray = $query->find($query);
		$docArrayCount = count($docArray);
		if ($docArrayCount == 1)
		{
			$this->getDocumentByContextualTagCache[$cacheKey] = $docArray[0];
			return $docArray[0];
		}
		else if ($docArrayCount > 1)
		{
			throw new TagException('Found more than one document with a contextual tag in the same context. Tag="'.$tag.'", Context document="'.$contextDocument->__toString().'".');
		}
		return null;
	}

	
	/**
	 * @param string $tag The exclusive tag.
	 * @param f_persistentdocument_PersistentDocument $contextDocument Parent contextual document
	 * @return f_persistentdocument_PersistentDocument
	 */
	private function getContextualDocument($tag, $contextDocument)
	{
		$cacheKey = $tag."|".$contextDocument->getId();
		if (isset($this->getDocumentByContextualTagCache[$cacheKey]))
		{
			return $this->getDocumentByContextualTagCache[$cacheKey];
		}
		
		$query = $this->getPersistentProvider()->createQuery();
		$query->add(Restrictions::descendentOf($contextDocument->getId()));
		$query->add(Restrictions::hasTag($tag));
		$docArray = $query->find($query);
		if (count($docArray) == 1)
		{
			$this->getDocumentByContextualTagCache[$cacheKey] = $docArray[0];
			return $docArray[0];
		}
		return null;	
	}

	private $getDocumentByContextualTagCache = array();

	/**
	 * Returns the unique sibling Document that has the given $tag.
	 * @param String $tag
	 * @param f_persistentdocument_PersistentDocument $siblingDocument
	 * @param boolean $throwIfNotFound
	 * @return f_persistentdocument_PersistentDocument
	 *
	 * @throws InvalidTagException If tag name is not valid
	 * @throws TagException If not or more one document founded
	 */
	public function getDocumentBySiblingTag($tag, $siblingDocument, $throwIfNotFound = true)
	{
		if (!($siblingDocument instanceof f_persistentdocument_PersistentDocument))
		{
			throw new TagException('Invalid siblingDocument');
		}
		
		if (!$this->isValidTag($tag))
		{
			throw new InvalidTagException($tag);
		}
		$doc = $this->findDocumentBySiblingTag($tag, $siblingDocument);
		if ($doc === null && $throwIfNotFound)
		{
			throw new TagException('No document has the requested tag: '.$tag);
		}
		return $doc;
	}
	
	/**
	 * @param String $tag
	 * @param f_persistentdocument_PersistentDocument $siblingDocument
	 * @return boolean
	 */
	public function hasDocumentBySiblingTag($tag, $siblingDocument)
	{
		if (!($siblingDocument instanceof f_persistentdocument_PersistentDocument))
		{
			return false;
		}
		if (!$this->isValidTag($tag))
		{
			return false;
		}
		
		$doc = $this->findDocumentBySiblingTag($tag, $siblingDocument);
		return ($doc !== null);
	}	
	
	/**
	 * @param String $tag
	 * @param f_persistentdocument_PersistentDocument $siblingDocument
	 * @return f_persistentdocument_PersistentDocument
	 */
	private function findDocumentBySiblingTag($tag, $siblingDocument)
	{
		$docArray = $this->getSiblingDocuments($tag, $siblingDocument);
		$docArrayCount = count($docArray);
		if ($docArrayCount == 1)
		{
			return $docArray[0];
		}
		else if ($docArrayCount > 1)
		{
			throw new TagException('Found more than one sibling document with a tag. Tag="'.$tag.'", Context document="'.$siblingDocument->__toString().'".');
		}

		return null;
	}

	/**
	 * @param String $tag
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	private function getSiblingDocuments($tag, $document)
	{
		$query = $this->getPersistentProvider()->createQuery();
		$query->add(Restrictions::siblingOf($document->getId()));
		$query->add(Restrictions::hasTag($tag));
		return $query->find($query);
	}

	/**
	 * Returns the Documents that have the given $tag.
	 *
	 * @param string $tag The tag.
	 * @return array<f_persistentdocument_PersistentDocument>
	 *
	 * @throws InvalidTagException If tag name is not valid
	 */
	public function getDocumentsByTag($tag)
	{
		if (!$this->isValidTag($tag))
		{
			throw new InvalidTagException($tag);
		}
		$ids = $this->getPersistentProvider()->getDocumentIdsByTag($tag);
		$docs = array();
		foreach ($ids as $id)
		{
			$docs[] = DocumentHelper::getDocumentInstance($id);
		}
		return $docs;
	}


	public function regenerateTags()
	{
		$this->tagsInfo = array();
		
		// Processing modules :
		$this->processModules();

		// Put locales in database :
		$this->saveAvailableTags();
	}
	
	private function saveAvailableTags()
	{
		// Clear table :	
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('availabletagsInfo.php');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize($this->tagsInfo), f_util_FileUtils::OVERRIDE);
		
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('availabletags.php');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize(array_keys($this->tagsInfo)), f_util_FileUtils::OVERRIDE);
	}
	
	/**
	 * @return array<tag>
	 */
	private function getAvailableTags()
	{
		if ($this->availableTags === null)
		{
			$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('availabletags.php');
			$this->availableTags = unserialize(f_util_FileUtils::read($compiledFilePath));
		}
		return $this->availableTags;
	}
	
	/**
	 * @return array<tag => array<tag, icon, label, labeli18n, package, component_type>>
	 */
	private function getAvailableTagsInfo()
	{
		if ($this->tagsInfo === null)
		{
			$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('availabletagsInfo.php');
			$this->tagsInfo = unserialize(f_util_FileUtils::read($compiledFilePath));
		}
		return $this->tagsInfo;
	}
	
	/**
	 * @var array
	 */
	private $tagsInfosByModel;
	
	/**
	 * @var array
	 */
	private $compatibleTagsInfosByModel;
	
	/**
	 * @param string $modelName
	 */
	public function getAvailableTagsInfoByModel($modelName)
	{
		if (!isset($this->compatibleTagsInfosByModel[$modelName]))
		{
			if ($this->tagsInfosByModel === null)
			{
				$this->tagsInfosByModel = array();
				foreach (TagService::getInstance()->getAllAvailableTags() as $tagName => $tagInfos)
				{
					$this->tagsInfosByModel[$tagInfos['component_type']][$tagName] = $tagInfos;
				}
			}
			
			$tagsInfos = array();
			$modelNames = f_persistentdocument_PersistentDocumentModel::getModelChildrenNames('modules_website/menu');
			$modelNames[] = 'modules_website/menu';
			foreach ($modelNames as $modelName)
			{
				if (isset($this->tagsInfosByModel[$modelName]))
				{
					$tagsInfos = array_merge($tagsInfos, $this->tagsInfosByModel[$modelName]);
				}
			}
			$this->compatibleTagsInfosByModel[$modelName] = $tagsInfos;
		}
		return $this->compatibleTagsInfosByModel[$modelName];
	}
	
	private function processModules()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("[CompileTags] processModules BEGIN");
		}

		$modulesArray = ModuleService::getInstance()->getModules();
		foreach ($modulesArray as $moduleName)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("**** Processing module ".$moduleName." ****");
			}
			$dirsToProcess = array();
			$configPaths = FileResolver::getInstance()->setPackageName($moduleName)->setDirectory('config')->getPaths('');
					
			// intbonjf 2007-06-01:
			// fixing #194: process the files in the modules THEN in the webapp.
			$configPaths = array_reverse($configPaths);

			$count = count($configPaths);
			foreach ($configPaths as $configPath)
			{
				// intbonjf 2007-01-10:
				// We must check if there are tags definition files in each path
				// to be sure to use the right ones.
				// Here is the rule I will implement now:
				// If there are 3 "config" dirs for a module (PEAR package, modules/ and webapp/modules)
				// that contain tags definition files, the ones in the packaged modules are
				// not taken into consideration.

				// Search for tags definition file in each "config" directory...
				$found = false;
				$dh = opendir($configPath);
				if ($dh)
				{
					while (($file = readdir($dh)) !== false)
					{
						if (f_util_StringUtils::endsWith($file, 'tags.xml'))
						{
							if ( Framework::isDebugEnabled())
							{
								Framework::debug(" - FOUND tags definition files here (used): ".$configPath);
							}
							// Tags are found in this directory: we must process it!
							$dirsToProcess[] = $configPath;
							$found = true;
							break;
						}
					}
				}
				if (! $found && Framework::isDebugEnabled())
				{
					Framework::debug(" - NO tags definition files here (skipped): ".$configPath);
				}
				closedir($dh);

			}

			$count = count($dirsToProcess);
			foreach ($dirsToProcess as &$dir)
			{
				// If there are 3 directories containing tags definition files,
				// skip the directory of the packaged module.
				if ( ! ($count == 3 && f_util_StringUtils :: beginsWith($dir, PEAR_DIR) ) )
				{
					if (Framework::isDebugEnabled())
					{
						Framework::debug(" - processing directory ".$dir);
					}
					$this->processDir($moduleName, $dir);
				}
				else if (Framework::isDebugEnabled())
				{
					Framework::debug(" - skipping directory ".$dir);
				}

			}
		}

		if (Framework::isDebugEnabled())
		{
			Framework::debug("See generic/CompileTagsAction.class.php for more details about skipped directories.");
			Framework::debug("[CompileTags] processModules END");
		}
	}

	private function processDir($package, $dir)
	{
		if (is_dir($dir))
		{
			$dh = opendir($dir);
			if ($dh)
			{
				while (($file = readdir($dh)) !== false)
				{
					if (f_util_StringUtils::endsWith($file, '.tags.xml'))
					{
						$this->processFile($package, $dir . DIRECTORY_SEPARATOR . $file);
					}
				}
				closedir($dh);
			}
		}
	}

	private function processFile($package, $file)
	{
		$parts = explode(".", basename($file));
		switch (count($parts))
		{
			case 3:
				$componentType = $package."/".$parts[0];
				break;
			case 4:
				$componentType = $parts[0]."/".$parts[1];
				break;
			default:
				return;
		}

		$xml = new DOMDocument();
		$xml->load($file);
		$tags = $xml->getElementsByTagName('tag');
		foreach ($tags as $tag)
		{
			$this->tagsInfo[$tag->textContent] = array(
				'tag' => $tag->textContent,
				'package' => $package,
				'component_type' => $componentType,
				'icon' => $tag->getAttribute('icon'),
				'label' => $tag->getAttribute('label'),
				'labeli18n' => $tag->getAttribute('labeli18n')
			);
		}
	}

	/**
	 * Returns the detail page for the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return website_persistentdocument_page or null
	 */
	public function getDetailPageForDocument($document)
	{
		$detailPage = $this->getDetailPageByModel($document->getDocumentModelName(), $document);
		if ($detailPage)
		{
			return $detailPage;
		}
		foreach ($document->getPersistentModel()->getAncestorModelNames() as $modelName)
		{
			$detailPage = $this->getDetailPageByModel($modelName, $document);
			if ($detailPage) {return $detailPage;}
		}
		return null;
	}
	
	/**
	 * @param $modelName
	 * @param document
	 */
	private function getDetailPageByModel($modelName, $document)
	{
		$parts = f_persistentdocument_PersistentDocumentModel::getModelInfo($modelName);
		$moduleName = $parts['module'];
		$documentName = $parts['document'];
						
		$tags = $this->getAvailableTags();
		$tag = 'functional_' . $moduleName . '_' . $documentName .'-detail';
		
		if (in_array($tag, $tags))
		{
			$docArray = $this->getSiblingDocuments($tag, $document);
			if (count($docArray) > 0)
			{
				return $docArray[0];
			}
		}
		
		$websiteId = $document->getDocumentService()->getWebsiteId($document);
		if ($websiteId)
		{
			$website = DocumentHelper::getDocumentInstance($websiteId, 'modules_website/website');
		}
		else
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			if ($website->isNew())
			{
				Framework::warn(__METHOD__ . ' ' .$document->__toString() . ' has no WebsiteId');
				return null;
			}
		}
		
		$tag = 'contextual_website_website_modules_' . $moduleName . '_'.$documentName;
		if (in_array($tag, $tags))
		{
			return $this->getContextualDocument($tag, $website);	
		}
		
		$tag = 'contextual_website_website_modules_' . $moduleName . '_page-detail';	
		if (in_array($tag, $tags))
		{
			return $this->getContextualDocument($tag, $website);	
		}
		return null;
	}
	
	/**
	 * Returns the list page for the given $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return website_persistentdocument_page
	 */
	public function getListPageForDocument($document)
	{
		if (!ModuleService::getInstance()->isInstalled('website'))
		{
			throw new Exception("Module website is required.");
		}
		
		//Check for original document;
		$document = DocumentHelper::getByCorrection($document);
		
		$model = $document->getPersistentModel();
		$moduleName = $model->getModuleName();
		$documentName = $model->getDocumentName();
						
		if ($model->isInjectedModel())
		{
			$documentName = $model->getOriginalDocumentName();
			$moduleName = $model->getOriginalModuleName();
		}

		$tags = $this->getAvailableTags();
		$tag = 'functional_' . $moduleName . '_' . $documentName .'-list';
		if (in_array($tag, $tags))
		{
			$docArray = $this->getSiblingDocuments($tag, $document);
			if (count($docArray) > 0)
			{
				return $docArray[0];
			}			
		}

		$websiteId = $document->getDocumentService()->getWebsiteId($document);
		if ($websiteId)
		{
			$website = DocumentHelper::getDocumentInstance($websiteId, 'modules_website/website');
		}
		else
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			if ($website->isNew())
			{
				Framework::warn(__METHOD__ . ' ' .$document->__toString() . ' has no WebsiteId');
				return null;
			}
		}
		
		$tag = 'contextual_website_website_modules_' . $moduleName . '_page-list';
		if (in_array($tag, $tags))
		{
			return $this->getContextualDocument($tag, $website);
		}
		
		return null;
	}
}