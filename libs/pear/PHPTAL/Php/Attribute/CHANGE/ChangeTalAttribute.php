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
	 * @see PHPTAL_Php_Attribute::start
	 */
	public function start()
	{
		$parametersString = $this->initParams();
		$this->getRenderMethodCall($parametersString);
	}

	protected function initParams()
	{
		$expressions = $this->tag->generator->splitExpression($this->expression);
		$parameters = array();
		$parameters[] =	'"tagname" =>' . var_export($this->tag->name, true);

		//TODO: cleanup parameters
		$this->parameters = $this->getDefaultValues();

		foreach ($this->getDefaultValues() as $name => $val)
		{
			$parameters[] = var_export($name, true) . '=>' . var_export(f_util_Convert::fixDataType($val), true);
		}

		// Parse "direct" attributes
		$varRegexp = '/\${[^}]*}/';
		foreach ($this->tag->attributes as $name => $val)
		{
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
						$paramValue .= $this->tag->generator->evaluateExpression($var);
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
				$parameters[] = var_export($name, true) . '=>' . $paramValue;
			}
			elseif (preg_match('#^<\?php echo (.*) \?>$#', $val, $matches))
			{
				$paramValue = $matches[1];
				$this->parameters[$name] = $paramValue;
				$parameters[] = var_export($name, true) . '=>' . $paramValue;
			}
			elseif (strpos($name, ':') === false || $name === 'xml:lang')
			{
				$convertedVal = f_util_Convert::fixDataType($val);
				$this->parameters[$name] = $convertedVal;
				$parameters[] = var_export($name, true) . '=>' . var_export($convertedVal, true);
			}
		}

		$evaluateAll = $this->evaluateAll();
		// Parse change:xxxx attribute
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


			if (f_Locale::isLocaleKey($value . ';'))
			{
				$value = $value . ';';
			}

			if ($evaluateAll || in_array($parameterName, $this->getEvaluatedParameters()))
			{
				$this->parameters[$parameterName] = $this->evaluate($value);
				$parameters[] = $this->evaluateParameter($parameterName, $value);
			}
			else
			{
				$this->parameters[$parameterName] = $value;
				$parameters[] = var_export($parameterName, true) . '=>' . var_export(f_util_Convert::fixDataType($value), true);
			}

		}
		return $parametersString = 'array(' . implode(', ', $parameters) . ')';
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
	 * @see PHPTAL_Php_Attribute::end
	 */
	public function end()
	{

	}

	/**
	 * Subclasses should override this method to return the list of parameters they will evaluate
	 * @example return array('value', 'label');
	 * @return String[]
	 */
	protected function getEvaluatedParameters()
	{
		return array();
	}

	/**
	 * Subclasses should override this method to return the default parameters
	 *
	 * @example return array('value' => 'toto', 'label' => 'tutu');
	 * @return String[]
	 */
	protected function getDefaultValues()
	{
		return array();
	}

	/**
	 * @see ChangeTalAttribute::buildAttribute()
	 * @example ChangeTalAttribute::buildAttributes(array("attrName" => "attrValue")) renders ' attrName="attrValue"'
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
	 * @example ChangeTalAttribute::buildAttribute("attrName", "attrValue") renders 'attrName="attrValue"'
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

	private function evaluateParameter($name, $value)
	{
		$normalizedValue = $this->evaluate($value);
		if ($normalizedValue[0] == '\'')
		{
			$normalizedValue = substr($normalizedValue, 1, strlen($normalizedValue) - 2);
		}
		if (strpos($normalizedValue, '$ctx') === false)
		{
			return var_export($name, true) . '=>' . var_export(f_util_Convert::fixDataType($normalizedValue), true);
		}
		return var_export($name, true) . '=>' . $normalizedValue;
	}

	/**
	 * @param String $parametersString
	 */
	protected function getRenderMethodCall($parametersString)
	{
		$this->tag->generator->pushCode('echo '.$this->getRenderClassName() . '::' . $this->getRenderMethodName() . '(' . $parametersString  . ', $ctx)');
	}

	/**
	 * @return Boolean
	 */
	protected function evaluateAll()
	{
		return false;
	}
}