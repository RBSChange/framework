<?php
// Alternative spl_object_hash() implementation taken from
// http://php.net/manual/en/function.spl-object-hash.php#87422:
if (!function_exists('spl_object_hash')) {

	/**
	 * Returns the hash of the unique identifier for the object.
	 *
	 * @param object $object Object
	 * @author Rafael M. Salvioni
	 * @return string
	 */
	function spl_object_hash($object)
	{
		if (is_object($object))
		{
			ob_start();
			var_dump($object);
			$dump = ob_get_clean();
			if (preg_match('/^object\((\w+)\)\#(\d)+/', $dump, $match))
			{
				return md5($match[1] . $match[2]);
			}
		}
		trigger_error(__FUNCTION__ . "() expects parameter 1 to be object", E_USER_WARNING);
		return null;
	}
}

/**
 * WARNING: if you plan to use eaccelerator, please compile it with
 * "--with-eaccelerator-doc-comment-inclusion" and define EACCELERATOR_PRESERVE_DOC_COMMENT
 * for better performances.
 * See http://eaccelerator.net/ticket/229
 */
class f_mvc_DynBean implements f_mvc_Bean
{
	private $properties;
	private $wrappedObject;
	private $model;

	function __construct($object)
	{
		if ($object !== null)
		{
			$this->wrappedObject = $object;
		}
	}

	/**
	 * @return f_mvc_BeanModel
	 */
	function getBeanModel()
	{
		if ($this->model === null)
		{
			if ($this->wrappedObject === null)
			{
				throw new Exception("Could not generate beanModel");
			}
			$this->model = f_mvc_DynBeanModel::getInstance($this->wrappedObject);
		}
		return $this->model;
	}

	/**
	 * @param f_mvc_BeanModel $model
	 * @return void
	 */
	function setBeanModel($model)
	{
		$this->model = $model;
	}

	/**
	 * @param Mixed $id
	 * @return f_mvc_Bean
	 */
	static function getInstanceById($id)
	{
		throw new Exception(__METHOD__." is not implemented");
	}

	/**
	 * @return f_mvc_Bean
	 */
	static function getNewInstance()
	{
		throw new Exception(__METHOD__." is not implemented");
	}

	/**
	 * @return Mixed
	 */
	function getBeanId()
	{
		if (f_util_ClassUtils::methodExists($this->wrappedObject, "getId"))
		{
			return $this->wrappedObject->getId();
		}
		elseif (f_util_ClassUtils::hasPublicProperty($this->wrappedObject, "id"))
		{
			return $this->wrappedObject->id;
		}
		return spl_object_hash($this->wrappedObject);
	}

	function getWrappedObject()
	{
		return $this->wrappedObject;
	}

	function __call($name, $arguments)
	{
		return call_user_func_array(array($this->wrappedObject, $name), $arguments);
	}
}

class f_mvc_DynBeanModel implements f_mvc_BeanModel
{
	/**
	 * @var array<String, BeanPropertyInfo>
	 */
	private $properties;
	/**
	 * @var Boolean
	 */
	private $canUpdate = true;
	
	/**
	 * @var String
	 */
	private $beanConstraints;

	/**
	 * @var array<String, f_mvc_DynBeanModel>
	 */
	private static $models;

	/**
	 * @param Object $object
	 * @return f_mvc_DynBeanModel
	 */
	static function getInstance($object)
	{
		if ($object === null)
		{
			throw new Exception("Object param can not be null. Use getNewInstance() instead.");
		}
		if (is_object($object))
		{
			if ($object instanceof ReflectionClass)
			{
				$key = $object->getName();	
			}
			else
			{
				$key = get_class($object);
			}
		}
		elseif (is_string($object))
		{
			$key = $object;
		}
		if (isset(self::$models[$key]))
		{
			return self::$models[$key];
		}
		// TODO: cache model (not just in memory ; using apc for instance) if not in developpement mode
		$model = new f_mvc_DynBeanModel($object, false);
		self::$models[$key] = $model;
		return $model;
	}

	/**
	 * @param mixed $object the object instance, ReflectionClass instance or class name
	 * @return f_mvc_DynBeanModel
	 */
	static function getNewInstance($object = null)
	{
		return new f_mvc_DynBeanModel($object);
	}

	/**
	 * @param Object|String $object
	 * @return f_mvc_DynBeanModel
	 */
	protected function __construct($object = null, $canUpdate = true)
	{
		if ($object === null)
		{
			return;
		}

		if ($object instanceof ReflectionClass)
		{
			$class = $object;
		}
		else
		{
			$class = new ReflectionClass($object);
		}
		
		$this->beanConstraints = f_util_ClassUtils::getMetaValue("constraints", $class);
		$properties = array();
		foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property)
		{
			if ($property->isStatic())
			{
				continue;
			}
			$properties[$property->getName()] = $property;
		}
		foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
		{
			$methodName = $method->getName();
			$matches = array();
			if (preg_match('/^(set|get)(.+)$/', $methodName, $matches))
			{
				$propName = strtolower($matches[2][0]).substr($matches[2], 1);
				if (!isset($properties[$propName]))
				{
					$properties[$propName] = 1;
				}
				else
				{
					$properties[$propName]++;
				}
			}
		}
			
