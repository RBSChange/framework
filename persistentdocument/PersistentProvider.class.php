<?php
/**
 * @deprecated
 */
class f_persistentdocument_PersistentProvider
{
	/**
	 * @var f_persistentdocument_PersistentProvider
	 */
	private static $instance;
			
	/**
	 * @var \Change\Db\Mysql\DbProvider
	 */
	private $wrapped;
	
	/**
	 * @var change_SchemaManager
	 */
	private $schemaManager;
	
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
	 * @var array
	*/
	protected $m_tmpRelation = array();
	
	/**
	 * Temporay identifier for new persistent document
	 * @var Integer
	*/
	protected $m_newInstancesCounter = 0;

	/**
	 * @deprecated
	 */
	protected function __construct()
	{
		$this->wrapped = \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
	}
	
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
		
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	public function isInCache($documentId)
	{
		return isset($this->m_documentInstances[intval($documentId)]);
	}
	
	/**
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getFromCache($documentId)
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
	 * @return void
	 */
	public function reset()
	{
		$this->clearDocumentCache();
	}
	
	/**
	 * @param boolean $useDocumentCache
	 */
	public function setDocumentCache($useDocumentCache)
	{
		if (!$useDocumentCache)
		{
			$this->clearDocumentCache();
		}
		return $this;
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
	 * @see f_persistentdocument_PersistentProvider::getDocumentInstanceIfExist()
	 */
	public function getDocumentInstanceIfExist($documentId)
	{
		if (!is_numeric($documentId) || $documentId <= 0)
		{
			return null;
		}
	
		$documentId = intval($documentId);
		if ($this->isInCache($documentId))
		{
			return $this->getFromCache($documentId);
		}
		return $this->getDocumentInstanceInternal($documentId);
	}
	
	public function getDocumentInstance($documentId, $modelName = null, $lang = null)
	{
		if (!is_numeric($documentId) || $documentId <= 0)
		{
			throw new Exception('Invalid document id: ' . $documentId);
		}
	
		$documentId = intval($documentId);
		if ($this->isInCache($documentId))
		{
			$document = $this->getFromCache($documentId);
		}
		else
		{
			$document = $this->getDocumentInstanceInternal($documentId);
			if ($document === null)
			{
				throw new Exception('Document "' . $documentId .'" not found');
			}
		}
		return $this->checkModelCompatibility($document, $modelName);
	}
	
	
	/**
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentDocument|NULL
	 */
	protected function getDocumentInstanceInternal($documentId)
	{
		$sql = 'SELECT `document_model`, `treeid`, `' . implode('`, `', $this->getI18nFieldNames()) . '` FROM `f_document` WHERE `document_id` = :document_id';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		if (!$result)
		{
			return null;
		}
		return $this->getDocumentInstanceWithModelName($documentId, $result['document_model'], $result['treeid'], $result);
	}	
	
	public function getDocumentModelName($id)
	{
		if (!is_numeric($id) || $id <= 0)
		{
			return false;
		}
		$documentId = intval($id);
		if ($this->isInCache($documentId))
		{
			return $this->getFromCache($documentId)->getDocumentModelName();
		}
	
		$sql = 'SELECT `document_model` FROM `f_document` WHERE `document_id` = :document_id';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($results) > 0)
		{
			return $results[0]['document_model'];
		}
		return false;
	}	
	

