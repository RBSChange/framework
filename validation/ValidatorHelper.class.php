<?php
abstract class validation_ValidatorHelper
{

	/**
	 * Simply validates a piece of data with the given constraints definition string.
	 * If the validation_Property is correctly used (ie. name is set), $errors will
	 * contain the localized error messages.
	 *
	 * @param validation_Property $field
	 * @param string $definition
	 * @param validation_Errors $errors
	 * @return boolean
	 */
	public static function validate(validation_Property $field, $definition, validation_Errors &$errors)
	{
		$parser = new validation_ContraintsParser();
		$validators = $parser->getValidatorsFromDefinition($definition);
		foreach ($validators as $validator)
		{
			$value = $field->getValue();
			if (!($validator instanceof validation_BlankValidator) && ($value === null || $value === ''))
			{
				continue;
			}
			$validator->validate($field, $errors);
		}
		return ($errors->isEmpty());
	}

	/**
	 * @param string $definition
	 * @param f_mvc_Bean|array<String, mixed> $beanOrArray
	 * @param array<String validation_ValidationErrors> $errors
	 * @return boolean
	 */
	public static function validateBean($definition, $beanOrArray, &$errors)
	{
		//propEq:password,confirmPassword
		$matches = null;
		if (!preg_match('/^(\w+):(.*)$/', $definition, $matches))
		{
			throw new Exception("Unable to parse beanValidator rule ".$definition);
				
		}
		$validatorClassName = "validation_".ucfirst($matches[1])."BeanValidator";
		if (!f_util_ClassUtils::classExists($validatorClassName))
		{
			throw new Exception("Could not find validator $validatorClassName");
		}
		$validatorClass = new ReflectionClass($validatorClassName);
		if (!$validatorClass->implementsInterface("validation_BeanValidator"))
		{
			throw new Exception("Invalid validation_BeanValidator $validatorClassName");
		}
		$validator = $validatorClass->newInstance();
		$validator->setParameter($matches[2]);
		return $validator->validate($beanOrArray, $errors);
	}


	/**
	 * Simply validates a piece of data with the given constraints definition string.
	 * No error message is available, only the return value (true or false).
	 *
	 * @param mixed $value
	 * @param string $definition
	 * @return boolean
	 */
	public static function validateValue($value, $definition)
	{
		$errors = new validation_Errors();
		return self::validate(new validation_Property('', $value), $definition, $errors);
	}


	/**
	 * Returns the validators associated to the property $propertyName of the document $document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array<validation_Validator>
	 */
	public static function getDocumentPropertyValidators($document, $propertyName)
	{
		$model = $document->getPersistentModel();
		$property = $model->getProperty($propertyName);
		$constraints = $property->getConstraints();
		return validation_ContraintsParser::getValidatorsFromDefinition($constraints);
	}


	public static function generateXULFormConstraints($documentModelName, $propertyName)
	{
		$model = f_component_DocumentModel::getInstanceFromDocumentType($documentModelName);
		$parser = new validation_ContraintsParser();
		$constraintArray = $parser->getConstraintArrayFromDefinition($model->getComponentConstraints($propertyName));
		$xml = array();
		foreach ($constraintArray as $name => $constraint)
		{
			$xml[] = '<constraint name="'.$name.'">'.$constraint.'</constraint>';
		}
		return join("\n", $xml);
	}
}
