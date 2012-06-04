<?php
abstract class validation_ValidatorImpl implements validation_Validator
{
	/**
	 * @var mixed
	 */
	private $parameter = null;

	/**
	 * @var boolean
	 */
	private $hasBeenRejected;

	/**
	 * @var boolean
	 */
	protected $usesReverseMode = false;


	/**
	 * Sets the value of the unique validator's parameter.
	 *
	 * @param mixed $value
	 */
	public function setParameter($value)
	{
		$this->parameter = $value;
	}


	/**
	 * Returns the validator's unique parameter.
	 *
	 * @return mixed
	 *
	 * Returned value may be a string, an integer, a float, an array or a validation_Range instance.
	 */
	public final function getParameter()
	{
		return $this->parameter;
	}


	/**
	 * Validates the property $property and stores the errors in $errors.
	 *
	 * @param validation_Property $property
	 * @param validation_Errors $errors
	 *
	 * @return boolean
	 */
	public final function validate($property, $errors)
	{
		$this->hasBeenRejected = false;
		if ( ! $property instanceof validation_Property )
		{
			$property = new validation_Property(null, $property);
		}
		$errorsCount = $errors->count();
		$this->doValidate($property, $errors);
		if (!$this->hasBeenRejected && $this->usesReverseMode)
		{
			$errors->rejectValue($property->getName(), $this->getMessage());
		}
		return $errors->count() === $errorsCount;
	}


	abstract protected function doValidate(validation_Property $field, validation_Errors $errors);


	/**
	 * Returns the error message.
	 *
	 * @return string
	 */
	protected function getMessage($args = null)
	{
		$substitution = array('param' => $this->getParameter());
		if ($args !== null)
		{
			$substitution = array_merge($substitution, $args);
		}
		return LocaleService::getInstance()->trans($this->getMessageCode(),	array('ucf'), $substitution);
	}

	/**
	 * Returns the localization key of the error message.
	 *
	 * @return string
	 */
	protected function getMessageCode()
	{
		// substr(get_class($this), 11, -9) to remove 'validation_' prefix and 'Validator' suffix
		$key = 'f.validation.validator.'.substr(get_class($this), 11, -9).'.message';
		return ($this->usesReverseMode) ? $key .= '.reversed' : $key;
	}

	/**
	 * Called to reject the value.
	 *
	 * @param string $name The $value's name.
	 * @param validation_Errors $errors The errors stack.
	 * @param array<String, String> $args optionnal message arguments
	 */
	protected final function reject($name, validation_Errors $errors, $args = null)
	{
		if (!$this->usesReverseMode)
		{
			$errors->rejectValue($name, $this->getMessage(), $args);
		}
		$this->hasBeenRejected = true;
	}


	/**
	 * Reverses the validator so that the conditions are reversed.
	 *
	 * @param boolean $bool
	 */
	public final function setReverseMode($bool = true)
	{
		if ($this->canBeReversed())
		{
			$this->usesReverseMode = $bool === true ? true : false;
		}
		elseif ($bool)
		{
			throw new Exception(get_class($this)." can not be reversed");
		}
	}

	/**
	 * @return Boolean
	 */
	protected function canBeReversed()
	{
		return true;
	}

	/**
	 * Indicates whether the validator is in reverse mode or not.
	 *
	 * @return boolean
	 */
	public final function usesReverseMode()
	{
		return $this->usesReverseMode;
	}
}
