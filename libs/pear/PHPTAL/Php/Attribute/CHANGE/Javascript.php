<?php
/**
 * @package phptal.php.attribute
 * @author INTbonjF
 * 2007-11-07
 */
class PHPTAL_Php_Attribute_CHANGE_javascript extends PHPTAL_Php_Attribute
{
    public function start()
    {
    	if (website_BlockController::getInstance()->getContext()->inBackofficeMode())
    	{
    		return;
    	}
        // split attributes to translate
        $expressions = $this->tag->generator->splitExpression($this->expression);

        // foreach attribute
        foreach ($expressions as $exp)
        {
            list($attribute, $value) = $this->parseSetExpression($exp);
            $attribute = trim($attribute);
            switch ($attribute)
            {
            	case 'src':
            		$src = $this->evaluate($value, true);
					$this->tag->generator->pushCode("\$jsService = JsService::getInstance();\n\$jsService->registerScript('".$src."');\n");
					$this->doEcho('$jsService->execute("html")');
            		$this->tag->generator->pushCode("\$jsService->unregisterScript('".$src."');\n");
					break;
					
            	case 'head':
            		$src = $this->evaluate($value, true);
					$this->tag->generator->pushCode("JsService::getInstance()->registerScript('".$src."');\n");
					break;

            	default:
            		$array = $this->evaluate($value, true);
					$this->tag->generator->pushRawHtml('<script type="text/javascript">');
		        	$this->doEcho('"var '.$attribute.' = " . f_util_StringUtils::JSONEncode('.$array.') . ";"');
					$this->tag->generator->pushRawHtml('</script>');
            		break;
            }
        }
    }

    public function end()
    {
    }
}
