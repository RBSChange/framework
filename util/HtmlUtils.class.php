<?php
abstract class f_util_HtmlUtils
{
	const REGEXLINKTAG = "/<a\s+(.*?)>(.*?)<\/a>/i";
	const REGEXIMAGETAG = "/<img\s+(.*?)>/i";
	
	/**
	 * @param String $string
	 * @return String
	 */
	public static function nlTobr($string)
	{
		if (!empty($string))
		{
			return nl2br($string);
		}
		return $string;
	}
	
	/**
	 * @param String $string
	 * @return String
	 */
	public static function textToHtml($string)
	{
		if (!empty($string))
		{
        	$string = htmlspecialchars($string, ENT_COMPAT, "utf-8");
			return nl2br($string);
		}
		return $string;
	}
	
	/**
	 * @var array
	 */
	private static $htmlFilters;
    
    /**
     * Validate <img> and <a> html balise
     * @param String $input;
     * @return String
     */
    public static function renderHtmlFragment($input)
    {   
        $out = preg_replace_callback(self::REGEXIMAGETAG, array(__CLASS__ , 'parseImageTag'), $input);
        $out = preg_replace_callback(self::REGEXLINKTAG, array(__CLASS__ , 'parseLinkTag'), $out);
        
    	if (self::$htmlFilters === null)
		{
			self::$htmlFilters = Framework::getConfigurationValue('framework/htmlfilters', array());
		}
		foreach (self::$htmlFilters as $params)
		{
	        $out = preg_replace_callback($params['regexp'],  explode("::", $params["method"]), $out);
		}
        return $out;
    }  
    
    /**
     * @param String $string
     * @return array
     */
    public static function parseStyleAttributes($string)
    {
        $attributeArray = array();
        if (! empty($string))
        {
            $datas = explode(';', $string);
            foreach ($datas as $item)
            {
                if (!empty($item))
                {
                    $keyValue = explode(':', $item);
                    if (count($keyValue) == 2)
                    {
                       $attributeArray[strtolower(trim($keyValue[0]))] = trim($keyValue[1]); 
                    }
                }
            }
        }
        return $attributeArray;
    }
    
     /**
     * @param array $styleAttributes
     * @return String | null
     */
    public static function buildStyleAttribute($styleAttributes)
    { 
        $results = array();
        if (is_array($styleAttributes) && count($styleAttributes) > 0)
        {
            foreach ($styleAttributes as $name => $value) 
            {
            	$results[] = $name  .': '. $value;
            }
        }
        if (count($results) > 0)
        {
            return implode('; ', $results);
        }
        return null;
    }
    
	/**
	 * @param String $name
	 * @param String $value
	 * @return String
	 */
	public static function buildAttribute($name, $value)
	{
		return $name . '="' . htmlspecialchars($value, ENT_COMPAT, "UTF-8") . '"';
	}
	
	/**
	 * @param array<String, String> $attributes
	 * @return String
	 */
	public static function buildAttributes($attributes)
	{
		$attrString = "";
		foreach ($attributes as $attrName => $attrValue)
		{
			$attrString .= " ".self::buildAttribute($attrName, $attrValue);
		}
		return $attrString;
	}

    /**
     * @param String $string
     * @return Array
     */
    private static function parseAttributes ($string)
    {
        $attributeArray = array();
        if (! empty($string))
        {
            $matches = array();
        	preg_match_all("/\s*([\w:]*)\s*=\s*\"(.*?)\"/i", $string, $matches, PREG_SET_ORDER);
            foreach ($matches as $matche)
            {
                $attributeArray[strtolower($matche[1])] = isset($matche[3]) ? $matche[3] : $matche[2];
            }
        }
        return $attributeArray;
    }
   
