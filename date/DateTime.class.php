<?php
/**
 * A class that represents a datetime information.
 * This class is able to handle dates before 1970 as it does not use timestamps.
 *
 * @deprecated Use @link date_Calendar, @link date_Date and @link date_DateFormat instead.
 *
 * @see http://fr.wikipedia.org/wiki/Calendrier_perp%C3%A9tuel
 *
 */
class date_DateTime
{

	/**
	 * @var date_GregorianCalendar
	 */
	private $calendar;

	/**
	 * Builds a new date_DateTime instance initialized from the given date as a
	 * string. Allowed format is the MySQL one: "Y-m-d H:i:s". Time information
	 * is optionnal, so "Y-m-d" is a valid argument.
	 *
	 * @param string $dateString The date string.
	 */
	public function __construct($dateString = null)
	{
		$this->calendar = date_GregorianCalendar::getInstance($dateString);
		$this->calendar->useSmartEndOfMonth(false);
	}


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// Initialization methods                                                 //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Builds a new date_DateTime instance initialized from the given date as a
	 * string. Allowed format is the MySQL one: "Y-m-d H:i:s". Time information
	 * is optionnal, so "Y-m-d" is a valid argument.
	 *
	 * @param string $dateString
	 *
	 * @return date_DateTime
	 */
	public static function fromString($dateString)
	{
		return new date_DateTime($dateString);
	}


	/**
	 * Returns a date_DateTime instance initialized with the current system date.
	 * If $keepTimeInformation is set to true (default), the time information
	 * will be kept. Otherwise, the time will be set to midnight.
	 *
	 * @param boolean $keepTimeInformation
	 *
	 * @return date_DateTime
	 */
	public static function now($keepTimeInformation = true)
	{
		$className = get_class();
		$instance = new $className();
		if (!$keepTimeInformation)
		{
			$instance->toMidnight();
		}
		return $instance;
	}


	/**
	 * Returns a date_DateTime instance initialized with the date of yesterday.
	 * If $keepTimeInformation is set to true (default), the time information
	 * will be kept. Otherwise, the time will be set to midnight.
	 *
	 * @param boolean $keepTimeInformation
	 *
	 * @return date_DateTime
	 */
	public static function yesterday($keepTimeInformation = true)
	{
		$className = get_class();
		$format = 'Y-m-d ' . ($keepTimeInformation ? 'H:i:s' : '00:00:00');
		$instance = new $className(date($format, time() - 60*60*24));
		return $instance;
	}


	/**
	 * Returns a date_DateTime instance initialized with the date of tomorrow.
	 * If $keepTimeInformation is set to true (default), the time information
	 * will be kept. Otherwise, the time will be set to midnight.
	 *
	 * @param boolean $keepTimeInformation
	 *
	 * @return date_DateTime
	 */
	public static function tomorrow($keepTimeInformation = true)
	{
		$className = get_class();
		$format = 'Y-m-d ' . ($keepTimeInformation ? 'H:i:s' : '00:00:00');
		$instance = new $className(date($format, time() + 60*60*24));
		return $instance;
	}


	/**
	 * Sets the time of the current date_DateTime to midnight.
     *
     * @return date_DateTime $this
	 */
	public function toMidnight()
	{
		$this->calendar->toMidnight();
		return $this;
	}


	/**
	 * Sets the time of the current date_DateTime to midday.
     *
     * @return date_DateTime $this
	 */
	public function toMidday()
	{
		$this->calendar->toMidday();
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
		return $this->calendar->getTimestamp();
	}


    /**
     * @param integer $second
     *
     * @return date_DateTime $this
     */
    public function setSecond($second)
    {
    	$this->calendar->setSecond($second);
		return $this;
    }

    /**
     * @return integer
     */
    public function getSecond()
    {
    	return $this->calendar->getSecond();
    }

    /**
     * @param integer $minute
     *
     * @return date_DateTime $this
     */
    public function setMinute($minute)
    {
    	$this->calendar->setMinute($minute);
		return $this;
    }

    /**
     * @return integer
     */
    public function getMinute()
    {
    	return $this->calendar->getMinute();
    }

    /**
     * @param integer $hour
     *
     * @return date_DateTime $this
     */
    public function setHour($hour)
    {
    	$this->calendar->setHour($hour);
		return $this;
    }

    /**
     * @return integer
     */
    public function getHour()
    {
    	return $this->calendar->getHour();
    }

    /**
     * @param integer $day
     *
     * @return date_DateTime $this
     */
    public function setDay($day)
    {
    	$this->calendar->setDay($day);
		return $this;
    }

    /**
     * @return integer
     */
    public function getDay()
    {
    	return $this->calendar->getDay();
    }

    /**
     * @param integer $month
     *
     * @return date_DateTime $this
     */
    public function setMonth($month)
    {
    	$this->calendar->setMonth($month);
		return $this;
    }

