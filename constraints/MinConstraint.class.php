<?php
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