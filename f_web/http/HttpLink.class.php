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
		return http_build_url($url);	    
	}
	
	
	/**
	 * @return String
	 */
	public function __toString()
	{
		return $this->getUrl();
	}
}