<?php
/**
 * A class that represents a datetime information.
 * This class is able to handle dates before 1970 as it does not use timestamps.
 *
 * @date Thu Jul 05 11:39:44 CEST 2007
 * @author intbonjf
 * @see http://fr.wikipedia.org/wiki/Calendrier_perp%C3%A9tuel
 */
abstract class date_Calendar
{
	// Time fields.
	const SECOND = 0;
	const MINUTE = 1;
	const HOUR   = 2;
	const DAY	= 3;
	const MONTH  = 4;
	const YEAR   = 5;

	// Days of the week.
	const SUNDAY	= 0;
	const MONDAY	= 1;
	const TUESDAY   = 2;
	const WEDNESDAY = 3;
	const THRUSDAY  = 4;
	const FRIDAY	= 5;
	const SATURDAY  = 6;

	// Months.
	const JANUARY   = 1;
	const FEBRUARY  = 2;
	const MARCH	 = 3;
	const APRIL	 = 4;
	const MAY	   = 5;
	const JUNE	  = 6;
	const JULY	  = 7;
	const AUGUST	= 8;
	const SEPTEMBER = 9;
	const OCTOBER   = 10;
	const NOVEMBER  = 11;
	const DECEMBER  = 12;


	////////////////////////////////////////////////////////////////////////////
	//																		//
	// Initialization methods												 //
	//																		//
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Builds a new date_Calendar instance initialized from the given date as a
	 * string. Allowed format is the MySQL one: "Y-m-d H:i:s". Time information
	 * is optionnal, so "Y-m-d" is a valid argument.
	 *
	 * @param string $dateString
	 * @param string $impl Desired implementation (only Gregorian is implemented for now).
	 *
	 * @return date_Calendar
	 *
	 * @throws ClassNotFoundException
	 */
	public static function getInstance($dateString = null, $impl = 'Gregorian')
	{
		$calendarClassName = 'date_'.$impl.'Calendar';
		if ( ! f_util_ClassUtils::classExists($calendarClassName) )
		{
			throw new ClassNotFoundException('Unknown Calendar implementation: '.$impl);
		}
		return f_util_ClassUtils::callMethodArgs($calendarClassName, 'getInstance', array($dateString));
	}
	
	/**
	 * Builds a new date_Calendar instance initialized from the given date as a
	 * string. Allowed format is the MySQL one: "Y-m-d H:i:s". Time information
	 * is optionnal, so "Y-m-d" is a valid argument.
	 *
	 * @param string $dateString
	 * @param string $impl Desired implementation (only Gregorian is implemented for now).
	 *
	 * @return date_Calendar
	 *
	 * @throws ClassNotFoundException
	 */
	public static function getUIInstance($dateString = null, $impl = 'Gregorian')
	{
		return date_Converter::convertDateToLocal(self::getInstance($dateString, $impl));
	}
	
	/**
	 * Builds a new date_Calendar instance initialized from the given date as a
	 * timestamp.
	 *
	 * @param integer $dateString
	 * @param string $impl Desired implementation (only Gregorian is implemented for now).
	 *
	 * @return date_Calendar
	 *
	 * @throws ClassNotFoundException
	 */
	public static function getInstanceFromTimestamp($timestamp, $impl = 'Gregorian')
	{
		$calendarClassName = 'date_'.$impl.'Calendar';
		if ( ! f_util_ClassUtils::classExists($calendarClassName) )
		{
			throw new ClassNotFoundException('Unknown Calendar implementation: '.$impl);
		}
		return f_util_ClassUtils::callMethodArgs($calendarClassName, 'getInstanceFromTimestamp', array($timestamp));
	}

