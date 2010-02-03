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
	 *
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
	 *
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
	 * 
	 * @throws IllegalArgumentException
	 * @param indexer_IndexableDocument $document
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
	 * 
	 * @throws IllegalArgumentException
	 * @param indexer_IndexableDocument $document
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
	
	/**
	 */
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
	 * @return indexer_SearchResults
	 */
	public function search(indexer_Query $query)
	{
		try
		{
			$this->beginFrontIndexerMode();
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
			$searchResults = $this->manager->getArrayPropertyFromData('suggestions', $data);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
		return $searchResults;
	}
	
	/**
	 * Get the array of declared synonyms in solr's schema.xml file.
	 *
	 * @return Array
	 */
	public function getSynonymsLists()
	{
		try
		{
			$this->beginFrontIndexerMode();
			if ($this->manager == null)
			{
				$searchResults = array();
			}
			$searchResults = $this->manager->getSynonymsLists();
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
		return $searchResults;
	}
	
	/**
	 * Update the synonyms list $synonymsList with the content $content
	 *
	 * @param String $synonymsList
	 * @param String $content
	 */
	public function updateSynonymsList($synonymsList, $content)
	{
		if ($this->manager == null)
		{
			return;
		}
		try
		{
			$this->beginFrontIndexerMode();
			$this->manager->updateSynonymsList($synonymsList, $content);
			$this->endIndexerMode();
		}
		catch (Exception $e)
		{
			$this->endIndexerMode($e);
		}
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
	
	/**
	 * @return array<integer>
	 */
	public function getIndexableDocumentIds()
	{
		$indexableDocumentIds = array();
		foreach ($this->getIndexablePersistentModels() as $model)
		{
			$query = $this->getIndexableDocumentsByDocumentModelQuery($model)->setProjection(Projections::property('id', 'id'));
			foreach ($query->find() as $idArray)
			{
				$indexableDocumentIds[] = $idArray['id'];
			}
		}
		return $indexableDocumentIds;
	}
	
	/**
	 * @return array<integer>
	 */
	public function getBackofficeIndexableDocumentIds()
	{
		$indexableDocumentIds = array();
		foreach ($this->getBackofficeIndexablePersistentModels() as $model)
		{
			$query = $this->getBackofficeIndexableDocumentsByDocumentModelQuery($model)->setProjection(Projections::property('id', 'id'));
			foreach ($query->find() as $idArray)
			{
				$indexableDocumentIds[] = $idArray['id'];
			}
		}
		return $indexableDocumentIds;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return Boolean
	 */
	public function isModelIndexable($model)
	{
		if ($this->getIndexerMode() == self::BACKOFFICE_SUFFIX)
		{
			return $model->isBackofficeIndexable();
		}
		return $model->isIndexable();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentImpl $document
	 * @return Array<Integer>
	 */
	public function getBackendAccessorIds($document)
	{
		$ps = f_permission_PermissionService::getInstance();
		$model = $document->getPersistentModel();
		$packageName = 'modules_' . $model->getOriginalModuleName();
		$roleService = f_permission_PermissionService::getRoleServiceByModuleName($model->getOriginalModuleName());
		
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
	 * @return Integer[]
	 */
	private function getFrontendAccessorIds($document)
	{
		$treeNode = TreeService::getInstance()->getInstanceByDocument($document);
		$users = array();
		if ($treeNode !== null)
		{
			$ancestors = $treeNode->getAncestors();
			$parent = f_util_ArrayUtils::lastElement($ancestors);
			if ($parent !== null && $parent->getPersistentDocument() instanceof website_persistentdocument_topic)
			{
				$ps = f_permission_PermissionService::getInstance();
				$users = $ps->getAccessorIdsForRoleByDocumentId('modules_website.AuthenticatedFrontUser', $parent->getId());
			}
		}
		if (count($users) == 0)
		{
			$users[] = indexer_IndexService::PUBLIC_DOCUMENT_ACCESSOR_ID;
		}
		return $users;
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
		else if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' $document->getIndexedDocument() ' . $this->getIndexerMode() . 'did not return an indexer_IndexedDocument (id =' . $document->getId() . ')');
		}
	}
	
	/**
	 * @param indexer_IndexableDocument $document
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
			$indexedDocument = $document->getBackofficeIndexedDocument();
		}
		else
		{
			$indexedDocument = $document->getIndexedDocument();
		}
		
		if ($indexedDocument === null)
		{
			$this->deleteDocument($document);
		}
		else
		{
			$this->addDocument($document);
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
		foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang)
		{
			if ($document->isLangAvailable($lang))
			{
				$this->manager->delete("$id/$lang");
			}
		}
	}
		
	/**
	 * @throws IllegalArgumentException
	 * @param indexer_IndexableDocument $document
	 */
	private function buildFrontIndexedDocument($document)
	{
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
			}
			$indexedDocument->addDocumentAncestor($parentId);
		}
	}
	
	/**
	 * @throws IllegalArgumentException
	 * @param f_persistentdocument_PersistentDocumentImpl $document
	 */
	private function buildBackIndexedDocument($document)
	{
		$backofficeIndexDocument = $document->getBackofficeIndexedDocument();
		if ($backofficeIndexDocument instanceof indexer_IndexedDocument)
		{
			if (!$backofficeIndexDocument->hasDocumentAccessors())
			{
				$backofficeIndexDocument->setDocumentAccessors($this->getBackendAccessorIds($document));
			}
			$this->setAncestors($document, $backofficeIndexDocument);
			$hasHtmlLink = false;
			$hasBlock = false;
			if (f_util_ClassUtils::methodExists($document, 'getHtmlLinkAttributeForIndexer'))
			{
				$backofficeIndexDocument->setStringField(tree_parser_XmlTreeParser::HTMLLINK_ATTRIBUTE, f_util_ClassUtils::callMethodOn($document, 'getHtmlLinkAttributeForIndexer'));
				$hasHtmlLink = true;
			}
			
			if (f_util_ClassUtils::methodExists($document, 'getBlockAttributeForIndexer'))
			{
				$backofficeIndexDocument->setStringField(tree_parser_XmlTreeParser::BLOCK_ATTRIBUTE, f_util_ClassUtils::callMethodOn($document, 'getBlockAttributeForIndexer'));
				$hasBlock = true;
			}
			
			if ($hasHtmlLink === false || $hasBlock === false)
			{
				$attributes = $this->getBackofficeAttributes($document);
			}
			
			if ($hasHtmlLink === false)
			{
				$backofficeIndexDocument->setStringField(tree_parser_XmlTreeParser::HTMLLINK_ATTRIBUTE, $attributes[tree_parser_XmlTreeParser::HTMLLINK_ATTRIBUTE]);
			}
			
			if ($hasBlock === false)
			{
				$backofficeIndexDocument->setStringField(tree_parser_XmlTreeParser::BLOCK_ATTRIBUTE, $attributes[tree_parser_XmlTreeParser::BLOCK_ATTRIBUTE]);
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
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return f_persistentdocument_criteria_Query
	 */
	private function getIndexableDocumentsByDocumentModelQuery($model)
	{
		return f_persistentdocument_PersistentProvider::getInstance()->createQuery($model->getName(), false);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return f_persistentdocument_criteria_Query
	 */
	private function getBackofficeIndexableDocumentsByDocumentModelQuery($model)
	{
		return f_persistentdocument_PersistentProvider::getInstance()->createQuery($model->getName(), false)->add(Restrictions::ne('publicationstatus', 'DEPRECATED'));
	}
	
	/**
	 * @return array<f_persistentdocument_PersistentDocumentModel>
	 */
	private function getIndexablePersistentModels()
	{
		$indexableModels = array();
		$models = f_persistentdocument_PersistentDocumentModel::getDocumentModels();
		foreach ($models as $model)
		{
			if ($this->isModelIndexable($model))
			{
				$indexableModels[] = $model;
			}
		}
		return $indexableModels;
	}
	
	/**
	 * @return array<f_persistentdocument_PersistentDocumentModel>
	 */
	private function getBackofficeIndexablePersistentModels()
	{
		$indexableModels = array();
		$models = f_persistentdocument_PersistentDocumentModel::getDocumentModels();
		foreach ($models as $model)
		{
			if ($model->isBackofficeIndexable())
			{
				$indexableModels[] = $model;
			}
		}
		return $indexableModels;
	}
	
	/**
	 * 
	 *
	 * @param f_persistentdocument_PersistentDocumentImpl $document
	 * @return array();
	 */
	private function getBackofficeAttributes($document)
	{
		$attributes = array();
		$model = $document->getPersistentModel();
		if (f_util_ClassUtils::methodExists($document, tree_parser_XmlTreeParser::DEFAULT_NAVIGATIONLABEL_METHOD))
		{
			$label = f_util_ClassUtils::callMethodOn($document, tree_parser_XmlTreeParser::DEFAULT_NAVIGATIONLABEL_METHOD);
		}
		else
		{
			$label = $document->getLabel();
		}
		
		$attributes[tree_parser_XmlTreeParser::HTMLLINK_ATTRIBUTE] = sprintf(tree_parser_XmlTreeParser::DEFAULT_HTMLLINK_TEMPLATE, $document->getId(), RequestContext::getInstance()->getLang(), RequestContext::getInstance()->getLang(), f_Locale::translateUI($label));
		if (!($document instanceof generic_persistentdocument_folder))
		{
			$attributes[tree_parser_XmlTreeParser::BLOCK_ATTRIBUTE] = str_replace('/', '_', $model->getName());
			$document->buildTreeAttributes($model->getModuleName(), tree_parser_TreeParser::TYPE_MULTI_LIST, $attributes);
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
	 * @deprecated
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
	
	/**
	 * Returns the array of document ids that should be reindexed for the frontoffice when the role $roleName was 
	 * attributed or removed to some user/group. Default implementation returns an empty array if the role is a 
	 * backoffice role and all frontoffice documents if it is a frontoffice role.
	 * 
	 * @param String $roleName
	 * @return Array
	 */
	public function getIndexableDocumentIdsForModifiedRole($roleName)
	{
		$roleService = f_permission_PermissionService::getRoleServiceByRole($roleName);
		if ($roleService->isBackEndRole($roleName))
		{
			return array();
		}
		return $this->getIndexableDocumentIds();
	}
	
	/**
	 * Returns the array of document ids that should be reindexed for the backoffice when the role $roleName was 
	 * attributed or removed to some user/group. Default implementation returns an empty array if the role is a 
	 * frontoffce role and all documents belonging to the corresponding module if it is a backoffice role.
	 * 
	 * @param String $roleName
	 * @return Array
	 */
	public function getBackofficeIndexableDocumentIdsForModifiedRole($roleName)
	{
		$roleService = f_permission_PermissionService::getRoleServiceByRole($roleName);
		if ($roleService->isFrontEndRole($roleName))
		{
			return array();
		}
		$models = ModuleService::getInstance()->getDefinedDocumentModels(f_permission_PermissionService::getModuleNameByRole($roleName));
		$indexableDocumentIds = array();
		foreach ($models as $model)
		{
			$query = $this->getBackofficeIndexableDocumentsByDocumentModelQuery($model)->setProjection(Projections::property('id', 'id'));
			foreach ($query->find() as $idArray)
			{
				$indexableDocumentIds[] = $idArray['id'];
			}
		}
		return $indexableDocumentIds;
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
}