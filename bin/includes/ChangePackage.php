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
	private $archivePath;
	
	/**
	 * @return string
	 */
	public function getArchivePath()
	{
		return $this->archivePath;
	}

	/**
	 * @param string $archivePath
	 */
	public function setArchivePath($archivePath)
	{
		$this->archivePath = $archivePath;
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
		
		if ($this->getArchivePath() && is_readable($this->getArchivePath()))
		{
			$node->setAttribute('archivePath', $this->getArchivePath());
		}
		else
		{
			$node->removeAttribute('archivePath');
		}
	}
		
	/**
	 * @param DOMElement $package
	 * @param string $projectHomePath
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
		
		if ($package->hasAttribute('archivePath'))
		{
			$o->setArchivePath($package->getAttribute('archivePath'));
		}
		return $o;	
	}
	
	/**
	 * @param DOMElement $package
	 * @param string $projectHomePath
	 * @return c_Package
	 */
	public static function getInstanceFromRepositoryElement($package, $projectHomePath)
	{
		$types = array('change-lib' => null, 'lib' => 'libs', 'theme' => 'themes', 'module' => 'modules');
		$o = new self($projectHomePath);
		if (array_key_exists($package->nodeName, $types))
		{
			$o->setType($types[$package->nodeName]);
			$o->setName($package->getAttribute('name'));
			if ($package->hasAttribute('downloadURL'))
			{
				$o->setDownloadURL($package->getAttribute('downloadURL'));
			}
			elseif($package->hasAttribute('releaseURL'))
			{
				$o->setReleaseURL($package->getAttribute('releaseURL'));
			}
		
			if ($package->hasAttribute('version'))
			{
				$o->setVersion($package->getAttribute('version'));
			}
		
			if ($package->hasAttribute('archivePath'))
			{
				$o->setArchivePath($package->getAttribute('archivePath'));
			}
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
	public function isValid()
	{
		return $this->getTypeAsInt() !== c_ChangeBootStrap::$DEP_UNKNOWN;
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
		$pathParts[] = $this->name . '-' . $this->getVersion();
		return implode('/', $pathParts);		
	}
	
	/**
	 * @return string
	 */
	public function populateDefaultDownloadUrl()
	{
		$downloadURL = $this->getReleaseURL() . $this->getRelativeReleasePath() . '.zip';
		$this->setDownloadURL($downloadURL);
		$this->setReleaseURL(null);
		return $downloadURL;
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
		return $this->name . ($this->getVersion() ? '-' . $this->getVersion() : '');		
	}
}