<?php
class f_web_CSSStylesheet
{
	/**
	 * @var String
	 */
	private $cssRules = array();
	
	/**
	 * @var f_web_CSSVarDeclaration[]
	 */
	private $varDeclarations = array();

	/**
	 * @var String
	 */
	private $id;

	/**
	 * @return f_web_CSSRules[]
	 */
	public function getCSSRules()
	{
		return $this->cssRules;
	}

	/**
	 * @return String
	 */
	public function __toString()
	{
		return $this->getCommentedCSS();
	}

	/**
	 * @param String $fullEngine
	 * @return String
	 */
	public function getCSSForEngine($fullEngine)
	{
		$result = "";
		foreach ($this->cssRules as $rule)
		{
			$result .= $rule->getCSSForEngine($fullEngine, true);
		}
		return $result;
	}

	/**
	 * @return String
	 */
	public function getCommentedCSS()
	{
		$result = "";
		foreach ($this->cssRules as $rule)
		{
			$result .= $rule->getCommentedCSS();
		}
		return $result;
	}

	/**
	 * @param String $fullEngine
	 * @param f_web_CSSVariables $skin
	 * @return string | null
	 */
	public function getAsCSS($fullEngine, $skin)
	{
		$result = array();
		$atSelectors = array('');
		$atLevel = 0;
		foreach ($this->cssRules as $rule)
		{
			/* @var $rule f_web_CSSRule */
			$ruleText = $rule->getAsCSS($fullEngine, $skin, $this);
			$currentAtLevel = $rule->getAtLevel();
			$currentAtSelector = $rule->getAtSelector();
			if ($currentAtLevel != $atLevel || $currentAtSelector != $atSelectors[$atLevel])
			{
				if ($currentAtLevel < $atLevel)
				{
					for ($i = $currentAtLevel; $i < $atLevel; $i++)
					{
						$result[] = "}\n";
					}
				}
				elseif ($currentAtLevel == $atLevel && $currentAtSelector != $atSelectors[$atLevel])
				{
					$result[] = "}\n";
				}
				elseif ($currentAtLevel > $atLevel+1)
				{
					throw new Exception('Invalid @-rules nesting!');
				}
				
				if ($currentAtSelector !== null)
				{
					$result[] = $currentAtSelector . " {\n";
				}
				$atLevel = $currentAtLevel;
				$atSelectors[$atLevel] = $currentAtSelector;
			}
			
			if ($ruleText !== null)
			{
				$result[] = $ruleText;
			}
		}
		if (count($result) > 0)
		{
			return implode('', $result);
		}
		return null;
	}

	/**
	 * @param String $filePath
	 * @return f_web_CSSStylesheet
	 */
	public static function getInstanceFromFile($filePath)
	{
		$sheet = new f_web_CSSStylesheet();
		if (strpos($filePath, '.xml') === strlen($filePath) - 4)
		{
			Framework::error(__METHOD__ . ' OBSOLETE XML CSS ' . $filePath);
		}
		else
		{
			$sheet->loadCSS(file_get_contents($filePath));
		}
		return $sheet;
	}

