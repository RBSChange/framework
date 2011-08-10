<?php
abstract class f_web_HttpLink implements f_web_Link
{	
    const SITE_PATH = '/index.php';
    const UI_PATH = '/xul_controller.php';
    const REWRITING_PATH = '/';
    
    const STANDARD_SEPARATOR = '&';
    const ESCAPE_SEPARATOR = '&amp;';
      
       
    /**
     * @param String $scheme
     * @param String $host
     * @param String $path
     * @param array $queryParameters
     * @param String $fragment
     * @param String $port
     * @return String
     */
	protected final function buildUrl($scheme, $host, $path = null, $queryParameters = array(), $fragment = null, $argSeparator = null, $port = null)
	{
		$url = array('scheme' => $scheme, 'host' => $host, 'path' => $path);

		if ($port !== NULL) {$url['port'] = $port;}		
		if ($fragment !== NULL) {$url['fragment'] = $fragment;}

		if (count($queryParameters) > 0)
		{
			self::sortQueryParamerters($queryParameters);
		    if ($argSeparator === null) {$argSeparator = self::STANDARD_SEPARATOR;}
			$query = http_build_query($queryParameters, '', $argSeparator);
			if (!empty($query)) {$url['query'] = $query;}
		}
		return  self::http_build_url($url);	    
	}
	
	
	/**
	 * @return String
	 */
	public function __toString()
	{
		return $this->getUrl();
	}

	/**
	 * @param array $parts
	 * @return string 
	 */
	public static function http_build_url($parts)
	{
		if (extension_loaded('http'))
		{
			return http_build_url($parts);	
		}
		
		//'scheme' :// 'user' : 'pass' @ 'host' 'path' ? 'query' # 'fragment';
		$url = array($parts['scheme'], '://');
		if (isset($parts['user']))
		{
			$url[] = $parts['user'];
			if (isset($parts['pass'])) 
			{
				$url[] = ':' .$parts['pass'];
			}
			$url[] = '@';
		}
		$url[] = $parts['host'];
		
		if (isset($parts['port']) && $parts['port'] != '80' && $parts['port'] != '443')
		{
			$url[] = ':' . $parts['port'];
		}		
		if (isset($parts['path']) && $parts['path'] != '')
		{
			$url[] = $parts['path'][0] == '/' ? $parts['path'] : '/' . $parts['path'];
		}
		if (isset($parts['query']))
		{
			$url[] = '?' . $parts['query'];
		}
		if (isset($parts['fragment']))
		{
			$url[] = '#' . $parts['fragment'];
		}
		return implode('', $url);	
	}
	
	/**
	 * @param array $queryParamerters 
	 */
	public static function sortQueryParamerters(&$queryParamerters)
	{
		if (is_array($queryParamerters))
		{
			ksort($queryParamerters);
			foreach ($queryParamerters as &$subSet) {if (is_array($subSet)) {ksort($subSet);}}
		}
	}
	
	/**
	 * Transforms an multi-dimensonnial query parameter array into a flat array
	 * 
	 * @param array $array
	 * @param String $key
	 * @return array 
	 */
	public static function flattenArray($array, $key = null)
	{
		$result = array();
		foreach ($array as $index => $value)
		{
			if ($value === null)
			{
				continue;
			}
			if (!is_array($value))
			{
				if ($key === null)
				{
					$result[$index] = $value;
				}
				else
				{
					$result[$key . '[' . $index . ']'] = $value;
				}
			}
			else
			{
				$result = array_merge($result, self::flattenArray($value, $index));
			}
		}
		return $result;
	}
}