	/**
	 * Returns a date_Calendar instance initialized from a given $dateString
	 * that is in the given $format.
	 *
	 * @param string $dateString
	 * @param string $format a string that contains Y, y, m, d, D, h, H, i, s and uses '.', '/', '-', ' ', ':' for separator. If no separator is used, it acts like getInstanceFromPhpFormat 
	 * @param string $impl
	 *
	 * @return date_Calendar
	 *
	 * @throws InvalidDateException
	 */
	public static function getInstanceFromFormat($dateString, $format, $impl = 'Gregorian')
	{
		$formatTokens = preg_split('/[\.\/\- :]/', $format);
		$dateTokens = preg_split('/[\.\/\- :]/', $dateString);
		
		// Date tokens length shouldn't be less than format tokens length
		if (count($formatTokens) > count($dateTokens))
		{
			throw new InvalidDateException($dateString);
		}
		
		if (count($formatTokens) == 1)
		{
			return self::getInstanceFromPhpFormat($dateString, $format, $impl);
		}

		// Set default values
		$year   = date('Y');
		$month  = date('m');
		$day	= date('d');
		$hour   = date('00');
		$minute = date('00');
		$second = date('00');

		// Parse tokens and retreive date information (year, month, day, hour, minute, second)
		foreach ($formatTokens as $i => $token)
		{
			switch ($token)
			{
				case 'y' :
				case 'Y' :
					$year = str_pad($dateTokens[$i], 4, '0', STR_PAD_LEFT);
					break;
				case 'm' :
					$month = str_pad($dateTokens[$i], 2, '0', STR_PAD_LEFT);
					break;
				case 'd' :
					$day = str_pad($dateTokens[$i], 2, '0', STR_PAD_LEFT);
					break;
				case 'h' :
				case 'H' :
					$hour = str_pad($dateTokens[$i], 2, '0', STR_PAD_LEFT);
					break;
				case 'i' :
					$minute = str_pad($dateTokens[$i], 2, '0', STR_PAD_LEFT);
					break;
				case 's' :
					$second = str_pad($dateTokens[$i], 2, '0', STR_PAD_LEFT);
					break;
			}
		}

		return self::getInstance("$year-$month-$day $hour:$minute:$second", $impl);
	}
	
	/**
	 * Returns a date_Calendar instance initialized from a given $dateString
	 * that is in the given $format.
	 *
	 * @param string $dateString
	 * @param string $format a PHP date() function format. Only 'Y', 'y', 'm', 'd', 'D', 'H', 'h', 'i', 's' and 'a' are supported
	 * @link http://php.net/manual/en/function.date.php
	 * @param string $impl
	 *
	 * @return date_Calendar
	 * @throws InvalidDateException
	 */
	public static function getInstanceFromPhpFormat($dateString, $format, $impl = 'Gregorian')
	{
		$year   = null;
		$month  = null;
		$day	= null;
		$hour   = null;
		$minute = null;
		$second = null;
		$am = null;
		$useShortHour = false;
		
		$fLen = mb_strlen($format);
		$start = 0;
		for ($i = 0; $i < $fLen; $i++)
		{
			switch ($format[$i])
			{
				case 'Y' :
					$year = mb_substr($dateString, $start, 4);
					$start += 4;
					break;
				case 'y' :
					$year = mb_substr($dateString, $start, 2);
					if ($year <= 69)
					{
						$year += 2000;
					}
					else
					{
						$year += 1900;
					}
					$start += 2;
					break;
				case 'm' :
					$month = mb_substr($dateString, $start, 2);
					$start += 2;
					break;
				case 'd' :
					$day = mb_substr($dateString, $start, 2);
					$start += 2;
					break;
				case 'h':
					$useShortHour = true;
				case 'H' :
					$hour = mb_substr($dateString, $start, 2);
					$start += 2;
					break;
				case 'i' :
					$minute = mb_substr($dateString, $start, 2);
					$start += 2;
					break;
				case 's' :
					$second = mb_substr($dateString, $start, 2);
					$start += 2;
					break;
				case 'a':
				case 'A':
					$am = mb_strtolower(mb_substr($dateString, $start, 2), "UTF-8") == "am";
					$start += 2;
					break;
				default:
					$start++;
			}
		}
		
		if ($year === null)
		{
			$year = date('Y');
		}
		if ($month === null)
		{
			$month = date('m');
		}
		if ($day === null)
		{
			$day = date('d');
		}
		if ($hour === null)
		{
			$hour = '00';
		}
		else if ($useShortHour)
		{
			if ($am === null)
			{
				throw new InvalidDateException($dateString);
			}
			if (!$am)
			{
				$hour += 12;
			}
		}
		if ($minute === null)
		{
			$minute = '00';
		}
		if ($second === null)
		{
			$second = '00';
		}
		
		if ($start != mb_strlen($dateString))
		{
			throw new InvalidDateException($dateString);
		}
		
		return self::getInstance("$year-$month-$day $hour:$minute:$second", $impl);
	}


