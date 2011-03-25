<?php
/**
 * @package framework.indexer
 * @author franck.stauffer
 */
class indexer_IndexService extends BaseService 
{
	const PUBLIC_DOCUMENT_ACCESSOR_ID = 0;
	const MAX_QUEUE_LENGTH = 100;
	const DEFAULT_SOLR_INDEXER_CLIENT = "defaultChangeIndexerClient";
	const BACKOFFICE_SUFFIX = '__backoffice';
	const INDEXER_MODE_BACKOFFICE = 0;
	const INDEXER_MODE_FRONTOFFICE = 1;
	
	/**
	 * @var indexer_IndexingService
	 */
	private static $instance = null;
	
	/**
	 * @var indexer_SolrManager
	 */
	private $manager = null;
	
	/**
	 * @var array<int>
	 */
	private $indexerModeSwitches = array();
	
	/**
	 * @var array
	 */
	private $modelsInfos;
	
	/**
	 * Get the service instance.
	 *
	 * @return indexer_IndexService
	 */
	public final static function getInstance()
	{
		if (null === self::$instance)
		{
			$newInstance = self::getServiceClassInstance(get_class());
			if (defined('SOLR_INDEXER_URL'))
			{
				$solrURL = SOLR_INDEXER_URL;
				if (!f_util_StringUtils::endsWith($solrURL, '/'))
				{
					$solrURL .= '/';
				}
				$newInstance->manager = new indexer_SolrManager($solrURL);
			}
			else
			{
				$newInstance->manager = new indexer_FakeSolrManager();
			}
			self::$instance = $newInstance;
			
			register_shutdown_function(array('indexer_IndexService','shutdownCommit'));
		}
		return self::$instance;
	}
	
	/**
	 * @return Int
	 */
	public function getIndexerMode()
	{
		if (f_util_ArrayUtils::isEmpty($this->indexerModeSwitches))
		{
			return self::INDEXER_MODE_FRONTOFFICE;
		}
		return f_util_ArrayUtils::lastElement($this->indexerModeSwitches);
	}
	
