<?php
class f_web_ResourceLink extends f_web_HttpLink
{
    
    protected $path;  
    protected $scheme;
    protected $host;
    
    private static $cacheVersion = false;
    
    /**
     * @param String $protocol
     * @param String $domaine
     */
    public function __construct($protocol, $domaine)
    {
        $this->scheme = $protocol;
        $this->host = $domaine;
        if (self::$cacheVersion === false)
        {
        	self::$cacheVersion = intval(f_persistentdocument_PersistentProvider::getInstance()->getSettingValue('modules_uixul', 'cacheVersion'));
        }
    }
    
    /**
     * @param String $path
     * @return f_web_ResourceLink
     */
    public function setPath($path)
    {
        $this->path = str_replace('\\', '/', $path);
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
	    return $this->buildUrl($this->scheme, $this->host, $this->path, array('cv' => self::$cacheVersion));
	}
	
	/**
	 * @return String
	 */
	public function getPath()
	{
		return $this->path;
	}	
}