	/**
	 * Returns a date_Calendar instance initialized with the current system date.
	 * If $keepTimeInformation is set to true (default), the time information
	 * will be kept. Otherwise, the time will be set to midnight.
	 *
	 * @param boolean $keepTimeInformation
	 *
	 * @return date_Calendar
	 */
	public final static function now($keepTimeInformation = true, $impl = 'Gregorian')
	{
		$instance = self::getInstance(null, $impl);
		if (!$keepTimeInformation)
		{
			$instance->toMidnight();
		}
		return $instance;
	}


	/**
	 * Returns a date_Calendar instance initialized with the date of yesterday.
	 * If $keepTimeInformation is set to true (default), the time information
	 * will be kept. Otherwise, the time will be set to midnight.
	 *
	 * @param boolean $keepTimeInformation
	 *
	 * @return date_Calendar
	 */
	public final static function yesterday($keepTimeInformation = true, $impl = 'Gregorian')
	{
		$format = 'Y-m-d ' . ($keepTimeInformation ? 'H:i:s' : '00:00:00');
		return self::getInstance(date($format, time() - 60*60*24), $impl);
	}


	/**
	 * Returns a date_Calendar instance initialized with the date of tomorrow.
	 * If $keepTimeInformation is set to true (default), the time information
	 * will be kept. Otherwise, the time will be set to midnight.
	 *
	 * @param boolean $keepTimeInformation
	 *
	 * @return date_Calendar
	 */
	public final static function tomorrow($keepTimeInformation = true, $impl = 'Gregorian')
	{
		$format = 'Y-m-d ' . ($keepTimeInformation ? 'H:i:s' : '00:00:00');
		return self::getInstance(date($format, time() + 60*60*24), $impl);
	}


	/**
	 * Sets the time of the current date_Calendar to midnight.
	 *
	 * @return date_Calendar $this
	 */
	public abstract function toMidnight();


	/**
	 * Sets the time of the current date_Calendar to midday.
	 *
	 * @return date_Calendar $this
	 */
	public abstract function toMidday();


	////////////////////////////////////////////////////////////////////////////
	//																		//
	// Basic getters and setters											  //
	//																		//
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Returns the timestamp for the current date.
	 * If the date is too far in the past (before 1901), this method returns false
	 * because the timestamp does not exist for such a date.
	 *
	 * @return integer or false if the date could not be converted into a timestamp (dates before 1901).
	 */
	public abstract function getTimestamp();


	/**
	 * @param integer $second
	 *
	 * @return date_Calendar $this
	 */
	public function setSecond($second)
	{
		$this->second = $second;
	}

	/**
	 * @return integer
	 */
	final public function getSecond()
	{
		return $this->second;
	}

	/**
	 * @param integer $minute
	 *
	 * @return date_Calendar $this
	 */
	public function setMinute($minute)
	{
		$this->minute = $minute;
	}

	/**
	 * @return integer
	 */
	final public function getMinute()
	{
		return $this->minute;
	}

	/**
	 * @param integer $hour
	 *
	 * @return date_Calendar $this
	 */
	public function setHour($hour)
	{
		$this->hour = $hour;
	}

	/**
	 * @return integer
	 */
	final public function getHour()
	{
		return $this->hour;
	}

	/**
	 * @param integer $day
	 *
	 * @return date_Calendar $this
	 */
	public function setDay($day)
	{
		$this->day = $day;
	}

	/**
	 * @return integer
	 */
	final public function getDay()
	{
		return $this->day;
	}

	/**
	 * @param integer $month
	 *
	 * @return date_Calendar $this
	 */
	public function setMonth($month)
	{
		$this->month = $month;
	}

