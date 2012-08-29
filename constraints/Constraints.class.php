<?php
abstract class change_Constraints 
{
	/**
	 * @param string $name
	 * @param array $params
	 * @return Zend_Validate_Interface
	 */
	public static function getByName($name, $params = array())
	{
		if (method_exists(__CLASS__, $name))
		{
			return self::$name($params);
		}
		$className = 'change_' . ucfirst($name) . 'Constraint';
		if (class_exists($className))
		{
			return new $className($params);
		}
		else
		{
			return new change_WrappedValidatorConstraint($name, $params);
		}
	}
	
	/**
	 * @param Zend_Validate_Interface $constraint
	 * @param string $fieldLabel
	 * @param array $params
	 * @return array
	 */
	public static function formatMessages($constraint, $fieldLabel = null, $params = array())
	{
		$messages = $constraint->getMessages();
		$result = array();
		$ls = LocaleService::getInstance();
		if (is_string($fieldLabel)) 
		{
			$fieldLabel = $ls->isKey($fieldLabel) ? $ls->trans($fieldLabel):  $fieldLabel;
		}
		
		foreach ($messages as $key => $msg) 
		{
			if (empty($msg))
			{
				$msg = self::getI18nConstraintValue($key);
			}
			elseif ($ls->isKey($msg))
			{
				$msg = $ls->trans($msg, array('ucf'));
			}
			if (count($params))
			{
				$search = array();
				$replace = array();
				foreach ($params as $k => $v)
				{
					$search[] = '{' . $k . '}';
					$replace[] = $v;
				}
				$msg = str_replace($search, $replace, $msg);
			}
			
			if ($fieldLabel !== null)
			{
				$result[] = self::addFieldLabel($fieldLabel, $msg);
			}
			else
			{
				$result[] = $msg;
			}
		}
		return array_unique($result, SORT_STRING);
	}
	
	/**
	 * 
	 * @param string $fieldLabel
	 * @param string $message
	 * @return string
	 */
	public static function addFieldLabel($fieldLabel, $message)
	{
		if (strpos($message, '{field}') === false)
		{
			$message = self::getI18nConstraintValue('isinvalidfield') . ' ' .$message;
		}
		return str_replace('{field}', $fieldLabel, $message);
	}
	
	private static $i18n = array();
	
	
	/**
	 * @param string $key
	 */
	public static function getI18nConstraintValue($key)
	{
		if (!isset(self::$i18n[$key]))
		{
			self::$i18n[$key] = LocaleService::getInstance()->trans('f.constraints.'. strtolower($key), array('ucf'));
		}
		return self::$i18n[$key];
	}
	
	/**
	 * @deprecated alias of required
	 * @param array $params <type => integer>
	 */
	public static function blank($params = array())
	{
		return self::required($params);		
	}
	
	/**
	 * @param array $params <type => integer>
	 */
	public static function required($params = array())
	{
		$c = new Zend_Validate_NotEmpty($params);
		$c->setMessage(self::getI18nConstraintValue(Zend_Validate_NotEmpty::IS_EMPTY));
		return $c;		
	}
	
	/**
	 * @param array $params
	 * @return Zend_Validate_Interface
	 */
	public static function email($params = array())
	{	
		$params['hostname'] = self::hostname($params);	
		$c = new Zend_Validate_EmailAddress($params);
		if (!isset($params['messages']))
		{ 
			foreach (array(Zend_Validate_EmailAddress::INVALID, 
				Zend_Validate_EmailAddress::INVALID_FORMAT, 
				Zend_Validate_EmailAddress::INVALID_HOSTNAME, 
				Zend_Validate_EmailAddress::INVALID_MX_RECORD, 
				Zend_Validate_EmailAddress::INVALID_SEGMENT, 
				Zend_Validate_EmailAddress::DOT_ATOM, 
				Zend_Validate_EmailAddress::QUOTED_STRING, 
				Zend_Validate_EmailAddress::INVALID_LOCAL_PART, 
				Zend_Validate_EmailAddress::LENGTH_EXCEEDED) as $key) 
			{
				$c->setMessage(self::getI18nConstraintValue($key), $key);
			}
		}
		$c->setDisableTranslator(true);
		return $c;
	}
	
