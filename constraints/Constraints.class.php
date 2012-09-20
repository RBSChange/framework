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
	
	private static $defaultOptions;
	
	/**
	 * @return array
	 */
	public static function getDefaultOptions()
	{
		if (self::$defaultOptions === null)
		{
			$t = change_ConstraintsTranslator::factory(array());
			self::$defaultOptions = array('Translator' => $t, 'translatorTextDomain' => 'f.constraints');
			 \Zend\Validator\AbstractValidator::setDefaultTranslatorTextDomain('f.constraints');
			 \Zend\Validator\AbstractValidator::setDefaultTranslator($t);
		}
		return array();
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
		$params += self::getDefaultOptions();
		$c = new \Zend\Validator\NotEmpty($params);
		return $c;		
	}
	
	/**
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public static function email($params = array())
	{	
		$params['hostname'] = self::hostname($params);	
		$params += self::getDefaultOptions();
		$c = new \Zend\Validator\EmailAddress($params);
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
		$params += self::getDefaultOptions();
		$c = new \Zend\Validator\StringLength($params);
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
		$params += self::getDefaultOptions();
		$c = new \Zend\Validator\StringLength($params);
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
		$params += self::getDefaultOptions();
		$c = new \Zend\Validator\Regex($params);
		if (isset($params['message']) && is_string($params['message']))
		{
			$c->setMessage($params['message']);
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
		$params += self::getDefaultOptions();
		$c = new change_MinConstraint($params);
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
		$params += self::getDefaultOptions();
		$c = new change_MaxConstraint($params);
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
		$params += self::getDefaultOptions();
		$c = new \Zend\Validator\Between($params);
		return $c;
	}
	
	/**
	 * 
	 * @param array $params<allow => \Zend\Validator\Hostname::ALLOW_*>
	 */
	public static function hostname($params = array())
	{
		$params += self::getDefaultOptions();
		$c = new \Zend\Validator\Hostname($params);	
		return $c;
	}
	
	/**
	 * @param array $params
	 * @return \Zend\Validator\ValidatorInterface
	 */
	public static function integer($params = array())
	{
		$params += self::getDefaultOptions();
		$c = new \Zend\Validator\Digits($params);
		return $c;		
	}	
}