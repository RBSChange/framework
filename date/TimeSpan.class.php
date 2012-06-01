<?php
class date_TimeSpan
{

	public function __construct($years = 0, $months = 0, $days = 0, $hours = 0, $minutes = 0, $seconds = 0)
	{
		$this->setYears($years);
		$this->setMonths($months);
		$this->setDays($days);
		$this->setHours($hours);
		$this->setMinutes($minutes);
		$this->setSeconds($seconds);
	}

	/**
	 * @param integer $seconds
	 */
	public function setSeconds($seconds)
	{
		$this->seconds = intval($seconds);
	}

	/**
	 * @return integer
	 */
	public function getSeconds()
	{
		return $this->seconds;
	}

	/**
	 * @param integer $minutes
	 */
	public function setMinutes($minutes)
	{
		$this->minutes = intval($minutes);
	}

	/**
	 * @return integer
	 */
	public function getMinutes()
	{
		return $this->minutes;
	}

	/**
	 * @param integer $hours
	 */
	public function setHours($hours)
	{
		$this->hours = intval($hours);
	}

	/**
	 * @return integer
	 */
	public function getHours()
	{
		return $this->hours;
	}

	/**
	 * @param integer $days
	 */
	public function setDays($days)
	{
		$this->days = intval($days);
	}

	/**
	 * @return integer
	 */
	public function getDays()
	{
		return $this->days;
	}

	/**
	 * @param integer $months
	 */
	public function setMonths($months)
	{
		$this->months = intval($months);
	}

	/**
	 * @return integer
	 */
	public function getMonths()
	{
		return $this->months;
	}

	/**
	 * @param integer $years
	 */
	public function setYears($years)
	{
		$this->years = intval($years);
	}

	/**
	 * @return integer
	 */
	public function getYears()
	{
		return $this->years;
	}

	public function setNumberOfSeconds($number)
	{
		$year = 31536000;
		$month = 2592000;
		$day = 86400;
		$hour = 3600;
		$minute = 60;
		
		$this->years = (int) ($number /$year);
		$number -= $year * $this->years;
		$this->months = (int) ($number / $month);
		$number -= $month * $this->months;
		$this->days = (int) ($number / $day);
		$number -= $day * $this->days;
		$this->hours = (int) ($number / $hour);
		$number -= $hour * $this->hours;
		$this->minutes = (int) ($number / $minute);
		$this->seconds = $number - $minute * $this->minutes;
	}

	/**
	 * Builds and returns a string representation of the TimeSpan object.
	 * @return string For example: years:0 months:0 days:3 hours:8 minutes:5 seconds:45
	 */
	public function toString()
	{
		$str = "";
		if ($this->years)
		{
			$str .= $this->years." years ";
		}
		if ($this->months)
		{
			$str .= $this->months." months ";
		}
		if ($this->days)
		{
			$str .= $this->days." days ";
		}
		if ($this->hours)
		{
			$str .= $this->hours." hours ";
		}
		if ($this->minutes)
		{
			$str .= $this->minutes." minutes ";
		}
		if ($this->seconds)
		{
			$str .= $this->seconds." seconds";
		}
		return $str;
	}

	public function __toString()
	{
		return $this->toString();
	}


	// --- PRIVATE STUFF -----------------------------------------------------------


	/**
	 * @var Integer
	 */
	private $seconds = 0;

	/**
	 * @var integer
	 */
	private $minutes = 0;

	/**
	 * @var integer
	 */
	private $hours = 0;

	/**
	 * @var integer
	 */
	private $days = 0;

	/**
	 * @var integer
	 */
	private $months = 0;

	/**
	 * @var integer
	 */
	private $years = 0;
}