	/**
	 * Return the persistent document class name from the document model name
	 * @param string $modelName
	 * @return string
	 */
	protected function getDocumentClassFromModel($modelName)
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName)->getDocumentClassName();
	}
		
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
	 * Return the I18n persistent document class name from the document model name
	 * @param string $modelName
	 * @return string
	 */
	protected function getI18nDocumentClassFromModel($modelName)
	{
		return $this->getDocumentClassFromModel($modelName).'I18n';
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
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return integer
	 */
	public function getCachedDocumentId($document)
	{
		$id = $document->getId();
		if ($id < 0)
		{
			$this->putInCache($id, $document);
			$this->m_tmpRelation[$id] = $id;
		}
		return $id;
	}
	
	/**
	 * @param integer $cachedId
	 * @return f_persistentdocument_PersistentDocument
	 * @throws Exception
	 */
	public function getCachedDocumentById($cachedId)
	{
		if ($cachedId < 0)
		{
			$id = isset($this->m_tmpRelation[$cachedId]) ? $this->m_tmpRelation[$cachedId] : $cachedId;
			if ($this->isInCache($id))
			{
				return $this->getFromCache($id);
			}
			throw new Exception('document ' . $cachedId . '/'. $id . ' is not in memory');
		}
		return $this->getDocumentInstance($cachedId);
	}
	
	protected function setCachedRelation($cachedId, $documentId)
	{
		if (isset($this->m_tmpRelation[$cachedId]))
		{
			$this->m_tmpRelation[$cachedId] = $documentId;
		}
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
	 * @param f_persistentdocument_PersistentDocument $document
	 * @throws Exception
	 */
	public function loadDocument($document)
	{
		$sh = $this->getSqlMapping();
		$documentId = $document->getId();
		$model = $document->getPersistentModel();
		$table = $sh->getDbNameByModel($model);
		$fields = array();
		$i18nTable = null;
		foreach ($model->getPropertiesInfos() as $propertyName => $propertyInfos)
		{
			/* @var $propertyInfos PropertyInfo */
			if ($propertyInfos->getLocalized())
			{
				$fields[] = $sh->escapeName($sh->getDbNameByProperty($propertyInfos), 'i', $propertyName);
				if ($i18nTable === null) {$i18nTable = $sh->getDbNameByModel($model, true);}
			}
			else
			{
				$fields[] = $sh->escapeName($sh->getDbNameByProperty($propertyInfos), 'd', $propertyName);
			}
		}
		$sql = 'SELECT ' .implode(', ', $fields). ' FROM '. $sh->escapeName($table, null, 'd');
		if ($i18nTable)
		{
			$sql .= ' INNER JOIN '. $sh->escapeName($i18nTable, null, 'i'). ' USING(`document_id`)';
		}
		$sql .=  ' WHERE `d`.`document_id` = :document_id';
		if ($i18nTable)
		{
			$sql .=  ' AND `d`.`document_lang` = `i`.`lang_i18n`';
		}
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
		$this->executeStatement($stmt);
	
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
	
		if ($result)
		{
			$this->initDocumentFromDb($document, $result);
		}
		else
		{
			throw new Exception(get_class($this).'->loadDocument : could not load document[@id = '.$document->getId().']');
		}
	}
	
	/**
	 * Initialize un document avec une ligne de resultat de la base de donnée
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @param array $dbresult contient statement->fetch(PDO::FETCH_ASSOC)
	 */
	protected function initDocumentFromDb($persistentDocument, $dbresult)
	{
		$documentModel = $persistentDocument->getPersistentModel();
		$dbresult['id'] = intval($persistentDocument->getId());
	
		if ($documentModel->isLocalized())
		{
			//Utilisé pour initialiser l'entrée du cache
			$lang = $dbresult['lang'];
			if ($this->getI18nDocumentFromCache($persistentDocument, $lang) === null)
			{
				$i18nDoc = $this->buildI18nDocument($persistentDocument, $lang, $dbresult);
				$persistentDocument->setI18nVoObject($i18nDoc);
			}
		}
		$persistentDocument->setDocumentProperties($dbresult);
		$persistentDocument->setDocumentPersistentState(f_persistentdocument_PersistentDocument::PERSISTENTSTATE_LOADED);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $doc
	 * @param string $lang
	 * @return f_persistentdocument_I18PersistentDocument
	 */
	public function getI18nDocument($doc, $lang, $isVo = false)
	{
		$i18ndoc = $this->getI18nDocumentFromCache($doc, $lang);
		if ($i18ndoc !== null)
		{
			return $i18ndoc;
		}
		$documentId = $doc->getId();
		$sh = $this->getSqlMapping();
		$properties = null;
		if ($documentId > 0)
		{
			$model = $doc->getPersistentModel();
			$fields = array();
			$table = $sh->getDbNameByModel($model, true);
			foreach ($model->getPropertiesInfos() as $propertyName => $propertyInfos)
			{
				/* @var $propertyInfos PropertyInfo */
				if ($propertyInfos->getLocalized())
				{
					$fields[] = $sh->escapeName($sh->getDbNameByProperty($propertyInfos), 'i', $propertyName);
				}
			}
				
			$sql = 'SELECT ' .implode(', ', $fields). ' FROM '.$sh->escapeName($table, null, 'i') . ' WHERE `i`.`document_id` = :document_id  AND `i`.`lang_i18n` = :lang';
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $doc->getId(), PDO::PARAM_INT);
			$stmt->bindValue(':lang', $lang, PDO::PARAM_STR);
				
			$this->executeStatement($stmt);
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			return $this->buildI18nDocument($doc, $lang, ($result != false) ? $result : null);
		}
		return $this->buildI18nDocument($doc, $lang, null);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $doc
	 * @param string $lang
	 * @param array $result or null
	 * @return f_persistentdocument_I18nPersistentDocument
	 */
	protected function buildI18nDocument($doc, $lang, $result = null)
	{
		$documentId = intval($doc->getId());
		$model = $doc->getPersistentModel();
	
		$className = $this->getI18nDocumentClassFromModel($model->getName());
		$i18nDoc = new $className($documentId, $lang, $result === null);
	
		/* @var $i18nDoc f_persistentdocument_I18nPersistentDocument */
		if ($result !== null)
		{
			$i18nDoc->setDocumentProperties($result);
		}
		else
		{
			$i18nDoc->setDefaultValues();
		}
		$this->m_i18nDocumentInstances[$documentId][$lang] = $i18nDoc;
		return $i18nDoc;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public function insertDocument($persistentDocument)
	{
		$documentId = $this->getNewDocumentId($persistentDocument);
		$this->insertDocumentInternal($documentId, $persistentDocument);
		$this->putInCache($documentId, $persistentDocument);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @return integer
	 */
	protected function getNewDocumentId($persistentDocument)
	{
		$documentId = $persistentDocument->getId();
		$documentModel = $persistentDocument->getDocumentModelName();
		$documentLangs =  $persistentDocument->getI18nInfo()->toPersistentProviderArray();
		$i18nFieldNames = $this->getI18nFieldNames();
		if ($documentId <= 0)
		{
			$sql = 'INSERT INTO f_document (document_model, '. implode(', ', $i18nFieldNames) .') VALUES (:document_model, :'. implode(', :', $i18nFieldNames) .')';
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_model', $documentModel, PDO::PARAM_STR);
			foreach ($this->getI18nFieldNames() as $i18nFieldName)
			{
				$value  = isset($documentLangs[$i18nFieldName]) ? $documentLangs[$i18nFieldName] : NULL;
				$stmt->bindValue(':'.$i18nFieldName, $value, PDO::PARAM_STR);
			}
	
			$this->executeStatement($stmt);
			$documentId = $this->getLastInsertId($persistentDocument->getPersistentModel()->getTableName());
		}
		else
		{
			$sql = 'INSERT INTO f_document (document_id, document_model, '. implode(', ', $i18nFieldNames) .') VALUES (:document_id, :document_model, :'. implode(', :', $i18nFieldNames) .')';
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
			$stmt->bindValue(':document_model', $documentModel, PDO::PARAM_STR);
	
			foreach ($i18nFieldNames as $i18nFieldName)
			{
				$value  = isset($documentLangs[$i18nFieldName]) ? $documentLangs[$i18nFieldName] : NULL;
				$stmt->bindValue(':'.$i18nFieldName, $value, PDO::PARAM_STR);
			}
			$this->executeStatement($stmt);
		}
		return $documentId;
	}
	
	/**
	 * @param integer $documentId
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	protected function insertDocumentInternal($documentId, $persistentDocument)
	{
		$documentModel = $persistentDocument->getPersistentModel();
		$sh = $this->getSqlMapping();
		$table = $sh->getDbNameByModel($documentModel);
	
		$propertiesInfo = $documentModel->getPropertiesInfos();
		$properties = $persistentDocument->getDocumentProperties();
	
		$tmpId = $properties['id'];
		$properties['id'] = $documentId;
		$this->setCachedRelation($tmpId, $documentId);
	
		$properties['model'] = $persistentDocument->getDocumentModelName();
	
		if ($documentModel->isLocalized())
		{
			$this->m_i18nDocumentInstances[$documentId] = array();
			if (array_key_exists($tmpId, $this->m_i18nDocumentInstances))
			{
				foreach ($this->m_i18nDocumentInstances[$tmpId] as $i18nDocument)
				{
					$i18nDocument->setId($documentId);
					$this->m_i18nDocumentInstances[$documentId][$i18nDocument->getLang()] = $i18nDocument;
					if ($i18nDocument->isModified())
					{
						$this->insertI18nDocumentInternal($i18nDocument, $documentModel);
					}
				}
				unset($this->m_i18nDocumentInstances[$tmpId]);
			}
		}
	
		$fieldsName = array('`document_id`', '`document_model`');
		$parameters = array(':document_id', ':document_model');
	
		foreach ($propertiesInfo as $propertyName => $propertyInfo)
		{
			/* @var $propertyInfo PropertyInfo */
			if ('id' == $propertyName || 'model' == $propertyName)
			{
				continue;
			}
			$dbName = $sh->getDbNameByProperty($propertyInfo, false);
			$fieldsName[$propertyName] = $sh->escapeName($dbName);
			$parameters[$propertyName] = $sh->escapeParameterName($propertyName);
	
			if (is_array($properties[$propertyName]) && $propertyInfo->isDocument())
			{
				$properties[$propertyName] = $this->cascadeSaveDocumentArray($persistentDocument, $propertyName, $properties[$propertyName]);
			}
		}
	
		$sql = 'INSERT INTO `'.$table.'` (' . implode(', ', $fieldsName) .') VALUES (' . implode(', ', $parameters) .')';
		$stmt = $this->prepareStatement($sql);
	
		$dataRelations = array();
	
		$stmt->bindValue(':document_id', $properties['id'], PDO::PARAM_INT);
		$stmt->bindValue(':document_model', $properties['model'], PDO::PARAM_STR);
		$this->buildRelationDataAndBindValues($dataRelations, $propertiesInfo, $properties, $stmt);
	
		$this->executeStatement($stmt);
	
		$persistentDocument->updateId($documentId);
		$this->saveRelations($persistentDocument, $dataRelations);
	
		$persistentDocument->setDocumentPersistentState(f_persistentdocument_PersistentDocument::PERSISTENTSTATE_LOADED);
	}
	
	/**
	 * @param f_persistentdocument_I18nPersistentDocument $i18nDocument
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 */
	protected function insertI18nDocumentInternal($i18nDocument, $documentModel)
	{
		$sh = $this->getSqlMapping();
		$table = $sh->getDbNameByModel($documentModel, true);
	
		$fieldsName = array('`document_id`', '`lang_i18n`');
		$parameters = array(':id', ':lang');
		$properties = $i18nDocument->getDocumentProperties();
		foreach ($properties as $propertyName => $propertyValue)
		{
			$property = $documentModel->getProperty($propertyName);
			$dbName = $sh->getDbNameByProperty($property, true);
			$fieldsName[$propertyName] = $sh->escapeName($dbName);
			$parameters[$propertyName] = $sh->escapeParameterName($propertyName);
		}
	
		$sql = 'INSERT INTO `'.$table.'` (' . implode(', ', $fieldsName) .') VALUES (' . implode(', ', $parameters) .')';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':id', $i18nDocument->getId(), PDO::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PDO::PARAM_STR);
		foreach ($properties as $propertyName => $propertyValue)
		{
			$stmt->bindPropertyValue($documentModel->getProperty($propertyName) , $propertyValue);
		}
		$this->executeStatement($stmt);
		$this->setI18nSynchroStatus($i18nDocument->getId(), $i18nDocument->getLang(), 'MODIFIED');
		$i18nDocument->setIsPersisted();
	}
	
	/**
	 * Update a document.
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public function updateDocument($persistentDocument)
	{
		$documentId = $persistentDocument->getId();
		$documentModel = $persistentDocument->getPersistentModel();
		$sh = $this->getSqlMapping();
		if ($documentModel->isLocalized())
		{
			// Foreach i18documents that were loaded, do an update
			if (isset($this->m_i18nDocumentInstances[$documentId]))
			{
				foreach ($this->m_i18nDocumentInstances[$documentId] as $i18nDocument)
				{
					if ($i18nDocument->isNew())
					{
						//echo "NEW I18N";
						$this->insertI18nDocumentInternal($i18nDocument, $documentModel);
					}
					elseif ($i18nDocument->isModified())
					{
						//echo "I18N has been modified";
						$this->updateI18nDocumentInternal($i18nDocument, $documentModel);
					}
					else
					{
						//echo "Not new and not modified";
					}
				}
			}
		}
	
		if ($persistentDocument->isI18InfoModified())
		{
			//echo "I18INfo modified";
			// Update i18n information, only if modified
			$documentLangs = $persistentDocument->getI18nInfo()->toPersistentProviderArray();
				
			$sqlFields = array();
			foreach ($this->getI18nFieldNames() as $i18nFieldName)
			{
				$sqlFields[] = $i18nFieldName . ' = :' .$i18nFieldName;
			}
				
			$sql = 'UPDATE f_document SET ' . implode(', ', $sqlFields) . ' WHERE (document_id = :document_id)';
			$stmt = $this->prepareStatement($sql);
			foreach ($this->getI18nFieldNames() as $i18nFieldName)
			{
				$value = isset($documentLangs[$i18nFieldName]) ? $documentLangs[$i18nFieldName] : NULL;
				$stmt->bindValue(':'.$i18nFieldName, $value, PDO::PARAM_STR);
			}
	
			$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
			$this->executeStatement($stmt);
		}
	
		$propertiesInfo = $documentModel->getPropertiesInfos();
		$properties = $persistentDocument->getDocumentProperties(false);
		$mapping = array();
		$lobParameters = array();
	
		foreach ($properties as $propertyName => $propertyValue)
		{
			if ($propertyName == 'id' || $propertyName == 'model' || !$persistentDocument->isPropertyModified($propertyName))
			{
				continue;
			}
	
			$propertyInfo = $propertiesInfo[$propertyName];
			$mapping[$propertyName] = $sh->escapeName($sh->getDbNameByProperty($propertyInfo, false)) . " = " .  $sh->escapeParameterName($propertyName);
			if ($propertyInfo->isDocument() && is_array($propertyValue))
			{
				$properties[$propertyName] = $this->cascadeSaveDocumentArray($persistentDocument, $propertyName, $propertyValue);
			}
		}
	
		$dataRelations = array();
	
		if (count($mapping))
		{
			$sql = 'UPDATE '. $sh->escapeName($sh->getDbNameByModel($documentModel)) . ' SET ' . implode(', ', $mapping) . ' WHERE (`document_id` = :document_id)';
			$stmt = $this->prepareStatement($sql);
			$this->buildRelationDataAndBindValues($dataRelations, $propertiesInfo, $properties, $stmt, $mapping);
			$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
			$this->executeStatement($stmt);
		}
	
		$this->saveRelations($persistentDocument, $dataRelations);
		$persistentDocument->setDocumentPersistentState(f_persistentdocument_PersistentDocument::PERSISTENTSTATE_LOADED);
		$this->putInCache($documentId, $persistentDocument);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @param mixed[] $dataRelations
	 */
	private function saveRelations($persistentDocument, $dataRelations)
	{
		if (count($dataRelations))
		{
			foreach ($dataRelations as $propertyName => $relationValues)
			{
				$this->saveRelation($persistentDocument, $propertyName, $relationValues);
			}
		}
	}	
	

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $propertyName
	 */
	public function loadRelations($document, $propertyName)
	{
		$masterDocId = $document->getId();
		$relId = RelationService::getInstance()->getRelationId($propertyName);
	
		$stmt = $this->prepareStatement('SELECT `relation_id2` AS `id` FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id ORDER BY `relation_order`');
		$stmt->bindValue(':relation_id1', $masterDocId, PDO::PARAM_INT);
		$stmt->bindValue(':relation_id', $relId, PDO::PARAM_INT);
		$this->executeStatement($stmt);
		$result = $stmt->fetchAll(PDO::FETCH_NUM);
		return array_map(function($row) {return intval($row[0]);}, $result);
	}
	
	/**
	 * @param string $url
	 * @return f_persistentdocument_I18PersistentDocument[]|null
	 */
	public function getI18nWebsitesFromUrl($url)
	{
		$stmt = $this->prepareStatement('SELECT document_id, lang_i18n FROM m_website_doc_website_i18n WHERE url_i18n = :url');
		$stmt->bindValue(':url', $url, PDO::PARAM_STR);
	
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($results) > 0)
		{
			$ret = array();
			foreach ($results as $result)
			{
				$ret[] = $this->getI18nDocument($this->getDocumentInstance($result['document_id']), $result['lang_i18n']);
			}
		}
		else
		{
			$ret = null;
		}
		return $ret;
	}	
	
	/**
	 *
	 * @param f_persistentdocument_PersistentDocument $parentDocument
	 * @param string $propertyName
	 * @param mixed $relationValues
	 */
	private function saveRelation($parentDocument, $propertyName, $relationValues)
	{
		$masterDocId = $parentDocument->getId();
		$masterDocType = $parentDocument->getDocumentModelName();
		$relId = RelationService::getInstance()->getRelationId($propertyName);
	
		//Recuperation des nouvelles relations
	
		if ($relationValues === null || (is_array($relationValues) && count($relationValues) === 0))
		{
			if (!$parentDocument->isNew())
			{
				$stmt = $this->prepareStatement('DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id');
				$stmt->bindValue(':relation_id1', $masterDocId, PDO::PARAM_INT);
				$stmt->bindValue(':relation_id', $relId, PDO::PARAM_INT);
				$this->executeStatement($stmt);
			}
			return;
		}
		elseif (!is_array($relationValues))
		{
			$relationValues = array($relationValues);
		}
	
		//Recuperations des anciens document_id / order
		$oldIds = array();
		$stmt = $this->prepareStatement('SELECT `relation_id2` AS doc_id, `relation_order` AS doc_order FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id');
		$stmt->bindValue(':relation_id1', $masterDocId, PDO::PARAM_INT);
		$stmt->bindValue(':relation_id', $relId, PDO::PARAM_INT);
		$this->executeStatement($stmt);
		foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row)
		{
			$oldIds[$row[0]] = $row[1];
		}
	
		$oldCount = count($oldIds);
		$updateOrder = false;
		$order = 0;
		foreach ($relationValues as $subDocId)
		{
			if (isset($oldIds[$subDocId]))
			{
				if ($oldIds[$subDocId] != $order)
				{
					$relOrder = -$order - 1;
					$updateOrder = true;
					$stmt = $this->prepareStatement('UPDATE `f_relation` SET relation_order = :new_order WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id AND relation_order = :relation_order');
					$stmt->bindValue(':new_order', $relOrder, PDO::PARAM_INT);
					$stmt->bindValue(':relation_id1', $masterDocId, PDO::PARAM_INT);
					$stmt->bindValue(':relation_id', $relId, PDO::PARAM_INT);
					$stmt->bindValue(':relation_order', $oldIds[$subDocId], PDO::PARAM_INT);
					$this->executeStatement($stmt);
				}
				unset($oldIds[$subDocId]);
			}
			else
			{
				if ($order >= $oldCount)
				{
					$relOrder = $order;
				}
				else
				{
					$relOrder = -$order - 1;
					$updateOrder = true;
				}
				$subDocType = $this->getCachedDocumentById($subDocId)->getDocumentModelName();
				$stmt = $this->prepareStatement('INSERT INTO `f_relation` (relation_id1, relation_id2, relation_order, relation_name, document_model_id1, document_model_id2, relation_id) VALUES (:relation_id1, :relation_id2, :relation_order, :relation_name, :document_model_id1, :document_model_id2, :relation_id)');
				$stmt->bindValue(':relation_id1', $masterDocId, PDO::PARAM_INT);
				$stmt->bindValue(':relation_id2', $subDocId, PDO::PARAM_INT);
				$stmt->bindValue(':relation_order', $relOrder, PDO::PARAM_INT);
	
				$stmt->bindValue(':relation_name', $propertyName, PDO::PARAM_STR);
				$stmt->bindValue(':document_model_id1', $masterDocType, PDO::PARAM_STR);
				$stmt->bindValue(':document_model_id2', $subDocType, PDO::PARAM_STR);
				$stmt->bindValue(':relation_id', $relId, PDO::PARAM_INT);
				$this->executeStatement($stmt);
			}
			$order++;
		}
	
		if (count($oldIds) > 0)
		{
			//Delete old relation;
			foreach ($oldIds as $subDocId => $order)
			{
				$stmt = $this->prepareStatement('DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id AND relation_order = :relation_order');
				$stmt->bindValue(':relation_id1', $masterDocId, PDO::PARAM_INT);
				$stmt->bindValue(':relation_id', $relId, PDO::PARAM_INT);
				$stmt->bindValue(':relation_order', $order, PDO::PARAM_INT);
				$this->executeStatement($stmt);
			}
		}
	
		if ($updateOrder)
		{
			$stmt = $this->prepareStatement('UPDATE `f_relation` SET relation_order = -relation_order - 1 WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id AND relation_order < 0');
			$stmt->bindValue(':relation_id1', $masterDocId, PDO::PARAM_INT);
			$stmt->bindValue(':relation_id', $relId, PDO::PARAM_INT);
			$this->executeStatement($stmt);
		}
	}
	
	/**
	 * @param array $dataRelations
	 * @param array $propertiesInfo
	 * @param array $properties
	 * @param StatmentMysql $stmt
	 * @param array mapping
	 */
	private function buildRelationDataAndBindValues(&$dataRelations, $propertiesInfo, $properties, $stmt, $mapping = null)
	{
		foreach ($properties as $propertyName => $propertyValue)
		{
			/* @var $propertyInfo PropertyInfo */
			$propertyInfo = $propertiesInfo[$propertyName];
			if ('id' == $propertyName || 'model' == $propertyName)
			{
				continue;
			}
			if (!$propertyInfo->isDocument())
			{
				if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
				{
					$stmt->bindPropertyValue($propertyInfo, $propertyValue);
				}
			}
			else
			{
				if ($propertyInfo->isArray())
				{
					if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
					{
						$stmt->bindPropertyValue($propertyInfo, is_array($propertyValue) ? count($propertyValue) : intval($propertyValue));
					}
						
					if (is_array($propertyValue))
					{
						$dataRelations[$propertyName] = $propertyValue;
					}
					elseif (is_array($mapping) && array_key_exists($propertyName, $mapping))
					{
						$dataRelations[$propertyName] = null;
					}
				}
				else
				{
					if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
					{
						$stmt->bindPropertyValue($propertyInfo, intval($propertyValue) > 0 ? intval($propertyValue) : null);
					}
						
					if (intval($propertyValue) > 0)
					{
						$dataRelations[$propertyName] = intval($propertyValue);
					}
					elseif (is_array($mapping) && array_key_exists($propertyName, $mapping))
					{
						$dataRelations[$propertyName] = null;
					}
				}
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @param string $propertyName
	 * @param integer[] $documentIds
	 * @return integer[]
	 */
	private function cascadeSaveDocumentArray($persistentDocument, $propertyName, $documentIds)
	{
		$self = $this;
		$ids =  array_map(function ($documentId) use ($self) {
			$subDoc = $self->getCachedDocumentById($documentId);
			if ($subDoc->isNew() || $subDoc->isModified())
			{
				$subDoc->save();
			}
			return $subDoc->getId();
		}, $documentIds);
	
			$persistentDocument->setDocumentProperties(array($propertyName => $ids));
			return $ids;
	}
	
	
	/**
	 * @param f_persistentdocument_I18nPersistentDocument $i18nDocument
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 */
	protected function updateI18nDocumentInternal($i18nDocument, $documentModel)
	{
		$sh = $this->getSqlMapping();
		$i18nSuffix = $this->getI18nSuffix();
		$table = $sh->getDbNameByModel($documentModel, true);
		$properties = $i18nDocument->getDocumentProperties();
	
		$mapping = array();
	
		foreach ($properties as $propertyName => $propertyValue)
		{
			if (!$i18nDocument->isPropertyModified($propertyName))
			{
				continue;
			}
	
			$propertyInfo = $documentModel->getProperty($propertyName);
	
			if ($propertyInfo->isDocument())
			{
				// this should not be possible
				continue;
			}
			$mapping[$propertyName] = $sh->escapeName($sh->getDbNameByProperty($propertyInfo, true)) . ' = ' . $sh->escapeParameterName($propertyName);
		}
		$sql = 'UPDATE `'.$table.'` SET ' . implode(', ', $mapping) . ' WHERE `document_id` = :id AND `lang_i18n` = :lang';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':id', $i18nDocument->getId(), PDO::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PDO::PARAM_STR);
		foreach ($mapping as $propertyName => $tmp)
		{
			$stmt->bindPropertyValue($documentModel->getProperty($propertyName), $properties[$propertyName]);
		}
		$this->executeStatement($stmt);
		$this->setI18nSynchroStatus($i18nDocument->getId(), $i18nDocument->getLang(), 'MODIFIED');
		$i18nDocument->setIsPersisted();
	
		$this->m_i18nDocumentInstances[$i18nDocument->getId()][$i18nDocument->getLang()] = $i18nDocument;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public function deleteDocument($persistentDocument)
	{
		$documentId = $persistentDocument->getId();
		$lang = $persistentDocument->getLang();
	
		$documentModel = $persistentDocument->getPersistentModel();
	
		$deleteDocumentInstance = true;
		if ($documentModel->isLocalized())
		{
	
			$i18nm = RequestContext::getInstance();
			$contextLang = $i18nm->getLang();
			if (!$persistentDocument->isLangAvailable($contextLang))
			{
				//Le document n'existe pas dans la langue du context on ne fait rien
				return;
			}
	
			if ($i18nm->hasI18nSynchro())
			{
				//Suppression de toute les versions de lang synchronisé
				foreach ($this->getI18nSynchroStatus($documentId) as $stl => $stInfo)
				{
					if (isset($stInfo['from']) && $stInfo['from'] === $contextLang)
					{
						$i18nSyncDoc = $this->getI18nDocument($persistentDocument, $stl);
						$this->deleteI18nDocument($i18nSyncDoc, $documentModel);
						unset($this->m_i18nDocumentInstances[$documentId][$stl]);
						$persistentDocument->getI18nInfo()->removeLabel($stl);
					}
				}
			}
	
			$langCount = $persistentDocument->removeContextLang();
			$deleteDocumentInstance = ($langCount == 0);
	
			//On supprime physiquement la traduction
	
			$i18nDocument = $this->getI18nDocument($persistentDocument, $contextLang);
			$this->deleteI18nDocument($i18nDocument, $documentModel);
		}
	
		if (!$deleteDocumentInstance)
		{
			//Election d'une nouvelle VO
			$this->setI18nSynchroStatus($documentId, $persistentDocument->getLang(), 'MODIFIED');
			$this->updateDocument($persistentDocument);
		}
		else
		{
			if ($documentModel->hasCascadeDelete())
			{
				$persistentDocument->preCascadeDelete();
			}
	
			$table = $documentModel->getTableName();
			$sql = 'DELETE FROM `f_document` WHERE (`document_id` = :document_id)';
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
			$this->executeStatement($stmt);
	
			$deletedrow = $stmt->rowCount();
			if ($deletedrow != 0)
			{
				$sql =  'DELETE FROM `'.$table.'` WHERE (`document_id` = :document_id)';
				$stmt = $this->prepareStatement($sql);
				$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
				$this->executeStatement($stmt);
	
				$stmt = $this->prepareStatement('DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1');
				$stmt->bindValue(':relation_id1', $documentId, PDO::PARAM_INT);
				$this->executeStatement($stmt);
			}
			$this->clearUrlRewriting($documentId);
	
			$persistentDocument->setDocumentPersistentState(f_persistentdocument_PersistentDocument::PERSISTENTSTATE_DELETED);
	
			if ($documentModel->hasCascadeDelete())
			{
				$persistentDocument->postCascadeDelete();
			}
	
			$this->deleteFromCache($documentId);
		}
	}
	
	protected function deleteI18nDocument($i18nDocument, $documentModel)
	{
		$table = $documentModel->getTableName() . $this->getI18nSuffix();
		$stmt = $this->prepareStatement('DELETE FROM `'. $table . '` WHERE `document_id` = :id AND `lang_i18n` = :lang');
		$stmt->bindValue(':id', $i18nDocument->getId(), PDO::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PDO::PARAM_STR);
		$this->executeStatement($stmt);
		$this->deleteI18nSynchroStatus($i18nDocument->getId(), $i18nDocument->getLang());
		unset($this->m_i18nDocumentInstances[$i18nDocument->getId()][$i18nDocument->getLang()]);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param f_persistentdocument_PersistentDocument $destDocument
	 * @return f_persistentdocument_PersistentDocument the result of mutation (destDocument)
	 */
	public function mutate($document, $destDocument)
	{
		try
		{
			$this->beginTransaction();
			$id = $document->getId();
			$sourceModel = $document->getPersistentModel();
			$sourceModelName = $sourceModel->getName();
			$destModel = $destDocument->getPersistentModel();
			$destModelName = $destModel->getName();
	
			if ($sourceModel->getTableName() != $destModel->getTableName())
			{
				throw new IllegalOperationException('Unable to mutate document ' . $document->toString() . ' to ' . $destDocument->__toString());
			}
	
			// Update model name in f_framework table
			$stmt = $this->prepareStatement('UPDATE `f_document` SET `document_model` = :destmodelname WHERE `document_id` = :id AND `document_model` = :sourcemodelname');
			$stmt->bindValue(':destmodelname', $destModelName, PDO::PARAM_STR);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PDO::PARAM_STR);
			$this->executeStatement($stmt);
	
			// Update model name in f_relation table
			$stmt = $this->prepareStatement('UPDATE `f_relation` SET `document_model_id1` = :destmodelname WHERE `relation_id1` = :id AND `document_model_id1` = :sourcemodelname');
			$stmt->bindValue(':destmodelname', $destModelName, PDO::PARAM_STR);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PDO::PARAM_STR);
			$this->executeStatement($stmt);
	
			$stmt = $this->prepareStatement('UPDATE `f_relation` SET `document_model_id2` = :destmodelname WHERE `relation_id2` = :id AND `document_model_id1` = :sourcemodelname');
			$stmt->bindValue(':destmodelname', $destModelName, PDO::PARAM_STR);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PDO::PARAM_STR);
			$this->executeStatement($stmt);
	
			// Update model name in document table
			$tableName = $sourceModel->getTableName();
			$stmt = $this->prepareStatement('UPDATE `'.$tableName.'` SET `document_model` = :destmodelname WHERE `document_id` = :id AND `document_model` = :sourcemodelname');
			$stmt->bindValue(':destmodelname', $destModelName, PDO::PARAM_STR);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PDO::PARAM_STR);
			$this->executeStatement($stmt);
	
			// Delete i18n cache information
			if ($sourceModel->isLocalized())
			{
				$tmpId = $destDocument->getId();
				if (isset($this->m_i18nDocumentInstances[$tmpId]))
				{
					$array = $this->m_i18nDocumentInstances[$tmpId];
					unset($this->m_i18nDocumentInstances[$tmpId]);
					foreach ($array as $lang => $i18nObject)
					{
						$i18nObject->copyMutateSource($id, $this->m_i18nDocumentInstances[$id][$lang]);
						$this->m_i18nDocumentInstances[$id][$lang] = $i18nObject;
					}
				}
			}
	
			$this->deleteFromCache($id);
			$destDocument->copyMutateSource($document);
			$this->putInCache($id, $destDocument);
			$this->commit();
			return $destDocument;
		}
		catch (Exception $e)
		{
			$this->rollBack($e);
			// unrecoverable ...
			throw $e;
		}
	}	
	
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $pm
	 * @param integer $id
	 * @param string $lang
	 * @param string $fromLang
	 */
	public function prepareI18nSynchro($pm, $documentId, $lang, $fromLang)
	{
		$sh = $this->getSqlMapping();
		$tableName = $sh->getDbNameByModel($pm, true);
		$className = $this->getI18nDocumentClassFromModel($pm->getName());
		$fields = array();
		foreach ($pm->getPropertiesInfos() as $key => $propertyInfo)
		{
			if ($propertyInfo->getLocalized())
			{
				$fields[] =  $sh->escapeName($sh->getDbNameByProperty($propertyInfo, true), null, $key);
			}
		}
	
		$sql =  "SELECT ". implode(', ', $fields)." FROM ".$tableName." WHERE document_id = :document_id and lang_i18n = :lang";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
		$stmt->bindValue(':lang', $fromLang, PDO::PARAM_STR);
		$this->executeStatement($stmt);
		$fromResult = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
	
		$from = new $className($documentId, $fromLang, false);
		$from->setDocumentProperties($fromResult);
	
		$sql =  "SELECT `document_publicationstatus_i18n` AS `publicationstatus` FROM ".$tableName." WHERE document_id = :document_id and lang_i18n = :lang";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
		$stmt->bindValue(':lang', $lang, PDO::PARAM_STR);
		$this->executeStatement($stmt);
		$toResult = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		$isNew = true;
		if ($toResult)
		{
			$fromResult['publicationstatus'] = $toResult['publicationstatus'];
			$isNew = false;
		}
		$to = new $className($documentId, $lang, $isNew);
		$to->setDocumentProperties($fromResult);
	
		return array($from, $to);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $pm
	 * @param f_persistentdocument_I18nPersistentDocument $to
	 */
	public function setI18nSynchro($pm, $to)
	{
		$sh = $this->getSqlMapping();
		$tableName = $sh->getDbNameByModel($pm, true);
		$sql = "select * from ".$tableName." where document_id = :document_id and lang_i18n = :lang";
		$id = $to->getId();
		$lang = $to->getLang();
	
		$sqlInsert = array('`document_id`', '`lang_i18n`');
		$sqlValues =  array(':document_id' => $id, ':lang_i18n' => $lang);
		$sqlUpdate = array();
	
		foreach ($to->getDocumentProperties() as $propertyName => $value)
		{
			$property = $pm->getProperty($propertyName);
			$fieldName = $sh->getDbNameByProperty($property, true);
	
			$fn = $sh->escapeName($fieldName);
			$pn = $sh->escapeParameterName($propertyName);
				
			if ($propertyName === 'publicationstatus')
			{
				$sqlInsert[] = $fn;
				$sqlValues[$pn] = $value;
			}
			elseif ($propertyName !== 'correctionid')
			{
				$sqlInsert[] = $fn;
				$sqlValues[$pn] = $value;
				$sqlUpdate[] = $fn .' = VALUES(' . $fn . ')';
			}
		}
		$sql = 'INSERT INTO `'.$tableName.'` (' . implode(', ', $sqlInsert) .
		') VALUES (' . implode(', ', array_keys($sqlValues)) .
		') ON DUPLICATE KEY UPDATE' . implode(', ', $sqlUpdate);
	
		$stmt = $this->prepareStatement($sql);
		foreach ($sqlValues as $bn => $value)
		{
			$stmt->bindValue($bn, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		}
		$this->executeStatement($stmt);
		$this->m_i18nDocumentInstances[$id] = array();
	
		$sql = 'UPDATE `f_document` SET `label_' . $lang . '` = :label  WHERE (document_id = :document_id)';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':label', $sqlValues[$sh->escapeParameterName('label')], PDO::PARAM_STR);
		$stmt->bindValue(':document_id', $id, PDO::PARAM_INT);
		$this->executeStatement($stmt);
		$this->deleteFromCache($id);
	}
	
	
	/**
	 * @deprecated wrapped method
	 */
	public function __call($name, $args)
	{
		return call_user_func_array(array($this->wrapped, $name), $args);
	}
	
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
		throw new Exception("Unimplemented");
		//$instance = self::getInstance();
		//$instance->closeConnection();
	}
	
	/**
	 * @deprecated
	 */
	public static function clearInstance()
	{
		throw new Exception("Unimplemented");
	}
	
	/**
	 * @return change_SchemaManager
	 */
	public function getSchemaManager()
	{
		if ($this->schemaManager === null)
		{
			$this->schemaManager = new change_SchemaManager($this->wrapped);
		}
		return $this->schemaManager;
	}
	
	
	
	
	
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
	public function find($query)
	{
		if ($query->hasHavingCriterion() && !$query->hasProjection())
		{
			$query->setProjection(Projections::this());
		}
		$queryBuilder = new f_persistentdocument_QueryBuilderMysql($query);
		$queryStr = $queryBuilder->getQueryString();
		
		/* @var $statement \Change\Db\Mysql\Statment */
		$statement = $this->prepareStatement($queryStr);
		foreach ($queryBuilder->getParams() as $name => $value)
		{
			$statement->bindValue($name, $value);
		}
		$statement->execute();
		$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
	
		if (!$query->hasProjectionDeep())
		{
			$docs = array();
			$fetchMode = $query->getFetchMode();
			if ($fetchMode === QueryConstants::FETCH_MODE_LAZY)
			{
				foreach ($rows as $row)
				{
					$docs[] = $this->getDocumentInstanceWithModelName(intval($row['document_id']), $row['document_model'], $row['treeid'], $row);
				}
			}
			elseif ($fetchMode === QueryConstants::FETCH_MODE_DIRECT)
			{
				$isLocalized = $query->getDocumentModel()->isLocalized();
				foreach ($rows as $row)
				{
					$document = null;
					$documentId = intval($row['document_id']);
					if (!$this->isInCache($documentId))
					{
						$document = $this->getDocumentInstanceWithModelName($documentId, $row['document_model'], $row['treeid'], $row);
						$this->initDocumentFromDb($document, $row);
					}
					else
					{
						$document = $this->getFromCache($documentId);
					}
	
					$docs[] = $document;
				}
			}
	
			return $docs;
		}
		else
		{
			return $this->fetchProjection($rows, $query);
		}
	}
	
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
			if (method_exists($groupedQuery, 'getIds'))
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
			if (method_exists($groupedQuery, 'getIds'))
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
	 * @return f_persistentdocument_PersistentDocument|null if no document was returned by find($query)
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
			if ($nbDocs > 1)
			{
				Framework::warn(get_class($this).'->findUnique() called while find() returned more than 1 results');
			}
			return $docs[0];
		}
		return null;
	}
}


