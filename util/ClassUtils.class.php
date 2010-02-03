<?php
abstract class f_util_ClassUtils
{
	/**
	 * @param String $fullMethodName
	 * @example getMethod('util_f_util_ClassUtils::getMethod')
	 * @return ReflectionMethod
	 */
	public static function getMethodByName($fullMethodName)
	{
		$methodParts = explode('::', $fullMethodName);
		if (count($methodParts) != 2)
		{
			throw new IllegalArgumentException("$fullMethodName is not a full static method name (className::methodName)");
		}
		return self::getMethod($methodParts[0], $methodParts[1]);
	}

	/**
	 * @param String $fullMethodName
	 * @param String $methodName
	 * @example getMethod('util_f_util_ClassUtils', 'getMethod')
	 * @return ReflectionMethod
	 */
	public static function getMethod($fullClassName, $methodName)
	{
		$classObj = new ReflectionClass($fullClassName);
		if (!$classObj->hasMethod($methodName))
		{
			return null;
		}
		return $classObj->getMethod($methodName);
	}

	/**
	 * @param String $fullMethodName
	 * @example callMethod('util_f_util_ClassUtils::getMethod', 'util_f_util_ClassUtils::callMethod') returns the ReflectionMethod util_f_util_ClassUtils::callMethod
	 * @param mixed $args
	 * @return mixed
	 */
	public static function callMethodByName($fullMethodName)
	{
		$method = self::getMethodByName($fullMethodName);
		if (is_null($method))
		{
			throw new IllegalArgumentException("Could not find $fullMethodName");
		}
		$args = func_get_args();
		return $method->invokeArgs(null, array_splice($args, 1));
	}

	/**
	 * @param String $fullMethodName
	 * @example callMethod('util_f_util_ClassUtils', 'getMethod', 'util_f_util_ClassUtils::callMethod') returns the ReflectionMethod util_f_util_ClassUtils::callMethod
	 * @param mixed $args
	 * @return mixed
	 */
	public static function callMethod($fullClassName, $methodName)
	{
		$method = self::getMethod($fullClassName, $methodName);
		if (is_null($method))
		{
			throw new IllegalArgumentException("Could not find $fullClassName::$methodName");
		}
		$args = func_get_args();
		return $method->invokeArgs(null, array_splice($args, 2));
	}

	/**
	 * @param String $fullMethodName
	 * @example callMethod('util_f_util_ClassUtils::getMethod', array('util_f_util_ClassUtils::callMethod')) returns the ReflectionMethod util_f_util_ClassUtils::callMethod
	 * @param array $args
	 * @return mixed
	 */
	public static function callMethodArgsByName($fullMethodName, $args = array())
	{
		$method = self::getMethodByName($fullMethodName);
		if (is_null($method))
		{
			throw new IllegalArgumentException("Could not find $fullMethodName");
		}
		return $method->invokeArgs(null, $args);
	}

	/**
	 * @param String $fullMethodName
	 * @example callMethod('util_f_util_ClassUtils', 'getMethod', array('util_f_util_ClassUtils::callMethod')) returns the ReflectionMethod util_f_util_ClassUtils::callMethod
	 * @param array $args
	 * @return mixed
	 */
	public static function callMethodArgs($fullClassName, $methodName, $args = array())
	{
		$method = self::getMethod($fullClassName, $methodName);
		if (is_null($method))
		{
			throw new IllegalArgumentException("Could not find $fullMethodName");
		}
		return $method->invokeArgs(null, $args);
	}

	/**
	 * @param Object $objectInstance
	 * @param String $methodName
	 * @example callMethodOn($myInstance, $myMethodName, $arg1, $arg2, $arg3 ...)
	 * @return mixed
	 */
	public static function callMethodOn($objectInstance, $methodName)
	{
		$classObj = new ReflectionObject($objectInstance);
		$args = func_get_args();
		return $method = $classObj->getMethod($methodName)->invokeArgs($objectInstance, array_splice($args, 2));
	}

	/**
	 * @param Object $objectInstance
	 * @param String $methodName
	 * @param array $args
	 * @example callMethodArgsOn($myInstance, $myMethodName, array($arg1, $arg2, $arg3 ...))
	 * @return mixed
	 */
	public static function callMethodArgsOn($objectInstance, $methodName, $args = array())
	{
		$classObj = new ReflectionObject($objectInstance);
		return $classObj->getMethod($methodName)->invokeArgs($objectInstance, $args);
	}

	/**
	 * @param String $className
	 * @example newInstance($className, $arg1, $arg2, $arg3 ...)
	 * @return mixed
	 */
	public static function newInstance($className)
	{
		$classObj = new ReflectionClass($className);
		$args = func_get_args();
		return $classObj->newInstanceArgs(array_slice($args, 1));
	}

	public static function newInstanceSandbox($className, $expectedClassName)
	{
		$classObj = new ReflectionClass($className);
		$expectedClass = new ReflectionClass($expectedClassName);
		if ($expectedClass->isInterface())
		{
			if (!$classObj->implementsInterface($expectedClass))
			{
				throw new Exception("$className does not implement $expectedClass");
			}
		}
		elseif (!$classObj->isSubclassOf($expectedClass))
		{
			throw new Exception("$className is not a subclass of $expectedClass");
		}

		$args = func_get_args();
		$constructorArgs = array_slice($args, 2);
		if (empty($constructorArgs))
		{
			return $classObj->newInstance();
		}
		return $classObj->newInstanceArgs($constructorArgs);
	}

