<?php

class f_web_CSSStylesheet
{
	/**
	 * @var String
	 */
	private $cssRules = array();

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
		$media = null;
		foreach ($this->cssRules as $rule)
		{
			$ruleText = $rule->getAsCSS($fullEngine, $skin);
			$currentMedia = $rule->getMediaType();
			if ($currentMedia!= $media)
			{
				if ($media !== null)
				{
					$result[] = "}\n";
				}
				if ($currentMedia !== null)
				{
					$result[] ='@media ' . $currentMedia . " {\n";
				}
				$media = $currentMedia;
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
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' OBSOLETE XML CSS ' . $filePath);
			}
			$sheet->loadXML(file_get_contents($filePath));
		}
		else
		{
			$sheet->loadCSS(file_get_contents($filePath));
		}
		return $sheet;
	}

	private function addForAttribute(&$writer, &$element)
	{
		$forEngine = $element->getEngine();
		if ($forEngine !== null)
		{
			list($type, $version) = explode('.', $forEngine);
			if ($version == "all" && $type == "all")
			{
				return;
			}

			if ($version == "all")
			{
				$forEngine = $type;
			}
			else
			{
				$forEngine = $type . ":" . $version;
			}
			if ($type == "xul")
			{
				$writer->writeAttribute('ctype', $type);
			}
			else if ($type == "!xul")
			{
				$writer->writeAttribute('ctype', 'html');
			}
			else
			{
				$writer->writeAttribute('for', $forEngine);
			}
		}
	}

	/**
	 * @param XMLWriter $xmlWriter
	 * @param String $textContent
	 * @param String $type
	 */
	private function addSelectorElement(&$xmlWriter, $textContent, $type)
	{
		$xmlWriter->startElement("selector");
		$classPos = strpos($textContent, '.');
		if ($classPos !== false)
		{
			$xmlWriter->writeAttribute('class', substr($textContent, $classPos + 1));
			$textContent = substr($textContent, 0, $classPos);
		}
		else
		{
			$idPos = strpos($textContent, '#');
			if ($idPos !== false)
			{
				$xmlWriter->writeAttribute('id', substr($textContent, $idPos + 1));
				$textContent = substr($textContent, 0, $idPos);
			}

		}

		$pseudoElementPos = strpos($textContent, '::');
		if ($pseudoElementPos !== false)
		{
			$xmlWriter->writeAttribute('pseudoelement', substr($textContent, $pseudoElementPos + 2));
			$textContent = substr($textContent, 0, $pseudoElementPos);
		}
		else
		{
			$pseudoClassPos = strpos($textContent, ':');
			if ($pseudoClassPos !== false)
			{
				$xmlWriter->writeAttribute('pseudoclass', substr($textContent, $pseudoClassPos + 1));
				$textContent = substr($textContent, 0, $pseudoClassPos);
			}
		}


		if ($type !== null)
		{
			$xmlWriter->writeAttribute('type', $type);
		}
		$xmlWriter->text($textContent);
		$xmlWriter->endElement('selector');
	}


	/**
	 * Enter description here...
	 *
	 * @param XMLWriter $xmlWriter
	 * @param String $selectorText
	 */
	private function buildXMLSelector(&$xmlWriter, $selectorText)
	{
		$selectorTextLength = strlen($selectorText);
		$i = 0;
		$inComment = false;
		$currentSelector = "";
		while ($i < $selectorTextLength)
		{
			if ($selectorText[$i] === '/' && (isset($selectorText[$i + 1]) && $selectorText[$i + 1] === '*'))
			{
				$inComment = true;
				++$i;
			}
			else if ($selectorText[$i] === '*' && (isset($selectorText[$i + 1]) && $selectorText[$i + 1] === '/'))
			{
				if (!$inComment)
				{
					throw new Exception("Unexpected end of comment");
				}
				$inComment = false;
				++$i;
			}
			else if ($selectorText[$i] === ' ')
			{
				if (!f_util_StringUtils::isEmpty($currentSelector))
				{
					$this->addSelectorElement($xmlWriter, $currentSelector, 'descendant');
					$currentSelector = "";
				}
			}
			else if ($selectorText[$i] === ',')
			{
				if (!f_util_StringUtils::isEmpty($currentSelector))
				{
					$this->addSelectorElement($xmlWriter, $currentSelector, null);
					$currentSelector = "";
				}
			}
			else if ($selectorText[$i] === '>')
			{
				if (!f_util_StringUtils::isEmpty($currentSelector))
				{
					$this->addSelectorElement($xmlWriter, $currentSelector, "child");
					$currentSelector = "";
				}
			}
			else if ($selectorText[$i] === '+')
			{
				if (!f_util_StringUtils::isEmpty($currentSelector))
				{
					$this->addSelectorElement($xmlWriter, $currentSelector, "adjacent");
					$currentSelector = "";
				}
			}
			else
			{
				if (!$inComment)
				{
					$currentSelector .= $selectorText[$i];
				}
			}
			$i++;
		}
		if (!f_util_StringUtils::isEmpty($currentSelector))
		{
			$this->addSelectorElement($xmlWriter, $currentSelector, null);
		}
	}
	/**
	 * @param XMLWriter $xmlWriter
	 * @param f_web_CSSDeclaration $declaration
	 */
	private function addDeclaration(&$xmlWriter, $declaration)
	{
		$xmlWriter->startElement('declaration');
		$this->addForAttribute($xmlWriter, $declaration);
		$xmlWriter->writeAttribute('property', $declaration->getPropertyName());
		if ($declaration->isImportant())
		{
			$xmlWriter->writeAttribute('important', 'true');
		}

		$propertyValue = $declaration->getPropertyValue();
		$matches = array();
		if (preg_match('/url\(([^\)]*)\)/', $propertyValue, $matches))
		{
			$url = $matches[1];
			if (strpos($url, '/media/') === 0)
			{
				$imageAttr = null;
				if (strpos($url, '/media/frontoffice/') === 0)
				{
					$imageAttr = 'front/' . substr($url, 19);
				}
				else if (strpos($url, '/media/backoffice/') === 0)
				{
					$imageAttr = 'back/' . substr($url, 18);
				}
				if ($imageAttr !== null)
				{
					$propertyValue = preg_replace('/url\(([^\)]*)\)/', '', $propertyValue);
					$xmlWriter->writeAttribute('image', $imageAttr);
				}
			}
		}
		$skinRef = $declaration->getSkinRef();
		if ($skinRef !== null)
		{
			$xmlWriter->writeAttribute('skin-ref', $skinRef);
		}
		$xmlWriter->text($propertyValue);
		$xmlWriter->endElement();
	}

	public function getAsXML()
	{
		$xmlWriter = new XMLWriter();

		$xmlWriter->openMemory();

		$xmlWriter->startDocument('1.0', 'UTF-8');
		$xmlWriter->setIndent(true);
		$xmlWriter->setIndentString("\t");
		$xmlWriter->startElement('stylesheet');
		foreach ($this->cssRules as $rule)
		{
			$xmlWriter->startElement('style');
			$this->addForAttribute($xmlWriter, $rule);
			$this->buildXMLSelector($xmlWriter, $rule->getSelectorText());
			foreach ($rule->getDeclarations() as $declaration)
			{
				$this->addDeclaration($xmlWriter, $declaration);

			}
			$xmlWriter->endElement();
		}
		$xmlWriter->endElement();
		$xmlWriter->endDocument();
		return $xmlWriter->flush();
	}

	/**
	 * @param String $cssText
	 */
	public function loadCSS($cssText, $currentEngine = null)
	{

		$i = 0;
		$cssTextLength = strlen($cssText);
		$inComment = false;
		$inParenthesis = false;
		$inDeclarationBlock = false;
		$inSelector = true;
		$inMediaType = false;
		$inMediaRule = false;
		$selectorText = "";
		$declarationText = "";
		$mediaText = "";
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
			if ($cssText[$i] === '@' && $inSelector)
			{
			    // handle import
			    if (substr($cssText, $i, 7) === '@import' && !$inComment)
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
			    else if (substr($cssText, $i, 6) === '@media' && !$inComment)
				{
					$inMediaRule = true;
					$inMediaType = true;
					$i += 6;
					$inSelector = false;
			    }
			    
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
						$lastDeclaration = new f_web_CSSDeclaration();
						$lastDeclaration->setCssText(trim($declarationText));
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
				else if ($inMediaRule)
				{
					$inMediaRule = false;
					$mediaText = "";
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
			    if ($inMediaType)
			    {
					$inMediaType = false;
					$inSelector = true;
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
					if ($mediaText != "")
					{
						$currentRule->setMediaType(trim($mediaText));
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
					$lastDeclaration = new f_web_CSSDeclaration();
					$lastDeclaration->setCssText(trim($declarationText));
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
				if (!$inDeclarationBlock && !$inComment && !$inMediaType)
				{
					$selectorText .= $cssText[$i];
				}
				else if ($inDeclarationBlock && !$inComment)
				{
					$declarationText .= $cssText[$i];
				}
				else if ($inMediaType && !$inComment)
				{
					$mediaText .= $cssText[$i];
				}
				else if ($inComment)
				{
					$commentText .= $cssText[$i];
				}
			}
			$i++;
		}
	}

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
			throw new Exception('Imported CSS not found : '. $url);
		}
	}

	/**
	 * Parses a change formatted xml stylesheet
	 *
	 * @param String $xmlText
	 */
	public function loadXML($xmlText)
	{
		$domCss = new DOMDocument();
		$loadResult = $domCss->loadXML($xmlText);
		if ($loadResult === false)
		{
			Framework::error(__METHOD__ . '$xmlText must be a valid xml');
			return;
		}
		if ($domCss->documentElement->hasAttribute('id'))
		{
			$this->setId($domCss->documentElement->getAttribute('id'));
		}
		else
		{
			$this->setId("");
		}

		foreach ($domCss->getElementsByTagName('import') as $import)
		{
			$stylename = $import->getAttribute("id");
			$path = StyleService::getInstance()->getSourceLocation($stylename);
			if ($path)
			{
				$this->loadXML(file_get_contents($path));
			}
		}

		foreach ($domCss->getElementsByTagName('style') as $style)
		{
			$currentRule = new f_web_CSSRule();
			$currentRule->setSelectorText($this->buildSelectorStringForStyle($style, $currentRule));
			if ($style->hasAttribute("label"))
			{
				$currentRule->setLabel($style->getAttribute("label"));
			}
			$this->buildEngineCommentForCSSElement($style, $currentRule);
			foreach ($style->getElementsByTagName('declaration') as $xmlDeclaration)
			{

				$declaration = $this->buildDeclaration($xmlDeclaration);
				$this->buildEngineCommentForCSSElement($xmlDeclaration, $declaration);
				$currentRule->addDeclaration($declaration);
			}
			$this->cssRules[] = $currentRule;
		}
	}

	/**
	 * @param DOMNode $style
	 * @param String $stylesheetId
	 * @return String
	 */
	private function buildSelectorStringForStyle(&$style, &$rule)
	{
		$stylesheetId = $this->getId();
		$selectorString = "";
		$selectors = $style->getElementsByTagName('selector');
		$selectorsCount = $selectors->length;
		for ($i = 0; $i < $selectorsCount; ++$i)
		{
			$selector = $selectors->item($i);
			$selectorString .= trim($selector->textContent);
			// TODO: remove this convention
			if ($selector->hasAttribute("label"))
			{
				$rule->setLabel($selector->getAttribute("label"));
			}

			if ($selector->hasAttribute("attribute"))
			{
				$selectorString .= '[' . $selector->getAttribute("attribute");
				if ($selector->hasAttribute("value"))
				{
					$selectorString .= '="' . $selector->getAttribute("value") . '"';
				}
				$selectorString .= ']';
			}

			if ($selector->hasAttribute("attributes"))
			{
				$attributes = f_util_StringUtils::parse_assoc_string($selector->getAttribute("attributes"));
				foreach ($attributes as $attributeName => $attributeValue)
				{
					if ($attributeName)
					{
						$selectorString .= '[' . $attributeName;
						if ($attributeValue)
						{
							$selectorString .= '="' . $attributeValue . '"';
						}
						$selectorString .= ']';
					}
				}
			}

			if ($selector->hasAttribute("class"))
			{
				$class = $selector->getAttribute("class");
				if (f_util_StringUtils::isEmpty($class))
				{
					$selectorString .= '.' . $stylesheetId;
				}
				else
				{
					$selectorString .= '.' . $class;
				}
			}
			else if ($selector->hasAttribute("id"))
			{
				$id = $selector->getAttribute("id");
				if (f_util_StringUtils::isEmpty($id))
				{
					$selectorString .= '#' . $stylesheetId;
				}
				else
				{
					$selectorString .= '#' . $id;
				}
			}

			if ($selector->hasAttribute("pseudoclass"))
			{
				$selectorString .= ':' . $selector->getAttribute("pseudoclass");
			}

			if ($selector->hasAttribute("pseudoelement"))
			{
				$selectorString .= '::' . $selector->getAttribute("pseudoelement");
			}

			if ($i != $selectorsCount - 1)
			{
				$selectorString .= $this->buildSelectorSeparatorForSelector($selector);
			}
		}
		return $selectorString;
	}

	/**
	 * @param DOMNode $selector
	 * @return String
	 */
	private function buildSelectorSeparatorForSelector(&$selector)
	{
		switch ($selector->getAttribute("type"))
		{
			case 'descendant':
				return ' ';
			case 'child':
				return '>';
			case 'adjacent':
				return '+';
			default:
				return ',';
		}
	}

	/**
	 * @param DOMNode $style
	 * @param f_web_CSSRule $rule
	 */
	private function buildEngineCommentForCSSElement(&$node, &$element)
	{
		if ($node->hasAttribute("for"))
		{
			$for = $node->getAttribute("for");
			if (strpos($for, ':') === false)
			{
				$for .= ".all";
			}
			else
			{
				$for = str_replace(':', '.', $for);
			}
			$element->setEngine($for);

		}

		if ($node->hasAttribute("ctype"))
		{
			$ctype = $node->getAttribute("ctype");
			if ($ctype == "xul" && substr($element->getEngine(), 0, 3) !== "xul")
			{
				$element->setEngine("xul.all");
			}
			if ($ctype == "html" && $element->getEngine() === 'all.all')
			{
				$element->setEngine("!xul.all");
			}
		}
	}

	/**
	 * @param DOMNode $xmlDeclaration
	 * @return f_web_CSSDeclaration
	 */
	private function buildDeclaration($xmlDeclaration)
	{
		$declaration = new f_web_CSSDeclaration();
		$declaration->setPropertyName($xmlDeclaration->getAttribute("property"));
		if ($xmlDeclaration->hasAttribute("binding"))
		{
			$this->buildBindingDeclaration($declaration, $xmlDeclaration->getAttribute("binding"));
		}
		else if ($xmlDeclaration->hasAttribute("image"))
		{
			$this->buildImageProperty($declaration, $xmlDeclaration->getAttribute("image"), trim($xmlDeclaration->textContent));
		}
		else if ($xmlDeclaration->hasAttribute("icon"))
		{
			$this->buildIconProperty($declaration, $xmlDeclaration->getAttribute("icon"), trim($xmlDeclaration->textContent));
		}
		else
		{
			$declaration->setPropertyValue(trim($xmlDeclaration->textContent));
		}

		if ($xmlDeclaration->hasAttribute("skin-ref"))
		{
			$declaration->setSkinRef($xmlDeclaration->getAttribute("skin-ref"));
		}

		if ($xmlDeclaration->hasAttribute("important"))
		{
			$declaration->setImportant($xmlDeclaration->getAttribute("important") === "true");
		}
		return $declaration;
	}

	/**
	 * @param f_web_CSSDeclaration $declaration
	 * @param string $binding
	 */
	private function buildBindingDeclaration($declaration, $binding)
	{
		$declaration->setPropertyName('-moz-binding');
		$declaration->setPropertyValue('url(binding:' . $binding . ')');
	}

	private function buildImageProperty($declaration, $imageData, $textContent)
	{
		if (is_numeric($imageData))
		{
			//TODO inthause : peut etre une image de la mediathèque ?
			Framework::error(__METHOD__ . ' Invalid image name :' . $imageData);
			$imageSrc = null;
		}
		else
		{
			$imageSrc = MediaHelper::getStaticUrl($imageData);
			$imageSrc = substr($imageSrc, strpos($imageSrc, '/', 8));

		}

		if ($imageSrc != null)
		{
			$declaration->setPropertyValue("url($imageSrc) " . $textContent);
		}
	}

	private function buildIconProperty($declaration, $rawIconData, $textContent)
	{
		$iconData = explode('.', $rawIconData);
		$iconLayout = '';

		if (count($iconData) == 3)
		{
			list($iconName, $iconFormat, $iconSize) = $iconData;

			if ($iconSize == 'shadow')
			{
				$iconLayout = MediaHelper::LAYOUT_SHADOW;
				$iconSize = $iconFormat;
				$iconFormat = null;
			}
			else
			{
				$iconFormat = '.' . $iconFormat;
			}
		}
		else
		{
			list($iconName, $iconInfo) = $iconData;

			if (($iconInfo == MediaHelper::IMAGE_PNG) || ($iconInfo == MediaHelper::IMAGE_GIF))
			{
				$iconSize = null;
				$iconFormat = '.' . $iconInfo;
			}
			else
			{
				$iconSize = $iconInfo;
				$iconFormat = null;
			}
		}
		$imageSrc = MediaHelper::getIcon($iconName, $iconSize, $iconFormat, $iconLayout);
		$imageSrc = substr($imageSrc, strpos($imageSrc, '/', 8));
		$declaration->setPropertyValue('url(' . $imageSrc . ') ' . $textContent);
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
}
