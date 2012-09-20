<?php
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