	/**
	 * @return integer
	 */
	final public function getMonth()
	{
		return $this->month;
	}

	/**
	 * @param integer $year
	 *
	 * @return date_Calendar $this
	 */
	public function setYear($year)
	{
		$this->year = $year;
	}

	/**
	 * @return integer
	 */
	final public function getYear()
	{
		return $this->year;
	}


	/**
	 * Returns the number of days in the the current date's month.
	 *
	 * @return integer
	 */
	public abstract function getDaysInMonth();


	/**
	 * Returns the day of the week as a number: 0=sunday, 6=saturday.
	 *
	 * @return integer
	 */
	public abstract function getDayOfWeek();


	/**
	 * Returns the day of year, from 0 for January 1st to 364 (or 365) for December 31st.
	 *
	 * @return integer
	 */
	abstract public function getDayOfYear();


	////////////////////////////////////////////////////////////////////////////
	//																		//
	// Formatting methods													 //
	//																		//
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Builds and returns a string representation of the date_Calendar object.
	 *
	 * @return string
	 */
	public function toString()
	{
		return sprintf(
			'%04d-%02d-%02d %02d:%02d:%02d',
			$this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second
			);
	}


	/**
	 * Builds and returns a string representation of the date_Calendar object.
	 *
	 * @see toString()
	 *
	 * @return string
	 */
	public final function __toString()
	{
		return $this->toString();
	}
	
	/**
	 * @return string
	 */
	public function toFormattedDate()
	{
		return date_Formatter::toDefaultDate($this);
	}
	
	/**
	 * @return string
	 */	
	public function toFormattedDateBO()
	{
		return date_Formatter::toDefaultDateBO($this);
	}
	
	/**
	 * @return string
	 */	
	public function toFormattedDateTime()
	{
		return date_Formatter::toDefaultDateTime($this);
	}
		
	/**
	 * @return string
	 */	
	public function toFormattedDateTimeBO()
	{
		return date_Formatter::toDefaultDateTimeBO($this);
	}
	
	/**
	 * Adds an $amount of time to the given $field, which can be one among the
	 * following: ::SECOND, ::MINUTE, ::HOUR, ::DAY, ::MONTH, ::YEAR.
	 *
	 * @param integer $field
	 * @param integer $amount
	 * @return date_GregorianCalendar
	 */
	public abstract function add($field, $amount);


	/**
	 * Substracts an $amount of time to the given $field, which can be one among the
	 * following: ::SECOND, ::MINUTE, ::HOUR, ::DAY, ::MONTH, ::YEAR.
	 *
	 * @param integer $field
	 * @param integer $amount
	 * @return date_GregorianCalendar
	 */
	public abstract function sub($field, $amount);


	/**
	 * Adds a date_TimeSpan to the current date.
	 *
	 * @param date_TimeSpan $timeSpan
	 * @param boolean $returnNewInstance If true, returns a new instance and
	 *	does not modify the current one.
	 *
	 * @return date_DateGregorianCalendar $this or new instance
	 */
	public final function addTimeSpan($timeSpan, $returnNewInstance = false)
	{
		$instance = $returnNewInstance ? clone $this : $this;
		return $instance
			->add(self::SECOND, $timeSpan->getSeconds())
			->add(self::MINUTE, $timeSpan->getMinutes())
			->add(self::HOUR, $timeSpan->getHours())
			->add(self::DAY, $timeSpan->getDays())
			->add(self::MONTH, $timeSpan->getMonths())
			->add(self::YEAR, $timeSpan->getYears());
	}


	/**
	 * Removes a date_TimeSpan from the current date.
	 *
	 * @param date_TimeSpan $timeSpan
	 * @param boolean $returnNewInstance If true, returns a new instance and
	 *	does not modify the current one.
	 *
	 * @return date_DateGregorianCalendar $this or new instance
	 */
	public final function subTimeSpan($timeSpan, $returnNewInstance = false)
	{
		$instance = $returnNewInstance ? clone $this : $this;
		return $instance
			->sub(self::SECOND, $timeSpan->getSeconds())
			->sub(self::MINUTE, $timeSpan->getMinutes())
			->sub(self::HOUR, $timeSpan->getHours())
			->sub(self::DAY, $timeSpan->getDays())
			->sub(self::MONTH, $timeSpan->getMonths())
			->sub(self::YEAR, $timeSpan->getYears());
	}


