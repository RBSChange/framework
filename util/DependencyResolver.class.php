<?php
class f_util_DependencyResolver
{
	private $dependencies;
	
	private $currentPath = array();
	
	private $solution = array();
	
	/**
	 * @param array $deps
	 */
	public function __construct($deps)
	{
		$this->dependencies = $deps;
		ksort($this->dependencies);
	}
	
	private function addDeps($id)
	{
		if (in_array($id, $this->solution))
		{
			return;
		}
		
		if (in_array($id, $this->currentPath))
		{
			throw new Exception("Circular dependency $id " . implode($this->currentPath));
		}
		
		$this->currentPath[] = $id;
		
		isset($this->dependencies[$id]) ? $deps = $this->dependencies[$id] : $deps = array();
		
		foreach ($deps as $dep)
		{
			if (in_array($dep, $this->solution))
			{
				continue;
			}
			$this->addDeps($dep);
		}
		$this->solution[] = $id;
		array_pop($this->currentPath);
	}
	
	/**
	 * @return Array
	 */
	function solve()
	{
		foreach (array_keys($this->dependencies) as $id)
		{
			$this->addDeps($id);
		}
		return $this->solution;
	}
}