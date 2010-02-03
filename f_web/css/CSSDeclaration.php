<?php

class f_web_CSSDeclaration
{
	/**
	 * @var String
	 */
	private $skinRef;
	
	/**
	 * @var String
	 */
	private $engine = "all.all";
	
	/**
	 * @var boolean
	 */
	private $important = false;
	
	/**
	 * @var String
	 */
	private $cssText;
	
	/**
	 * @var String
	 */
	private $propertyName;
	
	/**
	 * @var String
	 */
	private $propertyValue;
	

	/**
	 * @return String
	 */
	public function getSkinRef()
	{
		return $this->skinRef;
	}
	
	/**
	 * @param String $skinRef
	 */
	public function setSkinRef($skinRef)
	{
		$this->skinRef = $skinRef;
	}
	
	/**
	 * @return String
	 */
	public function getCssText()
	{
		return $this->cssText;
	}
	

	/**
	 * @param String $cssText
	 */
	public function setCssText($cssText)
	{
		$this->cssText = $cssText;
		$sepIndex = strpos($cssText, ':');
		if ($sepIndex === false)
		{
			throw new Exception("Invalid CSS Declaration");
		}
		
		$this->setImportant(strpos($cssText, '!important') !== false);
		$this->propertyName = trim(substr($cssText, 0, $sepIndex));
		$this->propertyValue = trim(str_replace('!important', '', substr($cssText, $sepIndex + 1)));
	}
	
	/**
	 * @return String
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	/**
	 * @return String
	 */
	public function getPropertyValue()
	{
		return $this->propertyValue;
	}
	
	/**
	 * @return Boolean
	 */
	public function isImportant()
	{
		return $this->important;
	}
	
	public function setImportant($important)
	{
		$this->important = $important;
	}	
	
	public function setComments($comments)
	{
		foreach ($comments as $comment)
		{
			if (strpos($comment, "@var") === 0)
			{
				$this->setSkinRef(trim(substr($comment, 4)));
			}
		}
	}
	
	/**
	 * @return unknown
	 */
	public function getEngine()
	{
		return $this->engine;
	}
	
	/**
	 * @param unknown_type $forEngine
	 */
	public function setEngine($forEngine)
	{
		$this->engine = $forEngine;
	}
	
	function getCSS()
	{
		return $this->renderCSS(true);
	}
	
	private function renderCSS($ignoreComments)
	{
		$cssText = "\t" . $this->getPropertyName() . ': ' . $this->getPropertyValue();
		if ($this->isImportant())
		{
			$cssText .= '!important';
		}	
		if ($this->skinRef !== null)
		{
			$cssText .= "/*@var $this->skinRef*/";
		}
		
		return $cssText . ";";
	}
	
	public function getCommentedCSS()
	{
		return $this->renderCSS(false);
	}
	
	
	/**
	 * @return String
	 */
	function __toString()
	{
		return $this->getCommentedCSS();
	}
	/**
	 * @param String $propertyName
	 */
	public function setPropertyName($propertyName)
	{
		$this->propertyName = $propertyName;
	}
	
	/**
	 * @param String $propertyValue
	 */
	public function setPropertyValue($propertyValue)
	{
		$this->propertyValue = $propertyValue;
	}
	
	private function isCompatibleWithEngine($engine)
	{
		if ($this->engine === "all.all" || $this->engine === "image.all" || $this->engine === $engine) {return true;}
		
		if ($this->engine[0] === '!')
		{
			$forEngine = substr($this->engine, 1);
			
			if ($engine === $forEngine)
			{
				return false;
			}
			return true;
		}
		$targetParts = explode('.', $engine);
		list($forEngineType, $forEngineVersion) = explode('.', $this->engine);
		return ($forEngineVersion === "all" && $targetParts[0] === $forEngineType);
	}
	
	/**
	 * @param String $fullEngine
	 * @param f_web_CSSVariables $skin
	 * @return String | null
	 */
	public function getAsCSS($fullEngine, $skin)
	{
		if ($this->isCompatibleWithEngine($fullEngine))
		{
			if ($this->skinRef !== null && $skin !== null)
			{	
				$value = $skin->getCSSValue($this->skinRef, $this->getPropertyValue());
			}
			else
			{
				$value = $this->getPropertyValue();			
			}
			if ($value === '') {return null;}
			if ($this->getPropertyName() === '-moz-binding')
			{
				$matches = array();
				if (preg_match('/url\(binding:(.*)\)/', $value, $matches))
				{
					$infos = explode('#', $matches[1]);	    	    
				    $link = LinkHelper::getUIChromeActionLink('uixul', 'GetBinding')->setQueryParametre('binding', $infos[0]);
					if (isset($infos[1]))
				    {
				        $link->setFragment($infos[1]);
				    }
					$value = 'url(' . $link->getUrl() . ')';
				}
			}
			elseif (strpos($fullEngine, 'xul.') === 0)
			{
				if (strpos($value, 'url(/') === 0)
				{
					$value = str_replace('url(/', 'url(' . Framework::getUIBaseUrl() . '/', $value);
				}
			}
			
			$cssText = $this->getPropertyName() . ': ' . $value;
			if ($this->isImportant())
			{
				$cssText .= '!important';
			}
			return $cssText . ';';			
		}
		return null;
	}
}