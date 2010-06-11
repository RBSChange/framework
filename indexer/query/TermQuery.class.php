<?php
/**
 * @package framework.indexer.query
 */
class indexer_TermQuery extends indexer_QueryBase implements indexer_Query
{
	/**
	 * @var String
	 */
	protected $value = "";

	/**
	 * @var Boolean
	 */
	protected $required = false;

	/**
	 * @var Boolean
	 */
	protected $prohibited = false;

	/**
	 * @var String
	 */
	protected $fieldName;
	
	/**
	 * @param String $fieldName
	 */
	function __construct($fieldName, $value = null)
	{
		$this->fieldName = $fieldName;
		if ($value !== null)
		{
			$this->add($value);
		}
	}

	/**
	 * @param $str
	 * @return indexer_TermQuery
	 */
	function add($str)
	{
		$this->value .= $str;
		return $this;
	}
	
	/**
	 * @param $str
	 * @return indexer_TermQuery
	 */
	function setValue($str)
	{
		$this->value = $str;
		return $this;
	}

	function required()
	{
		$this->required = true;
	}

	function prohibited()
	{
		$this->prohibited = true;
	}
	
	/**
	 * When set to true, the term is required.
	 *
	 * @param Boolean $bool
	 * @return indexer_TermQuery
	 */
	public function setIsRequired($bool = true)
	{
		$this->required = $bool;
	}
	
	/**
	 * When set to true, the term is prohibited.
	 *
	 * @param Boolean $bool
	 * @return indexer_TermQuery
	 */
	public function setIsProhibited($bool = true)
	{
		$this->prohibited = $bool;
	}

	/**
	 * @return Boolean
	 */
	function isEmpty()
	{
		return trim($this->value) == "";
	}

	/**
	 * @return String
	 */
	function __toString()
	{
		return $this->toSolrString();
	}

	/**
	 * @return String
	 */
	protected function toStringPrefix()
	{
		$prefix = "";
		if ($this->required)
		{
			$prefix .= "+";
		}
		if ($this->prohibited)
		{
			$prefix .= "-";
		}
		
		$lang = $this->getLang();
		return $prefix.$this->fieldName.(($lang !== null) ? "_".$lang : "").":";
	}

	/**
	 * @return String
	 */
	protected function toStringSuffix()
	{
		if ($this->boost != "")
		{
			return "^".$this->boost;
		}
		return "";
	}

	/**
	 * @return String
	 */
	public function toSolrString()
	{
		return urlencode($this->toStringPrefix().$this->escapeValue($this->value).$this->toStringSuffix());
	}
	
	protected function escapeValue($value)
	{
		return str_replace(array('+', '-', ':', '^', '"'), array('%2B', '%2D', '%3A', '%5E', '%22'), $value);
	}

	/**
	 * @return String
	 */	
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @return String[]
	 */
	public function getTerms()
	{
		return array($this->value);
	}
}