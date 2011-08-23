<?php
/**
 * @package framework.indexer.query
 */
abstract class indexer_QueryBase
{
	private $sortArray;
	private $showScore = true;
	private $filter = null;
	/**
	 * @var indexer_QueryBase[]
	 */
	private $filters = array();
	private $offset = 0;
	private $rows = 10;
	private $limitToFields = array();
	private $highlight = false;
	private $lang = null;
	protected $boost = null;
	private $clientId;
	/**
	 * @var String[]
	 */
	private $facets = array();

	public function setSortOnField($name, $descending=true)
	{
		if (!is_bool($descending))
		{
			$descending = true;
		}
		$this->sortArray[$name] = $descending;
		return $this;
	}

	public function getSortArray()
	{
		return $this->sortArray;
	}

	public function setShowScore($bool)
	{
		$this->showScore = $bool;
		return $this;
	}

	public function getShowScore()
	{
		return $this->showScore;
	}

	public function setFilterQuery($query)
	{
		$this->filter = $query;
		return $this;
	}
	
	/**
	 * @param indexer_QueryBase $query 
	 * @param String $tag A filter query can be tagged ; Cf. http://wiki.apache.org/solr/SimpleFacetParameters#Tagging_and_excluding_Filters
	 */
	public function addFilterQuery($query, $tag = null)
	{
		$this->filters[] = array($query, $tag);
	}
	
	/**
	 * @param String|indexer_Facet $fieldNameOrFacetObject
	 * @return indexer_Facet
	 */
	public function addFacet($fieldNameOrFacetObject)
	{
		if (is_string($fieldNameOrFacetObject))
		{
			$facet = new indexer_Facet($fieldNameOrFacetObject);
		}
		elseif ($fieldNameOrFacetObject instanceof indexer_Facet)
		{
			$facet = $fieldNameOrFacetObject;
		}
		$this->facets[$facet->field] = $facet;
		return $facet;
	}
	
	/**
	 * @param String $fieldName
	 * @return indexer_Facet
	 */
	public function addStringFacet($fieldName)
	{
		return $this->addFacet(indexer_Field::getStringFieldName($fieldName));
	}
	
	/**
	 * @param String $fieldName
	 * @return indexer_Facet
	 */
	public function addVolatileStringFacet($fieldName)
	{
		return $this->addFacet(indexer_Field::getVolatileStringFieldName($fieldName));
	}
	
	/**
	 * @param String $fieldName
	 * @return indexer_Facet
	 */
	public function addIntegerFacet($fieldName)
	{
		return $this->addFacet(indexer_Field::getIntegerFieldName($fieldName));
	}
	
	/**
	 * @param String $fieldName
	 * @param Boolean $multiple
	 * @return indexer_Facet
	 */
	public function addVolatileIntegerFacet($fieldName, $multiple = false)
	{
		if ($multiple)
		{
			return $this->addFacet(indexer_Field::getVolatileIntegerMultiFieldName($fieldName));
		}
		return $this->addFacet(indexer_Field::getVolatileIntegerFieldName($fieldName));		
	}
	
	/**
	 * @param String $fieldName
	 * @return indexer_Facet
	 */
	public function addVolatileIntegerMultiFacet($fieldName)
	{
		return $this->addVolatileIntegerFacet($fieldName, true);
	}
	
	/**
	 * @param String $fieldName
	 * @return indexer_Facet
	 */
	public function addFloatFacet($fieldName)
	{
		return $this->addFacet(indexer_Field::getFloatFieldName($fieldName));
	}
	
	/**
	 * @param String $fieldName
	 * @return indexer_Facet
	 */
	public function addVolatileFloatFacet($fieldName)
	{
		return $this->addFacet(indexer_Field::getVolatileFloatFieldName($fieldName));
	}

	/**
	 * @return Boolean
	 */
	public function hasFacet()
	{
		return count($this->facets) > 0;
	}

