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
		//self::clearInstanceByClassName('\Change\Db\Provider');
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
			elseif ($fetchMode === QueryConstants::FETCH_MODE_DIRECT) //TODO Old class Usage
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
	 * @param f_persistentdocument_criteria_ExecutableQuery $query TODO Old class Usage
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

		//TODO ehau : experimental
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