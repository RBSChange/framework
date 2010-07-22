<?php
/**
 * @package framework.validation.parsers
 */
class validation_ContraintsParser
{
	private
		$quoted,
		$escaped,
		$inValue,
		$constraintName,
		$constraintValue,
		$constraintArray;
	
	
	/**
	 * Returns an array of Validator objects, ready to be used, from a constraints
	 * definition string.
	 * 
	 * @example $validators = validation_ContraintsParser::getValidatorsFromDefinition('size:6..12;unique:true;notEqual:"wwwadmin"');
	 *
	 * @param string $definition
	 * @return array<Validator>
	 */
	public function getValidatorsFromDefinition($definition)
	{
		$this->parseDefinition($definition);
		$validators = array();
		foreach ($this->constraintArray as $name => $value)
		{
			$validators[] = $this->buildValidatorFromConstraint($name, $value);
		}
		return $validators;
	}
	
	
	/**
	 * Returns an array of constraints "name" => "parameter".
	 *
	 * @param string $definition
	 * @return array<string=>string>
	 */
	public function getConstraintArrayFromDefinition($definition)
	{
		$this->parseDefinition($definition);
		return $this->constraintArray;
	}
	
	
	/**
	 * @param string $string
	 */
	private function parseDefinition($string)
	{
		$this->resetDefaults();
		$length = strlen($string);
		for ($i = 0 ; $i < $length ; $i++)
		{
			if ( ! $this->handleChar($string[$i], $i, $i === $length-1) )
			{
				throw new ValidatorException("Malformed validator definition: unexpected character '".$string{$i}."' at position ".$i);
			}
		}
	}
	
	
	/**
	 * @param char $c
	 * @param integer $position
	 * @param boolean $lastChar
	 * @return boolean false on error
	 */
	private function handleChar($c, $position, $lastChar)
	{
		if ( ! $this->inValue )
		{
			if ($c == ':')
			{
				$this->inValue = true;
			}
			else if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c == '!' && strlen($this->constraintName) == 0))
			{
				$this->constraintName .= $c;
			}
			else 
			{
				// returns an error
				return false;
			}
		}
		else // inValue
		{
			if ($c == '\\')
			{
				$this->escaped = ! $this->escaped;
				$this->constraintValue .= '\\';
			}
			else 
			{
				if ($this->escaped)
				{
					$this->constraintValue .= $c;
					$this->escaped = false;
				}
				else 
				{
					if ($c == ';' && $this->quoted == -1)
					{
						$this->appendConstraint($this->constraintName, $this->constraintValue);
					}
					else 
					{
						// if (non-escaped double-quote)
						$this->constraintValue .= $c;
						if ($c == '"')
						{
							if ($this->quoted >= 0)
							{
								$this->quoted = -1;
							}
							else
							{
								$this->quoted = $position;
							}
						}
						if ($lastChar)
						{
							$this->appendConstraint($this->constraintName, $this->constraintValue);
						}
					}
				}
			}
		}
		
		return true;
	}
	
	
	private function buildValidatorFromConstraint($name, $value)
	{
		if ($name{0} == '!')
		{
			$name = substr($name, 1);
			$reversed = true;
		}
		else 
		{
			$reversed = false;
		}
		$className = 'validation_'.ucfirst($name).'Validator';
		if (!f_util_ClassUtils::classExists($className))
		{
			throw new ValidatorException("Unknown validator: ".$className);
		}
		
		$validator = new $className();
		if ( ! $validator instanceof validation_Validator )
		{
			throw new ValidatorException("Invalid validator: ".$className."; must be an instance of 'validation_Validator'.");
		}
		
		$validator->setReverseMode($reversed);
		/*
		// build validator parameter
		if (validation_InListValueParser::matches($value))
		{
			$value = validation_InListValueParser::getValidatorParameter($value);
		}
		else if (validation_RangeValueParser::matches($value))
		{
			$value = validation_RangeValueParser::getValidatorParameter($value);
		}
		else if (validation_BooleanValueParser::matches($value))
		{
			$value = validation_BooleanValueParser::getValidatorParameter($value);
		}
		else if (validation_NumberValueParser::matches($value))
		{
			$value = validation_NumberValueParser::getValidatorParameter($value);
		}
		else if (validation_NullValueParser::matches($value))
		{
			$value = validation_NullValueParser::getValidatorParameter($value);
		}
		else if (validation_StringValueParser::matches($value))
		{
			$value = validation_StringValueParser::getValidatorParameter($value);
		}
		*/
		// set validator parameter
		$validator->setParameter($value);
		
		return $validator;
	}
	
	
	private function appendConstraint($name, $value)
	{
		$this->constraintArray[$name] = $value;
		$this->inValue = false;
		$this->constraintName = '';
		$this->constraintValue = '';
		$this->quoted = -1;
	}
	
	protected function resetDefaults()
	{
		$this->quoted = -1;
		$this->escaped = false;
		$this->inValue = false;
		$this->constraintName = '';
		$this->constraintValue = '';
		$this->constraintArray = array();
	}
}