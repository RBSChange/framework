<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument.criteria
 */
class QueryConstants
{
	const ID = 'document_id';
	const MODEL = 'document_model';

	const FETCH_MODE_LAZY = 1;
	const FETCH_MODE_DIRECT = 2;
}

interface f_persistentdocument_criteria_Criteria
{
	/**
	 * @param f_persistentdocument_criteria_Criterion $criterion
	 * @return f_persistentdocument_criteria_Criteria
	 */
	function add($criterion);

	/**
	 * @param string $relationName
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Criteria
	 */
	function createCriteria($relationName, $documentModelName = null);
	
	
	/**
	 * @param string $propertyName
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Criteria
	 */
	function createPropertyCriteria($propertyName, $documentModelName);	
	
	/**
	 * @param f_persistentdocument_criteria_Projection [$args]
	 * @return f_persistentdocument_criteria_Criteria
	 */
	function setProjection();
}



interface f_persistentdocument_criteria_Query // extends f_persistentdocument_criteria_Criteria
{
	/**
	 * @param const $fetchMode QueryConstants::FETCH_MODE_LAZY or QueryConstants::FETCH_MODE_DIRECT
	 * @return f_persistentdocument_criteria_Query
	 */
	function setFetchMode($fetchMode);

	/**
	 * @param $fetchMode
	 * @return const QueryConstants::FETCH_MODE_LAZY or QueryConstants::FETCH_MODE_DIRECT
	 */
	function getFetchMode();

	/**
	 * @param f_persistentdocument_criteria_Projection [$args]
	 * @return f_persistentdocument_criteria_Query
	 */
	function setProjection();
		
	/**
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Query
	 */
	function setDocumentModelName($documentModelName);

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 * @return f_persistentdocument_criteria_Query
	 */
	function setDocumentModel($documentModel);

	/**
	 * Apply an order to the root query.
	 * @param Order $order
	 * @return f_persistentdocument_criteria_Query
	 */
	function addOrder($order);

	/**
	 * @param integer $firstResult
	 * @return f_persistentdocument_criteria_Query
	 */
	function setFirstResult($firstResult);

	/**
	 * @param integer $maxResult
	 * @return f_persistentdocument_criteria_Query
	 */
	function setMaxResults($maxResult);

	// grrr for next two methods : extends ... zend auto-completion ... php.

	/**
	 * @see f_persistentdocument_criteria_Criteria
	 * @return f_persistentdocument_criteria_Query
	 */
	function add($criterion);

	/**
	 * @param string $relationName
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Criteria
	 */
	function createCriteria($relationName, $documentModelName = null);
	
	
	/**
	 * @param string $propertyName
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Criteria
	 */
	function createPropertyCriteria($propertyName, $documentModelName);
	
	/**
	 * @param string $propertyName
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Criteria
	 */
	function createLeftCriteria($propertyName, $documentModelName = null);

	/**
	 * Shortcut for <code>$persitentProviderInstance->find($this)</code>
	 * @see f_persistentdocument_PersistentProvider#find
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	function find();

	/**
	 * Shortcut for <code>$persitentProviderInstance->findUnique($this)</code>
	 * @see f_persistentdocument_PersistentProvider#find
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	function findUnique();
	
	/**
	 * If the query has some projection, retrieve one of them into a dedicated array
	 * Shortcut for <code>$persitentProviderInstance->findColumn($this, $columnName)</code>
	 * @see f_persistentdocument_PersistentProvider#findColumn
	 * @param string $columnName  the name of the projection
	 * @return mixed[]
	 */
	function findColumn($columnName);

	/**
	 * Delete all the document matching the query
	 * @return integer number of deleted documents
	 */
	function delete();

	/**
	 * @param f_persistentdocument_criteria_HavingCriterion [$args]
	 * @return f_persistentdocument_criteria_Query
	 */
	function having();
}

