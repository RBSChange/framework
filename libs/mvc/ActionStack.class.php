<?php
class change_ActionStack
{
	private $stack = array();

	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @param change_ActionStackEntry $actionInstance
	 */
	public function addEntry ($moduleName, $actionName, $actionInstance)
	{

		$actionEntry = new change_ActionStackEntry($moduleName, $actionName, $actionInstance);
		$this->stack[] = $actionEntry;
		return $actionEntry;
	}

	/**
	 * @param integer $index
	 * @return change_ActionStackEntry
	 */
	public function getEntry($index)
	{
		$retval = null;
		if ($index > -1 && $index < count($this->stack))
		{
			$retval = $this->stack[$index];
		}
		return $retval;
	}

	/**
	 * @return change_ActionStackEntry
	 */
	public function getFirstEntry ()
	{
		$count  = count($this->stack);
		$retval = null;
		if ($count > 0)
		{
			$retval = $this->stack[0];
		}
		return $retval;
	}

	/**
	 * @return change_ActionStackEntry
	 */
	public function getLastEntry ()
	{
		$count  = count($this->stack);
		$retval = null;
		if ($count > 0)
		{
			$retval = $this->stack[$count - 1];
		}
		return $retval;
	}

	/**
	 * @return integer
	 */
	public function getSize ()
	{
		return count($this->stack);
	}
}

class change_ActionStackEntry
{
	private $actionInstance = null;
	private $actionName	 = null;
	private $microtime	  = null;
	private $moduleName	 = null;
	private $presentation   = null;
	
	public function __construct ($moduleName, $actionName, $actionInstance)
	{
		
		$this->actionName	 = $actionName;
		$this->actionInstance = $actionInstance;
		$this->microtime	  = microtime();
		$this->moduleName	 = $moduleName;
		
	}
	
	public function getActionName ()
	{	
		return $this->actionName;
	}
	
	public function getActionInstance ()
	{
		return $this->actionInstance;
	}
	

	public function getMicrotime ()
	{	
		return $this->microtime;
	}
	
	public function getModuleName ()
	{
		return $this->moduleName;
	}
}