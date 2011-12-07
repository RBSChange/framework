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
        $args = array_slice(func_get_args(), 1);
        if (empty($args))
        {
        	return $classObj->newInstance();
		}
		return $classObj->newInstanceArgs($args);
	}
	
	/**
	 * @param String $className
	 * @param String $expectedClassName
	 * @example newInstance($className, "mymodule_SomeClass", $arg1, $arg2, $arg3 ...)
	 * @throws Exception if the $className is not compatible with $expectedClassName
	 * @return mixed
	 */
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
		$regexp = '/@' . $metaName . '($|\s|\()/m';
		return preg_match($regexp, self::getDocComment($methodOrPropertyOrClass)) > 0;
	}
	
	/**
	 * @param String $metaName
	 * @param ReflectionMethod|ReflectionProperty|ReflectionClas $methodOrPropertyOrClass
	 * @return String
	 */
	public static function getMetaValue($metaName, $methodOrPropertyOrClass)
	{
		$regexp = '/@' . $metaName . '\(([^\)]*)\)/m';
		$matches = null;
		// TODO: handle multiple values, ie string indexed array. ex: '(name1="value1",name2=value2,name3={value3, value4})'
		if (preg_match($regexp, self::getDocComment($methodOrPropertyOrClass), $matches))
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
		$regexp = '/@' . $metaName . '\(([^\)]*)\)/m';
		$matches = null;
		// TODO: handle multiple values, ie string indexed array. ex: '(name1="value1",name2=value2,name3={value3, value4})'
		if (($method !== null && preg_match($regexp, self::getDocComment($method), $matches)) || ($property !== null && preg_match($regexp, self::getDocComment($property), $matches)))
		{
			return $matches[1];
		}
		return null;
	}
	
	static function getDocComment(&$reflectionObj)
	{
		if (extension_loaded("eaccelerator") && !defined("EACCELERATOR_PRESERVE_DOC_COMMENT") && !($reflectionObj instanceof f_util_ReflectionObjWrapper))
		{
			$reflectionObj = new f_util_ReflectionObjWrapper($reflectionObj);
		}
		return $reflectionObj->getDocComment();
	}
	
	/**
	 * @param ReflectionMethod $method
	 * @return String
	 */
	public static function getReturnType($method)
	{
		$regexp = '/@return[ ]+([^ ]*)/m';
		$matches = null;
		if (preg_match($regexp, self::getDocComment($method), $matches))
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
		$regexp = '/@param[ ]+([^ ]*)[ ]+\$' . $paramName . '( .*){0,1}$/m';
		$matches = null;
		if (preg_match($regexp, self::getDocComment($method), $matches))
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
		}
		else
		{
			throw new IllegalArgumentException('$object must be an object or a string.');
		}
		return new ReflectionClass($className);
	}

	// Deprecated.
	
	/**
	 * @deprecated use class_exists
	 */
	public static function classExists($className)
	{
		return class_exists($className);
	}
}

/**
 * A wrapper for a "ReflectionObject" (ReflectionClass, ReflectionProperty
 *  or ReflectionMethod instance) that re-implements getDocComment() to hack
 *  eaccelerator behaviour ; See http://eaccelerator.net/ticket/229
 */
class f_util_ReflectionObjWrapper
{
	private $docComment;
	private $obj;
	
	/**
	 * @param ReflectionClass|ReflectionProperty|ReflectionMethod $obj
	 */
	function __construct($obj)
	{
		$this->obj = $obj;
	}
	
	/**
	 * @return String
	 */
	function getDocComment()
	{
		if ($this->docComment !== null)
		{
			return $this->docComment;
		}
		
		if ($this->obj instanceof ReflectionProperty)
		{
			$propName = '$' . $this->obj->getName();
			$class = $this->obj->getDeclaringClass();
			$fileName = $class->getFileName();
			$lines = file($fileName, FILE_IGNORE_NEW_LINES);
			if ($lines === false)
			{
				throw new Exception("Could not read $fileName");
			}
			$code = "<?php " . join("\n", array_slice($lines, $class->getStartLine() - 1, $class->getEndLine() - $class->getStartLine() + 1));
			$braceLevel = 0;
			$lastComment = null;
			foreach (token_get_all($code) as $token)
			{
				if (is_array($token))
				{
					$type = $token[0];
					$value = $token[1];
				}
				else
				{
					if ($token == '{')
					{
						$braceLevel ++;
					}
					else if ($token == '}')
					{
						$braceLevel --;
					}
					$type = null;
					$value = $token;
				}
				
				switch ($type)
				{
					case T_DOC_COMMENT :
						$lastComment = $value;
						break;
					case T_VARIABLE :
						if ($braceLevel == 1 && $propName == $value)
						{	
							return $lastComment;
						}
						break;
					case T_PRIVATE:
					case T_PROTECTED:
					case T_PUBLIC:
						break;
					default:
						if (trim($value) != "")
						{
							$lastComment = null;
						}
				}
			}
			throw new Exception("Could not find ".$this->obj->getName()." property");
		}
		
		// $this->obj is ReflectionClass or ReflectionMethod
		
		$fileName = $this->obj->getFileName();
		$startLine = $this->obj->getStartLine();
		$lines = file($fileName, FILE_IGNORE_NEW_LINES);
		$docComment = array();
		$inComment = false;
		
		// probably could be better
		for ($i = $startLine - 1; $i > 0; $i --)
		{
			$line = $lines[$i];
			if (!$inComment)
			{
				$matches = null;
				if (preg_match('#^(.*)\*/(.*)$#', $line, $matches))
				{
					if (isset($matches[2]) && substr(trim($matches[2]), 0, 2) != '//' && (strpos($matches[2], "}") !== false || strpos($matches[2], ";") !== false))
					{
						$this->docComment = "";
						return $this->docComment;
					}
					
					$startCommentIndex = strpos($matches[1], "/*");
					if ($startCommentIndex !== false)
					{
						$this->docComment = substr($matches[1], $startCommentIndex) . "*/";
						return $this->docComment;
					}
					
					$docComment[] = $matches[1] . "*/";
					$inComment = true;
				}
				elseif (strpos($line, ";") !== false || strpos($line, "}") !== false)
				{
					$this->docComment = "";
					return $this->docComment;
				}
			}
			else
			{
				$startCommentIndex = strpos($line, "/*");
				if ($startCommentIndex !== false)
				{
					$docComment[] = substr($line, $startCommentIndex);
					break;
				}
				else
				{
					$docComment[] = $lines[$i];
				}
			}
		}
		
		return join("\n", array_reverse($docComment));
	}
	
	function __call($method, $args)
	{
		return f_util_ClassUtils::callMethodArgsOn($this->obj, $method, $args);
	}
}