    /**
     * @return integer
     */
    public function getMonth()
    {
    	return $this->calendar->getMonth();
    }

    /**
     * @param integer $year
     *
     * @return date_DateTime $this
     */
    public function setYear($year)
    {
    	$this->calendar->setYear($year);
		return $this;
    }

    /**
     * @return integer
     */
    public function getYear()
    {
    	return $this->calendar->getYear();
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
    	return $this->calendar->getCentury();
    }


	/**
	 * Returns the number of days in the the current date's month.
	 *
	 * @return integer
	 */
	public function getDaysInMonth()
	{
		return $this->calendar->getDaysInMonth();
	}


	/**
	 * Returns true is the current date is in a leap year.
	 *
	 * @return boolean true if the year of the current date is a leap year, false otherwise.
	 */
	public function isLeapYear()
	{
		// Leap years have been created in year 1582, by Gregoire III.
		return $this->calendar->isLeapYear();
	}


	/**
	 * Returns the day of the week as a number: 0=sunday, 6=saturday.
	 *
	 * @return integer
	 */
	public function getDayOfWeek()
	{
		return $this->calendar->getDayOfWeek();
	}


	/**
	 * Returns the day of year, from 0 for January 1st to 364 (or 365) for December 31st.
	 *
	 * @return integer
	 */
	public function getDayOfYear()
	{
		return $this->calendar->getDayOfYear();
	}


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// Formatting methods                                                     //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Builds and returns a string representation of the date_DateTime object.
	 *
	 * @return String
	 */
	public function toString()
	{
		return $this->calendar->toString();
	}


	/**
	 * Builds and returns a string representation of the date_DateTime object.
	 *
	 * @see toString()
	 *
	 * @return String
	 */
    public function __toString()
    {
    	return $this->toString();
    }


	/**
	 * Formats the date according to the given $format, using the same acronyms
	 * as the PHP built-in date() ones.
	 * Most of the options are handled except: e, I, O, P, T, Z, c, r, U, B.
	 * Characters that don't need to be translated must be escaped, just as with
	 * the PHP built-in date() function.
	 *
	 * This method does NOT use the PHP built-in date() function.
	 *
	 * Only the following acronyms need localization: D, l, F, M.
	 *
	 * @param String $format
	 * @param String $lang
	 *
	 * @return String
	 *
	 * @deprecated Use @link date_DateFormat::format($date, $format, $lang) instead.
	 */
	public function format($format, $lang = null)
	{
		return date_DateFormat::format($this->calendar, $format, $lang);
	}


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// add*() methods                                                         //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Adds seconds to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of seconds to add.
     *
     * @return date_DateTime $this
	 */
	public function addSeconds($amount)
	{
		$this->calendar->add(date_Calendar::SECOND, $amount);
		return $this;
	}

	/**
	 * Adds minutes to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of minutes to add.
     *
     * @return date_DateTime $this
	 */
	public function addMinutes($amount)
	{
		$this->calendar->add(date_Calendar::MINUTE, $amount);
		return $this;
	}

	/**
	 * Adds hours to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of hours to add.
     *
     * @return date_DateTime $this
	 */
	public function addHours($amount)
	{
		$this->calendar->add(date_Calendar::HOUR, $amount);
		return $this;
	}

	/**
	 * Adds days to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of days to add.
     *
     * @return date_DateTime $this
	 */
	public function addDays($amount)
	{
		$this->calendar->add(date_Calendar::DAY, $amount);
		return $this;
	}

	/**
	 * Adds weeks to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of weeks to add.
     *
     * @return date_DateTime $this
	 */
	public function addWeeks($amount)
	{
		$this->calendar->add(date_Calendar::DAY, $amount * 7);
		return $this;
	}

	/**
	 * Adds months to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of months to add.
     *
     * @return date_DateTime $this
	 */
	public function addMonths($amount)
	{
		$this->calendar->add(date_Calendar::MONTH, $amount);
		return $this;
	}

	/**
	 * Adds years to the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of years to add.
     *
     * @return date_DateTime $this
	 */
	public function addYears($amount)
	{
		$this->calendar->add(date_Calendar::YEAR, $amount);
		return $this;
	}


	/**
	 * Adds a date_TimeSpan to the current date.
	 *
	 * @param date_TimeSpan $timeSpan
	 * @param boolean $returnNewInstance If true, returns a new instance and
	 *    does not modify the current one.
	 *
	 * @return date_DateTime $this or new instance
	 */
	public function addTimeSpan($timeSpan, $returnNewInstance = false)
	{
		if ($returnNewInstance)
		{
			$newCalendar = $this->calendar->addTimeSpan($timeSpan, true);
			return new date_DateTime($newCalendar->toString());
		}
		else
		{
			$this->calendar->addTimeSpan($timeSpan, false);
		}
		return $this;
	}
	
