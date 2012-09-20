<?php
class change_UrlConstraint extends \Zend\Validator\AbstractValidator
{
	const INVALID = 'urlInvalid';

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
		if (!preg_match('/^[a-z]+:\/\/([a-z0-9\-\.]+\.[a-z0-9]+)|localhost(:[\d]{1,5})?(\/.*)?$/', $value))
		{
			$this->setValue($value);
			$this->error(self::INVALID);
			return false;
		}
		return true;
	}
}