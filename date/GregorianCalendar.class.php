<?php
/**
 * A class that represents a datetime information.
 * This class is able to handle dates before 1970 as it does not use timestamps.
 *
 * @date Thu Jul 05 11:39:44 CEST 2007
 * @author intbonjf
 * @see http://fr.wikipedia.org/wiki/Calendrier_perp%C3%A9tuel
 */
class date_GregorianCalendar extends date_Calendar
{
	const ALLOWED_FORMAT_REGEXP =
		'#^(\\d{4})\-(\\d{1,2})\-(\\d{1,2})(\s+(\\d{1,2}):(\\d{1,2}):(\\d{1,2}))?$#'
		;

	/**
	 * Builds a new date_Calendar instance initialized from the given date as a
	 * string. Allowed format is the MySQL one: "Y-m-d H:i:s". Time information
	 * is optionnal, so "Y-m-d" is a valid argument.
	 *
	 * @param string $dateString The date string.
	 * @param Integer $timestamp
	 */
	protected function __construct($dateString = null, $timestamp = null)
	{
		if ( empty($dateString) )
		{
			$format = 'Y-m-d H:i:s';
			if ($timestamp === null)
			{
				$dateString = date($format);
			}
			else
			{
				$dateString = date($format, $timestamp);
			}
		}
		$matches = array();
		if ( ! preg_match(self::ALLOWED_FORMAT_REGEXP, $dateString, $matches) )
		{
			throw new InvalidDateException("1: ".$dateString);
		}

		// convert all fields into integers
		foreach ($matches as &$match) $match = intval($match);

		$this->setYear($matches[1]);
		try
		{
			$this->setMonth($matches[2]);       // may throw IllegalArgumentException
			$this->setDay($matches[3]);         // may throw IllegalArgumentException
			if (count($matches) == 8)
			{
				$this->setHour($matches[5]);    // may throw IllegalArgumentException
				$this->setMinute($matches[6]);  // may throw IllegalArgumentException
				$this->setSecond($matches[7]);  // may throw IllegalArgumentException
			}
		}
		catch (IllegalArgumentException $e)
		{
			throw new InvalidDateException($dateString."; ".$e->getMessage());
		}

		// Well...
		// Between December 9th and December 20th of the year 1582 was... a temporal vacuum.
		if ($this->getYear() == 1582 && $this->getMonth() == 12 && $this->getDay() > 9 && $this->getDay() < 20)
		{
			throw new InvalidDateException($dateString);
		}
	}


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// Initialization methods                                                 //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////

	/**
	 * @param string $dateString
	 * @return date_GregorianCalendar
	 */
	public static function getInstance($dateString = null)
	{
		return new date_GregorianCalendar($dateString);
	}
	
	/**
	 * @param Integer $timestamp
	 * @return date_GregorianCalendar
	 */
	public static function getInstanceFromTimestamp($timestamp)
	{
		return new date_GregorianCalendar(null, $timestamp);
	}

	/**
	 * Sets the time of the current date_Calendar to midnight.
     *
     * @return date_Calendar $this
	 */
	public function toMidnight()
	{
		$this->setHour(0);
		$this->setMinute(0);
		$this->setSecond(0);
		return $this;
	}

	/**
	 * Sets the time of the current date_Calendar to midday.
     *
     * @return date_Calendar $this
	 */
	public function toMidday()
	{
		$this->setHour(12);
		$this->setMinute(0);
		$this->setSecond(0);
		return $this;
	}


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// Basic getters and setters                                              //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////

	/**
	 * Returns the timestamp for the current date.
	 * If the date is too far in the past (before 1901), this method returns false
	 * because the timestamp does not exist for such a date.
	 *
	 * @return integer or false if the date could not be converted into a timestamp (dates before 1901).
	 */
	public function getTimestamp()
	{
		return mktime($this->getHour(), $this->getMinute(), $this->getSecond(), $this->getMonth(), $this->getDay(), $this->getYear());
	}


    /**
     * @param integer $second
     *
     * @return date_Calendar $this
     */
    public function setSecond($second)
    {
    	if (!is_integer($second) || $second < 0 || $second > 59)
    	{
    		throw new IllegalArgumentException("Second must be an integer between 0 and 59.");
    	}
    	if ($this->getSecond() != $second)
    	{
    		parent::setSecond($second);
	    	$this->update();
    	}
		return $this;
    }