	/**
	 * @param String $cssText
	 */
	public function loadCSS($cssText, $currentEngine = null)
	{
		$i = 0;
		$cssTextLength = strlen($cssText);
		$inComment = false;
		$inSimpleQuotedString = false;
		$inDoubleQuotedString = false;
		$inParenthesis = false;
		$inDeclarationBlock = false;
		$inSelector = true;
		$inAtSelector = false;
		$atSelectors = array();
		$atLevel = 0;
		$selectorText = "";
		$declarationText = "";
		$commentText = "";
		$comments = array();
		$currentRule = null;
		$lastDeclaration = null;
		while ($i < $cssTextLength)
		{
			if ($inDeclarationBlock && !$inComment)
			{
				if ($cssText[$i] === '(')
				{
					$inParenthesis = true;
				}
				elseif ($cssText[$i] === ')')
				{
					$inParenthesis = false;
				}
			}
			// handle special chars in strings (for exemple in content: declarations)
			if ($cssText[$i] === "'" && $inDeclarationBlock && !$inComment)
			{
				$inSimpleQuotedString = !($inSimpleQuotedString && $cssText[$i-1] != '\\');
				$declarationText .= $cssText[$i];
			}
			else if ($cssText[$i] === '"' && $inDeclarationBlock && !$inComment)
			{
				$inDoubleQuotedString = !($inDoubleQuotedString && $cssText[$i-1] != '\\');
				$declarationText .= $cssText[$i];
			}
			else if ($inSimpleQuotedString || $inDoubleQuotedString)
			{
				$declarationText .= $cssText[$i];
			}
			// handle @import
		    else if ($cssText[$i] === '@' && $inSelector && substr($cssText, $i, 7) === '@import' && !$inComment)
		    {
				$idx = strpos($cssText, ";", $i);
				if (!$idx)
				{
					throw new Exception('Invalid directive @import syntax');
				}
				$directive = substr($cssText, $i, $idx - $i);
				$matches = array();
				if (preg_match('/url\((.*)\)/', $directive, $matches))
				{
					$this->importCSS(trim($matches[1]));
				}
				else
				{
					throw new Exception('Invalid directive @import syntax');
				}
				$i = $idx;
		    }
		    // handle @-rules
		    // Rules like @xxx ...; //@charset, @namespace, @phonetic-alphabet
		    // -> These @-rules are not very used, so we choose to ignore them here: using these rules will cause an error!
		    else if ($cssText[$i] === '@' && $inSelector && !$inComment && (substr($cssText, $i, 8) === '@charset' || substr($cssText, $i, 10) === '@namespace' || substr($cssText, $i, 18) === '@phonetic-alphabet'))
		    {
		    	throw new Exception('@charset, @namespace and @phonetic-alphabet are not handeled.');
		    }
		    // Rules like @xxx { ... }; //@page, @font-face
			// -> These @-rules work like selectors, so nothing specific to do.
		    // Rules like @xxx { yyy { ... } ... }; //@media, @document, @support, @keyframes
		    // -> These @-rules wil containt selectors or maybe nested @-rules, handle them specifically.
			else if ($cssText[$i] === '@' && $inSelector && !$inComment && substr($cssText, $i, 5) !== '@page' && substr($cssText, $i, 10) !== '@font-face')
			{
				$inAtSelector = true;
				$atLevel++;
				$atSelectors[$atLevel] = '@';
		    }
			else if ($cssText[$i] === '/' && $cssText[$i + 1] === '*' && !$inComment)
			{
				$inComment = true;
				++$i;
			}
			else if ($cssText[$i] === '*' && $cssText[$i + 1] === '/')
			{
				if (!$inComment)
				{
					throw new Exception("Unexpected end of comment");
				}
				if (!f_util_StringUtils::isEmpty($commentText))
				{
					$comments[] = trim($commentText);
				}
				$commentText = "";
				$inComment = false;
				++$i;
			}
			else if ($cssText[$i] === '}' && !$inComment)
			{
				if ($inDeclarationBlock)
				{
					if (!f_util_StringUtils::isEmpty($declarationText))
					{
						if ($lastDeclaration !== null)
						{
							$currentRule->addDeclaration($lastDeclaration);
							$lastDeclaration = null;
						}
						$declarationText = trim($declarationText);
						if (trim($selectorText) === ':root' && f_util_StringUtils::beginsWith($declarationText, 'var-'))
						{
							$lastDeclaration = new f_web_CSSVarDeclaration();
						}
						else
						{
							$lastDeclaration = new f_web_CSSDeclaration();
						}
						$lastDeclaration->setCssText($declarationText);
						if (f_util_ArrayUtils::isNotEmpty($comments))
						{
							$lastDeclaration->setComments($comments);
							$comments = array();
						}
						$declarationText = "";
					}
					
					// End of declarations
					$inSelector = true;

					$inDeclarationBlock = false;
					$selectorText = "";
				}								
				else if ($atLevel > 0)
				{
					unset($atSelectors[$atLevel]);
					$atLevel--;
				}
				
				if ($currentRule)
				{
					if ($lastDeclaration !== null)
					{
						$currentRule->addDeclaration($lastDeclaration);
						$lastDeclaration = null;
					}
					$this->cssRules[] = $currentRule;
					$currentRule = null;
				}
			}
			else if ($cssText[$i] === '{' && !$inComment)
			{
			    if ($inAtSelector)
			    {
					$inAtSelector = false;
			    }
				else
				{
					// Beginning of declarations
					if (!$inSelector)
					{
						throw new Exception("Declarations without a selector");
					}
					$inDeclarationBlock = true;
					$inSelector = false;

					if ($lastDeclaration !== null && $currentRule)
					{
						$currentRule->addDeclaration($lastDeclaration);
						$lastDeclaration = null;
					}

					$currentRule = new f_web_CSSRule();
					$currentRule->setSelectorText(trim($selectorText));
					$currentRule->setAtLevel($atLevel);
					if ($atLevel > 0)
					{
						$currentRule->setAtSelector(trim($atSelectors[$atLevel]));
					}
					if ($currentEngine !== null)
					{
						$currentRule->setEngine($currentEngine);
					}
					if (f_util_ArrayUtils::isNotEmpty($comments))
					{
						$currentRule->setComments($comments);
						$comments = array();
					}
				}
			}
			else if ($cssText[$i] === ";"  && !$inParenthesis && !$inComment)
			{
				if ($inDeclarationBlock && !f_util_StringUtils::isEmpty($declarationText))
				{
					if ($lastDeclaration !== null)
					{
						$currentRule->addDeclaration($lastDeclaration);
						$lastDeclaration = null;
					}
					$declarationText = trim($declarationText);
					if (trim($selectorText) === ':root' && f_util_StringUtils::beginsWith($declarationText, 'var-'))
					{
						$lastDeclaration = new f_web_CSSVarDeclaration();
					}
					else
					{
						$lastDeclaration = new f_web_CSSDeclaration();
					}
					$lastDeclaration->setCssText($declarationText);
					if (f_util_ArrayUtils::isNotEmpty($comments))
					{
						$lastDeclaration->setComments($comments);
						$comments = array();
					}
					$declarationText = "";
				}
			}
			else
			{
				if ($inComment)
				{
					$commentText .= $cssText[$i];
				}
				else if ($inDeclarationBlock)
				{
					$declarationText .= $cssText[$i];
				}
				else if ($inAtSelector)
				{
					$atSelectors[$atLevel] .= $cssText[$i];
				}
				else
				{
					$selectorText .= $cssText[$i];
				}
			}
			$i++;
		}
	}

