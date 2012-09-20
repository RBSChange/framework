<?php
class change_DomainConstraint extends \Zend\Validator\AbstractValidator
{
	const INVALID = 'domainInvalid';

	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::INVALID => self::INVALID);
		$params += change_Constraints::getDefaultOptions();
		parent::__construct($params);
	}
	
	/**
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		if (!is_string($value) || empty($value))
		{
			$this->setValue('');
			$this->error(self::INVALID);
			return false;
		}
		if (!preg_match('/(^([a-zA-Z0-9]([a-zA-Z0-9\-]*\.))*[a-zA-Z]+$)|(^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$)/', $value))
		{
			$this->setValue($value);
			$this->error(self::INVALID);
			return false;
		}
		return true;
	}
}