interface f_persistentdocument_criteria_ExecutableQuery extends f_persistentdocument_criteria_Query
{

	/**
	 * @param string $name
	 */
	function addDocumentProjection($name);

	/**
	 * @return array<String>
	 */
	function getDocumentProjections();

	/**
	 * @return array<f_persistentdocument_criteria_Criterion>
	 */
	public function getCriterions();
	 
	/**
	 * @return array<f_persistentdocument_criteria_f_persistentdocument_criteria_ExecutableQuery>
	 */
	public function getCriterias();

	/**
	 * @return boolean
	 */
	public function hasCriterias();

	/**
	 * @return boolean
	 */
	public function hasCriterions();

	/**
	 * @return array<f_persistentdocument_criteria_TreeCriterion>
	 */
	public function getTreeCriterions();

	/**
	 * @return array
	 */
	public function getOrders();

	/**
	 * @return boolean
	 */
	public function hasOrders();

	/**
	 * @return integer
	 */
	public function getFirstResult();
	
	/**
	 * @param string $columnName
	 * @return f_persistentdocument_criteria_Query
	 */
	public function setFetchColumn($columnName);

	/**
	 * @return integer
	 */
	public function getMaxResults();

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public function getDocumentModel();

	/**
	 * @return boolean
	 */
	public function hasProjection();
	
	

	/**
	 * @return f_persistentdocument_criteria_Projection[]
	 */
	public function getProjection();
}

class f_persistentdocument_criteria_QueryImpl implements f_persistentdocument_criteria_Query, f_persistentdocument_criteria_ExecutableQuery
{
	private static $F_DOCUMENT_FIELDS = array(QueryConstants::ID, QueryConstants::MODEL);

	/**
	 * @var array<f_persistentdocument_criteria_Projection>
	 */
	private $projections = array();

	/**
	 * @var String
	 */
	private $documentProjections = array();

	/**
	 * @var f_persistentdocument_criteria_Query
	 */
	private $parentQuery;
	
	/**
	 * @var boolean
	 */
	private $inverseQuery = false;	
	
	/**
	 * @var boolean
	 */
	private $leftJoin = false;
	
	/**
	 * @var array
	 */
	private $criterions = array();
	/**
	 * @var array
	 */
	private $treeCriterions = array();

	/**
	 * @var unknown_type
	 */
	private $havingCriterions = array();

	/**
	 * @var array
	 */
	private $criterias = array();
	/**
	 * @var array
	 */
	private $orders = array();
	/**
	 * @var Integer
	 */
	private $firstResult = 0;
	/**
	 * @var Integer
	 */
	private $maxResults = -1;

	/**
	 * @var f_persistentdocument_PersistentDocumentModel
	 */
	private $model;

	/**
	 * The Query fetch mode, QueryConstants::FETCH_MODE_LAZY by default
	 * @var const
	 */
	private $fetchMode = QueryConstants::FETCH_MODE_LAZY;
	
	/**
	 * @var String
	 */
	private $fetchColumnName;

	/**
	 * @var f_persistentdocument_PersistentProvider
	 */
	private $pp;
	
	/**
	 * @param f_persistentdocument_PersistentProvider $provider
	 * @return f_persistentdocument_criteria_QueryImpl
	 */
	public function setProvider($provider)
	{
		$this->pp = $provider;
		return $this;
	}
	
	public function getProvider()
	{
		if ($this->pp !== null)
		{
			return $this->pp;
		}
		return f_persistentdocument_PersistentProvider::getInstance();
	}
	
	/**
	 * @param const $fetchMode QueryConstants::FETCH_MODE_LAZY or QueryConstants::FETCH_MODE_DIRECT
	 * @return f_persistentdocument_criteria_QueryImpl
	 */
	public function setFetchMode($fetchMode)
	{
		if ($fetchMode === QueryConstants::FETCH_MODE_DIRECT && $this->model === null)
		{
			throw new Exception("Can not set fetchMode to DIRECT if the document model is not explicit");
		}
		$this->fetchMode = $fetchMode;
		return $this;
	}
	