		$this->properties = array();

		$matches = null;
		if (preg_match('/^(\w+)_(.*)$/', $class->getName(), $matches))
		{
			$localePrefix = 'm.'.$matches[1].'.document.'.strtolower($matches[2]);
		}
		else
		{
			$localePrefix = null;
		}
			
		foreach ($properties as $propName => $getSetCount)
		{
			$setterName = 'set'.ucfirst($propName);
			$setter = null;
			$matches = null;
			$property = null;
			$propertyInfo = null;
			
			if ($getSetCount instanceof ReflectionProperty)
			{
				$isPublic = true;
				$property = $getSetCount;
				// This one comes from a public property,
				// but setter has always priority when populate if it
				// exists and we then have to validate it.
				if ($class->hasMethod($setterName))
				{
					$setter = $class->getMethod($setterName);
					if ($setter->getNumberOfRequiredParameters() !== 1)
					{
						// invalid setter
						continue;
					}
				}
				// For public properties, the definition MUST be on the property, we do not care about the setter.
				preg_match("/\s*@var\s+(\S+)\s+/m", f_util_ClassUtils::getDocComment($property), $matches);
			}
			elseif ($getSetCount === 2)
			{
				$isPublic = false;
				$setter = $class->getMethod($setterName);
				if ($setter->getNumberOfRequiredParameters() !== 1)
				{
					// invalid setter
					continue;
				}

				$property = $class->hasProperty($propName) ? $class->getProperty($propName) : null;
				$params = $setter->getParameters();
				$firstParameter = $params[0];
				$propertyInfo = null;

				// try to determine the property type :
				// 1. setter parameter hinting
				if (($firstParameterClass = $firstParameter->getClass()) !== null)
				{
					$propertyInfo = $this->addClassProperty($propName, $firstParameterClass, $localePrefix);
				}
				else
				{
					// TODO: OK, we could do the next two lines ONLY if setter doc is not enough
					$getter = $class->getMethod('get'.ucfirst($propName));
					// 2. setter doc comment
					// or
					// 3. getter doc comment
					// or
					// 4. property doc comment
					// TODO: detect arrays
					if (preg_match("/\s*@param\s+(\S+)\s+\\$".$firstParameter->getName()."\s*/m", f_util_ClassUtils::getDocComment($setter), $matches)
					|| preg_match("/\s*@return\s+(\S+)\s+/m", f_util_ClassUtils::getDocComment($getter), $matches)
					|| ($property !== null && preg_match("/\s*@var\s+(\S+)\s+/m", f_util_ClassUtils::getDocComment($property), $matches)))
					{
						// nothing, just to have a $matches
					}
				}
			}

			if (isset($matches[1]))
			{
				list($paramType, $isArray, $paramClass) = $this->normalizeType($matches[1]);
				if ($paramClass !== null)
				{
					$propertyInfo = $this->addClassProperty($propName, $paramClass, $localePrefix);
				}
				elseif ($paramType !== null)
				{
					if ($paramType === BeanPropertyType::STRING)
					{
						switch(strtoupper(f_util_ClassUtils::getFieldMetaValue("type", $setter, $property)))
						{
							case "LONGSTRING":
								$paramType = BeanPropertyType::LONGSTRING;
								break;
							case "XHTMLFRAGMENT":
								$paramType = BeanPropertyType::XHTMLFRAGMENT;
								break;
						}
					}
					$propertyInfo = new BeanPropertyInfoImpl($propName, $paramType);
					if ($localePrefix !== null)
					{
						$propertyInfo->setLabelKey($localePrefix.".".$propName);
					}
					$this->addProperty($propertyInfo);
				}

				if ($propertyInfo !== null)
				{
					if ($isArray)
					{
						$propertyInfo->setCardinality(-1);
					}
					$propertyInfo->setIsPublic($isPublic);
					if ($setter !== null)
					{
						$propertyInfo->setSetterName($setter->getName());
					}
					$constraints = f_util_ClassUtils::getFieldMetaValue("constraints", $setter, $property);
					if ($constraints !== null)
					{
						$propertyInfo->setValidationRules($propName."{".$constraints."}");
					}

					$changeListId = f_util_ClassUtils::getFieldMetaValue("listId", $setter, $property);
					if ($changeListId !== null)
					{
						$propertyInfo->setListId($changeListId);
					}

					if (($setter !== null && f_util_ClassUtils::hasMeta("required", $setter)) || ($property !== null && f_util_ClassUtils::hasMeta("required", $property)))
					{
						$propertyInfo->setIsRequired(true);
					}
					
					if (($setter !== null && f_util_ClassUtils::hasMeta("requiredIf", $setter)) || ($property !== null && f_util_ClassUtils::hasMeta("requiredIf", $property)))
					{
						$this->addBeanConstraint("requiredIf:".$propName.",".f_util_ClassUtils::getFieldMetaValue("requiredIf", $setter, $property));
					}
				}
			}
		}
		$this->canUpdate = $canUpdate;
	}

	/**
	 * @param string $propName
	 * @param ReflectionClass $class
	 * @return BeanPropertyInfoImpl
	 */
	private function addClassProperty($propName, $class, $localePrefix = null)
	{
		$propertyInfo = null;
		if ($class->isSubclassOf("f_persistentdocument_PersistentDocument"))
		{
			$propertyInfo = new BeanPropertyInfoImpl($propName, BeanPropertyType::DOCUMENT, $class->getName());
		}
		elseif ($class->implementsInterface("f_mvc_Bean"))
		{
			$propertyInfo = new BeanPropertyInfoImpl($propName, BeanPropertyType::BEAN, $class->getName());
		}
		elseif ($class->isInstantiable())
		{
			$propertyInfo = new BeanPropertyInfoImpl($propName, BeanPropertyType::CLASS_TYPE, $class->getName());
		}
		if ($propertyInfo !== null)
		{
			if ($localePrefix !== null)
			{
				$propertyInfo->setLabelKey($localePrefix.".".$propName);
				$propertyInfo->setHelpKey($localePrefix.".".$propName.'-help');
			}
			$this->addProperty($propertyInfo);
		}
		return $propertyInfo;
	}

	/**
	 * @param string $docType
	 * @return mixed[] BeanPropertyType, ReflectionClass
	 */
	private function normalizeType($docType)
	{
		if (f_util_StringUtils::endsWith($docType, "[]"))
		{
			$isArray = true;
			$docType = substr($docType, 0, -2);
		}
		else
		{
			$isArray = false;
		}

		switch (strtolower($docType))
		{
			case "integer":
			case "int":
				return array(BeanPropertyType::INTEGER, $isArray, null);
			case "float":
			case "double":
				return array(BeanPropertyType::DOUBLE, $isArray, null);
			case "boolean":
				return array(BeanPropertyType::BOOLEAN, $isArray, null);
			case "string":
				return array(BeanPropertyType::STRING, $isArray, null);
			case "date_datetime":
				return array(BeanPropertyType::DATETIME, $isArray, null);
			case "date_date":
				return array(BeanPropertyType::DATE, $isArray, null);
			default:
				if (f_util_ClassUtils::classExists($docType))
				{
					return array(BeanPropertyType::CLASS_TYPE, $isArray, new ReflectionClass($docType));
				}
				return array(null, null, null);
		}
	}

	/**
	 * @return array<String, BeanPropertyInfo> ie. <propName, BeanpropertyInfo>
	 */
	function getBeanPropertiesInfos()
	{
		return $this->properties;
	}

	/**
	 * @param string $propertyName
	 * @return BeanPropertyInfo
	 * @throws Exception if property $propertyName does not exists
	 */
	function getBeanPropertyInfo($propertyName)
	{
		return $this->properties[$propertyName];
	}
	
	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	function hasBeanProperty($propertyName)
	{
		return isset($this->properties[$propertyName]);
	}

	/**
	 * Used as getter/setter prefix in the context of BeanAggregateModel
	 * @see BeanAggregateModel
	 * @return string
	 */
	function getBeanName()
	{
		return null;
	}
	
	/**
	 * @return string
	 */
	function getBeanConstraints()
	{
		return $this->beanConstraints;
	}
	
	/**
	 * @param string $beanConstraints
	 */
	function setBeanConstraints($beanConstraints)
	{
		$this->beanConstraints = $beanConstraints;
	}
	
	/**
	 * @param string $beanConstraint
	 */
	function addBeanConstraint($beanConstraint)
	{
		if ($this->beanConstraints === null)
		{
			$this->beanConstraints = $beanConstraint;
		}
		else
		{
			$this->beanConstraints .= ";".$beanConstraint;
		}
	}

	/**
	 * @param BeanPropertyInfo $property
	 * @return void
	 */
	function addProperty($property)
	{
		//echo "ADD PROPERTY ".$property->getName()."<br>";
		if (!$this->canUpdate)
		{
			throw new Exception("This ".__CLASS__." instance is readonly");
		}
		$this->properties[$property->getName()] = $property;
	}

	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use hasBeanProperty
	 */
	function hasProperty($propertyName)
	{
		return $this->hasBeanProperty($propertyName);
	}
}