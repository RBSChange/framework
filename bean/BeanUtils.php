<?php
class BeanUtils
{
	/**
	 * @param Object $bean
	 * @return f_mvc_BeanModel
	 */
	static function getBeanModel($bean)
	{
		return self::getBean($bean)->getBeanModel();
	}

	/**
	 * @param f_mvc_Bean $bean
	 * @return array<String, mixed>
	 */
	static function getProperties($bean)
	{
		$data = array();
		foreach ($bean->getBeanModel()->getBeanPropertiesInfos() as $propName => $propInfo)
		{
			$data[$propName] = self::getProperty($bean, $propName);
		}
		return $data;
	}

	/**
	 * @param Object $bean
	 * @param String $expectedClassName
	 * @return void
	 * @throws Exception if $bean is not an instance of expected class
	 */
	static function assertInstanceOf($bean, $expectedClassName)
	{
		if ($bean instanceof f_mvc_DynBean)
		{
			$wrappedObject = $bean->getWrappedObject();
			if (!$wrappedObject instanceof $expectedClassName)
			{
				throw new Exception("Object '$beanName' is not an instance of $expectedClassName but ".get_class($wrappedObject));
			}
		}
		elseif (!$bean instanceof $expectedClassName)
		{
			throw new Exception("Object '$beanName' is not an instance of $expectedClassName but ".get_class($bean));
		}
	}

	/**
	 * @param f_mvc_Bean $bean
	 */
	static function getSerializableProperties($bean)
	{
		$data = array();
		$class = new ReflectionClass($bean);
		foreach ($bean->getBeanModel()->getBeanPropertiesInfos() as $propName => $propInfo)
		{
			$value  = self::getProperty($bean, $propName, $class);
			$converter = $propInfo->getConverter();
			if ($converter !== null)
			{
				$value = $propInfo->getConverter()->convertFromBeanToRequestValue($value);
			}
			$data[$propName] = $value;
		}
		return $data;
	}

	/**
	 * @param ReflectionClass $reflectionClass
	 * @return Object
	 */
	static function getNewBeanInstance($reflectionClass)
	{
		if (!$reflectionClass->implementsInterface('f_mvc_Bean'))
		{
			if ($reflectionClass->isInstantiable())
			{
				return new f_mvc_DynBean($reflectionClass->newInstance());
			}
			else
			{
				throw new Exception("Can not instantiate ".$reflectionClass->getName());
			}
		}
		return $reflectionClass->getMethod('getNewInstance')->invoke(null);
	}

	static function getClassName($bean)
	{
		if ($bean instanceof f_mvc_DynBean)
		{
			return get_class($bean->getWrappedObject());
		}
		return get_class($bean);
	}

	/**
	 * @param ReflectionClass $reflectionClass
	 * @param Integer $documentId
	 * @return Object
	 */
	static function getBeanInstance($reflectionClass, $beanId)
	{
		if ($beanId <= 0)
		{
			return self::getNewBeanInstance($reflectionClass);
		}

		$bean = $reflectionClass->getMethod('getInstanceById')->invoke(null, $beanId);
		if ($reflectionClass->isInstance($bean))
		{
			if (!$reflectionClass->implementsInterface('f_mvc_Bean'))
			{
				return new f_mvc_DynBean($bean);
			}
			return $bean;
		}
		throw new Exception('beanInstance class is not the same as bean class: expected '.$reflectionClass->getName().', got '.get_class($bean));
	}

