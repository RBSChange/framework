<?php
class indexer_FacetCount
{
	private $value;
	private $label;
	private $count;

	function __construct($value, $count)
	{
		$this->value = $value;
		$this->count = $count;
	}

	/**
	 * @return string
	 */
	function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param string $value
	 */
	function setValue($value)
	{
		$this->value = $value;
	}
	
	/**
	 * @return string
	 */
	function getLabel()
	{
		if ($this->label === null)
		{
			return $this->getValueUcFirst();
		}
		return $this->label;
	}
	
	/**
	 * @param string $label
	 */
	function setLabel($label)
	{
		return $this->label = $label;
	}

	/**
	 * @return string
	 */
	function getValueUcFirst()
	{
		return f_util_StringUtils::ucfirst($this->value);
	}

	/**
	 * @return unknown
	 */
	function getCount()
	{
		return $this->count;
	}

	function isEmpty()
	{
		return $this->count == 0;
	}
	
	function isNotEmpty()
	{
		return !$this->isEmpty();
	}
}

class indexer_RangeFacetCount extends indexer_FacetCount 
{
	/**
	 * @var String
	 */
	private $min, $max;
	
	function __construct($value, $count)
	{
		$matches = null;
		if (preg_match('/^\[([^ ]*) TO ([^ ]*)\]$/', $value, $matches))
		{
			if ($matches[1] != "*")
			{
				$this->min = $matches[1];
			}
			if ($matches[2] != "*")
			{
				$this->max = $matches[2];
			}
		}
		
		if ($this->min === null)
		{
			$substitution = array("max" => $this->max);
			$this->setLabel(LocaleService::getInstance()->trans('f.indexer.to-rangefacet', array('ucf'), $substitution));
		}
		elseif ($this->max === null)
		{
			$substitution = array("min" => $this->min);
			$this->setLabel(LocaleService::getInstance()->trans('f.indexer.from-rangefacet', array('ucf'), $substitution));
		}
		else
		{
			$substitution = array("min" => $this->min, "max" => $this->max);
			$this->setLabel(LocaleService::getInstance()->trans('f.indexer.from-to-rangefacet', array('ucf'), $substitution));
		}
		parent::__construct($value, $count);
	}
	
	/**
	 * @return string
	 */
	function getMin()
	{
		return $this->min;
	}
	
	/**
	 * @return string
	 */
	function getMax()
	{
		return $this->max;
	}
}