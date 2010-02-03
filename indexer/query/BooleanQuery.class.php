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

	private function __construct($type)
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
	 * @return String
	 */
	public function toSolrString()
	{
		$elems = array();
		foreach ($this->queries as $query)
		{
			if($query instanceof indexer_Query)
			{
				$elems[] = $query->toSolrString();
			}
			else if (is_string($query))
			{
				$elems[] = $query;
			}
		}
		if (count($elems) == 0)
		{
			throw new IllegalArgumentException("Can not build Boolean Query containing no clauses");
		}

		$result = "(" . join(' ' . $this->type . ' ', $elems) .")";

		$boostValue = $this->getBoost();
		if (!is_null($boostValue))
		{
			$result .= "^$boostValue";
		}
		return $result;
	}

}