	public function loadModelsInfos()
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
	 * @throws IllegalArgumentException
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function add($document)
	{
		try
		{
			$this->beginFrontIndexerMode();
			$this->addDocument($document);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
	}
	
	/**
	 * @throws IllegalArgumentException
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function addBackoffice($document)
	{
		try
		{
			$this->beginBackIndexerMode();
			$this->addDocument($document);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
	}
	
	/**
	 * Update the indexer_IndexableDocument $document in the index. 
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function update($document)
	{
		try
		{
			$this->beginFrontIndexerMode();
			$this->updateDocument($document);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
	}
	
	/**
	 * Update the indexer_IndexableDocument $document in the index. 
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function updateBackoffice($document)
	{
		try
		{
			$this->beginBackIndexerMode();
			$this->updateDocument($document);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
	}
	
	/**
	 * Delete the indexer_IndexableDocument $document from the index.
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function delete($document)
	{
		try
		{
			$this->beginFrontIndexerMode();
			$this->deleteDocument($document);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
	}
	
	/**
	 * Delete the indexer_IndexableDocument $document from the index.
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function deleteBackoffice($document)
	{
		try
		{
			$this->beginBackIndexerMode();
			$this->deleteDocument($document);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
	}
	
	public function clearBackofficeIndex()
	{
		if ($this->manager !== null)
		{
			try
			{
				$this->beginBackIndexerMode();
				$this->manager->clearIndexQuery();
				$this->manager->commit();
				$this->endIndexerMode();
			}
			catch (Exception $e)
			{
				$this->endIndexerMode($e);
			}
		}
	}
	
	public function clearFrontofficeIndex()
	{
		if ($this->manager !== null)
		{
			try
			{
				$this->beginFrontIndexerMode();
				$this->manager->clearIndexQuery();
				$this->manager->commit();
				$this->endIndexerMode();
			}
			catch (Exception $e)
			{
				$this->endIndexerMode($e);
			}
		}
	}
	
	public function clearIndex()
	{
		$this->clearBackofficeIndex();
		$this->clearFrontofficeIndex();
	}
	
	public function optimizeIndex()
	{
		if ($this->manager !== null)
		{
			try
			{
				$this->beginBackIndexerMode();
				$this->manager->optimizeIndexQuery();
				$this->endIndexerMode();
				
				$this->beginFrontIndexerMode();
				$this->manager->optimizeIndexQuery();
				$this->endIndexerMode();
			}
			catch (Exception $e)
			{
				$this->endIndexerMode($e);
			}
		}
	}
	
	public function rebuildSpellCheckIndexForLang($lang)
	{
		if ($this->manager !== null)
		{
			try
			{
				$this->beginBackIndexerMode();
				$this->manager->rebuildSpellCheckIndexForLang($lang);
				$this->endIndexerMode();
				
				$this->beginFrontIndexerMode();
				$this->manager->rebuildSpellCheckIndexForLang($lang);
				$this->endIndexerMode();
			}
			catch (Exception $e)
			{
				$this->endIndexerMode($e);
			}
		}
	}
	

	/**
	 * Execute $query on the configured <strong>frontoffice</strong> indexer using the standard request handler
	 * (search on label and full text with a boost on the label). 
	 * 
	 * @param indexer_Query $query
	 * @param String[] $suggestionTerms
	 * @return indexer_SearchResults
	 */
	public function search(indexer_Query $query, $suggestionTerms = null)
	{
		try
		{
			$this->beginFrontIndexerMode();
			$solrSearch = new indexer_StandardSolrSearch($query);
			if (f_util_ArrayUtils::isNotEmpty($suggestionTerms))
			{
				$solrSearch->doSuggestion($suggestionTerms);
			}
			$data = $this->manager->query($solrSearch);
			$searchResults = new indexer_SolrSearchResults($data, $solrSearch);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
		return $searchResults;
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
		try
		{
			$this->beginBackIndexerMode();
			$solrSearch = new indexer_StandardSolrSearch($query);
			$data = $this->manager->query($solrSearch);
			$searchResults = new indexer_SolrSearchResults($data, $solrSearch);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
		return $searchResults;
	}
	
	/**
	 * Execute $query on the configured <strong>frontoffice</strong> using solr's dismax request handler
	 * (see http://wiki.apache.org/solr/DisMaxRequestHandler). 
	 * 
	 * @param indexer_Query $query
	 * @return indexer_SearchResults
	 */
	public function dismaxSearch(indexer_Query $query)
	{
		try
		{
			$this->beginFrontIndexerMode();
			$solrSearch = new indexer_DismaxSolrSearch($query);
			$data = $this->manager->query($solrSearch);
			$searchResults = new indexer_SolrSearchResults($data, $solrSearch);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
		return $searchResults;
	}
	
	/**
	 * Get a *single* suggestion for the word $word from the spellchecker for $lang. 
	 * If $lang is null, the RequestContext's lang is used.
	 *
	 * @param String $word
	 * @param String $lang
	 * @return String
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
	 * Get a suggestion for a term list. This method is only here for compatibility
	 * with old SolR schema (2.0.4). You should use indexer_StandardSolrSearch::doSuggestion() method
	 * @param String[] $words
	 * @param String $lang
	 * @return String
	 */
	public function getSuggestionForWords($words, $lang = null)
	{
		if (indexer_SolrManager::getSchemaVersion() == "2.0.4")
		{
			throw new Exception("This method only works with SolR 1.4 + change schema 3.0.3");
		}
		try
		{
			$this->beginFrontIndexerMode();
			$query = new indexer_SuggestionSolrSearch($words, $lang);
			$query->setSuggestionCount(1);
			$data = $this->manager->query($query);
			$this->endIndexerMode();
			
			$dataDom = f_util_DOMUtils::fromString($data);
			$elem = $dataDom->findUnique("lst[@name='spellcheck']/lst[@name='suggestions']/str[@name='collation']");
			if ($elem !== null)
			{
				return $elem->textContent;
			}
			return null;
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
	}
	
	/**
	 * Get an array of at most $count suggestions for the word $word from the spellchecker for $lang. 
	 *
	 * @param String $word
	 * @param String $lang
	 * @param String $count
	 * @return Array<String>
	 */
	public function getSuggestionArrayForWord($word, $lang = null, $count = null)
	{
		try
		{
			$this->beginFrontIndexerMode();
			$query = new indexer_SuggestionSolrSearch($word, $lang);
			if (!is_null($count) && $count > 0)
			{
				$query->setSuggestionCount($count);
			}
			$data = $this->manager->query($query);
			$schemaVersion = indexer_SolrManager::getSchemaVersion();
			if ($schemaVersion == "2.0.4")
			{
				$searchResults = $this->manager->getArrayPropertyFromData('suggestions', $data);
			}
			else
			{
				$dataDom = f_util_DOMUtils::fromString($data);
				$elems = $dataDom->find("lst[@name='spellcheck']/lst[@name='suggestions']/lst[@name='".str_replace("'", "&#39;", $word)."']/arr[@name='suggestion']/str");
				$searchResults = array();
				foreach ($elems as $elem)
				{
					$searchResults[] = $elem->textContent;
				}
			}
			
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
		return $searchResults;
	}
		
	/**
	 * Set the solr autocommit mode 
	 *
	 * @param Boolean $bool
	 */
	public function setAutoCommit($bool)
	{
		if (!is_null($this->manager) && is_bool($bool))
		{
			$this->manager->setAutoCommit($bool);
		}
	}
	
	/**
	 * Send a commit
	 */
	public function commit()
	{
		if (!is_null($this->manager))
		{
			$this->manager->sendCommit();
		}
	}
	
	/**
	 * @return String
	 */
	public function getClientId()
	{
		if ($this->getIndexerMode() == self::INDEXER_MODE_BACKOFFICE)
		{
			return $this->getBaseClientId() . self::BACKOFFICE_SUFFIX;
		}
		return $this->getBaseClientId();
	}
	
	public function indexDocumentChunk($mode, $modelName, $documentIndex, $chunkSize)
	{
		$backIndexing = ($mode == 'back');
		$persistentDocumentModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
		if ($persistentDocumentModel->getName() != $modelName)
		{
			//Injected document not indexed
			return 0;
		}
		
		if ($backIndexing && ! $persistentDocumentModel->isBackofficeIndexable())
		{
			Framework::warn(__METHOD__ . " model " . $modelName . " is not backoffice indexable");
			return 'ERROR';
		}
		if (!$backIndexing && ! $persistentDocumentModel->isIndexable())
		{
			Framework::warn(__METHOD__ . " model " . $modelName . " is not frontoffice indexable");
			continue;
		}		
		
		$query = $this->getIndexableDocumentsByModelNameQuery($modelName)
				->setProjection(Projections::property('id', 'id'))
				->setFirstResult($documentIndex)
				->setMaxResults($chunkSize)
				->addOrder(Order::asc('document_id'));
		$ids = $query->findColumn('id');
		$rc = RequestContext::getInstance();		
		foreach ($ids as $documentId)
		{
			try
			{
				$document = DocumentHelper::getDocumentInstance($documentId);
				foreach ($document->getI18nInfo()->getLangs() as $lang)
				{
					try
					{
						$rc->beginI18nWork($lang);	
						if (!$document->isContextLangAvailable())
						{
							continue;
						}
						if ($backIndexing)
						{
							if ($document->getPublicationstatus() !== "DEPRECATED")
							{
								if (Framework::isInfoEnabled())
								{
									Framework::info("Index BO ($lang) " . $document->__toString());
								}
								$this->addBackoffice($document);
							}
						}
						else if ($document->isPublished())
						{
							if (Framework::isInfoEnabled())
							{
								Framework::info("Index FO ($lang) " . $document->__toString());
							}
							$this->add($document);
						}
						$rc->endI18nWork();
					}
					catch (Exception $e)
					{
						$rc->endI18nWork($e);
					}
				}
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				echo $e->getMessage();
			}
		}
		return count($ids);
	}
	
	/**
	 * @param string $modelName
	 * @return boolean
	 */
	public function isModelNameIndexable($modelName)
	{
		if ($this->getIndexerMode() == self::BACKOFFICE_SUFFIX)
		{
			return in_array($modelName, $this->getBackOfficeModelsName());
		}
		return in_array($modelName, $this->getFrontOfficeModelsName());
	}	
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param indexer_IndexedDocument $indexedDocument
	 * @return Array<Integer>
	 */
	private function getBackendAccessorIds($document, $indexedDocument)
	{
		$ps = f_permission_PermissionService::getInstance();
		$model = $document->getPersistentModel();
		$fields = $indexedDocument->getFields();
		$module = $fields['module' . indexer_Field::STRING]['value'];
		$packageName = 'modules_' . $module;
		
		$roleService = f_permission_PermissionService::getRoleServiceByModuleName($module);		
		if ($roleService === null || count($roleService->getRoles()) === 0)
		{
			// We have no role service or no roles declared
			return array(self::PUBLIC_DOCUMENT_ACCESSOR_ID);
		}
		
		$definitionPointId = $ps->getDefinitionPointForPackage($document->getId(), $packageName);
		$permissionName = $packageName . '.Update.' . $model->getOriginalDocumentName();
		return $ps->getAccessorIdsForPermissionAndDocumentId($permissionName, $definitionPointId);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return Integer[]
	 */
	private function getFrontendAccessorIds($document)
	{
		$userIds = array();
		$page = $document->getDocumentService()->getDisplayPage($document);
		if ($page !== null)
		{
			$userIds = f_permission_PermissionService::getInstance()->getAccessorIdsForRoleByDocumentId('modules_website.AuthenticatedFrontUser', $page->getId());
		}
		if (count($userIds) == 0)
		{
			$userIds[] = indexer_IndexService::PUBLIC_DOCUMENT_ACCESSOR_ID;
		}
		return $userIds;
	}
	
	/**
	 * @param Int $mode
	 */
	protected function beginIndexerMode($mode)
	{
		if ($mode != self::INDEXER_MODE_BACKOFFICE && $mode != self::INDEXER_MODE_FRONTOFFICE)
		{
			throw new IllegalArgumentException('$mode has to be either self::INDEXER_MODE_BACKOFFICE or self::INDEXER_MODE_FRONTOFFICE');
		}
		$this->indexerModeSwitches[] = $mode;
	}
	
	/**
	 */
	protected function endIndexerMode($e = null)
	{
		if (f_util_ArrayUtils::isNotEmpty($this->indexerModeSwitches))
		{
			array_pop($this->indexerModeSwitches);
		}
		if (null !== $e)
		{
			throw $e;
		}
	}
	
	/**
	 * Shortcut to begin a front office indexing session
	 */
	protected final function beginFrontIndexerMode()
	{
		$this->beginIndexerMode(self::INDEXER_MODE_FRONTOFFICE);
	}
	
	/**
	 * Shortcut to begin a backoffice indexing session
	 */
	protected final function beginBackIndexerMode()
	{
		$this->beginIndexerMode(self::INDEXER_MODE_BACKOFFICE);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
	private function isIndexingOperationPossible($document)
	{
		if ($this->manager === null)
		{
			return false;
		}
		if ($this->getIndexerMode() == self::INDEXER_MODE_BACKOFFICE)
		{
			return $document->getPersistentModel()->isBackofficeIndexable();
		}
		return $document->getPersistentModel()->isIndexable();
	}
	

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function addDocument($document)
	{
		if (!$this->isIndexingOperationPossible($document))
		{
			Framework::warn(__METHOD__ . ' Can not index document ' . $document->getId() . ', please check your config and document model.');
			return;
		}
		
		if ($this->getIndexerMode() == self::BACKOFFICE_SUFFIX)
		{
			$indexedDocument = $this->buildBackIndexedDocument($document);
		}
		else
		{
			$indexedDocument = $this->buildFrontIndexedDocument($document);
		}
		
		if ($indexedDocument instanceof indexer_IndexedDocument)
		{
			$this->manager->add($indexedDocument);
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function updateDocument($document)
	{
		if (!$this->isIndexingOperationPossible($document))
		{
			Framework::warn(__METHOD__ . ' Can not update document ' . $document->getId() . ', please check your config and document model.');
			return;
		}
		
		if ($this->getIndexerMode() == self::INDEXER_MODE_BACKOFFICE)
		{
			$indexedDocument = $this->buildBackIndexedDocument($document);
		}
		else
		{
			$indexedDocument = $this->buildFrontIndexedDocument($document);
		}
		
		if ($indexedDocument instanceof indexer_IndexedDocument)
		{
			$this->manager->add($indexedDocument);
		}
		else
		{
			$id = $document->getId();
			foreach ($document->getI18nInfo()->getLangs() as $lang)
			{
				$this->manager->delete($id .'/' .$lang);
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function deleteDocument($document)
	{
		if (!$this->isIndexingOperationPossible($document))
		{
			Framework::warn(__METHOD__ . ' Can not delete document ' . $document->getId() . ' from index, please check your config and document model.');
			return;
		}
		
		$id = $document->getId();
		foreach ($document->getI18nInfo()->getLangs() as $lang)
		{
			$this->manager->delete($id .'/' .$lang);
		}
	}
		
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return indexer_IndexedDocument
	 */
	private function buildFrontIndexedDocument($document)
	{
		if (!$document->isPublished())
		{
			return null;
		}

		$indexedDocument = $document->getIndexedDocument();
		if ($indexedDocument instanceof indexer_IndexedDocument)
		{
			if (!$indexedDocument->hasDocumentAccessors())
			{
				$indexedDocument->setDocumentAccessors($this->getFrontendAccessorIds($document));
			}
			$this->setAncestors($document, $indexedDocument);
			
			// set the parent website if it is not set
			if (!$indexedDocument->hasParentWebsiteId())
			{
				$websiteId = intval($document->getDocumentService()->getWebsiteId($document));
				$indexedDocument->setParentWebsiteId($websiteId);
				if ($websiteId > 0)
				{
					$parent = $document->getDocumentService()->getParentOf($document);
					if ($parent instanceof website_persistentdocument_topic)
					{
						$indexedDocument->setParentTopicId($parent->getId());
					}
					else
					{
						$indexedDocument->setParentTopicId(0);
					}
				}
			}
			$indexedDocument->setDateField('sortable_date', date_GregorianCalendar::getInstance($document->getModificationdate()));
			return $indexedDocument;
		}
		return null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentImpl $document
	 * @param indexer_IndexedDocument $indexedDocument
	 */
	private function setAncestors($document, &$indexedDocument)
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
				$moduleName = $document->getPersistentModel()->getOriginalModuleName();
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
				$parentId = ModuleService::getInstance()->getRootFolderId($document->getPersistentModel()->getOriginalModuleName());
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
	 * @param f_persistentdocument_PersistentDocumentImpl $document
	 * @return indexer_IndexedDocument
	 */
	private function buildBackIndexedDocument($document)
	{
		if ($document->getPublicationstatus() === 'DEPRECATED')
		{
			return null;
		}
		
		$backofficeIndexDocument = $document->getBackofficeIndexedDocument();
		if ($backofficeIndexDocument instanceof indexer_IndexedDocument)
		{
			if (!$backofficeIndexDocument->hasDocumentAccessors())
			{
				$backofficeIndexDocument->setDocumentAccessors($this->getBackendAccessorIds($document, $backofficeIndexDocument));
			}
			$this->setAncestors($document, $backofficeIndexDocument);
			$hasHtmlLink = false;
			$hasBlock = false;
			if (f_util_ClassUtils::methodExists($document, 'getHtmlLinkAttributeForIndexer'))
			{
				$backofficeIndexDocument->setStringField('htmllink', f_util_ClassUtils::callMethodOn($document, 'getHtmlLinkAttributeForIndexer'));
				$hasHtmlLink = true;
			}
			
			if (f_util_ClassUtils::methodExists($document, 'getBlockAttributeForIndexer'))
			{
				$backofficeIndexDocument->setStringField('block', f_util_ClassUtils::callMethodOn($document, 'getBlockAttributeForIndexer'));
				$hasBlock = true;
			}
			
			if ($hasHtmlLink === false || $hasBlock === false)
			{
				$attributes = $this->getBackofficeAttributes($document);
			}
			
			if ($hasHtmlLink === false)
			{
				$backofficeIndexDocument->setStringField('htmllink', $attributes['htmllink']);
			}
			
			if ($hasBlock === false)
			{
				$backofficeIndexDocument->setStringField('block', $attributes['block']);
			}
			return $backofficeIndexDocument;
		}
		return null;
	}
	
	/**
	 * @return String
	 */
	private function getBaseClientId()
	{
		if (defined('SOLR_INDEXER_CLIENT'))
		{
			return SOLR_INDEXER_CLIENT;
		}
		return self::DEFAULT_SOLR_INDEXER_CLIENT;
	}

	/**
	 * @param string $modelName
	 * @return f_persistentdocument_criteria_Query
	 */
	private function getIndexableDocumentsByModelNameQuery($modelName)
	{
		return f_persistentdocument_PersistentProvider::getInstance()->createQuery($modelName, false);
	}
	
		
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array();
	 */
	private function getBackofficeAttributes($document)
	{
		$attributes = array();
		$model = $document->getPersistentModel();
		if (f_util_ClassUtils::methodExists($document, 'getNavigationtitle'))
		{
			$label = f_util_ClassUtils::callMethodOn($document, 'getNavigationtitle');
		}
		else
		{
			$label = $document->getLabel();
		}
		$lang = RequestContext::getInstance()->getLang();
		$escapedLabel = htmlspecialchars(f_Locale::translateUI($label), ENT_NOQUOTES, 'UTF-8');
		$attributes['htmllink'] = '<a class="link" href="#" rel="cmpref:' . $document->getId() . '" lang="' . $lang . '">' . $escapedLabel . '</a>';
		if (!($document instanceof generic_persistentdocument_folder))
		{
			$attributes['block'] = str_replace('/', '_', $model->getName());
			$document->getDocumentService()->addTreeAttributes($document, $model->getModuleName(), 'wmultilist', $attributes);
		}
		return $attributes;
	}
	
	/**
	 * @param Array $updatedRoles
	 */
	public function scheduleReindexingByUpdatedRoles($updatedRoles)
	{
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
	}
	
	/**
	 * Returns the array of document models that should be reindexed for the frontoffice when the role $roleName was 
	 * attributed or removed to some user/group. Default implementation returns an empty array if the role is a 
	 * backoffice role and all frontoffice documents if it is a frontoffice role.
	 * 
	 * @param String $roleName
	 * @return Array
	 */
	public function getIndexableDocumentModelsForModifiedRole($roleName)
	{
		$roleService = f_permission_PermissionService::getRoleServiceByRole($roleName);
		if ($roleService->isBackEndRole($roleName))
		{
			return array();
		}
		return $this->getFrontOfficeModelsName();
	}
	
	/**
	 * Returns the array of document models that should be reindexed for the backoffice when the role $roleName was 
	 * attributed or removed to some user/group. Default implementation returns an empty array if the role is a 
	 * frontoffce role and all documents belonging to the corresponding module if it is a backoffice role.
	 * 
	 * @param String $roleName
	 * @return Array
	 */
	public function getBackofficeIndexableDocumentModelsForModifiedRole($roleName)
	{
		$roleService = f_permission_PermissionService::getRoleServiceByRole($roleName);
		if ($roleService->isFrontEndRole($roleName))
		{
			return array();
		}
		$modelNames = ModuleService::getInstance()->getDefinedDocumentModelNames(f_permission_PermissionService::getModuleNameByRole($roleName));
		$result = array();
		foreach ($modelNames as $modelName)
		{
			if (in_array($modelName, $this->getBackOfficeModelsName()))
			{
				$result[] = $modelName;
			}
		}
		return $result;
	}
	
	public static function shutdownCommit()
	{
		umask(0002);
		self::getInstance()->shutdown();
	}
	
	/**
	 * @return Boolean
	 */
	public function isDirty()
	{
		if ($this->manager !== null)
		{
			return $this->manager->isDirty();
		}
		return false;
	}
	
	private function shutdown()
	{
		if ($this->manager)
		{
			$this->manager->commit();
		}
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentImpl
	 */
	private function getParentTopicId($document)
	{
		$parent = $document->getDocumentService()->getParentOf($document);
		if ($parent instanceof website_persistentdocument_topic)
		{
			return $parent->getId();
		}
		return null;
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) 
	 */
	public function getSynonymsLists()
	{
		return array();
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	public function updateSynonymsList($synonymsList, $content)
	{
		return;
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use isModelNameIndexable
	 */
	public function isModelIndexable($model)
	{
		return $this->isModelNameIndexable($model->getName());
	}
		
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	public function scheduleReindexing()
	{
		$taskService = task_PlannedtaskService::getInstance();
		if (f_util_ArrayUtils::isEmpty($taskService->getRunnableBySystemtaskclassname('f_tasks_ReindexDocumentsTask')))
		{
			$runDate = date_Calendar::getInstance()->add(date_Calendar::MINUTE, 720);
			$reindexDocumentTask = $taskService->getNewDocumentInstance();
			$reindexDocumentTask->setSystemtaskclassname('f_tasks_ReindexDocumentsTask');
			$reindexDocumentTask->setUniqueExecutiondate($runDate);
			$reindexDocumentTask->setLabel(__METHOD__);
			$reindexDocumentTask->save();
		}
	}
}