    /**
     * @param integer $minute
     *
     * @return date_Calendar $this
     */
    public function setMinute($minute)
    {
    	if (!is_integer($minute) || $minute < 0 || $minute > 59)
    	{
    		throw new IllegalArgumentException("Minute must be an integer between 0 and 59.");
    	}
    	if ($this->getMinute() != $minute)
    	{
    		parent::setMinute($minute);
	    	$this->update();
    	}
		return $this;
    }

    /**
     * @param integer $hour
     *
     * @return date_Calendar $this
     */
    public function setHour($hour)
    {
    	if (!is_integer($hour) || $hour < 0 || $hour > 23)
    	{
    		throw new IllegalArgumentException("Hour must be an integer between 0 and 23.");
    	}
    	if ($this->getHour() != $hour)
    	{
    		parent::setHour($hour);
	    	$this->update();
    	}
		return $this;
    }

    /**
     * @param integer $day
     *
     * @return date_Calendar $this
     */
    public function setDay($day)
    {
    	if (!is_integer($day) || $day < 1 || $day > $this->getDaysInMonth())
    	{
    		throw new IllegalArgumentException("Day must be an integer between 1 and ".$this->getDaysInMonth().".");
    	}
    	if ($this->getDay() != $day)
    	{
    		parent::setDay($day);
    		$this->update();
    	}
		return $this;
    }

    /**
     * @param integer $month
     *
     * @return date_Calendar $this
     */
    public function setMonth($month)
    {
    	if (!is_integer($month) || $month < 1 || $month > 12)
    	{
    		throw new IllegalArgumentException("Month must be an integer between 1 and 12.");
    	}
    	if ($this->getMonth() != $month)
    	{
    		parent::setMonth($month);
	    	$this->update();
    	}
		return $this;
    }

    /**
     * @param integer $year
     *
     * @return date_Calendar $this
     */
    public function setYear($year)
    {
    	if (!is_integer($year) || $year < 0 || $year > 9999)
    	{
    		throw new IllegalArgumentException("Year must be an integer between 0 and 9999.");
    	}
    	if ($this->getYear() != $year)
    	{
	    	parent::setYear($year);
	    	$this->update();
    	}
		return $this;
    }


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// Advanced getters                                                       //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////

	/**
	 * Returns the century of the current date.
	 *
	 * @return integer
	 */
    public function getCentury()
    {
    	$century = intval($this->getYear() / 100);
    	if ($this->getYear() % 100)
    	{
    		$century++;
    	}
    	return $century;
    }

	/**
	 * Returns the number of days in the the current date's month.
	 *
	 * @return integer
	 */
	public function getDaysInMonth()
	{
		return $this->daysPerMonth[$this->getMonth() - 1];
	}

	/**
	 * Returns true is the current date is in a leap year.
	 *
	 * @return boolean true if the year of the current date is a leap year, false otherwise.
	 */
	public function isLeapYear()
	{
		return self::staticIsLeapYear($this->getYear());
	}

	/**
	 * Returns true is the given $year is a leap year.
	 *
	 * @param integer $year
	 * @return boolean
	 */
	public static function staticIsLeapYear($year)
	{
		// Leap years have been created in year 1582. Thus, there is no leap
		// years before 1582.
		return $year >= 1582 && ! ($year % 4) && ($year % 100 || ! ($year % 400));
	}

	/**
	 * Returns the day of the week as a number: 0=sunday, 6=saturday.
	 *
	 * @return integer
	 */
	public function getDayOfWeek()
	{
		return ($this->getSecularNumber() + $this->getAnnualNumber() + $this->getMensualNumber() + $this->getDay()) % 7;
	}

	/**
	 * Returns the day of year, from 0 for January 1st to 364 (or 365) for December 31st.
	 *
	 * @return integer
	 */
	public function getDayOfYear()
	{
		$dayOfYear = 0;
		for ($i=1 ; $i < $this->getMonth() ; $i++)
		{
			$dayOfYear += $this->daysPerMonth[$i - 1];
		}
		$dayOfYear = $dayOfYear + $this->getDay() -1;

		return $dayOfYear;
	}

	/**
	 * Indicates if the current date is the last day on the month.
	 *
	 * @return boolean
	 */
    public function isLastDayOfMonth()
    {
    	return $this->getDay() == $this->getDaysInMonth();
    }


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// Arithmetical methods for adding and substracting time to the date      //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////

