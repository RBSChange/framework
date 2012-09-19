<?php
abstract class change_Constraints 
{
	/**
	 * @param string $name
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
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
	 * @param \Zend\Validator\ValidatorInterface $constraint
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
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public static function required($params = array())
	{
		$c = new \Zend\Validator\NotEmpty($params);
		$c->setMessage(self::getI18nConstraintValue(\Zend\Validator\NotEmpty::IS_EMPTY));
		return $c;		
	}
	
	/**
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public static function email($params = array())
	{	
		$params['hostname'] = self::hostname($params);	
		$c = new \Zend\Validator\EmailAddress($params);
		if (!isset($params['messages']))
		{ 
			foreach (array(\Zend\Validator\EmailAddress::INVALID, 
				\Zend\Validator\EmailAddress::INVALID_FORMAT, 
				\Zend\Validator\EmailAddress::INVALID_HOSTNAME, 
				\Zend\Validator\EmailAddress::INVALID_MX_RECORD, 
				\Zend\Validator\EmailAddress::INVALID_SEGMENT, 
				\Zend\Validator\EmailAddress::DOT_ATOM, 
				\Zend\Validator\EmailAddress::QUOTED_STRING, 
				\Zend\Validator\EmailAddress::INVALID_LOCAL_PART, 
				\Zend\Validator\EmailAddress::LENGTH_EXCEEDED) as $key) 
			{
				$c->setMessage(self::getI18nConstraintValue($key), $key);
			}
		}
		$c->setDefaultTranslator();
		return $c;
	}
	
	/**
	 * @param array $params <max => maxLength || parameter => maxLength>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public static function maxSize($params = array())
	{		
		if (isset($params['parameter'])) 
		{
			$params['max'] = intval($params['parameter']);
		}
		$messages = array(
		\Zend\Validator\StringLength::INVALID => self::getI18nConstraintValue(\Zend\Validator\StringLength::INVALID),
		\Zend\Validator\StringLength::TOO_LONG => self::getI18nConstraintValue(\Zend\Validator\StringLength::TOO_LONG));
		
		$c = new \Zend\Validator\StringLength($params);
		$c->setMessages($messages);
		$c->setDefaultTranslator();
		return $c;
	}	
	
	/**
	 * @param array $params <min => minLength || parameter => minLength>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public static function minSize($params = array())
	{		
		if (isset($params['parameter'])) 
		{
			$params['min'] = intval($params['parameter']);
		}
		$messages = array(
		\Zend\Validator\StringLength::INVALID => self::getI18nConstraintValue(\Zend\Validator\StringLength::INVALID),
		\Zend\Validator\StringLength::TOO_SHORT => self::getI18nConstraintValue(\Zend\Validator\StringLength::TOO_SHORT));
		
		$c = new \Zend\Validator\StringLength($params);
		$c->setMessages($messages);
		$c->setDefaultTranslator();
		return $c;
	}

	/**
	 * @param array $params <modelName => modelName, propertyName => propertyName, [documentId => documentId]>
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public static function unique($params = array())
	{
		$c = new change_UniqueConstraint($params);
		return $c;		
	}
	
	/**
	 * @param array $params <pattern => pattern, [message => message] || parameter => pattern#message>
	 * @return \Zend\Validator\ValidatorInterface
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

		$c = new \Zend\Validator\Regex($params);
		if (isset($params['message']) && is_string($params['message']))
		{
			$c->setMessage($params['message']);
		}
		else
		{
			$messages = array(
				\Zend\Validator\Regex::NOT_MATCH => self::getI18nConstraintValue(\Zend\Validator\Regex::NOT_MATCH));		
			$c->setMessages($messages);
		}
		return $c;
	}
	
	/**
	 * @param array $params <min => min || parameter => min>
	 * @return \Zend\Validator\ValidatorInterface
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
	 * @return \Zend\Validator\ValidatorInterface
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
	 * @return \Zend\Validator\ValidatorInterface
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
		
		$c = new \Zend\Validator\Between($params);
		$c->setDefaultTranslator();
		$messages = array(
			\Zend\Validator\Between::NOT_BETWEEN => self::getI18nConstraintValue(\Zend\Validator\Between::NOT_BETWEEN),
			\Zend\Validator\Between::NOT_BETWEEN_STRICT => self::getI18nConstraintValue(\Zend\Validator\Between::NOT_BETWEEN_STRICT));		
		$c->setMessages($messages);	
		return $c;
	}
	
	/**
	 * 
	 * @param array $params<allow => \Zend\Validator\Hostname::ALLOW_*>
	 */
	public static function hostname($params = array())
	{
		$c = new \Zend\Validator\Hostname($params);
		foreach (array(\Zend\Validator\Hostname::INVALID, 
				\Zend\Validator\Hostname::CANNOT_DECODE_PUNYCODE, 
				\Zend\Validator\Hostname::INVALID_DASH, 
				\Zend\Validator\Hostname::INVALID_HOSTNAME, 
				\Zend\Validator\Hostname::INVALID_HOSTNAME_SCHEMA, 
				\Zend\Validator\Hostname::INVALID_LOCAL_NAME, 
				\Zend\Validator\Hostname::INVALID_URI, 
				\Zend\Validator\Hostname::IP_ADDRESS_NOT_ALLOWED, 
				\Zend\Validator\Hostname::LOCAL_NAME_NOT_ALLOWED,
				\Zend\Validator\Hostname::UNDECIPHERABLE_TLD,
				\Zend\Validator\Hostname::UNKNOWN_TLD,
				) as $key) 
			{
				$c->setMessage(self::getI18nConstraintValue($key), $key);
			}		
		$c->setDefaultTranslator();
		return $c;
		

	}
	
	/**
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public static function integer($params = array())
	{
		$c = new \Zend\Validator\Digits($params);
		$c->setMessage(self::getI18nConstraintValue(\Zend\Validator\Digits::NOT_DIGITS), \Zend\Validator\Digits::NOT_DIGITS);
		return $c;		
	}	
}

class change_WrappedValidatorConstraint implements \Zend\Validator\ValidatorInterface
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



class change_MinConstraint extends \Zend\Validator\GreaterThan
{
	/**
	 * Returns true if and only if $value is greater or equals than min option
	 *
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$this->setValue($value);
		if ($this->min > $value) {
			$this->error(\Zend\Validator\GreaterThan::NOT_GREATER);
			return false;
		}
		return true;
	}	
}

class change_MaxConstraint extends \Zend\Validator\LessThan
{
	/**
	 * Defined by \Zend\Validator\ValidatorInterface
	 *
	 * Returns true if and only if $value is less or equals than max option
	 *
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$this->setValue($value);
		if ($this->max <= $value) {
			$this->error(\Zend\Validator\LessThan::NOT_LESS);
			return false;
		}
		return true;
	}	
}