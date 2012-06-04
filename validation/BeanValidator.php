<?php
interface validation_BeanValidator
{
	/**
	 * @param mixed $parameter
	 * @return void
	 */
	function setParameter($parameter);
	
	/**
	 * @param f_mvc_Bean|array<String, mixed> $beanOrArray
	 * @param validation_Errors $errors
	 * @return array(Boolean, $fieldNames)
	 */
	function validate($beanOrArray, &$errors);
}

class validation_PropEqBeanValidator implements validation_BeanValidator
{
	private $parameter;
	
	function setParameter($parameter)
	{
		$this->parameter = $parameter;
	}
	
	/**
	 * @param f_mvc_Bean|array<String, mixed> $beanOrArray
	 * @param array<String, validation_ValidationErrors $errors
	 * @return void
	 */
	function validate($beanOrArray, &$errors)
	{
		$properties = explode(",", $this->parameter);
		if (f_util_ArrayUtils::isEmpty($properties) || count($properties) !== 2)
		{
			throw new Exception(__METHOD__.": invalid parameter ".$this->parameter);
		}
		if (is_array($beanOrArray))
		{
			$value1 = $beanOrArray[$properties[0]];
			$value2 = $beanOrArray[$properties[1]];
			// TODO...
			$field1Label = $properties[0];
			$field2Label = $properties[1];
		}
		elseif (is_object($beanOrArray))
		{
			$bean = BeanUtils::getBean($beanOrArray);
			$value1 = BeanUtils::getProperty($bean, $properties[0]);
			$value2 = BeanUtils::getProperty($bean, $properties[1]);
			$field1Label = BeanUtils::getPropertyInfo($bean, $properties[0])->getLabelKey();
			$field2Label = BeanUtils::getPropertyInfo($bean, $properties[1])->getLabelKey();
		}
		else
		{
			throw new IllegalArgumentException(__METHOD__.": expected object or array");
		}
		
		$ret = f_util_ObjectUtils::equals($value1, $value2);
		if (!$ret)
		{
			$ls = LocaleService::getInstance();
			$substitution = array("field1" => $ls->trans($field1Label), "field2" => $ls->trans($field2Label));
			$errorMsg = $ls->trans('f.validation.validator.propeq.message', array('ucf'), $substitution);
			if ($errors === null)
			{
				$errors = array();
			} 
			$validationErrors = new validation_Errors();
			$validationErrors->append($errorMsg);
			
			// we add the same error for property1, property2 and as general error
			$errors[$properties[0]] = $validationErrors;
			$errors[$properties[1]] = $validationErrors;
			$errors[] = $validationErrors;
		}
		return $ret;
	}
}

class validation_RequiredIfBeanValidator implements validation_BeanValidator
{
	private $parameter;
	
	function setParameter($parameter)
	{
		$this->parameter = $parameter;
	}
	
	/**
	 * @param f_mvc_Bean|array<String, mixed> $beanOrArray
	 * @param array<String, validation_ValidationErrors $errors
	 * @return void
	 */
	function validate($beanOrArray, &$errors)
	{
		$properties = explode(",", $this->parameter);
		if (f_util_ArrayUtils::isEmpty($properties) || count($properties) !== 2)
		{
			throw new Exception(__METHOD__.": invalid parameter ".$this->parameter);
		}
		if (is_array($beanOrArray))
		{
			$value1 = $beanOrArray[$properties[0]];
			$value2 = $beanOrArray[$properties[1]];
			// TODO...
			$field1Label = $properties[0];
			$field2Label = $properties[1];
		}
		elseif (is_object($beanOrArray))
		{
			$bean = BeanUtils::getBean($beanOrArray);
			$value1 = BeanUtils::getProperty($bean, $properties[0]);
			$value2 = BeanUtils::getProperty($bean, $properties[1]);
			$field1Label = BeanUtils::getPropertyInfo($bean, $properties[0])->getLabelKey();
			$field2Label = BeanUtils::getPropertyInfo($bean, $properties[1])->getLabelKey();
		}
		else
		{
			throw new IllegalArgumentException(__METHOD__.": expected object or array");
		}
		
		$ret = f_util_ObjectUtils::isEmpty($value2) || !f_util_ObjectUtils::isEmpty($value1);
		if (!$ret)
		{
			$ls = LocaleService::getInstance();
			$substitution = array("field" => $ls->trans($field1Label), "fieldIf" => $ls->trans($field2Label));
			$errorMsg = $ls->trans('f.validation.validator.requiredif.message', array('ucf'), $substitution);
			if ($errors === null)
			{
				$errors = array();
			} 
			$validationErrors = new validation_Errors();
			$validationErrors->append($errorMsg);
			
			// we add the same error for property1 and as general error
			$errors[$properties[0]] = $validationErrors;
			$errors[] = $validationErrors;
		}
		return $ret;
	}
}