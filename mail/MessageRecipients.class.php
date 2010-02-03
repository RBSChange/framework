<?php
/**
 * @package framework.mail
 */
class mail_MessageRecipients
{
	/**
	 * @var array
	 */
	private $to = null;


	/**
	 * @var array
	 */
	private $cc = null;


	/**
	 * @var array
	 */
	private $bcc = null;


	/**
	 * @param mixed $to Coma-separated list of email addresses or array of email addresses.
	 */
	public function setTo($to)
	{
		$this->to = $this->fixValue($to);
		return $this;
	}


	/**
	 * @param mixed $cc Coma-separated list of email addresses or array of email addresses.
	 *
	 * @throws IllegalArgumentException
	 */
	public function setCC($cc)
	{
		$this->cc = $this->fixValue($cc);
		return $this;
	}


	/**
	 * @param mixed $bcc Coma-separated list of email addresses or array of email addresses.
	 *
	 * @throws IllegalArgumentException
	 */
	public function setBCC($bcc)
	{
		$this->bcc = $this->fixValue($bcc);
		return $this;
	}


	/**
	 * @return Array<String>
	 */
	public function getTo()
	{
		return $this->to;
	}


	/**
	 * @return Array<String>
	 */
	public function getCC()
	{
		return $this->cc;
	}


	/**
	 * @return Array<String>
	 */
	public function getBCC()
	{
		return $this->bcc;
	}


	/**
	 * @return boolean
	 */
	public function hasTo()
	{
		return ! is_null($this->getTo());
	}


	/**
	 * @return boolean
	 */
	public function hasCC()
	{
		return ! is_null($this->getCC());
	}


	/**
	 * @return boolean
	 */
	public function hasBCC()
	{
		return ! is_null($this->getBCC());
	}


	/**
	 * Fixes $value so that it is an array of string.
	 *
	 * @param mixed $value String or array.
	 *
	 * @throws IllegalArgumentException
	 */
	private function fixValue($value)
	{
		if (is_string($value))
		{
			$trimValue = trim($value);
			if (f_util_StringUtils::isEmpty($trimValue))
			{
				return null;
			}
			if (strpos($trimValue, ',') !== false)
			{
				return $this->fixValue(explode(',', $trimValue));
			}
			else
			{
				return array($trimValue);
			}
		}
		else if (empty($value))
		{
			return null;
		}
		else if (is_array($value))
		{
			$values = array();			
			foreach ($value as $email) 
			{
				$trimValue = trim($email);
				if (!f_util_StringUtils::isEmpty($trimValue))
				{
					$values[] = $trimValue;			
				}
			}
			if (count($values) > 0)
			{
				return $values;
			}
			return null;
		}
		throw new IllegalArgumentException('$value must be a string containing email addresses or an array of email addresses.');
	}

	/**
	 * @return Boolean
	 */
	public final function isEmpty()
	{
		return !$this->hasTo() && !$this->hasCC() && !$this->hasBCC();
	}
}
