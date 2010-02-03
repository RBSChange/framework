<?php
class f_util_StringUtils
{
	const CASE_SENSITIVE   = true;
	const CASE_INSENSITIVE = false;

	const SKIP_FIRST = true;
	const SKIP_NONE  = false;

	const TO_LOWER_CASE = 1;
	const TO_UPPER_CASE = 2;
	const STRIP_ACCENTS = 3;

	// static $_cache_caseToUnderscore = array();
	public static function return_bytes($val)
	{
	   $val = trim($val);
	   $last = strtolower($val{strlen($val)-1});
	   switch($last)
	   {
	       // Le modifieur 'G' est disponible depuis PHP 5.1.0
	       case 'g':
		   $val *= 1024;
	       case 'm':
		   $val *= 1024;
	       case 'k':
		   $val *= 1024;
	   }

	   return $val;
	}

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
	 * @param String $value
	 * @param String $toEncoding
	 * @return String
	 * @deprecated use convertEncoding()
	 */
	public static function utf8Decode($value, $toEncoding = 'ISO-8859-1')
	{
		if (mb_detect_encoding(" $value ", 'UTF-8, ISO-8859-1') == 'UTF-8')
		{
    		$value = utf8_decode($value);
    	}
    	return $value;
	}

	/**
	 * @param String $haystack
	 * @param String $needle
	 * @param Boolean $caseSensitive self::CASE_INSENSITIVE or self::CASE_SENSITIVE
	 * @return Boolean
	 */
	public static function endsWith($haystack, $needle, $caseSensitive = self::CASE_INSENSITIVE)
	{
		if ($caseSensitive === self::CASE_SENSITIVE)
		{
			return substr($haystack, -strlen($needle)) == $needle;
		}
		return strcasecmp(substr($haystack, -strlen($needle)), $needle) === 0;
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
			return substr($haystack, 0, strlen($needle)) == $needle;
		}
		return strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0;
	}
	
	/**
	 * @param String $str the string you search in
	 * @param String $needle the string that you search for
	 * @return Boolean 
	 */
	public static function contains($str, $needle)
	{
		return strpos($str, $needle) !== false;
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
		for ($i=0; $i<strlen($str); $i++)
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
		return strtolower(join('_', $tokens));
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
	 * @param String $str
	 * @return String
	 *
	 * @deprecated Use self::lcfirst() instead.
	 */
	public static function lowerCaseFirstLetter($str)
	{
		return self::lcfirst($str);
	}

	/**
	 * @deprecated
	 */
	final static function getFileExtension($filename, $includeDot = false, $nb_ext = 1)
	{
		return f_util_FileUtils::getFileExtension($filename, $includeDot, $nb_ext);
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
		if (is_array($in_array) === false)
			return $in_array;

		if ($in_array != null && count($in_array) > 0)
		{
			$l_keys = array_keys($in_array);
			$l_nbElementsInArray = count($l_keys);

			for ($i = 0; $i < $l_nbElementsInArray; $i++)
			{
				$l_element = $in_array[$l_keys[$i]];

				if (is_array($l_element))
					$in_array[$l_keys[$i]] = self::transcodeStringsInArray($l_element);
				else
					$in_array[$l_keys[$i]] = self::transcodeString($in_array[$l_keys[$i]]);
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
		$l_result = "";

		if ($in_toTranscode != null && strlen($in_toTranscode) > 0)
		{
			if (strpos($in_toTranscode, "%u") === false)
				$l_result = $in_toTranscode;
			else
			{
				$l_separatedChars = explode("%u", $in_toTranscode);
				$l_nbElementsInArray = count($l_separatedChars);

				for ($i = 0; $i < $l_nbElementsInArray; $i++)
				{
					$l_value = substr($l_separatedChars[$i], 0, 4);

					if (self::is_hexa($l_value))
					{
						if (strlen($l_separatedChars[$i]) > 4)
						{
							$l_result .= "&#x";
							$l_result .= $l_value;
							$l_result .= ";";
							$l_result .= substr($l_separatedChars[$i], 4);
						}
						else
							$l_result .= "&#x".$l_separatedChars[$i].";";
					}
					else
						$l_result .= $l_separatedChars[$i];
				}
			}
		}

		if (strlen($l_result) == 0)
			$l_result = $in_toTranscode;

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
	 *
     * @return true or false
     **/
	public static function is_hexa($in_hexaTest = null)
	{
		if (!is_string($in_hexaTest)) { return false; }

		$l_value = trim(strtolower($in_hexaTest));
		$l_allowed = array("a","b","c","d","e","f","0","1","2","3","4","5","6","7","8","9");

		for($j = 0; $j < strlen($l_value); $j++)
		{
			if (!in_array($l_value[$j], $l_allowed))
				return false;
		}

		return true;
	}

	/**
	 * @param Array $array
	 * @param String $prep
	 * @return String
	 *
	 * @deprecated Use var_export($var, true) instead.
	 */
	 public static function parray($array , $prep='')
	 {
		$ret = "";
		$prep = "$prep|";

		if (is_array($array))
		{
			while(list($key,$val) = each($array))
			{
				$type = gettype($val);

				if(is_array($val))
				{
					$line = "-+ $key ($type)\n";
					$line .= self::parray($val,"$prep ");
				}
				else
				{
					$line = "-> $key = \"$val\" ($type)\n";
				}
				$ret .= $prep.$line;
			}
		}
		else
			$ret = $array;
		return $ret;
	}

	/**
	 * The aim of this function is to return the var_dump value
	 * under the string form, it's very usefull for debugging
	 *
	 * @param mixed in_varToDump Variable to dump
	 * @return string var_dump under string form
	 *
	 * @deprecated Use var_export($varToDump, true);
	 */
	 public static function var_dump_desc($in_varToDump = null)
	 {
	 	ob_start();
		var_dump($in_varToDump);
		return ob_get_clean();
	 }

	 /**
	 * The aim of this function is to return an associative array
	 * from a given "associative" string :
	 *
	 *  "name1: value1; name2: value2;" --> array(
	 *                                         0 => "name1: value1; name2: value2;"
	 *                                         "name1" => "value1",
	 *                                         "name2" => "value2"
	 *                                      )
	 *
	 * @param mixed in_varToDump Variable to dump
	 * @return string var_ump under string form
	 */
	 public static function parse_assoc_string($in_assoc_string = '')
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
            if (isset($declaration[0])
            && isset($declaration[1]))
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

	public static function array_to_string($array)
	{
	    $string = "";
	    foreach ($array as $key => $value)
	    {
	        if (trim($key))
	        {
    	        $string .= sprintf(
    	            "%s: %s; ",
    	            trim($key),
    	            trim($value)
    	        );
	        }
	    }
	    return trim($string);
	}
	
	/**
	 * Remove every nl/crlf from the given string.
	 * @param string $string
	 * @return string
	 */
	public static function stripnl($string)
	{
	    return trim(preg_replace('/\s+/', ' ', preg_replace('/[\r\n]/', ' ', $string)));
	}
	
	/**
	 * Properly quote the given string for direct JS use.
	 * @param string $string
	 * @return string
	 */
	public static function jsquote($string)
	{
	   return self::stripnl(self::quoteDouble($string));
	}
	
	/**
	 * @param Mixed $mixed
	 * @param Boolean $convertArrayAsObject
	 * @return String
	 */
	public static function php_to_js($mixed, $convertArrayAsObject = false)
	{
	    if (is_numeric($mixed))
	    {
	        $js = strval($mixed);
	    }
	    else if (is_string($mixed))
	    {
	        $js = sprintf('"%s"', self::stripnl(self::quoteDouble($mixed)));
	    }
	    else if (is_array($mixed))
	    {
	        $js = array();

	        if ($convertArrayAsObject)
	        {
    	        foreach ($mixed as $key => $value)
    	        {
    	            if (!is_numeric($key))
    	            {
    	               $js[] = sprintf(
    	                   '%s: %s',
    	                   $key,
    	                   self::php_to_js($value)
    	               );
    	            }
    	        }

    	        $js = '{' . implode(', ', $js) . '}';
	        }
	        else
	        {
	            $mixed = array_values($mixed);

	            foreach ($mixed as $value)
    	        {
    	            $js[] = self::php_to_js($value);
    	        }

    	        $js = '[' . implode(', ', $js) . ']';
	        }
	    }
	    else if (is_object($mixed))
	    {
	        // @fixme not implemented :
            $js = 'undefined';
	    }
	    else if (is_null($mixed))
	    {
            $js = 'null';
	    }
	    else if ($mixed)
	    {
            $js = 'true';
	    }
	    else
	    {
            $js = 'false';
	    }

	    return $js;
	}

	/**
	 * @param String $string
	 * @param Integer $maxLen
	 * @param String $dots
	 * @return String
	 */
	public static function shortenString($string, $maxLen = f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING_DEAFULT_MAX_LENGTH, $dots = '...')
	{
	    if (self::strlen($string) > $maxLen)
		{
           $string = self::substr(
                $string,
                0,
                $maxLen - self::strlen($dots)
            ) . $dots;
        }
        return $string;
	}

	/**
	 * @param String $string
	 * @param Array<String> $highlights
	 * @param String $begin
	 * @param String $end
	 * @return String
	 */
	public static function highlightString($string, $highlights, $begin = '<strong>', $end = '</strong>')
	{
	    if (!is_array($highlights))
	    {
	        $highlights = array($highlights);
	    }
	    foreach ($highlights as $highlight)
        {
           $string = preg_replace(
              '/(' . $highlight . ')/i',
              $begin . '$1' . $end,
              $string
           );
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

    /**
     * @param String $string
     * @return String
     */
    public static function strip_accents($string)
	{
		return self::handleAccent($string, self::STRIP_ACCENTS);
	}

	/**
	 * UTF8-safe strtolower.
	 *
	 * @param String $string
	 * @return String
	 */
    public static function strtolower($string)
    {
        return self::handleAccent($string, self::TO_LOWER_CASE);
    }

	/**
	 * UTF8-safe strtoupper.
	 *
	 * @param String $string
	 * @return String
	 */
    public static function strtoupper($string)
    {
        return self::handleAccent($string, self::TO_UPPER_CASE);
    }

	/**
	 * UTF8-safe ucfirst.
	 *
	 * @param String $string
	 * @return String
	 */
    public static function ucfirst($string)
    {
        return self::strtoupper(self::substr($string, 0, 1)) . self::substr($string, 1);
    }

	/**
	 * UTF8-safe lcfirst.
	 *
	 * @param String $string
	 * @return String
	 */
    public static function lcfirst($string)
    {
        return self::strtolower(self::substr($string, 0, 1)) . self::substr($string, 1);
    }

    private static $from_accents = null, $to_accents = null;
    private static $lower = null, $upper = null;

    public static function handleAccent($string, $action)
    {
    	/*
    	 Keep this here to be able to generate $accents,

    		$accents = array();
    		$accents[] = array(
    		'lower' => array("à", "â", "ä", "á", "ã", "å"),
    		'upper' => array("À", "Â", "Ä", "Á", "Ã", "Å"),
    		'strip' => array("a", "A")
    		);
    		$accents[] = array(
    		'lower' => array("æ"),
    		'upper' => array("Æ"),
    		'strip' => array("ae", "AE")
    		);
    		$accents[] = array(
    		'lower' => array("ç"),
    		'upper' => array("Ç"),
    		'strip' => array("c", "C")
    		);
    		$accents[] = array(
    		'lower' => array("è", "ê", "ë", "é"),
    		'upper' => array("È", "Ê", "Ë", "É"),
    		'strip' => array("e", "E")
    		);
    		$accents[] = array(
    		'lower' => array("ð"),
    		'upper' => array("Ð"),
    		'strip' => array("ed", "ED")
    		);
    		$accents[] = array(
    		'lower' => array("ì", "î", "ï", "í"),
    		'upper' => array("Ì", "Î", "Ï", "Í"),
    		'strip' => array("i", "I")
    		);
    		$accents[] = array(
    		'lower' => array("ñ"),
    		'upper' => array("Ñ"),
    		'strip' => array("n", "N")
    		);
    		$accents[] = array(
    		'lower' => array("ò", "ô", "ö", "ó", "õ", "ø"),
    		'upper' => array("Ò", "Ô", "Ö", "Ó", "Õ", "Ø"),
    		'strip' => array("o", "O")
    		);
    		$accents[] = array(
    		'lower' => array("œ"),
    		'upper' => array("Œ"),
    		'strip' => array("oe", "OE")
    		);
    		$accents[] = array(
    		'lower' => array("ù", "û", "ü", "ú"),
    		'upper' => array("Ù", "Û", "Ü", "Ú"),
    		'strip' => array("u", "U")
    		);
    		$accents[] = array(
    		'lower' => array("ý", "ÿ"),
    		'upper' => array("Ý", "Ÿ"),
    		'strip' => array("y", "Y")
    		);

    	}
    	*/

        switch ($action)
        {
            case self::STRIP_ACCENTS:
            	if (is_null(self::$from_accents))
            	{
            		self::$from_accents = array('à', 'â', 'ä', 'á', 'ã', 'å', 'À', 'Â', 'Ä', 'Á', 'Ã', 'Å', 'æ', 'Æ', 'ç', 'Ç', 'è', 'ê', 'ë', 'é', 'È', 'Ê', 'Ë', 'É', 'ð', 'Ð', 'ì', 'î', 'ï', 'í', 'Ì', 'Î', 'Ï', 'Í', 'ñ', 'Ñ', 'ò', 'ô', 'ö', 'ó', 'õ', 'ø', 'Ò', 'Ô', 'Ö', 'Ó', 'Õ', 'Ø', 'œ', 'Œ', 'ù', 'û', 'ü', 'ú', 'Ù', 'Û', 'Ü', 'Ú', 'ý', 'ÿ', 'Ý', 'Ÿ');
					self::$to_accents = array('a', 'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A', 'A', 'ae', 'AE', 'c', 'C', 'e', 'e', 'e', 'e', 'E', 'E', 'E', 'E', 'ed', 'ED', 'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'n', 'N', 'o', 'o', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'O', 'O', 'oe', 'OE', 'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'y', 'y', 'Y', 'Y');
            	}
				return str_replace(self::$from_accents, self::$to_accents, $string);
            case self::TO_LOWER_CASE:
            	if (is_null(self::$lower))
            	{
					self::$lower = array('à', 'â', 'ä', 'á', 'ã', 'å', 'æ', 'ç', 'è', 'ê', 'ë', 'é', 'ð', 'ì', 'î', 'ï', 'í', 'ñ', 'ò', 'ô', 'ö', 'ó', 'õ', 'ø', 'œ', 'ù', 'û', 'ü', 'ú', 'ý', 'ÿ');
					self::$upper = array('À', 'Â', 'Ä', 'Á', 'Ã', 'Å', 'Æ', 'Ç', 'È', 'Ê', 'Ë', 'É', 'Ð', 'Ì', 'Î', 'Ï', 'Í', 'Ñ', 'Ò', 'Ô', 'Ö', 'Ó', 'Õ', 'Ø', 'Œ', 'Ù', 'Û', 'Ü', 'Ú', 'Ý', 'Ÿ');
                }
                 return strtolower(str_replace(self::$upper, self::$lower, $string));
            case self::TO_UPPER_CASE:
            	if (is_null(self::$lower))
            	{
					self::$lower = array('à', 'â', 'ä', 'á', 'ã', 'å', 'æ', 'ç', 'è', 'ê', 'ë', 'é', 'ð', 'ì', 'î', 'ï', 'í', 'ñ', 'ò', 'ô', 'ö', 'ó', 'õ', 'ø', 'œ', 'ù', 'û', 'ü', 'ú', 'ý', 'ÿ');
					self::$upper = array('À', 'Â', 'Ä', 'Á', 'Ã', 'Å', 'Æ', 'Ç', 'È', 'Ê', 'Ë', 'É', 'Ð', 'Ì', 'Î', 'Ï', 'Í', 'Ñ', 'Ò', 'Ô', 'Ö', 'Ó', 'Õ', 'Ø', 'Œ', 'Ù', 'Û', 'Ü', 'Ú', 'Ý', 'Ÿ');
                }
                return strtoupper(str_replace(self::$lower, self::$upper, $string));
            default :
            	throw new Exception("Unkown handleAccent action $action");
        }
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
    	// FIXME intbonjf 2007-11-06: I think "\'" should be '\'', right?
    	return str_replace("'", "\'", $string);
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
     * @param String $string
     * @param Boolean $translateUri
     * @param Boolean $convertNlToSpace
     * @return String
     */
    public static function htmlToText($string, $translateUri = true, $convertNlToSpace = false)
    {
    	if ($string === null)
    	{
    		return "";
    	}
    	$string = self::addCrLfToHtml($string);
    	if ($translateUri)
        {
            $string = preg_replace(
            	array('/<a[^>]+href="([^"]+)"[^>]*>([^<]+)<\/a>/i', '/<img[^>]+alt="([^"]+)"[^>]*\/>/i'),
            	array('$2 [$1]', K::CRLF . '[$1]' . K::CRLF), $string);
        }
        $string = trim(html_entity_decode(strip_tags($string), ENT_QUOTES, 'UTF-8'));
        if ($convertNlToSpace)
        {
            $string = str_replace(K::CRLF, ' ', $string);
        }
        return $string;
    }

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
    		self::$htmlToTagsTo = array('</div>'. K::CRLF,'</p>'. K::CRLF, '<br/>'. K::CRLF,'<br>'. K::CRLF, '</li>'. K::CRLF);
    	}
    	$html = str_replace(self::$htmlToTagsFrom, self::$htmlToTagsTo, $html);
        $html = preg_replace('/<\/h(\d)>/i', '</h$1>' . K::CRLF, $html);
        return $html;
	}
    
    /**
     * Parse HTML content (coming from a RichText block, for example) in order to produce a valid content.
     *
     * Default parsing process :
     *  - Complete internal URLs.
     *  - Check images availability (and try to generate the missing ones).
     *  - Report broken links and resources.
     *
     * @param string $string The original HTML content.
     * @return string Parsed (and checked) content.
     * @deprecated use f_util_HtmlUtils::renderHtmlFragment
     */
    public static function parseHtml($string)
    {
        return f_util_HtmlUtils::renderHtmlFragment($string);
    }


    public static function mergeAttributes($current, $new)
    {
        $current = explode(";", $current);
        $new = explode(";", $new);
        $mergeArray = array();
        foreach ($new as $newDeclaration)
        {
            if ($newDeclaration)
            {
                list($attribute, $value) = explode(':', $newDeclaration);
                $attribute = trim($attribute);
                $value = trim($value);
                $mergeArray[$attribute] = $value;
            }
        }
        foreach ($current as $currentDeclaration)
        {
            if ($currentDeclaration)
            {
                list($attribute, $value) = explode(':', $currentDeclaration);
                $attribute = trim($attribute);
                $value = trim($value);
                if (!isset($mergeArray[$attribute]))
                {
                    $mergeArray[$attribute] = $value;
                }
            }
        }
        $merge = '';
        foreach ($mergeArray as $attribute => $value)
        {
            if ($attribute && $value)
            {
                $merge .= sprintf('%s: %s; ', $attribute, $value);
            }
        }
        return trim($merge);
    }

    /**
     * @param String $string
     * @return String
     */
    public static function cleanString($string)
    {
        $string = self::htmlToText($string, false,true);
        $string = self::strip_accents($string);
        $string = preg_replace(array('/[^a-z0-9]/i', '/\s[a-z0-9]{1,2}\s/i'), array(' ', ' '), $string);
        $string = str_replace('  ', ' ', $string);
        return trim(strtolower($string));
    }

    /**
     * @param String $string
     * @return String
     */
	public static function ordString($string)
    {
	    $int = "";
	    for($i=0;$i<strlen($string);$i++)
		{
			$int .= strval(ord($string[$i]));

		}
	   return $int;

    }

    /**
     * This function takes a string and replace the prefix $old_prefix by $new_prefix.
     * If the prefix doesn't exist in the string, nothing is done.
     * @param String $old_prefix The prefix we want to change
     * @param String $new_prefix The new prefix
     * @param String $string The string to work on
     * @return String
     */
    public static function prefixReplace($old_prefix, $new_prefix, $string)
    {
    	// if we found the prefix into the string, we replace, else we don't do anything.
    	$len = mb_strlen($old_prefix);
    	if (strncmp($string, $old_prefix, $len) == 0) {
    		$string = $new_prefix . mb_substr($string, $len);
    	}
    	return ($string);
    }

    /**
     * This function take a string and uppercase the letter of the $nbLetter.
     * @param : $string : The string we want to upper the first letter.
     * @param : $nbLetter : Optionnal, the number of letter we want to upper. By default, only the first letter
     * is uppercased.
     * @param : $forceLower : Optionnal, if set to true, force the rest of the string to be lowercase. False by default.
     *
     * @deprecated Uh, uh... amazing method. Sometimes mb_*, sometimes not... Please, do not use it!
     */
    public static function prefixUpper($string, $nbLetter=1, $forceLower=false) {
    	$len = mb_strlen($string);
    	$upper = mb_strtoupper(substr($string, 0, $nbLetter));
    	if ($nbLetter >= $len) {
    		return ($upper);
    	} else {
    		if ($forceLower == true)
	    		$lower = mb_strtolower(mb_substr($string, $nbLetter, strlen($string)));
    		else
	    		$lower = mb_substr($string, $nbLetter, strlen($string));
    	}
    	return ($upper . $lower);
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
		return self::containsCharBetween($string, 'A', 'Z');
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
		return self::containsCharBetween($string, 'a', 'z');
	}

    /**
     * Returns true if the given $string contains digits, false otherwise.
     *
     * @param String $string
     * @return Boolean
     */
	public static function containsDigit($string)
	{
		return self::containsCharBetween($string, '0', '9');
	}

    /**
     * Returns true if the given $string contains letters, false otherwise.
     *
     * @param String $string
     * @return Boolean
     */
	public static function containsLetter($string)
	{
		return self::containsUppercasedLetter($string) || self::containsLowercasedLetter($string);
	}

	/**
	 * Returns true if the given $string contains chars between $first and $last.
	 *
	 * @param unknown_type $string
	 * @param unknown_type $first
	 * @param unknown_type $last
	 * @return unknown
	 */
	public static function containsCharBetween($string, $first, $last)
	{
		$found = false;
		for ($i=0 ; $i<self::strlen($string) && !$found ; $i++)
		{
			$char = self::substr($string, $i, 1);
			if ($char >= $first && $char <= $last)
			{
				$found = true;
			}
		}
		return $found;
	}


	/**
	 * Performs a regular expression search on the given $subject with the given
	 * $pattern. This method deals correctly with UTF-8 strings.
	 *
	 * @param String $pattern
	 * @param String $subject
	 * @param Array $matches
	 * @return Integer
	 */
	public static function utf8Ereg($pattern, $subject, &$matches)
	{
		mb_regex_encoding('utf-8');
		return mb_ereg($pattern, self::utf8Encode($subject), $matches);
	}

	/**
	 * @param String $pattern
	 * @param String $replacement
	 * @param String $subject
	 * @param String $option
	 * @return String
	 */
	public static function utf8EregReplace($pattern, $replacement, $subject, $option = null)
	{
		mb_regex_encoding('utf-8');
		return mb_ereg_replace($pattern, self::utf8Encode($replacement), self::utf8Encode($subject), $option);
	}

	/**
	 * Performs a case-INsensitive regular expression search on the given
	 * $subject with the given $pattern. This method deals correctly with UTF-8
	 * strings.
	 *
	 * @param String $pattern
	 * @param String $subject
	 * @param Array $matches
	 * @return Integer
	 */
	public static function utf8Eregi($pattern, $subject, &$matches)
	{
		mb_regex_encoding('utf-8');
		return mb_eregi($pattern, self::utf8Encode($subject), $matches);
	}


	/**
	 * Sent content usually involves "special tags", like {firstname} or {lastname}, used
	 * to integrate personalized data (based on recipient's data).
	 * This method is used to parse any kind of textual content in order to replace these tags
	 * with the given contextual data.
	 *
	 * For example :
	 *  - input : "Hello {fullname} !" and array("fullname" => "world")
	 *  - output : "Hello world !"
	 *
	 * @param string $content Content (HTML or plain text) to parse for "special tags".
	 * @param array $substData Associative array of content to match with "special tags".
	 * @return string Parsed content
	 */
	public static function parseTextContent($content, $substData = array())
    {
    	if (!empty($substData))
    	{
    		$substitueFrom = array();
    		$substitueTo = array();
    		foreach ($substData as $name => $value)
    		{
    			$substitueFrom[] = '{' . $name . '}';
    			$substitueTo[] = $value;
    		}
    		$content = str_replace($substitueFrom, $substitueTo, $content);
    	}
        $content = preg_replace('/\{[a-z0-9_-]+\}/i', '', $content);

        return trim($content);
    }

    /**
     * Returns true when the string contains only whitespaces or null.
     *
     * @param String $string
     * @return Boolean
     */
    public static function isEmpty($string)
    {
    	return is_null($string) || (!is_array($string) && strlen(trim($string))) == 0;
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