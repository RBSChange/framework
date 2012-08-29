<?php
/**
 * @method indexer_IndexService getInstance()
 */
class indexer_IndexService extends change_BaseService 
{
	const PUBLIC_DOCUMENT_ACCESSOR_ID = 0;
	
	const MAX_QUEUE_LENGTH = 100;
	
	const INDEXER_MODE_BACKOFFICE = 0;
	
	const INDEXER_MODE_FRONTOFFICE = 1;
	
	const TO_INDEX = 'TO_INDEX';
	
	const INDEXED = 'INDEXED';
	
	const DELETED = 'DELETED';
		
	/**
	 * @var array
	 */
	private $modelsInfos;
	
	/**
	 * @var array
	 */
	private $documentIdsToIndex;
	
	protected final function loadModelsInfos()
	{
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('indexableDocumentInfos.ser');
		if (!file_exists($compiledFilePath))
		{
			throw new Exception("File not found : $compiledFilePath. compile-documents needed");
		}
		$this->modelsInfos = unserialize(file_get_contents($compiledFilePath));
	}

	/**
	 * @return string[]
	 */
	public function getBackOfficeModelsName()
	{
		if ($this->modelsInfos === null)
		{
			$this->loadModelsInfos();
		}
		return $this->modelsInfos['bo'];
	}
	
	/**
	 * @return string[]
	 */
	public function getFrontOfficeModelsName()
	{
		if ($this->modelsInfos === null)
		{
			$this->loadModelsInfos();
		}
		return $this->modelsInfos['fo'];
	}

	/**
	 * @return string[]
	 */
	public function getIndexableModelsName()
	{
		if ($this->modelsInfos === null)
		{
			$this->loadModelsInfos();
		}
		if (!isset($this->modelsInfos['all']))
		{
			$this->modelsInfos['all'] = array_unique(array_merge($this->modelsInfos['fo'], $this->modelsInfos['bo']));
		}
		return $this->modelsInfos['all'];
	}	

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
	public final function isIndexingOperationPossible($document)
	{
		if ($document instanceof f_persistentdocument_PersistentDocument)
		{
			return $this->isModelNameIndexable($document->getDocumentModelName());
		}
		return false;
	}
	
	/**
	 * @param string $modelName
	 * @return boolean
	 */
	public final function isModelNameIndexable($modelName)
	{
		return in_array($modelName, $this->getIndexableModelsName());
	}
	
	/**
	 * @internal used by f_persistentdocument_PersistentProvider
	 */
	public function beginIndexTransaction()
	{
		$this->documentIdsToIndex = array();
	}

	/**
	 * @internal used by f_persistentdocument_PersistentProvider
	 */
	public function commitIndex()
	{
		if (is_array($this->documentIdsToIndex))
		{
			if (count($this->documentIdsToIndex))
			{
				if (Framework::isInfoEnabled())
				{
					Framework::info(__METHOD__ . ' Count To Index: ' . count($this->documentIdsToIndex));
				}
				
				$pp = $this->getPersistentProvider();
				$lastUpdate = date_Calendar::getInstance()->toString();
				foreach ($this->documentIdsToIndex as $id => $status) 
				{
					$pp->setIndexingDocumentStatus($id, $status, $lastUpdate);
				}
			}
			$this->documentIdsToIndex = null;
		}
	}
	