	/**
	 * Populate a bean with an array of
	 * @param stdClass $bean
	 * @param array<String,mixed> $properties
	 * @param array<String> $include
	 * @param array<String> $exclude
	 * @return array<String,mixed> of invalide property value;
	 */
	static function populate($bean, $properties, $include = null, $exclude = null)
	{
		$invalidProperties = array();

		if ($bean instanceof f_mvc_DynBean)
		{
			$target = $bean->getWrappedObject();
			$class = new ReflectionClass($target);
		}
		else
		{
			$class = new ReflectionClass($bean);
			$target = $bean;
		}
		$beanModel = self::getBeanModel($bean);
		$nestedProperties = array();
		foreach ($properties as $name => $value)
		{
			if ($include !== null && !in_array($name, $include))
			{
				continue;
			}
			if ($exclude !== null && in_array($name, $exclude))
			{
				continue;
			}

			if (($index = strpos($name, ".")) !== false)
			{
				$subBeanName = substr($name, 0, $index);
				if (!$beanModel->hasBeanProperty($subBeanName))
				{
					continue;
				}
				if (!isset($nestedProperties[$subBeanName]))
				{
					$nestedProperties[$subBeanName] = array();
				}
				$nestedProperties[$subBeanName][substr($name, $index+1)] = $value;
			}
			else
			{
				if (!self::setDirectProperty($bean, $name, $value, $class))
				{
					$invalidProperties[$name] = $value;
				}
			}
		}
		if (count($nestedProperties) > 0)
		{
			foreach ($nestedProperties as $subBeanName => $subBeanProperties)
			{
				$subBean = self::getDirectProperty($bean, $subBeanName);
				if ($subBean === null)
				{
					$subBeanProperty = $beanModel->getBeanPropertyInfo($subBeanName);
					$subBeanClass = new ReflectionClass($subBeanProperty->getClassName());
					if (isset($subBeanProperties["id"]))
					{
						$subBean = self::getBeanInstance($subBeanClass, $subBeanProperties["id"]);
						unset($subBeanProperties["id"]);
					}
					else
					{
						$subBean = self::getNewBeanInstance($subBeanClass);
					}
					$subSetterName = $subBeanProperty->getSetterName();
					if ($subSetterName !== null)
					{
						$target->{$subSetterName}($subBean);
					}
					else
					{
						$target->{$subBeanName} = $subBean;
					}
				}
				$subInvalidProperties = self::populate($subBean, $subBeanProperties);
				foreach ($subInvalidProperties as $key => $value)
				{
					$invalidProperties[$subBeanName.'.'.$key] = $value;
				}
			}
		}
		return $invalidProperties;
	}
	
	/**
	 * @param String|Object $beanClassNameOrObject
	 * @param String $propertyName
	 * @return BeanPropertyInfo
	 */
	static function getBeanPropertyInfo($beanClassNameOrObject, $propertyName)
	{
		if (is_object($beanClassNameOrObject))
		{
			$beanInstance = self::getBean($beanClassNameOrObject);
		}
		else
		{
			$reflectionClass = new ReflectionClass($beanClassNameOrObject);
			$beanInstance = self::getNewBeanInstance($reflectionClass);
		}
		list($finalBean, $propName) = self::resolveModel($beanInstance, $propertyName);
		return $finalBean->getBeanModel()->getBeanPropertyInfo($propName);
	}

	/**
	 * @param f_mvc_Bean $beanInstance
	 * @param String $propertyName
	 */
	static function hasProperty($beanInstance, $propertyName)
	{
		list($finalBean, $propName) = self::resolveModel($beanInstance, $propertyName);
		if ($finalBean === null)
		{
			return false;
		}
		return $finalBean->getBeanModel()->hasBeanProperty($propName);
	}

	/**
	 * @param f_mvc_Bean $beanInstance
	 * @param String $propertyName
	 * @return array : f_mvc_Bean, propertyName
	 */
	private static function resolveModel($beanInstance, $propertyName)
	{
		$propInfo = explode(".", $propertyName);
		$propInfoCount = count($propInfo);
		if ($propInfoCount > 1)
		{
			$model = $beanInstance->getBeanModel();
			$subBean = $beanInstance;
			for ($i = 0; $i < $propInfoCount-1; $i++)
			{
				$propName = $propInfo[$i];
				if (!$model->hasBeanProperty($propName))
				{
					return null;
				}
				$subBean = self::getDirectProperty($subBean, $propName);
				if ($subBean === null)
				{
					// TODO: miss a way to obtain a model without any instance !!
					$propertyInfo = $model->getBeanPropertyInfo($propName);
					$subBean = self::getNewBeanInstance(new ReflectionClass($propertyInfo->getClassName()));
					$model = $subBean->getBeanModel();
				}
				else
				{
					$subBean = self::getBean($subBean);
					$model = self::getBeanModel($subBean);
				}
			}
			return array($subBean, $propInfo[$i]);
		}
		else
		{
			return array($beanInstance, $propertyName);
		}
	}

	/**
	 * @param f_mvc_Bean $beanInstance
	 * @param String $propertyName
	 * @return BeanPropertyInfo
	 */
	static function getPropertyInfo($beanInstance, $propertyName)
	{
		list($finalBean, $propName) = self::resolveModel($beanInstance, $propertyName);
		if ($finalBean === null)
		{
			throw new Exception("object has no property $propertyName");
		}
		return $finalBean->getBeanModel()->getBeanPropertyInfo($propName);
	}

