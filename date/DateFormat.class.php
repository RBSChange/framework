<?php
/**
 * A class to format dates.
 */
class date_DateFormat
{
	const SQL_DATE_FORMAT = "Y-m-d H:i:s";
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
	 * @param date_DateTime $date The DateTime object to format.
	 * @param String $format
	 * @param String $lang
	 *
	 * @return String
	 *
	 * @example date_DateFormat::format($date, 'd/m/Y');
	 * @example date_DateFormat::format($date, 'd/m/Y H:i:s');
	 * @example date_DateFormat::format($date, 'D M Y', 'en');
	 */
	public static function format($date, $format = null, $lang = null)
	{
		if ( is_null($lang) )
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		if ( is_null($format) )
		{
			$format = self::getDateTimeFormatForLang($lang);
		}
    	if ( is_null($date) )
    	{
    		return '';
    	}
    	if ( is_string($date) )
    	{
    		$date = date_Calendar::getInstance($date);
    	}
		$result = '';
		$escaped = false;
		for ($i = 0 ; $i<f_util_StringUtils::strlen($format) ; $i++)
		{
			$c = f_util_StringUtils::substr($format, $i, 1);
			if ($c == '\\')
			{
				if ($escaped)
				{
					$result .= '\\';
				}
				$escaped = ! $escaped;
			}
			else if ( $escaped )
			{
				$result .= $c;
				$escaped = false;
			}
			else
			{
				switch ($c)
				{
					// Day of the month, 2 digits with leading zeros
					case 'd' :
						$result .= sprintf('%02d', $date->getDay());
						break;

					// A textual representation of a day, three letters
					case 'D' :
						$result .= f_Locale::translate(
							// key
							'&framework.date.date.abbr.'.strtolower(self::$dayArray[$date->getDayOfWeek()]).';',
							// substitution array
							null,
							// language
							$lang
							);
						break;

					// Day of the month without leading zeros
					case 'j' :
						$result .= strval($date->getDay());
						break;

					// A full textual representation of the day of the week
					case 'l' :
						$result .= f_Locale::translate(
							// key
							'&framework.date.date.'.strtolower(self::$dayArray[$date->getDayOfWeek()]).';',
							// substitution array
							null,
							// language
							$lang
							);
						break;

					// English ordinal suffix for the day of the month, 2 characters
					case 'S' :
						if ( array_key_exists(strval($date->getDay()), self::$englishOrdinalSuffixArray) )
						{
							$result .= self::$englishOrdinalSuffixArray[strval($date->getDay())];
						}
						else
						{
							$result .= self::$englishOrdinalSuffixArray['default'];
						}
						break;

					// Numeric representation of the day of the week
					case 'w' :
						$result .= strval($date->getDayOfWeek());
						break;

					// A full textual representation of a month, such as January or March
					case 'F' :
						$result .= f_Locale::translate(
							// key
							'&framework.date.date.'.strtolower(self::$monthArray[$date->getMonth() - 1]).';',
							// substitution array
							null,
							// language
							$lang
							);
						break;

					// Numeric representation of a month, with leading zeros
					case 'm' :
						$result .= sprintf('%02d', $date->getMonth());
						break;

					// A short textual representation of a month, three letters
					case 'M' :
						$result .= f_Locale::translate(
							// key
							'&framework.date.date.abbr.'.strtolower(self::$monthArray[$date->getMonth() - 1]).';',
							// substitution array
							null,
							// language
							$lang
							);
						break;

					// Numeric representation of a month, without leading zeros
					case 'n' :
						$result .= strval($date->getMonth());
						break;

					// Number of days in the given month
					case 't' :
						$result .= strval($date->getDaysInMonth());
						break;

					// The day of the year (starting from 0)
					case 'z' :
						$result .= strval($date->getDayOfYear());
						break;

					// Whether it's a leap year
					case 'L' :
						$result .= $date->isLeapYear() ? '1' : '0';
						break;

					// A full numeric representation of a year, 4 digits
					case 'Y' :
						$result .= sprintf('%04d', $date->getYear());
						break;

					// A two digit representation of a year
					case 'y' :
						$result .= substr(strval($date->getYear()), -2);
						break;

					// Lowercase Ante meridiem and Post meridiem
					case 'a' :
						$result .= ($date->getHour() < 12) ? 'am' : 'pm';
						break;

					// Uppercase Ante meridiem and Post meridiem
					case 'A' :
						$result .= ($date->getHour() < 12) ? 'AM' : 'PM';
						break;

					// 12-hour format of an hour without leading zeros
					case 'g' :
						$result .= strval($date->getHour() % 12);
						break;

					// 24-hour format of an hour without leading zeros
					case 'G' :
						$result .= strval($date->getHour());
						break;

					// 12-hour format of an hour with leading zeros
					case 'h' :
						$result .= sprintf('%02d', $date->getHour() % 12);
						break;

					// 24-hour format of an hour with leading zeros
					case 'H' :
						$result .= sprintf('%02d', $date->getHour());
						break;

					// Minutes with leading zeros
					case 'i' :
						$result .= sprintf('%02d', $date->getMinute());
						break;

					// Seconds with leading zeros
					case 's' :
						$result .= sprintf('%02d', $date->getSecond());
						break;

					default :
						$result .= $c;
				}
			}
		}

		return $result;
	}