	/**
	 * Adds an $amount of time to the given $field, which can be one among the
	 * following: ::SECOND, ::MINUTE, ::HOUR, ::DAY, ::MONTH, ::YEAR.
	 *
	 * @param integer $field
	 * @param integer $amount
	 * @return date_GregorianCalendar
	 */
	public function add($field, $amount)
	{
		switch ($field)
		{
			case self::SECOND :
				return $this->addSeconds($amount);
			case self::MINUTE :
				return $this->addMinutes($amount);
			case self::HOUR :
				return $this->addHours($amount);
			case self::DAY :
				return $this->addDays($amount);
			case self::MONTH :
				return $this->addMonths($amount);
			case self::YEAR :
				return $this->addYears($amount);
			default :
				throw new Exception('Unknown Calendar field: ' . $field);
		}
	}

	/**
	 * Substracts an $amount of time to the given $field, which can be one among
	 * the following: ::SECOND, ::MINUTE, ::HOUR, ::DAY, ::MONTH, ::YEAR.
	 *
	 * @param integer $field
	 * @param integer $amount
	 * @return date_GregorianCalendar
	 */
	public function sub($field, $amount)
	{
		switch ($field)
		{
			case self::SECOND :
				return $this->subSeconds($amount);
			case self::MINUTE :
				return $this->subMinutes($amount);
			case self::HOUR :
				return $this->subHours($amount);
			case self::DAY :
				return $this->subDays($amount);
			case self::MONTH :
				return $this->subMonths($amount);
			case self::YEAR :
				return $this->subYears($amount);
			default :
				throw new Exception('Unknown Calendar field: '.$field);
		}
	}


	/**
	 * Tells the calendar whether to use the smart end-of-month computing mode
	 * or not.
	 * When the smart end-of-month computing mode is ON and when adding (or
	 * substracting) months, the calendar will try to guess if the current date
	 * is the last day of the month; in that case, it will set the resulting
	 * date to the last day of the resulting month.
	 *
	 * Example: '2007-06-30' + 1 month = '2007-07-31' with smart end-of-month ON
	 * Example: '2007-06-30' + 1 month = '2007-07-30' with smart end-of-month OFF
	 *
	 * @param boolean $bool
	 *
	 * @return date_GregorianCalendar $this
	 */
    public function useSmartEndOfMonth($bool)
    {
    	$this->smartEndOfMonth = (bool)$bool;
    	return $this;
    }


// --- PRIVATE STUFF -----------------------------------------------------------


	/**
	 * Number of days per month.
	 *
	 * @var array
	 */
	private $daysPerMonth;

	/**
	 * @var boolean
	 */
	private $smartEndOfMonth = true;


	/**
	 * Adds a day to the current date.
	 */
	private function addDay()
	{
		$d = $this->getDay() + 1;
		$daysInMonth = $this->getDaysInMonth();
		if ($d > $daysInMonth)
		{
			$d = 1;
			$this->addMonths(1);
		}
		// Well... dates between 1582/12/09 and 1582/12/20 do not exist (in France).
		else if ($this->getYear() == 1582 && $this->getMonth() == 12 && $d == 10)
		{
			$d = 20;
		}
		parent::setDay($d);
		$this->update();
	}

	/**
	 * Removes a day to the current date.
	 */
	private function subDay()
	{
		$d = $this->getDay() - 1;
		if ($d < 1)
		{
			$this->subMonths(1);
			$d = $this->daysPerMonth[$this->getMonth() - 1];
		}
		// Well... dates between 1582/12/09 and 1582/12/20 do not exist (in France).
		else if ($this->getYear() == 1582 && $this->getMonth() == 12 && $d == 19)
		{
			$d = 9;
		}
		parent::setDay($d);
		$this->update();
	}


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// The three following methods are used to manage the perpetual calendar. //
	//                                                                        //
	// http://fr.wikipedia.org/wiki/Calendrier_perp%C3%A9tuel                 //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Computes and returns the secular number "nombre séculaire" for the
	 * current date.
	 *
	 * @see http://fr.wikipedia.org/wiki/Calendrier_perp%C3%A9tuel
	 *
	 * @return integer
	 */
	private function getSecularNumber()
	{
		// before 1582-12-09: use Julian calendar
		if ($this->getYear() <= 1582 && $this->getMonth() <= 12 && $this->getDay() <= 9)
		{
			return 19 - intval($this->getYear() / 100);
		}
		// after 1582-12-09: use Gregorian calendar
		else
		{
			$modulo = intval(floor($this->getYear() / 100)) % 4;
			return $modulo > 0 ? (5 - ($modulo-1)*2) : 0;
		}
	}

