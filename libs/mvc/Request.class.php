<?php
class change_Request
{
	/**
	 * @param string $class
	 * @throws Exception
	 * @return change_Request
	 */
	public static function newInstance($class)
	{
		$object = new $class();
		if (!($object instanceof change_Request))
		{
			$error = 'Class "' . $class . '" is not of the type change_Request';
			throw new Exception($error);
		}
		return $object;
	}
	
	/**
	 * @var integer
	 */
	const GET = 2;
	
	/**
	 * @var integer
	 */
	const POST = 4;	
	
	/**
	 * @var integer
	 */
	const PUT = 8;	
	
	/**
	 * @var integer
	 */
	const DELETE = 16;	
	
	/**
	 * @var string
	 */	
	const DOCUMENT_ID = 'cmpref';

	private $attributes = array();
	private $errors	 = array();
	private $method	 = null;	
	
	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @return void
	 */
	public function clearParameters()
	{
		$this->parameters = null;
		$this->parameters = array();
	}

	/**
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	public function getParameter($name, $default = null)
	{
		return (isset($this->parameters[$name])) ? $this->parameters[$name] : $default;
	}

	/**
	 * @return string[]
	 */
	public function getParameterNames()
	{
		return array_keys($this->parameters);
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasParameter($name)
	{
		return isset($this->parameters[$name]);
	}

	/**
	 * @param string $name
	 * @return mixed old value
	 */
	public function removeParameter($name)
	{
		if (isset($this->parameters[$name]))
		{
			$retval = $this->parameters[$name];
			unset($this->parameters[$name]);
			return $retval;
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setParameterByRef($name, &$value)
	{
		$this->parameters[$name] = $value;
	}

	/**
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
	}
	
	/**
	 * @return void
	 */
	public function clearAttributes()
	{
		$this->attributes = null;
		$this->attributes = array();
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getAttribute($name)
	{
		return (isset($this->attributes[$name])) ? $this->attributes[$name] : null;
	}

	/**
	 * @return string[]
	 */
	public function getAttributeNames()
	{
		return array_keys($this->attributes);
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasAttribute($name)
	{
		return isset($this->attributes[$name]);
	}
	
	/**
	 * @param string $name
	 * @return mixed old value
	 */
	public function removeAttribute($name)
	{
		if (isset($this->attributes[$name]))
		{
			$retval = $this->attributes[$name];
			unset($this->attributes[$name]);
			return $retval;
		}
		return null;
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}	
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */	
	public function setAttributeByRef ($name, &$value)
	{
		$this->attributes[$name] = $value;
	}
		
	/**
	 * @param array $attributes
	 */
	public function setAttributes($attributes)
	{
		$this->attributes = array_merge($this->attributes, $attributes);
	}
			
	/**
	 * @param string $name
	 * @return string|NULL
	 */
	public function getError($name)
	{
		return (isset($this->errors[$name])) ? $retval = $this->errors[$name] : null;
	}
	
	/**
	 * @return string[]
	 */
	public function getErrorNames()
	{
		return array_keys($this->errors);
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasError($name)
	{
		return isset($this->errors[$name]);
	}
	
	/**
	 * @return boolean
	 */
	public function hasErrors()
	{
		return (count($this->errors) > 0);
	}
	
	/**
	 * @param string $name
	 * @return string|NULL old value
	 */
	public function removeError($name)
	{
		if (isset($this->errors[$name]))
		{
			$retval = $this->errors[$name];
			unset($this->errors[$name]);
			return $retval;
		}
		return null;	
	}
		
	/**
	 * @param string $name
	 * @param string $message
	 */
	public function setError($name, $message)
	{
		$this->errors[$name] = $message;
	}
		
	/**
	 * @param array $errors
	 */
	public function setErrors($errors)
	{
		$this->errors = array_merge($this->errors, $errors);
	}	
	
	/**
	 * @return integer|NULL change_Request::[GET | POST | PUT | DELETE]
	 */
	public function getMethod()
	{
		return $this->method;
	}
	
	/**
	 * @param integer $method change_Request::[GET | POST | PUT | DELETE]
	 * @throws Exception
	 */
	public function setMethod($method)
	{
		if ($method == self::GET || $method == self::POST || $method == self::PUT || $method == self::DELETE)
		{
			$this->method = $method;
			return;
		}
		throw new Exception('Invalid request method: ' . $method);
	}

	// -------------------------------------------------------------------------
	
	/**
	 * @param string $name
	 * @return array|NULL
	 */
	public function getFile($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name] : null;
	}

	/**
	 * @param string $name
	 * @return intger One of the following error codes:
	 *  - UPLOAD_ERR_OK (no error)
	 *  - UPLOAD_ERR_INI_SIZE (the uploaded file exceeds the upload_max_filesize directive in php.ini)
	 *  - UPLOAD_ERR_FORM_SIZE (the uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form)
	 *  - UPLOAD_ERR_PARTIAL (the uploaded file was only partially uploaded)
	 *  - UPLOAD_ERR_NO_FILE (no file was uploaded)
	 */
	public function getFileError($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['error'] : UPLOAD_ERR_NO_FILE;
	}

	/**
	 * @param string $name
	 * @return string|NULL
	 */
	public function getFileName($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['name'] : null;
	}
	
	/**
	 * @return string[]
	 */
	public function getFileNames()
	{
		return array_keys($_FILES);
	}

	/**
	 * @return array
	 */
	public function getFiles()
	{
		return $_FILES;
	}

	/**
	 * @param string $name
	 * @return string|NULL
	 */
	public function getFilePath($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['tmp_name'] : null;
	}

	/**
	 * @param string $name
	 * @return integer|NULL
	 */
	public function getFileSize($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['size'] : null;
	}
	
	/**
	 * @param string $name
	 * @return string|NULL
	 */
	public function getFileType($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['type'] : null;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasFile($name)
	{
		return isset($_FILES[$name]) ? true : false;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasFileError($name)
	{
		if (isset($_FILES[$name]))
		{
			return ($_FILES[$name]['error'] != UPLOAD_ERR_OK);
		}
		return false;
	}
	
	/**
	 * @return boolean
	 */
	public function hasFileErrors()
	{
		foreach ($_FILES as &$file)
		{
			if ($file['error'] != UPLOAD_ERR_OK)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	public function hasFiles()
	{
		return (count($_FILES) > 0);
	}

	/**
	 * 
	 * @param change_Context $context
	 * @param array $parameters
	 */
	public function initialize($context, $parameters = null)
	{		
		if (isset($_SERVER['REQUEST_METHOD']))
		{
			$this->setParameters($_GET);
			switch ($_SERVER['REQUEST_METHOD'])
			{
				case 'POST':
					$this->setMethod(self::POST);
					$this->setParameters($_POST);
					break;
				case 'PUT':
					$this->setMethod(self::PUT);
					break;
				case 'DELETE':
					$this->setMethod(self::DELETE);
					break;
				default:
					$this->setMethod(self::GET);
					break;
			}
		} 
		else
		{
			$this->setMethod(self::GET);
			if (isset($_SERVER['argv']))
			{
				$this->setParameters($_SERVER['argv']);
			}
		}
		
		if (is_array($parameters))
		{
			$this->setParameters($parameters);
		}
	}

	/**
	 * 
	 * @param string $name
	 * @param string $file
	 * @param integer $fileMode
	 * @param boolean $create
	 * @param integer $dirMode
	 * @throws Exception
	 * @return boolean
	 */
	public function moveFile($name, $file, $fileMode = 0775, $create = true, $dirMode = 0775)
	{
		if (isset($_FILES[$name]) && $_FILES[$name]['error'] == UPLOAD_ERR_OK)
		{
			$directory = dirname($file);
			if (!file_exists($directory))
			{
				if ($create && !@mkdir($directory, $dirMode, true))
				{
					$error = 'Failed to create file upload directory "%s"';
					$error = sprintf($error, $directory);
					throw new Exception($error);					
				}
			}
			if (!is_dir($directory))
			{
				// the directory path exists but it's not a directory
				$error = 'File upload path "%s" exists, but is not a directory';
				$error = sprintf($error, $directory);				
				throw new Exception($error);
			}
			
			if (!is_writable($directory))
			{
				// the directory isn't writable
				$error = 'File upload path "%s" is not writable';
				$error = sprintf($error, $directory);				
				throw new Exception($error);
			}
			
			if (@move_uploaded_file($_FILES[$name]['tmp_name'], $file))
			{
				@chmod($file, $fileMode);
				return true;
			}
		}
		return false;
	}

	public function shutdown ()
	{
	}

	/**
	 * Set a cookie.
	 * @param string $key Cookie key
	 * @param string $value Cookie value
	 * @param string $lifeTime Cookie life time in seconds
	 */
	public function setCookie($key, $value, $lifeTime = null)
	{
		if ($lifeTime === null)
		{
			$lifeTime = 60 * 60 * 24 * 15;
		}
		setcookie($key, $value, time() + $lifeTime, '/');
	}
	
	/**
	 * Test a cookie availability.
	 * @param string $key Cookie key
	 * @return boolean
	 */
	public function hasCookie($key)
	{
		if (isset($_COOKIE[$key]) && $_COOKIE[$key])
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Get a cookie value.
	 * @param string $key Cookie key
	 * @param string $defaultValue Cookie default value
	 * @return string
	 */
	public function getCookie($key, $defaultValue = '')
	{
		if ($this->hasCookie($key))
		{
			return $_COOKIE[$key];
		}
		return $defaultValue;
	}
	
	/**
	 * Remove a cookie.
	 * @param string $key Cookie key
	 */
	public function removeCookie($key)
	{
		setcookie($key, '', time() - 3600, '/');
	}
	
	/**
	 * @param string $paramName
	 * @return boolean
	 */
	public function hasNonEmptyParameter($paramName)
	{
		return $this->hasParameter($paramName) && f_util_StringUtils::isNotEmpty($this->getParameter($paramName));
	}

	/**
	 * Retrieve a module parameter.
	 *
	 * @param string $moduleName The module name.
	 * @param string $paramName The parameter name.
	 */
	public function getModuleParameter($moduleName, $paramName)
	{
		$moduleParams = $this->getModuleParameters($moduleName);
		if (is_array($moduleParams) && isset($moduleParams[$paramName]))
		{
			return $moduleParams[$paramName];
		}
		return null;
	}

   /**
	 * set a module parameter.
	 * @param string $moduleName The module name.
	 * @param string $paramName The parameter name.
	 * @param mixed $paramValue
	 */
	public function setModuleParameter($moduleName, $paramName, $paramValue)
	{
		if (!isset($this->parameters[$moduleName."Param"]))
		{
			$this->parameters[$moduleName."Param"] = array($paramName => $paramValue);
		}
		else
		{
			$this->parameters[$moduleName."Param"][$paramName] = $paramValue;
		}
	}
	
	/**
	 * Indicates whether the request has the given module parameter or not.
	 *
	 * @param string $moduleName The module name.
	 * @param string $paramName The parameter name.
	 * @return boolean true if the module parameter exists, false otherwise.
	 */
	public function hasModuleParameter($moduleName, $paramName)
	{
		$moduleParams = $this->getModuleParameters($moduleName);
		return is_array($moduleParams) &&  isset($moduleParams[$paramName]);
	}
	
	/**
	 * Retrieve all the parameters defined for the given module.
	 * @param string $moduleName The module name.
	 * @return array|NULL
	 */
	public function getModuleParameters($moduleName)
	{
		return $this->getParameter($moduleName."Param");
	}
	
	//DEPRECATED 
	
	/**
	 * @deprecated
	 */
	public function setParametersByRef(&$parameters)
	{
		foreach ($parameters as $key => &$value)
		{
			if (is_array($value) && isset($this->parameters[$key]))
			{
				if (!is_array($this->parameters[$key]))
				{
					$this->parameters[$key] = array($this->parameters[$key]);
				}
				$this->parameters[$key] = array_merge($this->parameters[$key], f_util_StringUtils::doTranscode($value));
			}
			else
			{
				$this->parameters[$key] = f_util_StringUtils::doTranscode($value);
			}
		}
	}
	
	/**
	 * @deprecated
	 */
	public function extractParameters($names)
	{
		$array = array();
		foreach ($this->parameters as $key => &$value)
		{
			if (in_array($key, $names))
			{
				$array[$key] = $value;
			}
		}
		return $array;
	}
	
	/**
	 * @deprecated
	 */
	public function setAttributesByRef (&$attributes)
	{
		foreach ($attributes as $key => &$value)
		{
			$this->attributes[$key] = $value;
		}
	}
}