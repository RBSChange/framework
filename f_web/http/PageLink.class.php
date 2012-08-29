<?php
class f_web_PageLink extends f_web_HttpLink
{
	private $scheme;
	private $host;
	
	private $queryParameters = array();
	private $path;
	
	private $localizeByPath;

	private $fragment;
	private $argSeparator;
	
	/**
	 * @param f_web_PageLink $protocol
	 * @param f_web_PageLink $domaine
	 * @param boolean $localizeByPath
	 */
	public function __construct($protocol, $domaine, $localizeByPath = false)
	{
		$this->scheme = $protocol;
		$this->host = $domaine;
		$this->localizeByPath = $localizeByPath;
	}
	
	/**
	 * @param string $pageName
	 * @return f_web_PageLink
	 */
	public function setPageName($pageName)
	{
		$this->path = $pageName;
		return $this;
	}
	
	/**
	 * @param string $fragment
	 * @return f_web_PageLink
	 */
	public function setFragment($fragment)
	{
		$this->fragment = $fragment;
		return $this;
	}
	
	/**
	 * @param string $argSeparator
	 * @return f_web_PageLink
	 */
	public function setArgSeparator($argSeparator)
	{
		$this->argSeparator = $argSeparator;
		return $this;
	}
	
	/**
	 * @param array $queryParameters
	 * @return f_web_PageLink
	 */
	public function setQueryParameters($queryParameters)
	{
		$this->queryParameters = $queryParameters;
		return $this;
	}	
	
	/**
	 * @param string $key
	 * @param string|array $value
	 * @return f_web_PageLink
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
	 * @return string
	 */
	public function getUrl()
	{
		$path =  (empty($this->path)) ? self::REWRITING_PATH : $this->path;
		
		if ($this->localizeByPath)
		{
			$rq = RequestContext::getInstance();
			if ($path == self::REWRITING_PATH)
			{
				if ($rq->getDefaultLang() != $rq->getLang())
				{
					$path = self::REWRITING_PATH.$rq->getLang().$path;
				}
			} 
			else 
			{
				$path = self::REWRITING_PATH.$rq->getLang().$path;
			}
		}	
		return $this->buildUrl($this->scheme, $this->host, $path, $this->queryParameters, 
			$this->fragment, $this->argSeparator);
	}
	
	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
}