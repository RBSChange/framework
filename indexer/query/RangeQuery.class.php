<?php
/**
 * @package framework.indexer.query
 */
class indexer_RangeQuery extends indexer_QueryBase implements indexer_Query
{
	/**
	 * @var mixed
	 */
	private $min = null;

	/**
	 * @var mixed
	 */
	private $max = null;

	/**
	 * @var String
	 */
	private $field = null;
	
	private $sepStart = "[";
	private $sepEnd = "]";
	
	/**
	 * @var Boolean
	 */
	private $prohibited = false;

	/**
	 * Construct a range, including min and max. Use <code>setExclusive()</code> to exclude min and max
	 * @param String $field
	 * @param String $min
	 * @param String $max
	 */
	public function __construct($field, $min = null, $max = null)
	{
		if (is_null($max) && is_null($min))
		{
			throw new IllegalArgumentException('$min and $max arguments can not be both null');
		}
		$this->field = $field;
		if ($min instanceof date_Calendar )
		{
			$this->min = date_Formatter::format($min, indexer_Field::SOLR_DATE_FORMAT);
		}
		else
		{
			$this->min = $min;
		}
		if ($max instanceof date_Calendar )
		{
			$this->max = date_Formatter::format($max, indexer_Field::SOLR_DATE_FORMAT);
		}
		else
		{
			$this->max = $max;
		}
	}

	/**
	 * Sets the range's maximum value to $value (inclusive)
	 *
	 * @param Mixed $value
	 * @return indexer_RangeQuery
	 */
	public function setMaxValue($value)
	{
		$this->max = $value;
		return $this;
	}

	/**
	 * Sets the range's minimum value to $value (inclusive)
	 *
	 * @param Mixed $value
	 * @return indexer_RangeQuery
	 */
	public function setMinValue($value)
	{
		$this->min = $value;
		return $this;
	}
	
	/**
	 * @return indexer_RangeQuery
	 */
	public function setExclusive()
	{
		$this->sepStart = "{";
		$this->sepEnd = "}";
		return $this;
	}
	
	/**
	 * @param Boolean $bool
	 */
	public function setIsProhibited($bool = true)
	{
		$this->prohibited = $bool;
	}

	/**
	 * @return String
	 */
	public function toSolrString()
	{
		$solrString = "";
		if ($this->prohibited)
		{
			$solrString .= "-";
		}
		$solrString .= $this->field . ":".$this->sepStart . $this->wildCardIfNull($this->min) . '%20TO%20' . $this->wildCardIfNull($this->max) . $this->sepEnd;
		return $solrString;
	}

	/**
	 * Insert wildcard if needed for null min/max arguments
	 *
	 * @param mixed $val
	 * @return String
	 */
	private function wildCardIfNull($val)
	{
		return $val === null ? "*" : strval($val);
	}
}