    /**
     * Parse <a> balise
     * @internal For internal usage of f_util_HtmlUtils only
     * @param Array $matches
     * @return String
     */
    public static function parseLinkTag ($matches)
    {
        $attributes = self::parseAttributes(trim($matches[1]));
        $content = $matches[2];
        
        $classes = isset($attributes['class']) ? explode(' ', $attributes['class']) : array();
  
        //Deprecated attribute
        if (isset($attributes['popup']))
        {
            unset($attributes['popup']);
            if (!in_array('popup', $classes))
            {
            	$classes[] = 'popup';
            }
        }
        
        if (in_array('popup', $classes))
        {
        	 $attributes['onclick'] = 'return accessiblePopup(this);';
        	// FIX #583 - If we have a popup link, make the title RGAA compatible
            if (isset($attributes['title']))
            {
            	$attributes['title'] .= ' ' . f_Locale::translate('&framework.util.htmlutils.popupTitleAddition;'); 
            }
            else 
            {
            	$attributes['title'] =  f_Locale::translate('&framework.util.htmlutils.popupTitleAddition;'); 
            }        	
        }
        
        if (count($classes))
        {
        	$attributes['class'] = implode(' ', $classes);
        }
        
        $documentId  = 0;
        if (isset($attributes['rel']))
        {
        	foreach (explode(',', $attributes['rel']) as $rel) 
        	{
        		if (strpos($rel, 'cmpref:') === 0)
        		{
        			$documentId = intval(substr($rel, 7));
        			break;
        		}
        	}
        }
        
        //Deprecated attribute
        if (isset($attributes['cmpref']))
        {
        	if ($documentId == 0)
        	{
        		$documentId = intval($attributes['cmpref']);
        	}
        	unset($attributes['cmpref']);
        }
        
        if ($documentId > 0)
        {  
            try
            {
                return self::renderDocumentLink($documentId, $attributes, $content);
            } 
            catch (Exception $e)
            {
                Framework::exception($e);
                return self::buildBrokenLink($documentId, $content);
            }
        }
        else if (isset($attributes['tag']))
        {
            $tag = $attributes['tag'];
            unset($attributes['tag']);
            
	        if (isset($attributes['lang']))
	        {
	            $lang = $attributes['lang'];
	        }
	        else
	        {
	            $lang = RequestContext::getInstance()->getLang();
	        }
       
            $href = LinkHelper::getTagUrl($tag, $lang);
            if (empty($href))
            {
            	return self::buildBrokenLink($tag, $content);
            }
            else
            {
            	$attributes['href'] = $href;
            	return self::buildLink($attributes, $content);
            }
        }
        else
        {
            if (isset($attributes['href']) && !empty($attributes['href']))
            {
            	$documentInfos = array();
            	if (preg_match('/original\/((?:[0-9]{1,3}\/)+)([a-z]{2})\//i', $attributes['href'], $documentInfos))
            	{
            		$documentId = intval(str_replace('/', '', $documentInfos[1]));
            		$attributes['lang'] = $documentInfos[2];
            	    try
		            {
		                return self::renderDocumentLink($documentId, $attributes, $content);
		            } 
		            catch (Exception $e)
		            {
		                Framework::exception($e);
		                return self::buildBrokenLink($documentId, $content);
		            }            			
            	} 
            	else if (!preg_match('/^(#|\.|\/|http:\/\/|https:\/\/|ftp:\/\/|mailto:|javascript:)/', $attributes['href']))
                {
                   $attributes['href'] = Framework::getBaseUrl() . '/' . $attributes['href'];
        		}                
            }
            else
            {
               $attributes['href'] = '#'; 
            }

            return self::buildLink($attributes, $content);
        }
        
        //return original link
        return $matches[0];
    }
    /**
     * remove script elements and replace change:id and replace html entities to decimal entities
     * 
     * @param String $blockContent
     * @return String
     */
    public static function cleanHtmlForBackofficeEdition($blockContent)
    {
    	$search = array('/<script[^>]+\/>/mi', '/<script.*<\/script>/mis', '/change:([a-z0-9]+=")/mi');
		$replace = array('', '', 'change_$1');
		$blockContent = preg_replace($search, $replace, $blockContent);
		// replace html entities to decimal form (xml compatible)
		$blockContent = self::htmlEntitiesToXMLEntities($blockContent);
		$blockContent = preg_replace('/&(?!gt;|lt;|amp;|quot;|#\d+;)/m', '&amp;$1', $blockContent);
		return $blockContent;
    }
    
    private static $htmlEntities;
    private static $xmlEntities;
    
