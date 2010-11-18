<?php
class indexer_Facet
{
	const METHOD_FC = "fc";
	const METHOD_ENUM = "enum";
	
	/**
	 * @var array
	 */
	private $ranges;

	/**
	 * @see http://wiki.apache.org/solr/SimpleFacetParameters
	 */
	public $field, $prefix, $sort = true, $limit = 100, $offet = 0, $mincount = 0, $missing = false, $method = self::METHOD_FC, $enum_cache_minDf = 0, $key, $ex;

	function __construct($field, $prefix = null)
	{
		$this->field = $field;
		$this->prefix = $prefix;
	}
	
	/**
	 * @param String $min
	 * @param String $max
	 */
	function addRange($min, $max)
	{
		if ($this->ranges === null)
		{
			$this->ranges = array();	
		}
		if ($min === null)
		{
			$min = "*";
		}
		if ($max === null)
		{
			$max = "*";
		}
		$this->ranges[] = array($min, $max);
	}
	
	function toSolrString()
	{
		$localParams = array();
		if ($this->ex !== null)
		{
			$localParams[] = "ex=".$this->ex;
		}
		if ($this->key !== null)
		{
			$localParams[] = " key=".$this->key;
		} 
		$solrStrPrefix = (count($localParams) > 0) ? "{!".join(" ", $localParams)."}" : "";
		if ($this->ranges === null)
		{
			$solrStr = "&facet.field=".$solrStrPrefix.$this->field;
			if (f_util_StringUtils::isNotEmpty($this->prefix))
			{
				$solrStr .= "&f.".$this->field.".facet.prefix=".urlencode($this->prefix);	
			}
			$solrStr .= "&f.".$this->field.".facet.limit=".$this->limit;
			$solrStr .= "&f.".$this->field.".facet.method=".$this->method;
		}
		else
		{
			$solrStr = "";
			foreach ($this->ranges as $range)
			{
				$solrStr .= "&facet.query=".$solrStrPrefix.$this->field.":[".$range[0]."%20TO%20".$range[1]."]";
			}
		}
		return $solrStr;
	}
}

class indexer_VolatileStringFacet extends indexer_Facet
{
	function __construct($field)
	{
		parent::__construct($field.indexer_Field::STRING_VOLATILE);
	}
}

class indexer_FloatFacet extends indexer_Facet
{
	function __construct($field)
	{
		parent::__construct($field.indexer_Field::FLOAT);
	}
}

class indexer_VolatileFloatFacet extends indexer_Facet
{
	function __construct($field)
	{
		parent::__construct($field.indexer_Field::FLOAT_VOLATILE);
	}
}

class indexer_IntegerFacet extends indexer_Facet
{
	function __construct($field)
	{
		parent::__construct($field.indexer_Field::INTEGER);
	}
}

class indexer_VolatileIntegerFacet extends indexer_Facet
{
	function __construct($field)
	{
		parent::__construct($field.indexer_Field::INTEGER_VOLATILE);
	}
}