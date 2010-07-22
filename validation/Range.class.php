<?php
/**
 * @package framework.validation
 */
class validation_Range
{
	private $min;
	private $max;
	private $useStrings;
	
	public function __construct($min, $max)
	{
		// TODO intbonjf 2007-02-21: swap min/max values if needed
		if (is_string($min) && is_string($max))
		{
			$this->setMin($min);
			$this->setMax($max);
			$this->useStrings = true;
		}
		else if (is_numeric($min) && is_numeric($max))
		{
			$this->setMin($min);
			$this->setMax($max);
			$this->useStrings = false;
		}
		else 
		{
			throw new IllegalArgumentException('$min/$max must both be valid numbers or strings');
		}
	}
	
	/**
	 * @param mixed $min
	 */
	public function setMin($min)
	{
		$this->min = $min;
	}
	
	/**
	 * @return mixed
	 */
	public function getMin()
	{
		return $this->min;
	}
	
	/**
	 * @param mixed $max
	 */
	public function setMax($max)
	{
		$this->max = $max;
	}
	
	/**
	 * @return mixed
	 */
	public function getMax()
	{
		return $this->max;
	}
}