	/**
	 * Indicates whether the class $className exists or not.
	 *
	 * This replaces the PHP built-in function class_exists() and it is strongly
	 * recommended to use f_util_ClassUtils::classExists() instead of class_exists().
	 *
	 * @param string $className
	 * @return boolean
	 */
	public static function classExists($className)
	{
		return ClassLoader::getInstance()->exists($className);
	}
	
	public static function classExistsNoLoad($className)
	{
		return ClassLoader::getInstance()->existsNoLoad($className);
	}

	/**
	 * Indicates whether the method $methodName exists in class $className.
	 *
	 * @param mixed $objectOrClassName Class name or instance.
	 * @param string $methodName
	 * @return boolean
	 *
	 * @throws IllegalArgumentException
	 */
	public static function methodExists($objectOrClassName, $methodName)
	{
		return method_exists($objectOrClassName, $methodName);
	}

	/**
	 * @param mixed $objectOrClassName Class name or instance.
	 * @param string $propertyName
	 * @return boolean
	 */
	public static function propertyExists($objectOrClassName, $propertyName)
	{
		return self::getReflectionClassFromInstanceOrClassName($objectOrClassName)->hasProperty($propertyName);
	}
	
	/**
	 * @param mixed $objectOrClassName Class name or instance.
	 * @param string $propertyName
	 * @return boolean
	 */
	public static function hasPublicProperty($objectOrClassName, $propertyName)
	{
		$class = self::getReflectionClassFromInstanceOrClassName($objectOrClassName);
		return $class->hasProperty($propertyName) && $class->getProperty($propertyName)->isPublic();
	}

	/**
	 * @param mixed $objectOrClassName
	 * @return array<ReflectionMethod>
	 */
	public static function getMethods($objectOrClassName)
	{
		return self::getReflectionClassFromInstanceOrClassName($objectOrClassName)->getMethods();
	}

	/**
	 * @param String $metaName
	 * @param ReflectionMethod|ReflectionProperty|ReflectionClass $methodOrPropertyOrClass
	 * @return Boolean
	 */
	public static function hasMeta($metaName, $methodOrPropertyOrClass)
	{
		$regexp = '/@'.$metaName.'($|\s|\()/m';
		return preg_match($regexp, $methodOrPropertyOrClass->getDocComment()) > 0;
	}

	/**
	 * @param String $metaName
	 * @param ReflectionMethod|ReflectionProperty|ReflectionClas $methodOrPropertyOrClass
	 * @return String
	 */
	public static function getMetaValue($metaName, $methodOrPropertyOrClass)
	{
		$regexp = '/@'.$metaName.'\(([^\)]*)\)/m';
		$matches = null;
		// TODO: handle multiple values, ie string indexed array. ex: '(name1="value1",name2=value2,name3={value3, value4})'
		//echo $methodOrProperty->getDocComment();
		if (preg_match($regexp, $methodOrPropertyOrClass->getDocComment(), $matches))
		{
			return $matches[1];
		}
		return null;
	}

	/**
	 * @param String $metaName
	 * @param ReflectionMethod $method
	 * @param ReflectionProperty $property
	 * @return String
	 */
	public static function getFieldMetaValue($metaName, $method, $property)
	{
		$regexp = '/@'.$metaName.'\(([^\)]*)\)/m';
		$matches = null;
		// TODO: handle multiple values, ie string indexed array. ex: '(name1="value1",name2=value2,name3={value3, value4})'
		//echo $methodOrProperty->getDocComment();
		if (($method !== null && preg_match($regexp, $method->getDocComment(), $matches)) ||
		($property !== null && preg_match($regexp, $property->getDocComment(), $matches)))
		{
			return $matches[1];
		}
		return null;
	}
	
	/**
	 * @param ReflectionMethod $method
	 * @return String
	 */
	public static function getReturnType($method)
	{
		$regexp = '/@return[ ]+([^ ]*)/m';
		$matches = null;
		if (preg_match($regexp, $method->getDocComment(), $matches))
		{
			return trim($matches[1]);	
		}
		return null;
	}
	
	/**
	 * @param ReflectionMethod $method
	 * @param String $paramName
	 * @return String
	 */
	public static function getParamType($method, $paramName)
	{
		$regexp = '/@param[ ]+([^ ]*)[ ]+\$'.$paramName.'( .*){0,1}$/m';
		$matches = null;
		if (preg_match($regexp, $method->getDocComment(), $matches))
		{
			return trim($matches[1]);	
		}
		return null;
	}

	// private methods

	/**
	 * @param mixed $objectOrClassName
	 * @return ReflectionClass
	 * @throws IllegalArgumentException
	 */
	private static function getReflectionClassFromInstanceOrClassName($objectOrClassName)
	{
		if (is_object($objectOrClassName))
		{
			$className = get_class($objectOrClassName);
		}
		else if (is_string($objectOrClassName))
		{
			$className = $objectOrClassName;
		} else {
			throw new IllegalArgumentException('$object must be an object or a string.');
		}
		return new ReflectionClass($className);
	}
}
