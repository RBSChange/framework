<?php
/**
 * @deprecated use date_Formatter
 */
class date_DateFormat
{
	const SQL_DATE_FORMAT = "Y-m-d H:i:s";
    const FORMAT_WITHOUT_TIME            = 1;
    const FORMAT_SHORT_NAMES             = 2;
    const FORMAT_LONG_NAMES_ON_FULL_DATE = 4;
	
	/**
	 * @deprecated use date_Formatter::format
	 */
	public static function format($date, $format = null, $lang = null)
	{
		if ($lang !== null)
		{
			$rc = RequestContext::getInstance();
			try 
			{
				$rc->beginI18nWork($lang);
				$result = date_Formatter::format($date, $format);
				$rc->endI18nWork();
			} 
			catch (Exception $e) 
			{
				$rc->endI18nWork($e);
			}
		}
		else
		{
			$result = date_Formatter::format($date, $format);
		}
		return $result;
	}

    /**
     * @deprecated with no replacement
     */
    public static function smartFormat($date, $options = 0, $lang = null)
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
					$key = "same-monthandyear";
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
		$key = "f.date.date.smart-" . $key;
		
		// translate the date format according to the desired language
		$format = LocaleService::getInstance()->formatKey($lang === null ? RequestContext::getInstance()->getLang() : $lang, $key);

		// format the date according to the format and return it
		return self::format($date, $format, $lang);
    }



    /**
     * @deprecated use date_Formatter::getDefaultDateFormat
     */
    public static function getDateFormatForLang($lang)
    {
        return date_Formatter::getDefaultDateFormat($lang);
    }
    
 	/**
     * @deprecated use date_Formatter::getDefaultDateFormat
     */
    public static function getDateFormat()
    {
    	return date_Formatter::getDefaultDateFormat(null);
    }

    /**
     * @deprecated use date_Formatter::getDefaultDateTimeFormat
     */
    public static function getDateTimeFormatForLang($lang)
    {
    	return date_Formatter::getDefaultDateTimeFormat($lang);
    }
    
	/**
     * @deprecated use date_Formatter::getDefaultDateTimeFormat
     */
    public static function getDateTimeFormat()
    {
    	return date_Formatter::getDefaultDateTimeFormat(null);
    }
}