<?php
/**
 * @package framework.persistentdocument
 */
class PersistentProviderConst
{
	const FETCH_ASSOC = 'FETCH_ASSOC';
	const FETCH_NUM = 'FETCH_NUM';

	const PARAM_INT = 'PARAM_INT';
	const PARAM_NUM = 'PARAM_NUM';
	const PARAM_DATE = 'PARAM_DATE';
	const PARAM_STR = 'PARAM_STR';
	const PARAM_NULL = 'PARAM_NULL';
	const PARAM_LOB = 'PARAM_LOB';
	const PARAM_FLOAT = 'PARAM_FLOAT';
}

abstract class f_persistentdocument_PersistentProvider
{
	/**
	 * the singleton instance
	 * @var f_persistentdocument_PersistentProvider
	 */
	protected static $m_instance = null;	

	/**
	 * @var array mapping (DB type => PDODriverClassName)
	 */
	protected static $m_classByDriverName;

	/**
	 * @var PDO instance provided by PDODatabase
	 */
	private $m_driver = null;
	private $m_inTransaction = false;

	/**
	 * Temporay identifier for new persistent document
	 * @var Integer
	 */
	private $m_newInstancesCounter = 0;

	/**
	 * instance scope cache of document class names by type (ie. 'model')
	 * @var array<String,String>
	 */
	private $m_documentClassByType = array();

	/**
	 * @var boolean
	 */
	protected $useDocumentCache = true;
	/**
	 * Document instances by id
	 * @var array<Integer, f_persistentdocument_PersistentDocument>
	 */
	protected $m_documentInstances = array();

	/**
	 * I18nDocument instances by id
	 * @var array<Integer, f_persistentdocument_I18nPersistentDocument>
	 */
	protected $m_i18nDocumentInstances = array();

	/**
	 * @var array
	 */
	protected $connectionInfos;
		
	/**
	 * @var Integer
	 */
	private $id;
	

	private $i18nfieldNames;

	/**
	 * @var String
	 */
	protected static $databaseProfile;

	/**
	 * This variable is used to protect name field on sql request for having a difference for example
	 * between date (the sql type) and date (the field name)
	 * Nothing by default.
	 *
	 * @var String
	 */
	protected $sql_fieldescape = '';

	protected function escapeFieldName($fieldName)
	{
		return $this->sql_fieldescape . $fieldName . $this->sql_fieldescape;
	}

	protected function escapeParamName($name, $usePrefix = false)
	{
		return ':'. ($usePrefix) ? 'p' : '' . $name;
	}

