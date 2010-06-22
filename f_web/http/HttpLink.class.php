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
		    if ($argSeparator === null) {$argSeparator = self::STANDARD_SEPARATOR;}
			$url['query'] = http_build_query($queryParameters, '', $argSeparator);
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
		
		if (isset($parts['path']))
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
}