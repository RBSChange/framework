<?php
class ChangeTalAttribute extends PHPTAL_Php_Attribute
{
	/**
	 * @var array
	 */
	private $parameters = array();

	/**
	 * @return array
	 */
	protected function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param String $name
	 * @return Boolean
	 */
	protected function hasParameter($name)
	{
		return isset($this->parameters[$name]);
	}

	/**
	 * @param String $name
	 * @param Mixed $defaultValue
	 * @return Mixed
	 */
	protected function getParameter($name, $defaultValue = null)
	{
		if (isset($this->parameters[$name]))
		{
			return $this->parameters[$name];
		}
		return $defaultValue;
	}

	/**
     * Called before element printing.
     */
    public function before(PHPTAL_Php_CodeWriter $codewriter)
    {
		$parametersString = $this->initParams($codewriter);
		$this->getRenderMethodCall($codewriter, $parametersString);
	}
	
	/**
     * Called after element printing.
     */
    public function after(PHPTAL_Php_CodeWriter $codewriter)
    {

	}

	protected function initParams(PHPTAL_Php_CodeWriter $codewriter, $excludedNames = null)
	{
		$parameters = array();
		$parameters['tagname'] =	'"tagname" =>' . var_export($this->phpelement->getLocalName(), true);

		//TODO: cleanup parameters
		$this->parameters = $this->getDefaultValues();

		foreach ($this->getDefaultValues() as $name => $val)
		{
			$parameters[$name] = var_export($name, true) . '=>' . var_export(f_util_Convert::fixDataType($val), true);
		}

		
		// Parse "direct" attributes
		$varRegexp = '/\${[^}]*}/';
		foreach ($this->phpelement->getAttributeNodes() as  $attrNode)
		{
			/* @var $attrNode PHPTAL_Dom_Attr */
			if ($attrNode->getNamespaceURI() !== '') 
			{
				if ($attrNode->getQualifiedName() === 'xml:lang')
				{
					$this->parameters[$name] = $attrNode->getValueEscaped();
					$parameters[$name] = var_export($name, true) . '=>' . var_export($this->parameters[$name], true);
				}
				continue;
			}
			
			$name = $attrNode->getLocalName();
			if ($excludedNames !== null && in_array($name, $excludedNames))
			{
				continue;
			}
			$val = $attrNode->getValueEscaped();
			if (preg_match_all($varRegexp, $val, $matches))
			{
				$paramValue = null;
				$i = 0;
				foreach (preg_split($varRegexp, $val) as $valPart)
				{
					if (isset($matches[0][$i]))
					{
						$var = $matches[0][$i];
						$var = substr($var, 2, strlen($var)-3);

						if ($valPart != "")
						{
							if ($paramValue !== null)
							{
								$paramValue .= '.';
							}
							$paramValue .= var_export($valPart, true);
						}
						if ($paramValue !== null)
						{
							$paramValue .= '.';
						}
						$paramValue .= $codewriter->evaluateExpression($var);
					}
					else
					{
						if ($valPart != "")
						{
							if ($paramValue !== null)
							{
								$paramValue .= '.';
							}
							$paramValue .= var_export($valPart, true);
						}
					}
					$i++;
				}
				$this->parameters[$name] = $paramValue;
				$parameters[$name] = var_export($name, true) . '=>' . $paramValue;
			}
			elseif (preg_match('#^<\?php echo (.*) \?>$#', $val, $matches))
			{
				$this->parameters[$name] = $matches[1];
				$parameters[$name] = var_export($name, true) . '=>' . $this->parameters[$name];
			}
			else
			{
				$this->parameters[$name] = f_util_Convert::fixDataType($val);
				$parameters[$name] = var_export($name, true) . '=>' . var_export($this->parameters[$name], true);
			}
		}

		$evaluateAll = $this->evaluateAll();
		$expressions = $codewriter->splitExpression($this->expression);
		
		foreach ($expressions as $exp)
		{
			if (f_util_StringUtils::isEmpty($exp))
			{
				continue;	
			}
			list($parameterName, $value) = $this->parseSetExpression($exp);

			if ($value === null)
			{
				$defaultParameterName = $this->getDefaultParameterName();
				if ($defaultParameterName !== null)
				{
					$value = $parameterName;
					$parameterName = $defaultParameterName;
				}
				else
				{
					if (Framework::isWarnEnabled())
					{
						Framework::warn(__METHOD__ . ': no default parameter for phptal extension ' . get_class($this) . '!');
					}
					continue;
				}
			}

			if ($evaluateAll || in_array($parameterName, $this->getEvaluatedParameters()))
			{
				$this->parameters[$parameterName] = $codewriter->evaluateExpression($value);			
				$parameters[$parameterName] = var_export($parameterName, true) . '=>' . $this->parameters[$parameterName];
			}
			else
			{
				$this->parameters[$parameterName] = $value;
				$parameters[$parameterName] = var_export($parameterName, true) . '=>' . var_export(f_util_Convert::fixDataType($value), true);
			}

		}
		return 'array(' . implode(', ', $parameters) . ')';
	}
	