    private static function initEntities()
    {
    	if (self::$htmlEntities === null)
    	{
    		self::$htmlEntities = array("&Aacute;", "&aacute;", "&Acirc;", "&acirc;", "&acute;", "&AElig;", "&aelig;", "&Agrave;", "&agrave;", "&Aring;", "&aring;", "&Atilde;", "&atilde;", "&Auml;", "&auml;", "&brvbar;", "&Ccedil;", "&ccedil;", "&cedil;", "&cent;", "&copy;", "&curren;", "&deg;", "&divide;", "&Eacute;", "&eacute;", "&Ecirc;", "&ecirc;", "&Egrave;", "&egrave;", "&ETH;", "&eth;", "&Euml;", "&euml;", "&frac12;", "&frac14;", "&frac34;", "&Iacute;", "&iacute;", "&Icirc;", "&icirc;", "&iexcl;", "&Igrave;", "&igrave;", "&iquest;", "&Iuml;", "&iuml;", "&laquo;", "&macr;", "&micro;", "&middot;", "&nbsp;", "&not;", "&Ntilde;", "&ntilde;", "&Oacute;", "&oacute;", "&Ocirc;", "&ocirc;", "&Ograve;", "&ograve;", "&ordf;", "&ordm;", "&Oslash;", "&oslash;", "&Otilde;", "&otilde;", "&Ouml;", "&ouml;", "&para;", "&plusmn;", "&pound;", "&raquo;", "&reg;", "&sect;", "&shy;", "&sup1;", "&sup2;", "&sup3;", "&szlig;", "&THORN;", "&thorn;", "&times;", "&Uacute;", "&uacute;", "&Ucirc;", "&ucirc;", "&Ugrave;", "&ugrave;", "&uml;", "&Uuml;", "&uuml;", "&Yacute;", "&yacute;", "&yen;", "&yuml;");
    		self::$xmlEntities = array("&#193;", "&#225;", "&#194;", "&#226;", "&#180;", "&#198;", "&#230;", "&#192;", "&#224;", "&#197;", "&#229;", "&#195;", "&#227;", "&#196;", "&#228;", "&#166;", "&#199;", "&#231;", "&#184;", "&#162;", "&#169;", "&#164;", "&#176;", "&#247;", "&#201;", "&#233;", "&#202;", "&#234;", "&#200;", "&#232;", "&#208;", "&#240;", "&#203;", "&#235;", "&#189;", "&#188;", "&#190;", "&#205;", "&#237;", "&#206;", "&#238;", "&#161;", "&#204;", "&#236;", "&#191;", "&#207;", "&#239;", "&#171;", "&#175;", "&#181;", "&#183;", "&#160;", "&#172;", "&#209;", "&#241;", "&#211;", "&#243;", "&#212;", "&#244;", "&#210;", "&#242;", "&#170;", "&#186;", "&#216;", "&#248;", "&#213;", "&#245;", "&#214;", "&#246;", "&#182;", "&#177;", "&#163;", "&#187;", "&#174;", "&#167;", "&#173;", "&#185;", "&#178;", "&#179;", "&#223;", "&#222;", "&#254;", "&#215;", "&#218;", "&#250;", "&#219;", "&#251;", "&#217;", "&#249;", "&#168;", "&#220;", "&#252;", "&#221;", "&#253;", "&#165;", "&#255;");
    	}
    }
    
    /**
     * @param String $string
     * @return String
     */
    static function htmlEntitiesToXMLEntities($string)
    {
    	self::initEntities();
    	return str_replace(self::$htmlEntities, self::$xmlEntities, $string);
    }
    
    private static function buildLink ($attributes, $content)
    {
        $link = '<a';
        foreach ($attributes as $name => $value)
        {
            $link .= ' ' . $name . '="' . $value . '"';
        }
        return $link . '>' . $content . '</a>';
    }
    
    private static function buildBrokenLink ($documentId, $content)
    {
        Framework::warn(__METHOD__ . ' Broken document link (ID=' . $documentId . ')');
        return '<a href="#" class= "link-broken">' . $content . '</a>';
    }
    
