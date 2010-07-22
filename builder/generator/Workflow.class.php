<?php
/**
 * @package framework.builder.generator
 */
class generator_Workflow
{

	/**
	 * @var generator_PersistentModel
	 */
	private $model;

	private $startTask = null;
	private $parameters = array();

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
				case "start-task":
					$this->startTask = $value;
					break;
				default:
					break;
			}
		}

		foreach ($xmlElement->childNodes as $node)
		{
			if ($node->nodeName == 'parameter')
			{
				$nodeValue = $node->nodeValue;
				foreach($node->attributes as $attribute)
				{
					$name = $attribute->nodeName;
					$value = $attribute->nodeValue;
					switch ($name)
					{
						case "name":
							$this->parameters[$value] = $nodeValue;
							break;
						default:
							break;
					}
				}

			}
		}
	}

	public function getStartTask()
	{
		return $this->startTask;
	}

	public function getParameters()
	{
		return $this->parameters;
	}
}