class f_persistentdocument_QueryBuilderMysql
{
	protected $i18nfieldNames;

	protected $fields = array();
	protected $distinctNeeded = false;
	protected $params = array();

	protected $modelCount = 0;
	protected $models = array();
	protected $modelsAlias = array();

	protected $from = array();
	protected $where = array();
	protected $order = array();

	protected $aliasCount = 0;
	protected $relationAliasCount = 0;
	protected $aliasByPath = array();
	protected $currentPropertyPath = array();
	protected $groupBy = array();
	protected $having = array();

	protected $junctions = array();
	protected $currentSqlJunction = null;

	protected $localizedTables = array();

	protected $treeTableName;
	protected $treeTableNameCurrentModelAlias;

	protected $firstResult = 0;
	protected $maxResults = -1;

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	public function __construct($query)
	{
		$model = $query->getDocumentModel();
		$this->pushModel($model);

		if ($model !== null)
		{
			$this->setFirstResult($query->getFirstResult());
			$this->setMaxResults($query->getMaxResults());

			$this->processProjections($query, $model);

			if ($query->hasCriterions())
			{
				$this->processCriterions($query);
			}
			if ($query->hasTreeCriterions())
			{
				$this->processTreeCriterions($query);
			}
			if ($query->hasCriterias())
			{
				$this->processCriterias($query);
			}
			if ($query->hasHavingCriterion())
			{
				$this->processHavingCriterion($query);
			}
			if ($query->hasOrders())
			{
				$this->processOrders($query);
			}
		}
		else
		{
			$this->setFirstResult($query->getFirstResult());
			$this->setMaxResults($query->getMaxResults());

			$this->processProjections($query, null);

			if ($query->hasCriterions())
			{
				$this->processCriterions($query);
			}
			if ($query->hasTreeCriterions())
			{
				$this->processTreeCriterions($query);
			}
			if ($query->hasOrders())
			{
				$this->processOrders($query);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @return string
	 */
	public function getQueryString()
	{
		$query = array('SELECT ');
		if ($this->distinctNeeded)
		{
			$query[] = 'DISTINCT ';
		}
		$query[] = implode(', ', $this->fields);
		$query[] = ' FROM ';
		$query[] = implode(' ', $this->from);

		if ($this->treeTableName !== null && empty($this->order) && $this->maxResults != 2)
		{
			$query[] = ' INNER JOIN '. $this->treeTableName.' as treeOrder on '.$this->treeTableNameCurrentModelAlias.'.document_id = treeOrder.document_id';
			$this->order[] = 'treeOrder.node_level, treeOrder.node_order';
		}

		if (count($this->where))
		{
			$query[] = ' WHERE '. implode(' AND ', $this->where);
		}
		if (count($this->groupBy))
		{
			$query[] = ' GROUP BY '.implode(', ', $this->groupBy);
		}
		if (count($this->having))
		{
			$query[] = ' HAVING '.implode(' AND ', $this->having);
		}
		if (count($this->order))
		{
			$query[] = ' ORDER BY '.implode(', ', $this->order);
		}
		if ($this->maxResults != -1)
		{
			$query[] = ' LIMIT '. $this->firstResult . ', ' . $this->maxResults;
		}
		return implode('', $query);
	}

	/**
	 * @param integer $firstResult
	 */
	protected  function setFirstResult($firstResult)
	{
		$this->firstResult = $firstResult;
	}

	/**
	 * @param integer $maxResults
	 */
	protected function setMaxResults($maxResults)
	{
		$this->maxResults = $maxResults;
	}

	protected function setTreeTableName($tableAlias, $currentModelAlias)
	{
		if ($this->treeTableName === null)
		{
			$this->treeTableName = $tableAlias;
			$this->treeTableNameCurrentModelAlias = $currentModelAlias;
		}
	}

	/**
	 * @param string $propertyName
	 * @param mixed $value
	 * @return string
	 */
	protected function addParam($propertyName, $value)
	{
		$key = ':p'.(count($this->params)+1);
		$this->params[$key] = $this->translateValue($propertyName, $value);
		return $key;
	}

	/**
	 * @param string $sql
	 */
	protected function addHaving($sql)
	{
		$this->having[] = $sql;
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	protected function pushModel($model, $propertyName = null)
	{
		if (!is_null($model))
		{
			$this->models[] = $model;
			$this->modelCount++;
			$this->aliasCount++;
			$this->modelsAlias[] = $this->getTableAlias();

			if (!is_null($propertyName))
			{
				$this->currentPropertyPath[] = $propertyName;
				$this->aliasByPath[join('.', $this->currentPropertyPath)] = $this->getTableAlias();
			}
		}
	}

	protected function newTableAlias()
	{
		$this->aliasCount++;
		return $this->getTableAlias();
	}

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function popModel()
	{
		$this->modelCount--;
		array_pop($this->currentPropertyPath);
		array_pop($this->modelsAlias);

		return array_pop($this->models);
	}

	protected function beginJunction($junction)
	{
		$this->currentSqlJunction = (object)array('op' => $junction->getOp(), 'where' => array());
		$this->junctions[] = $this->currentSqlJunction;
	}

	protected function endJunction()
	{
		$sqlJunction = array_pop($this->junctions);
		if (!empty($this->junctions))
		{
			$this->currentSqlJunction = end($this->junctions);
		}
		else
		{
			$this->currentSqlJunction = null;
		}

		$this->addWhere('(' . join(' '.$sqlJunction->op.' ', $sqlJunction->where) . ')');
	}

	/**
	 * @param string $field
	 */
	protected function addField($field)
	{
		// avoid duplicate field using $field as key
		$this->fields[$field] = $field;
	}

	/**
	 * @param string $from
	 */
	protected function addFrom($from)
	{
		$this->from[] = $from;
	}

	/**
	 * @param string $where
	 */
	protected function addWhere($where)
	{
		if (is_null($this->currentSqlJunction))
		{
			$this->where[] = $where;
		}
		else
		{
			$this->currentSqlJunction->where[] = $where;
		}
	}

	/**
	 * @param string $groupBy
	 */
	protected function addGroupBy($groupBy)
	{
		$this->groupBy[] = $groupBy;
	}

	/**
	 * @param Order $order
	 */
	protected function addOrder($order)
	{
		$orderStr = '';
		$propertyName = $order->getPropertyName();
		if (strpos($propertyName, '.') !== false)
		{
			$propInfo = explode(".", $propertyName);
			$lastPropName = array_pop($propInfo);
			if ($this->modelCount == 0)
			{
				throw new Exception("Could not resolve $propertyName. Did you made any criteria ?");
			}
			$model = $this->getModel();
			foreach ($propInfo as $propName)
			{
				$prop = $model->getProperty($propName);
				if ($prop === null)
				{
					$prop = $model->getInverseProperty($propName);
				}
				if ($prop === null || !$prop->isDocument())
				{
					throw new Exception("$propName is not a document property");
				}
				$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($prop->getDocumentType());
			}
			if ($model->hasProperty($lastPropName))
			{
				$columnName = $model->getProperty($lastPropName)->getDbMapping();
			}
			else
			{
				$columnName = $lastPropName;
			}

			$tablePropName = $this->getTablePropertyName($propertyName);
			if (!isset($this->aliasByPath[$tablePropName]))
			{
				throw new Exception("Could not resolve $tablePropName. Did you made a criteria on it ?");
			}
			$orderStr = $this->aliasByPath[$tablePropName].'.'.$columnName;
		}
		else
		{
			if ($this->modelCount == 0)
			{
				$tableAlias = 't0';
				$model = f_persistentdocument_PersistentDocumentModel::getInstance("generic", "Document");
			}
			else
			{
				// FIXME: using statically t1 as table alias ... but is it *really* a problem ?
				$tableAlias = 't1';
				$model = $this->getModel();
			}
			$propertyName = $order->getPropertyName();
			if ($model->hasProperty($propertyName))
			{
				$columnName = $model->getProperty($propertyName)->getDbMapping();
			}
			else
			{
				$columnName = $propertyName;
			}
			$orderStr = $tableAlias.'.'.$columnName;
		}
		if ($order->getIgnorecase())
		{
			$orderStr = "lower(".$orderStr.")";
		}
		if (!$order->getAscending())
		{
			$orderStr .= ' desc';
		}
		$this->order[] = $orderStr;
	}

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getModel()
	{
		return $this->models[$this->modelCount-1];
	}

	protected function getModelAlias()
	{
		return ($this->modelCount == 0) ? 't0' : $this->modelsAlias[$this->modelCount-1];
	}

	/**
	 * @return string
	 */
	protected function getTableAlias()
	{
		return 't'.$this->aliasCount;
	}

	/**
	 * If called, a "DISTINCT" selector will be added
	 */
	protected function distinctNeeded()
	{
		$this->distinctNeeded = true;
	}

	protected function newRelation()
	{
		$this->relationAliasCount++;
	}

	protected function getRelationAlias()
	{
		return 'r'.$this->relationAliasCount;
	}

	protected function getComponentDbMapping($propertyName)
	{
		if ($this->modelCount == 0)
		{
			return $propertyName;
		}
		else
		{
			return $this->getModel()->getProperty($propertyName)->getDbMapping();
		}
	}

	protected function getQualifiedColumnName($propertyName)
	{
		if ($this->modelCount == 0)
		{
			return $this->getModelAlias() . '.' . $propertyName;
		}

		$model = $this->getModel();
		$property = $model->getProperty($propertyName);
		if ($property === NULL)
		{
			throw new Exception('Invalid property name : '.$propertyName);
			// TODO ...
			$property = $model->getInverseProperty($propertyName);
			if ($property === NULL)
			{
				throw new Exception('Invalid property name : '.$propertyName);
			}
		}

		if ($property->isLocalized())
		{
			$this->checkLocalizedTable($model);
			$qName = 'l' . $this->getModelAlias() . '.' . $property->getDbMapping() . $this->getI18nSuffix();
		}
		else
		{
			$qName = $this->getModelAlias() . '.' . $property->getDbMapping();
		}

		return $qName;
	}

	protected function getI18nSuffix()
	{
		return '_i18n';
	}

	/**
	 * @return string[]
	 */
	public function getI18nFieldNames()
	{
		if ($this->i18nfieldNames === null)
		{
			$array = array('lang_vo');
			foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang)
			{
				$array[] = 'label_'.$lang;
			}
			$this->i18nfieldNames = $array;
		}
		return $this->i18nfieldNames;
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	protected function checkLocalizedTable($model)
	{

		$tableAlias = $this->getModelAlias();
		if (!array_key_exists($tableAlias, $this->localizedTables))
		{
			$table  = $model->getTableName() . $this->getI18nSuffix();
			$localizedTableAlias = 'l'. $tableAlias;
			$lang  = RequestContext::getInstance()->getLang();

			$from = 'inner join ' . $table . ' ' . $localizedTableAlias . ' on '
				. $tableAlias .'.document_id = ' . $localizedTableAlias .'.document_id and '
					. $localizedTableAlias .'.lang_i18n = \'' . $lang . '\'';

			$this->addFrom($from);
			$this->localizedTables[$tableAlias] = $table;
		}
	}

	/**
	 *
	 * @param string $propertyName
	 * @param mixed $value
	 * @return mixed
	 */
	protected function translateValue($propertyName, $value)
	{
		return $value;
	}

	/**
	 * @param string $propertyName
	 * @return string
	 */
	protected function getTablePropertyName($propertyName)
	{
		$lastIndex = strrpos($propertyName, '.');
		return substr($propertyName, 0, $lastIndex);
	}

	/**
	 * @param string $propertyName
	 * @return string
	 */
	protected function getRelativePropertyName($propertyName)
	{
		$lastIndex = strrpos($propertyName, '.');
		return substr($propertyName, $lastIndex+1);
	}

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 * @return array<String>
	 */
	protected function processHavingCriterion($query)
	{
		foreach ($query->getHavingCriterion() as $havingCriterion)
		{
			if ($havingCriterion instanceof f_persistentdocument_criteria_HavingSimpleExpression)
			{
				$propName = $this->resolveHavingCriterionPropName($havingCriterion);
				$paramKey = $this->addParam($propName, $havingCriterion->getValue());
				$sql = $propName." ".$havingCriterion->getOp()." ".$paramKey;
			}
			elseif ($havingCriterion instanceof f_persistentdocument_criteria_HavingBetweenExpression)
			{
				$propName = $this->resolveHavingCriterionPropName($havingCriterion);
				$minKey = $this->addParam($propName, $havingCriterion->getMin());
				$maxKey = $this->addParam($propName, $havingCriterion->getMax());
				if ($havingCriterion->isStrict())
				{
					$sql = "(".$propName." >= ".$minKey." and ".$propName." < ".$maxKey.")";
				}
				else
				{
					$sql = "(".$propName." between ".$minKey." and ".$maxKey.")";
				}
			}
			elseif ($havingCriterion instanceof f_persistentdocument_criteria_HavingInExpression)
			{
				$propName = $this->resolveHavingCriterionPropName($havingCriterion);
				$paramKey = $this->addParam($propName, $havingCriterion->getValues());
				$sql = $propName.(($havingCriterion->getNot())?" not": "")." in ".$paramKey;
			}
			else
			{
				throw new Exception("Unsupported havingCriterion ".get_class($havingCriterion));
			}

			$this->addHaving($sql);
		}
	}

	/**
	 * @param f_persistentdocument_criteria_HavingCriterion $havingCriterion
	 * @return string
	 */
	protected function resolveHavingCriterionPropName($havingCriterion)
	{
		$propName = $havingCriterion->getPropertyName();
		if (is_string($propName))
		{
			return $propName;
		}
		if ($propName instanceof f_persistentdocument_criteria_RowCountProjection)
		{
			return "count(distinct ".$this->getTableAlias().".document_id)";
		}
		if ($propName instanceof f_persistentdocument_criteria_DistinctCountProjection)
		{
			$columnName = $this->getQualifiedColumnName($propName->getPropertyName());
			return 'count(distinct ' . $columnName .')';
		}
		if ($propName instanceof f_persistentdocument_criteria_OperationProjection)
		{
			$columnName = $this->getQualifiedColumnName($propName->getPropertyName());
			return $propName->getOperation() . '(' . $columnName .')';
		}
	}

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	protected function processProjections($query, $model)
	{
		if ($model === null)
		{
			$this->addFrom('f_document t0');
		}
		elseif (!$query->hasParent())
		{
			$this->addFrom($model->getTableName().' '.$this->getModelAlias() .' inner join f_document t0 using(document_id)');
		}

		if (!$query->hasProjection())
		{
			$this->addField('t0.document_id');
			$this->addField('t0.document_model');
			$this->addField('t0.treeid');
			foreach ($this->getI18nFieldNames() as $i18nFieldName)
			{
				$this->addField('t0.' . $i18nFieldName);
			}
			if ($query->getFetchMode() === QueryConstants::FETCH_MODE_DIRECT)
			{
				// TODO: explicit field filter (field by field)
				$this->addField($this->getModelAlias().'.*');
			}
			return;
		}

		$subdoc = 0;
		foreach ($query->getProjection() as $projection)
		{
			if ($projection instanceof f_persistentdocument_criteria_RowCountProjection)
			{
				$this->addField('count(distinct '.$this->getTableAlias().'.document_id) as ' . $projection->getAs());
			}
			else if ($projection instanceof f_persistentdocument_criteria_DistinctCountProjection)
			{
				$columnName = $this->getQualifiedColumnName($projection->getPropertyName());
				$this->addField('count(distinct ' . $columnName .') as ' . $projection->getAs());
			}
			else if ($projection instanceof f_persistentdocument_criteria_OperationProjection)
			{
				$columnName = $this->getQualifiedColumnName($projection->getPropertyName());
				$this->addField($projection->getOperation() . '(' . $columnName .') as ' . $projection->getAs());
			}
			else if ($projection instanceof f_persistentdocument_criteria_ThisProjection)
			{
				$as = f_persistentdocument_criteria_ThisProjection::NAME;
				$query->addDocumentProjection($as);
				if ($query->hasParent())
				{
					throw new Exception("Can not handle ThisProjection on a criteria");
				}
				$alias = "t0";
				$this->addField($alias . '.document_id as '.$as.'_id');
				$this->addField($alias . '.document_model as '.$as.'_model');
				$this->addField($alias . '.treeid as ' . $as . '_treeid');
				foreach ($this->getI18nFieldNames() as $i18nFieldName)
				{
					$this->addField($alias . '.' . $i18nFieldName.' as '.$as.'_'.$i18nFieldName);
				}
				$this->addGroupBy($as . '_id');
			}
			else if ($projection instanceof f_persistentdocument_criteria_PropertyProjection)
			{
				$propNameInfo = explode(".", $projection->getPropertyName());
				$propNameInfoCount = count($propNameInfo);
				$property = $this->getModel()->getProperty($propNameInfo[0]);
				if ($property === null)
				{
					throw new Exception('Property [' . $propNameInfo[0] . '] not found on document: ' . $this->getModel()->getName());
				}
				if ($property->isDocument())
				{
					$relationAlias = 'ra' . $subdoc;
					$documentalias = 'sd' . $subdoc;

					if ($propNameInfoCount == 1)
					{
						$query->addDocumentProjection($projection->getAs());
						$this->addField($documentalias . '.document_id as ' . $projection->getAs() . '_id');
						$this->addField($documentalias . '.document_model as ' . $projection->getAs() . '_model');
						$this->addField($documentalias . '.treeid as ' . $projection->getAs() . '_treeid');

						foreach ($this->getI18nFieldNames() as $i18nFieldName)
						{
							$this->addField($documentalias . '.' . $i18nFieldName . ' as ' . $projection->getAs() . '_' . $i18nFieldName);
						}
							
						$documentTableName = "f_document";
					}
					elseif ($propNameInfoCount == 2)
					{
						$projectionModel = $property->getDocumentModel();
						$subProperty = $projectionModel->getProperty($propNameInfo[1]);
						$subPropertyDbMapping = $subProperty->getDbMapping();
						$documentTableName = $projectionModel->getTableName();
						if ($subProperty->isLocalized())
						{
							$documentTableName .= $this->getI18nSuffix();
							$subPropertyDbMapping .= $this->getI18nSuffix();
							$this->addWhere($documentalias.'.lang_i18n = \'' . RequestContext::getInstance()->getLang() . '\'');
						}
							
						$this->addField($documentalias.'.'.$subPropertyDbMapping.' as '.$projection->getAs());
					}
					else
					{
						throw new Exception("Unsupported nested projection count (> 1): ".$projection->getPropertyName());
					}

					if ($property->isArray())
					{
						$this->addFrom('inner join f_relation '.$relationAlias.' on '.$relationAlias.'.relation_id1 = t0.document_id');
						$this->addFrom('inner join '.$documentTableName.' '.$documentalias.' on '.$documentalias.'.document_id = '.$relationAlias.'.relation_id2');
					}
					else
					{
						$columnName = $this->getQualifiedColumnName($projection->getPropertyName());
						$this->addFrom('inner join '.$documentTableName.' '.$documentalias.' on '.$documentalias.'.document_id = '.$columnName);
					}

					if ($projection->getGroup())
					{
						if ($propNameInfoCount == 1)
						{
							$this->addGroupBy($documentalias . '.document_id');
							$this->addGroupBy($documentalias . '.document_model');
							$this->addGroupBy($documentalias . '.treeid');
							foreach ($this->getI18nFieldNames() as $i18nFieldName)
							{
								$this->addGroupBy($documentalias . '.' . $i18nFieldName);
							}
						}
						else
						{
							// TODO
							throw new Exception("Unsupported operation: group");
						}
					}

					$subdoc++;
				}
				else
				{
					$columnName = $this->getQualifiedColumnName($projection->getPropertyName());
					$this->addField($columnName .' as ' . $projection->getAs());
					if ($projection->getGroup())
					{
						$this->addGroupBy($columnName);
					}
				}
			}
		}
	}

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	protected function processCriterias($query)
	{
		$currentTableAlias = $this->getModelAlias();
		foreach ($query->getCriterias() as $propertyName => $criteria)
		{
			$this->processCriteria($propertyName, $criteria, $currentTableAlias, $query);
		}
	}

	/**
	 * @param string $propertyName
	 * @param f_persistentdocument_criteria_ExecutableQuery $criteria
	 * @param string $currentTableAlias
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	protected function processCriteria($propertyName, $criteria, $currentTableAlias, $query)
	{
		$inverseProperty = $criteria->getInverseQuery();
		$subModel = $criteria->getDocumentModel();
		$this->pushModel($subModel, $propertyName);
		$subTableAlias = $this->getModelAlias();

		$currentModel = $query->getDocumentModel();
		$propertyInfo = $currentModel->getProperty($propertyName);
		if (is_null($propertyInfo))
		{
			if ($currentModel->hasInverseProperty($propertyName))
			{
				$propertyInfo = $currentModel->getInverseProperty($propertyName);
			}
			else
			{
				$propertyInfo = $subModel->getProperty($propertyName);
			}
			$inverseProperty = true;
		}
		$join = $criteria->getLeftJoin() ? 'left outer join ' : 'inner join ';
		if ($propertyInfo->getMaxOccurs() == 1)
		{
			// mono-valued property
			if ($inverseProperty)
			{
				$this->distinctNeeded();
				$this->addFrom($join.$subModel->getTableName().' '.$subTableAlias.' on '.$currentTableAlias.'.document_id = '.$subTableAlias.'.'.$propertyInfo->getDbMapping());
			}
			else
			{
				$this->addFrom($join.$subModel->getTableName().' '.$subTableAlias.' on '.$subTableAlias.'.document_id = '.$currentTableAlias.'.'.$propertyInfo->getDbMapping());
			}
		}
		else
		{
			// multi-valued property
			$this->distinctNeeded();

			$this->newRelation();
			$relationAlias = $this->getRelationAlias();

			if ($inverseProperty)
			{
				$relation_id = RelationService::getInstance()->getRelationId($propertyInfo->getDbMapping());
				$this->addFrom($join.'f_relation '.$relationAlias.' on '.$relationAlias.'.relation_id2 = '.$currentTableAlias.'.document_id AND '.$relationAlias.'.relation_id = '.$relation_id);
				$this->addFrom($join.$subModel->getTableName().' '.$subTableAlias.' on '.$subTableAlias.'.document_id = '.$relationAlias.'.relation_id1');
			}
			else
			{
				$relation_id = RelationService::getInstance()->getRelationId($propertyName);
				$this->addFrom($join.'f_relation '.$relationAlias.' on '.$relationAlias.'.relation_id1 = '.$currentTableAlias.'.document_id AND '.$relationAlias.'.relation_id = '.$relation_id);
				$this->addFrom($join.$subModel->getTableName().' '.$subTableAlias.' on '.$subTableAlias.'.document_id = '.$relationAlias.'.relation_id2');
			}

		}

		if ($criteria->hasProjection())
		{
			$this->processProjections($criteria, $this->getModel());
		}

		$this->processTreeCriterions($criteria);
		$this->processCriterions($criteria);

		if ($criteria->hasCriterias())
		{
			$this->processCriterias($criteria);
		}
		$this->popModel();
	}

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	protected function processCriterions($query)
	{
		foreach ($query->getCriterions() as $criterion)
		{
			$this->processCriterion($criterion, $query);
		}
	}

	/**
	 * @param f_persistentdocument_criteria_Criterion $criterion
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	protected function processCriterion($criterion, $query)
	{
		if ($criterion instanceof f_persistentdocument_criteria_HasTagExpression)
		{
			$currentAlias = $this->getModelAlias();
			$tagAlias = $this->newTableAlias();
			$this->addFrom('inner join f_tags '.$tagAlias.' on '.$currentAlias.'.document_id = '.$tagAlias.'.id');
			$pAlias = $this->addParam('tag', $criterion->getTagName());
			$this->addWhere($tagAlias.'.tag = '.$pAlias);
			return;
		}

		if ($criterion instanceof f_persistentdocument_criteria_IsTaggedExpression)
		{
			$this->distinctNeeded();
			$currentAlias = $this->getModelAlias();
			$tagAlias = $this->newTableAlias();
			$this->addFrom('inner join f_tags '.$tagAlias.' on '.$currentAlias.'.document_id = '.$tagAlias.'.id');
			return;
		}

		if ($criterion instanceof f_persistentdocument_criteria_Junction)
		{
			$this->beginJunction($criterion);
			$subCriterions = $criterion->getCriterions();
			foreach ($subCriterions as $subcriterion)
			{
				if ($subcriterion instanceof f_persistentdocument_criteria_TreeCriterion)
				{
					$this->processTreeCriterion($subcriterion, $query);
				}
				else if ($subcriterion instanceof f_persistentdocument_criteria_Criterion || $subcriterion instanceof f_persistentdocument_criteria_Junction)
				{
					$this->processCriterion($subcriterion, $query);
				}
				else
				{
					throw new Exception('Invalide type : '.get_class($subcriterion) .', Criterion expected');
				}
			}
			$this->endJunction();
			return;
		}

		$property = $criterion->popPropertyName();
		if ($property !== null)
		{
			list($relationName, $c) = $query->createSubCriteria($property);
			$c->add($criterion);
			$currentTableAlias = $this->getModelAlias();
			$this->processCriteria($relationName, $c, $currentTableAlias, $query);
			return;
		}

		$propertyName = $criterion->getPropertyName();
		$columnName = $this->getQualifiedColumnName($propertyName);

		if ($criterion instanceof f_persistentdocument_criteria_BetweenExpression)
		{
			$minKey = $this->addParam($propertyName, $criterion->getMin());
			$maxKey = $this->addParam($propertyName, $criterion->getMax());
			$this->addWhere('('.$columnName.' between '.$minKey.' and '.$maxKey.')');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_EmptyExpression)
		{
			if ($this->getModel()->isDocumentProperty($propertyName))
			{
				if ($this->getModel()->isUniqueProperty($propertyName))
				{
					// intsimoa : I prefer NULL values for this case, but ...
					$this->addWhere('('.$columnName. ' IS NULL)');
				}
				else
				{
					$this->addWhere('('.$columnName. ' = 0)');
				}
			}
			else
			{
				$this->addWhere('('.$columnName. ' IS NULL OR '.$columnName.' = \'\')');
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_InExpression)
		{
			$values = $criterion->getValues();
			if (count($values) >= 1)
			{
				$sql = '('.$columnName;

				if ($criterion->getNot())
				{
					$sql .= ' NOT';
				}

				$sql .= ' IN (';
				$keys = array();
				foreach ($values as $value)
				{
					$keys[] = $this->addParam($propertyName, $value);
				}
				$sql .= join(',', $keys);
				$sql .= '))';
				$this->addWhere($sql);
			}
			else if ($criterion->getNot())
			{
				// Nothing to do: nothing is excluded, so no restriction.
			}
			else
			{
				// Nothing is included, so nothing should be returned...
				$this->addWhere('(0)');
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_LikeExpression)
		{
			$value = $criterion->getValue();
			if ($criterion->getIgnoreCase())
			{
				$value = strtolower($value);
				$columnName = 'lower('.$columnName.')';
			}
			$op = $criterion->getNot() ? 'NOT LIKE' : 'LIKE';

			$key = $this->addParam($propertyName, $criterion->getMatchMode()->toMatchString($value));
			$this->addWhere('('.$columnName.' '.$op.' '.$key.')');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_NotEmptyExpression)
		{
			if ($this->getModel()->isDocumentProperty($propertyName))
			{
				if ($this->getModel()->isUniqueProperty($propertyName))
				{
					// intsimoa : I prefer NULL values for this case, but ...
					$this->addWhere('('.$columnName. ' IS NOT NULL)');
				}
				else
				{
					$this->addWhere('('.$columnName. ' > 0)');
				}
			}
			else
			{
				$this->addWhere('('.$columnName. ' IS NOT NULL and '.$columnName.' != \'\')');
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_NotNullExpression)
		{
			$this->addWhere('('.$columnName. ' IS NOT NULL)');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_NullExpression)
		{
			$this->addWhere('('.$columnName. ' IS NULL)');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_PropertyExpression)
		{
			$otherPropertyName = $criterion->getOtherPropertyName();
			$otherColumnName = $this->getQualifiedColumnName($otherPropertyName);

			switch ($criterion->getOp())
			{
				case '=':
				case '!=':
				case '<=':
				case '>=':
				case '<':
				case '>':
					$this->addWhere('('.$columnName.' '.$criterion->getOp().' '.$otherColumnName.')');
					break;
				default:
					throw new Exception('Unknown operator '.$criterion->getOp());
					break;
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_SimpleExpression)
		{
			switch ($criterion->getOp())
			{
				case '=':
				case '!=':
				case '<=':
				case '>=':
				case '<':
				case '>':
					$value = $criterion->getValue();
					if ($criterion->getIgnoreCase())
					{
						$value = strtolower($value);
						$columnName = 'lower('.$columnName.')';
					}
					$key = $this->addParam($propertyName, $value);
					$this->addWhere('('.$columnName.' '.$criterion->getOp().' '.$key.')');
					break;
				default:
					throw new Exception('Unknown operator '.$criterion->getOp());
					break;
			}
		}
	}

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	protected function processTreeCriterions($query)
	{
		foreach ($query->getTreeCriterions() as $criterion)
		{
			$this->processTreeCriterion($criterion, $query);
		}
	}


	/**
	 * @param f_persistentdocument_criteria_TreeCriterion $criterion
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	protected function processTreeCriterion($criterion, $query)
	{
		$modelAlias = $this->getModelAlias();

		if ($criterion instanceof f_persistentdocument_criteria_AncestorOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$this->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'AncestorOfExpression Node ' . $docId . ' not in tree');
				return;
			}

			$level = $criterion->getLevel();
			$treeAlias = $this->newTableAlias();
			$this->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			$childTreeAlias = $this->newTableAlias();
			if ($level === 1)
			{
				$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias.' INNER JOIN f_tree_'.$treeId.' '.$childTreeAlias.' ON ('.$childTreeAlias.'.document_id = '. $docId
				. ' AND '.$treeAlias.'.document_id = '.$childTreeAlias.'.parent_id)';
				$this->addWhere($modelAlias.'.document_id = (' .$subquery. ')');
			}
			else
			{
				$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias.' INNER JOIN f_tree_'.$treeId.' '.$childTreeAlias.' ON ('.$childTreeAlias.'.document_id = '. $docId
				. ' AND '.$treeAlias.'.node_level < '.$childTreeAlias.'.node_level';
				if ($level > 1)
				{
					$subquery .= ' AND '.$treeAlias.'.node_level >= ('.$childTreeAlias.'.node_level - '.$level.')';
				}
				$subquery .= ' AND LOCATE(CONCAT(\'/\', '.$treeAlias.'.document_id, \'/\' ) , '.$childTreeAlias.'.node_path ) > 0)';
				$this->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_DescendentOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$this->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'DescendentOfExpression Node ' . $docId . ' not in tree');
				return;
			}

			$level = $criterion->getLevel();

			$this->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			if ($level == 1)
			{
				$treeAlias = $this->newTableAlias();
				$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
				. ' WHERE '.$treeAlias.'.parent_id = '.$docId;
				$this->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');
			}
			else
			{
				$treeAlias = $this->newTableAlias();

				$parenTreeAlias = $this->newTableAlias();

				$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
				. ' INNER JOIN f_tree_'.$treeId.' '.$parenTreeAlias.' ON ('.$parenTreeAlias.'.document_id = '. $docId
				. ' AND '.$treeAlias.'.node_level > '.$parenTreeAlias.'.node_level'
					. ' AND '.$treeAlias.'.node_path LIKE CONCAT('.$parenTreeAlias.'.node_path, \''.$docId.'/%\')';
				if ($level > 1)
				{
					$subquery .= ' AND '.$treeAlias.'.node_level <= '.$parenTreeAlias.'.node_level + '.$level;
				}
				$this->addWhere($modelAlias.'.document_id IN (' .$subquery. '))');
			}
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_NextSiblingOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$this->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'NextSiblingOfExpression Node ' . $docId . ' not in tree');
				return;
			}
			$treeAlias = $this->newTableAlias();
			$this->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			$nodeTreeAlias = $this->newTableAlias();

			$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
			. ' INNER JOIN f_tree_'.$treeId.' '.$nodeTreeAlias.' ON ('.$nodeTreeAlias.'.document_id = '. $docId
			. ' AND '.$treeAlias.'.parent_id = '.$nodeTreeAlias.'.parent_id'
				. ' AND '.$treeAlias.'.node_order > '.$nodeTreeAlias.'.node_order)';

			$this->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_PreviousSiblingOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$this->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'PreviousSiblingOfExpression Node ' . $docId . ' not in tree');
				return;
			}
			$treeAlias = $this->newTableAlias();
			$this->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			$nodeTreeAlias = $this->newTableAlias();

			$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
			. ' INNER JOIN f_tree_'.$treeId.' '.$nodeTreeAlias.' ON ('.$nodeTreeAlias.'.document_id = '. $docId
			. ' AND '.$treeAlias.'.parent_id = '.$nodeTreeAlias.'.parent_id'
				. ' AND '.$treeAlias.'.node_order < '.$nodeTreeAlias.'.node_order)';

			$this->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');
		}
		elseif ($criterion instanceof f_persistentdocument_criteria_SiblingOfExpression)
		{
			$docId = $criterion->getDocumentId();
			$document = DocumentHelper::getDocumentInstance($docId);
			$treeId = $document->getTreeId();
			if (!$treeId)
			{
				$this->addWhere("2 = 1");
				Framework::info(__METHOD__ . 'SiblingOfExpression Node ' . $docId . ' not in tree');
				return;
			}
			$treeAlias = $this->newTableAlias();
			$this->setTreeTableName('f_tree_'.$treeId, $modelAlias);
			$nodeTreeAlias = $this->newTableAlias();

			$subquery = 'SELECT '.$treeAlias.'.document_id FROM f_tree_'.$treeId.' '.$treeAlias
			. ' INNER JOIN f_tree_'.$treeId.' '.$nodeTreeAlias.' ON ('.$nodeTreeAlias.'.document_id = '. $docId
			. ' AND '.$treeAlias.'.parent_id = '.$nodeTreeAlias.'.parent_id'
				. ' AND '.$treeAlias.'.document_id != '.$docId.')';

			$this->addWhere($modelAlias.'.document_id IN (' .$subquery. ')');
		}
	}

	/**
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 */
	protected function processOrders($query)
	{
		foreach ($query->getOrders() as $order)
		{
			$this->addOrder($order);
		}
	}
}