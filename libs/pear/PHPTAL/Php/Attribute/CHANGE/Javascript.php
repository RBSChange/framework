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
            		$code = '$jsService = JsService::newInstance();$jsService->registerScript("'.$src.'");';
					$this->tag->generator->pushCode($code);
					$this->doEcho('$jsService->execute("html")');
					break;
            	case 'head':
            		$src = $this->evaluate($value, true);
            		$code = '$wp = $ctx->__get("website_page");if ($wp !== null) {$wp->addScript("'.$src.'");} else {JsService::getInstance()->registerScript("'.$src.'");};';
					$this->tag->generator->pushCode($code);
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
