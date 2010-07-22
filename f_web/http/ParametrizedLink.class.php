<?php
class f_web_ParametrizedLink extends f_web_HttpLink
{      
	private $queryParameters = array();
	 
    private $scheme;
    private $host;
    private $path;  

    private $fragment;
    private $argSeparator;
    
    public function __construct($protocol, $domaine, $path = self::SITE_PATH)
    {
        $this->scheme = $protocol;
        $this->host = $domaine;
        $this->path = $path;
    }
	
	/**
	 * @return array
	 */
	protected function getQueryParametres()
	{
		return $this->queryParameters;
	}
	
    /**
     * @param String $fragment
     * @return f_web_ParametrizedLink
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
        return $this;
    }
    
    /**
     * @param String $argSeparator
     * @return f_web_ParametrizedLink
     */
    public function setArgSeparator($argSeparator)
    {
        $this->argSeparator = $argSeparator;
        return $this;
    }
    

	/**
	 * @param array $queryParameters
	 * @return f_web_ParametrizedLink
	 */
	public function setQueryParameters($queryParameters)
	{
		if (!is_array($queryParameters))
		{
			$this->queryParameters = array();
		}
		else
		{
			$this->queryParameters = $queryParameters;
		}
		return $this;
	}
	
	/**
	 * @deprecated use setQueryParameters
	 * @param array $queryParameters
	 * @return f_web_ParametrizedLink
	 */
	public function setQueryParametres($queryParameters)
	{
		return $this->setQueryParameters($queryParameters);
	}
	
	/**
	 * @param String $key
	 * @param String|array $value
	 * @return f_web_ParametrizedLink
	 */
	public function setQueryParameter($key, $value)
	{
		if ($value === null)
		{
			if (isset($this->queryParameters[$key]))
			{
				unset($this->queryParameters[$key]);
			}
		}
		else
		{
			$this->queryParameters[$key] = $value;
		}
		return $this;
	}

	/**
	 * @deprecated use setQueryParameter
	 * @param String $key
	 * @param String|array $value
	 * @return f_web_ParametrizedLink
	 */
	public function setQueryParametre($key, $value)
	{
		return $this->setQueryParameter($key, $value);		
	}
	
	
    /**
	 * @return String
	 */
	public function getUrl()
	{
	    return $this->buildUrl($this->scheme, $this->host, $this->path, $this->getQueryParametres()
	            , $this->fragment, $this->argSeparator);
	}

	/**
	 * @param String $url
	 * @return f_web_HttpLink
	 */
	public function setParametersFromUrl($url)
	{
		$infos = parse_url($url);
		if (isset($infos['query']))
		{
			parse_str($infos['query'], $this->queryParameters);
		}
		else
		{
			$this->queryParameters = array();
		}

		return $this;
	}	
}

class f_web_ChromeParametrizedLink extends f_web_ParametrizedLink
{
    public function __construct($domaine)
    {
    	parent::__construct('xchrome', $domaine, '/');
    }
    
	public function getUrl()
	{
		$params = $this->getQueryParametres();
		if (!isset($params['uilang']))
		{
			$this->setQueryParameter('uilang', RequestContext::getInstance()->getUILang());
		}
	    return str_replace('/?', '/', parent::getUrl());
	}   
}