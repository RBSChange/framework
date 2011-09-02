<?php
class c_Package
{
	/**
	 * @var string	
	 */
	private $type;
	
	/**
	 * @var string	
	 */
	private $name;

	/**
	 * @var string	
	 */
	private $version;
	
	/**
	 * @var string	
	 */
	private $hotfix;
	
	/**
	 * @var string	
	 */
	private $downloadURL;
	
	/**
	 * @var string	
	 */
	private $releaseURL;
	
	/**
	 * @var string
	 */
	private $projectHomePath;
	
	/**
	 * @var string
	 */
	private $temporaryPath;
	
	/**
	 * @var string
	 */
	private $hotfixHistory;


	/**
	 * @return string
	 */
	public function getHotfixHistory()
	{
		return $this->hotfixHistory;
	}

	/**
	 * @param string $hotfixHistory
	 */
	public function setHotfixHistory($hotfixHistory)
	{
		$this->hotfixHistory = $hotfixHistory;
	}
	
	/**
	 * @return array
	 */
	public function getHotfixArray()
	{
		$result = array();
		if ($this->getHotfixHistory())
		{
			foreach (explode(',', $this->getHotfixHistory()) as $str) 
			{
				$hotfix = trim($str);
				if (!empty($hotfix)) {$result[] = $hotfix;}
			}
		}
		if ($this->getHotfix() && !in_array($this->getHotfix(), $result))
		{
			$result[] = $this->getHotfix();
		}
		return $result;
	}

	/**
	 * @return the $temporaryPath
	 */
	public function getTemporaryPath()
	{
		return $this->temporaryPath;
	}

