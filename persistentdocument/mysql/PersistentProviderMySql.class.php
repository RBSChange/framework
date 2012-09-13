<?php
/**
 * @package framework.persistentdocument
 */
class f_persistentdocument_PersistentProviderMySql extends f_persistentdocument_PersistentProvider
{
	/**
	 * @var string[]
	 */
	protected $i18nfieldNames;	
	
	/**
	 * @var StatmentMysql
	 */
	protected $currentStatment = null;
		
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
	 * @return string
	 */
	protected function getI18nSuffix()
	{
		return '_i18n';
	}
	
	/**
	 * @return string
	 */
	public function getType()
	{
		return 'mysql';
	}
	
	/**
	 * @var PDO instance provided by PDODatabase
	 */
	private $m_driver = null;
	
	/**
	 * @param PDO $driver
	 */
	public function setDriver($driver)
	{
		$this->m_driver = $driver;
		if ($driver === null)
		{
			$duration = microtime(true) - $this->timers['init'];
			if ($duration > 60)
			{
				Framework::warn('Total DB connection: ' . round($duration, 4) . 's');
			}
			elseif (Framework::isDebugEnabled())
			{
				Framework::debug('Total DB connection: ' . round($duration, 4) . 's');
			}
		}
	}
	
	/**
	 * @return PDO
	 */
	public function getDriver()
	{
		if ($this->m_driver === null)
		{
			$this->m_driver = $this->getConnection($this->connectionInfos);
			register_shutdown_function(array($this, "closeConnection"));
		}
	
		return $this->m_driver;
	}
	
	/**
	 * @return string
	 */
	protected function errorCode()
	{
		return $this->getDriver()->errorCode();
	}
	
	/**
	 * @return array("sqlstate" => ..., "errorcode" => ..., "errormessage" => ...)
	 */
	protected function getErrorParameters()
	{
		$errorInfo = $this->getDriver()->errorInfo();
		return array("sqlstate" => $errorInfo[0], "errorcode" => $errorInfo[1], "errormessage" => $errorInfo[2]);
	}
	
	/**
	 * @return string
	 */
	protected function errorInfo()
	{
		return print_r($this->getDriver()->errorInfo(), true);
	}
	
	/**
	 * @param string $tableName
	 * @return integer
	 */
	protected function getLastInsertId($tableName)
	{
		return $this->getDriver()->lastInsertId();
	}
		
	/**
	 * @param boolean $bool
	 * @return boolean
	 */
	public function setAutoCommit($bool)
	{
		$previousValue = $this->getDriver()->getAttribute(PDO::ATTR_AUTOCOMMIT);
		$this->getDriver()->setAttribute(PDO::ATTR_AUTOCOMMIT, (bool)$bool);	
		if ($bool != $previousValue)
		{
			$this->setCurrentStatment(null);
		}
		return $previousValue;
	}
	
