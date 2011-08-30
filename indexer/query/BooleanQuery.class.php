<?php
/**
 * @package framework.indexer.query
 */
class indexer_BooleanQuery extends indexer_QueryBase implements indexer_Query
{
	/**
	 * @var Array<indexer_Query>
	 */
	private $queries = array();

	/**
	 * @var String
	 */
	private $type = null;

	protected function __construct($type)
	{
		$this->type = $type;
	}

	/**
	 * Returns a new 'OR' boolean query instance
	 *
	 * @return indexer_BooleanQuery
	 */
	public static function orInstance()
	{
		return new indexer_BooleanQuery('OR');
	}

	/**
	 * Returns a new 'AND' boolean query instance
	 *
	 * @return indexer_BooleanQuery
	 */
	public static function andInstance()
	{
		return new indexer_BooleanQuery('AND');
	}
	
	/**
	 * @param String $opStr "AND" or "OR"
	 * @return indexer_BooleanQuery
	 */
	public static function byStringInstance($opStr)
	{
		switch (strtoupper($opStr))
		{
			case "OR": return self::orInstance();
			case "AND": return self::andInstance();
			default: throw new Exception("Unknown operator: ".$opStr);
		}
	}

	/**
	 * Returns a new 'NOT' boolean query instance
	 *
	 * @return indexer_BooleanQuery
	 */
	public static function notInstance()
	{
		return new indexer_BooleanQuery('NOT');
	}

	/**
	 * Add a clause to the boolean query
	 *
	 * @param Mixed $query
	 */
	public function add($query)
	{
		$this->queries[] = $query;
	}

	public function getSubqueries()
	{
		return $this->queries;
	}

	/**
	 * Returns the number of subqueries added.
	 *
	 * @return Integer
	 */
	public function getSubqueryCount()
	{
		return count($this->queries);
	}
	
	/**
	 * @return String[]
	 */
	public function getTerms()
	{
		$terms = array();
		foreach ($this->queries as $query)
		{
			$terms = array_merge($terms, $query->getTerms());
		}
		return array_unique($terms);
	}
	
	/**
	 * @return Boolean
	 */
	public function isEmpty()
	{
		return $this->getSubqueryCount() == 0;
	}
	
	/**
	 * @return string 
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return String
	 */
	public function toSolrString()
	{
		$elems = array();
		foreach ($this->queries as $query)
		{
			if (is_string($query))
			{
				$elems[] = $query;
			}
			elseif (f_util_ClassUtils::methodExists($query, "toSolrString"))
			{
				$elems[] = $query->toSolrString();
			}
		}
		if (count($elems) == 0)
		{
			throw new IllegalArgumentException("Can not build Boolean Query containing no clauses");
		}

		$result = "(" . join('%20' . $this->type . '%20', $elems) .")";

		$boostValue = $this->getBoost();
		if (!is_null($boostValue))
		{
			$result .= "^$boostValue";
		}
		return $result;
	}

}