	/**
	 * @internal used by f_persistentdocument_PersistentProvider
	 */	
	public function rollBackIndex()
	{
		$this->documentIdsToIndex = null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function toIndex($document)
	{
		if (!$this->isIndexingOperationPossible($document))
		{
			$documentId =  ($document instanceof f_persistentdocument_PersistentDocument) ? $document->getId() : ''; 
			Framework::warn(__METHOD__ . ' Can not index document ' . $documentId . ', please check your config and document model.');
			return;
		}
		
		$documentId = $document->getId();
		if (is_array($this->documentIdsToIndex)) // If already in transaction
		{
			$this->documentIdsToIndex[$documentId] = self::TO_INDEX;
		}
		else
		{
			$this->getTransactionManager()->beginTransaction();
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' To Index -> ' . $documentId);
			}
			$this->getPersistentProvider()->setIndexingDocumentStatus($documentId, self::TO_INDEX);
			$this->getTransactionManager()->commit();
		}				
	}
		
	public function clearIndex()
	{
		try 
		{
			$this->getTransactionManager()->beginTransaction();
			$this->getPersistentProvider()->clearIndexingDocumentStatus();
			$this->getTransactionManager()->commit();				
		} 
		catch (Exception $e) 
		{
			$this->getTransactionManager()->rollBack($e);
		}
	}
	
	public function optimizeIndex()
	{

	}
	
	/**
	 * @param string $lang
	 */
	public function rebuildSpellCheckIndexForLang($lang)
	{

	}
	
	/**
	 * @param string $modelName
	 * @return f_persistentdocument_criteria_Query
	 */
	protected final function getIndexableDocumentsByModelNameQuery($modelName)
	{
		return f_persistentdocument_PersistentProvider::getInstance()->createQuery($modelName, false);
	}
	
	/**
	 * @param string $documentModelName
	 * @param integer $documentIndex
	 * @param integer $chunkSize
	 * @return integer
	 */
	public function reIndexModelName($documentModelName, $documentIndex, $chunkSize)
	{
		if (!$this->isModelNameIndexable($documentModelName))
		{
			return 0;
		}
		
		$query = $this->getIndexableDocumentsByModelNameQuery($documentModelName)
				->setProjection(Projections::property('id', 'id'))
				->setFirstResult($documentIndex)
				->setMaxResults($chunkSize)
				->addOrder(Order::asc('document_id'));
		$ids = $query->findColumn('id');
		$this->getTransactionManager()->beginTransaction();
		$pp = $this->getPersistentProvider();
		
		$lastUpdate = date_Calendar::getInstance()->toString();
		foreach ($ids as $id) 
		{
			$pp->setIndexingDocumentStatus($id, self::TO_INDEX, $lastUpdate);
		}
		$this->getTransactionManager()->commit();
		return count($ids);	
	}
	
	
	/**
	 * @param integer $maxDocumentId
	 * @param integer $chunkSize
	 * @return integer
	 */
	public function backgroundIndex($maxDocumentId, $chunkSize = 100)
	{
		$documentIds = $this->getPersistentProvider()->getIndexingDocuments($maxDocumentId, $chunkSize);
		if (count($documentIds) === 0)
		{
			Framework::error(__METHOD__ . "($maxDocumentId, $chunkSize, ZERO)");
			return -1;
		}	
		
		$tm = $this->getTransactionManager();
		$pp = $tm->getPersistentProvider();
		foreach ($documentIds as $documentId) 
		{		
			try 
			{
				$tm->beginTransaction();	
				$result = $this->indexDocumentId($documentId);
				if ($result === self::INDEXED)
				{
					$pp->setIndexingDocumentStatus($documentId, $result);
				}
				elseif ($result === self::DELETED)
				{
					$pp->deleteIndexingDocumentStatus($documentId);
				}				
				$tm->commit();
			}
			catch (Exception $e)
			{
				$tm->rollBack($e);
			}
		}
			
		if (count($documentIds) < $chunkSize)
		{
			return -1;
		}
		return min($documentIds) - 1;
	}	
	
	/**
	 * @param string $modelName
	 * @param integer $documentIndex
	 * @param integer $chunkSize
	 */
	protected function indexDocumentChunkDelayed($modelName, $documentIndex, $chunkSize)
	{
		$persistentDocumentModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
		if ($persistentDocumentModel->getName() != $modelName)
		{
			//Injected document not indexed
			return 0;
		}
		
		if (!$this->isModelNameIndexable($modelName))
		{
			Framework::warn(__METHOD__ . " model " . $modelName . " is not indexable");
			return 'ERROR';
		}
		
		$query = $this->getIndexableDocumentsByModelNameQuery($modelName)
				->setProjection(Projections::property('id', 'id'))
				->setFirstResult($documentIndex)
				->setMaxResults($chunkSize)
				->addOrder(Order::asc('document_id'));
				
		$ids = $query->findColumn('id');
				
		try 
		{
			$this->getTransactionManager()->beginTransaction();
			foreach ($ids as $documentId)
			{
				try 
				{
					$document = DocumentHelper::getDocumentInstance($documentId);
					$this->toIndex($document);
				}
				catch (Exception $ed)
				{
					Framework::exception($ed);
				}
			}
			$this->getTransactionManager()->commit();				
		} 
		catch (Exception $e) 
		{
			Framework::exception($e);
		}	
		return count($ids);
	}	
	
	/**
	 * @param string $modelName
	 * @param integer $documentIndex
	 * @param integer $chunkSize
	 * @param boolean $delayed
	 * @return integer || 'ERROR'
	 */
	public function indexDocumentChunk($modelName, $documentIndex, $chunkSize, $delayed = false)
	{
		if ($delayed)
		{
			return $this->indexDocumentChunkDelayed($modelName, $documentIndex, $chunkSize);
		}
			
		$persistentDocumentModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
		if ($persistentDocumentModel->getName() != $modelName)
		{
			//Injected document not indexed
			return 0;
		}
		
		if (!$this->isModelNameIndexable($modelName))
		{
			Framework::warn(__METHOD__ . " model " . $modelName . " is not indexable");
			return 'ERROR';
		}
		
		$query = $this->getIndexableDocumentsByModelNameQuery($modelName)
				->setProjection(Projections::property('id', 'id'))
				->setFirstResult($documentIndex)
				->setMaxResults($chunkSize)
				->addOrder(Order::asc('document_id'));
		$ids = $query->findColumn('id');	
		
		
		
		foreach ($ids as $documentId)
		{					
			try
			{
				$this->getTransactionManager()->beginTransaction();
				
				list($oldStatus, $lastUpdate) = $this->getPersistentProvider()->getIndexingDocumentStatus($documentId);
				$result = $this->indexDocumentId($documentId);
				$lastUpdate = date_Calendar::getInstance()->toString();
				if ($result === self::INDEXED)
				{
					if ($oldStatus !== $result)
					{
						$this->getPersistentProvider()->setIndexingDocumentStatus($documentId, self::INDEXED, $lastUpdate);
					}
				}
				elseif ($result === self::DELETED)
				{
					if ($oldStatus !== null)
					{
						$this->getPersistentProvider()->deleteIndexingDocumentStatus($documentId);
					}
				}
				elseif ($oldStatus !== self::TO_INDEX)
				{
					$this->getPersistentProvider()->setIndexingDocumentStatus($documentId, self::TO_INDEX, $lastUpdate);
				}
				
				$this->getTransactionManager()->commit();
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				$this->getTransactionManager()->rollBack($e);
			}
		}
		
		return count($ids);
	}	
	

	
	
	/**
	 * Execute $query on the configured <strong>frontoffice</strong> indexer using the standard request handler
	 * (search on label and full text with a boost on the label). 
	 * 
	 * @param indexer_Query $query
	 * @param string[] $suggestionTerms
	 * @return indexer_SearchResults
	 */
	public function search(indexer_Query $query, $suggestionTerms = null)
	{
		return new indexer_EmptySearchResults();
	}
	
	/**
	 * Execute $query on the configured <strong>backoffice</strong> indexer using the standard request handler
	 * (search on label and full text with a boost on the label). 
	 * 
	 * @param indexer_Query $query
	 * @return indexer_SearchResults
	 */
	public function searchBackoffice(indexer_Query $query)
	{
		return new indexer_EmptySearchResults();
	}	
	
	/**
	 * Get a *single* suggestion for the word $word from the spellchecker for $lang. 
	 * If $lang is null, the RequestContext's lang is used.
	 *
	 * @param string $word
	 * @param string $lang
	 * @return string
	 */
	public function getSuggestionForWord($word, $lang = null)
	{
		$suggestions = $this->getSuggestionArrayForWord($word, $lang);
		if (count($suggestions) > 0)
		{
			return $suggestions[0];
		}
		return null;
	}
	
	/**
	 * Get an array of at most $count suggestions for the word $word from the spellchecker for $lang. 
	 *
	 * @param string $word
	 * @param string $lang
	 * @param string $count
	 * @return Array<String>
	 */
	public function getSuggestionArrayForWord($word, $lang = null, $count = null)
	{
		return array();
	}	

	/**
	 * @param integer $documentId
	 * @return string
	 */
	protected function indexDocumentId($documentId)
	{
		$rc = RequestContext::getInstance();
		$projectLangs = $rc->getSupportedLanguages();
		$documentModelName = $this->getPersistentProvider()->getDocumentModelName($documentId);
		if ($documentModelName === false || !$this->isModelNameIndexable($documentModelName))
		{
			return $this->deleteDocumentIdForLangs($documentId, $projectLangs);
		}
		
		$result = false;
		$document = DocumentHelper::getDocumentInstance($documentId, $documentModelName);
		$documentModel = $document->getPersistentModel();
		try 
		{
			if ($documentModel->isLocalized())
			{
				foreach ($projectLangs as $lang)
				{
					$rc->setLang($lang);
					if ($document->isContextLangAvailable())
					{
						$res = $this->updateRealDocument($document);
					}
					else
					{
						$res = $this->deleteDocumentIdForLangs($documentId, array($lang));
					}
					
					if ($result === false || $result === self::DELETED)
					{
						$result = $res;
					}
				}
			}
			else
			{
				$rc->setLang($document->getLang());
				$result = $this->updateRealDocument($document);
			}
		} 
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return $result;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return indexer_IndexedDocument || null
	 */
	public function getNewIndexedDocument($document)
	{
		$pm = $document->getPersistentModel();
		if (!$pm->isIndexable() && !$pm->isBackofficeIndexable())
		{
			return null;
		}
		$returnIndexedDoc = false;
		$indexedDoc = new indexer_IndexedDocument();
		$indexedDoc->setId($document->getId());
		$indexedDoc->setDocumentModel($document->getDocumentModelName());
		$indexedDoc->setLang(RequestContext::getInstance()->getLang());
		$indexedDoc->setLabel($document->getTreeNodeLabel());
		$indexedDoc->setText($document->getTreeNodeLabel());
		$indexedDoc->setStringField('module', $pm->getModuleName());
		$indexedDoc->setDateField('modificationdate', $document->getModificationdate());
		$indexedDoc->setDateField('creationdate', $document->getCreationdate());
		
		$userIds = array();	
		if ($pm->isIndexable() && $document->isPublished())
		{
			$websiteIds = $document->getDocumentService()->getWebsiteIds($document);
			if (!is_array($websiteIds) || count($websiteIds) > 0)
			{		
				if (is_array($websiteIds))
				{
					$indexedDoc->setWebsiteIds($websiteIds);
					foreach (DocumentHelper::getDocumentArrayFromIdArray($websiteIds) as $website) 
					{
						website_WebsiteService::getInstance()->setCurrentWebsite($website);
						$userIds = array_merge($this->getFrontendAccessorIds($document), $userIds);
					}
					$userIds = array_unique($userIds);
				}
				else
				{
					$indexedDoc->setWebsiteIds(array(0));
				}
				if (count($userIds) == 0)
				{
					$userIds[] = self::PUBLIC_DOCUMENT_ACCESSOR_ID;	
				}
				$indexedDoc->foIndexable(true);				
				$indexedDoc->setLabel($document->getNavigationLabel());
				$indexedDoc->setDateField('sortable_date', $document->getModificationdate());
				$returnIndexedDoc = true;			
			}
		}
		
		if ($pm->isBackofficeIndexable() && $document->getPublicationstatus() !== 'DEPRECATED')
		{
			$indexedDoc->boIndexable(true);
			$indexedDoc->setStringField('editmodule', uixul_DocumentEditorService::getInstance()->getEditModuleName($document));
			$indexedDoc->setStringField('documentpath', $document->getDocumentService()->getPathOf($document));
			$userIds = array_merge($userIds, $this->getBackendAccessorIds($document, $indexedDoc));
			$this->setAncestors($document, $indexedDoc);
			$returnIndexedDoc = true;
		}		
		
		if ($returnIndexedDoc)
		{
			$indexedDoc->setDocumentAccessors($userIds);
			$this->addIndexedDocumentProperties($indexedDoc, $document);		
			return $indexedDoc;
		}
		return null;
	}
	
	/**
	 * 
	 * @param indexer_IndexedDocument $indexedDoc
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function addIndexedDocumentProperties($indexedDoc, $document)
	{
		$textEntries = array();	
		$aggregateEntries = array();		
		$pm = $document->getPersistentModel();
		foreach ($pm->getIndexedPropertiesInfos() as $property) 
		{
			
			/* @var $property PropertyInfo */
			if ($property->getName() === 'label' && $property->getIndexed() !== 'description')
			{
				continue;
			}
			
			if ($property->isDocument())
			{
				if ($property->isArray())
				{
					$propertyName = ucfirst($property->getName()) . 'Array';
					foreach ($document->{'get' . $propertyName}() as $value) 
					{
						$indexedDoc->setIntegerField($property->getName(), $value->getId(), true);
						$aggregateEntries[] = $value->isContextLangAvailable() ? $value->getLabel() : $value->getVoLabel();
					}
				}
				else
				{
					$propertyName = ucfirst($property->getName());
					$value = $document->{'get' . $propertyName}();
					if ($value instanceof f_persistentdocument_PersistentDocument)
					{
						$indexedDoc->setIntegerField($property->getName(), $value->getId());
						$aggregateEntries[] = $value->isContextLangAvailable() ? $value->getLabel() : $value->getVoLabel();
					}	
				}
			}
			elseif ($property->getType() === f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
			{
				$propertyName = ucfirst($property->getName());
				$value = strval($document->{'get' . $propertyName . 'AsHtml'}());	
				if (!empty($value))
				{
					if ($property->getIndexed() === 'description')
					{
						$textEntries[] = f_util_HtmlUtils::htmlToText($value, false);
					}
					else
					{
						$aggregateEntries[] =  f_util_HtmlUtils::htmlToText($value, false);
						$indexedDoc->setStringField($property->getName(), $value);
					}
				}
			}			
			else
			{
				$propertyName = ucfirst($property->getName());
				$value = strval($document->{'get' . $propertyName}());			
				if (!empty($value))
				{
					if ($property->getIndexed() === 'description')
					{
						$textEntries[] = $value;
					}
					else
					{
						$aggregateEntries[] =  $value;
						$indexedDoc->setStringField($property->getName(), $value);	
					}
				}
			}
		}
		
		if (count($aggregateEntries))
		{
			$lang = $pm->isLocalized() ? RequestContext::getInstance()->getLang() : null;
			$indexedDoc->setAggregateTexts($aggregateEntries, $lang);
		}
		if (count($textEntries))	
		{	
			$indexedDoc->setText(implode('. ', $textEntries));
		}		
	}
	
	/**
	 * @param integer $documentId
	 * @param string[] $langs
	 * @return string
	 */
	protected function deleteDocumentIdForLangs($documentId, $langs)
	{
		return self::DELETED;
	}
	
	/**
	 * @param indexer_IndexedDocument $indexedDocument
	 * @return string
	 */
	protected function addInIndex($indexedDocument)
	{
		return self::INDEXED;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return string
	 */
	protected function updateRealDocument($document)
	{
		$indexedDocument = $document->getDocumentService()->getIndexedDocument($document, $this);
		if ($indexedDocument === null)
		{
			return $this->deleteDocumentIdForLangs($document->getId(), array(RequestContext::getInstance()->getLang()));
		}
		return $this->addInIndex($indexedDocument);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param indexer_IndexedDocument $indexedDocument
	 * @return Array<Integer>
	 */
	private function getBackendAccessorIds($document, $indexedDocument)
	{
		$ps = change_PermissionService::getInstance();
		$model = $document->getPersistentModel();
		$fields = $indexedDocument->getFields();
		$module = $fields['editmodule' . indexer_Field::STRING]['value'];
		if (empty($module)) {$module = $fields['module' . indexer_Field::STRING]['value'];}
		$packageName = 'modules_' . $module;
		
		$roleService = change_PermissionService::getRoleServiceByModuleName($module);		
		if ($roleService === null || count($roleService->getRoles()) === 0)
		{
			$dgrp = users_BackendgroupService::getInstance()->getDefaultGroup();
			return $dgrp !== null ? array($dgrp->getId()) : array();
		}
		
		$definitionPointId = $ps->getDefinitionPointForPackage($document->getId(), $packageName);
		$permissionName = $packageName . '.List.' . $model->getDocumentName();
		return $ps->getAccessorIdsForPermissionAndDocumentId($permissionName, $definitionPointId);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return integer[]
	 */
	private function getFrontendAccessorIds($document)
	{
		$userIds = array();
		$page = $document->getDocumentService()->getDisplayPage($document);
		if ($page !== null)
		{
			$userIds = change_PermissionService::getInstance()->getAccessorIdsForRoleByDocumentId('modules_website.AuthenticatedFrontUser', $page->getId());
		}
		return $userIds;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param indexer_IndexedDocument $indexedDocument
	 */
	private function setAncestors($document, $indexedDocument)
	{
		$treeNode = TreeService::getInstance()->getInstanceByDocument($document);
		if ($treeNode !== null)
		{
			$ancestors = $treeNode->getAncestors();
			$parent = f_util_ArrayUtils::lastElement($ancestors);
			foreach ($ancestors as $ancestor)
			{
				$indexedDocument->addDocumentAncestor($ancestor->getId());
			}
			if ($parent !== null)
			{
				$moduleName = $document->getPersistentModel()->getModuleName();
				if ($parent->getPersistentDocument() instanceof website_persistentdocument_topic && $moduleName !== 'website')
				{
					$indexedDocument->addDocumentAncestor(ModuleService::getInstance()->getRootFolderId($moduleName));
				}
			}
		}
		else
		{
			$ms = ModuleBaseService::getInstanceByModuleName($document->getPersistentModel()->getModuleName());
			$parent = null;
			if ($ms !== null)
			{
				$parent = $ms->getVirtualParentForBackoffice($document);
			}
			// still no parent, fallback to the root node of the original module
			if ($parent == null)
			{
				$parentId = ModuleService::getInstance()->getRootFolderId($document->getPersistentModel()->getModuleName());
			}
			else 
			{
				$parentId = $parent->getId();
				$this->setAncestors($parent, $indexedDocument);
			}
			$indexedDocument->addDocumentAncestor($parentId);
		}
	}
	
	/**
	 * @param Array $updatedRoles
	 */
	public function scheduleReindexingByUpdatedRoles($updatedRoles)
	{
		try 
		{
			$this->getTransactionManager()->beginTransaction();
			
			$taskService = task_PlannedtaskService::getInstance();
			$plannedTasks = $taskService->getRunnableBySystemtaskclassname('f_tasks_ReindexDocumentsByUpdatedRolesTask');
			if (f_util_ArrayUtils::isNotEmpty($plannedTasks))
			{
				$reindexDocumentTask = f_util_ArrayUtils::firstElement($plannedTasks);
				$parameters = unserialize($reindexDocumentTask->getParameters());
				$roles = $parameters['updatedRoles'];
			}
			else 
			{
				$reindexDocumentTask = $taskService->getNewDocumentInstance();
				$reindexDocumentTask->setSystemtaskclassname('f_tasks_ReindexDocumentsByUpdatedRolesTask');
				$reindexDocumentTask->setLabel(__METHOD__);
				$roles = array();
			}
			
			$roles = array_unique(array_merge($roles, $updatedRoles));
			$runDate = date_Calendar::getInstance()->add(date_Calendar::MINUTE, 20);
			$reindexDocumentTask->setParameters(serialize(array('updatedRoles' => $roles)));
			$reindexDocumentTask->setUniqueExecutiondate($runDate);	
			$reindexDocumentTask->save();
					
			$this->getTransactionManager()->commit();
		} 
		catch (Exception $e) 
		{
			$this->getTransactionManager()->rollBack($e);
		}
	}
	
	/**
	 * Returns the array of document models that should be reindexed when the role $roleName was 
	 * attributed or removed to some user/group. Default implementation returns an empty array if the role is a 
	 * backoffice role and all frontoffice documents if it is a frontoffice role.
	 * @param string $roleName
	 * @return Array
	 */
	public function getIndexableDocumentModelsForModifiedRole($roleName)
	{
		$result = array();
		$roleService = change_PermissionService::getRoleServiceByRole($roleName);
		if ($roleService->hasRole($roleName))
		{
			$perms = $roleService->getPermissionsByRole($roleName);
			foreach ($perms as $permsname) 
			{
				if ($permsname === 'modules_website.AuthenticatedFrontUser')
				{
					foreach ($this->getFrontOfficeModelsName() as $modelName) 
					{
						$result[$modelName] = $modelName;
					}
				}
				elseif (strpos($permsname, '.List.'))
				{
					$modelNames = ModuleService::getInstance()->getDefinedDocumentModelNames(change_PermissionService::getModuleNameByRole($roleName));
					foreach ($modelNames as $modelName)
					{
						if (in_array($modelName, $this->getBackOfficeModelsName()))
						{
							$result[$modelName] = $modelName;
						}
					}
				}
			}
		}
		return array_values($result);
	}
	
	// Deprecated
	
	/**
	 * @deprecated
	 */
	public function clearBackofficeIndex()
	{
		$this->clearIndex();
	}
	
	/**
	 * @deprecated
	 */	
	public function clearFrontofficeIndex()
	{
		$this->clearIndex();
	}
}