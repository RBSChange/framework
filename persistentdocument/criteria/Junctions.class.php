<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument.criteria
 */
class f_persistentdocument_criteria_Junction
{
	/** @var String	 */
	private $op;
	
	private $criterions = array();
	
	protected function __construct($op)
	{
		$this->op = $op;
	}
	
	/**
	 * @return String
	 */
	public function getOp()
	{
		return $this->op;
	}
	
	/**
	 * @param f_persistentdocument_criteria_Criterion $criterion
	 * @return f_persistentdocument_criteria_Junction
	 */
	public function add($criterion)
	{
		$this->criterions[] = $criterion;
		return $this;
	}
	
	/**
     * @return array
     */
    public function getCriterions()
    {
        return $this->criterions;
    }
}

class f_persistentdocument_criteria_Conjunction extends f_persistentdocument_criteria_Junction
{
	public function __construct()
	{
		parent::__construct('and');
	}
}

class f_persistentdocument_criteria_Disjunction extends f_persistentdocument_criteria_Junction
{
	public function __construct()
	{
		parent::__construct('or');
	}
}