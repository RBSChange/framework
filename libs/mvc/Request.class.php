<?php
/**
 * @deprecated use \Change\Mvc\Request
 */
class change_Request extends \Change\Mvc\Request
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
	 * @deprecated
	 */
	protected $attributes = array();

	/**
	 * @deprecated
	 */
	public function setParameterByRef($name, &$value)
	{
		$this->parameters[$name] = $value;
	}

	/**
	 * @deprecated
	 */
	public function clearAttributes()
	{
		$this->attributes = null;
		$this->attributes = array();
	}

	/**
	 * @deprecated
	 */
	public function getAttribute($name)
	{
		return (isset($this->attributes[$name])) ? $this->attributes[$name] : null;
	}

	/**
	 * @deprecated
	 */
	public function getAttributeNames()
	{
		return array_keys($this->attributes);
	}
	
	/**
	 * @deprecated
	 */
	public function hasAttribute($name)
	{
		return isset($this->attributes[$name]);
	}
	
	/**
	 * @deprecated
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
	 * @deprecated
	 */
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}	
	
	/**
	 * @deprecated
	 */	
	public function setAttributeByRef ($name, &$value)
	{
		$this->attributes[$name] = $value;
	}
		
	/**
	 * @deprecated
	 */
	public function setAttributes($attributes)
	{
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function shutdown()
	{
		parent::shutdown();
		$this->attributes = null;
	}

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
	
	/**
	 * @deprecated
	 */
	public function hasNonEmptyParameter($paramName)
	{
		return $this->hasParameter($paramName) && $this->getParameter($paramName) != '';
	}
	
	/**
	 * @deprecated
	 */
	public function getFile($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name] : null;
	}
	
	/**
	 * @deprecated
	 */
	public function getFileError($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['error'] : UPLOAD_ERR_NO_FILE;
	}
	
	/**
	 * @deprecated
	 */
	public function getFileName($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['name'] : null;
	}
	
	/**
	 * @deprecated
	 */
	public function getFileNames()
	{
		return array_keys($_FILES);
	}
	
	/**
	 * @deprecated
	 */
	public function getFiles()
	{
		return $_FILES;
	}
	
	/**
	 * @deprecated
	 */
	public function getFilePath($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['tmp_name'] : null;
	}
	
	/**
	 * @deprecated
	 */
	public function getFileSize($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['size'] : null;
	}
	
	/**
	 * @deprecated
	 */
	public function getFileType($name)
	{
		return (isset($_FILES[$name])) ? $_FILES[$name]['type'] : null;
	}
	
	/**
	 * @deprecated
	 */
	public function hasFile($name)
	{
		return isset($_FILES[$name]) ? true : false;
	}
	
	/**
	 * @deprecated
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
	 * @deprecated
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
	 * @deprecated
	 */
	public function hasFiles()
	{
		return (count($_FILES) > 0);
	}
	
	/**
	 * @deprecated
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
					throw new \Exception($error);
				}
			}
			if (!is_dir($directory))
			{
				// the directory path exists but it's not a directory
				$error = 'File upload path "%s" exists, but is not a directory';
				$error = sprintf($error, $directory);
				throw new \Exception($error);
			}
				
			if (!is_writable($directory))
			{
				// the directory isn't writable
				$error = 'File upload path "%s" is not writable';
				$error = sprintf($error, $directory);
				throw new \Exception($error);
			}
				
			if (@move_uploaded_file($_FILES[$name]['tmp_name'], $file))
			{
				@chmod($file, $fileMode);
				return true;
			}
		}
		return false;
	}
}