    const FORMAT_WITHOUT_TIME            = 1;
    const FORMAT_SHORT_NAMES             = 2;
    const FORMAT_LONG_NAMES_ON_FULL_DATE = 4;


    public function smartFormat($date, $options = 0, $lang = null)
    {
    	if ( is_null($date) )
    	{
    		return '';
    	}
    	if ( is_string($date) )
    	{
    		$date = date_Calendar::getInstance($date);
    	}
		// build the format, depending on the options and the current date
		$now = date_Calendar::now();

		// same year?
		if ( $date->getYear() == $now->getYear() )
		{
			// same month?
			if ( $date->getMonth() == $now->getMonth() )
			{
				// today?
				if ( $date->getDay() == $now->getDay() )
				{
					$key = "today";
				}
				else
				{
					$key = "same-monthAndYear";
				}
			}
			else
			{
				$key = "same-year";
			}
		}
		else
		{
			$key = "full";
		}

		// handle options
		if ( ($options & self::FORMAT_WITHOUT_TIME) == 0 )
		{
			$key .= "-time";
			if ($date->getHour() == 0 && $date->getMinute() == 0 && $date->getSecond() == 0)
			{
				$key .= "-midnight";
			}
		}

		if ( $options & self::FORMAT_SHORT_NAMES || ( ($options & self::FORMAT_LONG_NAMES_ON_FULL_DATE) == 0 && ($key == "full" || $key == "full-time")) )
		{
			$key .= "-short";
		}

		// build full localization key
		$key = "&framework.date.date.smart-" . $key . ";";

		// translate the date format according to the desired language
		$format = f_Locale::translate($key);

		// format the date according to the format and return it
		return self::format($date, $format, $lang);
    }



    /**
     * @param string $lang
     * @return string
     */
    public static function getDateFormatForLang($lang)
    {
        return f_Locale::translate('&framework.date.date.default-date-format;', null, $lang);
    }
    
 	/**
     * @return string
     */
    public static function getDateFormat()
    {
    	return self::getDateFormatForLang(null);
    }

    /**
     * @param string $lang
     * @return string
     */
    public static function getDateTimeFormatForLang($lang)
    {
        return f_Locale::translate('&framework.date.date.default-datetime-format;', null, $lang);
    }
    
	/**
     * @return string
     */
    public static function getDateTimeFormat()
    {
    	return self::getDateTimeFormatForLang(null);
    }


// --- PRIVATE STUFF -----------------------------------------------------------


	/**
	 * Array of month names.
	 *
	 * @var array<string>
	 */
	private static $monthArray = array(
		'January', 'February', 'March', 'April', 'May', 'June',
		'July', 'August', 'September', 'October', 'November', 'December'
		);

	/**
	 * Array of abbreviated month names.
	 *
	 * @var array<string>
	 */
	private static $monthAbbrArray = array(
		'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
		'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
		);

	/**
	 * Array of day names.
	 *
	 * @var array<string>
	 */
	private static $dayArray = array(
		'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'
		);

	/**
	 * Array of abbreviated day names.
	 *
	 * @var array<string>
	 */
	private static $dayAbbrArray = array(
		'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'
		);

	/**
	 * Array of English ordinal suffixes.
	 *
	 * @var array<string,string>
	 */
	private static $englishOrdinalSuffixArray = array (
		'1'  => 'st',
		'21' => 'st',
		'31' => 'st',
		'2'  => 'nd',
		'22' => 'nd',
		'3'  => 'rd',
		'13' => 'rd',
		'23' => 'rd',
		'default' => 'th'
		);
}


class date_DateFormatOptions
{
	private $withTime = true;
	private $useShortNames = false;
	private $useShortNamesOnLongDates = true;
}