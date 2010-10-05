<?php
/**
 * @package framework.persistentdocument.criteria
 */
class Projections
{
	private function __construct()
	{
		// empty
	}
	
	/**
	 * Group by id + f_document properties
	 * @return unknown_type
	 */
	public static function this()
	{
		return new f_persistentdocument_criteria_ThisProjection();
	}
	
	public static function property($propertyName, $as = null)
	{
		return new f_persistentdocument_criteria_PropertyProjection($propertyName, $as, false);
	}
	
	public static function groupProperty($propertyName, $as = null)
	{
		return new f_persistentdocument_criteria_PropertyProjection($propertyName, $as, true);
	}
	
	public static function rowCount($as = null)
	{
		return new f_persistentdocument_criteria_RowCountProjection($as);
	}
	
	public static function min($propertyName, $as = null)
	{
		return new f_persistentdocument_criteria_OperationProjection($propertyName, $as, 'min');
	}
	
	public static function max($propertyName, $as = null)
	{
		return new f_persistentdocument_criteria_OperationProjection($propertyName, $as, 'max');
	}
	
	public static function avg($propertyName, $as = null)
	{
		return new f_persistentdocument_criteria_OperationProjection($propertyName, $as, 'avg');
	}
	
	public static function count($propertyName, $as = null)
	{
		return new f_persistentdocument_criteria_OperationProjection($propertyName, $as, 'count');
	}
	
	public static function sum($propertyName, $as = null)
	{
		return new f_persistentdocument_criteria_OperationProjection($propertyName, $as, 'sum');
	}
	
	public static function distinctCount($propertyName, $as = null)
	{
		return new f_persistentdocument_criteria_DistinctCountProjection($propertyName, $as);
	}	
}

interface f_persistentdocument_criteria_Projection
{
	
}

abstract class f_persistentdocument_criteria_ProjectionBase implements f_persistentdocument_criteria_Projection
{
	private $as;
	
	public function __construct($as)
	{
		$this->as = $as;
	}
	
	public function getAs()
	{
		return $this->as;
	}
}

class f_persistentdocument_criteria_OperationProjection extends f_persistentdocument_criteria_ProjectionBase
{
	private $propertyName;
	
	private $operation;
	
	public function __construct($propertyName, $as, $operation)
	{
		parent::__construct($as);
		$this->propertyName = $propertyName;
		$this->operation = $operation;
	}
	
	public function getAs()
	{
		$as = parent::getAs();
		return is_null($as) ? $this->operation . $this->propertyName : $as;
	}
	
	/**
	 * @return String
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}

	/**
	 * @return String
	 */
	public function getOperation()
	{
		return $this->operation;
	}
}

class f_persistentdocument_criteria_DistinctCountProjection extends f_persistentdocument_criteria_OperationProjection
{
	public function __construct($propertyName, $as)
	{
		parent::__construct($propertyName, $as, 'distinctcount');
	}
}

class f_persistentdocument_criteria_RowCountProjection extends f_persistentdocument_criteria_ProjectionBase
{
	public function getAs()
	{
		$as = parent::getAs();
		return is_null($as) ? 'rowcount' : $as;
	}
}

class f_persistentdocument_criteria_PropertyProjection extends f_persistentdocument_criteria_ProjectionBase
{
	private $propertyName;
	
	private $group;
	
	public function __construct($propertyName, $as, $group)
	{
		parent::__construct($as);
		$this->propertyName = $propertyName;
		$this->group = $group;
	}
	
	public function getAs()
	{
		$as = parent::getAs();
		return $as === null ? $this->propertyName : $as;
	}
	
	/**
	 * @return String
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}

	/**
	 * @return Boolean
	 */
	public function getGroup()
	{
		return $this->group;
	}
}

class f_persistentdocument_criteria_ThisProjection implements f_persistentdocument_criteria_Projection
{
	const NAME = "this";
}