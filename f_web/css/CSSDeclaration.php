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
	 * @return boolean
	 */
	public function isImportant()
	{
		return $this->important;
	}
	
	/**
	 * @param boolean $important
	 */
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
	
	/**
	 * @param string $engine
	 * @return boolean
	 */
	protected function engineHandlesVars($engine)
	{
		return false;
	}
	
	/**
	 * @param string $engine
	 * @return boolean
	 */
	protected function isCompatibleWithEngine($engine)
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
	 * @param f_web_CSSStylesheet $stylesheet
	 * @return String | null
	 */
	public function getAsCSS($fullEngine, $skin, $stylesheet)
	{
		if ($this->isCompatibleWithEngine($fullEngine))
		{
			$value = $this->calculateValue($this->getPropertyValue(), $fullEngine, $skin, $stylesheet);
			if ($value === '') {return null;}
			
			if ($this->getPropertyName() === '-moz-binding')
			{
				$matches = array();
				if (preg_match('/url\(binding:(.*)\)/', $value, $matches))
				{
					$infos = explode('#', $matches[1]);	    	    
				    $link = LinkHelper::getUIChromeActionLink('uixul', 'GetBinding')->setQueryParameter('binding', $infos[0]);
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
	
	/**
	 * @param String $fullEngine
	 * @param f_web_CSSVariables $skin
	 * @param f_web_CSSStylesheet $stylesheet
	 * @return String | null
	 */
	protected function calculateValue($value, $fullEngine, $skin, $stylesheet)
	{
		if ($this->getSkinRef() !== null && $skin instanceof f_web_CSSVariables)
		{
			if (!($this instanceof f_web_CSSVarDeclaration))
			{
				Framework::warn(__METHOD__ . ' Skin vars are deprecated out of css var declaration: ' . $this->getSkinRef());
			}
			$value = $skin->getCSSValue($this->getSkinRef(), $value);
		}
		if ($value !== '' && !$this->engineHandlesVars($fullEngine))
		{
			$value = preg_replace_callback('#var\(([a-z\-]+)\)#', array($stylesheet, 'replaceMatchingVar'), $value);
		}
		return $value;
	}
}

class f_web_CSSVarDeclaration extends f_web_CSSDeclaration
{
	/**
	 * @param String $fullEngine
	 * @param f_web_CSSVariables $skin
	 * @param f_web_CSSStylesheet $stylesheet
	 * @return String | null
	 */
	public function getAsCSS($fullEngine, $skin, $stylesheet)
	{
		if ($this->engineHandlesVars($fullEngine))
		{
			return parent::getAsCSS($fullEngine, $skin, $stylesheet);
		}
		if ($this->isCompatibleWithEngine($fullEngine))
		{
			$value = $this->calculateValue($this->getPropertyValue(), $fullEngine, $skin, $stylesheet);
			$stylesheet->addVar($this->getVarName(), $value);
		}
		return null;
	}
	
	/**
	 * @return string
	 */
	public function getVarName()
	{
		return substr($this->getPropertyName(), 4);
	}
	
	/**
	 * @return string
	 */
	public function getVarValue()
	{
		return substr($this->getPropertyName(), 4);
	}
}