    /**
     * Parse <img> balise
     * @internal For internal usage of f_util_HtmlUtils only
     * @param Array $matches
     * @return String
     */
    public static function parseImageTag($matches)
    {       
        $attributes = self::parseAttributes(trim($matches[1]));
        if (isset($attributes['cmpref']))
        {
            $documentId = intval($attributes['cmpref']);
            unset($attributes['cmpref']);
            
            try
            {
                return self::renderDocumentImage($documentId, $attributes);
            } 
            catch (Exception $e)
            {
                Framework::exception($e);
                return self::buildBrokenImage($documentId);
            }
        }
        else
        {
            return self::buildImage($attributes);
        }
        
        //return original image
        return $matches[0];
    }
    
    
    private static function buildImage($attributes, $format)
    {
        $html = '<img';
        $document = null;
        $wantZoom = false;
        foreach ($attributes as $name => $value)
        {
        	if ($name == 'change:id')
        	{
        		list(,$id) = explode('-', $value);
        		$document = DocumentHelper::getDocumentInstance($id);
        		continue;
        	}
        	if ($name == "zoom" && $value == "true")
        	{
        		$wantZoom = true;
        		continue;
        	}
            $html .= ' ' . $name . '="' . $value . '"';
        }
        $html .= ' />';
        
        if ($wantZoom && $document && f_util_ClassUtils::methodExists($document, 'getInfo'))
        {
			$docInfo = $document->getInfo();        	
        	$askedWidth = self::min(array($format['width'], $format['max-width']));
			$askedHeight = self::min(array($format['height'], $format['max-height']));
			// TODO: externalize the percentage of tolerance: configuration / preferences
			$formatted = ($askedWidth !== null && $askedWidth < (0.85 * $docInfo['width']))
			   || ($askedHeight !== null && $askedHeight < (0.85 * $docInfo['height']));
			
			if ($formatted)
			{
        		$html = '<a class="lightbox" href="' . media_MediaService::getInstance()->generateUrl($document, null, $docInfo) . '" title="'.$attributes["alt"].'">' . $html . '</a>';
			}
        }
        return $html;
    }
    
    // TODO: refactor with MediaHelper::min
	private static function min($values)
	{
		$min = null;
		foreach ($values as $value)
		{
			if ($value === null) continue;
			if ($min === null || $value < $min)
			{
				$min = $value;
			}
		}
		return $min;
	}
    
    private static function buildBrokenImage($documentId)
    {
        Framework::warn(__METHOD__ . ' Broken document image (ID=' . $documentId . ')');
        return '<img src="' . Framework::getUIBaseUrl() .'/icons/normal/unknown.png" class="image-broken" />';
    }
    
    private static function renderDocumentLink($documentId, $attributes, $content)
    {
        $rq = RequestContext::getInstance();
        if (isset($attributes['lang']))
        {
            $lang = $attributes['lang'];
        } 
        else if (isset($attributes['xml:lang']))
        {
            $lang = $attributes['xml:lang'];
        }
        else
        {
            $lang = $rq->getLang();
        }
        try
        {
            $rq->beginI18nWork($lang);
           	$document = DocumentHelper::getDocumentInstance($documentId);
            
            if ($document instanceof media_persistentdocument_media && $document->getMediatype() == MediaHelper::TYPE_FLASH)
            {
                $link = self::renderFlashTag($document, $attributes);
            } 
            else if ($document instanceof media_persistentdocument_media && $document->getMediatype() == MediaHelper::TYPE_IMAGE)
            {
            	$attributes['href'] = media_MediaService::getInstance()->generateUrl($document, $lang);	
                $link = self::buildLink($attributes, $content);
            }
            else if ($document instanceof media_persistentdocument_file)
            {
            	$attributes['href'] = media_FileService::getInstance()->generateDownloadUrl($document, $lang);
            	$document->addDownloadAttributes($attributes);       	
                $link = self::buildLink($attributes, $content);
            }
            else
            {
                $attributes['href'] = LinkHelper::getDocumentUrl($document, $lang);
                $link = self::buildLink($attributes, $content);
            }
            
            $rq->endI18nWork();
        } 
        catch (Exception $e)
        {
            $rq->endI18nWork($e);
        }
        return $link;
    }
    