	/**
	 * @param String $beanClassName
	 * @param String $propertyName
	 * @return String
	 */
	static function getBeanPropertyValidationRules($beanClassName, $propertyName)
	{
		return self::getBeanPropertyInfo($beanClassName, $propertyName)->getValidationRules();
	}

	/**
	 * @param String|Object $beanClassNameOrObject
	 * @param String[] $include
	 * @param String[] $exclude
	 * @return String[]
	 */
	static function getBeanValidationRules($beanClassNameOrObject, $include = null, $exclude = null)
	{
		return self::_getBeanValidationRules($beanClassNameOrObject, $include, $exclude);
	}

	/**
	 * Be sure the object you manage is a bean. Is it is not,
	 * a f_mvc_DynBean is generated using object.
	 * @param Object $object
	 * @return f_mvc_Bean
	 */
	static function getBean($object)
	{
		if ($object instanceof f_mvc_Bean)
		{
			return $object;
		}
		return new f_mvc_DynBean($object);
	}

	/**
	 * @param String|Object $beanClassNameOrObject
	 * @param String[] $include
	 * @param String[] $exclude
	 * @return String[]
	 */
	private static function _getBeanValidationRules($beanClassNameOrObject, $include = null, $exclude = null, $suffix = null)
	{
		if (is_object($beanClassNameOrObject))
		{
			$beanInstance = self::getBean($beanClassNameOrObject);
		}
		elseif (is_string($beanClassNameOrObject))
		{
			$beanInstance = self::getNewBeanInstance(new ReflectionClass($beanClassNameOrObject));
		}
		else
		{
			throw new InvalidArgumentException("Expected argument object or string");
		}
		$beanModel = $beanInstance->getBeanModel();
		$beanRules = $beanModel->getBeanConstraints();
		$rules = array();
		if ($beanRules != null)
		{
			foreach (explode(";", $beanRules) as $beanRule)
			{
				$rules[] = $beanRule;
			}
		}
		foreach ($beanModel->getBeanPropertiesInfos() as $propertyName => $beanPropertyInfo)
		{
			if ($beanPropertyInfo->isHidden())
			{
				continue;
			}
			if ($include !== null && !in_array($propertyName, $include))
			{
				continue;
			}
			if ($exclude !== null && in_array($propertyName, $exclude))
			{
				continue;
			}
			$rule = self::getBeanPropertyInfo($beanInstance, $propertyName)->getValidationRules();
			if (!f_util_StringUtils::isEmpty($rule))
			{
				$rules[] = $suffix.$rule;
			}
		}
		return $rules;
	}

	/**
	 * @param String $beanClassName
	 * @param String[] $include
	 * @param String[] $exclude
	 * @return String[]
	 * @example BeanUtils::getSubBeanValidationRules("mymodule_persistentdocument_mybean", "aDocumentPropertyName", array("label", "aSubBeanPropertyName")));
	 *
	 */
	static function getSubBeanValidationRules($beanClassName, $subBeanName, $include = null, $exclude = null)
	{
		$propertyPath = explode('.', $subBeanName);
		$tempBeanClassName = $beanClassName;
		foreach ($propertyPath as $property)
		{
			$beanInstance = self::getNewBeanInstance(new ReflectionClass($tempBeanClassName));
			$beanModel = $beanInstance->getBeanModel();
			if (!$beanModel->hasBeanProperty($property))
			{
				throw new Exception("Bean $tempBeanClassName has no property named $property");
			}
			$subBeanPropertyInfo = $beanModel->getBeanPropertyInfo($property);
			$tempBeanClassName = $subBeanPropertyInfo->getClassName();
		}
		$rules = self::_getBeanValidationRules($subBeanPropertyInfo->getClassName(), $include, $exclude, $subBeanName.".");
		return $rules;
	}

	/**
	 * @param stdClass $bean
	 * @param String $name
	 * @param ReflectionClass $class deprecated parameter
	 * @return Mixed
	 */
	static function getProperty($bean, $name, ReflectionClass $class = null)
	{
		list($finalBean, $propName) = self::resolveModel($bean, $name);
		return self::getDirectProperty($finalBean, $propName);
	}

