<?php
class change_Request
{
	
	public static function newInstance ($class)
	{
		$object = new $class();
		if (!($object instanceof change_Request))
		{
			$error = 'Class "'. $class.'" is not of the type ChangeRequest';
			throw new Exception($error);
		}
		return $object;
	}
	
	const GET = 2;
	const NONE = 1;
	const POST = 4;
	const CONSOLE = 8;
	
	const DOCUMENT_ID = 'cmpref';

	private $attributes = array();
	private $errors     = array();
	private $method     = null;	
	
	protected $parameters = array();

	public function clearParameters()
	{
		$this->parameters = null;
		$this->parameters = array();
	}

	public function getParameter($name, $default = null)
	{
		return (isset($this->parameters[$name])) ? $this->parameters[$name] : $default;
	}

	public function getParameterNames()
	{
		return array_keys($this->parameters);
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function hasParameter($name)
	{
		return isset($this->parameters[$name]);

	}

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

	public function setParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	public function setParameterByRef($name, &$value)
	{
		$this->parameters[$name] = $value;
	}

	public function setParameters ($parameters)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
	}

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



	public function clearAttributes ()
	{
		$this->attributes = null;
		$this->attributes = array();
	}

	public function getAttribute ($name)
	{
		return (isset($this->attributes[$name])) ? $this->attributes[$name] : null;
	}

	public function getAttributeNames ()
	{
		return array_keys($this->attributes);
	}

	public function hasAttribute ($name)
	{
		return isset($this->attributes[$name]);
	}
	

	public function removeAttribute ($name)
	{
		if (isset($this->attributes[$name]))
		{
			$retval = $this->attributes[$name];
			unset($this->attributes[$name]);
			return $retval;
		}
		return null;
	}
	
	public function setAttribute ($name, $value)
	{
		$this->attributes[$name] = $value;
	}	
	
	public function setAttributeByRef ($name, &$value)
	{
		$this->attributes[$name] = $value;
	}
		
	public function setAttributes($attributes)
	{
		$this->attributes = array_merge($this->attributes, $attributes);
	}
		
	public function setAttributesByRef (&$attributes)
	{
		foreach ($attributes as $key => &$value)
		{
			$this->attributes[$key] = $value;
		}
	}	
	
	
	
	
	
	public function getError ($name)
	{
		return (isset($this->errors[$name])) ? $retval = $this->errors[$name] : null;
	}
	
	public function getErrorNames ()
	{
		return array_keys($this->errors);
	}

	public function getErrors ()
	{

		return $this->errors;

	}

	public function hasError ($name)
	{
		return isset($this->errors[$name]);
	}
	
	public function hasErrors ()
	{
		return (count($this->errors) > 0);
	}
	
	public function removeError ($name)
	{
		if (isset($this->errors[$name]))
		{
			$retval = $this->errors[$name];
			unset($this->errors[$name]);
			return $retval;
		}
		return null;	
	}
		
	public function setError ($name, $message)
	{
		$this->errors[$name] = $message;
	}
		
	public function setErrors ($errors)
	{
		$this->errors = array_merge($this->errors, $errors);

	}	
	
	
	
	
	
	
	
	
	
	public function getMethod ()
	{
		return $this->method;
	}
	
	public function setMethod ($method)
	{
		if ($method == self::GET || $method == self::POST || $method == self::CONSOLE)
		{
			$this->method = $method;
			return;
		}
		// invalid method type
		$error = 'Invalid request method: %s';
		$error = sprintf($error, $method);
		throw new Exception($error);
	}

	public function getFile($name)
	{
		if (isset($_FILES[$name]))
		{
			return $_FILES[$name];
		}
		return null;
	}

	// -------------------------------------------------------------------------

	/**
	 * @param string $name
	 * @return int One of the following error codes:
	 *  - UPLOAD_ERR_OK (no error)
	 *  - UPLOAD_ERR_INI_SIZE (the uploaded file exceeds the upload_max_filesize directive in php.ini)
	 *  - UPLOAD_ERR_FORM_SIZE (the uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form)
	 *  - UPLOAD_ERR_PARTIAL (the uploaded file was only partially uploaded)
	 *  - UPLOAD_ERR_NO_FILE (no file was uploaded)
	 */
	public function getFileError ($name)
	{
		if (isset($_FILES[$name]))
		{
			return $_FILES[$name]['error'];
		}
		return UPLOAD_ERR_NO_FILE;
	}

	public function getFileName($name)
	{
		if (isset($_FILES[$name]))
		{
			return $_FILES[$name]['name'];
		}
		return null;
	}
	
	public function getFileNames ()
	{
		return array_keys($_FILES);
	}

	public function getFiles ()
	{
		return $_FILES;
	}

	public function getFilePath ($name)
	{
		if (isset($_FILES[$name]))
		{
			return $_FILES[$name]['tmp_name'];
		}
		return null;
	}

	public function getFileSize ($name)
	{
		if (isset($_FILES[$name]))
		{
			return $_FILES[$name]['size'];
		}
		return null;
	}

	public function getFileType ($name)
	{
		if (isset($_FILES[$name]))
		{
			return $_FILES[$name]['type'];
		}
		return null;
	}

	public function hasFile ($name)
	{
		return isset($_FILES[$name]);
	}

	public function hasFileError ($name)
	{
		if (isset($_FILES[$name]))
		{
			return ($_FILES[$name]['error'] != UPLOAD_ERR_OK);
		}
		return false;
	}

	public function hasFileErrors ()
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

	public function hasFiles ()
	{
		return (count($_FILES) > 0);
	}

	public function initialize ($context, $parameters = null)
	{
		if (isset($_SERVER['REQUEST_METHOD']))
		{
			switch ($_SERVER['REQUEST_METHOD'])
			{

				case 'GET':
				    $this->setMethod(self::GET);
				    break;

				case 'POST':
				    $this->setMethod(self::POST);
				    break;

				default:
				    $this->setMethod(self::GET);

			}
		} 
		else
		{
			$this->setMethod(self::GET);
		}
		$this->loadParameters();
	}

	private function loadParameters()
	{
		$this->setParametersByRef($_GET);
		$this->setParametersByRef($_POST);
	}

	public function moveFile($name, $file, $fileMode = 0666, $create = true, $dirMode = 0777)
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
        if ($moduleParams !== null && isset($moduleParams[$paramName]))
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
        return $moduleParams !== null &&  isset($moduleParams[$paramName]);
    }
    
    /**
     * Retrieve all the parameters defined for the given module.
     * @param string $moduleName The module name.
     */
    public function getModuleParameters($moduleName)
    {
        return $this->getParameter($moduleName."Param");
    }
}