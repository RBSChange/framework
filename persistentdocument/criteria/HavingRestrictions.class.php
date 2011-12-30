<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument.criteria
 */
class HavingRestrictions
{
	private function __construct()
	{
		// empty
	}

	/**
	 * Apply a "between" constraint to the named property
	 * For example between("creationdate", "2007-01-31 00:00:00", "2007-02-28 00:00:00")
	 * @param String $propertyName
	 * @param Integer $min
	 * @param Integer $max
	 * @return f_persistentdocument_criteria_HavingBetweenExpression
	 */
	static function between($propertyName, $min, $max)
	{
		return new f_persistentdocument_criteria_HavingBetweenExpression($propertyName, $min, $max);
	}

	/**
	 * Apply an "equal" constraint to the named property
	 * @param String $propertyName
	 * @param mixed $value
	 * @param boolean $ignoreCase deprecated, use ieq($propertyName, $value) instead of eq($propertyName, $value, true)
	 * @return f_persistentdocument_criteria_HavingSimpleExpression
	 */
	static function eq($propertyName, $value)
	{
		return new f_persistentdocument_criteria_HavingSimpleExpression($propertyName, $value, '=');
	}

	/**
	 * Apply a "greater than or equal" constraint to the named property
	 * @param String $propertyName
	 * @param mixed $value
	 * @return f_persistentdocument_criteria_HavingSimpleExpression
	 */
	static function ge($propertyName, $value)
	{
		return new f_persistentdocument_criteria_HavingSimpleExpression($propertyName, $value, '>=');
	}

	/**
	 * Apply a "greater than" constraint to the named property
	 * @param String $propertyName
	 * @param mixed $value
	 * @return f_persistentdocument_criteria_HavingSimpleExpression
	 */
	static function gt($propertyName, $value)
	{
		return new f_persistentdocument_criteria_HavingSimpleExpression($propertyName, $value, '>');
	}

	/**
	 * Apply an "in" constraint to the named property
	 * @param String $propertyName
	 * @param array $value
	 * @return f_persistentdocument_criteria_HavingSimpleExpression
	 */
	static function in($propertyName, $values)
	{
		return new f_persistentdocument_criteria_HavingInExpression($propertyName, $values, false);
	}

	/**
	 * Apply an "notin" constraint to the named property
	 * @param String $propertyName
	 * @param array $value
	 * @return f_persistentdocument_criteria_HavingSimpleExpression
	 */
	static function notin($propertyName, $values)
	{
		return new f_persistentdocument_criteria_HavingInExpression($propertyName, $values, true);
	}

	/**
	 * Apply a "less than or equal" constraint to the named property
	 *
	 * @param String $propertyName
	 * @param mixed $value
	 * @return f_persistentdocument_criteria_HavingSimpleExpression
	 */
	static function le($propertyName, $value)
	{
		return new f_persistentdocument_criteria_HavingSimpleExpression($propertyName, $value, '<=');
	}

	/**
	 * Apply a "less than" constraint to the named property
	 *
	 * @param String $propertyName
	 * @param mixed $value
	 * @return f_persistentdocument_criteria_HavingSimpleExpression
	 */
	static function lt($propertyName, $value)
	{
		return new f_persistentdocument_criteria_HavingSimpleExpression($propertyName, $value, '<');
	}

	/**
	 * Apply a "not equal" constraint to the named property
	 *
	 * @param String $propertyName
	 * @param mixed $value
	 * @return f_persistentdocument_criteria_HavingSimpleExpression
	 */
	static function ne($propertyName, $value)
	{
		return new f_persistentdocument_criteria_HavingSimpleExpression($propertyName, $value, '!=');
	}
}

interface f_persistentdocument_criteria_HavingCriterion
{

}

class f_persistentdocument_criteria_HavingSimpleExpression implements f_persistentdocument_criteria_HavingCriterion
{
	/** @var String */
	private $propertyName;
	/** @var mixed */
	private $value;
	/** @var String */
	private $op;
	/** @var Boolean */
	private $ignoreCase;

	/**
	 * @param String $propertyName
	 * @param mixed $value
	 * @param String $op
	 * @param Boolean $ignoreCase
	 */
	public function __construct($propertyName, $value, $op, $ignoreCase = false)
	{
		$this->propertyName = $propertyName;
		$this->value = $value;
		$this->op = $op;
		$this->ignoreCase = $ignoreCase;
	}

	public function ignoreCase()
	{
		$this->ignoreCase = true;
	}

	/**
	 * @return String
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return String
	 */
	public function getOp()
	{
		return $this->op;
	}

	/**
	 * @return Boolean
	 */
	public function getIgnoreCase()
	{
		return $this->ignoreCase;
	}
}

class f_persistentdocument_criteria_HavingBetweenExpression implements f_persistentdocument_criteria_HavingCriterion
{
	/** @var String */
	private $propertyName;
	/** @var Integer */
	private $min;
	/** @var Integer */
	private $max;
	/** @var Boolean */
	private $strict = false;

	/**
	 * min <= value <= max
	 * @param String $String
	 * @param Integer $Integer
	 * @param Integer $Integer
	 */
	public function __construct($propertyName, $min, $max)
	{
		$this->propertyName = $propertyName;
		$this->min = $min;
		$this->max = $max;
	}

	/**
	 * min <= value < $max 
	 * @return f_persistentdocument_criteria_HavingBetweenExpression
	 */
	function setStrict()
	{
		$this->strict = true;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}

	/**
	 * @return Integer
	 */
	public function getMin()
	{
		return $this->min;
	}

	/**
	 * @return Integer
	 */
	public function getMax()
	{
		return $this->max;
	}
	
	/**
	 * @return Boolean
	 */
	public function isStrict()
	{
		return $this->strict;
	}
}

class f_persistentdocument_criteria_HavingInExpression implements f_persistentdocument_criteria_HavingCriterion
{
	private $propertyName;
	private $values;
	private $not;

	/**
	 * @param String $propertyName
	 * @param array $values
	 */
	public function __construct($propertyName, $values, $not = false)
	{
		$this->propertyName = $propertyName;
		$this->values = $values;
		$this->not = $not;
	}

	/**
	 * @return String
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}

	/**
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @return boolean
	 */
	public function getNot()
	{
		return $this->not;
	}
}