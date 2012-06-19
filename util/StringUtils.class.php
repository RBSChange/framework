<?php
/**
 * @package framework.util
 */
abstract class f_util_StringUtils
{
	const CASE_SENSITIVE   = true;
	const CASE_INSENSITIVE = false;

	const SKIP_FIRST = true;
	const SKIP_NONE  = false;

	const TO_LOWER_CASE = 1;
	const TO_UPPER_CASE = 2;
	const STRIP_ACCENTS = 3;

	/**
	 * @param String $value
	 * @param String $fromEncoding
	 * @param String $toEncoding
	 * @return String
	 */
	public static function convertEncoding($value, $fromEncoding, $toEncoding = 'UTF-8')
	{
		if ($fromEncoding != $toEncoding)
		{
			$value = mb_convert_encoding($value, $toEncoding, $fromEncoding);
		}
		return $value;
	}

	/**
	 * @param String $value
	 * @param String $fromEncoding
	 * @return String
	 */
	public static function utf8Encode($value, $fromEncoding = null)
	{
		// If there is no given encoding try to detect it.
		// Warning: There is no 100% reliable method to do this... 
		// 			So if you can, prefer to give the set the 
		//			$fromEncoding parameter.
		if (is_null($fromEncoding))
		{
			$fromEncoding = mb_detect_encoding(" $value ", 'UTF-8, ISO-8859-1');
		}

		return self::convertEncoding($value, $fromEncoding, $toEncoding = 'UTF-8');
   	}

	/**
	 * @param String $haystack
	 * @param String $needle
	 * @param Boolean $caseSensitive self::CASE_INSENSITIVE or self::CASE_SENSITIVE
	 * @return Boolean
	 */
	public static function endsWith($haystack, $needle, $caseSensitive = self::CASE_INSENSITIVE)
	{
		$len = mb_strlen($needle, "UTF-8");
		if ($caseSensitive === self::CASE_SENSITIVE)
		{
			return mb_substr($haystack, -$len, $len, "UTF-8") == $needle;
		}
		return mb_strtolower(mb_substr($haystack, -$len, $len, "UTF-8"), "UTF-8") === mb_strtolower($needle, "UTF-8");
	}

	/**
	 * @param String $haystack
	 * @param String $needle
	 * @param Boolean $caseSensitive self::CASE_INSENSITIVE or self::CASE_SENSITIVE
	 * @return Boolean
	 */
	public static function beginsWith($haystack, $needle, $caseSensitive = self::CASE_INSENSITIVE)
	{
		if ($caseSensitive === self::CASE_SENSITIVE)
		{
			return mb_substr($haystack, 0, mb_strlen($needle, "UTF-8"), "UTF-8") == $needle;
		}
		return mb_strtolower(mb_substr($haystack, 0, mb_strlen($needle, "UTF-8"), "UTF-8"), "UTF-8") === mb_strtolower($needle, "UTF-8");
	}
	
	/**
	 * @param String $str the string you search in
	 * @param String $needle the string that you search for
	 * @return Boolean 
	 */
	public static function contains($str, $needle)
	{
		return mb_strpos($str, $needle, 0, "UTF-8") !== false;
	}

	/**
	 * Transforms a string from 'testSampleString' to 'test_sample_string'.
	 *
	 * @param String $str
	 * @return String
	 */
	public static function caseToUnderscore($str)
	{
		$t = 0;
		$tokens = array('');
		for ($i=0; $i<mb_strlen($str, "UTF-8"); $i++)
		{
			$c = $str{$i};
			if ($i > 0)
			{
				if ($c >= 'A' && $c <= 'Z')
				{
					$tokens[++$t] = '';
				}
			}
			$tokens[$t] .= $c;
		}
		return mb_strtolower(join('_', $tokens), "UTF-8");
	}

	/**
	 * Transforms a string from 'test_sample_string' to 'testSampleString'.
	 *
	 * @param String $str
	 * @param Integer $skipFirst self::SKIP_NONE or self::SKIP_FIRST
	 * @return String
	 */
	public static function underscoreToCase($str, $skipFirst = self::SKIP_NONE)
	{
		$parts = explode('_', $str);
		$resStr = '';
		for ($i=0; $i<count($parts); $i++)
		{
			if ($i !== 0 || $skipFirst !== self::SKIP_FIRST)
			{
				$parts[$i] = ucfirst(strtolower($parts[$i]));
			}
			$resStr .= $parts[$i];
		}
		return $resStr;
	}