	/**
	 * @param array $params <max => maxLength || parameter => maxLength>
	 * @return Zend_Validate_Interface
	 */
	public static function maxSize($params = array())
	{		
		if (isset($params['parameter'])) 
		{
			$params['max'] = intval($params['parameter']);
		}
		$messages = array(
		Zend_Validate_StringLength::INVALID => self::getI18nConstraintValue(Zend_Validate_StringLength::INVALID),
		Zend_Validate_StringLength::TOO_LONG => self::getI18nConstraintValue(Zend_Validate_StringLength::TOO_LONG));
		
		$c = new Zend_Validate_StringLength($params);
		$c->setMessages($messages);
		$c->setDisableTranslator(true);
		return $c;
	}	
	
	/**
	 * @param array $params <min => minLength || parameter => minLength>
	 * @return Zend_Validate_Interface
	 */
	public static function minSize($params = array())
	{		
		if (isset($params['parameter'])) 
		{
			$params['min'] = intval($params['parameter']);
		}
		$messages = array(
		Zend_Validate_StringLength::INVALID => self::getI18nConstraintValue(Zend_Validate_StringLength::INVALID),
		Zend_Validate_StringLength::TOO_SHORT => self::getI18nConstraintValue(Zend_Validate_StringLength::TOO_SHORT));
		
		$c = new Zend_Validate_StringLength($params);
		$c->setMessages($messages);
		$c->setDisableTranslator(true);
		return $c;
	}

	/**
	 * @param array $params <modelName => modelName, propertyName => propertyName, [documentId => documentId]>
	 * @return Zend_Validate_Interface
	 */
	public static function unique($params = array())
	{
		$c = new change_UniqueConstraint($params);
		return $c;		
	}
	
	/**
	 * @param array $params <pattern => pattern, [message => message] || parameter => pattern#message>
	 * @return Zend_Validate_Interface
	 */	
	public static function matches($params = array())
	{
		if (isset($params['parameter']) && is_string($params['parameter']))
		{
			$pattern = $params['parameter'];
			if (($splitIndex = strpos($pattern, '#')) !== false)
			{
				$params['message'] = substr($pattern, $splitIndex + 1);
				$pattern = substr($pattern, 0, $splitIndex);
			}
			$params['pattern'] = '#' . $pattern . '#';
		}

		$c = new Zend_Validate_Regex($params);
		if (isset($params['message']) && is_string($params['message']))
		{
			$c->setMessage($params['message']);
		}
		else
		{
			$messages = array(
				Zend_Validate_Regex::NOT_MATCH => self::getI18nConstraintValue(Zend_Validate_Regex::NOT_MATCH));		
			$c->setMessages($messages);
		}
		return $c;
	}
	
	/**
	 * @param array $params <min => min || parameter => min>
	 * @return Zend_Validate_Interface
	 */	
	public static function min($params = array())
	{
		if (isset($params['parameter']))
		{
			$params['min'] = $params['parameter'];
		}
		$c = new change_MinConstraint($params);
		$messages = array(
				change_MinConstraint::NOT_GREATER => self::getI18nConstraintValue(change_MinConstraint::NOT_GREATER));		
		$c->setMessages($messages);	
		return $c;
	}

	/**
	 * @param array $params <max => max || parameter => max>
	 * @return Zend_Validate_Interface
	 */	
	public static function max($params = array())
	{
		if (isset($params['parameter']))
		{
			$params['max'] = $params['parameter'];
		}
		$c = new change_MaxConstraint($params);
		$messages = array(
			change_MaxConstraint::NOT_LESS => self::getI18nConstraintValue(change_MaxConstraint::NOT_LESS));	
		$c->setMessages($messages);	
		return $c;
	}

