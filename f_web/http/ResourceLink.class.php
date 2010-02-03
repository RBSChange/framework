<?php
class f_web_ResourceLink extends f_web_HttpLink
{
    
    protected $path;  
    protected $scheme;
    protected $host;
    
    /**
     * @param String $protocol
     * @param String $domaine
     */
    public function __construct($protocol, $domaine)
    {
        $this->scheme = $protocol;
        $this->host = $domaine;
    }
    
    /**
     * @param String $path
     * @return f_web_ResourceLink
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
    
    /**
	 * @return String
	 */
	public function getUrl()
	{
		if ($this->scheme === NULL)
		{
			return $this->path;
		}
	    return $this->buildUrl($this->scheme, $this->host, $this->path);
	}
}