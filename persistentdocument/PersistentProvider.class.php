<?php
/**
 * @method f_persistentdocument_PersistentProvider getInstance()
 */
abstract class f_persistentdocument_PersistentProvider extends change_Singleton
{
	/**
	 * @var integer
	 */
	protected $id;	
	
	/**
	 * @var array
	 */
	protected $connectionInfos;
	
	/**
	 * @var array
	 */
	protected $timers;
	
	/**
	 * Document instances by id
	 * @var array<integer, f_persistentdocument_PersistentDocument>
	 */
	protected $m_documentInstances = array();
	
	/**
	 * I18nDocument instances by id
	 * @var array<integer, f_persistentdocument_I18nPersistentDocument>
	*/
	protected $m_i18nDocumentInstances = array();
	
	/**
	 * Temporay identifier for new persistent document
	 * @var Integer
	 */
	protected $m_newInstancesCounter = 0;
	
	/**
	 * instance scope cache of document class names by type (ie. 'model')
	 * @var array<String,String>
	 */
	protected $m_documentClassByType = array();
		
	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}	
	
	/**
	 * @return string
	 */
	public abstract function getType();
	
	
	protected function __construct()
	{
		parent::__construct();
		$connectionInfos = Framework::getConfiguration('databases/default');
		$this->connectionInfos = $connectionInfos;
		$this->timers = array('init' => microtime(true), 'longTransaction' => isset($connectionInfos['longTransaction']) ? floatval($connectionInfos['longTransaction']) : 0.2);
	}	
		
	/**
	 * @return array
	 */
	public function getConnectionInfos()
	{
		return $this->connectionInfos;
	}	
	
	/**
	 * @return boolean
	 */
	public abstract function checkConnection();
	
	/**
	 * @return void
	 */
	public abstract function closeConnection();

	
	/**
	 * @return change_SchemaManager
	 */
	public abstract function getSchemaManager();	
	
	/**
	 * @param boolean $useDocumentCache
	 * @return f_persistentdocument_PersistentProvider
	 */
	public abstract function setDocumentCache($useDocumentCache);

	/**
	 * @return void
	 */
	public function reset()
	{
		$this->clearDocumentCache();
	}
	
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	protected function isInCache($documentId)
	{
		return isset($this->m_documentInstances[intval($documentId)]);
	}
	
	/**
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function getFromCache($documentId)
	{
		return $this->m_documentInstances[intval($documentId)];
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $doc
	 * @param string $lang
	 * @return f_persistentdocument_I18nPersistentDocument|NULL
	 */
	protected function getI18nDocumentFromCache($doc, $lang)
	{
		$docId = intval($doc->getId());
		if (isset($this->m_i18nDocumentInstances[$docId]))
		{
			if (isset($this->m_i18nDocumentInstances[$docId][$lang]))
			{
				return $this->m_i18nDocumentInstances[$docId][$lang];
			}
		}
		else
		{
			$this->m_i18nDocumentInstances[$docId] = array();
		}
		return null;
	}
	
	/**
	 * @param integer $documentId
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return void
	 */
	protected function putInCache($documentId, $document)
	{
		$documentId = intval($documentId);
		$this->m_documentInstances[$documentId] = $document;
		if ($document->getPersistentModel()->isLocalized() && $document->getRawI18nVoObject() !== null)
		{
			$this->m_i18nDocumentInstances[$documentId][$document->getLang()] = $document->getRawI18nVoObject();
		}
	}
	
	/**
	 * @param integer $documentId
	 * @return void
	 */
	protected function deleteFromCache($documentId)
	{
		unset($this->m_documentInstances[$documentId]);	
	}	
	
	/**
	 * @return void
	 */
	protected function clearDocumentCache()
	{
		$this->m_documentInstances = array();
		$this->m_i18nDocumentInstances = array();
	}	
	
	/**
	 * @var boolean
	 */
	protected $m_inTransaction = false;
	
	
	/**
	 * @return void
	 */
	public function beginTransaction()
	{
		if ($this->m_inTransaction)
		{
			Framework::warn("PersistentProvider->beginTransaction() while already in transaction");
		}
		else
		{
			$this->timers['bt'] = microtime(true);
			$this->beginTransactionInternal();
			$this->m_inTransaction = true;
			indexer_IndexService::getInstance()->beginIndexTransaction();
		}
	}

	/**
	 * @return void
	 */
	protected abstract function beginTransactionInternal();
	
	/**
	 * @return void
	 */
	public function commit()
	{
		if (!$this->m_inTransaction)
		{
			Framework::warn("PersistentProvider->commit() called while not in transaction");
		}
		else
		{
			$this->commitInternal();
			$duration = round(microtime(true) - $this->timers['bt'], 4);
			if ($duration > $this->timers['longTransaction'])
			{
				Framework::warn('Long Transaction detected '.  number_format($duration, 3) . 's > ' . $this->timers['longTransaction']);
				if (Framework::inDevelopmentMode())
				{
					Framework::warn(f_util_ProcessUtils::getBackTrace());
				}
			}
			$this->m_inTransaction = false;
			
			$this->beginTransactionInternal();
			indexer_IndexService::getInstance()->commitIndex();
			$this->commitInternal();
		}
	}
	
	/**
	 * @return void
	 */	
	protected abstract function commitInternal();
	
	/**
	 * @return void
	 */	
	public function rollBack()
	{
		if (!$this->m_inTransaction)
		{
			Framework::warn("PersistentProvider->rollBack() called while not in transaction");
		}
		else
		{
			$this->clearDocumentCache();
			indexer_IndexService::getInstance()->rollBackIndex();
			$this->rollBackInternal();
			$this->m_inTransaction = false;
		}
	}
	
	/**
	 * @return void
	 */	
	protected abstract function rollBackInternal();	
	
	
	/**
	 * @param string $script
	 * @return PDOStatement
	 */
	public abstract function executeSQLSelect($script);
	
	
	/**
	 * Return a instance of the document[@id = $id and @modelName = $modelName]
	 *
	 * @param integer $id
	 * @param string $modelName
	 * @param integer $treeId
	 * @param array $I18nInfoArray
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function getDocumentInstanceWithModelName($id, $modelName, $treeId, $I18nInfoArray)
	{
		if (!$this->isInCache($id))
		{
			$className = $this->getDocumentClassFromModel($modelName);
			$i18nInfo = (count($I18nInfoArray) === 0) ? null : I18nInfo::getInstanceFromArray($I18nInfoArray);
			$doc = new $className($id, $i18nInfo, $treeId);
			$this->putInCache($id, $doc);
			return $doc;
		}
		return $this->getFromCache($id);
	}
	
	/**
	 * @param string $documentModelName
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getNewDocumentInstance($documentModelName)
	{
		$this->m_newInstancesCounter--;
		$className = $this->getDocumentClassFromModel($documentModelName);
		return new $className($this->m_newInstancesCounter);
	}
	
	/**
	 * Return the persistent document class name from the document model name
	 * @param string $modelName
	 * @return string
	 */
	protected function getDocumentClassFromModel($modelName)
	{
		if (isset($this->m_documentClassByType[$modelName]))
		{
			return $this->m_documentClassByType[$modelName];
		}
		$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
		$className = $model->getDocumentClassName();
		$this->m_documentClassByType[$modelName] = $className;
		return $className;
	}
	
	/**
	 * Return the I18n persistent document class name from the document model name
	 * @param string $modelName
	 * @return string
	 */
	protected function getI18nDocumentClassFromModel($modelName)
	{
		return $this->getDocumentClassFromModel($modelName).'I18n';
	}
		
	/**
	 * Return the model name of the document or false
	 * @param integer $id
	 * @return string|false
	 */
	public abstract function getDocumentModelName($id);
	
	/**
	 * Return a instance of the document or null
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentDocument|NULL
	 */
	public abstract function getDocumentInstanceIfExist($documentId);
	
	/**
	 * Return a instance of the document or Exception if the document not found
	 * @param integer $documentId
	 * @param string $modelName
	 * @param string $lang
	 * @return f_persistentdocument_PersistentDocument
	 * @throws Exception
	 */
	public abstract function getDocumentInstance($documentId, $modelName = null, $lang = null);
	
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return integer
	 */
	public function getCachedDocumentId($document)
	{
		$id = $document->getId();
		if ($id < 0) 
		{
			$this->putInCache($id, $document);
		}
		return $id;
	}
	
	/**
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentDocument
	 * @throws Exception
	 */
	public function getCachedDocumentById($documentId)
	{
		if ($documentId < 0)
		{
			if ($this->isInCache($documentId))
			{
				return $this->getFromCache($documentId);
			}
			throw new Exception('document ' . $documentId . ' is not in memory');
		}
		return $this->getDocumentInstance($documentId);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $modelName
	 * @throws Exception
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function checkModelCompatibility($document, $modelName)
	{
		if ($modelName !== null && !$document->getPersistentModel()->isModelCompatible($modelName))
		{
			throw new Exception('document ' . $document->getId() . ' is a ' . $document->getDocumentModelName() . ' but not a ' . $modelName);
		}
		return $document;
	}
	
	/**
	 * When we want to get a document, the data is not loaded. When we want to access to it,
	 * this function is called for giving all data to the object.
	 *
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @throws Exception
	 */
	public abstract function loadDocument($persistentDocument);
	
	/**
	 * @param f_persistentdocument_PersistentDocument $doc
	 * @param string $lang
	 * @return f_persistentdocument_I18PersistentDocument
	 */
	public abstract function getI18nDocument($doc, $lang, $isVo = false);
	
	/**
	 * @param string $propertyName
	 * @return integer
	 */
	public abstract function getRelationId($propertyName);
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $propertyName
	 */
	public abstract function loadRelations($document, $propertyName);
	

	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public abstract function insertDocument($persistentDocument);
	
	/**
	 * Update a document.
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public abstract function updateDocument($persistentDocument);

	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public abstract function deleteDocument($persistentDocument);
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param f_persistentdocument_PersistentDocument $destDocument
	 * @return f_persistentdocument_PersistentDocument the result of mutation (destDocument)
	 */
	public abstract function mutate($document, $destDocument);

	/**
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery($documentModelName = null, $includeChildren = true)
	{
		$query = new f_persistentdocument_criteria_QueryImpl();
		if (!is_null($documentModelName))
		{
			$query->setDocumentModelName($documentModelName, $includeChildren);
		}
		return $query;
	}
	
	/**
	 * @param f_persistentdocument_criteria_Query $query
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public abstract function find($query);
	
	/**
	 * If the query has some projection, retrieve one of them into a dedicated array
	 * @param f_persistentdocument_criteria_Query $query
	 * @param string $columnName the name of the projection
	 * @return mixed[]
	*/
	public function findColumn($query, $columnName)
	{
		if (!$query->hasProjectionDeep())
		{
			throw new Exception("Could not find column if there is no projection");
		}
		$rows = $this->find($query);
		if (count($rows) == 0)
		{
			return $rows;
		}
		$result = array();
		if (!array_key_exists($columnName, $rows[0]))
		{
			throw new Exception("Column $columnName not found in query");
		}
		foreach ($rows as $row)
		{
			$result[] = $row[$columnName];
		}
		return $result;
	}
	
	/**
	 * @param f_persistentdocument_criteria_QueryIntersection $intersection
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function findIntersection($intersection)
	{
		$ids = $this->findIntersectionIds($intersection);
		if (count($ids) == 0)
		{
			return array();
		}
		return $this->find($this->createQuery($intersection->getDocumentModel()->getName())->add(Restrictions::in("id", $ids)));
	}
	
	/**
	 * @param f_persistentdocument_criteria_QueryIntersection $intersection
	 * @return integer[]
	 */
	public function findIntersectionIds($intersection)
	{
		// TODO: merge queries that are "mergeable"
		// TODO: here we may have queries on different compatible models. Restrict queries to
		//		 the most specific model to reduce the number of returned ids to intersect?
		$idRows = null;
		foreach ($intersection->getQueries() as $groupedQuery)
		{
			if (f_util_ClassUtils::methodExists($groupedQuery, "getIds"))
			{
				$ids = $groupedQuery->getIds();
				$result = array();
				foreach ($ids as $id)
				{
					$result[] = array("id" => $id);
				}
			}
			else
			{
				$this->addIdProjectionIfNeeded($groupedQuery);
				//$result = $this->find($groupedQuery);
				$result = $groupedQuery->find();
			}
			if ($idRows === null)
			{
				$idRows = $result;
			}
			else
			{
				$idRows = array_uintersect($idRows, $result, array($this, "compareRows"));
			}
		}
	
		/* TODO: uncomment when order implemented
		if ($intersection->getMaxResults() > 0)
		{
			return array_slice(array_map(array($this, "getIdFromRow"), $idRows), 0 , $intersection->getMaxResults());
		}
		*/
		return array_map(array($this, "getIdFromRow"), $idRows);
	}
	
	private function compareRows($row1, $row2)
	{
		return (int)$row1["id"] - (int)$row2["id"];
	}
	
	private function getIdFromRow($row)
	{
		return $row["id"];
	}
	
	protected function addIdProjectionIfNeeded($groupedQuery)
	{
		$hasIdProjection = false;
		$hasThisProjection = false;
		$newProjections = array();
		if ($groupedQuery->hasProjection())
		{
			foreach ($groupedQuery->getProjection() as $projection)
			{
				if ($projection instanceof f_persistentdocument_criteria_ThisProjection)
				{
					// FIXME: remove other documentProjections ... ?
					$hasThisProjection = true;
					continue;
				}
				if ($projection instanceof f_persistentdocument_criteria_PropertyProjection)
				{
					if ($projection->getAs() == "id")
					{
						$hasIdProjection = true;
						// continue; // FIXME .. ?
					}
				}
				$newProjections[] = $projection;
			}
		}
		elseif ($groupedQuery->hasHavingCriterion() && !$groupedQuery->hasProjection())
		{
			// implicit this projection
			$hasThisProjection = true;
		}
	
		if (!$hasIdProjection)
		{
			if ($hasThisProjection || $groupedQuery->hasHavingCriterion())
			{
				$newProjections[] = Projections::groupProperty("id");
			}
			else
			{
				$newProjections[] = Projections::property("id");
			}
		}
		$groupedQuery->setProjectionArray($newProjections);
	}
	
	/**
	 * @param f_persistentdocument_criteria_QueryUnion $union
	 * @return integer[]
	 */
	public function findUnionIds($union)
	{
		// TODO: use UNION SQL operator
		$idRows = array();
		foreach ($union->getQueries() as $groupedQuery)
		{
			if (f_util_ClassUtils::methodExists($groupedQuery, "getIds"))
			{
				$ids = $groupedQuery->getIds();
				$newIdRows = array();
				foreach ($ids as $id)
				{
					$newIdRows[] = array("id" => $id);
				}
			}
			else
			{
				$this->addIdProjectionIfNeeded($groupedQuery);
				//$newIdRows = $this->find($groupedQuery);
				$newIdRows = $groupedQuery->find();
			}
			$idRows = array_merge($idRows, $newIdRows);
		}
	
		return array_unique(array_map(array($this, "getIdFromRow"), $idRows));
	}
	
	/**
	 * @param f_persistentdocument_criteria_QueryIntersection $union
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function findUnion($union)
	{
		$ids = $this->findUnionIds($union);
		if (count($ids) == 0)
		{
			return array();
		}
		return $this->find($this->createQuery($union->getDocumentModel()->getName())->add(Restrictions::in("id", $ids)));
	}
	
	/**
	 * Transform result
	 *
	 * @param array<array<String, mixed>> $rows
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 * @return array<mixed>
	 */
	protected function fetchProjection($rows, $query)
	{
		$names = $query->getDocumentProjections();
		$namesCount = count($names);
		if ($namesCount > 0)
		{
			$result = array();
			$i18nFieldNames = $this->getI18nFieldNames();
			foreach ($rows as $row)
			{
				foreach ($names as $name)
				{
					$i18NInfos = array();
					foreach ($i18nFieldNames as $i18nFieldName)
					{
						$i18NInfos[$i18nFieldName] = $row[$name. '_' . $i18nFieldName];
					}
					$row[$name] = $this->getDocumentInstanceWithModelName(intval($row[$name.'_id']), $row[$name.'_model'], $row[$name.'_treeid'], $i18NInfos);
				}
				$result[] = $row;
			}
			return $result;
		}
		return $rows;
	}
	
	/**
	 * Helper for '$this->find($query)[0]'
	 *
	 * @param f_persistentdocument_criteria_Query $query
	 * @return f_persistentdocument_PersistentDocument or null if no document was returned by find($query)
	 */
	public function findUnique($query)
	{
		if ($query->getMaxResults() != 1)
		{
			$query->setMaxResults(2);
		}
	
		$docs = $this->find($query);
		$nbDocs = count($docs);
		if ($nbDocs > 0)
		{
			if ($nbDocs > 1 && Framework::isWarnEnabled())
			{
				Framework::warn(get_class($this).'->findUnique() called while find() returned more than 1 results');
				if (Framework::isInfoEnabled())
				{
					Framework::info(f_util_ProcessUtils::getBackTrace());
				}
			}
			return $docs[0];
		}
		return null;
	}
	
	//
	// Tree Methods à usage du treeService
	//
	
	/**
	* @param integer $documentId
	* @param integer $treeId
	* @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	*/
	public abstract function getNodeInfo($documentId, $treeId);	

	/**
	 * @param integer[] $documentsId
	 * @param integer $treeId
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public abstract function getNodesInfo($documentsId, $treeId);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public abstract function getChildrenNodesInfo($node);


	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public abstract function getDescendantsNodesInfo($node, $deep = -1);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return integer[]
	 */
	public abstract function getChildrenId($node);


	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return integer[]
	 */
	public abstract function getDescendantsId($node);


	/**
	 * @param f_persistentdocument_PersistentTreeNode $rootNode
	 */
	public function createTree($rootNode)
	{
		$this->getSchemaManager()->createTreeTable($rootNode->getId());
		$this->insertNode($rootNode);
	}

	/**
	 * Suppression de tout l'arbre
	 * @param f_persistentdocument_PersistentTreeNode $rootNode
	 * @return integer[]
	 */
	public abstract function clearTree($rootNode);

	/**
	 * Ajoute un nouveau noeud
	 * @param f_persistentdocument_PersistentTreeNode $node
	 */
	protected abstract function insertNode($node);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode[] $nodes
	 */
	public abstract function orderNodes($parentNode, $nodes);


	/**
	 * Supression d'un noeud
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 */
	public abstract function deleteEmptyNode($treeNode);


	/**
	 * Supression d'une arboresence
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 * @return integer[]
	 */
	public abstract function deleteNodeRecursively($treeNode);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode $childNode
	 */
	public abstract function appendChildNode($parentNode, $childNode);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode $childNode
	 */
	public abstract function insertChildNodeAtOrder($parentNode, $childNode);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode $movedNode
	 * @param f_persistentdocument_PersistentTreeNode $destNode
	 * @return integer[]
	 */
	public abstract function moveNode($parentNode, $movedNode, $destNode);


	// Relation
	
	/**
	 * @param string $type
	 * @param integer $documentId1
	 * @param integer $documentId2
	 * @param string $documentModel1
	 * @param string $documentModel2
	 * @param string $name
	 * @return f_persistentdocument_PersistentRelation[]
	 */
	protected abstract function getRelations($type = null, $documentId1 = null, $documentId2 = null, $name = null, $documentModel1 = null, $documentModel2 = null);
	
	
	/**
	 * @param integer $masterDocumentId
	 * @param string $relationName
	 * @param string $slaveDocumentModel
	 * @return f_persistentdocument_PersistentRelation[]
	 */
	public function getChildRelationByMasterDocumentId($masterDocumentId, $relationName = null, $slaveDocumentModel = null)
	{
		return $this->getRelations("CHILD", $masterDocumentId, null, $relationName, null, $slaveDocumentModel);
	}
	
	/**
	 * @param integer $slaveDocumentId
	 * @param string $relationName
	 * @param string $masterDocumentModel
	 * @return f_persistentdocument_PersistentRelation[]
	 */
	public function getChildRelationBySlaveDocumentId($slaveDocumentId, $relationName = null, $masterDocumentModel = null)
	{
		return $this->getRelations("CHILD", null, $slaveDocumentId, $relationName, $masterDocumentModel, null);
	}
	
	/**
	 * @param integer $documentId1
	 * @param integer $documentId2
	 * @return boolean
	 */
	public function getChildRelationExist($documentId1, $documentId2)
	{
		if (count($this->getRelations(null, $documentId1, $documentId2, null, null, null)) > 0)
		{
			return true;
		}
		if (count($this->getRelations(null, $documentId2, $documentId1, null, null, null)) > 0)
		{
			return true;
		}
		return false;
	}	

	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @param integer $userId
	 * @return string|NULL
	 */
	public abstract function getUserSettingValue($packageName, $settingName, $userId);
	
	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @return string|NULL
	 */
	public function getSettingValue($packageName, $settingName)
	{
		return $this->getUserSettingValue($packageName, $settingName, 0);
	}

	/**
	 * @param string $value
	 * @param string $settingName
	 * @return string|NULL
	 */
	public abstract function getSettingPackage($value, $settingName);

	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @param integer $userId
	 * @param string|NULL $value
	 */
	public abstract function setUserSettingValue($packageName, $settingName, $userId, $value);
	

	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @param string|NULL $value
	 */
	public function setSettingValue($packageName, $settingName, $value)
	{
		$this->setUserSettingValue($packageName, $settingName, 0, $value);
	}


	// -------------------------------------------------------------------------
	// TAGS STUFF
	// -------------------------------------------------------------------------
	
	/**
	 * Return the tags affected to the document with ID $documentId.
	 * @internal use by TagService
	 * @param integer $documentId Id of the document the get the list of tags of.
	 * @return string[]
	 */
	public abstract function getTags($documentId);
	
	/**
	 * @return array<tag => array<id>>
	*/
	public abstract function getAllTags();
	
	/**
	 * @internal use by TagService
	 * @param string $tag
	 * @return array of documentid
	 */
	public abstract function getDocumentIdsByTag($tag);

	/**
	 * @internal use by TagService
	 *
	 * @param integer $documentId
	 * @param array $tags Array of string tag name (tolower)
	 * @param boolean $allTagsRequired
	 * @return boolean
	 */
	public abstract function hasTags($documentId, $tags, $allTagsRequired);

	/**
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 * @return boolean
	 */
	public abstract function hasTag($documentId, $tag);

	/**
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 * @return boolean
	 */
	public abstract function removeTag($documentId, $tag);


	/**
	 * Adds the tag $tag tag to the document with ID $documentId.
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 */
	public abstract function addTag($documentId, $tag);

	/**
	 * Return a translated text or null
	 * @param string $lcid
	 * @param string $id
	 * @param string $keyPath
	 * @return array[$content, $format]
	 */
	public abstract function translate($lcid, $id, $keyPath);

	/**
	 * @param string $lcid
	 * @param string $id
	 * @param string $keyPath
	 * @param string $content
	 * @param integer $useredited
	 * @param string $format [TEXT] | HTML
	 * @param boolean $forceUpdate
	 */
	public abstract function addTranslate($lcid, $id, $keyPath, $content, $useredited, $format = 'TEXT', $forceUpdate = false);
		
	/**
	 * @return array
	 */
	public abstract function getPackageNames();

		
	/**
	 * @return array
	 */
	public abstract function getUserEditedPackageNames();
	

	/**
	 * @param string $keyPath
	 * @return array['id' => string, 'lang' => string, 'content' => string, 'useredited' => integer, 'format' => string]
	 */
	public abstract function getPackageData($keyPath);
	
	/**
	 * @param string $keyPath
	 * @param string $id
	 * @param string $lcid
	 */
	public abstract function deleteI18nKey($keyPath, $id = null, $lcid = null);
	
	//I18nSynchro
	
	/**
	 * @param integer $id
	 * @param string $lang
	 * @param string $synchroStatus 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 * @param string|null $fromLang
	 */
	public abstract function setI18nSynchroStatus($id, $lang, $synchroStatus, $fromLang = null);
	
	/**
	 * @param integer $id
	 * @return array
	 * 		- 'fr'|'en'|'??' : array
	 * 			- status : 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 * 			- from : fr'|'en'|'??'|null
	*/
	public abstract function getI18nSynchroStatus($id);
	
	/**
	 * @return integer[]
	*/
	public abstract function getI18nSynchroIds();
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $pm
	 * @param integer $id
	 * @param string $lang
	 * @param string $fromLang
	*/
	public abstract function prepareI18nSynchro($pm, $documentId, $lang, $fromLang);
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $pm
	 * @param f_persistentdocument_I18nPersistentDocument $to
	*/
	public abstract function setI18nSynchro($pm, $to);
	
	/**
	 * @param integer $id
	 * @param string|null $lang
	*/
	public abstract function deleteI18nSynchroStatus($id, $lang = null);
		
	/**
	 * @param integer $documentId
	 * @return array<<nb_rules, website_id, website_lang>>
	 */
	public abstract function getUrlRewritingDocumentWebsiteInfo($documentId);

	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @return array<<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type>>
	 */
	public abstract function getUrlRewritingDocument($documentId, $lang, $websiteId);

	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 */
	public abstract function deleteUrlRewritingDocument($documentId, $lang, $websiteId);
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @return array<<nb_rules, website_id, website_lang>>
	 */
	public abstract function getUrlRewritingActionWebsiteInfo($moduleName, $actionName);

	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @param string $lang
	 * @param integer $websiteId
	 * @return array<<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type>>
	 */
	public abstract function getUrlRewritingAction($moduleName, $actionName, $lang, $websiteId);

	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @param string $lang
	 * @param integer $websiteId
	 */
	public abstract function deleteUrlRewritingAction($moduleName, $actionName, $lang, $websiteId);
	
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @return string from_url
	 */
	public abstract function getUrlRewriting($documentId, $lang, $websiteId = 0, $actionName = 'ViewDetail');

	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @return array<array<rule_id, origine, document_id, website_lang, website_id, from_url, to_url, redirect_type, modulename, actionname>>
	 */
	public abstract function getUrlRewritingInfo($documentId, $lang);
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @param string $fromURL
	 * @param string $toURL
	 * @param integer $redirectType
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $origine
	 */
	public abstract function setUrlRewriting($documentId, $lang, $websiteId, $fromURL, $toURL, $redirectType, $moduleName, $actionName, $origine = 0);
	
	/**
	 * @param integer $documentId
	 * @return integer count deleted rules
	 */
	public abstract function clearUrlRewriting($documentId);
	
	
	/**
	 * @param string $url
	 * @param integer $websiteId
	 * @param string $lang
	 * @return array<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, to_url, redirect_type>
	 */
	public abstract function getUrlRewritingInfoByUrl($url, $websiteId, $lang);

	/**
	 * @param string $url
	 * @param integer $websiteId
	 * @return array<rule_id, document_id, website_lang, website_id, to_url, redirect_type>
	 */
	public abstract function getPageForUrl($url, $websiteId = 0);

	//
	// Permission Section
	//

	/**
	 * Compile a user/groupAcl in f_permission_compiled.
	 *
	 * @param users_persistentdocument_userAcl | users_persistentdocument_groupAcl $acl
	 */
	public abstract function compileACL($acl);

	/**
	 * Remove all compiled acls for node $nodeId
	 *
	 * @param integer $nodeId
	 * @param string $packageName (ex: modules_website)
	 */
	public abstract function removeACLForNode($nodeId, $packageName = null);


	/**
	 * Permissions defined on $nodeId predicate
	 *
	 * @param integer $nodeId
	 * @return boolean
	 */
	public abstract function hasCompiledPermissions($nodeId);

	/**
	 * Permissions defined on $nodeId for $package predicate
	 *
	 * @param integer $nodeId
	 * @param string $packageName
	 * @return boolean
	 */
	public abstract function hasCompiledPermissionsForPackage($nodeId, $packageName);

	/**
	 * Checks the existence of a permission on a node for an array of accessors.
	 *
	 * @param array<Integer> $accessors
	 * @param string $fullPermName
	 * @param integer $nodeId
	 * @return boolean
	 */
	public abstract function checkCompiledPermission($accessors, $perm, $node);


	/**
	 * @param string $permission
	 * @param integer $nodeId
	 * @return array<Integer>
	 */
	public abstract function getAccessorsByPermissionForNode($permission, $nodeId);


	/**
	 * @param array<Integer> $accessorIds
	 * @param integer $nodeId
	 * @return array<String>
	 */
	public abstract function getPermissionsForUserByNode($accessorIds, $nodeId);


	public abstract function clearAllPermissions();
	
	/**
	 * Get the permission "Definition" points for tree $packageName (ex: modules_website).
	 *
	 * @param string $packageName
	 * @return Array<Integer>
	 */
	public abstract function getPermissionDefinitionPoints($packageName);

	/**
	 * @param string $url
	 * @return f_persistentdocument_I18PersistentDocument[]|null
	 */
	public abstract function getI18nWebsitesFromUrl($url);


	/**
	 * @param string $blockName
	 * @param array<String> $specs
	 * @param website_persistentdocument_page $page
	 */
	public abstract function registerSimpleCache($cacheId, $specs);

	/**
	 * @param string $pattern
	 */
	public abstract function getCacheIdsByPattern($pattern);

	/**
	 * @param string $date_entry
	 * @param integer $userId
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $documentId
	 * @param string $username
	 * @param string $serializedInfo
	 * @return integer
	 */
	public abstract function addUserActionEntry($date_entry, $userId, $moduleName, $actionName, $documentId, $username, $serializedInfo);

	/**
	 * @param integer $userId
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $documentId
	 * @return integer
	 */
	public abstract function getCountUserActionEntry($userId, $moduleName, $actionName, $documentId);

	/**
	 * @param integer $userId
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $documentId
	 * @param integer $rowIndex
	 * @param integer $rowCount
	 * @param string $sortOnField (date | user)
	 * @param string $sortDirection (ASC | DESC)
	 * @return array(array(entry_id, entry_date, user_id, document_id, module_name, action_name, info, link_id));
	 */
	public abstract function getUserActionEntry($userId, $moduleName, $actionName, $documentId, $rowIndex, $rowCount, $sortOnField, $sortDirection);

	/**
	 * @param string $fieldName (document | module | action | [user])
	 * @return array<array<distinctvalue => VALUE>>
	 */
	public abstract function getDistinctLogEntry($fieldName);


	/**
	 * @param string $date
	 * @param string|null $moduleName
	 */
	public abstract function deleteUserActionEntries($date, $moduleName = null);
	
	
	// Indexing function
	
	/** 
	 * @param integer $documentId
	 * @param array<status, lastupdate>
	 */
	public abstract function getIndexingDocumentStatus($documentId);

	/**
	 * @param integer $documentId
	 * @param string $newStatus
	 * @param string $lastUpdate
	 */
	public abstract function setIndexingDocumentStatus($documentId, $newStatus, $lastUpdate = null);

	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	public abstract function deleteIndexingDocumentStatus($documentId);

	
	/**
	 * @return integer
	 */
	public abstract function clearIndexingDocumentStatus();
	
	/**
	 * @return array<indexing_status =>, nb_document =>, max_id>
	 */
	public abstract function getIndexingStats();

	/**
	 * @return array<max_id => integer >
	 */
	public abstract function getIndexingPendingEntries();

	/** 
	 * @param integer $maxDocumentId
	 * @param integer $chunkSize
	 * @param integer[]
	 */
	public abstract function getIndexingDocuments($maxDocumentId, $chunkSize = 100);
	
	
	//DEPRECATED
	
	/**
	 * @deprecated
	 */
	public function clearFrameworkCacheByTTL($ttl)
	{
	}
	
	/**
	 * @deprecated
	 */
	public static function refresh()
	{
		$instance = self::getInstanceByClassName(get_called_class());
		$instance->closeConnection();
	}
	
	/**
	 * @deprecated
	 */
	public static function clearInstance()
	{
		self::clearInstanceByClassName(get_called_class());
	}
	
	/**
	 * @deprecated
	 */
	public abstract function setAutoCommit($bool);
	
	/**
	 * @deprecated
	 */
	protected function bindValue($statement, $data, $propertyInfo)
	{
		return $statement->bindPropertyValue($propertyInfo, $data);
	}
	
	/**
	 * @deprecated
	 */
	protected abstract function prepareStatement($sql);
	
	/**
	 * @deprecated
	 */
	public abstract function executeStatement($stmt);
	
	/**
	 * @deprecated
	 */
	protected abstract function getLastInsertId($tableName);
}