	/**
	 * @param date_DateTime $other
	 * @return date_TimeSpan
	 */
	public function sub($other)
	{
		$diff = $this->getTimestamp()-$other->getTimestamp();
		$span = new date_TimeSpan();
		$span->setNumberOfSeconds($diff);
		return $span;
	}


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// sub*() methods                                                         //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Removes seconds from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of seconds to remove.
     *
     * @return date_DateTime $this
	 */
	public function subSeconds($amount)
	{
		$this->calendar->sub(date_Calendar::SECOND, $amount);
		return $this;
	}

	/**
	 * Removes minutes from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of minutes to remove.
     *
     * @return date_DateTime $this
	 */
	public function subMinutes($amount)
	{
		$this->calendar->sub(date_Calendar::MINUTE, $amount);
		return $this;
	}

	/**
	 * Removes hours from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of hours to remove.
     *
     * @return date_DateTime $this
	 */
	public function subHours($amount)
	{
		$this->calendar->sub(date_Calendar::HOUR, $amount);
		return $this;
	}

	/**
	 * Removes days from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of days to remove.
     *
     * @return date_DateTime $this
	 */
	public function subDays($amount)
	{
		$this->calendar->sub(date_Calendar::DAY, $amount);
		return $this;
	}

	/**
	 * Removes weeks from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of weeks to remove.
     *
     * @return date_DateTime $this
	 */
	public function subWeeks($amount)
	{
		$this->calendar->sub(date_Calendar::DAY, $amount * 7);
		return $this;
	}

	/**
	 * Removes months from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of months to remove.
     *
     * @return date_DateTime $this
	 */
	public function subMonths($amount)
	{
		$this->calendar->sub(date_Calendar::MONTH, $amount);
		return $this;
	}

	/**
	 * Removes years from the current date and updates it so that it is always correct.
	 *
	 * @param integer $amount Amount of years to remove.
     *
     * @return date_DateTime $this
	 */
	public function subYears($amount)
	{
		$this->calendar->sub(date_Calendar::YEAR, $amount);
		return $this;
	}

	/**
	 * Removes a date_TimeSpan from the current date.
	 *
	 * @param date_TimeSpan $timeSpan
	 * @param boolean $returnNewInstance If true, returns a new instance and
	 *    does not modify the current one.
	 *
	 * @return date_DateTime $this or new instance
	 */
	public function subTimeSpan($timeSpan, $returnNewInstance = false)
	{
		if ($returnNewInstance)
		{
			$newCalendar = $this->calendar->subTimeSpan($timeSpan, true);
			return new date_DateTime($newCalendar->toString());
		}
		else
		{
			$this->calendar->subTimeSpan($timeSpan, false);
		}
		return $this;
	}


	////////////////////////////////////////////////////////////////////////////
	//                                                                        //
	// Comparison methods                                                     //
	//                                                                        //
	////////////////////////////////////////////////////////////////////////////


	/**
     * Indicates whether the current date is before the given $dateTime or not.
     *
     * @param date_DateTime $dateTime
     * @param boolean $strict If true (default), strict comparison is done.
     *
     * @return boolean
     */
    public function isBefore($dateTime, $strict = true)
    {
		return $this->calendar->isBefore($dateTime->calendar, $strict);
    }


    /**
     * Indicates whether the current date is after the given $dateTime or not.
     *
     * @param date_DateTime $dateTime
     * @param boolean $strict If true (default), strict comparison is done.
     *
     * @return boolean
     */
    public function isAfter($dateTime, $strict = true)
    {
		return $this->calendar->isAfter($dateTime->calendar, $strict);
    }


    /**
     * Indicates whether the current date is between $dt1 and $dt2 or not.
     *
     * @param date_DateTime $dt1
     * @param date_DateTime $dt2
     * @param boolean $strict If true (default), strict comparisons are done.
     *
     * @return boolean
     */
    public function isBetween($dt1, $dt2, $strict = true)
    {
		return $this->calendar->isBetween($dt1->calendar, $dt2->calendar, $strict);
    }


    /**
     * Indicates if the current date belongs to the past or not.
     *
     * @return boolean
     */
    public function belongsToPast()
    {
    	return $this->calendar->belongsToPast();
    }


    /**
     * Indicates if the current date belongs to the future or not.
     *
     * @return boolean
     */
    public function belongsToFuture()
    {
    	return $this->calendar->belongsToFuture();
    }


    /**
     * Indicates if the current date is today (time is not taken into consideration).
     *
     * @return boolean
     */
    public function isToday()
    {
    	return $this->calendar->isToday();
    }


    /**
     * Indicates whether the current date equals the given $dateTime or not.
     *
     * @param date_DateTime $dateTime
     *
     * @return boolean
     */
    public function equals($dateTime)
    {
    	return $this->calendar->equals($dateTime->calendar);
    }
}

/**
 * @deprecated use date_Calendar
 */
class date_Date extends date_DateTime
{
	
}
