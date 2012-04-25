<?php
/**
 * ComponentTypeObject provides a simple way to learn more about a component
 * from its full component type.
 */
/**
 * @deprecated
 */
class ComponentTypeObject
{
	const MODULE    = "modules";
	const FRAMEWORK = "framework";
	
	/**
	 * @var String
	 * @deprecated (will become private in 4.0) use getter instead
	 */
	public $package;
	
	/**
	 * @var String
	 * @deprecated (will become private in 4.0) use getter instead
	 */
	public $packageType;
	
	/**
	 * @var String
	 * @deprecated (will become private in 4.0) use getter instead
	 */
	public $packageName;
	
	/**
	 * @var String
	 * @deprecated (will become private in 4.0) use getter instead
	 */
	public $componentType;
	
	/**
	 * @var String
	 * @deprecated (will become private in 4.0) use getter instead
	 */
	public $fullComponentType;
	
	private function __construct($component)
	{
		if ( is_numeric($component) )
		{
			$component = f_persistentdocument_PersistentProvider::getInstance()->getDocumentModelName($component);
		}
		else if ($component instanceof f_persistentdocument_PersistentDocument)
		{
			$component = $component->getDocumentModelName();
		}
		$this->fullComponentType = $component;
		
		$matches = array();
		if (preg_match('#^(.*)/([\w_\-]+)$#', $component, $matches)) {
			$this->componentType = $matches[2];
			$this->package       = $matches[1];
			$tt = explode("_", $this->package);

			if ($tt[0] == "framework")
			{
				$this->packageType = self::FRAMEWORK;
				$this->packageName = "framework";
			}
			else
			{
				$this->packageType = $tt[0];
				$this->packageName = $tt[1];
			}
		}
		else
		{
			$e = new InvalidComponentTypeException("invalid_component_type");
			$e->setAttribute('component', $component);
			throw $e;
		}
	}
	
	/**
	 * @param String $componentType
	 * @return ComponentTypeObject
	 */
	public static function getInstance($componentType)
	{
		return new ComponentTypeObject($componentType);
	}

	/**
     * @return String
     */
	public function getPackage()
	{
		return $this->package;
	}

	/**
     * @return String
     */
	public function getPackageType()
	{
		return $this->packageType;
	}

	/**
     * @return String
     */
	public function getPackageName()
	{
		return $this->packageName;
	}

	/**
     * @return String
     */
	public function getComponentType()
	{
		return $this->componentType;
	}

	/**
     * @return String
     */
	public function getFullComponentType()
	{
		return $this->fullComponentType;
	}
}