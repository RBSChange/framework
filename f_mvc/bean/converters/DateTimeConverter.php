<?php
class bean_DateTimeConverter implements BeanValueConverter
{
	/**
	 * @var String
	 */
	private $dateFormat;
	
	/**
	 * @return String
	 */
	public function getDateFormat()
	{
		if ($this->dateFormat === null)
		{
			return date_Formatter::getDefaultDateFormat(RequestContext::getInstance()->getLang());
		}
		return $this->dateFormat;
	}
	
	/**
	 * @param String $dateFormat
	 */
	public function setDateFormat($dateFormat)
	{
		$this->dateFormat = $dateFormat;
	}
	
	/**
	 * @see BeanValueConverter::convertFromBeanToRequestValue()
	 *
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromBeanToRequestValue($value)
	{
		if (!f_util_StringUtils::isEmpty($value))
		{
			$convertedValue = date_Calendar::getInstance($value);
			$convertedValue = date_Converter::convertDateToLocal($convertedValue);
			$convertedValue = $convertedValue->toString();
			return date_DateFormat::format($convertedValue, $this->getDateFormat());
		}
		return "";
	}
	
	/**
	 * @see BeanValueConverter::convertFromRequestToBeanValue()
	 *
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromRequestToBeanValue($value)
	{
		if (!f_util_StringUtils::isEmpty($value))
		{
			$convertedValue = date_Calendar::getInstanceFromFormat($value, $this->getDateFormat());
			$convertedValue = date_Converter::convertDateToGMT($convertedValue);
			$convertedValue = $convertedValue->toString();
			return $convertedValue;
		}
		return null;
	}
	
	/**
	 * @see BeanValueConverter::isValidRequestValue()
	 *
	 * @param Mixed $value
	 * @return Boolean
	 */
	public function isValidRequestValue($value)
	{
		if (!f_util_StringUtils::isEmpty($value))
		{
			$format = $this->getDateFormat();
			return $this->isValidFormat($value, $format);
		}
		return true;
	}
	
	/**
	 * TODO Capitalize date format validation
	 *
	 * @param String $dateString
	 * @param String $format
	 * @return Boolean
	 */
	private function isValidFormat($dateString, $format)
	{
		$formatTokens = preg_split('/[\.\/\- :]/', $format);
		$dateParts = preg_split('/[\.\/\- :]/', $dateString);
		if (count($dateParts) != count($dateParts))
		{
			return false;
		}
		// Parse tokens and retreive date information (year, month, day, hour, minute, second)
		foreach ($formatTokens as $i => $token)
		{
			$dv = intval($dateParts[$i]);
			switch ($token)
			{
				case 'y' :
				case 'Y' :
					if (strval($dv) != $dateParts[$i] || $dv < 1000 || $dv > 2500)
					{
						return false;
					}
					break;
				case 'm' :
					if (strval($dv) != $dateParts[$i] || $dv < 1 || $dv > 12)
					{
						return false;
					}
					break;
				case 'd' :
					if (strval($dv) != $dateParts[$i] || $dv < 1 || $dv > 31)
					{
						return false;
					}
					break;
				case 'h' :
				case 'H' :
					if (strval($dv) != $dateParts[$i] || $dv < 0 || $dv > 23)
					{
						return false;
					}
					break;
				case 'i' :
				case 's' :
					if (strval($dv) != $dateParts[$i] || $dv < 0 || $dv > 59)
					{
						return false;
					}
					break;
			}
		}
		return true;
	}
}