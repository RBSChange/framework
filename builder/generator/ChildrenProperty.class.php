<?php
/**
 * @package framework.builder.generator
 */
class generator_ChildrenProperty
{
	/**
	 * @var generator_PersistentModel
	 */
	private $model;

	private $name = null;
	private $type  = null;

	/**
	 * @var generator_ChildrenProperty
	 */
	private $parentProperty;

	/**
	 * @param generator_PersistentModel $model
	 */
	public function __construct($model)
	{
		$this->setModel($model);
	}

	/**
	 * @param generator_PersistentModel $model
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}

	/**
	 * @param DOMElement $xmlElement
	 */
	public function initialize($xmlElement)
	{
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;
			switch ($name)
			{
				case "name":
					$this->name = $value;
					break;
				case "type":
					$this->type = $value;
					break;
				default:
					generator_PersistentModel::addMessage("Obsolete Children Property attribute '$name' => $value ");
			}
		}
	}

	/**
	 * @param generator_ChildrenProperty $parentProperty
	 */
	public function setParentProperty($parentProperty)
	{
		$this->parentProperty = $parentProperty;
	}

	/**
	 * @return String
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param generator_ChildrenProperty $property
	 */
	public function override($property)
	{
		if (!is_null($property->type)) {$this->type = $property->type;}
	}

	/**
	 * @return String
	 */
	public function getPhpName()
	{
		return ucfirst($this->name);
	}

	/**
	 * @return String
	 */
	public function getCommentaryType()
	{
		if ($this->getType() == generator_PersistentModel::BASE_MODEL)
		{
			return generator_PersistentModel::BASE_CLASS_NAME;
		}
		
		list ($package, $docName) = explode('/', $this->getType());
		list (, $packageName) = explode('_', $package);
		return "" . $packageName . "_persistentdocument_" . $docName;
	}
}