	/**
     * Transcode parameter value(s) into their hexadecimal if needed.
	 * This function is very usefull for specials characters encoding,
	 * like arabic, chinese... characters sets that are outbound of Javascript
	 * capabilities.
	 *
     * @return the parameter with transcoded values or the parameter
     */
	 final static function doTranscode($in_value = null)
	 {
		if (is_array($in_value))
		{
			return self::transcodeStringsInArray($in_value);
		}
		if (is_string($in_value))
		{
			return self::transcodeString($in_value);
		}
		return $in_value;
	}

	/**
     * Transcode strings contained in given array into their hexadecimal
     * representation if needed.
	 * This function is very usefull for specials characters encoding,
	 * like arabic, chinese... characters sets that are outbound of Javascript
	 * capabilities.
	 *
     * @return the same array with encoded string or the given object if it's not an array
     */
	 final static function transcodeStringsInArray($in_array = null)
	 {
		if (is_array($in_array) === false) return $in_array;

		if ($in_array != null && count($in_array) > 0)
		{
			$l_keys = array_keys($in_array);
			$l_nbElementsInArray = count($l_keys);

			for ($i = 0; $i < $l_nbElementsInArray; $i++)
			{
				$l_element = $in_array[$l_keys[$i]];

				if (is_array($l_element)) 
				{
					$in_array[$l_keys[$i]] = self::transcodeStringsInArray($l_element);
				}
				else 
				{
					$in_array[$l_keys[$i]] = self::transcodeString($in_array[$l_keys[$i]]);
				}
			}
		}

		return $in_array;
	}

	/**
     * Transcode strings into their hexadecimal representation if needed.
	 * This function is very usefull for specials characters encoding,
	 * like arabic, chinese... characters sets that are outbound of Javascript
	 * capabilities.
	 *
     * @return encoded string or the given string if no action needed
     */
	 public static function transcodeString($in_toTranscode = null)
	 {
	 	mb_internal_encoding("UTF-8");
		$l_result = "";

		if ($in_toTranscode != null && mb_strlen($in_toTranscode) > 0)
		{
			if (mb_strpos($in_toTranscode, "%u") === false) 
			{
				$l_result = $in_toTranscode;
			}
			else
			{
				$l_separatedChars = explode("%u", $in_toTranscode);
				$l_nbElementsInArray = count($l_separatedChars);

				for ($i = 0; $i < $l_nbElementsInArray; $i++)
				{
					$l_value = mb_substr($l_separatedChars[$i], 0, 4);

					if (self::is_hexa($l_value))
					{
						if (mb_strlen($l_separatedChars[$i]) > 4)
						{
							$l_result .= "&#x";
							$l_result .= $l_value;
							$l_result .= ";";
							$l_result .= mb_substr($l_separatedChars[$i], 4);
						}
						else
							$l_result .= "&#x".$l_separatedChars[$i].";";
					}
					else
						$l_result .= $l_separatedChars[$i];
				}
			}
		}

		if (mb_strlen($l_result) == 0)
		{
			$l_result = $in_toTranscode;
		}

		return $l_result;
	}