	/**
	 * @param string $columnName
	 * @return f_persistentdocument_criteria_Query
	 */
	public function setFetchColumn($columnName)
	{
		$this->fetchColumnName = $columnName;
		return $this;
	}

	/**
	 * @param $fetchMode
	 * @return const QueryConstants::FETCH_MODE_LAZY or QueryConstants::FETCH_MODE_DIRECT
	 */
	public function getFetchMode()
	{
		return $this->fetchMode;
	}

	/**
	 * @param string $documentModel
	 * @see persistentdocument/criteria/f_persistentdocument_criteria_Query#setDocumentModelName($documentModelName)
	 */
	public function setDocumentModelName($documentModelName, $includeChildren = true)
	{
		$this->setDocumentModel(f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($documentModelName), $includeChildren);
		return $this;
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 * @see persistentdocument/criteria/f_persistentdocument_criteria_Query#setDocumentModel($documentModel)
	 */
	public function setDocumentModel($documentModel, $includeChildren = true)
	{
		$this->model = $documentModel;
		if ($documentModel->hasParent())
		{
			if ($includeChildren)
			{
				$model = $documentModel;				
				if ($model->hasChildren())
				{
					$modelNames = $model->getChildrenNames();
					$modelNames[] = $documentModel->getName();
					$this->add(Restrictions::in("model", $modelNames));
				}
				else
				{
					$this->add(Restrictions::eq("model", $documentModel->getName()));
				}
			}
			else
			{
				$this->add(Restrictions::eq("model", $documentModel->getName()));
			}
		}
		elseif (!$includeChildren && $documentModel->hasChildren())
		{
			$this->add(Restrictions::eq("model", $documentModel->getName()));
		}
		return $this;
	}

	/**
	 * @param f_persistentdocument_criteria_Projection $projection,...
	 * @return f_persistentdocument_criteria_Query
	 */
	public function setProjection()
	{
		return $this->setProjectionArray(func_get_args());
	}
	
	/**
	 * @param f_persistentdocument_criteria_Projection[] $projections
	 * @return f_persistentdocument_criteria_Query
	 */
	public function setProjectionArray($projections)
	{
		$this->documentProjections = array(); 
		$this->projections = array();
		foreach ($projections as $projection)
		{
			$this->addProjection($projection);
		}
		return $this;
	}

	/**
	 * @param f_persistentdocument_criteria_Projection $projection
	 * @return f_persistentdocument_criteria_Query
	 */
	public function addProjection($projection)
	{
		if ($projection instanceof f_persistentdocument_criteria_Projection)
		{
			$this->projections[] = $projection;
		}
		else
		{
			throw new Exception(get_class($projection) . ' is not a projection.');
		}
		return $this;
	}

	/**
	 * "private" use of QueryBuilder
	 *
	 * @param unknown_type $name
	 */
	public function addDocumentProjection($name)
	{
		$this->documentProjections[] = $name;
	}

	/**
	 * "private" use of QueryBuilder
	 *
	 * @param unknown_type $name
	 */
	public function getDocumentProjections()
	{
		return $this->documentProjections;
	}

	public function addOrder($order)
	{
		if ($this->hasParent())
		{
			$this->getParentQuery()->addOrder($order);
		}
		else
		{
			$this->orders[] = $order;
		}
		return $this;
	}

	public function setFirstResult($firstResult)
	{
		if ($this->hasParent())
		{
			$this->getParentQuery()->setFirstResult($firstResult);
		}
		else
		{
			$this->firstResult = $firstResult;
		}
		return $this;
	}

	public function setMaxResults($maxResults)
	{
		if ($this->hasParent())
		{
			$this->getParentQuery()->setMaxResults($maxResults);
		}
		else
		{
			$this->maxResults = $maxResults;
		}
		return $this;
	}

	public function add($criterion)
	{
		if (is_null($criterion))
		{
			throw new Exception('Null criterion has not allowed');
		}

		if ($criterion instanceof Example)
		{
			$this->addExample($criterion);
		}
		else if ($criterion instanceof f_persistentdocument_criteria_TreeCriterion)
		{
			$this->treeCriterions[] = $criterion;
		}
		else if ($criterion instanceof f_persistentdocument_criteria_HasTagExpression)
		{
			$this->criterions[] = $criterion;
		}
		else if ($criterion instanceof f_persistentdocument_criteria_Junction)
		{
			$this->criterions[] = $criterion;
		}
		else // "DATA" criterion
		{
			if (is_null($this->model) && !in_array($criterion->getPropertyName(), self::$F_DOCUMENT_FIELDS))
			{
				throw new Exception('Can not query on '.$criterion->getPropertyName().' with a NULL model');
			}
				
			$this->criterions[] = $criterion;
				
		}
		return $this;
	}

	/**
	 * @param f_persistentdocument_criteria_HavingCriterion [$args]
	 * @return f_persistentdocument_criteria_Query
	 */
	function having()
	{
		foreach (func_get_args() as $havingCriterion)
		{
			if ($havingCriterion instanceof f_persistentdocument_criteria_HavingCriterion)
			{
				$this->havingCriterions[] = $havingCriterion;
			}
			else
			{
				throw new Exception(get_class($havingCriterion) . ' is not a HavingCriterion.');
			}
		}

		return $this;
	}

	/**
	 * @return boolean
	 */
	function hasHavingCriterion()
	{
		return !empty($this->havingCriterions);
	}

	/**
	 * @return f_persistentdocument_criteria_HavingCriterion[]
	 */
	function getHavingCriterion()
	{
		return $this->havingCriterions;
	}

	/**
	 * @param string $relationName
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Criteria
	 */
	public function createCriteria($relationName, $documentModelName = null)
	{
		$c = new f_persistentdocument_criteria_QueryImpl();
		$subModel = null;
		if ($this->model === null)
		{
			throw new Exception('Can not create criteria without model assigned (see setDocumentModelName())');
		}
		$property = $this->model->getProperty($relationName);
		if ($property === null)
		{
			$property = $this->model->getInverseProperty($relationName);
			$c->inverseQuery = true;
			
		}
		
		if ($property === null && $documentModelName !== null)
		{
			$subModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($documentModelName);
			$property = $subModel->getProperty($relationName);
			if ($property !== null && !$this->model->isModelCompatible($property->getDocumentType()))
			{
				throw new Exception($documentModelName . ' Is not compatible with '. $property->getDocumentType() . ' for property ' . $relationName);	
			}
		}
		
		if ($property === null)
		{
			throw new Exception('Can not create criteria on unknown property '.$relationName);
		}
		if (!$property->isDocument())
		{
			throw new Exception('Can not create criteria on scalare property '.$relationName);
		}
		
		if ($documentModelName === null)
		{
			$documentModelName = $property->getDocumentType();
		}
		else if ($subModel === null && !f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($documentModelName)
				->isModelCompatible($property->getDocumentType()))
		{
			throw new Exception($documentModelName . ' Is not compatible with '. $property->getDocumentType() . ' for property ' . $relationName);	
		}
		$c->setDocumentModelName($documentModelName);
		$c->setParentQuery($this);
		$c->setProvider($this->getProvider());
		$this->addCriteria($relationName, $c);
		return $c;
	}
	
	/**
	 * @param string $propertyName
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Criteria
	 */
	public function createPropertyCriteria($propertyName, $documentModelName)
	{
		if ($this->model === null)
		{
			throw new Exception('Can not create criteria without model assigned (see setDocumentModelName())');
		}
		$c = new f_persistentdocument_criteria_QueryImpl();
		$property = $this->model->getProperty($propertyName);
		if (is_null($property))
		{
			$subModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($documentModelName);
			$property = $subModel->getProperty($propertyName);
			$c->inverseQuery = true;
			if ($property === null)
			{
				throw new Exception('Can not create criteria on unknown property '.$propertyName);
			}
		}
		if ($property->getType() !== f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER && 
			$property->getType() !== f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTID)
		{
			throw new Exception('Can not create criteria on none INTEGER property '.$propertyName);
		}
		$c->setDocumentModelName($documentModelName);
		$c->setParentQuery($this);
		$c->setProvider($this->getProvider());
		$this->addCriteria($propertyName, $c);
		return $c;		
	}
	
	/**
	 * @param string $propertyName
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Criteria
	 */
	function createLeftCriteria($propertyName, $documentModelName = null)
	{
		if ($this->model === null)
		{
			throw new Exception('Can not create criteria without model assigned (see setDocumentModelName())');
		}
		
		$c = new f_persistentdocument_criteria_QueryImpl();
		$subModel = null;
		$property = null;
		if ($documentModelName !== null)
		{
			$subModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($documentModelName);
			$property = $subModel->getProperty($propertyName);
			if ($property !== null)
			{
				if ($property->getType() === f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER ||
					$property->getType() === f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTID || 
					($property->isDocument() && !$property->isArray()))
				{
					$c->inverseQuery = true;
				}
				else
				{
					$property = null;					
				}
			}
		}
		
		if ($property === null)
		{
			$property = $this->model->getProperty($propertyName);
			if ($property === null)
			{
				$property = $this->model->getInverseProperty($propertyName);
				if ($property === null)
				{
					throw new Exception('Property '.$propertyName . ' not found on document ' . $this->model->getName());
				}
				$c->inverseQuery = true;		
			}
			
			if ($property->getType() !== f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER && 
				$property->getType() !== f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTID && 
				!$property->isDocument())
			{
				throw new Exception('Property '.$propertyName . ' has no valid type ' . $property->getType());
			}
			
			if ($subModel === null)
			{
				if ($property->isDocument())
				{
					$documentModelName = $property->getDocumentType();
					$subModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($documentModelName);
				}
				else
				{
					throw new Exception('Property '.$propertyName . ' has no valid type ' . $property->getType());
				}
			}
			if ($property->getType() !== f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER && 
				$property->getType() !== f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTID &&
				!$property->isDocument())
			{
				throw new Exception('Property '.$propertyName . ' has no valid type ' . $property->getType());
			}
		}
		
		$c->leftJoin = true;
		$c->setDocumentModel($subModel, true);
		$c->setParentQuery($this);
		$c->setProvider($this->getProvider());
		$this->addCriteria($propertyName, $c);
		return $c;		
	}

	public function createSubCriteria($relationName)
	{
		$c = new f_persistentdocument_criteria_QueryImpl();
		$property = $this->model->getProperty($relationName);
		if (is_null($property))
		{
			$property = $this->model->getInverseProperty($relationName);
			$c->inverseQuery = true;
			if (is_null($property))
			{
				throw new Exception('Can not create criteria on unknown property '.$relationName);
			}
		}
		if (!$property->isDocument())
		{
			throw new Exception('Can not create criteria on scalare property '.$relationName);
		}
		$c->setDocumentModelName($property->getDocumentType());
		$c->setParentQuery($this);
		$c->setProvider($this->getProvider());
		return array($relationName, $c);
	}

	/**
	 * @return array
	 */
	public function getCriterions()
	{
		return $this->criterions;
	}

	/**
	 * @return array
	 */
	public function getTreeCriterions()
	{
		return $this->treeCriterions;
	}

	/**
	 * @return array
	 */
	public function getCriterias()
	{
		return $this->criterias;
	}

	/**
	 * @return array
	 */
	public function getOrders()
	{
		return $this->orders;
	}

	/**
	 * @return integer
	 */
	public function getFirstResult()
	{
		return $this->firstResult;
	}

	/**
	 * @return integer
	 */
	public function getMaxResults()
	{
		return $this->maxResults;
	}

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public function getDocumentModel()
	{
		return $this->model;
	}

	/**
	 * @return boolean
	 */
	public function hasCriterias()
	{
		return !empty($this->criterias);
	}

	/**
	 * @return boolean
	 */
	public function hasCriterions()
	{
		return !empty($this->criterions);
	}

	/**
	 * @return boolean
	 */
	public function hasTreeCriterions()
	{
		return !empty($this->treeCriterions);
	}

	/**
	 * @return boolean
	 */
	public function hasOrders()
	{
		return !empty($this->orders);
	}

	/**
	 * @return f_persistentdocument_criteria_Projection[]
	 */
	public function getProjection()
	{
		return $this->projections;
	}

	/**
	 * @return boolean
	 */
	public function hasProjection()
	{
		return !empty($this->projections);
	}
	
	/**
	 * @return boolean true if the query or any of the criterias has a projection
	 */
	public function hasProjectionDeep()
	{
		if ($this->hasProjection())
		{
			return true;
		}
		foreach ($this->criterias as $criteria)
		{
			if ($criteria->hasProjectionDeep())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Shortcut for <code>$persitentProviderInstance->find($this)</code> or
	 * <code>$persitentProviderInstance->findColumn($this, $fetchColumnName)</code> (if setFetchColumnName() was called)
	 * @see f_persistentdocument_PersistentProvider#find
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	function find($providerInstance = null)
	{
		if ($providerInstance === null)
		{
			
		}
		if ($this->fetchColumnName !== null)
		{
			return $this->getProvider()->findColumn($this, $this->fetchColumnName);
		}
		return $this->getProvider()->find($this);
	}
	
	/**
	 * If the query has some projection, retrieve one of them into a dedicated array
	 * Shortcut for <code>$persitentProviderInstance->findColumn($this, $columnName)</code>
	 * @see f_persistentdocument_PersistentProvider#findColumn
	 * @param string $columnName  the name of the projection
	 * @return mixed[]
	 */
	function findColumn($columnName)
	{
		return $this->getProvider()->findColumn($this, $columnName);
	}

	/**
	 * Shortcut for <code>$persitentProviderInstance->findUnique($this)</code>
	 * @see f_persistentdocument_PersistentProvider#find
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	function findUnique()
	{
		return $this->getProvider()->findUnique($this);
	}

	/**
	 * Delete all the documents matching the query.<br/>
	 * <strong>Note</strong> : only for queries returning documents (of course...)
	 * @return integer number of deleted documents
	 */
	function delete()
	{
		// TODO: enhance this sometimes doing direct delete in the DB.
		// Think how to manage correctly the code that is linked to the document deletion
		$documents = $this->find();
		foreach ($documents as $document)
		{
			$document->delete();
		}
		return count($documents);
	}

	// private methods

	/**
	 * @param Example $example
	 */
	private function addExample($example)
	{
		$properties = $example->getDocumentInstanceProperties();
		foreach ($properties['scalars'] as $propertyName => $value)
		{
			if (is_null($value))
			{
				$this->criterions[] = Restrictions::isNull($propertyName);
			}
			else if ($example->getLikeEnabled())
			{
				$this->criterions[] = Restrictions::like($propertyName, $value, $example->getMatchMode(), $example->getIgnoreCase());
			}
			else
			{
				$this->criterions[] = Restrictions::eq($propertyName, $value, $example->getIgnoreCase());
			}
		}
		foreach ($properties['documents'] as $propertyName => $value)
		{
			if (count($value) == 0)
			{
				$this->criterions[] = Restrictions::isNull($propertyName);
			}
			else
			{
				// TODO: Bof ...
				//echo "SubCriteria : $propertyName\n";
				$c = $this->createCriteria($propertyName);

				if (count($value) > 1)
				{
					throw new Exception('Multiple sub-document examples not implemented');
				}
				$e = Example::create($value[0]);

				if ($example->getLikeEnabled())
				{
					$e->enableLike($example->getMatchMode());
				}
				if ($example->getIgnoreCase())
				{
					$e->ignoreCase();
				}
				$c->add($e);
			}
		}
	}

	/**
	 * @param string $relationName
	 * @param f_persistentdocument_criteria_Criteria $criteria
	 */
	private function addCriteria($relationName, $criteria)
	{
		$this->criterias[$relationName] = $criteria;
	}

	/**
	 * @param f_persistentdocument_criteria_Query $parentQuery
	 */
	public function setParentQuery($parentQuery)
	{
		$this->parentQuery = $parentQuery;
	}
	
	/**
	 * @return f_persistentdocument_criteria_Query
	 */
	private function getParentQuery()
	{
		return $this->parentQuery;
	}

	/**
	 * @return boolean
	 */
	public function hasParent()
	{
		return !is_null($this->parentQuery);
	}
	
	/**
	 * @return boolean
	 */
	public function getInverseQuery()
	{
		return $this->inverseQuery;
	}
	
	/**
	 * @return boolean
	 */
	public function getLeftJoin()
	{
		return $this->leftJoin;
	}
	
	public function __toString()
	{
		$str = "Criterias:\n";
		foreach ($this->criterias as $criteria)
		{
			$str .= get_class($criteria)." ";
		}
		$str .= "Criterions:\n";
		foreach ($this->criterions as $criterion)
		{
			$str .= get_class($criterion)." ";
		}
		return $str;
	}
}

class f_persistentdocument_criteria_QueryIntersection
{
	
	/**
	 * @var f_persistentdocument_criteria_Query[]
	 */
	private $queries = array();
	/**
	 * @var f_persistentdocument_PersistentDocumentModel
	 */
	private $documentModel;
	
	
	private $maxResult = -1;
	
	/**
	 * @var f_persistentdocument_PersistentProvider
	 */
	private $pp;
	
	/**
	 * @param f_persistentdocument_PersistentProvider $provider
	 * @return f_persistentdocument_criteria_QueryImpl
	 */
	public function setProvider($provider)
	{
		$this->pp = $provider;
		return $this;
	}
	
	public function getProvider()
	{
		if ($this->pp !== null)
		{
			return $this->pp;
		}
		return f_persistentdocument_PersistentProvider::getInstance();
	}
	
	/**
	 * @param f_persistentdocument_criteria_Query $query
	 * @return f_persistentdocument_criteria_QueryIntersection
	 */
	function add($query)
	{
		$queryModel = $query->getDocumentModel();
		if ($this->documentModel === null)
		{
			$this->documentModel = $queryModel;
		}

		//Ordered queries at first
		if (f_util_ClassUtils::methodExists($query, "hasOrders") && $query->hasOrders() && count($this->queries) > 0)
		{
			$this->queries = array_merge(array($query), $this->queries);
		}
		else
		{
			$this->queries[] = $query;
		}
		
		return $this;
	}
	
	/**
	 * @return integer || -1
	 */
	public function getMaxResults()
	{
		return $this->maxResult;
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	function getDocumentModel()
	{
		return $this->documentModel;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 */
	public function setDocumentModel($documentModel)
	{
		$this->documentModel = $documentModel;
	}
	
	/**
	 * @return f_persistentdocument_criteria_Query[]
	 */
	function getQueries()
	{
		return $this->queries;
	}
	
	/**
	 * @return boolean
	 */
	function isEmpty()
	{
		return count($this->queries) == 0;
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	function find()
	{
		return $this->getProvider()->findIntersection($this);
	}
	
	/**
	 * @return integer[]
	 */
	function findIds()
	{
		return $this->getProvider()->findIntersectionIds($this);
	}
	
	function getIds()
	{
		return $this->findIds();
	}
	
	/**
	 * @param integer $offset
	 * @param integer $count
	 * @param string $orderByIds [null] | 'ASC' | 'DESC'
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	function findAtOffset($offset, $count, &$totalCount = null, $orderByIds = null)
	{
		$ids = $this->findIds();
		$totalCount = count($ids);
		if ($totalCount == 0 || $offset >= $totalCount)
		{
			return array();
		}
		
		if ($orderByIds === 'ASC')
		{
			sort($ids);
		}
		else if ($orderByIds === 'DESC')
		{
			rsort($ids);
		}
		
		$pp = $this->getProvider();
		$q = $pp->createQuery($this->getDocumentModel()->getName())->add(Restrictions::in("id", array_slice($ids, $offset, $count)));
		if ($orderByIds === 'ASC')
		{
			$q->addOrder(Order::asc('id'));
		} 
		else if ($orderByIds === 'DESC')
		{
			$q->addOrder(Order::desc('id'));
		}
		
		return $pp->find($q);
	}
}

class f_persistentdocument_criteria_QueryUnion
{
	/**
	 * @var f_persistentdocument_criteria_Query[]
	 */
	private $queries = array();
	/**
	 * @var f_persistentdocument_PersistentDocumentModel
	 */
	private $documentModel;
	
	/**
	 * @var f_persistentdocument_PersistentProvider
	 */
	private $pp;
	
	/**
	 * @param f_persistentdocument_PersistentProvider $provider
	 * @return f_persistentdocument_criteria_QueryImpl
	 */
	public function setProvider($provider)
	{
		$this->pp = $provider;
		return $this;
	}
	
	public function getProvider()
	{
		if ($this->pp !== null)
		{
			return $this->pp;
		}
		return f_persistentdocument_PersistentProvider::getInstance();
	}
	
	/**
	 * @param f_persistentdocument_criteria_Query $query
	 * @return f_persistentdocument_criteria_QueryUnion
	 */
	function add($query)
	{
		$queryModel = $query->getDocumentModel();
		if ($this->documentModel === null)
		{
			$this->documentModel = $queryModel;
		}

		$this->queries[] = $query;
		
		return $this;
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	function getDocumentModel()
	{
		return $this->documentModel;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $documentModel
	 */
	public function setDocumentModel($documentModel)
	{
		$this->documentModel = $documentModel;
	}
	
	/**
	 * @return f_persistentdocument_criteria_Query[]
	 */
	function getQueries()
	{
		return $this->queries;
	}
	
	/**
	 * @return boolean
	 */
	function isEmpty()
	{
		return count($this->queries) == 0;
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	function find()
	{
		return $this->getProvider()->findUnion($this);
	}
	
	/**
	 * @return integer[]
	 */
	function findIds()
	{
		return $this->getProvider()->findUnionIds($this);
	}
	
	/**
	 * @return integer[]
	 */
	function getIds()
	{
		return $this->findIds();
	}
	
	/**
	 * @param integer $offset
	 * @param integer $count
	 * @param string $orderByIds [null] | 'ASC' | 'DESC'
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	function findAtOffset($offset, $count, &$totalCount = null, $orderByIds = null)
	{
		$ids = $this->findIds();
		$totalCount = count($ids);
		if ($totalCount == 0 || $offset >= $totalCount)
		{
			return array();
		}
		$pp = $this->getProvider();
		$q = $pp->createQuery($this->getDocumentModel()->getName())->add(Restrictions::in("id", array_slice($ids, $offset, $count)));
		if ($orderByIds === 'ASC')
		{
			$q->addOrder(Order::asc('id'));
		} 
		else if ($orderByIds === 'DESC')
		{
			$q->addOrder(Order::desc('id'));
		}
		return $pp->find($q);
	}
}