	/**
	 * @param string $temporaryPath
	 */
	public function setTemporaryPath($temporaryPath)
	{
		$this->temporaryPath = $temporaryPath;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = empty($type) ? null : $type;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = empty($name) ? null : $name;
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	public function setVersion($version)
	{
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function getHotfix()
	{
		return $this->hotfix;
	}

	/**
	 * @param string $hotfix
	 */
	public function setHotfix($hotfix)
	{
		$this->hotfix = empty($hotfix) ? null : $hotfix;
	}

	/**
	 * @return string
	 */
	public function getDownloadURL()
	{
		return $this->downloadURL;
	}

	/**
	 * @param string $downloadURL
	 */
	public function setDownloadURL($downloadURL)
	{
		$this->downloadURL = $downloadURL;
	}

	/**
	 * @return string
	 */
	public function getReleaseURL()
	{
		return $this->releaseURL;
	}
	
	/**
	 * @param string $releaseURL
	 */
	public function setReleaseURL($releaseURL)
	{
		$this->releaseURL = $releaseURL;
	}

	protected function __construct($projectHomePath)
	{
		$this->projectHomePath = $projectHomePath;
	}
	
	/**
	 * @param DOMElement $node
	 */
	public function populateNode($node)
	{
		$this->populateNodeName($node);
		$this->populateNodeVersion($node);
		$this->populateNodeRelease($node);		
	}	
	
	/**
	 * @param DOMElement $node
	 */
	public function populateNodeName($node)
	{
		if ($this->getType())
		{
			$node->setAttribute('type', $this->getType());
		}
		elseif($node->hasAttribute('type'))
		{
			$node->removeAttribute('type');
		}
		$node->setAttribute('name', $this->getName());	
		
	}
	
	/**
	 * @param DOMElement $node
	 */
	public function populateNodeVersion($node)
	{	
		$attName = 	'version';
		if ($this->getVersion())
		{
			$node->setAttribute($attName, $this->getVersion());
		}
		elseif($node->hasAttribute($attName))
		{
			$node->removeAttribute($attName);
		}
		
		$attName = 'hotfix';
		if ($this->getHotfix())
		{
			$node->setAttribute($attName, $this->getHotfix());
		}
		elseif($node->hasAttribute($attName))
		{
			$node->removeAttribute($attName);
		}
		
		$attName = 'hotfixHistory';
		if ($this->getHotfixHistory())
		{
			$node->setAttribute($attName, $this->getHotfixHistory());
		}
		elseif($node->hasAttribute($attName))
		{
			$node->removeAttribute($attName);
		}
	}
	
	/**
	 * @param DOMElement $node
	 */	
	public function populateNodeRelease($node)
	{
		if ($this->getReleaseURL())
		{
			$node->setAttribute('releaseURL', $this->getReleaseURL());
			
			if ($node->hasAttribute('downloadURL'))
			{
				$node->removeAttribute('downloadURL');
			}
		}
		else
		{
			if ($node->hasAttribute('releaseURL'))
			{
				$node->removeAttribute('releaseURL');
			}
			
			if ($this->getDownloadURL())
			{
				$node->setAttribute('downloadURL', $this->getDownloadURL());
			}
			elseif ($node->hasAttribute('downloadURL'))		
			{
				$node->removeAttribute('downloadURL');
			}
		}
	}
		
	/**
	 * @param DOMElement $package
	 * @return c_Package
	 */
	public static function getInstanceFromPackageElement($package, $projectHomePath)
	{
		$o = new self($projectHomePath);
		
		$o->setType($package->getAttribute('type'));
		$o->setName($package->getAttribute('name'));
		if ($package->hasAttribute('downloadURL'))
		{
			$o->setDownloadURL($package->getAttribute('downloadURL'));
		}
		elseif($package->hasAttribute('releaseURL'))
		{
			$o->setReleaseURL($package->getAttribute('releaseURL'));
		}
		
		if($package->hasAttribute('version'))
		{
			$o->setVersion($package->getAttribute('version'));
		}
		
		if($package->hasAttribute('hotfix'))
		{
			$o->setVersion($package->getAttribute('hotfix'));
		}
		
		//Specifique Release Information
		if ($package->hasAttribute('hotfixHistory'))
		{
			$value = trim($package->getAttribute('hotfixHistory'));
			if (!empty($value)){$o->setHotfixHistory($value);}
		}	
		return $o;	
	}
	
	/**
	 * @param string $type
	 * @param string $name
	 * @param string $projectHomePath
	 * @return c_Package
	 */
	public static function getNewInstance($type, $name, $projectHomePath)
	{
		$o = new self($projectHomePath);
		$o->setType($type);
		$o->setName($name);
		return $o;	
	}
	
	public function getKey()
	{
		return ($this->type) ? $this->type . '/' . $this->name : $this->name;
	}
	
	/**
	 * @return string
	 */
	public function getHotfixedVersion()
	{
		if ($this->hotfix)
		{
			return $this->version . '-' . $this->hotfix;
		}
		return $this->version;
	}
	
	/**
	 * @return integer
	 */
	public function getTypeAsInt()
	{
		if ($this->type === null && $this->name === 'framework')
		{
			return c_ChangeBootStrap::$DEP_FRAMEWORK;
		}
		elseif ($this->name !== null)
		{
			if ($this->type === 'modules')
			{
				return c_ChangeBootStrap::$DEP_MODULE;
			}
			elseif ($this->type === 'libs')
			{
				return c_ChangeBootStrap::$DEP_LIB;
			}
			elseif ($this->type === 'themes')
			{
				return c_ChangeBootStrap::$DEP_THEME;
			}
		}
		return c_ChangeBootStrap::$DEP_UNKNOWN;
	}
	
	/**
	 * @return boolean
	 */
	public function isModule()
	{
		return $this->getTypeAsInt() === c_ChangeBootStrap::$DEP_MODULE;
	}
	
	/**
	 * @return boolean
	 */
	public function islib()
	{
		return $this->getTypeAsInt() === c_ChangeBootStrap::$DEP_LIB;
	}
	
	/**
	 * @return boolean
	 */
	public function isTheme()
	{
		return $this->getTypeAsInt() === c_ChangeBootStrap::$DEP_THEME;
	}
	
	
	/**
	 * @return boolean
	 */
	public function isFramework()
	{
		return $this->getTypeAsInt() === c_ChangeBootStrap::$DEP_FRAMEWORK;
	}
	
	/**
	 * @return boolean
	 */
	public function isStandalone()
	{
		return $this->getDownloadURL() === 'none';
	}	
	
	/**
	 * @return string
	 */
	public function getRelativePath()
	{
		$pathParts = array('');
		if ($this->type) {$pathParts[] = $this->type;}
		$pathParts[] = $this->name;
		return implode('/', $pathParts);
	}
	
	public function getRelativeReleasePath()
	{
		$pathParts = array('');
		if ($this->type) {$pathParts[] = $this->type;}
		$pathParts[] = $this->name;
		$pathParts[] = $this->name . '-' . $this->getHotfixedVersion();
		return implode('/', $pathParts);		
	}
	
	/**
	 * @return f_util_DOMDocument
	 */
	public function getInstallDocument()
	{
		$path = $this->getPath() . '/install.xml';
		if (is_readable($path))
		{
			return f_util_DOMUtils::fromPath($path);
		}
		return null;
	}
	
	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->projectHomePath . $this->getRelativePath();
	}
	
	
	/**
	 * @return boolean
	 */
	public function isInProject()
	{
		return is_dir($this->getPath());
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->name . ($this->getVersion() ? '-' . $this->getHotfixedVersion() : '');		
	}
}