	/**
	 * @param array $params <min => min, max => max, [inclusive => inclusive] || parameter => min..max>
	 * @return Zend_Validate_Interface
	 */	
	public static function range($params = array())
	{
		if (isset($params['parameter']))
		{
			list($min, $max) = explode('..', $params['parameter']);
			$params['min'] = $min;
			$params['max'] = $max;
			$params['inclusive'] = true;
		}
		
		$c = new Zend_Validate_Between($params);
		$c->setDisableTranslator(true);
		$messages = array(
			Zend_Validate_Between::NOT_BETWEEN => self::getI18nConstraintValue(Zend_Validate_Between::NOT_BETWEEN),
			Zend_Validate_Between::NOT_BETWEEN_STRICT => self::getI18nConstraintValue(Zend_Validate_Between::NOT_BETWEEN_STRICT));		
		$c->setMessages($messages);	
		return $c;
	}
	
	/**
	 * 
	 * @param array $params<allow => Zend_Validate_Hostname::ALLOW_*>
	 */
	public static function hostname($params = array())
	{
		$c = new Zend_Validate_Hostname($params);
		foreach (array(Zend_Validate_Hostname::INVALID, 
				Zend_Validate_Hostname::CANNOT_DECODE_PUNYCODE, 
				Zend_Validate_Hostname::INVALID_DASH, 
				Zend_Validate_Hostname::INVALID_HOSTNAME, 
				Zend_Validate_Hostname::INVALID_HOSTNAME_SCHEMA, 
				Zend_Validate_Hostname::INVALID_LOCAL_NAME, 
				Zend_Validate_Hostname::INVALID_URI, 
				Zend_Validate_Hostname::IP_ADDRESS_NOT_ALLOWED, 
				Zend_Validate_Hostname::LOCAL_NAME_NOT_ALLOWED,
				Zend_Validate_Hostname::UNDECIPHERABLE_TLD,
				Zend_Validate_Hostname::UNKNOWN_TLD,
				) as $key) 
			{
				$c->setMessage(self::getI18nConstraintValue($key), $key);
			}		
		$c->setDisableTranslator(true);
		return $c;
		

	}
	
	/**
	 * @param array $params
	 * @return Zend_Validate_Interface
	 */
	public static function integer($params = array())
	{
		$c = new Zend_Validate_Digits($params);
		$c->setMessage(self::getI18nConstraintValue(Zend_Validate_Digits::NOT_DIGITS), Zend_Validate_Digits::NOT_DIGITS);
		return $c;		
	}	
}

class change_WrappedValidatorConstraint implements Zend_Validate_Interface
{
	/**
	 * @var validation_ValidatorImpl
	 */
	protected $wrappedValidator;
	
	protected $name;
	
	protected $messages = array();
	
	/**
	 * @param array $params
	 */
	public function __construct($name, $params = array())
	{
		$this->name = $name;
		if (!isset($params['parameter'])) {$params['parameter'] = 'true';}
		
		$definition = $name . ':' . $params['parameter'];
		Framework::warn(__METHOD__ . ' deprecated ' . $definition . ' validator.');
		$constraintsParser = new validation_ContraintsParser();
		$validators = $constraintsParser->getValidatorsFromDefinition($definition);
		if (count($validators) !== 1)
		{
			throw new Exception("Invalid validator definition: ". $definition);
		}
		$this->wrappedValidator = $validators[0];
	}	

	
	/**
	 * @param  mixed $value
	 * @return boolean
	 */	
 	public function isValid($value)
	{
		$this->messages = array();
		
	  	$property   = new validation_Property('{field}', $value);
		$errors	 = new validation_Errors();
		$this->wrappedValidator->validate($property, $errors);
		
		if ($errors->count() > 0)
		{
			$this->messages = $errors->getArrayCopy();
			return false;
		}
		return true;
	}
	
	/**
	 * @return array
	 */
	public function getMessages()
	{
		return $this->messages;
	}
}



class change_MinConstraint extends Zend_Validate_GreaterThan
{
	/**
	 * Returns true if and only if $value is greater or equals than min option
	 *
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$this->_setValue($value);
		if ($this->_min > $value) {
			$this->_error(self::NOT_GREATER);
			return false;
		}
		return true;
	}	
}

class change_MaxConstraint extends Zend_Validate_LessThan
{
	/**
	 * Defined by Zend_Validate_Interface
	 *
	 * Returns true if and only if $value is less or equals than max option
	 *
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$this->_setValue($value);
		if ($this->_max <= $value) {
			$this->_error(self::NOT_LESS);
			return false;
		}
		return true;
	}	
}