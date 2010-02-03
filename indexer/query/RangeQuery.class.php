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

	public function __construct($field, $min = null, $max = null)
	{
		if (is_null($max) && is_null($min))
		{
			throw new IllegalArgumentException('$min and $max arguments can not be both null');
		}
		$this->field = $field;
		if ($min instanceof date_Calendar )
		{
			$this->min = date_DateFormat::format($min, indexer_Field::SOLR_DATE_FORMAT);
		}
		else
		{
			$this->min = $min;
		}
		if ($max instanceof date_Calendar )
		{
			$this->max = date_DateFormat::format($max, indexer_Field::SOLR_DATE_FORMAT);
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
	 * @return String
	 */
	public function toSolrString()
	{
		return $this->field . ":[" . $this->wildCardIfNull($this->min) . ' TO ' . $this->wildCardIfNull($this->max) . "]";
	}

	/**
	 * Insert wildcard if needed for null min/max arguments
	 *
	 * @param mixed $val
	 * @return String
	 */
	private function wildCardIfNull($val)
	{
		return is_null($val) ? "*" : strval($val);
	}
}