	/**
	 * @param array<String, String> $connectionInfos
	 * @return PDO
	 */
	protected function getConnection($connectionInfos)
	{
		$protocol = 'mysql';
		$dsnOptions = array();
	
		$database = isset($connectionInfos['database']) ? $connectionInfos['database'] : null;
		$password = isset($connectionInfos['password']) ? $connectionInfos['password'] : null;
		$username = isset($connectionInfos['user']) ? $connectionInfos['user'] : null;
	
		$dsn = $protocol.':';
	
		if ($database !== null)
		{
			$dsnOptions[] = 'dbname='.$database;
		}
		$unix_socket = isset($connectionInfos['unix_socket']) ? $connectionInfos['unix_socket'] : null;
		if ($unix_socket != null)
		{
			$dsnOptions[] = 'unix_socket='.$unix_socket;
		}
		else
		{
			$host = isset($connectionInfos['host']) ? $connectionInfos['host'] : 'localhost';
			$dsnOptions[] = 'host='.$host;
			$port = isset($connectionInfos['port']) ? $connectionInfos['port'] : 3306;
			$dsnOptions[] = 'port='.$port;
		}
	
		$dsn = $protocol.':'.join(';', $dsnOptions);
	
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ ."($dsn, $username)");
		}
	
		$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'") ;
		$pdo = new PDO($dsn, $username, $password, $options);
	
		return $pdo;
	}
	
	/**
	 * @param StatmentMysql $mysqlStatment or null
	 */
	protected function setCurrentStatment($mysqlStatment)
	{
		if ($this->currentStatment instanceof StatmentMysql)
		{
			$this->currentStatment->closeCursor();
			$this->currentStatment = null;
		}
		$this->currentStatment = $mysqlStatment;
	}
	
	/**
	 * 
	 * @param string $fieldName
	 * @return string
	 */
	protected function escapeFieldName($fieldName)
	{
		return '`' . $fieldName . '`';
	}
	
	/**
	 * @param string $name
	 * @param boolean $usePrefix
	 * @return string
	 */
	protected function escapeParamName($name, $usePrefix = false)
	{
		return ':'. ($usePrefix) ? 'p' : '' . $name;
	}
	
	
	/**
	 * @return boolean
	 */
	public function checkConnection()
	{
		try
		{
			$this->getDriver();
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * @return void
	 */
	public function closeConnection()
	{
		$this->setCurrentStatment(null);
		$this->setDriver(null);
	}
	
	
	/**
	 * @var change_SchemaManagerMySql
	 */
	protected $schemaManager = null;
	
	/**
	 * @return change_SchemaManagerMySql
	 */
	public function getSchemaManager()
	{
		if ($this->schemaManager === null)
		{
			$this->schemaManager = new change_SchemaManagerMySql();
		}
		return $this->schemaManager;
	}	
	
	
	/**
	 * @param boolean $useDocumentCache
	 * @return f_persistentdocument_PersistentProvider
	 */
	public function setDocumentCache($useDocumentCache)
	{
		if (!$useDocumentCache)
		{
			$this->clearDocumentCache();
		}
		return $this;
	}
	
	protected function beginTransactionInternal()
	{
		$this->setCurrentStatment(null);
		$this->getDriver()->beginTransaction();
	}
	
	protected function commitInternal()
	{
		$this->setCurrentStatment(null);
		$this->getDriver()->commit();
	}
	
	protected function rollBackInternal()
	{
		$this->getDriver()->rollBack();
	}	
	

	/**
	 * @param string $script
	 * @return PDOStatement
	 */
	public function executeSQLSelect($script)
	{
		return $this->getDriver()->query($script);
	}
	
	/**
	 * @param string $sql
	 * @return StatmentMysql
	 */
	protected function prepareStatement($sql)
	{
		$this->setCurrentStatment(null);
		$stmt = new StatmentMysql($sql, $this->getDriver());
		$this->setCurrentStatment($stmt);
		return $stmt;
	}
	
	/**
	 * @param StatmentMysql $stmt
	 */
	public function executeStatement($stmt)
	{
		if (!$stmt->execute())
		{
			$this->showError($stmt);
		}
	}
		
	/**
	 * @param StatmentMysql $statement
	 */
	protected function showError($statement = null)
	{
		if (!is_null($statement))
		{
			$errorCode = $statement->errorCode();
			$msg = "ERROR Code (". $statement->errorCode() . ") : " . var_export($statement->errorInfo(), true) ."\n";
		}
		else
		{
			$errorCode = $this->errorCode();
			$msg = "Driver ERROR Code (". $this->errorCode() . ") : " . var_export($this->errorInfo(), true)."\n";
		}
		throw new f_DatabaseException($errorCode, $msg);
	}
	
	
	/**
	 * @see f_persistentdocument_PersistentProvider::getDocumentModelName()
	 */
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
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			return $results[0]['document_model'];
		}
		return false;
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
	
	/**
	 * @see f_persistentdocument_PersistentProvider::getDocumentInstance()
	 */
	public function getDocumentInstance($documentId, $modelName = null, $lang = null)
	{
		if (!is_numeric($documentId) || $documentId <= 0)
		{
			throw new BaseException('Invalid document id: ' . $documentId, 'f.exception.errors.invalid-document-id', array('id' => $documentId));
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
				throw new BaseException('Document "' . $documentId .'" not found', 'f.exception.errors.object-not-found', array('id' => $documentId));
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
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);	
		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		if (!$result)
		{
			return null;
		}
		return $this->getDocumentInstanceWithModelName($documentId, $result['document_model'], $result['treeid'], $result);
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
		$documentId = $document->getId();
		$model = $document->getPersistentModel();	
		$table = $model->getTableName();
		$fields = array();
		$hasI18n = false;
		$i18nSuffix = $this->getI18nSuffix();
		foreach ($model->getPropertiesInfos() as $propertyName => $propertyInfos)
		{
			/* @var $propertyInfos PropertyInfo */
			if ($propertyInfos->getLocalized())
			{
				$fields[] = '`i`.`' . $propertyInfos->getDbMapping() . $i18nSuffix . '` AS `' . $propertyName . '`';
				$hasI18n = true;
			}
			else
			{
				$fields[] = '`d`.`' . $propertyInfos->getDbMapping() . '` AS `' . $propertyName . '`';
			}
		}
		
		$sql = 'SELECT ' .implode(', ', $fields). ' FROM `'.$table.'` AS `d`';
		if ($hasI18n)
		{
			$sql .= ' INNER JOIN `'.$table. $i18nSuffix . '` AS `i` USING(`document_id`)';
		}	
		$sql .=  ' WHERE `d`.`document_id` = :document_id';
		if ($hasI18n)
		{
			$sql .=  ' AND `d`.`document_lang` = `i`.`lang_i18n`';
		}
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
	
		if ($result)
		{
			$this->initDocumentFromDb($document, $result);
		}
		else
		{
			throw new Exception(get_class().'->loadDocument : could not load document[@id = '.$document->getId().']');
		}
	}
	
	/**
	 * Initialize un document avec une ligne de resultat de la base de donnée
	 *
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @param array $dbresult contient statement->fetch(PersistentProviderConst::FETCH_ASSOC)
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
		$properties = null;
		if ($documentId > 0)
		{
			$model = $doc->getPersistentModel();
			$fields = array();
			$i18nSuffix = $this->getI18nSuffix();
			$table = $model->getTableName() . $i18nSuffix;
			foreach ($model->getPropertiesInfos() as $propertyName => $propertyInfos)
			{
				/* @var $propertyInfos PropertyInfo */
				if ($propertyInfos->getLocalized())
				{
					$fields[] = '`i`.`' . $propertyInfos->getDbMapping() . $i18nSuffix . '` AS `' . $propertyName . '`';
					$hasI18n = true;
				}
			}
			
			$sql = 'SELECT ' .implode(', ', $fields). ' FROM `'.$table.'` AS `i`  WHERE `i`.`document_id` = :document_id  AND `i`.`lang_i18n` = :lang';
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $doc->getId(), PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':lang', $lang, PersistentProviderConst::PARAM_STR);
			
			$this->executeStatement($stmt);
			$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
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
	 * @param string $propertyName
	 * @return integer
	 */
	public function getRelationId($propertyName)
	{
		$stmt = $this->prepareStatement('SELECT `relation_id` FROM `f_relationname` WHERE `property_name` = :property_name');
		$stmt->bindValue(':property_name', $propertyName, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		if ($result)
		{
			return intval($result['relation_id']);
		}
		else
		{
			$stmt = $this->prepareStatement('INSERT INTO `f_relationname` (`property_name`) VALUES (:property_name)');
			$stmt->bindValue(':property_name', $propertyName, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
			return intval($this->getLastInsertId('f_relationname'));
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentArray $documentArray
	 */
	public function loadRelations($documentArray)
	{
		$masterDocId = $documentArray->getParentDocument()->getId();
		$relType = $documentArray->getRelationType();
		$relName = $documentArray->getRelationName();
		$relId = $documentArray->getRelationId();
	
		$stmt = $this->prepareStatement('SELECT `relation_id2` AS document_id FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id ORDER BY `relation_order`');
		$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);
	
		$this->executeStatement($stmt);
	
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$documentArray->loadDocumentIds($results);
	}
	
	
	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public function insertDocument($persistentDocument)
	{
		$documentId = $this->getNewDocumentId($persistentDocument);
		$this->insertDocumentInternal($documentId, $persistentDocument);
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
			$stmt->bindValue(':document_model', $documentModel, PersistentProviderConst::PARAM_STR);
			foreach ($this->getI18nFieldNames() as $i18nFieldName)
			{
				$value  = isset($documentLangs[$i18nFieldName]) ? $documentLangs[$i18nFieldName] : NULL;
				$stmt->bindValue(':'.$i18nFieldName, $value, PersistentProviderConst::PARAM_STR);
			}
	
			$this->executeStatement($stmt);
			$documentId = $this->getLastInsertId($persistentDocument->getPersistentModel()->getTableName());
		}
		else
		{
			$sql = 'INSERT INTO f_document (document_id, document_model, '. implode(', ', $i18nFieldNames) .') VALUES (:document_id, :document_model, :'. implode(', :', $i18nFieldNames) .')';			
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':document_model', $documentModel, PersistentProviderConst::PARAM_STR);
	
			foreach ($i18nFieldNames as $i18nFieldName)
			{
				$value  = isset($documentLangs[$i18nFieldName]) ? $documentLangs[$i18nFieldName] : NULL;
				$stmt->bindValue(':'.$i18nFieldName, $value, PersistentProviderConst::PARAM_STR);
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
		$table = $persistentDocument->getPersistentModel()->getTableName();
		$documentModel = $persistentDocument->getPersistentModel();
	
		$propertiesInfo = $documentModel->getPropertiesInfos();
		$properties = $persistentDocument->getDocumentProperties();
	
		$tmpId = $properties['id'];
		$properties['id'] = $documentId;
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
	
			$fieldsName[$propertyName] = '`' . $propertyInfo->getDbMapping(). '`';
			$parameters[$propertyName] = ':p'. $propertyInfo->getDbMapping();
	
			if ($properties[$propertyName] instanceof f_persistentdocument_PersistentDocumentArray)
			{
				$this->cascadeSaveDocumentArray($properties[$propertyName]);
			}
		}
	
		$sql = 'INSERT INTO `'.$table.'` (' . implode(', ', $fieldsName) .') VALUES (' . implode(', ', $parameters) .')';	
		$stmt = $this->prepareStatement($sql);
	
		$dataRelations = array();
	
		$stmt->bindValue(':document_id', $properties['id'], PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_model', $properties['model'], PersistentProviderConst::PARAM_STR);
		$this->buildRelationDataAndBindValues($dataRelations, $propertiesInfo, $properties, $stmt);
	
		$this->executeStatement($stmt);
	
		$persistentDocument->updateId($documentId);
		$this->saveRelations($persistentDocument, $dataRelations);
	
		$persistentDocument->setDocumentPersistentState(f_persistentdocument_PersistentDocument::PERSISTENTSTATE_LOADED);
	
		$this->putInCache($documentId, $persistentDocument);
	}	
	
	/**
	 * @param f_persistentdocument_I18nPersistentDocument $i18nDocument
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 */
	protected function insertI18nDocumentInternal($i18nDocument, $documentModel)
	{
		$table = $documentModel->getTableName() . $this->getI18nSuffix();
	
		$fieldsName = array($this->escapeFieldName('document_id'), $this->escapeFieldName('lang' . $this->getI18nSuffix()));
		$parameters = array(':id', ':lang');
		$properties = $i18nDocument->getDocumentProperties();
		foreach ($properties as $propertyName => $propertyValue)
		{
			$property = $documentModel->getProperty($propertyName);
			$fieldsName[$propertyName] = $this->escapeFieldName($property->getDbMapping() . $this->getI18nSuffix());
			$parameters[$propertyName] = ':p'.$property->getDbMapping();
		}
		
		$sql = 'INSERT INTO `'.$table.'` (' . implode(', ', $fieldsName) .') VALUES (' . implode(', ', $parameters) .')';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':id', $i18nDocument->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PersistentProviderConst::PARAM_STR);
		foreach ($properties as $propertyName => $propertyValue)
		{
			$this->bindValue($stmt, $propertyValue, $documentModel->getProperty($propertyName));
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
				$stmt->bindValue(':'.$i18nFieldName, $value, PersistentProviderConst::PARAM_STR);
			}
	
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
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
	
			if (!$propertyInfo->isDocument())
			{
				$dbname = '`'.$propertyInfo->getDbMapping().'`';
				$dbparemetername = ':p' .$propertyInfo->getDbMapping();
				$mapping[$propertyName] = $dbname . " = " . $dbparemetername;
			}
			elseif ($propertyValue !== null && $propertyValue->isModified())
			{
				$mapping[$propertyName] = '`'.$propertyInfo->getDbMapping().'` = :p' .$propertyInfo->getDbMapping();
			}
	
			if ($propertyValue instanceof f_persistentdocument_PersistentDocumentArray)
			{
				$this->cascadeSaveDocumentArray($propertyValue);
			}
		}
	
		$dataRelations = array();
	
		if (f_util_ArrayUtils::isNotEmpty($mapping))
		{
			$sql = 'UPDATE `'.$persistentDocument->getPersistentModel()->getTableName().
				'` SET ' . implode(', ', $mapping) . ' WHERE (`document_id` = :document_id)';
			$stmt = $this->prepareStatement($sql);
			$this->buildRelationDataAndBindValues($dataRelations, $propertiesInfo, $properties, $stmt, $mapping);
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
		}
		else
		{
			$this->buildRelationData($dataRelations, $propertiesInfo, $properties);
		}
	
		$this->saveRelations($persistentDocument, $dataRelations);
	
		$persistentDocument->setDocumentPersistentState(f_persistentdocument_PersistentDocument::PERSISTENTSTATE_LOADED);
		$this->postUpdate($documentId, $persistentDocument);
	}	
	
	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @param f_persistentdocument_PersistentDocumentArray[] $dataRelations
	 */
	private function saveRelations($persistentDocument, $dataRelations)
	{
		foreach ($dataRelations as $relation)
		{
			if (!is_null($relation))
			{
				$this->saveRelation($relation);
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentArray $documentArray
	 */
	private function saveRelation($documentArray)
	{
		if (!$documentArray->isModified())
		{
			return;
		}
	
		$parentDocument = $documentArray->getParentDocument();
		$masterDocId = $parentDocument->getId();
		$masterDocType = $parentDocument->getDocumentModelName();
		$relName = $documentArray->getRelationName();
		$relId = $documentArray->getRelationId();
		
		//Recuperation des nouvelles relations
		$docs = $documentArray->getInternalArray();
		if (count($docs) === 0)
		{
			if (!$documentArray->getParentDocument()->isNew())
			{
				$stmt = $this->prepareStatement('DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id');
				$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);
				$this->executeStatement($stmt);
			}
			$documentArray->setIsPersisted();
			return;
		}
	
		//Recuperations des anciens document_id / order
		$oldIds = array();
		$stmt = $this->prepareStatement('SELECT `relation_id2` AS doc_id, `relation_order` AS doc_order FROM `f_relation` WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id');
		$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		foreach ($stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC) as $row)
		{
			$oldIds[$row['doc_id']] = $row['doc_order'];
		}
		$oldCount = count($oldIds);
		$updateOrder = false;
		$order = 0;
		foreach ($docs as $docInfo)
		{
			$subDocId = is_numeric($docInfo) ? $docInfo : $docInfo->getId();
			if (isset($oldIds[$subDocId]))
			{
				if ($oldIds[$subDocId] != $order)
				{
					$relOrder = -$order - 1;
					$updateOrder = true;
					$stmt = $this->prepareStatement('UPDATE `f_relation` SET relation_order = :new_order WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id AND relation_order = :relation_order');
					$stmt->bindValue(':new_order', $relOrder, PersistentProviderConst::PARAM_INT);
					$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
					$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);
					$stmt->bindValue(':relation_order', $oldIds[$subDocId], PersistentProviderConst::PARAM_INT);
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
				$subDocType = $docInfo->getDocumentModelName();
				$stmt = $this->prepareStatement('INSERT INTO `f_relation` (relation_id1, relation_id2, relation_order, relation_name, document_model_id1, document_model_id2, relation_id) VALUES (:relation_id1, :relation_id2, :relation_order, :relation_name, :document_model_id1, :document_model_id2, :relation_id)');
				$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':relation_id2', $subDocId, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':relation_order', $relOrder, PersistentProviderConst::PARAM_INT);
	
				$stmt->bindValue(':relation_name', $relName, PersistentProviderConst::PARAM_STR);
				$stmt->bindValue(':document_model_id1', $masterDocType, PersistentProviderConst::PARAM_STR);
				$stmt->bindValue(':document_model_id2', $subDocType, PersistentProviderConst::PARAM_STR);
				$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);
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
				$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':relation_order', $order, PersistentProviderConst::PARAM_INT);
				$this->executeStatement($stmt);
			}
		}
	
		if ($updateOrder)
		{
			$stmt = $this->prepareStatement('UPDATE `f_relation` SET relation_order = -relation_order - 1 WHERE `relation_id1` = :relation_id1 AND `relation_id` = :relation_id AND relation_order < 0');
			$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
		}
	
		$documentArray->setIsPersisted();
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
				$dataRelations[] = $propertyValue;
				if ($propertyInfo->isArray())
				{
					if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
					{
						$stmt->bindPropertyValue($propertyInfo, $propertyValue->count());
					}
				}
				elseif ($propertyValue->count() != 0)
				{
					if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
					{
						$stmt->bindPropertyValue($propertyInfo, $propertyValue[0]->getId());
					}
				}
				else
				{
					if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
					{
						$stmt->bindPropertyValue($propertyInfo, null);
					}
				}
			}
		}
	}
	
	/**
	 * @param array $dataRelations
	 * @param array $propertiesInfo
	 * @param array $properties
	 */
	private function buildRelationData(&$dataRelations, $propertiesInfo, $properties)
	{
		foreach ($properties as $propertyName => $propertyValue)
		{
			if ('id' == $propertyName || 'model' == $propertyName)
			{
				continue;
			}
			$propertyInfo = $propertiesInfo[$propertyName];
			if ($propertyInfo->isDocument())
			{
				$dataRelations[] = $propertyValue;
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentArray $documentArray
	 */
	private function cascadeSaveDocumentArray($documentArray)
	{
		if ($documentArray->isModified())
		{
			foreach ($documentArray as $subDoc)
			{
				/* @var $subDoc f_persistentdocument_PersistentDocument */
				if ($subDoc->isNew() || $subDoc->isModified())
				{
					$subDoc->save();
				}
			}
		}
	}
		
	/**
	 * @param integer $documentId
	 * @param f_persistentdocumentPersistentDocument $persistentDocument
	 * @param boolean $clearCache
	 */
	protected function postUpdate($documentId, $persistentDocument, $clearCache = true)
	{
		$this->putInCache($documentId, $persistentDocument);
	}	
	
	/**
	 * @param f_persistentdocument_I18nPersistentDocument $i18nDocument
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 */
	protected function updateI18nDocumentInternal($i18nDocument, $documentModel)
	{
		$i18nSuffix = $this->getI18nSuffix();
		$table = $documentModel->getTableName() . $i18nSuffix;
		$properties = $i18nDocument->getDocumentProperties();
	
		$mapping = array();
		$lobParameters = array();
	
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
	
			$dbname = '`'.$propertyInfo->getDbMapping() . $i18nSuffix.'`';
			$dbparemetername = ":p" .$propertyInfo->getDbMapping();
	
			if ($propertyInfo->isLob())
			{
				$lobParameters[$propertyName] = array($dbname, $dbparemetername);
			}
	
			$mapping[$propertyName] = $dbname . " = " . $dbparemetername;
		}
		$sql = 'UPDATE `'.$table.'` SET ' . implode(', ', $mapping) . ' WHERE `document_id` = :id AND `lang_i18n` = :lang';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':id', $i18nDocument->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PersistentProviderConst::PARAM_STR);
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
			$rc = RequestContext::getInstance();
			$contextLang = $rc->getLang();
			if (!$persistentDocument->isLangAvailable($contextLang))
			{
				//Le document n'existe pas dans la langue du context on ne fait rien
				return;
			}
	
			if ($rc->hasI18nSynchro())
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
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
	
			$deletedrow = $stmt->rowCount();
			if ($deletedrow != 0)
			{
				$sql =  'DELETE FROM `'.$table.'` WHERE (`document_id` = :document_id)';
				$stmt = $this->prepareStatement($sql);
				$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
				$this->executeStatement($stmt);
	
				$stmt = $this->prepareStatement('DELETE FROM `f_relation` WHERE `relation_id1` = :relation_id1');
				$stmt->bindValue(':relation_id1', $documentId, PersistentProviderConst::PARAM_INT);
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
		$stmt->bindValue(':id', $i18nDocument->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PersistentProviderConst::PARAM_STR);
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
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
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
			$stmt->bindValue(':destmodelname', $destModelName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
	
			// Update model name in f_relation table
			$stmt = $this->prepareStatement('UPDATE `f_relation` SET `document_model_id1` = :destmodelname WHERE `relation_id1` = :id AND `document_model_id1` = :sourcemodelname');
			$stmt->bindValue(':destmodelname', $destModelName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
				
			$stmt = $this->prepareStatement('UPDATE `f_relation` SET `document_model_id2` = :destmodelname WHERE `relation_id2` = :id AND `document_model_id1` = :sourcemodelname');
			$stmt->bindValue(':destmodelname', $destModelName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
	
			// Update model name in document table
			$tableName = $sourceModel->getTableName();
			$stmt = $this->prepareStatement('UPDATE `'.$tableName.'` SET `document_model` = :destmodelname WHERE `document_id` = :id AND `document_model` = :sourcemodelname');
			$stmt->bindValue(':destmodelname', $destModelName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PersistentProviderConst::PARAM_STR);
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
			$tm->commit();
			return $destDocument;
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			// unrecoverable ...
			throw $e;
		}
	}	

	/**
	 * @param f_persistentdocument_criteria_Query $query
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function find($query)
	{
		if ($query->hasHavingCriterion() && !$query->hasProjection())
		{
			// implicit this projection
			$query->setProjection(Projections::this());
		}
		$queryBuilder = new f_persistentdocument_QueryBuilderMysql($query);
		$params = $queryBuilder->getParams();
		$queryStr = $queryBuilder->getQueryString();
		
		$statement = $this->prepareStatement($queryStr);
		// N.B.: we must check if errorCode is a real error code since execute()
		// can return false for correct executions !
		if ($statement->execute($params) === false && $statement->errorCode() !== '00000')
		{
			throw new Exception("Error while executing :[$queryStr]" . " ". var_export($params, true) .':' . join(', ', $statement->errorInfo()));
		}
		$rows = $statement->fetchAll(PersistentProviderConst::FETCH_ASSOC);
	
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
	
	//
	// Tree Methods à usage du treeService
	//
	
	/**
	* @param integer $documentId
	* @param integer $treeId
	* @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	*/
	public function getNodeInfo($documentId, $treeId)
	{
		$stmt = $this->prepareStatement('SELECT document_id, parent_id, node_order, node_level, node_path, children_count FROM f_tree_'.$treeId
		. ' WHERE document_id = :document_id');
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		
		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		if (!$result) {return null;}
		$result['tree_id'] = $treeId;
		return $result;
	}
	
	/**
	 * @param integer[] $documentsId
	 * @param integer $treeId
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public function getNodesInfo($documentsId, $treeId)
	{
		$result = array();
		$documentCount = count($documentsId);
		if ($documentCount === 0) {return $result;}
		
		$params = array();
		for($i = 0; $i < $documentCount; $i++) {$params[] = ':p' . $i;}
		
		$sql = 'SELECT document_id, parent_id, node_order, node_level, node_path, children_count FROM f_tree_'.$treeId
			. ' WHERE document_id in (' . implode(', ', $params) . ')';
		
		$stmt = $this->prepareStatement($sql);
		for($i = 0; $i < $documentCount; $i++)
		{
			$stmt->bindValue($params[$i], $documentsId[$i], PersistentProviderConst::PARAM_INT);
		}
		$this->executeStatement($stmt);
		while (($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC)) != false)
		{
			$row['tree_id'] = $treeId;
			$result[$row['document_id']] = $row;
		}
		$stmt->closeCursor();
		return $result;
	}	
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public function getChildrenNodesInfo($node)
	{
		$result = array();
		$treeId = $node->getTreeId();
		$stmt = $this->prepareStatement('SELECT t.document_id, parent_id, node_order, node_level, node_path, children_count, d.document_model'
		. ' FROM f_tree_'.$treeId. ' AS t INNER JOIN f_document AS d ON t.document_id = d.document_id'
		. ' WHERE parent_id = :parent_id ORDER BY node_order');
		$stmt->bindValue(':parent_id', $node->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		while (($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC)) != false)
		{
			$row['tree_id'] = $treeId;
			$result[] = $row;
		}
		$stmt->closeCursor();
		return $result;
	}	

	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public function getDescendantsNodesInfo($node, $deep = -1)
	{
		if ($deep === 1) {return $this->getChildrenNodesInfo($node);}
	
		$result = array();
		$treeId = $node->getTreeId();
		$maxlvl = $node->getLevel() + ($deep < 1 ? 1000 :  $deep);
		$stmt = $this->prepareStatement('SELECT t.document_id, parent_id, node_order, node_level, node_path, children_count, d.document_model' 
		. ' FROM f_tree_'.$treeId. ' AS t INNER JOIN f_document AS d ON t.document_id = d.document_id'
		. '	WHERE node_level > :min_level AND node_level <= :max_level AND node_path like :node_path ORDER BY node_level, node_order');
		
		$stmt->bindValue(':min_level', $node->getLevel(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':max_level', $maxlvl, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_path', $node->getPath() . $node->getId() . '/%', PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
	
		while (($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC)) != false)
		{
			$row['tree_id'] = $treeId;
			$result[] = $row;
		}
		$stmt->closeCursor();
		return $result;
	}	
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return integer[]
	 */
	public function getChildrenId($node)
	{
		$result = array();
		if (!$node->hasChildren()) {return $result;}
			
		$stmt = $this->prepareStatement('SELECT document_id FROM f_tree_'.$node->getTreeId().' WHERE parent_id = :parent_id ORDER BY node_order');
		$stmt->bindValue(':parent_id', $node->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		while (($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC)) != false)
		{
			$result[] = $row['document_id'];
		}
		$stmt->closeCursor();
		return $result;
	}	
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return integer[]
	 */
	public function getDescendantsId($node)
	{
		$result = array();
		if (!$node->hasChildren()) {return $result;}
			
		$stmt = $this->prepareStatement('SELECT document_id FROM f_tree_'.$node->getTreeId(). ' WHERE node_level > :node_level AND node_path like :node_path');
		$stmt->bindValue(':node_level', $node->getLevel(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_path', $node->getPath() . $node->getId() . '/%', PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		while (($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC)) != false)
		{
			$result[] = $row['document_id'];
		}
		$stmt->closeCursor();
		return $result;
	}	
	
	/**
	 * Suppression de tout l'arbre
	 * @param f_persistentdocument_PersistentTreeNode $rootNode
	 * @return integer[]
	 */
	public function clearTree($rootNode)
	{
		$ids = $this->getDescendantsId($rootNode);
		if (count($ids) === 0) return $ids;
		$treeId = $rootNode->getId();
	
		$stmt = $this->prepareStatement('UPDATE f_document SET treeid = NULL WHERE treeid = :treeid AND document_id <> :document_id');
		$stmt->bindValue(':treeid', $treeId , PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id', $treeId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		$stmt = $this->prepareStatement('DELETE FROM f_tree_'.$treeId);
		$this->executeStatement($stmt);
	
		//Update node information
		$rootNode->setEmpty();
		$this->insertNode($rootNode);
		return $ids;
	}	
	
	/**
	 * Ajoute un nouveau noeud
	 * @param f_persistentdocument_PersistentTreeNode $node
	 */
	protected function insertNode($node)
	{
		$stmt = $this->prepareStatement('INSERT INTO f_tree_'.$node->getTreeId()
		. ' (`document_id`, `parent_id`, `node_order`, `node_level`, `node_path`, `children_count`) VALUES (:document_id, :parent_id, :node_order, :node_level, :node_path, :children_count)');
		$stmt->bindValue(':document_id', $node->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':parent_id', $node->getParentId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_order', $node->getIndex(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_level', $node->getLevel(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_path', $node->getPath(), PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':children_count', $node->getChildCount(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		$stmt = $this->prepareStatement('UPDATE f_document SET treeid = :treeid WHERE document_id = :document_id');
		$stmt->bindValue(':treeid', $node->getTreeId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id', $node->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		if ($this->isInCache($node->getId()))
		{
			$document = $this->getFromCache($node->getId());
			$document->setProviderTreeId($node->getTreeId());
		}
	}	
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode[] $nodes
	 */
	public function orderNodes($parentNode, $nodes)
	{
		$countIds = count($nodes);
		$treeId = $parentNode->getTreeId();
		$params = array();
		for($i = 0; $i < $countIds; $i++) {$params[] = ':p' . $i;}
		
		$sql = 'UPDATE f_tree_'.$treeId . ' SET node_order = - node_order - 1 WHERE document_id in (' . implode(', ', $params) . ')';
		
		$stmt = $this->prepareStatement($sql);
		foreach ($nodes as $i => $node)
		{
			$stmt->bindValue($params[$i], $node->getId() , PersistentProviderConst::PARAM_INT);
		}
		$this->executeStatement($stmt);
		foreach ($nodes as $node)
		{
			$stmt = $this->prepareStatement('UPDATE f_tree_'.$treeId. ' SET node_order = :node_order WHERE document_id = :document_id');
			$stmt->bindValue(':node_order', $node->getIndex() , PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':document_id', $node->getId() , PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
		}
	}	

	/**
	 * @param integer $treeId
	 * @return string
	 */
	protected function updateChildenCountQuery($treeId)
	{
		return 'UPDATE f_tree_'.$treeId . ' SET children_count = children_count + :offest WHERE document_id = :document_id';
	}
	
	/**
	 * @param integer $treeId
	 * @param integer $offset
	 * @return string
	 */
	protected function updateChildrenOrderQuery($treeId, $offset)
	{
		return 'UPDATE f_tree_'.$treeId . ' SET node_order = node_order + :offest WHERE parent_id = :parent_id AND node_order >= :node_order order by node_order'. ($offset < 0 ? ' asc' : ' desc');
	}
	
	/**
	 * Supression d'un noeud
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 */
	public function deleteEmptyNode($treeNode)
	{
		$stmt = $this->prepareStatement('UPDATE f_document SET treeid = :treeid WHERE document_id = :document_id');
		$stmt->bindValue(':treeid', null, PersistentProviderConst::PARAM_NULL);
		$stmt->bindValue(':document_id', $treeNode->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
			
		$sql = 'DELETE FROM f_tree_'.$treeNode->getTreeId() . ' WHERE document_id = :document_id';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $treeNode->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		if ($treeNode->getParentId())
		{
			//Mise à jour du nombre de fils
			$stmt = $this->prepareStatement($this->updateChildenCountQuery($treeNode->getTreeId()));
			$stmt->bindValue(':offest', -1, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':document_id',$treeNode->getParentId(), PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
	
			//Mise à jour de l'ordre des fils
			$stmt = $this->prepareStatement($this->updateChildrenOrderQuery($treeNode->getTreeId(), -1));
			$stmt->bindValue(':offest', -1, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':parent_id',$treeNode->getParentId(), PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':node_order',$treeNode->getIndex(), PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
		}
	}	
	
	/**
	 * Supression d'une arboresence
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 * @return integer[]
	 */
	public function deleteNodeRecursively($treeNode)
	{
		$ids = $this->getDescendantsId($treeNode);
		$countIds = count($ids);
		if ($countIds !== 0)
		{
			$path = $treeNode->getPath() . $treeNode->getId() . '/%';
			$sql = 'UPDATE f_document SET treeid = NULL WHERE document_id IN (SELECT document_id FROM f_tree_'.$treeNode->getTreeId()
					. ' WHERE node_level > :node_level AND node_path like :node_path)';
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':node_level', $treeNode->getLevel(), PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':node_path', $path, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
	
			$sql = 'DELETE FROM f_tree_'.$treeNode->getTreeId() . ' WHERE node_level > :node_level AND node_path like :node_path';
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':node_level', $treeNode->getLevel(), PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':node_path', $path, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
		}
	
		$ids[] = $treeNode->getId();
		$this->deleteEmptyNode($treeNode);
		return $ids;
	}	
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode $childNode
	 */
	public function appendChildNode($parentNode, $childNode)
	{
		//Mise à jour du nombre de fils
		$stmt = $this->prepareStatement($this->updateChildenCountQuery($childNode->getTreeId()));
		$stmt->bindValue(':offest', 1, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id', $parentNode->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		//Insertion du noeud
		$this->insertNode($childNode);
	
		//Mise à jour du parent en memoire
		$parentNode->addChild($childNode);
	}	
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode $childNode
	 */
	public function insertChildNodeAtOrder($parentNode, $childNode)
	{
		//Mise à jour du nombre de fils
		$stmt = $this->prepareStatement($this->updateChildenCountQuery($childNode->getTreeId()));
		$stmt->bindValue(':offest', 1, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id',$parentNode->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		//Mise à jour de l'ordre des fils
		$stmt = $this->prepareStatement($this->updateChildrenOrderQuery($childNode->getTreeId(), 1));
		$stmt->bindValue(':offest', 1, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':parent_id', $parentNode->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_order', $childNode->getIndex(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		//Insertion du noeud
		$this->insertNode($childNode);
	
		//Mise à jour du parent en memoire
		$parentNode->insertChildAt($childNode);
	}

	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode $movedNode
	 * @param f_persistentdocument_PersistentTreeNode $destNode
	 * @return integer[]
	 */
	public function moveNode($parentNode, $movedNode, $destNode)
	{
		if ($movedNode->hasChildren())
		{
			$result = $this->getDescendantsId($movedNode);
		}
		else
		{
			$result = array();
		}
	
		$result[] = $movedNode->getId();
		$destPath = $destNode->getPath() . $destNode->getId() . '/';
		$originalPath = $movedNode->getPath();
	
		$lvlOffset = $destNode->getLevel() - $parentNode->getLevel();
		$orderdest = $destNode->getChildCount();
	
		$stmt = $this->prepareStatement('UPDATE f_tree_'.$movedNode->getTreeId()
		. ' SET parent_id = :parent_id, node_order = :node_order, node_level = node_level + :offestlvl, node_path = :node_path'
		. ' WHERE document_id = :document_id');
		$stmt->bindValue(':parent_id', $destNode->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_order', $orderdest, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':offestlvl', $lvlOffset, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_path', $destPath, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':document_id', $movedNode->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		//Mise à jour du nombre de fils destination
		$stmt = $this->prepareStatement($this->updateChildenCountQuery($destNode->getTreeId()));
		$stmt->bindValue(':offest', 1, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id',$destNode->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
	
		//Mise à jour du nombre de fils depart
		$stmt = $this->prepareStatement($this->updateChildenCountQuery($parentNode->getTreeId()));
		$stmt->bindValue(':offest', -1, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id',$parentNode->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		//Mise à jour de l'ordre des fils de fils depart
		$stmt = $this->prepareStatement($this->updateChildrenOrderQuery($parentNode->getTreeId(), -1));
		$stmt->bindValue(':offest', -1, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':parent_id', $parentNode->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_order', $movedNode->getIndex(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		if ($movedNode->hasChildren())
		{
			$originalPath .= $movedNode->getId() .'/';
			$destPath .= $movedNode->getId() .'/';
			$stmt = $this->prepareStatement('UPDATE f_tree_'.$movedNode->getTreeId()
		. ' SET node_level = node_level + :offestlvl, node_path = REPLACE(node_path, :from_path, :to_path)'
		. ' WHERE node_level > :node_level AND node_path like :node_path');
			$stmt->bindValue(':offestlvl', $lvlOffset, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':from_path', $originalPath, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':to_path', $destPath, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':node_level', $movedNode->getLevel(), PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':node_path', $originalPath.'%', PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
		}
	
		$parentNode->removeChild($movedNode);
		$movedNode->moveTo($destNode);
		return $result;
	}

	/**
	 * @param string $type
	 * @param integer $documentId1
	 * @param integer $documentId2
	 * @param string $documentModel1
	 * @param string $documentModel2
	 * @param string $name
	 * @return f_persistentdocument_PersistentRelation[]
	 */
	protected function getRelations($type = null, $documentId1 = null, $documentId2 = null, $name = null, $documentModel1 = null, $documentModel2 = null)
	{
		$relationId = ($name === null) ? NULL : RelationService::getInstance()->getRelationId($name);

		$where = array();
		if (!is_null($documentId1)) { $where[] = 'relation_id1 = :relation_id1'; }
		if (!is_null($documentModel1)) { $where[] = 'document_model_id1 = :document_model_id1'; }
		if (!is_null($documentId2)) { $where[] = 'relation_id2 = :relation_id2'; }
		if (!is_null($documentModel2)) { $where[] = 'document_model_id2 = :document_model_id2'; }
		if (!is_null($relationId)) { $where[] = 'relation_id = :relation_id'; }
		
		$stmt = $this->prepareStatement('SELECT * FROM f_relation WHERE ' . join(' AND ', $where) . ' ORDER BY relation_order ASC');
	
		if (!is_null($documentId1)) { $stmt->bindValue(':relation_id1', $documentId1, PersistentProviderConst::PARAM_INT); }
		if (!is_null($documentModel1)) { $stmt->bindValue(':document_model_id1', $documentModel1, PersistentProviderConst::PARAM_STR); }
		if (!is_null($documentId2))  { $stmt->bindValue(':relation_id2', $documentId2, PersistentProviderConst::PARAM_INT); }
		if (!is_null($documentModel2)) { $stmt->bindValue(':document_model_id2', $documentModel2, PersistentProviderConst::PARAM_STR); }
		if (!is_null($relationId)) { $stmt->bindValue(':relation_id', $relationId, PersistentProviderConst::PARAM_INT); }
	
		$this->executeStatement($stmt);
	
		$references = array();
		foreach ($stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC) as $result)
		{
			$references[] = new f_persistentdocument_PersistentRelation(
				$result['relation_id1'], $result['document_model_id1'], $result['relation_id2'], $result['document_model_id2'],
				'CHILD', $result['relation_name'], $result['relation_order']);
		}
	
		return $references;
	}
			
	/**
	 * @param string $value
	 * @param string $settingName
	 * @return string|NULL
	 */
	public function getSettingPackage($value, $settingName)
	{
		$stmt = $this->prepareStatement('SELECT package FROM f_settings WHERE value = :value AND name = :name AND userid = 0');
		$stmt->bindValue(':value', $value, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		return count($results) === 1 ? $results[0]['package'] : null;
	}	
	
	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @param integer $userId
	 * @return string|NULL
	 */
	public function getUserSettingValue($packageName, $settingName, $userId)
	{
		$stmt = $this->prepareStatement('SELECT value FROM f_settings WHERE package = :package AND name = :name AND userid = :userid');
		$stmt->bindValue(':package', $packageName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':userid', $userId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		return count($results) === 1 ? $results[0]['value'] : null;
	}	
	
	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @param integer $userId
	 * @param string|NULL $value
	 */
	public function setUserSettingValue($packageName, $settingName, $userId, $value)
	{
		$stmt = $this->prepareStatement('DELETE FROM `f_settings` WHERE `package` = :package AND `name` = :name AND `userid` = :userid');
		$stmt->bindValue(':package', $packageName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':userid', $userId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		if ($value !== null)
		{
			$stmt = $this->prepareStatement('INSERT INTO `f_settings` (`package`, `name`, `userid`, `value`) VALUES (:package, :name, :userid, :value)');
			$stmt->bindValue(':package', $packageName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':userid', $userId, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':value', $value, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
		}
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
	public function getTags($documentId)
	{
		$stmt = $this->prepareStatement('SELECT tag FROM f_tags WHERE id = :id');
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		$tags = array();
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			foreach ($results as $result)
			{
				$tags[] = $result['tag'];
			}
		}
		return $tags;
	}
	
	/**
	 * @return array<tag => array<id>>
	 */
	public function getAllTags()
	{
		$stmt = $this->prepareStatement('SELECT tags.tag, tags.id FROM f_tags tags');
		$this->executeStatement($stmt);
		$allTags = array();
		foreach ($stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC) as $row)
		{
			$allTags[$row['tag']][] = $row['id'];
		}
		return $allTags;
	}
	
	/**
	 * @internal use by TagService
	 * @param string $tag
	 * @return array of documentid
	 */
	public function getDocumentIdsByTag($tag)
	{
		$stmt = $this->prepareStatement('SELECT id FROM f_tags WHERE tag = :tag');
		$stmt->bindValue(':tag', $tag, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
	
		$ids = array();
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			foreach ($results as $result)
			{
				$ids[] = $result['id'];
			}
		}
		return $ids;
	}	
	
	/**
	 * @internal use by TagService
	 *
	 * @param integer $documentId
	 * @param array $tags Array of string tag name (tolower)
	 * @param boolean $allTagsRequired
	 * @return boolean
	 */
	public function hasTags($documentId, $tags, $allTagsRequired)
	{
		$sql = 'SELECT count(*) nbtags FROM f_tags WHERE id = :id AND tag IN (\'' . implode("', '", $tags) . '\')';
	
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	
		$nb = 0;
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			$nb = intval($results[0]['nbtags']);
		}
		if (($nb == count($tags) && $allTagsRequired) || ($nb > 0 && !$allTagsRequired))
		{
			return true;
		}
		return false;
	}	
	
	/**
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 * @return boolean
	 */
	public function hasTag($documentId, $tag)
	{
		$stmt = $this->prepareStatement('SELECT id FROM f_tags WHERE id = :id AND tag = :tag');
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':tag', $tag, PersistentProviderConst::PARAM_STR);
	
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			return true;
		}
		return false;
	}	
	
	/**
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 * @return boolean
	 */
	public function removeTag($documentId, $tag)
	{
		$stmt = $this->prepareStatement('DELETE FROM f_tags WHERE id = :id AND tag = :tag');
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':tag', $tag, PersistentProviderConst::PARAM_STR);
	
		$this->executeStatement($stmt);
		return true;
	}	
	
	/**
	 * Adds the tag $tag tag to the document with ID $documentId.
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 */
	public function addTag($documentId, $tag)
	{
		$stmt = $this->prepareStatement('INSERT INTO f_tags (id, tag) VALUES (:id, :tag)');
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':tag', $tag, PersistentProviderConst::PARAM_STR);	
		$this->executeStatement($stmt);
	}	
	
	/**
	 * Return a translated text or null
	 * @param string $lcid
	 * @param string $id
	 * @param string $keyPath
	 * @return array[$content, $format]
	 */
	public function translate($lcid, $id, $keyPath)
	{
		$stmt = $this->prepareStatement('SELECT `content`, `format` FROM `f_locale` WHERE `lang` = :lang AND `id` = :id AND `key_path` = :key_path');
		$stmt->bindValue(':lang', $lcid, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':key_path', $keyPath, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			$content = $results[0]['content'];
			if ($content == NULL) {$content = '';}
			return array($content, $results[0]['format']);
		}
		return array(null, null);
	}	
	
	/**
	 * Clear the translation table or a part of that
	 *
	 * @param string $package Example: m.users
	 */
	public function clearTranslationCache($package = null)
	{
		if ($package === null)
		{
			 $sql = 'DELETE FROM `f_locale` WHERE `useredited` != 1';
		}
		else
		{
			 $sql = "DELETE FROM `f_locale` WHERE `useredited` != 1 AND `key_path` LIKE '" . $package . ".%'";
		}
		$stmt = $this->prepareStatement($sql);
		$this->executeStatement($stmt);
	}	
	
	/**
	 * @param string $lcid
	 * @param string $id
	 * @param string $keyPath
	 * @param string $content
	 * @param integer $useredited
	 * @param string $format [TEXT] | HTML
	 * @param boolean $forceUpdate
	 */
	public function addTranslate($lcid, $id, $keyPath, $content, $useredited, $format = 'TEXT', $forceUpdate = false)
	{
		$stmt = $this->prepareStatement('SELECT `useredited` FROM `f_locale` WHERE `lang` = :lang AND `id` = :id  AND `key_path` = :key_path');
		$stmt->bindValue(':lang', $lcid, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':key_path', $keyPath, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results))
		{
			if ($forceUpdate || $useredited == 1 || $results[0]['useredited'] != 1)
			{
				$stmt = $this->prepareStatement('UPDATE `f_locale` SET `content` = :content, `useredited` = :useredited, `format` = :format WHERE `lang` = :lang AND `id` = :id  AND `key_path` = :key_path');
				$stmt->bindValue(':content', $content, PersistentProviderConst::PARAM_LOB);
				$stmt->bindValue(':useredited', $useredited, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':format', $format, PersistentProviderConst::PARAM_STR);
				$stmt->bindValue(':lang', $lcid, PersistentProviderConst::PARAM_STR);
				$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_STR);
				$stmt->bindValue(':key_path', $keyPath, PersistentProviderConst::PARAM_STR);
				$this->executeStatement($stmt);
			}
		}
		else
		{
			$stmt = $this->prepareStatement('INSERT INTO `f_locale` (`lang`, `id`, `key_path`, `content`, `useredited`, `format`) VALUES (:lang, :id, :key_path, :content, :useredited, :format)');
			$stmt->bindValue(':lang', $lcid, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':key_path', $keyPath, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':content', $content, PersistentProviderConst::PARAM_LOB);
			$stmt->bindValue(':useredited', $useredited, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':format', $format, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
		}
	}	
	
	/**
	 * @return array
	 */
	public function getPackageNames()
	{
		$stmt = $this->prepareStatement('SELECT COUNT(*) AS `nbkeys`, `key_path` FROM `f_locale` GROUP BY `key_path` ORDER BY `key_path`');
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$paths = array();
		foreach ($results as $result)
		{
			$paths[$result['key_path']] = $result['nbkeys'];
		}
		return $paths;
	}

	/**
	 * @return array
	 */
	public function getUserEditedPackageNames()
	{
		$stmt = $this->prepareStatement('SELECT COUNT(*) AS `nbkeys`, `key_path` FROM `f_locale` WHERE `useredited` = 1 GROUP BY `key_path` ORDER BY `key_path`');
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$paths = array();
		foreach ($results as $result)
		{
			$paths[$result['key_path']] = $result['nbkeys'];
		}
		return $paths;
	}

	/**
	 * @param string $keyPath
	 * @return array['id' => string, 'lang' => string, 'content' => string, 'useredited' => integer, 'format' => string]
	 */
	public function getPackageData($keyPath)
	{
		$stmt = $this->prepareStatement('SELECT `id`,`lang`,`content`,`useredited`,`format` FROM `f_locale` WHERE `key_path` = :key_path');
		$stmt->bindValue(':key_path', $keyPath, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		return $results;
	}

	/**
	 * @param string $keyPath
	 * @param string $id
	 * @param string $lcid
	 */
	public function deleteI18nKey($keyPath, $id = null, $lcid = null)
	{
		$sql = 'DELETE FROM `f_locale` WHERE `key_path` = :key_path';
		if ($id !== null)
		{
			$sql .= ' AND `id` = :id';
			if ($lcid !== null)
			{
				$sql .= ' AND `lang` = :lang';
			}
		}
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':key_path', $keyPath, PersistentProviderConst::PARAM_STR);
		if ($id !== null)
		{
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_STR);
			if ($lcid !== null)
			{
				$stmt->bindValue(':lang', $lcid, PersistentProviderConst::PARAM_STR);
			}
		}
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	}

	/**
	 * @param integer $id
	 * @param string $lang
	 * @param string $synchroStatus 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 * @param string|null $fromLang
	 */
	public function setI18nSynchroStatus($id, $lang, $synchroStatus, $fromLang = null)
	{
		$sql = "INSERT INTO `f_i18n` (`document_id`, `document_lang`, `synchro_status`, `synchro_from`)
			VALUES (:document_id, :document_lang, :synchro_status, :synchro_from)
			ON DUPLICATE KEY UPDATE `synchro_status` = VALUES(`synchro_status`), `synchro_from` = VALUES(`synchro_from`)";
	
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_lang', $lang, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':synchro_status', $synchroStatus, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':synchro_from', $fromLang, ($fromLang === null ? PersistentProviderConst::PARAM_NULL : PersistentProviderConst::PARAM_STR));
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	}

	/**
	 * @param integer $id
	 * @return array
	 * 		- 'fr'|'en'|'??' : array
	 * 			- status : 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 * 			- from : fr'|'en'|'??'|null
	 */
	public function getI18nSynchroStatus($id)
	{
		$sql = "SELECT `document_lang`, `synchro_status`, `synchro_from` FROM `f_i18n` WHERE `document_id` = :document_id";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$result = array();
		$row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		while ($row)
		{
			$result[$row['document_lang']] = array('status' => $row['synchro_status'], 'from' => $row['synchro_from']);
			$row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		}
		$stmt->closeCursor();
		return $result;
	}	
	
	/**
	 * @return integer[]
	 */
	public function getI18nSynchroIds()
	{
		$sql = "SELECT DISTINCT `document_id` FROM `f_i18n` WHERE `synchro_status` = 'MODIFIED' LIMIT 0, 100";
	
		$stmt = $this->prepareStatement($sql);
		$this->executeStatement($stmt);
		return $stmt->fetchAll(PersistentProviderConst::FETCH_COLUMN);
	}	
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $pm
	 * @param integer $id
	 * @param string $lang
	 * @param string $fromLang
	 */
	public function prepareI18nSynchro($pm, $documentId, $lang, $fromLang)
	{
		$suf = $this->getI18nSuffix();
		$tableName = $pm->getTableName() . $suf;
		$className = $this->getI18nDocumentClassFromModel($pm->getName());
		$fields = array();
		foreach ($pm->getPropertiesInfos() as $key => $propertyInfo)
		{
			if ($propertyInfo->isLocalized())
			{
				$fields[] = '`' . $propertyInfo->getDbMapping() . $suf. '` AS `' . $key . '`';
			}
		}
	
		$sql =  "SELECT ". implode(', ', $fields)." FROM ".$tableName." WHERE document_id = :document_id and lang_i18n = :lang";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $fromLang, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$fromResult = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
	
		$from = new $className($documentId, $fromLang, false);
		$from->setDocumentProperties($fromResult);
	
		$sql =  "SELECT `document_publicationstatus_i18n` AS `publicationstatus` FROM ".$tableName." WHERE document_id = :document_id and lang_i18n = :lang";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $lang, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$toResult = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
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
		$suf = $this->getI18nSuffix();
		$tableName = $pm->getTableName() . $suf;
		$sql = "select * from ".$tableName." where document_id = :document_id and lang_i18n = :lang";
		$id = $to->getId();
		$lang = $to->getLang();
	
		$sqlInsert = array('`document_id`', '`lang_i18n`');
		$sqlValues =  array(':document_id' => $id, ':lang_i18n' => $lang);
		$sqlUpdate = array();
	
		foreach ($to->getDocumentProperties() as $propertyName => $value)
		{
			$property = $pm->getProperty($propertyName);
			$fieldName = $property->getDbMapping() . $suf;
				
			if ($propertyName === 'publicationstatus')
			{
				$sqlInsert[] = '`' . $fieldName . '`';
				$sqlValues[':' . $fieldName] = $value;
			}
			elseif ($propertyName !== 'correctionid')
			{
				$sqlInsert[] = '`' . $fieldName . '`';
				$sqlValues[':' . $fieldName] = $value;
				$sqlUpdate[] = '`' . $fieldName . '` = VALUES(`' . $fieldName . '`)';
			}
		}
		$sql = 'INSERT INTO `'.$tableName.'` (' . implode(', ', $sqlInsert) .
		') VALUES (' . implode(', ', array_keys($sqlValues)) .
		') ON DUPLICATE KEY UPDATE' . implode(', ', $sqlUpdate);
	
		$stmt = $this->prepareStatement($sql);
		foreach ($sqlValues as $bn => $value)
		{
			$stmt->bindValue($bn, $value, $value === null ? PersistentProviderConst::PARAM_NULL : PersistentProviderConst::PARAM_STR);
		}
		$this->executeStatement($stmt);
		$this->m_i18nDocumentInstances[$id] = array();
	
		$sql = 'UPDATE `f_document` SET `label_' . $lang . '` = :label  WHERE (document_id = :document_id)';
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':label', $sqlValues[':document_label_i18n'], PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$this->deleteFromCache($id);
	}
	
	/**
	 * @param integer $id
	 * @param string|null $lang
	 */
	public function deleteI18nSynchroStatus($id, $lang = null)
	{
		$sql = "DELETE FROM `f_i18n` WHERE `document_id` = :document_id";
		if ($lang !== null)
		{
			$sql .= " AND `document_lang` = :document_lang";
		}
	
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
		if ($lang !== null)
		{
			$stmt->bindValue(':document_lang', $lang, PersistentProviderConst::PARAM_STR);
		}
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	}	
	
	/**
	 * @param integer $documentId
	 * @return array<<nb_rules, website_id, website_lang>>
	 */
	public function getUrlRewritingDocumentWebsiteInfo($documentId)
	{
		$sql = "SELECT count(rule_id) AS nb_rules, website_id, website_lang FROM f_url_rules WHERE document_id = :document_id GROUP BY website_id, website_lang";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
	}	
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @return array<<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type>>
	 */
	public function getUrlRewritingDocument($documentId, $lang, $websiteId)
	{
		$sql = "SELECT rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type
			FROM f_url_rules WHERE document_id = :document_id AND website_lang = :website_lang AND website_id = :website_id";
		$stmt = $this->prepareStatement($sql);
	
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':website_lang', $lang, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_id', $websiteId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
	}	
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 */
	public function deleteUrlRewritingDocument($documentId, $lang, $websiteId)
	{
		$sql = "DELETE FROM f_url_rules WHERE document_id = :document_id AND website_lang = :website_lang AND website_id = :website_id";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':website_lang', $lang, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_id', $websiteId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	}	
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @return array<<nb_rules, website_id, website_lang>>
	 */
	public function getUrlRewritingActionWebsiteInfo($moduleName, $actionName)
	{
		$sql = "SELECT count(rule_id) AS nb_rules, website_id, website_lang FROM f_url_rules WHERE modulename = :modulename AND actionname = :actionname GROUP BY website_id, website_lang";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':modulename', $moduleName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':actionname', $actionName, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		return $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
	}	
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @param string $lang
	 * @param integer $websiteId
	 * @return array<<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type>>
	 */
	public function getUrlRewritingAction($moduleName, $actionName, $lang, $websiteId)
	{
		$sql = "SELECT rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type
			FROM f_url_rules WHERE modulename = :modulename AND actionname = :actionname AND website_lang = :website_lang AND website_id = :website_id";
		$stmt = $this->prepareStatement($sql);
	
		$stmt->bindValue(':modulename', $moduleName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':actionname', $actionName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_lang', $lang, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_id', $websiteId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
	}	
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @param string $lang
	 * @param integer $websiteId
	 */
	public function deleteUrlRewritingAction($moduleName, $actionName, $lang, $websiteId)
	{
		$sql = "DELETE FROM f_url_rules WHERE modulename = :modulename AND actionname = :actionname AND website_lang = :website_lang AND website_id = :website_id";
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':modulename', $moduleName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':actionname', $actionName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_lang', $lang, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_id', $websiteId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	
	}	
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @return string from_url
	 */
	public function getUrlRewriting($documentId, $lang, $websiteId = 0, $actionName = 'ViewDetail')
	{
		$stmt = $this->prepareStatement('SELECT from_url	FROM f_url_rules WHERE document_id = :id AND website_lang = :lang AND website_id = :website_id AND actionname = :actionname AND redirect_type = 200');
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $lang, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_id', $websiteId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':actionname', $actionName, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			return $results[0]['from_url'];
		}
		return null;
	}	
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @return array<array<rule_id, origine, document_id, website_lang, website_id, from_url, to_url, redirect_type, modulename, actionname>>
	 */
	public function getUrlRewritingInfo($documentId, $lang)
	{
		$stmt = $this->prepareStatement('SELECT rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type FROM f_url_rules WHERE document_id = :id AND website_lang = :lang');
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $lang, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		return $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
	}	
	
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
	public function setUrlRewriting($documentId, $lang, $websiteId, $fromURL, $toURL, $redirectType, $moduleName, $actionName, $origine = 0)
	{
		$stmt = $this->prepareStatement('INSERT INTO f_url_rules (document_id, website_lang, website_id, from_url, to_url, redirect_type, modulename, actionname, origine) VALUES (:document_id, :website_lang, :website_id, :from_url, :to_url, :redirect_type, :modulename, :actionname, :origine)');
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':website_lang', $lang, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_id', $websiteId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':from_url', $fromURL, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':to_url', $toURL, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':redirect_type', $redirectType, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':modulename', $moduleName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':actionname', $actionName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':origine', $origine, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	}	
	
	/**
	 * @param integer $documentId
	 * @return integer count deleted rules
	 */
	public function clearUrlRewriting($documentId)
	{
		$stmt = $this->prepareStatement('DELETE FROM f_url_rules WHERE document_id = :document_id');
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	}	
	
	/**
	 * @param string $url
	 * @param integer $websiteId
	 * @param string $lang
	 * @return array<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, to_url, redirect_type>
	 */
	public function getUrlRewritingInfoByUrl($url, $websiteId, $lang)
	{
		$stmt = $this->prepareStatement('SELECT `rule_id`, `origine`, `modulename`, `actionname`, `document_id`, `website_lang`, `website_id`, `to_url`, `redirect_type` FROM `f_url_rules` WHERE from_url = :url AND website_id = :website_id AND `website_lang` = :website_lang');
		$stmt->bindValue(':url', $url, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_id', $websiteId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':website_lang', $lang, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			return $results[0];
		}
		return null;
	}	
	
	
	/**
	 * @param string $url
	 * @param integer $websiteId
	 * @return array<rule_id, document_id, website_lang, website_id, to_url, redirect_type>
	 */
	public function getPageForUrl($url, $websiteId = 0)
	{
		$stmt = $this->prepareStatement('SELECT rule_id, document_id, website_lang, website_id, to_url, redirect_type FROM f_url_rules WHERE from_url = :url AND (website_id = 0 OR website_id = :website_id)');
		$stmt->bindValue(':url', $url, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':website_id', $websiteId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) > 0)
		{
			return $results[0];
		}
		return null;
	}	
	
	/**
	 * Compile a user/groupAcl in f_permission_compiled.
	 *
	 * @param users_persistentdocument_userAcl | users_persistentdocument_groupAcl $acl
	 */
	public function compileACL($acl)
	{
		$accessorId = $acl->getAccessorId();
		$nodeId = $acl->getDocumentId();
		$role = $acl->getRole();
	
		$roleService = change_PermissionService::getRoleServiceByRole($role);
		if ($roleService === null)
		{
			$acl->getDocumentService()->delete($acl);
			return;
		}
		try
		{
			$permissions = $roleService->getPermissionsByRole($role);
			foreach ($permissions as $permission)
			{
				if (!$this->checkCompiledPermission(array($accessorId), $permission, $nodeId))
				{
					$stmt = $this->prepareStatement('INSERT INTO `f_permission_compiled` VALUES (:accessorId, :permission, :nodeId)');
					$stmt->bindValue(':accessorId', $accessorId);
					$stmt->bindValue(':permission', $permission);
					$stmt->bindValue(':nodeId', $nodeId);
					$this->executeStatement($stmt);
				}
			}
		}
		catch (IllegalArgumentException $e)
		{
			Framework::error($e->getMessage());
			$acl->getDocumentService()->delete($acl);
		}
	}	
	
	/**
	 * Remove all compiled acls for node $nodeId
	 *
	 * @param integer $nodeId
	 * @param string $packageName (ex: modules_website)
	 */
	public function removeACLForNode($nodeId, $packageName = null)
	{
		if (is_null($packageName))
		{
			$stmt = $this->prepareStatement('DELETE FROM `f_permission_compiled` WHERE `node_id` = :nodeId');
			$stmt->bindValue(':nodeId', $nodeId);
		}
		else
		{
			$stmt = $this->prepareStatement('DELETE FROM `f_permission_compiled` WHERE `node_id` = :nodeId AND permission LIKE :permission');
			$stmt->bindValue(':nodeId', $nodeId);
			$stmt->bindValue(':permission', $packageName . '%');
		}
		$this->executeStatement($stmt);
	}	
	
	/**
	 * Permissions defined on $nodeId predicate
	 *
	 * @param integer $nodeId
	 * @return boolean
	 */
	public function hasCompiledPermissions($nodeId)
	{
		$stmt = $this->prepareStatement('SELECT COUNT(*) FROM `f_permission_compiled` WHERE `node_id` = :nodeId');
		$stmt->bindValue(':nodeId', $nodeId);
		$this->executeStatement($stmt);
		return $stmt->fetchColumn()>0;
	}	
	
	/**
	 * Permissions defined on $nodeId for $package predicate
	 *
	 * @param integer $nodeId
	 * @param string $packageName
	 * @return boolean
	 */
	public function hasCompiledPermissionsForPackage($nodeId, $packageName)
	{
		$stmt = $this->prepareStatement('SELECT COUNT(*) FROM `f_permission_compiled` WHERE `node_id` = :nodeId AND `permission` LIKE :permission');
		$stmt->bindValue(':nodeId', $nodeId);
		$stmt->bindValue(':permission', $packageName .'%');
		$this->executeStatement($stmt);
		return $stmt->fetchColumn()>0;
	}	
	
	/**
	 * Checks the existence of a permission on a node for an array of accessors.
	 *
	 * @param array<Integer> $accessors
	 * @param string $fullPermName
	 * @param integer $nodeId
	 * @return boolean
	 */
	public function checkCompiledPermission($accessors, $perm, $node)
	{
		$stmt = $this->prepareStatement('SELECT count(*) FROM `f_permission_compiled` WHERE `accessor_id` IN (' . implode(', ', $accessors). ') AND `permission` = :permission AND `node_id` = :nodeId');
		$stmt->bindValue(':permission', $perm);
		$stmt->bindValue(':nodeId', $node);
		$this->executeStatement($stmt);
		return $stmt->fetchColumn() > 0;
	}	
	
	/**
	 * @param string $permission
	 * @param integer $nodeId
	 * @return array<Integer>
	 */
	public function getAccessorsByPermissionForNode($permission, $nodeId)
	{
		$stmt = $this->prepareStatement('SELECT DISTINCT accessor_id FROM `f_permission_compiled` WHERE `permission` = :permission AND `node_id` = :nodeId');
		$stmt->bindValue(':permission', $permission);
		$stmt->bindValue(':nodeId', $nodeId);
		$this->executeStatement($stmt);
		$result = array();
		foreach ($stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC) as $row)
		{
			$result[] = intval($row['accessor_id']);
		}
		return $result;
	}	

	/**
	 * @param array<Integer> $accessorIds
	 * @param integer $nodeId
	 * @return array<String>
	 */
	public function getPermissionsForUserByNode($accessorIds, $nodeId)
	{
		$stmt = $this->prepareStatement('SELECT DISTINCT `permission` FROM `f_permission_compiled` WHERE `node_id` = :nodeId and `accessor_id` in (' . implode(', ', $accessorIds). ')');
		$stmt->bindValue(':nodeId', $nodeId);
		$this->executeStatement($stmt);
		$result = array();
		while(($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC)) != false)
		{
			$result[] = $row['permission'];
		}
		return $result;
	}
	
	
	public function clearAllPermissions()
	{
		$stmt = $this->prepareStatement('TRUNCATE TABLE f_permission_compiled');
		$this->executeStatement($stmt);
	}	
	
	/**
	 * Get the permission "Definition" points for tree $packageName (ex: modules_website).
	 *
	 * @param string $packageName
	 * @return Array<Integer>
	 */
	public function getPermissionDefinitionPoints($packageName)
	{
		$stmt = $this->prepareStatement('SELECT DISTINCT node_id FROM f_permission_compiled WHERE permission LIKE :permission');
		$stmt->bindValue(':permission', $packageName . '%');
		$this->executeStatement($stmt);
		$result = array();
		while(($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC)) != false)
		{
			$result[] = intval($row['node_id']);
		}
		return $result;
	}
	
	/**
	 * @param string $url
	 * @return f_persistentdocument_I18PersistentDocument[]|null
	 */
	public function getI18nWebsitesFromUrl($url)
	{
		$stmt = $this->prepareStatement('SELECT document_id, lang_i18n FROM m_website_doc_website_i18n WHERE url_i18n = :url');
		$stmt->bindValue(':url', $url, PersistentProviderConst::PARAM_STR);
	
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
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
	 * @param string $blockName
	 * @param array<String> $specs
	 * @param website_persistentdocument_page $page
	 */
	public function registerSimpleCache($cacheId, $specs)
	{
		$deleteStmt = $this->prepareStatement('DELETE FROM f_simplecache_registration WHERE cache_id = :cacheId');
		$deleteStmt->bindValue(':cacheId', $cacheId, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($deleteStmt);
	
		if (f_util_ArrayUtils::isNotEmpty($specs))
		{
			$registerQuery = 'INSERT INTO f_simplecache_registration VALUES (:pattern, :cacheId)';
			foreach (array_unique($specs) as $spec)
			{
				$stmt = $this->prepareStatement($registerQuery);
				$stmt->bindValue(':pattern', $spec, PersistentProviderConst::PARAM_STR);
				$stmt->bindValue(':cacheId', $cacheId, PersistentProviderConst::PARAM_STR);
				$this->executeStatement($stmt);
			}
		}
	}	
	
	/**
	 * @param string $pattern
	 */
	public function getCacheIdsByPattern($pattern)
	{
		$stmt = $this->prepareStatement('SELECT DISTINCT(cache_id) FROM f_simplecache_registration WHERE pattern = :pattern');
		$stmt->bindValue(':pattern', $pattern);
		$this->executeStatement($stmt);
		$blockNames = array();
		foreach ($stmt->fetchAll(PersistentProviderConst::FETCH_NUM) as $row)
		{
			$blockNames[] = $row[0];
		}
		return $blockNames;
	}
	
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
	public function addUserActionEntry($date_entry, $userId, $moduleName, $actionName, $documentId, $username, $serializedInfo)
	{
		$stmt = $this->prepareStatement('INSERT INTO f_user_action_entry (entry_date, user_id, document_id, module_name, action_name, username, info) VALUES (:entry_date, :user_id, :document_id, :module_name, :action_name, :username, :info)');
		$stmt->bindValue(':entry_date', $date_entry, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':user_id', $userId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':module_name', $moduleName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':action_name', $actionName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':username', $username, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':info', $serializedInfo, PersistentProviderConst::PARAM_LOB);
		$this->executeStatement($stmt);
		return intval($this->getLastInsertId('f_user_action_entry'));
	}	
	
	/**
	 * @param integer $userId
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $documentId
	 * @return integer
	 */
	public function getCountUserActionEntry($userId, $moduleName, $actionName, $documentId)
	{
		$stmt = $this->prepareStatement($this->getUserActionEntryQuery($userId, $moduleName, $actionName, $documentId, null, null, null, null));
		if ($userId !== null) {$stmt->bindValue(':user_id', $userId, PersistentProviderConst::PARAM_INT);}
		if ($moduleName !== null) {$stmt->bindValue(':module_name', $moduleName, PersistentProviderConst::PARAM_STR);}
		if ($actionName !== null) {$stmt->bindValue(':action_name', $actionName, PersistentProviderConst::PARAM_STR);}
		if ($documentId !== null) {$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);}
		$this->executeStatement($stmt);
		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		if ($result)
		{
			return intval($result['countentry']);
		}
		return 0;
	}	
	
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
	public function getUserActionEntry($userId, $moduleName, $actionName, $documentId, $rowIndex, $rowCount, $sortOnField, $sortDirection)
	{
		$stmt = $this->prepareStatement($this->getUserActionEntryQuery($userId, $moduleName, $actionName, $documentId, $rowIndex, $rowCount, $sortOnField, $sortDirection));
		if ($userId !== null) {$stmt->bindValue(':user_id', $userId, PersistentProviderConst::PARAM_INT);}
		if ($moduleName !== null) {$stmt->bindValue(':module_name', $moduleName, PersistentProviderConst::PARAM_STR);}
		if ($actionName !== null) {$stmt->bindValue(':action_name', $actionName, PersistentProviderConst::PARAM_STR);}
		if ($documentId !== null) {$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);}
		$this->executeStatement($stmt);
		$result = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		return $result;
	}	
	
	protected function getUserActionEntryQuery($userId, $moduleName, $actionName, $documentId, $rowIndex, $rowCount, $sortOnField, $sortDirection)
	{
		if ($rowIndex === null)
		{
			$sql = "SELECT count(*) as countentry FROM f_user_action_entry as uac";
		}
		else
		{
			$sql = "SELECT uac.entry_id, uac.entry_date, uac.user_id, uac.document_id, uac.module_name, uac.action_name, uac.info, d.document_id as link_id FROM f_user_action_entry as uac left outer join f_document as d on uac.document_id = d.document_id";
		}
		$where = array();
		if ($userId !== null) {$where[] = 'uac.user_id = :user_id';}
		if ($moduleName !== null) {$where[] = 'uac.module_name = :module_name';}
		if ($actionName !== null) {$where[] = 'uac.action_name = :action_name';}
		if ($documentId !== null) {$where[] = 'uac.document_id = :document_id';}
		if (count($where) > 0) {$sql .= " WHERE " . implode(" AND ", $where);}
		if ($rowIndex === null)
		{
			return $sql;
		}
		if ($sortDirection != 'DESC') {$sortDirection = 'ASC';}
		if ($sortOnField == 'user')
		{
			$sql .= " ORDER BY uac.username " . $sortDirection;
		}
		else
		{
			$sql .= " ORDER BY uac.entry_id " . $sortDirection;
		}
		return $sql . " LIMIT " . intval($rowIndex) .", " . intval($rowCount);
	}	
	
	/**
	 * @param string $fieldName (document | module | action | [user])
	 * @return array<array<distinctvalue => VALUE>>
	 */
	public function getDistinctLogEntry($fieldName)
	{
		switch ($fieldName)
		{
			case 'document': $sqlName = 'document_id'; break;
			case 'module': $sqlName = 'module_name'; break;
			case 'action': $sqlName = 'action_name'; break;
			default: $sqlName = 'user_id'; break;
		}
		$sql = 'SELECT '.$sqlName.' as distinctvalue FROM f_user_action_entry GROUP BY '.$sqlName;
		
		$stmt = $this->prepareStatement($sql);
		$this->executeStatement($stmt);
		$result = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		return $result;
	}	

	/**
	 * @param string $date
	 * @param string|null $moduleName
	 */
	public function deleteUserActionEntries($date, $moduleName = null)
	{
		if ($moduleName !== null)
		{
			$sql = 'DELETE FROM f_user_action_entry WHERE entry_date < :entry_date AND module_name = :module_name';
		}
		else
		{
			$sql = 'DELETE FROM f_user_action_entry WHERE entry_date < :entry_date';
		}
	
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':entry_date', $date, PersistentProviderConst::PARAM_STR);
	
		if ($moduleName !== null)
		{
			$stmt->bindValue(':module_name', $moduleName, PersistentProviderConst::PARAM_STR);
		}
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	}
	
	/**
	 * @param integer $documentId
	 * @param array<status, lastupdate>
	 */
	public function getIndexingDocumentStatus($documentId)
	{
		$stmt = $this->prepareStatement("SELECT `indexing_status`, `lastupdate` FROM `f_indexing` WHERE `document_id` = :document_id");
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		if ($result)
		{
			return array($result['indexing_status'], $result['lastupdate']);
		}
		return array(null, null);
	}
		
	/**
	 * @param integer $documentId
	 * @param string $newStatus
	 * @param string $lastUpdate
	 */
	public function setIndexingDocumentStatus($documentId, $newStatus, $lastUpdate = null)
	{
		$stmt = $this->prepareStatement("SELECT `indexing_status` FROM `f_indexing` WHERE `document_id` = :document_id FOR UPDATE");
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(PersistentProviderConst::FETCH_NUM);
		$stmt->closeCursor();
		if (is_array($result) && $result[0] === $newStatus)
		{
			return array($newStatus, null);
		}
	
		if (is_array($result))
		{
			$updatestmt = $this->prepareStatement("UPDATE `f_indexing` SET `indexing_status` = :indexing_status, `lastupdate` = :lastupdate WHERE `document_id` = :document_id");
		}
		else
		{
			$updatestmt = $this->prepareStatement("INSERT INTO `f_indexing` (`indexing_status`, `lastupdate`, `document_id`) VALUES (:indexing_status, :lastupdate, :document_id)");
		}
	
		if ($lastUpdate === null) {$lastUpdate = date_Calendar::getInstance()->toString();}
		$updatestmt->bindValue(':indexing_status', $newStatus, PersistentProviderConst::PARAM_STR);
		$updatestmt->bindValue(':lastupdate', $lastUpdate, PersistentProviderConst::PARAM_STR);
		$updatestmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($updatestmt);
		return array($newStatus, $lastUpdate);
	}	
	
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	public function deleteIndexingDocumentStatus($documentId)
	{
		$stmt = $this->prepareStatement("DELETE FROM `f_indexing` WHERE `document_id` = :document_id");
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->rowCount() == 1;
	}	
	
	/**
	 * @return integer
	 */
	public function clearIndexingDocumentStatus()
	{
		$stmt = $this->prepareStatement("DELETE FROM `f_indexing`");
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	}
	
	/**
	 * @return array<indexing_status =>, nb_document =>, max_id>
	 */
	public function getIndexingStats()
	{
		$stmt = $this->prepareStatement("SELECT `indexing_status`, count(`document_id`) as nb_document,  max(`document_id`) as max_id FROM `f_indexing` GROUP BY `indexing_status`");
		$this->executeStatement($stmt);
		$result = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		return $result;
	}	

	/**
	 * @return array<max_id => integer >
	 */
	public function getIndexingPendingEntries()
	{
		$stmt = $this->prepareStatement("SELECT max(`document_id`) as max_id FROM `f_indexing` WHERE `indexing_status` <> 'INDEXED'");
		$this->executeStatement($stmt);
		$result = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @param integer $maxDocumentId
	 * @param integer $chunkSize
	 * @param integer[]
	 */
	public function getIndexingDocuments($maxDocumentId, $chunkSize = 100)
	{
		$stmt = $this->prepareStatement("SELECT `document_id` FROM `f_indexing` WHERE `document_id` <= :document_id AND `indexing_status` <> 'INDEXED' ORDER BY `document_id` DESC LIMIT 0, " .intval($chunkSize));
		$stmt->bindValue(':document_id', $maxDocumentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$rows = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		$result = array();
		foreach ($rows as $row)
		{
			$result[] = intval($row['document_id']);
		}
		return $result;
	}
}