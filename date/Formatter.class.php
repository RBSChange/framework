<?php
class date_Formatter
{
	const SQL_DATE_FORMAT = "Y-m-d H:i:s";
	
	/**
	 * @param date_Calendar $date or string
	 * @param string $format
	 * @return string
	 */
	public static function format($date, $format = null)
	{
		$lang = RequestContext::getInstance()->getLang();
		if ($format === null)
		{
			$format = self::getDefaultDateTimeFormat($lang);
		}
    	if (is_string($date) && $date !== '')
    	{
    		$date = date_Calendar::getInstance($date);
    	}
    	elseif (is_integer($date))
    	{
    		$date = date_Calendar::getInstanceFromTimestamp($date);
    	}
    	elseif (!($date instanceof date_Calendar))
    	{
    		return '';
    	}
	if ($format === 'c')
	{
	    return date('c', $date->getTimestamp());
	}
    	$ls = LocaleService::getInstance();
		$result = '';
		$escaped = false;
		$formatLength = strlen($format);
		for ($i = 0 ; $i <  $formatLength; $i++)
		{
			$c = $format[$i];
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
						$result .= str_pad(strval($date->getDay()), 2, '0', STR_PAD_LEFT);
						break;

					// A textual representation of a day, three letters
					case 'D' :
						$result .= $ls->trans('f.date.date.abbr.'.self::$dayArray[$date->getDayOfWeek()]);
						break;

					// Day of the month without leading zeros
					case 'j' :
						$result .= strval($date->getDay());
						break;

					// A full textual representation of the day of the week
					case 'l' :
						$result .=  $ls->trans('f.date.date.'.self::$dayArray[$date->getDayOfWeek()]);
						break;

					// English ordinal suffix for the day of the month, 2 characters
					case 'S' :
						$key = strval($date->getDay());		
						$key =  (isset(self::$ordinalSuffixArray[$key])) ? self::$ordinalSuffixArray[$key] : self::$ordinalSuffixArray['default'];
						$result .= $ls->trans('f.date.date.suffix.'.$key);
						break;

					// Numeric representation of the day of the week
					case 'w' :
						$result .= strval($date->getDayOfWeek());
						break;

					// A full textual representation of a month, such as January or March
					case 'F' :
						$result .= $ls->trans('f.date.date.'.self::$monthArray[$date->getMonth() - 1]);
						break;

					// Numeric representation of a month, with leading zeros
					case 'm' :
						$result .= str_pad(strval($date->getMonth()), 2, '0', STR_PAD_LEFT);
						break;

					// A short textual representation of a month, three letters
					case 'M' :
						$result .= $ls->trans('f.date.date.abbr.'.self::$monthArray[$date->getMonth() - 1]);
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
						$result .= strval($date->getYear());
						break;

					// A two digit representation of a year
					case 'y' :
						$result .= substr(strval($date->getYear()), -2);
						break;

					// Lowercase Ante meridiem and Post meridiem
					case 'a' :
						
						$result .= $ls->trans('f.date.date.'. (($date->getHour() < 12) ? 'am' : 'pm'));
						break;

					// Uppercase Ante meridiem and Post meridiem
					case 'A' :
						$result .= $ls->trans('f.date.date.'. (($date->getHour() < 12) ? 'am' : 'pm'), array('uc'));
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
						$result .= str_pad(strval($date->getHour() % 12), 2, '0', STR_PAD_LEFT);
						break;

					// 24-hour format of an hour with leading zeros
					case 'H' :
						$result .= str_pad(strval($date->getHour()), 2, '0', STR_PAD_LEFT);
						break;

					// Minutes with leading zeros
					case 'i' :
						$result .= str_pad(strval($date->getMinute()), 2, '0', STR_PAD_LEFT);
						break;

					// Seconds with leading zeros
					case 's' :
						$result .= str_pad(strval($date->getSecond()), 2, '0', STR_PAD_LEFT);
						break;
						
					default :
						$result .= $c;
				}
			}
		}

		return $result;	
	}

	/**
	 * @param date_Calendar $date or string
	 * @param string $format
	 * @return string
	 */
	public static function formatBO($date, $format = null)
	{
		$rc = RequestContext::getInstance();
		try 
		{
			$rc->beginI18nWork($rc->getUILang());
			$result = self::format($date, $format);
			$rc->endI18nWork();
		} 
		catch (Exception $e) 
		{
			$rc->endI18nWork($e);
		}
		return $result;
	}
	
	/**
	 * @param string $lang
	 * @return string
	 */
	public static function getDefaultDateFormat($lang)
	{
		return RequestContext::getInstance()->getDateFormat($lang);
	}
	
	/**
	 * @param string $lang
	 * @return string
	 */	
	public static function getDefaultDateTimeFormat($lang)
	{
		return RequestContext::getInstance()->getDateTimeFormat($lang);
	}
	
	/**
	 * @param date_Calendar $date or string
	 * @return string
	 */
	public static function toDefaultDate($date)
	{
		return self::format($date, self::getDefaultDateFormat(RequestContext::getInstance()->getLang()));
	}

	/**
	 * @param date_Calendar $date or string
	 * @return string
	 */
	public static function toDefaultDateBO($date)
	{
		return self::formatBO($date, self::getDefaultDateFormat(RequestContext::getInstance()->getUILang()));
	}
	

	
	/**
	 * @param date_Calendar $date or string
	 * @return string
	 */
	public static function toDefaultDateTime($date)
	{
		return self::format($date, self::getDefaultDateTimeFormat(RequestContext::getInstance()->getLang()));
	}
	
	/**
	 * @param date_Calendar $date or string
	 * @return string
	 */
	public static function toDefaultDateTimeBO($date)
	{
		return self::formatBO($date, self::getDefaultDateTimeFormat(RequestContext::getInstance()->getUILang()));
	}
	
	

	
	/**
	 * @var string[]
	 */
	private static $monthArray = array(
		'january', 'february', 'march', 'april', 'may', 'june',
		'july', 'august', 'september', 'october', 'november', 'december'
		);

	/**
	 * @var string[]
	 */
	private static $dayArray = array(
		'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'
		);
		
	/**
	 * @var string[]
	 */
	private static $ordinalSuffixArray = array (
		'1'  => 'st', '21' => 'nst', '31' => 'nst', '2'  => 'nd', '22' => 'nnd', '3'  => 'nrd', '13' => 'rd', '23' => 'nrd',
		'default' => 'th');
}