	/**
	 * Return the current document persistent provider
	 * @return f_persistentdocument_PersistentProvider
	 */
	public static function getInstance()
	{
		if (self::$m_instance == null)
		{
			// load database configuration into self::$m_classByDriverName
			self::$m_classByDriverName = Framework::getConfiguration('persistent_provider');
			$connectionInfos = self::_getConnectionInfos();
			$driverName = $connectionInfos['protocol'];

			if (!array_key_exists($driverName, self::$m_classByDriverName))
			{
				throw new Exception('Unknown persistent provider protocol '.$driverName);
			}

			$instance = new self::$m_classByDriverName[$driverName];
			$instance->connectionInfos = $connectionInfos;

			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__.'('. get_class($instance) .')');
			}
			self::$m_instance = $instance;
		}
		return self::$m_instance;
	}
	
	/**
	 * @return Integer
	 */
	function getId()
	{
		return $this->id;
	}

	/**
	 * @param String $profile profile name. Use null to have default behaviour
	 */
	public static function setDatabaseProfileName($profile)
	{
		$wasSetted = (self::$databaseProfile !== null);
		self::$databaseProfile = $profile;
		if ($wasSetted)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' ' . $profile);
			}
			f_persistentdocument_PersistentProvider::refresh();
		}
	}

	/**
	 * @return String the database profile, 'default' by default
	 */
	public static function getDatabaseProfileName()
	{
		if (self::$databaseProfile === null)
		{
			self::$databaseProfile = 'default';
		}
		return self::$databaseProfile;
	}

	protected static function _getConnectionInfos()
	{
		$profileName = self::getDatabaseProfileName();
		$connections = Framework::getConfiguration('databases');
		$connection = $connections[$connections['connections'][$profileName]];
		return $connection;
	}

	function getConnectionInfos()
	{
		return $this->connectionInfos;
	}

	public static function refresh()
	{
		if (self::$m_instance === null)
		{
			return;
		}

		self::$m_instance->closeConnection();
		// instances are refreshed on demand during transaction, so we do not clear the document cache.
		// self::$m_instance->clearDocumentCache();
		self::$m_instance->connectionInfos = self::_getConnectionInfos();
	}

	public static function clearInstance()
	{
		self::$m_instance = null;
	}

	/**
	 *
	 * @param String $documentModelName
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
	 * @param boolean $useDocumentCache
	 * @return f_persistentdocument_PersistentProvider
	 */
	public function setDocumentCache($useDocumentCache)
	{
		$this->useDocumentCache = $useDocumentCache;
		if (!$this->useDocumentCache)
		{
			$this->clearDocumentCache();
		}
		return $this;
	}

	/**
	 * @param array<Integer> $ids
	 */
	public function initDocumentCache($ids)
	{
		$begin = microtime(true);
		$docInstances = $this->getCacheService()->getMultiple($ids);
		if ($docInstances === false)
		{
			Framework::error(get_class().'->initDocumentCache didnt worked for '.var_export($ids, true));
			return;
		}

		$this->m_documentInstances = &$docInstances;
		if (Framework::isDebugEnabled())
		{
			$end = microtime(true);
			Framework::debug('Time initDocumentCache '.($end-$begin).' s, '.count($this->m_documentInstances).' documents');
			//Framework::debug('INITDOCUMENTCACHE '.var_export($this->m_documentInstances, true));
		}
	}

	/**
	 * @return array<Integer>
	 */
	public function getCollectedDocumentIds()
	{
		return array_keys($this->m_documentInstances);
	}

	/**
	 * @param PDO $driver
	 */
	public function setDriver($driver)
	{
		$this->m_driver = $driver;
	}

	/**
	 * @return PDO
	 */
	public function getDriver()
	{
		if (is_null($this->m_driver))
		{
			$this->m_driver = $this->getConnection($this->connectionInfos);
			register_shutdown_function(array($this, "closeConnection"));
		}

		return $this->m_driver;
	}

	/**
	 * @return Boolean
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
	 * @param array<String, String> $connectionInfos
	 * @return mixed
	 */
	protected abstract function getConnection($connectionInfos);

	/**
	 * @param c_Properties $props
	 * @throws Exception on error
	 * @return Boolean true if database was created
	 */
	abstract function createDB($props);

	/**
	 * @throws Exception on error
	 */
	abstract function clearDB();

	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @param generator_PersistentProperty $oldProperty
	 * @return String[] the SQL statements that where executed
	 */
	abstract function delProperty($moduleName, $documentName, $oldProperty);

	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @param generator_PersistentProperty $oldProperty
	 * @param generator_PersistentProperty $newProperty
	 * @return String[] the SQL statements that where executed
	 */
	abstract function renameProperty($moduleName, $documentName, $oldProperty, $newProperty);

	public abstract function closeConnection();

	protected abstract function errorCode();

	protected abstract function errorInfo();

	/**
	 * @param String $script
	 */
	public abstract function executeSQLScript($script);

	/**
	 * @param String $script
	 * @return PDOStatement
	 */
	public abstract function executeSQLSelect($script);

	private function compareRows($row1, $row2)
	{
		return (int)$row1["id"] - (int)$row2["id"];
	}

	private function getIdFromRow($row)
	{
		return $row["id"];
	}
	
	/**
	 * @return array<string, string>
	 * return[0] => allowed file Extension
	 * return[1] => sql script Separator
	 */
	public function getScriptFileInfos()
	{
		return array(null, null);
	}
	
	/**
	 * @param array<tableName=>string, moduleName=>string, documentName=>string, tableNameOci=>string> $properties
	 * @return string
	 */
	public function generateTableName($properties)
	{
		if ($properties['tableName'])
		{
			return $properties['tableName'];
		}
		return strtolower("m_". $properties['moduleName'] ."_doc_" . $properties['documentName']);
	}

	/**
	 * @param array<dbMapping=>string, name=>string, dbMappingOci=>string> $properties
	 * @return string
	 */
	public function generateFieldName($properties)
	{
		return (is_null($properties['dbMapping'])) ?  strtolower($properties['name']) : $properties['dbMapping'];
	}

	/**
	 * @param f_persistentdocument_criteria_Query $query
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public final function find($query)
	{
		if ($query->hasHavingCriterion() && !$query->hasProjection())
		{
			// implicit this projection
			$query->setProjection(Projections::this());
		}
		$params = array();
		$queryStr = $this->buildQueryString($query, $params);

		$statement = $this->prepareStatement($queryStr);
		// N.B.: we must check if errorCode is a real error code since execute()
		// can return false for correct executions !
		if ($statement->execute($params) === false && $statement->errorCode() !== '00000')
		{
			throw new Exception("Error while executing :[$queryStr]" . " ". var_export($params, true) .':' . join(', ', $statement->errorInfo()));
		}
		$rows = $statement->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		//Framework::debug("FIND from : ".f_util_ProcessUtils::getBackTrace());
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
						$document = $this->getDocumentInstanceWithModelName($documentId, $row['document_model'], $row['treeid'], $row, true);
						if ($isLocalized)
						{
							$this->buildI18nDocument($document, $document->getLang(), $row);
						}
						$this->_loadDocument($document, $row);
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
	 * @param String $columnName the name of the projection
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
	 * @return Integer[]
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
	 * @return Integer[]
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
	 * @param f_persistentdocument_criteria_ExecutableQuery $query
	 * @param array $params the parameters for the query, created and filled by buildQueryString
	 * @return String the queryString as a pdo preparedStatement
	 */
	protected abstract function buildQueryString($query, &$params);

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
	public final function findUnique($query)
	{
		$docs = $this->find($query->setMaxResults(2));

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

	protected function debug($message)
	{
		Framework::debug($message);
	}

	/**
	 * @param MysqlStatment $statement
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
	 * Return a instance of the document
	 * or Exception if the document not found
	 * @param int $id
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getDocumentInstance($id, $modelName = null, $lang = null)
	{
		if (!is_numeric($id) || $id <= 0)
		{
			throw new IllegalArgumentException('id', 'integer');
		}
		$documentId = intval($id);

		if (!$this->isInCache($documentId))
		{
			if ($this->useDocumentCache)
			{
				$cached = $this->getCacheService()->get($documentId);
				if ($cached !== null)
				{
					$this->putInCache($id, $cached);
					return  $this->checkModelCompatibility($cached, $modelName);
				}
			}
			if ($modelName === null)
			{
				$sql = $this->getDocumentInstanceQuery();
			}
			else
			{
				$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
				// TODO: not *, especially if a lang is requested
				$sql = "select * from ".$model->getTableName()." inner join f_document using(document_id)";
				$where = array($model->getTableName().".document_id = :document_id");
				if ($lang !== null && $model->isLocalized())
				{
					$sql .= " inner join ".$model->getTableName() . $this->getI18nSuffix()." using(document_id)";
					$where[] = "lang_i18n = :lang";
				}
				$sql .= " where ".join(" and ", $where);
			}
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
			if ($lang !== null)
			{
				$stmt->bindValue(':lang', $lang, PersistentProviderConst::PARAM_STR);
			}
			$this->executeStatement($stmt);

			$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
			$stmt->closeCursor();

			if (!$result)
			{
				throw new BaseException('object-not-found', 'framework.exception.errors.Object-not-found', array('id' => $documentId));
			}
			if ($modelName === null)
			{
				return $this->checkModelCompatibility($this->getDocumentInstanceWithModelName($documentId, $result['document_model'], $result['treeid'], $result, true), $modelName);
			}
			$document = $this->getDocumentInstanceWithModelName($documentId, $result['document_model'], $result['treeid'], $result, true);
			if ($lang !== null && $result['document_lang'] != $lang)
			{
				$i18nDoc = $this->buildI18nDocument($document, $lang, $result);
			}
			$this->_loadDocument($document, $result);
			if ($this->useDocumentCache)
			{
				$this->getCacheService()->set($documentId, $document);
			}

			return $this->checkModelCompatibility($document, $modelName);;
		}
		$document = $this->getFromCache($documentId);
		return $this->checkModelCompatibility($document, $modelName);
	}
	
	private function checkModelCompatibility($document, $modelName)
	{
		if ($modelName !== null && !$document->getPersistentModel()->isModelCompatible($modelName))
		{
			
			throw new Exception('document ' . $document->getId() . ' is a ' . $document->getDocumentModelName() . ' but not a ' . $modelName);
		}
		return $document;
	}

	private function getI18nDocumentFromCache($doc, $lang)
	{
		$docId = $doc->getId();
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
	 * @param f_persistentdocument_PersistentDocument $doc
	 * @param String $lang
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
			// TODO : if isVo, get the data from the persistentDocument
			if ($isVo && $doc->getDocumentPersistentState() === f_persistentdocument_PersistentDocument::PERSISTENTSTATE_LOADED)
			{
				Framework::info(__METHOD__." could be optimized");
				// argh !! the document does not have the vo properties !
				/*
				$properties = $doc->getDocumentProperties(false); // this can not be done as it checks for i18nObject ...
				foreach ($model->getPropertiesInfos() as $key => $propertyInfo)
				{
				if ($propertyInfo->isLocalized())
				{
				$properties[$key] = $documentProperties[$key];
				}
				}*/
			}
			// else
			{
				$sql = $this->getI18nDocumentQuery($model->getTableName() . $this->getI18nSuffix());
				$stmt = $this->prepareStatement($sql);
				$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':lang', $lang, PersistentProviderConst::PARAM_STR);

				$this->executeStatement($stmt);
				$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
				if (count($results) > 0)
				{
					$dbresult = $results[0];
					$properties = array();
					// TODO : $model::getLocalizedPropertiesInfos() to avoid the if
					foreach ($model->getPropertiesInfos() as $key => $propertyInfo)
					{
						if ($propertyInfo->isLocalized())
						{
							$properties[$key] = $dbresult[$propertyInfo->getDbMapping() . $this->getI18nSuffix()];
						}
					}
				}
			}
		}

		return $this->buildI18nDocument($doc, $lang, $properties);
	}

	private function buildI18nDocument($doc, $lang, $result = null)
	{
		$documentId = $doc->getId();
		$model = $doc->getPersistentModel();
		$className = $this->getI18nDocumentClassFromModel($model->getName());

		$i18nDoc = new $className($documentId, $lang, $result === null);

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
	 * @param String $tableName
	 * @return String
	 */
	protected abstract function getI18nDocumentQuery($tableName);

	/**
	 * @return String
	 */
	protected abstract function getDocumentInstanceQuery();

	/**
	 * Return a instance of the document[@id = $id and @modelName = $modelName]
	 *
	 * @param integer $id
	 * @param String $modelName
	 * @param Integer $treeId
	 * @param Array $I18nInfoArray
	 * @param Boolean $cacheServiceCalled
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected final function getDocumentInstanceWithModelName($id, $modelName, $treeId, $I18nInfoArray, $cacheServiceCalled = false)
	{
		if (!$this->isInCache($id))
		{
			if ($this->useDocumentCache && !$cacheServiceCalled)
			{
				$cached = $this->getCacheService()->get($id);

				if ($cached !== null)
				{
					$this->putInCache($id, $cached);
					return $cached;
				}
			}

			$className = $this->getDocumentClassFromModel($modelName);
			if (f_util_ArrayUtils::isEmpty($I18nInfoArray))
			{
				$i18nInfo = null;
			}
			else
			{
				$i18nInfo = I18nInfo::getInstanceFromArray($I18nInfoArray);
			}
			$doc = new $className($id, $i18nInfo, $treeId);
			$this->putInCache($id, $doc);
			return $doc;
		}
		return $this->getFromCache($id);
	}

	/**
	 * Return the name of the document
	 *
	 * @param Integer $id
	 * @return String the name of the document model or false
	 */
	public function getDocumentModelName($id)
	{
		if ($this->isInCache($id))
		{
			return $this->getFromCache($id)->getDocumentModelName();
		}
		$sql = $this->getDocumentModelNameQuery();
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $id, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);

		if (count($results) > 0)
		{
			return $results[0]['document_model'];
		}
		return false;
	}


	/**
	 * @return String
	 */
	protected abstract function getDocumentModelNameQuery();

	/**
	 * @param String $documentModelName
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
	 *
	 * @param String $modelName
	 * @return String
	 */
	protected function getDocumentClassFromModel($modelName)
	{
		if (isset($this->m_documentClassByType[$modelName]))
		{
			return $this->m_documentClassByType[$modelName];
		}
		$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
		$className = $model->getModuleName()."_persistentdocument_".$model->getDocumentName();
		$this->m_documentClassByType[$modelName] = $className;
		return $className;
	}

	/**
	 * Return the I18n persistent document class name from the document model name
	 * @param String $modelName
	 * @return String
	 */
	protected function getI18nDocumentClassFromModel($modelName)
	{
		return $this->getDocumentClassFromModel($modelName).'I18n';
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param f_persistentdocument_PersistentDocument $destDocument
	 * @return f_persistentdocument_PersistentDocument the result of mutation (destDocument)
	 */
	public final function mutate($document, $destDocument)
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
			$stmt = $this->prepareStatement($this->mutateFrameworkQuery());
			$stmt->bindValue(':destmodelname', $destModelName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);

			// Update model name in f_relation table
			$stmt = $this->prepareStatement($this->mutateRelationId1Query());
			$stmt->bindValue(':destmodelname', $destModelName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
			$stmt = $this->prepareStatement($this->mutateRelationId2Query());
			$stmt->bindValue(':destmodelname', $destModelName, PersistentProviderConst::PARAM_STR);
			$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':sourcemodelname', $sourceModelName, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);

			// Update model name in document table
			$tableName = $sourceModel->getTableName();
			$stmt = $this->prepareStatement($this->mutateDocumentQuery($tableName));
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
	 * @return String
	 */
	protected abstract function mutateFrameworkQuery();

	/**
	 * @return String
	 */
	protected abstract function mutateRelationId1Query();

	/**
	 * @return String
	 */
	protected abstract function mutateRelationId2Query();

	/**
	 * @return String
	 */
	protected abstract function mutateDocumentQuery($table);

	/**
	 * @return String
	 */
	protected abstract function mutateDocumentI18NQuery($tableI18N);

	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public function insertDocument($persistentDocument)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::insertDocument : " .get_class($persistentDocument));
		}
		$documentId = $this->getNewDocumentId($persistentDocument);
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::insertDocument newId : " . $documentId);
		}
		$this->_insertDocument($documentId, $persistentDocument);
	}

	/**
	 * When we want to get a document, the data is not loaded. When we want to access to it,
	 * this function is called for giving all data to the object.
	 *
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public function loadDocument($persistentDocument)
	{
		$documentId = $persistentDocument->getId();

		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::loadDocument ($documentId) ". get_class($persistentDocument));
		}
		$table = $persistentDocument->getPersistentModel()->getTableName();

		$sql = $this->getLoadDocumentQuery($table);
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();

		if ($result)
		{
			$this->_loadDocument($persistentDocument, $result);

			if ($this->useDocumentCache)
			{
				$this->getCacheService()->set($documentId, $persistentDocument);
			}
		}
		else
		{
			throw new Exception(get_class().'->loadDocument : could not load document[@id = '.$persistentDocument->getId().']');
		}
	}


	/**
	 * @return String
	 */
	protected abstract function getLoadDocumentQuery($table);

	/**
	 * Initialize un document avec une ligne de resultat de la base de donnée
	 *
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @param array $dbresult contient statement->fetch(PersistentProviderConst::FETCH_ASSOC)
	 */
	protected function _loadDocument($persistentDocument, $dbresult)
	{
		$documentModel = $persistentDocument->getPersistentModel();
		$propertiesInfo = $documentModel->getPropertiesInfos();
		$properties = array('id' => $persistentDocument->getId());

		foreach ($propertiesInfo as $key => $value)
		{
			if ($value->isDocument())
			{
				$dbvalue = intval($dbresult[$value->getDbMapping()]);
				if ($value->isArray())
				{
					$properties[$key] = $dbvalue;
				}
				else if ($dbvalue)
				{
					$properties[$key] = $dbvalue;
				}
				else
				{
					$properties[$key] = null;
				}
			}
			else
			{
				$properties[$key] = $dbresult[$value->getDbMapping()];
			}
		}

		if ($documentModel->isLocalized())
		{
			//Utilisé pour initialiser l'entrée du cache
			$lang = $properties["lang"];
			if ($this->getI18nDocumentFromCache($persistentDocument, $lang) === null)
			{
				$i18nDoc = $this->buildI18nDocument($persistentDocument, $lang, $properties);
				$persistentDocument->setI18nVoObject($i18nDoc);
			}
		}

		$persistentDocument->setDocumentProperties($properties);

		$persistentDocument->setDocumentPersistentState(f_persistentdocument_PersistentDocument::PERSISTENTSTATE_LOADED);
	}

	/**
	 * Update a document.
	 *
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public function updateDocument($persistentDocument)
	{
		$documentId = $persistentDocument->getId();
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::updateDocument ($documentId) ". get_class($persistentDocument));
		}


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
						$this->_insertI18nDocument($i18nDocument, $documentModel);
					}
					elseif ($i18nDocument->isModified())
					{
						//echo "I18N has been modified";
						$this->_updateI18nDocument($i18nDocument, $documentModel);
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
			$sql = $this->getUpdateDocumentLangs();
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
			//Framework::debug("Property : $propertyName");
			if ($propertyName == 'id' || $propertyName == 'model' || !$persistentDocument->isPropertyModified($propertyName))
			{
				continue;
			}

			$propertyInfo = $propertiesInfo[$propertyName];

			if (!$propertyInfo->isDocument())
			{
				$dbname = $this->escapeFieldName($propertyInfo->getDbMapping());
				$dbparemetername = ':p' .$propertyInfo->getDbMapping();

				if ($propertyInfo->isLob())
				{
					$lobParameters[$propertyName] = array($dbname, $dbparemetername);
				}
				$mapping[$propertyName] = $dbname . " = " . $dbparemetername;
			}
			elseif ($propertyValue !== null && $propertyValue->isModified())
			{
				$mapping[$propertyName] = $this->escapeFieldName($propertyInfo->getDbMapping()) . "= :p" .$propertyInfo->getDbMapping();
			}

			if ($propertyValue instanceof f_persistentdocument_PersistentDocumentArray)
			{
				$this->cascadeSaveDocumentArray($propertyValue);
			}
		}

		$dataRelations = array();

		if (f_util_ArrayUtils::isNotEmpty($mapping))
		{
			$sql = $this->getUpdateDocumentQuery($persistentDocument->getPersistentModel()->getTableName(), $mapping, $lobParameters);
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
	 * @param Integer $documentId
	 * @param f_persistentdocumentPersistentDocument $persistentDocument
	 * @param boolean $clearCache
	 */
	protected function postUpdate($documentId, $persistentDocument, $clearCache = true)
	{
		$this->putInCache($documentId, $persistentDocument);
		if ($clearCache && $this->useDocumentCache)
		{
			$this->getCacheService()->set($documentId, null);
		}
	}

	/**
	 * @return String
	 */
	protected abstract function getUpdateDocumentLangs();


	/**
	 * @param f_persistentdocument_I18nPersistentDocument $i18nDocument
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 */
	protected function _updateI18nDocument($i18nDocument, $documentModel)
	{
		$table = $documentModel->getTableName() . $this->getI18nSuffix();
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

			$dbname = $this->escapeFieldName($propertyInfo->getDbMapping() . $this->getI18nSuffix());
			$dbparemetername = ":p" .$propertyInfo->getDbMapping();

			if ($propertyInfo->isLob())
			{
				$lobParameters[$propertyName] = array($dbname, $dbparemetername);
			}

			$mapping[$propertyName] = $dbname . " = " . $dbparemetername;
		}
		$sql = $this->getUpdateI18nDocumentQuery($table, $mapping, $lobParameters);
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':id', $i18nDocument->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PersistentProviderConst::PARAM_STR);
		foreach ($mapping as $propertyName => $tmp)
		{
			$this->bindValue($stmt, $properties[$propertyName], $documentModel->getProperty($propertyName));
		}
		$this->executeStatement($stmt);

		$i18nDocument->setIsPersisted();

		$this->m_i18nDocumentInstances[$i18nDocument->getId()][$i18nDocument->getLang()] = $i18nDocument;
	}

	/**
	 * @param String $table
	 * @param array<String, String> $mapping
	 * @param array<String, array<>> $lobParameters
	 * @return String
	 */
	protected abstract function getUpdateI18nDocumentQuery($table, $mapping, $lobParameters = array());

	protected function _deleteI18nDocument($i18nDocument, $documentModel)
	{
		$table = $documentModel->getTableName() . $this->getI18nSuffix();
		$stmt = $this->prepareStatement($this->getDeleteI18nDocument($table));
		$stmt->bindValue(':id', $i18nDocument->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$this->m_i18nDocumentInstances[$i18nDocument->getId()][$i18nDocument->getLang()] = null;
	}
	/**
	 * @param String $tableName
	 * @return String
	 */
	protected abstract function getDeleteI18nDocument($tableName);



	/**
	 * @param String $table
	 * @param array<String, String> $mapping
	 * @param array<String, array<>> $lobParameters
	 * @return String
	 */
	protected abstract function getUpdateDocumentQuery($table, $mapping, $lobParameters = array());

	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	public function deleteDocument($persistentDocument)
	{
		$documentId = $persistentDocument->getId();
		$lang = $persistentDocument->getLang();
		
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::deleteDocument($documentId, $lang) ". get_class($persistentDocument));
		}
		$documentModel = $persistentDocument->getPersistentModel();

		$deleteDocumentInstance = true;
		if ($documentModel->isLocalized())
		{
			if (!$persistentDocument->isContextLangAvailable())
			{
				//Le document n'existe pas dans la langue du context on ne fait rien
				return;
			}
			$langCount = $persistentDocument->removeContextLang();
			$deleteDocumentInstance = ($langCount == 0);

			//On supprime physiquement la traduction
			$contextLang = RequestContext::getInstance()->getLang();
			$i18nDocument = $this->getI18nDocument($persistentDocument, $contextLang);
			$this->_deleteI18nDocument($i18nDocument, $documentModel);
			unset($this->m_i18nDocumentInstances[$documentId][$contextLang]);
		}

		if (!$deleteDocumentInstance)
		{
			$this->updateDocument($persistentDocument);
		}
		else
		{
			if ($documentModel->hasCascadeDelete())
			{
				$persistentDocument->preCascadeDelete();
			}

			$table = $persistentDocument->getPersistentModel()->getTableName();
			$sql = $this->getDeleteDocumentQuery1();
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);

			$deletedrow = $stmt->rowCount();
			if ($deletedrow != 0)
			{
				$sql = $this->getDeleteDocumentQuery2($table);
				$stmt = $this->prepareStatement($sql);
				$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
				$this->executeStatement($stmt);

				$stmt = $this->prepareStatement($this->getDeleteDocumentQuery3());;
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

	/**
	 * @return String
	 */
	protected abstract function getDeleteDocumentQuery1();

	/**
	 * @return String
	 */
	protected abstract function getDeleteDocumentQuery2($table);

	/**
	 * @return String
	 */
	protected abstract function getDeleteDocumentQuery3();


	public function beginTransaction()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::beginTransaction");
		}
		if ($this->m_inTransaction)
		{
			Framework::warn("PersistentProvider->beginTransaction() while already in transaction");
		}
		else
		{
			$this->beginTransactionInternal();
			$this->m_inTransaction = true;
			if ($this->useDocumentCache)
			{
				$this->getCacheService()->beginTransaction();
			}
			indexer_IndexService::getInstance()->beginIndexTransaction();
		}
	}

	protected abstract function beginTransactionInternal();


	public function commit()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::commit");
		}
		if (!$this->m_inTransaction)
		{
			Framework::warn("PersistentProvider->commit() called while not in transaction");
		}
		else
		{
			if ($this->useDocumentCache)
			{
				$this->getCacheService()->commit();
			}
			$this->commitInternal();
			$this->m_inTransaction = false;		
				
			$this->commitIndexService();
		}
	}
	
	protected function commitIndexService()
	{
		$this->beginTransactionInternal();
		indexer_IndexService::getInstance()->commitIndex();
		$this->commitInternal();
	}

	protected abstract function commitInternal();


	public function rollBack()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::rollBack");
		}
		if (!$this->m_inTransaction)
		{
			Framework::warn("PersistentProvider->rollBack() called while not in transaction");
		}
		else
		{
			if ($this->useDocumentCache)
			{
				$this->getCacheService()->rollBack();
			}
			indexer_IndexService::getInstance()->rollBackIndex();
			$this->rollBackInternal();
			$this->m_inTransaction = false;
		}
	}

	protected abstract function rollBackInternal();

	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @return Integer
	 */
	protected function getNewDocumentId($persistentDocument)
	{
		$documentId = $persistentDocument->getId();
		$documentModel = $persistentDocument->getDocumentModelName();
		$documentLangs =  $persistentDocument->getI18nInfo()->toPersistentProviderArray();

		if ($documentId <= 0)
		{
			$stmt = $this->prepareStatement($this->getNewDocumentIdQuery1());
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
			$stmt = $this->prepareStatement($this->getNewDocumentIdQuery2());
			$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':document_model', $documentModel, PersistentProviderConst::PARAM_STR);

			foreach ($this->getI18nFieldNames() as $i18nFieldName)
			{
				$value  = isset($documentLangs[$i18nFieldName]) ? $documentLangs[$i18nFieldName] : NULL;
				$stmt->bindValue(':'.$i18nFieldName, $value, PersistentProviderConst::PARAM_STR);
			}
			$this->executeStatement($stmt);
		}
		return $documentId;
	}

	protected abstract function getLastInsertId($tableName);

	/**
	 * @example INSERT INTO f_document (document_model, lang_vo, label_fr, ...) VALUES (:document_model, :lang_vo, :label_fr, ...)
	 * @return String
	 */
	protected abstract function getNewDocumentIdQuery1();

	/**
	 * @example INSERT INTO f_document (document_id, document_model, lang_vo, label_fr, ...) VALUES (:document_id, :document_model, :lang_vo, :label_fr, ...)';
	 * @return String
	 */
	protected abstract function getNewDocumentIdQuery2();

	/**
	 * @param Integer $documentId
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 */
	protected function _insertDocument($documentId, $persistentDocument)
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
						$this->_insertI18nDocument($i18nDocument, $documentModel);
					}
				}
				unset($this->m_i18nDocumentInstances[$tmpId]);
			}
		}

		$fieldsName = array($this->escapeFieldName('document_id'), $this->escapeFieldName('document_model'));
		$parameters = array(':document_id', ':document_model');
		$lobParameters = array();

		foreach ($propertiesInfo as $propertyName => $propertyInfo)
		{
			if ('id' == $propertyName || 'model' == $propertyName)
			{
				continue;
			}

			$fieldsName[$propertyName] = $this->escapeFieldName($propertyInfo->getDbMapping());
			$parameters[$propertyName] = ":p". $propertyInfo->getDbMapping();
			if ($propertyInfo->isLob())
			{
				$lobParameters[$propertyName] = true;
			}

			if ($properties[$propertyName] instanceof f_persistentdocument_PersistentDocumentArray)
			{
				$this->cascadeSaveDocumentArray($properties[$propertyName]);
			}
		}

		$sql = $this->get_insertDocumentQuery($table, $fieldsName, $parameters, $lobParameters);
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
	 * @example INSERT INTO '.$table.' (' . implode(', ', $fieldsName) .') VALUES (' . implode(', ', $parameters) .')
	 * @return String
	 */
	protected abstract function get_insertDocumentQuery($table, $fieldsName, $parameters, $lobParameters = array());

	/**
	 * @return string suffix localized table and field
	 */
	protected function getI18nSuffix()
	{
		return '_i18n';
	}

	/**
	 * @param f_persistentdocument_I18nPersistentDocument $i18nDocument
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 */
	protected function _insertI18nDocument($i18nDocument, $documentModel)
	{
		$table = $documentModel->getTableName() . $this->getI18nSuffix();

		$fieldsName = array($this->escapeFieldName('document_id'), $this->escapeFieldName('lang' . $this->getI18nSuffix()));
		$parameters = array(':id', ':lang');
		$lobParameters = array();

		$properties = $i18nDocument->getDocumentProperties();
		foreach ($properties as $propertyName => $propertyValue)
		{
			$property = $documentModel->getProperty($propertyName);
			$fieldsName[$propertyName] = $this->escapeFieldName($property->getDbMapping() . $this->getI18nSuffix());
			$parameters[$propertyName] = ':p'.$property->getDbMapping();
			if ($property->isLob())
			{
				$lobParameters[$propertyName] = true;
			}
		}
		$sql = $this->get_insertDocumentQuery($table, $fieldsName, $parameters, $lobParameters);
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':id', $i18nDocument->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $i18nDocument->getLang(), PersistentProviderConst::PARAM_STR);
		foreach ($properties as $propertyName => $propertyValue)
		{
			$this->bindValue($stmt, $propertyValue, $documentModel->getProperty($propertyName));
		}
		$this->executeStatement($stmt);

		$i18nDocument->setIsPersisted();
	}

	protected function bindValue($statement, $data, $propertyInfo)
	{
		$mapping = ':p' . $propertyInfo->getDbMapping();
		$this->bindStatmentValue($statement, $mapping, $data, $propertyInfo->getType());
	}

	protected abstract function bindStatmentValue($statement, $name, $value, $propertyType);

	/**
	 * @param f_persistentdocument_PersistentDocumentArray $documentArray
	 */
	public function loadRelations($documentArray)
	{
		$masterDocId = $documentArray->getParentDocument()->getId();
		$relType = $documentArray->getRelationType();
		$relName = $documentArray->getRelationName();
		$relId = $documentArray->getRelationId();
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::loadRelations : $masterDocId, $relType, $relName, $relId");
		}

		$stmt = $this->prepareStatement($this->getLoadRelationsQuery());
		$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);

		$this->executeStatement($stmt);

		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$documentArray->loadDocumentIds($results);
	}

	/**
	 * @return String
	 */
	protected abstract function getLoadRelationsQuery();


	/**
	 * @return String
	 */
	protected abstract function getSaveRelationsQuery();

	/**
	 * @return String
	 */
	protected abstract function getloadAllRelations();
	
	/**
	 * @param f_persistentdocument_PersistentDocumentArray $documentArray
	 */
	private function saveRelation($documentArray)
	{
		if (!$documentArray->isModified())
		{
			if (Framework::isDebugEnabled())
			{
				$masterDocId = $documentArray->getParentDocument()->getId();
				$relName = $documentArray->getRelationName();
				Framework::debug("PersistentProvider::saveRelation on document $masterDocId, $relName -> Ignored");
			}
			return;
		}

		$parentDocument = $documentArray->getParentDocument();
		$masterDocId = $parentDocument->getId();
		$masterDocType = $parentDocument->getDocumentModelName();
		$relName = $documentArray->getRelationName();
		$relId = $documentArray->getRelationId();
		
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::saveRelation on document $masterDocId, $masterDocType, $relName, $relId");
		}
		
		//Recuperation des nouvelles relations
		$docs = $documentArray->getInternalArray();
		if (count($docs) === 0)
		{
			if (!$documentArray->getParentDocument()->isNew())
			{
				$stmt = $this->prepareStatement($this->getSaveRelationDeleteAllQuery());
				$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);
				$this->executeStatement($stmt);
			}
			$documentArray->setIsPersisted();			
			return;
		}
		
		//Recuperations des anciens document_id / order
		$oldIds = array();	
		$stmt = $this->prepareStatement($this->getSaveRelationsPreviousQuery());
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
					$stmt = $this->prepareStatement($this->getSaveRelationUpdateQuery());
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
				$stmt = $this->prepareStatement($this->getSaveRelationInsertQuery());
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
				$stmt = $this->prepareStatement($this->getSaveRelationDeleteQuery());
				$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
				$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);	
				$stmt->bindValue(':relation_order', $order, PersistentProviderConst::PARAM_INT);
				$this->executeStatement($stmt);
			}
		}
		
		if ($updateOrder)
		{
			$stmt = $this->prepareStatement($this->getSaveRelationReorderQuery());
			$stmt->bindValue(':relation_id1', $masterDocId, PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':relation_id', $relId, PersistentProviderConst::PARAM_INT);	
			$this->executeStatement($stmt);	
		}
		
		$documentArray->setIsPersisted();
	}

	/**
	 * @return String
	 */
	protected abstract function getSaveRelationDeleteAllQuery();

	/**
	 * @return String
	 */
	protected abstract function getSaveRelationsPreviousQuery();
	
	
	/**
	 * @return String
	 */
	protected abstract function getSaveRelationInsertQuery();
	
	/**
	 * @return String
	 */	
	protected abstract function getSaveRelationUpdateQuery();
	
	/**
	 * @return String
	 */
	protected abstract function getSaveRelationDeleteQuery();

	/**
	 * @return String
	 */	
	protected abstract function getSaveRelationReorderQuery();
	
	/**
	 * @return String
	 */
	public function getSQLScriptSufixName()
	{
		return '.'.$this->getType().'.sql';
	}

	/**
	 * @return String
	 */
	public abstract function getType();

	//
	// Tree Methods à usage du treeService
	//

	public function createTreeTable($treeId)
	{
		$stmt = $this->prepareStatement($this->dropTreeTableQuery($treeId));
		$this->executeStatement($stmt);

		$stmt = $this->prepareStatement($this->createTreeTableQuery($treeId));
		$this->executeStatement($stmt);
	}

	protected abstract function dropTreeTableQuery($treeId);

	protected abstract function createTreeTableQuery($treeId);

	/**
	 * @param integer $documentId
	 * @param integer $treeId
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public function getNodeInfo($documentId, $treeId)
	{
		$stmt = $this->prepareStatement($this->getNodeInfoQuery($treeId));
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		if (!$result) {return null;}
		$result['tree_id'] = $treeId;
		return $result;
	}

	/**
	 * @example 'SELECT parent_id, node_order, node_level, node_path, children_count FROM f_tree_'.$treeId.' WHERE document_id = :document_id';
	 */
	protected abstract function getNodeInfoQuery($treeId);

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
			
		$stmt = $this->prepareStatement($this->getNodesInfoQuery($treeId, $documentCount));
		for($i = 0; $i < $documentCount; $i++)
		{
			$stmt->bindValue(':p' . $i, $documentsId[$i], PersistentProviderConst::PARAM_INT);
		}
		$this->executeStatement($stmt);
		while ($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC))
		{
			$row['tree_id'] = $treeId;
			$result[$row['document_id']] = $row;
		}
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @example 'SELECT document_id, parent_id, node_order, node_level, node_path, children_count FROM f_tree_'.$treeId.' WHERE document_id in (:p0, ...)';
	 */
	protected abstract function getNodesInfoQuery($treeId, $documentCount);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public function getChildrenNodesInfo($node)
	{
		$result = array();
		$treeId = $node->getTreeId();
		$stmt = $this->prepareStatement($this->getChildrenNodesInfoQuery($treeId));
		$stmt->bindValue(':parent_id', $node->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		while ($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC))
		{
			$row['tree_id'] = $treeId;
			$result[] = $row;
		}
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @example 'SELECT document_id, parent_id, node_order, node_level, node_path, children_count FROM f_tree_'.$treeId.'
	 * 	WHERE parent_id = :parent_id ORDER BY node_order'
	 */
	protected abstract function getChildrenNodesInfoQuery($treeId);


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
		$stmt = $this->prepareStatement($this->getDescendantsNodesInfoQuery($treeId));
		$stmt->bindValue(':min_level', $node->getLevel(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':max_level', $maxlvl, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_path', $node->getPath() . $node->getId() . '/%', PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);

		while ($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC))
		{
			$row['tree_id'] = $treeId;
			$result[] = $row;
		}
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @example 'SELECT document_id, parent_id, node_order, node_level, node_path, children_count FROM f_tree_'.$treeId
	 * 	WHERE node_level > :min_level AND node_level <= :max_level AND node_path like :node_path ORDER BY node_level, node_order'
	 */
	protected abstract function getDescendantsNodesInfoQuery($treeId);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return integer[]
	 */
	public function getChildrenId($node)
	{
		$result = array();
		if (!$node->hasChildren()) {return $result;}
			
		$stmt = $this->prepareStatement($this->getChildrenIdQuery($node->getTreeId()));
		$stmt->bindValue(':parent_id', $node->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		while ($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC))
		{
			$result[] = $row['document_id'];
		}
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @example 'SELECT document_id FROM f_tree_'.$treeId.' WHERE parent_id = :parent_id ORDER BY node_order'
	 */
	protected abstract function getChildrenIdQuery($treeId);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return integer[]
	 */
	public function getDescendantsId($node)
	{
		$result = array();
		if (!$node->hasChildren()) {return $result;}
			
		$stmt = $this->prepareStatement($this->getDescendantsIdQuery($node->getTreeId()));
		$stmt->bindValue(':node_level', $node->getLevel(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_path', $node->getPath() . $node->getId() . '/%', PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		while ($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC))
		{
			$result[] = $row['document_id'];
		}
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @example 'SELECT document_id FROM f_tree_'.$treeId.' WHERE node_level > :node_level and node_path like :node_path';
	 */
	protected abstract function getDescendantsIdQuery($treeId);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $rootNode
	 */
	public function createTree($rootNode)
	{
		$this->createTreeTable($rootNode->getId());
		$this->insertNode($rootNode);
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

		$sql = $this->getDeleteTreeDocumentQuery();
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':treeid', $treeId , PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id', $treeId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		$sql = $this->getDeleteTreeQuery($treeId);
		$stmt = $this->prepareStatement($sql);
		$this->executeStatement($stmt);

		//Update node information
		$rootNode->setEmpty();
		$this->insertNode($rootNode);
		return $ids;
	}

	/**
	 * @example 'UPDATE `f_document` SET `treeid` = NULL WHERE `treeid` = :treeid AND document_id <> :document_id'
	 */
	protected abstract function getDeleteTreeDocumentQuery();

	/**
	 * @example 'DELETE FROM f_tree_'.$treeId
	 */
	protected abstract function getDeleteTreeQuery($treeId);


	/**
	 * Ajoute un nouveau noeud
	 * @param f_persistentdocument_PersistentTreeNode $node
	 */
	protected function insertNode($node)
	{
		$stmt = $this->prepareStatement($this->getInsertNodeQuery($node->getTreeId()));
		$stmt->bindValue(':document_id', $node->getId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':parent_id', $node->getParentId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_order', $node->getIndex(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_level', $node->getLevel(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':node_path', $node->getPath(), PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':children_count', $node->getChildCount(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		$stmt = $this->prepareStatement($this->getUpdateDocumentTreeQuery());
		$stmt->bindValue(':treeid', $node->getTreeId(), PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id', $node->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		if ($this->useDocumentCache)
		{
			if ($this->isInCache($node->getId()))
			{
				$document = $this->getFromCache($node->getId());
				$document->setProviderTreeId($node->getTreeId());
			}

			$this->getCacheService()->set($node->getId(), null);
		}
	}

	/**
	 * @example INSERT INTO `f_tree_'.$treeId.'` (`document_id`, `parent_id`, `node_order`, `node_level`, `node_path`, `children_count`)
	 * 	VALUES (:document_id, :parent_id, :node_order, :node_level, :node_path, :children_count)'
	 */
	protected abstract function getInsertNodeQuery($treeId);

	/**
	 * @example 'UPDATE `f_document` SET `treeid` = :treeid WHERE `document_id` = :document_id';
	 */
	protected abstract function getUpdateDocumentTreeQuery();

	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode[] $nodes
	 */
	public function orderNodes($parentNode, $nodes)
	{
		$countIds = count($nodes);
		$treeId = $parentNode->getTreeId();
		$stmt = $this->prepareStatement($this->orderNodesPrepareQuery($treeId, $countIds));
		foreach ($nodes as $i => $node)
		{
			$stmt->bindValue(':p' . $i, $node->getId() , PersistentProviderConst::PARAM_INT);
		}
		$this->executeStatement($stmt);

		foreach ($nodes as $node)
		{
			$stmt = $this->prepareStatement($this->orderNodesQuery($treeId));
			$stmt->bindValue(':node_order', $node->getIndex() , PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':document_id', $node->getId() , PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);
		}
	}

	/**
	 * UPDATE f_tree_'.$treeId . ' SET node_order = -node_order WHERE document_id in (:p0, ...)
	 */
	protected abstract function orderNodesPrepareQuery($treeId, $countIds);

	/**
	 * 'UPDATE f_tree_'.$treeId. ' SET node_order = :node_order WHERE document_id = :document_id'
	 */
	protected abstract function orderNodesQuery($treeId);

	/**
	 * Supression d'un noeud
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 */
	public function deleteEmptyNode($treeNode)
	{
		$sql = $this->getUpdateDocumentTreeQuery();
		$stmt = $this->prepareStatement($sql);
		$stmt->bindValue(':treeid', null, PersistentProviderConst::PARAM_NULL);
		$stmt->bindValue(':document_id', $treeNode->getId(), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
			
		$sql = $this->deleteEmptyNodeDocumentQuery($treeNode->getTreeId());
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
	 * @example 'DELETE FROM `f_tree_'.$treeId.'` WHERE `document_id` = :document_id'
	 */
	protected abstract function deleteEmptyNodeDocumentQuery($treeId);

	/**
	 * @example 'UPDATE f_tree_'.$treeId.' SET children_count = children_count + :offest WHERE document_id = :document_id'
	 */
	protected abstract function updateChildenCountQuery($treeId);

	/**
	 * @example 'UPDATE f_tree_'.$treeId.' SET node_order = node_order + :offest WHERE parent_id = :parent_id AND node_order >= :node_order'
	 */
	protected abstract function updateChildrenOrderQuery($treeId, $offset);

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
			$sql = $this->getUpdateDocumentsTreeQuery($treeNode->getTreeId());
			$stmt = $this->prepareStatement($sql);
			$stmt->bindValue(':node_level', $treeNode->getLevel(), PersistentProviderConst::PARAM_INT);
			$stmt->bindValue(':node_path', $path, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);

			$sql = $this->deleteNodeRecursivelyQuery($treeNode->getTreeId());
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
	 * @example 'UPDATE `f_document` SET `treeid` = NULL WHERE
	 * 	`document_id` IN (SELECT document_id FROM f_tree_'.$treeId.' WHERE node_level > :node_level and node_path like :node_path)'
	 */
	protected abstract function getUpdateDocumentsTreeQuery($treeId);

	/**
	 * @example 'DELETE FROM f_tree_'.$treeId.' WHERE node_level > :node_level and node_path like :node_path)'
	 */
	protected abstract function deleteNodeRecursivelyQuery($treeId);

	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param f_persistentdocument_PersistentTreeNode $childNode
	 */
	public function appendChildNode($parentNode, $childNode)
	{
		//Mise à jour du nombre de fils
		$stmt = $this->prepareStatement($this->updateChildenCountQuery($childNode->getTreeId()));
		$stmt->bindValue(':offest', 1, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':document_id',$parentNode->getId(), PersistentProviderConst::PARAM_INT);
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

		$stmt = $this->prepareStatement($this->moveNodeBaseQuery($movedNode->getTreeId()));
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
			$stmt = $this->prepareStatement($this->moveNodeRecurseQuery($movedNode->getTreeId()));
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
	 *'UPDATE f_tree_'.$treeId
	 * SET parent_id = :parent_id, node_order = :node_order, node_level = node_level + :offestlvl, node_path = :node_path
	 * WHERE document_id = :document_id
	 */
	protected abstract function moveNodeBaseQuery($treeId);

	/**
	 * 'UPDATE f_tree_'.$treeId
	 * SET node_level = node_level + :offestlvl, node_path = REPLACE(node_path, :from_path, :to_path)'
	 * WHERE node_level > :node_level AND node_path like :node_path'
	 */
	protected abstract function moveNodeRecurseQuery($treeId);

	/**
	 * @param string $type
	 * @param integer $documentId1
	 * @param integer $documentId2
	 * @param string $documentModel1
	 * @param string $documentModel2
	 * @param string $name
	 * @return array of f_persistentdocument_PersistentRelation
	 */
	protected function getRelations($type = null, $documentId1 = null, $documentId2 = null, $name = null, $documentModel1 = null, $documentModel2 = null)
	{
		$relationId = ($name === null) ? NULL : RelationService::getInstance()->getRelationId($name);
		$sql = $this->getRelationsQuery($documentId1, $documentId2, $relationId, $documentModel1, $documentModel2);
		$stmt = $this->prepareStatement($sql);

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
	 * @return String
	 */
	protected abstract function getRelationsQuery($documentId1, $documentId2, $relationId, $documentModel1, $documentModel2);

	/**
	 * @param integer $masterDocumentId
	 * @param string $relationName
	 * @param string $slaveDocumentModel
	 * @return array of f_persistentdocument_PersistentRelation
	 */
	public function getChildRelationByMasterDocumentId($masterDocumentId, $relationName = null, $slaveDocumentModel = null)
	{
		return $this->getRelations(f_persistentdocument_PersistentDocumentArray::RELATION_CHILD,
		$masterDocumentId, null, $relationName, null, $slaveDocumentModel);
	}

	/**
	 * @param integer $slaveDocumentId
	 * @param string $relationName
	 * @param string $masterDocumentModel
	 * @return array of f_persistentdocument_PersistentRelation
	 */
	public function getChildRelationBySlaveDocumentId($slaveDocumentId, $relationName = null, $masterDocumentModel = null)
	{
		return $this->getRelations(f_persistentdocument_PersistentDocumentArray::RELATION_CHILD,
		null, $slaveDocumentId, $relationName, $masterDocumentModel, null);
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
	 * Return the tags affected to the document with ID $documentId.
	 * @internal use by TagService
	 * @param integer $documentId Id of the document the get the list of tags of.
	 * @return array<string>
	 */
	public function getTags($documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::getTags($documentId)");
		}

		$stmt = $this->prepareStatement($this->getTagsQuery());
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
	 * @return String
	 */
	protected abstract function getTagsQuery();

	/**
	 * @return String 'SELECT tags.tag, tags.id FROM f_tags tags'
	 */
	protected abstract function getAllTagsTagsQuery();

	/**
	 * @return array<tag => array<id>>
	 */
	public function getAllTags()
	{
		$query = $this->getAllTagsTagsQuery();
		$stmt = $this->prepareStatement($query);
		$this->executeStatement($stmt);
		$allTags = array();
		foreach ($stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC) as $row) 
		{
			$allTags[$row['tag']][] = $row['id'];
		}
		return $allTags;
	}

	protected abstract function getSettingQuery();

	protected abstract function getSettingPackageQuery();

	protected abstract function getUserSettingQuery();

	protected abstract function getTruncateTableQuery($tableName);

	public function getSettingValue($packageName, $settingName)
	{
		$query = $this->getSettingQuery();
		$stmt = $this->prepareStatement($query);
		$stmt->bindValue(':package', $packageName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		return count($results) === 1 ? $results[0]['value'] : null;
	}

	public function getSettingPackage($value, $settingName)
	{
		$query = $this->getSettingPackageQuery();
		$stmt = $this->prepareStatement($query);
		$stmt->bindValue(':value', $value, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		return count($results) === 1 ? $results[0]['package'] : null;
	}

	public function getUserSettingValue($packageName, $settingName, $userId)
	{
		$query = $this->getUserSettingQuery();
		$stmt = $this->prepareStatement($query);
		$stmt->bindValue(':package', $packageName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':userid', $userId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		return count($results) === 1 ? $results[0]['value'] : null;
	}

	public function setUserSettingValue($packageName, $settingName, $userId, $value)
	{
		$stmt = $this->prepareStatement($this->getSaveSettingDeleteQuery());
		$stmt->bindValue(':package', $packageName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':userid', $userId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		$stmt = $this->prepareStatement($this->getSaveUserSettingQuery());
		$stmt->bindValue(':package', $packageName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':name', $settingName, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':userid', $userId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':value', $value, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
	}


	public function setSettingValue($packageName, $settingName, $value)
	{
		$this->setUserSettingValue($packageName, $settingName, 0, $value);
	}

	/**
	 * @return String
	 */
	protected abstract function getSaveSettingDeleteQuery();

	/**
	 * @return String
	 */
	protected abstract function getSaveUserSettingQuery();


	// -------------------------------------------------------------------------
	// TAGS STUFF
	// -------------------------------------------------------------------------

	/**
	 * @internal use by TagService
	 * @param string $tag
	 * @return array of documentid
	 */
	public function getDocumentIdsByTag($tag)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::getDocumentIdsByTag($tag)");
		}

		$stmt = $this->prepareStatement($this->getDocumentIdsByTagQuery());
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
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::hasTags($documentId, " . count($tags) . ", $allTagsRequired)");
		}

		$sql = $this->getHasTagsQuery($tags);

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
	 *
	 * @throws InvalidTagException
	 */
	public function hasTag($documentId, $tag)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::hasTag($documentId, $tag)");
		}

		$stmt = $this->prepareStatement($this->getHasTagQuery());
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
	 *
	 */
	public function removeTag($documentId, $tag)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::removeTag($documentId, $tag)");
		}

		$stmt = $this->prepareStatement($this->getRemoveTagQuery());
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':tag', $tag, PersistentProviderConst::PARAM_STR);

		$this->executeStatement($stmt);
		return true;
	}


	/**
	 * Adds the tag $tag tag to the document with ID $documentId.
	 * @internal use by TagService
	 *
	 * @param integer $documentId
	 * @param string $tag
	 *
	 * @throws Exception on database error.
	 */
	public function addTag($documentId, $tag)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("PersistentProvider::addTag($documentId, $tag)");
		}

		$stmt = $this->prepareStatement($this->getAddTagQuery());
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':tag', $tag, PersistentProviderConst::PARAM_STR);

		$this->executeStatement($stmt);
	}

	/**
	 * @param array<String> $tags
	 * @return String
	 */
	protected abstract function getHasTagsQuery($tags);

	/**
	 * @return String
	 */
	protected abstract function getHasTagQuery();

	/**
	 * @return String
	 */
	protected abstract function getAddTagQuery();

	/**
	 * @return String
	 */
	protected abstract function getRemoveTagQuery();

	/**
	 * @return String
	 */
	protected abstract function getRemoveExclusiveTagQuery();

	/**
	 * @return String
	 */
	protected abstract function getDocumentIdsByTagQuery();


	// -------------------------------------------------------------------------
	// END OF TAGS STUFF
	// -------------------------------------------------------------------------

	/**
	 * @param integer $key
	 * @return f_persistentdocument_PersistentDocument | NULL
	 */
	public function getFromFrameworkCache($key)
	{
		$stmt = $this->prepareStatement($this->getFromFrameworkCacheQuery());
		$stmt->bindValue(':key', intval($key), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);

		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results) != 1)
		{
			return null;
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . "($key) -> Loaded from db cache");
		}
		return unserialize($results[0]['text_value']);
	}

	/**
	 * @return String
	 */
	protected abstract function getFromFrameworkCacheQuery();

	/**
	 * @param integer[] $keys
	 * @return array<integer, f_persistentdocument_PersistentDocument>
	 */
	public function getMultipleFromFrameworkCache($keys)
	{
		$keysCount = count($keys);
		if ($keysCount == 0) return array();

		$in = array();
		for ($i = 0; $i < $keysCount; $i++)
		{
			$in[] = ':k'.$i;
		}

		$stmt = $this->prepareStatement($this->getMultipleFromFrameworkCacheQuery($in));
		for ($i = 0; $i < $keysCount; $i++)
		{
			$stmt->bindValue(':k'.$i, intval($keys[$i]), PersistentProviderConst::PARAM_INT);
		}

		$this->executeStatement($stmt);

		$objs = array();
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		foreach ($results as $result)
		{
			$objs[$result['cache_key']] = unserialize($result['text_value']);
		}
		return $objs;
	}

	/**
	 * @return String
	 */
	protected abstract function getMultipleFromFrameworkCacheQuery($keys);

	/**
	 * @param integer[] $keys
	 */
	public function deleteFrameworkCacheKeys($keys)
	{
		$keysCount = count($keys);

		$in = array();
		for ($i = 0; $i < $keysCount; $i++)
		{
			$in[] = ':k'.$i;
		}

		$stmt = $this->prepareStatement($this->deleteFrameworkCacheKeysQuery($in));
		for ($i = 0; $i < $keysCount; $i++)
		{
			$stmt->bindValue(':k'.$i, intval($keys[$i]), PersistentProviderConst::PARAM_INT);
		}
		$this->executeStatement($stmt);
	}

	/**
	 * @return String
	 */
	protected abstract function deleteFrameworkCacheKeysQuery($keys);

	/**
	 * @param integer $key
	 * @param f_persistentdocument_PersistentDocument $object can be NULL
	 * @return boolean
	 */
	public function setInFrameworkCache($key, $object)
	{
		if (is_null($object))
		{
			// delete from frameworkcache
			$stmt = $this->prepareStatement($this->setInFrameworkCacheDeleteQuery());
			$stmt->bindValue(':key', intval($key), PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);

			return $stmt->rowCount() == 1;
		}
		else
		{
			$stmt = $this->prepareStatement($this->setInFrameworkCacheQuery());
			$stmt->bindValue(':key', intval($key), PersistentProviderConst::PARAM_INT);
			$this->executeStatement($stmt);

			if (count($stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC)) == 0)
			{
				// if do not exists, insert
				try
				{
					$stmt = $this->prepareStatement($this->setInFrameworkCacheInsertQuery());
					$stmt->bindValue(':key', intval($key), PersistentProviderConst::PARAM_INT);
					$stmt->bindValue(':text_value', serialize($object), PersistentProviderConst::PARAM_LOB);
					$this->executeStatement($stmt);

					if (Framework::isDebugEnabled())
					{
						Framework::debug(__METHOD__ . "($key) -> Added in cache");
					}
				}
				catch (f_DatabaseException $e)
				{
					if (23000 == $e->getErrorCode())
					{
						if (Framework::isDebugEnabled())
						{
							Framework::debug(__METHOD__ ." duplicate $key");
						}
					}
					else
					{
						throw $e;
					}
				}
			}
			else
			{
				// update
				$stmt = $this->prepareStatement($this->setInFrameworkCacheUpdateQuery());
				$stmt->bindValue(':text_value', serialize($object), PersistentProviderConst::PARAM_LOB);
				$stmt->bindValue(':key', intval($key), PersistentProviderConst::PARAM_INT);
				$this->executeStatement($stmt);
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . "($key) -> Updated in cache");
				}
			}
		}
		return true;
	}

	/**
	 * @return String
	 */
	protected abstract function setInFrameworkCacheDeleteQuery();

	/**
	 * @return String
	 */
	protected abstract function setInFrameworkCacheQuery();

	/**
	 * @return String
	 */
	protected abstract function setInFrameworkCacheInsertQuery();
	/**
	 * @return String
	 */
	protected abstract function setInFrameworkCacheUpdateQuery();

	/**
	 * @param string $pattern Not implemented
	 */
	public function clearFrameworkCache($pattern = null)
	{
		// Truncate can not be done in a transaction !
		if (f_persistentdocument_TransactionManager::getInstance()->hasTransaction())
		{
			$stmt = $this->prepareStatement($this->clearFrameworkCacheDeleteQuery());
		}
		else
		{
			$stmt = $this->prepareStatement($this->clearFrameworkCacheTruncateQuery());
		}
		$this->executeStatement($stmt);
	}

	/**
	 * @return String
	 */
	protected abstract function clearFrameworkCacheDeleteQuery();
	
	/**
	 * @return String
	 */
	protected abstract function clearFrameworkCacheTruncateQuery();
	
	/**
	 * @param int $ttl
	 */
	public function clearFrameworkCacheByTTL($ttl)
	{
		$stmt = $this->prepareStatement($this->clearFrameworkCacheByTTLQuery());
		$stmt->bindValue(':ttl', intval($ttl), PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
	}
	
	/**
	 * DELETE FROM f_cache WHERE (insert_time + :ttl) < NOW()
	 * @return string
	 */
	protected abstract function clearFrameworkCacheByTTLQuery();

	/**
	 * @return void
	 */
	public function reset()
	{
		$this->clearCaches();
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
		$stmt = $this->prepareStatement($this->getTranslateQuery());
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
	 * @return 'SELECT `content`, `format` FROM `f_locale` WHERE `lang` = :lang AND `id` = :id AND `key_path` = :key_path'
	 */
	protected abstract function getTranslateQuery();

	/**
	 * Clear the translation table or a part of that
	 *
	 * @param string $package Example: m.users
	 */
	public function clearTranslationCache($package = null)
	{
		$stmt = $this->prepareStatement($this->clearTranslationCacheQuery($package));
		$this->executeStatement($stmt);
	}

	/**
	 * @return DELETE FROM `f_locale` WHERE `useredited` != 1 [ AND `key_path` LIKE '$package.%'"]
	 */
	protected abstract function clearTranslationCacheQuery($package = null);

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
		$stmt = $this->prepareStatement($this->addTranslateSelectQuery());
		$stmt->bindValue(':lang', $lcid, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':id', $id, PersistentProviderConst::PARAM_STR);
		$stmt->bindValue(':key_path', $keyPath, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		if (count($results))
		{
			if ($forceUpdate || $useredited == 1 || $results[0]['useredited'] != 1)
			{
				$stmt = $this->prepareStatement($this->updateTranslateQuery());
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
			$stmt = $this->prepareStatement($this->addTranslateQuery());
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
	 * @return SELECT `useredited` FROM `f_locale` WHERE `lang` = :lang AND `id` = :id  AND `key_path` = :key_path
	 */
	protected abstract function addTranslateSelectQuery();
	
	/**
	 * @return INSERT INTO `f_locale` (`lang`, `id`, `key_path`, `content`, `useredited`, `format`) VALUES (:lang, :id, :key_path, :content, :useredited, :format)
	 */
	protected abstract  function addTranslateQuery();

	/**
	 * @return UPDATE `f_locale` SET `content` = :content, `useredited` = :useredited, `format` = :format WHERE `lang` = :lang AND `id` = :id  AND `key_path` = :key_path
	 */
	protected abstract function updateTranslateQuery();
	
	/**
	 * @return array
	 */
	public function getPackageNames()
	{
		$stmt = $this->prepareStatement($this->getPackageNamesQuery());
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
	 * @return SELECT COUNT(*) AS `nbkeys`, `key_path` FROM `f_locale` GROUP BY `key_path` ORDER BY `key_path`
	 */
	protected abstract function getPackageNamesQuery();
		
	/**
	 * @return array
	 */
	public function getUserEditedPackageNames()
	{
		$stmt = $this->prepareStatement($this->getUserEditedPackageNamesQuery());
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
	 * @return SELECT COUNT(*) AS `nbkeys`, `key_path` FROM `f_locale` WHERE `useredited` = 1 GROUP BY `key_path` ORDER BY `key_path`
	 */
	protected abstract function getUserEditedPackageNamesQuery();
	
	/**
	 * @param string $keyPath
	 * @return array['id' => string, 'lang' => string, 'content' => string, 'useredited' => integer, 'format' => string]
	 */
	public function getPackageData($keyPath)
	{
		$stmt = $this->prepareStatement($this->getPackageDataQuery());
		$stmt->bindValue(':key_path', $keyPath, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		$results = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		return $results;
	}
		
	/**
	 * @return SELECT `id`,`lang`,`content`,`useredited`,`format` FROM `f_locale` WHERE `key_path` = :key_path
	 */
	protected abstract function getPackageDataQuery();
	
	/**
	 * @param string $keyPath
	 * @param string $id
	 * @param string $lcid
	 */
	public function deleteI18nKey($keyPath, $id = null, $lcid = null)
	{
		$stmt = $this->prepareStatement($this->deleteI18nKeyQuery($id, $lcid));
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
	 * @return DELETE FROM `f_locale` WHERE `key_path` = :key_path AND `id` = :id AND `lang` = :lang
	 */
	protected abstract function deleteI18nKeyQuery($id, $lcid);	

	

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
	 * @return string
	 */
	protected abstract function getUrlRewritingQuery();
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @return string from_url
	 */
	public function getUrlRewriting($documentId, $lang, $websiteId = 0, $actionName = 'ViewDetail')
	{		
		$stmt = $this->prepareStatement($this->getUrlRewritingQuery());
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
	 * @return String
	 */
	protected abstract function getUrlRewritingInfoQuery();
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @return array<array<rule_id, origine, document_id, website_lang, website_id, from_url, to_url, redirect_type, modulename, actionname>>
	 */
	public function getUrlRewritingInfo($documentId, $lang)
	{
		$stmt = $this->prepareStatement($this->getUrlRewritingInfoQuery());
		$stmt->bindValue(':id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':lang', $lang, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($stmt);
		return $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
	}
	
	/**
	 * @return String
	 */
	protected abstract function setUrlRewritingQuery();
	
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
		$stmt = $this->prepareStatement($this->setUrlRewritingQuery());
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
	 * @return String
	 */
	protected abstract function clearUrlRewritingQuery();
	
	/**
	 * @param integer $documentId
	 * @return integer count deleted rules
	 */
	public function clearUrlRewriting($documentId)
	{
		$stmt = $this->prepareStatement($this->clearUrlRewritingQuery());
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->rowCount();
	}
	
	/**
	 * @return string
	 */
	protected abstract function getUrlRewritingInfoByUrlQuery();
	
	/**
	 * @param string $url
	 * @param integer $websiteId
	 * @param string $lang
	 * @return array<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, to_url, redirect_type>
	 */
	public function getUrlRewritingInfoByUrl($url, $websiteId, $lang)
	{
		$stmt = $this->prepareStatement($this->getUrlRewritingInfoByUrlQuery());
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
	 * @return String
	 */
	protected abstract function getPageForUrlQuery();

	/**
	 * @param string $url
	 * @param integer $websiteId
	 * @return array<rule_id, document_id, website_lang, website_id, to_url, redirect_type>
	 */
	public function getPageForUrl($url, $websiteId = 0)
	{
		$stmt = $this->prepareStatement($this->getPageForUrlQuery());
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

	//
	// Permission Section
	//

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
					$stmt = $this->prepareStatement($this->getCompileACLQuery());
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
	 * @param Integer $nodeId
	 * @param String $packageName (ex: modules_website)
	 */
	public function removeACLForNode($nodeId, $packageName = null)
	{
		if (is_null($packageName))
		{
			$stmt = $this->prepareStatement($this->getRemoveACLForNodeQuery());
			$stmt->bindValue(':nodeId', $nodeId);
		}
		else
		{
			$stmt = $this->prepareStatement($this->getRemovePackageACLForNodeQuery());
			$stmt->bindValue(':nodeId', $nodeId);
			$stmt->bindValue(':permission', $packageName . '%');
		}
		$this->executeStatement($stmt);
	}

	/**
	 * Permissions defined on $nodeId predicate
	 *
	 * @param Integer $nodeId
	 * @return boolean
	 */
	public function hasCompiledPermissions($nodeId)
	{
		$stmt = $this->prepareStatement($this->getHasPermissionACLQuery());
		$stmt->bindValue(':nodeId', $nodeId);
		$this->executeStatement($stmt);
		return $stmt->fetchColumn()>0;
	}


	/**
	 * Permissions defined on $nodeId for $package predicate
	 *
	 * @param Integer $nodeId
	 * @param String $packageName
	 * @return boolean
	 */
	public function hasCompiledPermissionsForPackage($nodeId, $packageName)
	{
		$stmt = $this->prepareStatement($this->getHasPermissionForPackageQuery());
		$stmt->bindValue(':nodeId', $nodeId);
		$stmt->bindValue(':permission', $packageName .'%');
		$this->executeStatement($stmt);
		return $stmt->fetchColumn()>0;
	}

	/**
	 * Checks the existence of a permission on a node for an array of accessors.
	 *
	 * @param array<Integer> $accessors
	 * @param String $fullPermName
	 * @param Integer $nodeId
	 * @return boolean
	 */
	public function checkCompiledPermission($accessors, $perm, $node)
	{
		$stmt = $this->prepareStatement($this->getCheckACLQuery($accessors));
		$stmt->bindValue(':permission', $perm);
		$stmt->bindValue(':nodeId', $node);
		$this->executeStatement($stmt);
		return $stmt->fetchColumn()>0;
	}

	/**
	 * @param String $permission
	 * @param integer $nodeId
	 * @return array<Integer>
	 */
	public function getAccessorsByPermissionForNode($permission, $nodeId)
	{
		$stmt = $this->prepareStatement($this->getAccessorsByPermissionForNodeQuery());
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
	 * @param Integer $nodeId
	 * @return array<String>
	 */
	public function getPermissionsForUserByNode($accessorIds, $nodeId)
	{
		$stmt = $this->prepareStatement($this->getPermissionsForUserByNodeQuery($accessorIds));
		$stmt->bindValue(':nodeId', $nodeId);
		$this->executeStatement($stmt);
		$result = array();
		while( $row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC) )
		{
			$result[] = $row['permission'];
		}
		return $result;
	}

	public function clearAllPermissions()
	{
		$stmt = $this->prepareStatement($this->getTruncateTableQuery('f_permission_compiled'));
		$this->executeStatement($stmt);
	}
	/**
	 * @param array<Integer> $accessors
	 * @return String
	 */
	protected abstract function getPermissionsForUserByNodeQuery($accessors);
	/**
	 * Get the permission "Definition" points for tree $packageName (ex: modules_website).
	 *
	 * @param String $packageName
	 * @return Array<Integer>
	 */
	public function getPermissionDefinitionPoints($packageName)
	{
		$stmt = $this->prepareStatement($this->getACLDefinitionPointQuery());
		$stmt->bindValue(':permission', $packageName . '%');
		$this->executeStatement($stmt);
		$result = array();
		while($row = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC) )
		{
			$result[] = intval($row['node_id']);
		}
		return $result;
	}

	/**
	 * @return String
	 */
	protected abstract function getCompileACLQuery();

	/**
	 * @return String
	 */
	protected abstract function getCheckACLQuery($accessors);

	/**
	 * @return String
	 */
	protected abstract function getAccessorsByPermissionForNodeQuery();

	/**
	 * @return String
	 */
	protected abstract function getHasPermissionACLQuery();

	/**
	 * @return String
	 */
	protected abstract function getHasPermissionForPackageQuery();

	/**
	 * @return String
	 */
	protected abstract function getRemoveACLForNodeQuery();

	/**
	 * @return String
	 */
	protected abstract function getRemovePackageACLForNodeQuery();


	/**
	 * @return String
	 */
	protected abstract function getACLDefinitionPointQuery();

	//
	// End of Permission Section
	//


	public function getI18nWebsitesFromUrl($url)
	{
		$stmt = $this->prepareStatement($this->getWebsiteIdAndLangFromUrlQuery());
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

	protected abstract function getWebsiteIdAndLangFromUrlQuery();

	/**
	 * @param String $blockName
	 * @param array<String> $specs
	 * @param website_persistentdocument_page $page
	 */
	public function registerSimpleCache($cacheId, $specs)
	{
		$deleteStmt = $this->prepareStatement($this->getUnregisterSimpleCacheQuery());
		$deleteStmt->bindValue(':cacheId', $cacheId, PersistentProviderConst::PARAM_STR);
		$this->executeStatement($deleteStmt);

		if (f_util_ArrayUtils::isNotEmpty($specs))
		{
			$registerQuery = $this->getRegisterSimpleCacheQuery();
			foreach (array_unique($specs) as $spec)
			{
				$stmt = $this->prepareStatement($registerQuery);
				$stmt->bindValue(':pattern', $spec, PersistentProviderConst::PARAM_STR);
				$stmt->bindValue(':cacheId', $cacheId, PersistentProviderConst::PARAM_STR);
				$this->executeStatement($stmt);
			}
		}
	}

	public function getCacheIdsByPattern($pattern)
	{
		$stmt = $this->prepareStatement($this->getCacheIdsByPatternQuery());
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
	 * @return String
	 */
	protected abstract function getCacheIdsByPatternQuery();

	/**
	 * @return String
	 */
	protected abstract function getRegisterSimpleCacheQuery();

	/**
	 * @return String
	 */
	protected abstract function getUnregisterSimpleCacheQuery();

	// private methods

	/**
	 * @param Integer $documentId
	 * @return boolean
	 */
	private function isInCache($documentId)
	{
		return $this->useDocumentCache && isset($this->m_documentInstances[$documentId]);
	}

	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentDocument
	 */
	private function getFromCache($documentId)
	{
		return $this->m_documentInstances[$documentId];
	}

	/**
	 * @param Integer $documentId
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return void
	 */
	protected function putInCache($documentId, $document)
	{
		if ($this->useDocumentCache)
		{
			$this->m_documentInstances[$documentId] = $document;
			if ($document->getPersistentModel()->isLocalized() && $document->getRawI18nVoObject() !== null)
			{
				$this->m_i18nDocumentInstances[$documentId][$document->getLang()] = $document->getRawI18nVoObject();
			}
		}
	}

	/**
	 * @param Integer $documentId
	 * @return void
	 */
	private function deleteFromCache($documentId)
	{
		if ($this->useDocumentCache)
		{
			unset($this->m_documentInstances[$documentId]);
			$this->getCacheService()->set($documentId, null);
		}
	}

	/**
	 * @return void
	 */
	private function clearCaches()
	{
		$this->clearDocumentCache();
		$this->getCacheService()->clear();
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $persistentDocument
	 * @param array<f_persistentdocument_PersistentDocumentArray> $dataRelations
	 */
	private function saveRelations($persistentDocument, $dataRelations)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug('PersistentProvider::saveRelations ' . count($dataRelations) . ' relations on document ' . $persistentDocument->getId());
		}
		foreach ($dataRelations as $relation)
		{
			if (!is_null($relation))
			{
				$this->saveRelation($relation);
			}
		}
	}

	/**
	 * @param array $dataRelations
	 * @param array $propertiesInfo
	 * @param array $properties
	 * @param Statement $stmt
	 * @param array mapping
	 */
	private function buildRelationDataAndBindValues(&$dataRelations, $propertiesInfo, $properties, $stmt, $mapping = null)
	{
		foreach ($properties as $propertyName => $propertyValue)
		{
			$propertyInfo = $propertiesInfo[$propertyName];
			if ('id' == $propertyName || 'model' == $propertyName)
			{
				continue;
			}
			if (!$propertyInfo->isDocument())
			{
				if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
				{
					$this->bindValue($stmt, $propertyValue, $propertyInfo);
				}
			}
			else
			{
				$dataRelations[] = $propertyValue;
				if ($propertyInfo->isArray())
				{
					if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
					{
						$this->bindValue($stmt, $propertyValue->count(), $propertyInfo);
					}
				}
				elseif ($propertyValue->count() != 0)
				{
					if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
					{
						$this->bindValue($stmt, $propertyValue[0]->getId(), $propertyInfo);
					}
				}
				else
				{
					if (!is_array($mapping) || array_key_exists($propertyName, $mapping))
					{
						$this->bindValue($stmt, null, $propertyInfo);
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
				if ($subDoc->isNew() || $subDoc->isModified())
				{
					$subDoc->save();
				}
			}
		}
	}

	/**
	 * @param string $sql
	 * @return PDOStatement
	 */
	protected abstract function prepareStatement($sql);

	/**
	 * Executes a Statement...
	 * @param PDOStatement $stmt
	 */
	function executeStatement($stmt)
	{
		if (!$stmt->execute())
		{
			$this->showError($stmt);
		}
	}

	//
	// Cache methods
	//

	/**
	 * @return void
	 */
	private function clearDocumentCache($id = null)
	{
		$this->m_documentInstances = array();
		$this->m_i18nDocumentInstances = array();
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	protected final function getCacheService()
	{
		return f_persistentdocument_CacheService::getInstance();
	}

	/**
	 * @param boolean $bool
	 * @return boolean
	 */
	abstract public function setAutoCommit($bool);



	public final function addLang($lang)
	{
		if ($this->columnExists("f_document", "label_".$lang))
		{
			return false;
		}
		$stmt = $this->prepareStatement($this->addLangQuery($lang));
		$this->executeStatement($stmt);
		return true;
	}

	/**
	 * @param String $tableName
	 * @param String $fieldName
	 * @return Boolean
	 */
	abstract protected function columnExists($tableName, $fieldName);


	/**
	 * @param string $lang
	 * @return string sql
	 */
	abstract protected function addLangQuery($lang);

	/**
	 * Enter description here...
	 *
	 * @return array;
	 */
	public final function getI18nFieldNames()
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
	 * @return "SELECT relation_id FROM f_relationname WHERE property_name = :property_name"
	 */
	abstract protected function getRelationIdQuery1();

	/**
	 * @return "INSERT INTO f_relationname (property_name) VALUES (:property_name)"
	 */
	abstract protected function getRelationIdQuery2();

	public final function getRelationId($propertyName)
	{
		$stmt = $this->prepareStatement($this->getRelationIdQuery1());
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
			$stmt = $this->prepareStatement($this->getRelationIdQuery2());
			$stmt->bindValue(':property_name', $propertyName, PersistentProviderConst::PARAM_STR);
			$this->executeStatement($stmt);
			return intval($this->getLastInsertId('f_relationname'));
		}
	}

	/**
	 * @example INSERT INTO f_user_action_entry (entry_date , user_id , document_id , module_name , action_name, username, info)
	 * VALUES (:entry_date, :user_id, :document_id, :module_name, :action_name, :username, :info)
	 * @return String
	 */
	protected function addUserActionEntryQuery()
	{
		return "INSERT INTO f_user_action_entry (entry_date, user_id, document_id, module_name, action_name, username, info) VALUES (:entry_date, :user_id, :document_id, :module_name, :action_name, :username, :info)";
	}

	/**
	 * @param String $date_entry
	 * @param Integer $userId
	 * @param String $moduleName
	 * @param String $actionName
	 * @param Integer $documentId
	 * @param String $username
	 * @param String $serializedInfo
	 * @return Integer
	 */
	public final function addUserActionEntry($date_entry, $userId, $moduleName, $actionName, $documentId, $username, $serializedInfo)
	{
		$stmt = $this->prepareStatement($this->addUserActionEntryQuery());
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
	 * @param Integer $userId
	 * @param String $moduleName
	 * @param String $actionName
	 * @param Integer $documentId
	 * @return Integer
	 */
	public final function getCountUserActionEntry($userId, $moduleName, $actionName, $documentId)
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
	 * @param Integer $userId
	 * @param String $moduleName
	 * @param String $actionName
	 * @param Integer $documentId
	 * @param Integer $rowIndex
	 * @param Integer $rowCount
	 * @param String $sortOnField (date | user)
	 * @param String $sortDirection (ASC | DESC)
	 * @return array(array(entry_id, entry_date, user_id, document_id, module_name, action_name, info, link_id));
	 */
	public final function getUserActionEntry($userId, $moduleName, $actionName, $documentId, $rowIndex, $rowCount, $sortOnField, $sortDirection)
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

	/**
	 *
	 * @param String $fieldName (document | module | action | [user])
	 * @return String
	 * @example SELECT $sqlName as distinctvalue FROM f_user_action_entry GROUP BY $sqlName
	 */
	protected function getDistinctLogEntryQuery($fieldName)
	{
		switch ($fieldName)
		{
			case 'document': $sqlName = 'document_id'; break;
			case 'module': $sqlName = 'module_name'; break;
			case 'action': $sqlName = 'action_name'; break;
			default: $sqlName = 'user_id'; break;
		}
		return "SELECT $sqlName as distinctvalue FROM f_user_action_entry GROUP BY $sqlName";
	}

	/**
	 * @param String $fieldName (document | module | action | [user])
	 * @return array<array<distinctvalue => VALUE>>
	 */
	public final function getDistinctLogEntry($fieldName)
	{
		$stmt = $this->prepareStatement($this->getDistinctLogEntryQuery($fieldName));
		$this->executeStatement($stmt);
		$result = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		return $result;
	}
	
	
	
	
	// Indexing function
	protected function getIndexingDocumentStatusQuery()
	{
		return "SELECT `indexing_status`, `lastupdate` FROM `f_indexing` WHERE `document_id` = :document_id AND `indexing_mode` = :indexing_mode";
	}
	
	/** 
	 * @param integer $documentId
	 * @param integer $mode
	 * @param array<status, lastupdate>
	 */
	public final function getIndexingDocumentStatus($documentId, $mode)
	{
		//Framework::fatal(__METHOD__ . "($documentId, $mode)");
		$stmt = $this->prepareStatement($this->getIndexingDocumentStatusQuery());
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':indexing_mode', $mode, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		if ($result)
		{
			return array($result['indexing_status'], $result['lastupdate']);
		}
		return array(null, null);
	}
	
	protected function setIndexingDocumentStatusSelectQuery()
	{
		return "SELECT `indexing_status` FROM `f_indexing` WHERE `document_id` = :document_id AND `indexing_mode` = :indexing_mode FOR UPDATE";
	}
	
	protected function setUpdateIndexingDocumentStatusQuery()
	{
		return "UPDATE `f_indexing` SET `indexing_status` = :indexing_status, `lastupdate` = :lastupdate WHERE `document_id` = :document_id AND `indexing_mode` = :indexing_mode";
	}

	protected function setInsertIndexingDocumentStatusQuery()
	{
		return "INSERT INTO `f_indexing` (`indexing_status`, `lastupdate`, `document_id`, `indexing_mode`) VALUES (:indexing_status, :lastupdate, :document_id, :indexing_mode)";
	}
	
	/**
	 * @param integer $documentId
	 * @param integer $mode
	 * @param string $newStatus
	 * @param string $lastUpdate
	 */
	public final function setIndexingDocumentStatus($documentId, $mode, $newStatus, $lastUpdate = null)
	{
		$stmt = $this->prepareStatement($this->setIndexingDocumentStatusSelectQuery());
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':indexing_mode', $mode, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		$result = $stmt->fetch(PersistentProviderConst::FETCH_NUM);
		$stmt->closeCursor();
		if (is_array($result) && $result[0] === $newStatus)
		{
			return array($newStatus, null);
		}
		
		if (is_array($result))
		{
			$updatestmt = $this->prepareStatement($this->setUpdateIndexingDocumentStatusQuery());
		}
		else
		{
			$updatestmt = $this->prepareStatement($this->setInsertIndexingDocumentStatusQuery());
		}
		
		if ($lastUpdate === null) {$lastUpdate = date_Calendar::getInstance()->toString();}
		$updatestmt->bindValue(':indexing_status', $newStatus, PersistentProviderConst::PARAM_STR);
		$updatestmt->bindValue(':lastupdate', $lastUpdate, PersistentProviderConst::PARAM_STR);
		$updatestmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$updatestmt->bindValue(':indexing_mode', $mode, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($updatestmt);
		return array($newStatus, $lastUpdate);
	}
	
	protected function deleteIndexingDocumentStatusQuery()
	{
		return "DELETE FROM `f_indexing` WHERE `document_id` = :document_id AND `indexing_mode` = :indexing_mode";
	}
	
	/**
	 * @param integer $documentId
	 * @param integer $mode
	 * @return boolean
	 */
	public final function deleteIndexingDocumentStatus($documentId, $mode)
	{
		//Framework::fatal(__METHOD__ . "($documentId, $mode)");
		$stmt = $this->prepareStatement($this->deleteIndexingDocumentStatusQuery());
		$stmt->bindValue(':document_id', $documentId, PersistentProviderConst::PARAM_INT);
		$stmt->bindValue(':indexing_mode', $mode, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->rowCount() == 1;
	}
	
	protected function clearIndexingDocumentStatusQuery()
	{
		return "DELETE FROM `f_indexing` WHERE `indexing_mode` = :indexing_mode";
	}
	
	/**
	 * @param integer $mode
	 * @return integer
	 */
	public final function clearIndexingDocumentStatus($mode)
	{
		//Framework::fatal(__METHOD__ . "($mode)");
		$stmt = $this->prepareStatement($this->clearIndexingDocumentStatusQuery());
		$stmt->bindValue(':indexing_mode', $mode, PersistentProviderConst::PARAM_INT);
		$this->executeStatement($stmt);
		return $stmt->rowCount();		
	}
	
	protected function getIndexingStatsQuery()
	{
		return "SELECT `indexing_mode`, `indexing_status`, count(`document_id`) as nb_document,  max(`document_id`) as max_id FROM `f_indexing` GROUP BY `indexing_mode`, `indexing_status`";
	}
	
	/**
	 * @return array<indexing_mode =>, indexing_status =>, nb_document =>, max_id>
	 */
	public final function getIndexingStats()
	{
		//Framework::fatal(__METHOD__);
		$stmt = $this->prepareStatement($this->getIndexingStatsQuery());
		$this->executeStatement($stmt);
		$result = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		return $result;
	}	
	
	
	protected function getIndexingPendingEntriesQuery()
	{
		return "SELECT `indexing_mode`, max(`document_id`) as max_id FROM `f_indexing` WHERE `indexing_status` <> 'INDEXED' GROUP BY `indexing_mode`";
	}
	
	/**
	 * @return array<indexing_mode => integer, max_id => integer >
	 */
	public final function getIndexingPendingEntries()
	{
		$stmt = $this->prepareStatement($this->getIndexingPendingEntriesQuery());
		$this->executeStatement($stmt);
		$result = $stmt->fetchAll(PersistentProviderConst::FETCH_ASSOC);
		$stmt->closeCursor();
		return $result;
	}
	
	protected function getIndexingDocumentsQuery($chunkSize)
	{
		return "SELECT `document_id` FROM `f_indexing` WHERE  `indexing_mode` = :indexing_mode AND `document_id` <= :document_id AND `indexing_status` <> 'INDEXED' ORDER BY `document_id` DESC LIMIT 0, " .intval($chunkSize);
	}
	
	/** 
	 * @param integer $documentId
	 * @param integer $mode
	 * @param integer[]
	 */
	public final function getIndexingDocuments($mode, $maxDocumentId, $chunkSize = 100)
	{
		//Framework::fatal(__METHOD__ . "($mode, $maxDocumentId, $chunkSize)");
		$stmt = $this->prepareStatement($this->getIndexingDocumentsQuery($chunkSize));
		$stmt->bindValue(':indexing_mode', $mode, PersistentProviderConst::PARAM_INT);
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

class f_DatabaseException extends Exception
{
	private $errorCode;

	public function __construct($errorCode, $msg)
	{
		$this->errorCode = (int) $errorCode;
		parent::__construct($msg);
	}

	public function getErrorCode()
	{
		return $this->errorCode;
	}
}