	////////////////////////////////////////////////////////////////////////////
	//																		//
	// Comparison methods													 //
	//																		//
	////////////////////////////////////////////////////////////////////////////


	/**
	 * Indicates whether the current date is before the given $dateTime or not.
	 *
	 * @param date_Calendar $calendar
	 * @param boolean $strict If true (default), strict comparison is done.
	 *
	 * @return boolean
	 */
	public final function isBefore($calendar, $strict = true)
	{
		$thisString = sprintf('%04d%02d%02d%02d%02d%02d', $this->getYear(), $this->getMonth(), $this->getDay(), $this->getHour(), $this->getMinute(), $this->getSecond());
		$compString = sprintf('%04d%02d%02d%02d%02d%02d', $calendar->getYear(), $calendar->getMonth(), $calendar->getDay(), $calendar->getHour(), $calendar->getMinute(), $calendar->getSecond());
		$comp = strcmp($thisString, $compString);
		return ($comp < 0) || ($comp == 0 && ! $strict);
	}


	/**
	 * Indicates whether the current date is after the given $calendar or not.
	 *
	 * @param date_Calendar $calendar
	 * @param boolean $strict If true (default), strict comparison is done.
	 *
	 * @return boolean
	 */
	public final function isAfter($calendar, $strict = true)
	{
		$thisString = sprintf('%04d%02d%02d%02d%02d%02d', $this->getYear(), $this->getMonth(), $this->getDay(), $this->getHour(), $this->getMinute(), $this->getSecond());
		$compString = sprintf('%04d%02d%02d%02d%02d%02d', $calendar->getYear(), $calendar->getMonth(), $calendar->getDay(), $calendar->getHour(), $calendar->getMinute(), $calendar->getSecond());
		$comp = strcmp($thisString, $compString);
		return ($comp > 0) || ($comp == 0 && ! $strict);
	}


	/**
	 * Indicates whether the current date is between $dt1 and $dt2 or not.
	 *
	 * @param date_Calendar $dt1
	 * @param date_Calendar $dt2
	 * @param boolean $strict If true (default), strict comparisons are done.
	 *
	 * @return boolean
	 */
	public final function isBetween($c1, $c2, $strict = true)
	{
		return $this->isAfter($c1, $strict) && $this->isBefore($c2, $strict);
	}


	/**
	 * Indicates if the current date belongs to the past or not.
	 *
	 * @return boolean
	 */
	public function belongsToPast()
	{
		return $this->isBefore(date_Calendar::now());
	}


	/**
	 * Indicates if the current date belongs to the future or not.
	 *
	 * @return boolean
	 */
	public function belongsToFuture()
	{
		return $this->isAfter(date_Calendar::now());
	}


	/**
	 * Indicates if the current date is today (time is not taken into consideration).
	 *
	 * @return boolean
	 */
	public function isToday()
	{
		$now = date_Calendar::now();
		return true
			&& $this->getYear() == $now->year
			&& $this->getMonth() == $now->month
			&& $this->getDay() == $now->day;
	}


	/**
	 * Indicates whether the current date equals the given $dateTime or not.
	 *
	 * @param date_Calendar $calendar
	 *
	 * @return boolean
	 */
	public function equals($calendar)
	{
		return true
			&& $this->year == $calendar->year
			&& $this->month == $calendar->month
			&& $this->day == $calendar->day
			&& $this->hour == $calendar->hour
			&& $this->minute == $calendar->minute
			&& $this->second == $calendar->second;
	}
	
	// Private stuff.

	/**
	 * @var Integer
	 */
	private $second = 0;

	/**
	 * @var integer
	 */
	private $minute = 0;

	/**
	 * @var integer
	 */
	private $hour = 0;

	/**
	 * @var integer
	 */
	private $day = 0;

	/**
	 * @var integer
	 */
	private $month = 0;

	/**
	 * @var integer
	 */
	private $year = 0;
}