	/**
	 * @param string $url
	 * @throws Exception
	 */
	private function importCSS($url)
	{
		$parts = explode('/', $url);
		$path = FileResolver::getInstance()
			->setPackageName($parts[1]. '_' . $parts[2])
			->setDirectory($parts[3])->getPath($parts[4]);
		if ($path)
		{
			$engPart = explode('.', $parts[4]);
			if (count($engPart) == 4)
			{
				$engine = $engPart[1] . '.' . $engPart[2];
			}
			else
			{
				$engine = null;
			}
			$css = file_get_contents($path);
			$this->loadCSS($css, $engine);
		}
		else
		{
			throw new Exception('Imported CSS not found: '. $url);
		}
	}

	/**
	 * @return String
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param String $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string[]
	 */
	public function getAllEngine()
	{
		$engines = array();
		foreach ($this->cssRules as $rule)
		{
			$engines[$rule->getEngine()] = true;
			foreach ($rule->getDeclarations() as $declaration)
			{
				$engines[$declaration->getEngine()] = true;
			}
		}
		return array_keys($engines);
	}
	
	/**
	 * @param f_web_CSSVarDeclaration $varDeclaration
	 */
	public function addVar($varName, $varValue)
	{
		$this->varDeclarations[$varName] = $varValue;
	}
	
	/**
	 * @param string[] $matches
	 * @return string
	 */
	public function replaceMatchingVar($matches)
	{
		if (isset($this->varDeclarations[$matches[1]]))
		{
			return $this->varDeclarations[$matches[1]];
		}
		Framework::warn(__METHOD__ . ' Unknown var name: ' . $matches[1]);
		return $matches[0];
	}
	
	// Deprecated.
	
	/**
	 * @deprecated
	 */
	public function getAsXML()
	{
		throw new Exception('CSS as XML are deprecated.');
	}
	
	/**
	 * @deprecated
	 */
	public function loadXML($xmlText)
	{
		throw new Exception('CSS as XML are deprecated.');
	}
}