	/**
	 * @param stdClass $bean
	 * @param String $name
	 * @param ReflectionClass $class
	 * @return Mixed
	 */
	private static function getDirectProperty($bean, $name, ReflectionClass $class = null)
	{
		$model = self::getBeanModel($bean);
		if ($model->hasBeanProperty($name))
		{
			$beanPropertyInfo = $model->getBeanPropertyInfo($name);
			$getterName = $beanPropertyInfo->getGetterName();
		}
		else
		{
			$getterName = 'get' . ucfirst($name);
		}

		$target = null;
		if ($class === null)
		{
			if ($bean instanceof f_mvc_DynBean)
			{
				$target = $bean->getWrappedObject();
				$class = new ReflectionClass($target);
			}
			else
			{
				$class = new ReflectionClass($bean);
			}
		}

		if ($getterName === null)
		{
			return $target->{$name};
		}
		else
		{
			if (!$class->hasMethod($getterName))
			{
				throw new Exception('bean of type ' . $class->getName() . ' has no method named ' . $getterName);
			}

			$getter = $class->getMethod($getterName);
			if (!$getter->isPublic())
			{
				throw new Exception('method ' . $getterName . ' of class ' . $class->getName() . ' is not public');
			}
			if ($target !== null)
			{
				return $getter->invoke($target);
			}
			return $getter->invoke($bean);
		}
	}

	/**
	 * @param stdClass $bean
	 * @param String $name
	 * @param mixed $value empty strings are considered as null
	 * @param ReflectionClass $class
	 * @return Boolean
	 */
	static function setProperty($bean, $name, $value, ReflectionClass $class = null)
	{
		list($finalBean, $propName) = self::resolveModel($bean, $name);
		return self::setDirectProperty($finalBean, $propName, $value);
	}

	/**
	 * @param stdClass $bean
	 * @param String $name
	 * @param mixed $value empty strings are considered as null or empty array, depending on the type of the property
	 * @param ReflectionClass $class
	 * @return Boolean
	 */
	private static function setDirectProperty($bean, $name, $value, ReflectionClass $class = null)
	{
		$model = self::getBeanModel($bean);
		if ($model->hasBeanProperty($name))
		{
			$beanPropertyInfo = $model->getBeanPropertyInfo($name);
			$converter = $beanPropertyInfo->getConverter();
			if ($converter !== null)
			{
				if (is_string($value) && f_util_StringUtils::isEmpty($value))
				{
					$value = ($beanPropertyInfo->getMaxOccurs() != 1) ? array() : null;
				}
				elseif ($converter->isValidRequestValue($value))
				{
					$value = $converter->convertFromRequestToBeanValue($value);
				}
				else
				{
					return false;
				}
			}
			$setterName = $beanPropertyInfo->getSetterName();
		}
		else
		{
			$setterName = 'set' . ucfirst($name);
		}

		if ($bean instanceof f_mvc_DynBean)
		{
			$setterTarget = $bean->getWrappedObject();
		}
		else
		{
			$setterTarget = $bean;
		}

		if ($setterName === null)
		{
			$setterTarget->{$name} = $value;
		}
		else
		{
			if ($class === null)
			{
				$class = new ReflectionClass($setterTarget);
			}
			if ($class->hasMethod($setterName))
			{
				$setter = $class->getMethod($setterName);
				if ($setter->isPublic())
				{
					$setter->invoke($setterTarget, $value);
				}
			}
		}
		return true;
	}

	/**
	 * @param ReflectionMethod $method
	 * @param Integer $parameterIndex the parameter index, starting with 0
	 * @param String $beanName
	 * @return ReflectionClas|null
	 */
	static function getBeanClass(ReflectionMethod $method, $parameterIndex, &$beanName)
	{
		if ($parameterIndex < $method->getNumberOfParameters())
		{
			$parameters = $method->getParameters();
			$parameter = $parameters[$parameterIndex];
			$beanClass = $parameter->getClass();
			if ($beanClass !== null)
			{
				$beanName = $parameter->getName();
				return $beanClass;
			}
		}
		return null;
	}

	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use getBeanPropertyInfo
	 */
	static function getBeanProperyInfo($beanClassNameOrObject, $propertyName)
	{
		return self::getBeanPropertyInfo($beanClassNameOrObject, $propertyName);
	}
}