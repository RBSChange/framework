<?php
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