	/**
	 * @return indexer_Facet[]
	 */
	public function getFacets()
	{
		return $this->facets;
	}
	
	/**
	 * @param indexer_Facet[] $facets
	 */
	public function setFacets($facets)
	{
		$this->facets = $facets;
	}

	/**
	 * @var String[]
	 */
	private $stats = null;
	
	/**
	 * @param String $fieldName
	 */
	public function addStatsOnField($fieldName)
	{
		if ($this->stats === null)
		{
			$this->stats = array();
		}
		$this->stats[] = $fieldName;
	}
	
	/**
	 * @return String[]
	 */
	public function getStats()
	{
		return $this->stats;
	}
	
	/**
	 * @return Boolean
	 */
	public function hasStats()
	{
		return f_util_ArrayUtils::isNotEmpty($this->stats);
	}

	/**
	 * @return indexer_Query
	 */
	public function getFilterQuery()
	{
		$boolQuery = indexer_QueryHelper::andInstance();
		if (!is_null($this->filter))
		{
			$boolQuery->add($this->filter);
		}
		if (!f_util_StringUtils::isEmpty($this->clientId))
		{
			$boolQuery->add(new indexer_TermQuery('client', $this->clientId));
		}
		$accessorFilterQuery = $this->getAccessorFilterQuery();
		if ($accessorFilterQuery !== null)
		{
			$boolQuery->add($accessorFilterQuery);
		}
		if ($boolQuery->getSubqueryCount() > 0)
		{
			return $boolQuery;
		}
		return null;
	}
	
	/**
	 * @return array {indexer_QueryBase[], String}[]
	 */
	public function getOtherFilterQueries()
	{
		return $this->filters;
	}

	/**
	 * Get the OR'ed query that will filter results on all document a user has the right to view
	 *
	 * @return indexer_Query or null if no accessor filter has to be made
	 */
	private function getAccessorFilterQuery()
	{
		$currentUser = users_UserService::getInstance()->getCurrentUser();
		if (($currentUser instanceof users_persistentdocument_backenduser) && $currentUser->getIsroot())
		{
			return null;
		}
		$res = indexer_QueryHelper::orInstance();
		$res->add(new indexer_TermQuery('document_accessor', indexer_IndexService::PUBLIC_DOCUMENT_ACCESSOR_ID));
		if ($currentUser !== null)
		{
			$ps = f_permission_PermissionService::getInstance();
			foreach ($ps->getAccessorIdsByUser($currentUser) as $id)
			{
				$res->add(new indexer_TermQuery('document_accessor', $id));
			}
		}
		return $res;
	}

	public function setFirstHitOffset($offset)
	{
		$this->offset = $offset;
		return $this;
	}

	public function getFirstHitOffset()
	{
		return $this->offset;
	}

	public function setReturnedHitsCount($value)
	{
		$this->rows = $value;
		return $this;
	}

	public function getReturnedHitsCount()
	{
		return $this->rows;
	}

	public function setFieldsLimit($fArray)
	{
		$this->limitToFields = $fArray;
		return $this;
	}

	public function getFieldsLimit()
	{
		return $this->limitToFields;
	}

	public function setHighlighting($bool)
	{
		$this->highlight = $bool;
		return $this;
	}

	public function getHighlighting()
	{
		return $this->highlight;
	}

	public function setLang($lang)
	{
		$this->lang = $lang;
		return $this;
	}

	public function getLang()
	{
		return $this->lang;
	}
	
	public function setBoost($value)
	{
		if (!is_numeric($value) || $value < 0)
		{
			throw new IllegalArgumentException('$value must be a positive real number');
		}
		$this->boost = $value;
		return $this;
	}

	public function getBoost()
	{
		return $this->boost;
	}

	public function setClientId($value)
	{
		$this->clientId = $value;
	}
	
	/**
	 * @return String[]
	 */
	public function getTerms()
	{
		return array();
	}
}