	/**
	 * Computes and returns the annual number "nombre annuel" for the
	 * current date.
	 *
	 * @see http://fr.wikipedia.org/wiki/Calendrier_perp%C3%A9tuel
	 *
	 * @return integer
	 */
	private function getAnnualNumber()
	{
		$a = $this->getYear() - floor($this->getYear() / 100) * 100;
		$c = intval($a / 4);
		$result = ($a + $c - 5) % 7;
		if ($result < 0) $result += 7;
		return intval($result);
	}

	/**
	 * Computes and returns the mensual number "nombre mensuel" for the
	 * current date.
	 *
	 * @see http://fr.wikipedia.org/wiki/Calendrier_perp%C3%A9tuel
	 *
	 * @return integer
	 */
	private function getMensualNumber()
	{
		/*             leap
		janvier		4    3   !=
		février		0    6   !=
		mars		0    0
		avril		3    3
		mai			5    5
		juin		1    1
		juillet		3    3
		aout		6    6
		septembre	2    2
		octobre		4    4
		novembre	0    0
		décembre	2    2
		*/
		$mensualNumberArray = array(4, 0, 0, 3, 5, 1, 3, 6, 2, 4, 0, 2);
		if ($this->isLeapYear())
		{
			$mensualNumberArray[0] = 3;
			$mensualNumberArray[1] = 6;
		}
		return $mensualNumberArray[$this->getMonth() - 1];
	}

	/**
	 * Updates the instance after changes.
	 */
	private function update()
	{
		$this->daysPerMonth = array(31, $this->isLeapYear() ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		if (isset($this->daysPerMonth[$this->getMonth() - 1])
		&& $this->getDay() > $this->daysPerMonth[$this->getMonth() - 1])
		{
			parent::setDay($this->daysPerMonth[$this->getMonth() - 1]);
		}
	}

	/**
	 * Fixes the month information.
	 */
	private function fixMonth()
	{
		if ($this->getMonth() > 12)
		{
			parent::setMonth($this->getMonth() % 12);
			if ( ! $this->getMonth() )
			{
				 parent::setMonth(12);
			}
		}
		else if ($this->getMonth() < 1)
		{
			parent::setMonth(12 + ($this->getMonth() % 12));
		}
	}

	/**
	 * Adds seconds to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of seconds to add.
     *
     * @return date_Calendar $this
	 */
	private function addSeconds($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->subSeconds(abs($amount));
		}
		else if ($amount > 0)
		{
			$v = $this->getSecond() + $amount;
			if ($v > 59)
			{
				$this->addMinutes(intval($v / 60));
			}
			parent::setSecond($v % 60);
			$this->update();
		}
		return $this;
	}

