<?php
class f_util_Properties
{
	/**
	 * @var array<String,String>
	 */
	private $properties;
	
	/**
	 * @var Boolean
	 */
	private $preserveComments = false;
	
	/**
	 * @var Boolean
	 */
	private $preserveEmptyLines = false;

	/**
	 * @param String $path
	 */
	function load($path)
	{
		if (!is_readable($path))
		{
			throw new Exception("Can not read file $path");
		}
		$this->parse($path);
	}

	/**
	 * @param String $path
	 */
	function save($path)
	{
		$dir = dirname($path);
		if ((!file_exists($path) && !is_writable($dir)) || (file_exists($path) && !is_writable($path)))
		{
			throw new Exception("Can not write to $path");
		}
		if (file_put_contents($path, $this->__toString()) === false)
		{
			throw new Exception("Could not write to $path");
		}
	}
	
	/**
	 * (by defaults, comments are not preserved)
	 * @param Boolean $preserveComments
	 */
	function setPreserveComments($preserveComments)
	{
		$this->preserveComments = $preserveComments;
	}
	
	/**
	 * (by defaults, comments are not preserved)
	 * @param Boolean $preserveComments
	 */
	function setPreserveEmptyLines($preserveEmptyLines)
	{
		$this->preserveEmptyLines = $preserveEmptyLines;
	}

	/**
	 * @return String
	 */
	public function __toString()
	{
		if ($this->properties !== null)
		{
			$buf = "";
			foreach($this->properties as $key => $item)
			{
				if ($this->preserveComments && is_int($key))
				{
					$buf .= $item."\n";
				}
				else
				{
					$buf .= $key . "=" . $this->writeValue($item)."\n";
				}
			}
			return $buf;
		}
		return "";
	}

	/**
	 * Returns copy of internal properties hash.
	 * Mostly for performance reasons, property hashes are often
	 * preferable to passing around objects.
	 *
	 * @return array
	 */
	function getProperties()
	{
		return $this->properties;
	}

	/**
	 * Get value for specified property.
	 * This is the same as get() method.
	 *
	 * @param string $prop The property name (key).
	 * @return mixed
	 * @see get()
	 */
	function getProperty($prop, $defaultValue = null)
	{
		if (!isset($this->properties[$prop]))
		{
			return $defaultValue;
		}
		return $this->properties[$prop];
	}

	/**
	 * Set the value for a property.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed Old property value or NULL if none was set.
	 */
	function setProperty($key, $value)
	{
		$oldValue = @$this->properties[$key];
		$this->properties[$key] = $value;
		return $oldValue;
	}

	/**
	 * Same as keys() function, returns an array of property names.
	 * @return array
	 */
	function propertyNames()
	{
		return $this->keys();
	}

	/**
	 * Whether loaded properties array contains specified property name.
	 * @return boolean
	 */
	function hasProperty($key)
	{
		return isset($this->properties[$key]);
	}

	/**
	 * Whether properties list is empty.
	 * @return boolean
	 */
	function isEmpty()
	{
		return empty($this->properties);
	}

	// protected methods

	/**
	 * @param unknown_type $val
	 * @return unknown
	 */
	protected function readValue($val)
	{
		if ($val === "true")
		{
			$val = true;
		}
		elseif ($val === "false")
		{
			$val = false;
		}
		else
		{
			$valLength = strlen($val);
			if ($val[0] == "'" && $val[$valLength-1] == "'" || $val[0] == "\"" && $val[$valLength-1] == "\"")
			{
				$val = substr($val, 1, -1);
			}
		}
		return $val;
	}

	/**
	 * Process values when being written out to properties file.
	 * does things like convert true => "true"
	 * @param mixed $val The property value (may be boolean, etc.)
	 * @return string
	 */
	protected function writeValue($val)
	{
		if ($val === true)
		{
			$val = "true";
		}
		elseif ($val === false)
		{
			$val = "false";
		}
		return $val;
	}

	// private methods

	/**
	 * @param String $filePath
	 */
	private function parse($filePath)
	{
		$lines = @file($filePath);
		$this->properties = array();
		foreach($lines as $line)
		{
			$line = trim($line);
			if($line == "")
			{
				if ($this->preserveEmptyLines)
				{
					$this->properties[] = " ";
				}
				continue;
			}

			if ($line{0} == '#' || $line{0} == ';')
			{
				// it's a comment, so continue to next line
				if ($this->preserveComments)
				{
					$this->properties[] = $line;
				}
				continue;
			}
			else
			{
				$pos = strpos($line, '=');
				if ($pos === false)
				{
					throw new Exception("Invalid property file line $line");
				}
				$property = trim(substr($line, 0, $pos));
				$value = trim(substr($line, $pos + 1));
				$this->properties[$property] = $this->readValue($value);
			}
		}
	}
}