    private static function renderDocumentImage($documentId, $attributes)
    {
        $rq = RequestContext::getInstance();
        if (isset($attributes['lang']))
        {
            $lang = $attributes['lang'];
        } else if (isset($attributes['xml:lang']))
        {
            $lang = $attributes['xml:lang'];
        } else
        {
            $lang = $rq->getLang();
        }
        try
        {
            $rq->beginI18nWork($lang);
            $document = DocumentHelper::getDocumentInstance($documentId);            
            if ($document instanceof media_persistentdocument_media)
            {
                if ($document->getMediatype() == MediaHelper::TYPE_FLASH)
                {
                    $image = self::renderFlashTag($document, $attributes);
                }
                else
                {
                    list($attributes['src'], $format) = self::buildImageSrc($document, $attributes);
                    $attributes['change:id'] = 'media-' . $documentId;
                    if ($document->getDescription())
                    {
                    	$attributes['longdesc'] = LinkHelper::getActionUrl("media", "DisplayMediaDescription", array(K::COMPONENT_ID_ACCESSOR => $document->getId(), "label" => $document->getLabel(), "lang" => $lang));
                    }
               		if (isset($attributes["format"]))
                    {
                    	unset($attributes["format"]);
                    }
                 	if (isset($attributes["usemediaalt"]))
                    {
                    	unset($attributes["usemediaalt"]);
                    	$attributes["alt"] = htmlspecialchars($document->getTitle(), ENT_COMPAT, "UTF-8");
                    }
                    $image = self::buildImage($attributes, $format);
                }
            }
            else
            {
               throw new Exception('Document is not a instance of media_persistentdocument_media');
            }
            $rq->endI18nWork();
        } 
        catch (Exception $e)
        {
            $rq->endI18nWork($e);
        }
        return $image;
    }
    
    /**
     * @param media_persistentdocument_media $document
     * @param Array $attributes
     * @return String
     */
    private function renderFlashTag($document, $attributes)
    {
        $attributes['id'] = 'media-' . $document->getId();
        $attributes['url'] = LinkHelper::getDocumentUrl($document);
        $attributes = array_merge($attributes, MediaHelper::getImageSize(media_MediaService::getInstance()->getOriginalPath($document)));        
        unset($attributes['src']);
        $attributes['alt'] = $document->getTitle();
        if ($document->getDescription())
        {
            $attributes['description'] = $document->getDescriptionAsHtml();
        }
        
        $templateComponent = TemplateLoader::getInstance()->setpackagename('modules_media')->setMimeContentType(K::HTML)->load('Media-Block-Flash-Success');
        $templateComponent->setAttribute('medias', array($attributes));
        $content = $templateComponent->execute();
        return $content;
    }
        
     /**
     * @param media_persistentdocument_media $document
     * @param Array $attributes
     * @return String
     */   
    private function buildImageSrc($document, &$attributes)
    {
    	$lang = RequestContext::getInstance()->getLang();
    	$urlLang = $lang;
    	$infos = null; 
  
    	if ($document instanceof media_persistentdocument_file)
    	{
    		$urlLang = ($document->getFilename()) ?  $lang : $document->getLang();
    		$infos = $document->getInfoForLang($urlLang);
    	}
    	
        if (isset($attributes['format']) && !empty($attributes['format']))
        {
            list($stylesheet, $formatName) = explode('/', $attributes['format']);
			$format = MediaHelper::getFormatProperties($stylesheet, $formatName);
			unset($attributes['format']);
        }
        else if ($infos)
        {	 
             $format = array();
             if (isset($infos['width']))
             {
             	$format['width'] = $infos['width'];
             	$format['height'] = $infos['height'];
             }
        }
        
        if (isset($attributes['style']))
        {
            $styleAttributes = self::parseStyleAttributes($attributes['style']);
            if (isset($styleAttributes['width']))
            {
                $width = intval($styleAttributes['width']);
                if ($width) 
                {
                    $format['max-width'] = $width;
                }               
            }
            if (isset($styleAttributes['height']))
            {
                $height = intval($styleAttributes['height']);
                if ($height) 
                {
                    $format['max-height'] = $height;
                }               
            }
        }
     
        if (isset($attributes['width'])) 
        {
            $width = intval($attributes['width']);
            if ($width) 
            {
                $format['max-width'] = $width;
            }
        }
        
        if (isset($attributes['height'])) 
        {
            $height = intval($attributes['height']);
            if ($height) 
            {
                $format['max-height'] = $height;
            }
        }
       
        if ($infos && !isset($attributes['width']) && !isset($attributes['height']))
        {
        	$computedDimensions = MediaHelper::computeImageSize($infos['width'], $infos['height'], $format);
        	$attributes['width'] = $computedDimensions['width'];
        	$attributes['height'] = $computedDimensions['height'];
        }
        return array(LinkHelper::getDocumentUrl($document, $urlLang, $format), $format);
    }
}
