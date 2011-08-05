<?php
class f_web_CSSRule
{
	/**
	 * @var String
	 */
	private $engine = "all.all";
	
	/**
	 * @var String
	 */
	private $label;
	
	/**
	 * @var String
	 */
	private $description;
	
	/**
	 * @var String
	 */
	private $selectorText;
	
	/**
	 * @var String
	 */
	private $declarationBlock;
	
	
	private $comments;
	/**
	 * @var CSSDeclaration[]
	 */
	private $declarations = array();
	
	/**
	 * @return CSSDeclaration[]
	 */
	public function getDeclarations()
	{
		return $this->declarations;
	}
	
	/**
	 * @param CSSDeclaration $declaration
	 */
	public function addDeclaration($declaration)
	{
		$this->declarations[] = $declaration;
	}
	
	/**
	 * @return String
	 */
	public function getSelectorText()
	{
		return $this->selectorText;
	}
	
	/**
	 * @param String $selectorText
	 */
	public function setSelectorText($selectorText)
	{
		$this->selectorText = $selectorText;
	}
	
	public function setComments($comments)
	{
		$this->comments = $comments;
		foreach ($comments as $comment)
		{
			if (strpos($comment, "@label") === 0)
			{
				$this->setLabel(trim(substr($comment, 6)));
			}
			else if (strpos($comment, "@description") === 0)
			{
				$this->setDescription(trim(substr($comment, 12)));
			}
		}
	}
	
	/**
	 * @return string
	 */
	public function getEngine()
	{
		return $this->engine;
	}
	
	/**
	 * @param string $forEngine
	 */
	public function setEngine($forEngine)
	{
		$this->engine = $forEngine;
	}
	
	/**
	 * @param string $engine
	 * @return boolean
	 */
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
	
	public function getCSSForEngine($fullEngine)
	{
		if ($this->engine === $fullEngine && $this->engine !== "all.all")
		{
			$declarations = $this->getDeclarations();
		}
		else if ($this->engine === "all.all")
		{
			$declarations = $this->getSpecializedDeclarations($fullEngine);	
		}
		else 
		{
			$declarations = array();
		}
		if(count($declarations) === 0)
		{
			return "";
		}
		return $this->getSelectorText() . "{\n" . $this->renderDeclarations($declarations, true) . "}\n";	
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
			$declarations = array();
			foreach ($this->getDeclarations() as $declaration) 
			{	
				$cssText = $declaration->getAsCSS($fullEngine, $skin);
				if ($cssText !== null)
				{
					$declarations[] = $cssText;
				}
			}
			if (count($declarations) > 0)
			{
				return $this->getSelectorText() . "{" . implode("", $declarations) . "}\n";	
			}
		}
		return null;		
	}	
	
	private function renderDeclarations($declarations, $ignoreComments = false)
	{
		$cssText = '';
		foreach ($declarations as $declaration) 
		{
			if ($ignoreComments)
			{
				$cssText .= $declaration->getCSS() . "\n";
			}
			else 
			{
				$cssText .= $declaration->getCommentedCSS() . "\n";
			}
		}
		return $cssText;
	}
	
	/**
	 * @param String $fullEngine
	 * @return CSSDeclaration[]
	 */
	function getSpecializedDeclarations($fullEngine)
	{
		$declarations = array();
		foreach ($this->getDeclarations() as $declaration)
		{
			$declarationEngine = $declaration->getEngine();
			if ($declarationEngine === $fullEngine)
			{
				$declarations[] = $declaration;
			}
		}
		return $declarations;
	}
	
	/**
	 * @param String $fullEngine
	 * @return CSSDeclaration[]
	 */
	function getNonSpecializedDeclarations()
	{
		$declarations = array();
		foreach ($this->getDeclarations() as $declaration)
		{
			$declarationEngine = $declaration->getEngine();
			if ($declarationEngine === null || $declarationEngine === "all.all")
			{
				$declarations[] = $declaration;
			}
		}
		return $declarations;
	}
	
	function getCommentedCSS()
	{
		$cssText = "";
		if ($this->label !== null)
		{
			$cssText .= "/*@label $this->label*/\n";
		}
		if ($this->description !== null)
		{
			$cssText .= "/*@description $this->description*/\n";
		}
		return $cssText . $this->selectorText . " {\n" . $this->renderDeclarations($this->getDeclarations(), false) . "}\n";
	}
	

	/**
	 * @return String
	 */
	function __toString()
	{
		return $this->getCommentedCSS();
	}
	/**
	 * @return String
	 */
	public function getDescription()
	{
		return $this->description;
	}
	
	/**
	 * @return String
	 */
	public function getLabel()
	{
		return $this->label;
	}
	
	/**
	 * @param String $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}
	
	/**
	 * @param String $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}
	
    /**
     * @var String 
     */
    private $mediaType;
    
    /**
     * @return String 
     */
    public function getMediaType() 
    {
		return $this->mediaType;
    }

    /**
     * @param String $type 
     */
    public function setMediaType($type) 
    {
		$this->mediaType = $type;
    }

}