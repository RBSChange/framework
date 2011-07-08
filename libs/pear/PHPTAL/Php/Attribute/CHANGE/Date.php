<?php
// change:date
//
// format par défaut :
//   <span change:date="page/getStartpublicationdate" />
// spécification du format :
//   <span change:date="format \l\e d \d\u \m\o\i\s \d\e F, \a\n\n\é\e Y; value page/getStartpublicationdate" />

/**
 * @package phptal.php.attribute
 * @author INTbonjF
 * 2007-04-19
 */
class PHPTAL_Php_Attribute_CHANGE_date extends ChangeTalAttribute 
{	
	/**
	 * @see ChangeTalAttribute::getDefaultParameterName()
	 *
	 * @return String
	 */
	protected function getDefaultParameterName()
	{
		return 'date';
	}
		
	/**
	 * @see ChangeTalAttribute::getDefaultValues()
	 *
	 * @return unknown
	 */
	public function getDefaultValues()
	{
		return array('format' => 'names');
	}
	
	/**
	 * @see ChangeTalAttribute::getEvaluatedParameters()
	 *
	 * @return array
	 */
	public function getEvaluatedParameters()
	{
		return array('format', 'date', 'value');
	}

	public static function renderDate($params, $dropTimeInfo = true)
	{
		$dateValue = self::getDateFromParams($params);
		if ($dateValue === null)
		{
			return "";
		}
		if ($dateValue === false)
		{
			$date = date_Calendar::getInstance($dateValue);
		}
		else
		{
			$date = date_Calendar::getInstance($dateValue);
		}
		
		$uiDate = date_Converter::convertDateToLocal($date);
		if (isset($params['formatI18n']))
		{
			return date_DateFormat::format($uiDate, f_Locale::translate('&' . $params['formatI18n'] . ';'));
		}
		$format = $params['format'];
    	if ($format == "names")
		{
			if ($dropTimeInfo)
			{
				$dateStr = date_DateFormat::smartFormat($uiDate, date_DateFormat::FORMAT_WITHOUT_TIME);
			}
			else 
			{	
				$dateStr = date_DateFormat::smartFormat($uiDate);
			}
		}
		else
		{
			if ($format == "classic") 
			{
				if ($dropTimeInfo)
				{
					$format = date_Formatter::getDefaultDateFormat();
				}
				else
				{
					$format = date_Formatter::getDefaultDateTimeFormat();
				}
			}
			$dateStr = date_Formatter::format($uiDate, $format);
		}
    	return $dateStr;
	}
	
	private static function getDateFromParams($params)
	{
		$rawDate = false;
		if (array_key_exists('value', $params))
		{
			$rawDate = $params['value'];
		}
		
		if (array_key_exists('date', $params))
		{
			$rawDate = $params['date'];
		}
		return $rawDate;
	}
}

// change:datetime
//
// format par défaut :
//   <span change:datetime="page/getStartpublicationdate" />
// spécification du format :
//   <span change:datetime="format \l\e d \d\u \m\o\i\s \d\e F, \a\n\n\é\e Y \à H\h i\m\i\n \e\t s\s\e\c.; value page/getStartpublicationdate" />

/**
 * @package phptal.php.attribute
 * @author INTbonjF
 * 2007-04-19
 */

class PHPTAL_Php_Attribute_CHANGE_datetime extends PHPTAL_Php_Attribute_CHANGE_date
{		
	public static function renderDateTime($params)
	{
		return self::renderDate($params, false);
	}
}