<?php
/**
 * @package framework.indexer.query
 */
class indexer_TermQuery extends indexer_QueryBase implements indexer_Query
{
	/**
	 * @var String
	 */
	private $name = null;
	private $prefix = "";

	/**
	 * @var mixed
	 */
	private $value = null;

	public function __construct($name, $value)
	{
		if (is_null($name) || is_null($value))
		{
			throw new IllegalArgumentException('$name and $value must both be non-null');
		}
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * When set to true, the term is required.
	 *
	 * @param Boolean $bool
	 * @return indexer_TermQuery
	 */
	public function setIsRequired($bool=true)
	{
		if ($bool)
		{
			$this->prefix = "+";
		}
		else
		{
			$this->prefix = null;
		}
		return $this;
	}

	/**
	 * When set to true, the term is prohibited.
	 *
	 * @param Boolean $bool
	 * @return indexer_TermQuery
	 */
	public function setIsProhibited($bool=true)
	{
		if ($bool)
		{
			$this->prefix = "-";
		}
		else
		{
			$this->prefix = null;
		}
		return $this;
	}

	/**
	 * @return String
	 */
	public function toSolrString()
	{
		$lang = $this->getLang();
		
		// In case value contains whitespaces, we split it and expand the individual terms on the field.
		$valueArray = preg_split('/[\s]+/', trim($this->value));
		
		$fieldName = $this->prefix . $this->name;
		
		if (!is_null($lang))
		{
			$fieldName .= "_$lang";
		}
		$result = "$fieldName:" . join(" $fieldName:", $valueArray);
		
		$boostValue = $this->getBoost();
		if (!is_null($boostValue))
		{
			$result .= "^$boostValue";
		}
		return $result;
	}
}