	/**
	 * Adds minutes to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of minutes to add.
     *
     * @return date_Calendar $this
	 */
	private function addMinutes($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->subMinutes(abs($amount));
		}
		else if ($amount > 0)
		{
			$v = $this->getMinute() + $amount;
			if ($v > 59)
			{
				$this->addHours(intval($v / 60));
			}
			parent::setMinute($v % 60);
			$this->update();
		}
		return $this;
	}

	/**
	 * Adds hours to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of hours to add.
     *
     * @return date_Calendar $this
	 */
	private function addHours($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->subHours(abs($amount));
		}
		else if ($amount > 0)
		{
			$v = $this->getHour() + $amount;
			if ($v > 23)
			{
				$this->addDays(intval($v / 24));
			}
			parent::setHour($v % 24);
			$this->update();
		}
		return $this;
	}

	/**
	 * Adds days to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of days to add.
     *
     * @return date_Calendar $this
	 */
	private function addDays($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->subDays(abs($amount));
		}
		else if ($amount > 0)
		{
			for ($i=0 ; $i<$amount; $i++)
			{
				$this->addDay();
			}
		}
		return $this;
	}

	/**
	 * Adds weeks to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of weeks to add.
     *
     * @return date_Calendar $this
	 */
	private function addWeeks($amount)
	{
		$this->addDays($amount * 7);
		return $this;
	}

	/**
	 * Adds months to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of months to add.
     *
     * @return date_Calendar $this
	 */
	private function addMonths($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->subMonths(abs($amount));
		}
		else if ($amount > 0)
		{
			$isLastDayOfMonth = $this->isLastDayOfMonth();

			$v = $this->getMonth() + $amount;
			parent::setMonth($v);
			if ($v > 12)
			{
				$y = intval(($v-1) / 12);
				$this->fixMonth();
				$this->addYears($y);
			}
			else
			{
				$this->update();
			}
			if ($this->getDay() > $this->getDaysInMonth())
			{
				parent::setDay($this->getDaysInMonth());
			}
			else if ($isLastDayOfMonth && $this->smartEndOfMonth)
			{
				parent::setDay($this->getDaysInMonth());
			}
			$this->update();
		}
		return $this;
	}

	/**
	 * Adds years to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of years to add.
     *
     * @return date_Calendar $this
	 */
	private function addYears($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->subYears(abs($amount));
		}
		else if ($amount > 0)
		{
			$this->setYear($this->getYear() + $amount);
			if ($this->getDay() > $this->daysPerMonth[$this->getMonth() - 1])
			{
				parent::setDay($this->daysPerMonth[$this->getMonth() - 1]);
			}
		}
		return $this;
	}

	/**
	 * Removes seconds from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of seconds to remove.
     *
     * @return date_Calendar $this
	 */
	private function subSeconds($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->addSeconds(abs($amount));
		}
		else if ($amount > 0)
		{
			$v = $this->getSecond() - $amount;
			if ($v < 0)
			{
				$this->subMinutes(ceil(abs($v / 60)));
				$v = 60 + ($v % 60);
				if ($v == 60) $v = 0;
			}
			parent::setSecond($v);
			$this->update();
		}
		return $this;
	}

	/**
	 * Removes minutes from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of minutes to remove.
     *
     * @return date_Calendar $this
	 */
	private function subMinutes($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->addMinutes(abs($amount));
		}
		else if ($amount > 0)
		{
			$v = $this->getMinute() - $amount;
			if ($v < 0)
			{
				$this->subHours(ceil(abs($v) / 60));
				$v = 60 + ($v % 60);
				if ($v == 60) $v = 0;
			}
			parent::setMinute($v);
			$this->update();
		}
		return $this;
	}

	/**
	 * Removes hours from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of hours to remove.
     *
     * @return date_Calendar $this
	 */
	private function subHours($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->addHours(abs($amount));
		}
		else if ($amount > 0)
		{
			$v = $this->getHour() - $amount;
			if ($v < 0)
			{
				$this->subDays(ceil(abs($v) / 24));
				$v = 24 + ($v % 24);
				if ($v == 24) $v = 0;
			}
			parent::setHour($v);
			$this->update();
		}
		return $this;
	}

	/**
	 * Removes days from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of days to remove.
     *
     * @return date_Calendar $this
	 */
	private function subDays($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->addDays(abs($amount));
		}
		else if ($amount > 0)
		{
			for ($i=0 ; $i<$amount; $i++)
			{
				$this->subDay();
			}
		}
		return $this;
	}

	/**
	 * Removes weeks from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of weeks to remove.
     *
     * @return date_Calendar $this
	 */
	private function subWeeks($amount)
	{
		$this->subDays($amount * 7);
		return $this;
	}

	/**
	 * Removes months from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of months to remove.
     *
     * @return date_Calendar $this
	 */
	private function subMonths($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->addMonths(abs($amount));
		}
		else if ($amount > 0)
		{
			$isLastDayOfMonth = $this->isLastDayOfMonth();

			$v = $this->getMonth() - $amount;
			parent::setMonth($v);
			if ($v < 1)
			{
				$y = ceil(abs(($v-1) / 12));
				$this->fixMonth();
				$this->subYears($y);
			}
			else
			{
				$this->update();
			}
			if ($this->getDay() > $this->getDaysInMonth())
			{
				parent::setDay($this->getDaysInMonth());
			}
			else if ($isLastDayOfMonth && $this->smartEndOfMonth)
			{
				parent::setDay($this->getDaysInMonth());
			}
			$this->update();
		}
		return $this;
	}

	/**
	 * Removes years from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of years to remove.
     *
     * @return date_Calendar $this
	 */
	private function subYears($amount)
	{
		$amount = intval($amount);
		if ($amount < 0)
		{
			$this->addYears(abs($amount));
		}
		else if ($amount > 0)
		{
			$this->setYear($this->getYear() - $amount);
			if ($this->getDay() > $this->daysPerMonth[$this->getMonth() - 1])
			{
				parent::setDay($this->daysPerMonth[$this->getMonth() - 1]);
			}
		}
		return $this;
	}
}