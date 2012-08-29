<?php

class BeanAggregateModel implements f_mvc_BeanModel 
{
	private $beanModels = array();
	private $beanPropertiesInfos;
	
	public final function addBeanModel($name, $instance)
	{
		$this->beanModels[$name] = $instance;
	}
	
	/**
	 * @return array<String, BeanPropertyInfo> ie. <propName, BeanpropertyInfo> 
	 */
	function getBeanPropertiesInfos()
	{
		if ($this->beanPropertiesInfos === null)
		{
			$this->loadBeanProperties();
		}
		return $this->beanPropertiesInfos;
	}
	
	/**
	 * @param string $propertyName
	 * @return BeanPropertyInfo
	 */
	function getBeanPropertyInfo($propertyName)
	{
		if ($this->beanPropertiesInfos === null)
		{
			$this->loadBeanProperties();
		}
		
		if (!$this->hasBeanProperty($propertyName))
		{
			throw new Exception("Unknown property $propertyName");
		}
		return $this->beanPropertiesInfos[$propertyName];
	}
		
	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	function hasBeanProperty($propertyName)
	{
		if ($this->beanPropertiesInfos === null)
		{
			$this->loadBeanProperties();
		}
		return isset($this->beanPropertiesInfos[$propertyName]);
	}
	
	/**
	 * @see f_mvc_BeanModel::getBeanConstraints()
	 *
	 * @return string
	 */
	function getBeanConstraints()
	{
	}

	private $beanName;
	
	/**
	 * @return string
	 */
	function getBeanName()
	{
		if ($this->beanName === null)
		{
			$names = array();
			foreach ($this->beanModels as  $beanModelInstance)
			{
				$names[] = $beanModelInstance->getBeanName();
			}
			$this->beanName = implode($names);
		}
		return $this->beanName;
	}	
	
	private function loadBeanProperties()
	{
		$this->beanPropertiesInfos = array();
		foreach($this->beanModels as $modelName => $modelInstance)
		{
			foreach($modelInstance->getBeanPropertiesInfos() as $propName => $beanPropertiesInfos)
			{
				$this->beanPropertiesInfos[$modelName.ucfirst($propName)] =  new BeanAggregatePropertyInfo($modelName, $beanPropertiesInfos);
			}
		}		
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	function hasProperty($propertyName)
	{
		return $this->hasBeanProperty($propertyName);
	}
}