    /**
     * Aim of this function is to return an easy memorisable string
     * It's usefull for random password generation for example
     *
     * @param  int     Length of the random string
     * @param  boolean Indicate if return string is case sensitive
     * @return string  Random string
     */
    public static function randomString($length = 8, $caseSensitive = true)
    {
        $randomString = "";
        $consons  = array("b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "q", "r", "s", "t", "v", "z", "bl", "br", "cl", "cr", "ch", "dr", "fl", "fr", "gl", "gr", "pl", "pr", "qu", "sl", "sr");
        $vowels = array("a", "e", "i", "o", "u", "ae", "ai", "au", "eu", "ia", "io", "iu", "oa", "oi", "ou", "ua", "ue", "ui");

        if ($caseSensitive == true)
        {
            // Add upper conson to consons' array
            foreach ($consons as $conson)
            {
                $consons[] = strtoupper($conson);
            }
            // Add upper vowel to vowels' array
            foreach ($vowels as $vowel)
            {
                $vowels[] = strtoupper($vowel);
            }
        }
        $nbC = count($consons) - 1;
        $nbV = count($vowels) - 1;

        for ($i = 0; $i < $length; $i++)
        {
            $randomString .= $consons[rand(0, $nbC)] . $vowels[rand(0, $nbV)];
        }

        return substr($randomString, 0, $length);
    }

	/**
	 * Test if sended string is hexadecimal
	 * @param string $in_hexaTest
	 * @return true or false
	 **/
	public static function isHexa($in_hexaTest = null)
	{
		if (!is_string($in_hexaTest)) 
		{ 
			return false; 
		}

		$l_value = trim(strtolower($in_hexaTest));
		$l_allowed = array("a","b","c","d","e","f","0","1","2","3","4","5","6","7","8","9");

		for($j = 0; $j < strlen($l_value); $j++)
		{
			if (!in_array($l_value[$j], $l_allowed))
			{
				return false;
			}
		}

		return true;
	}
	
	/**
	 * The aim of this function is to return an associative array
	 * from a given "associative" string :
	 *
	 * "name1: value1; name2: value2;" --> array(
	 * 0 => "name1: value1; name2: value2;"
	 * "name1" => "value1",
	 * "name2" => "value2"
	 * )
	 *
	 * @param
	 *        	mixed in_varToDump Variable to dump
	 * @return string var_dump under string form
	 */
	public static function parseAssocString($in_assoc_string = '')
	{
		if (!trim($in_assoc_string))
		{
			return array();
		}
		$out_assoc_array = array($in_assoc_string);
		$assoc_strings = explode(";", $in_assoc_string);
		foreach ($assoc_strings as $assoc_string)
		{
			$declaration = explode(":", $assoc_string);
			if (isset($declaration[0]) && isset($declaration[1]))
			{
				$property = trim($declaration[0]);
				$value = trim($declaration[1]);
				if ($property)
				{
					$out_assoc_array[$property] = $value;
				}
			}
			else if (trim($assoc_string))
			{
				$out_assoc_array[] = trim($assoc_string);
			}
		}
		return $out_assoc_array;
	}

	/**
	 * @param string $string
	 * @param integer $maxLen
	 * @param string $dots
	 * @return string
	 */
	public static function shortenString($string, $maxLen = f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING_DEAFULT_MAX_LENGTH, $dots = '...')
	{
	    if (self::strlen($string) > $maxLen)
		{
           $string = self::substr( $string, 0, $maxLen - self::strlen($dots)) . $dots;
        }
        return $string;
	}

	/**
	 * @param string $string
	 * @param string[] $highlights
	 * @param string $begin
	 * @param string $end
	 * @return string
	 */
	public static function highlightString($string, $highlights, $begin = '<strong>', $end = '</strong>')
	{
	    if (!is_array($highlights))
	    {
	        $highlights = array($highlights);
	    }
	    foreach ($highlights as $highlight)
        {
           $string = preg_replace('/(' . $highlight . ')/i', $begin . '$1' . $end, $string);
        }
        return $string;
	}

	/**
	 * UTF8-safe strlen.
	 *
	 * @param String $string
	 * @return Integer
	 */
	public static function strlen($string)
    {
        return mb_strlen($string, "UTF-8");
    }

    /**
     * UTF8-safe substr.
     *
     * @param String $string
     * @param Integer $start
     * @param Integer $length
     * @return String
     */
	public static function substr($string, $start, $length = null)
    {
        if (is_null($length))
        {
            $length = self::strlen($string);
        }
        return mb_substr($string, $start, $length, "UTF-8");
    }

	private static $fromAccents = array('à', 'â', 'ä', 'á', 'ã', 'å', 'À', 'Â', 'Ä', 'Á', 'Ã', 'Å', 'æ', 'Æ', 'ç', 'Ç', 'è', 'ê', 'ë', 'é', 'È', 'Ê', 
		'Ë', 'É', 'ð', 'Ð', 'ì', 'î', 'ï', 'í', 'Ì', 'Î', 'Ï', 'Í', 'ñ', 'Ñ', 'ò', 'ô', 'ö', 'ó', 'õ', 'ø', 'Ò', 'Ô', 'Ö', 'Ó', 'Õ', 'Ø', 'œ', 'Œ', 
		'ù', 'û', 'ü', 'ú', 'Ù', 'Û', 'Ü', 'Ú', 'ý', 'ÿ', 'Ý', 'Ÿ');
	private static $toAccents = array('a', 'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A', 'A', 'ae', 'AE', 'c', 'C', 'e', 'e', 'e', 'e', 'E', 'E', 
		'E', 'E', 'ed', 'ED', 'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'n', 'N', 'o', 'o', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'O', 'O', 'oe', 'OE', 
		'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'y', 'y', 'Y', 'Y');
	
	/**
     * @param String $string
     * @return String
     */
	public static function stripAccents($string)
	{
		return str_replace(self::$fromAccents, self::$toAccents, $string);
	}

	/**
	 * UTF8-safe strtolower.
	 * @param string $string
	 * @return string
	 */
	public static function toLower($string)
	{
		return mb_strtolower($string, "UTF-8");
	}
	
	/**
	 * UTF8-safe strtoupper.
	 * @param string $string
	 * @return string
	 */
	public static function toUpper($string)
	{
		return mb_strtoupper($string, "UTF-8");
	}

	/**
	 * UTF8-safe ucfirst.
	 *
	 * @param String $string
	 * @return String
	 */
    public static function ucfirst($string)
    {
        return self::toUpper(self::substr($string, 0, 1)) . self::substr($string, 1);
    }

	/**
	 * UTF8-safe lcfirst.
	 *
	 * @param String $string
	 * @return String
	 */
    public static function lcfirst($string)
    {
        return self::toLower(self::substr($string, 0, 1)) . self::substr($string, 1);
    }

    /**
     * Converts the '@' char to the string "[at]".
     *
     * @param String $email
     * @return String
     */
    public static function emailToAntispamString($email)
    {
      return str_replace("@", "[at]", $email);
    }

    /**
     * @param String $string
     * @return String
     */
    public static function quoteSingle($string)
    {
    	return str_replace("'", "\\'", $string);
    }

    /**
     * @param String $string
     * @return String
     */
    public static function quoteDouble($string)
    {
      return str_replace('"', '\"', $string);
    }

    private static $htmlToTagsFrom = null, $htmlToTagsTo = null;


    /**
     * @param String $html
     * @return String
     */
	public static function addCrLfToHtml($html)
	{
		if (self::isEmpty($html))
		{
			return '';
		}
		
		if (is_null(self::$htmlToTagsFrom))
    	{
    		self::$htmlToTagsFrom = array('</div>','</p>', '<br/>','<br>', '</li>');
    		self::$htmlToTagsTo = array('</div>'. PHP_EOL,'</p>'. PHP_EOL, '<br/>'. PHP_EOL,'<br>'. PHP_EOL, '</li>'. PHP_EOL);
    	}
    	$html = str_replace(self::$htmlToTagsFrom, self::$htmlToTagsTo, $html);
        $html = preg_replace('/<\/h(\d)>/i', '</h$1>' . PHP_EOL, $html);
        return $html;
	}
    

	/**
     * Returns true if the given $string contains upper cased letters,
     * false otherwise.
     *
     * @param String $string
     * @return Boolean
     */
	public static function containsUppercasedLetter($string)
	{
		return preg_match('/[A-Z]+/', $string) != 0;
	}

    /**
     * Returns true if the given $string contains lower cased letters,
     * false otherwise.
     *
     * @param String $string
     * @return Boolean
     */
	public static function containsLowercasedLetter($string)
	{
		return preg_match('/[a-z]+/', $string) != 0;
	}

    /**
     * Returns true if the given $string contains digits, false otherwise.
     *
     * @param String $string
     * @return Boolean
     */
	public static function containsDigit($string)
	{
		return preg_match('/[0-9]+/', $string) != 0;
	}

    /**
     * Returns true if the given $string contains letters, false otherwise.
     *
     * @param String $string
     * @return Boolean
     */
	public static function containsLetter($string)
	{
		return preg_match('/[a-zA-Z]+/', $string) != 0;
	}

    /**
     * Returns true when the string contains only whitespaces or null.
     *
     * @param String $string
     * @return Boolean
     */
    public static function isEmpty($string)
    {
    	return $string === null || (!is_array($string) && strlen(trim($string))) == 0;
    }
    
    /**
     * Returns true when the string contains at least one non whitespace character
     *
     * @param String $string
     * @return Boolean
     */
    public static function isNotEmpty($string)
    {
    	return !self::isEmpty($string);
    }

    /**
     * @param mixed $value
     * @return String
     */
    public static function JSONEncode($value)
    {
    	return JsonService::getInstance()->encode($value);
    }

    /**
     * @param String $string
     * @return mixed
     */
    public static function JSONDecode($string)
    {
    	return JsonService::getInstance()->decode($string);
    } 
}