	/**
	 * Add [ ] char in parameter name
	 * @see PHPTAL_Php_Attribute::parseSetExpression()
	 */
    protected function parseSetExpression($exp)
    {
        $exp = trim($exp);
        // (dest) (value)
        if (preg_match('/^([a-z0-9:\[\]\-_]+)\s+(.*?)$/si', $exp, $m)) {
            return array($m[1], trim($m[2]));
        }
        // (dest)
        return array($exp, null);
    }
	
	/**
	 * @return String
	 */
	protected function getRenderClassName()
	{
		return get_class($this);
	}

	/**
	 * @return String
	 */
	protected function getRenderMethodName()
	{
		$nameParts = explode("_", get_class($this));
		return 'render'. ucfirst(f_util_ArrayUtils::lastElement($nameParts));
	}

	/**
	 * Subclasses should override this method to return the list of parameters they will evaluate
	 * @return string[] For example: array('value', 'label');
	 */
	protected function getEvaluatedParameters()
	{
		return array();
	}

	/**
	 * Subclasses should override this method to return the default parameters
	 * @return string[] For example: array('value' => 'toto', 'label' => 'tutu');
	 */
	protected function getDefaultValues()
	{
		return array();
	}

	/**
	 * @see ChangeTalAttribute::buildAttribute()
	 * For example: ChangeTalAttribute::buildAttributes(array("attrName" => "attrValue")) renders ' attrName="attrValue"'
	 * @param array<String, String> $attributes
	 * @return String
	 */
	protected static function buildAttributes($attributes)
	{
		$attrString = "";
		foreach ($attributes as $attrName => $attrValue)
		{
			$attrString .= " ".self::buildAttribute($attrName, $attrValue);
		}
		return $attrString;
	}

	/**
	 * You should always use this method to output an attribute so it is safe
	 * For example: ChangeTalAttribute::buildAttribute("attrName", "attrValue") renders 'attrName="attrValue"'
	 * @param String $attrName
	 * @param String $attrValue
	 * @return String
	 */
	protected static function buildAttribute($attrName, $attrValue)
	{
		return $attrName . '="' . str_replace('"', '&quot;', $attrValue) . '"';
	}

	/**
	 * @return String
	 */
	protected function getDefaultParameterName()
	{
		return null;
	}

	/**
	 * @param String $parametersString
	 */
	protected function getRenderMethodCall(PHPTAL_Php_CodeWriter $codeWriter, $parametersString)
	{
		$codeWriter->pushCode('echo '.$this->getRenderClassName() . '::' . $this->getRenderMethodName() . '(' . $parametersString  . ', $ctx)');
	}

	/**
	 * @return Boolean
	 */
	protected function evaluateAll()
	{
		return false;
	}
}