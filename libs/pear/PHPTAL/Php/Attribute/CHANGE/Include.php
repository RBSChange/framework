<?php
// change:include
//

/**
 * @package phptal.php.attribute
 * @author INTbonjF
 * 2007-06-14
 */
class PHPTAL_Php_Attribute_CHANGE_include extends ChangeTalAttribute 
{
	
	/**
	 * @see ChangeTalAttribute::evaluateAll()
	 *
	 * @return Boolean
	 */
	protected function evaluateAll()
	{
		return true;
	}
	
	/**
	 * @param array $params
	 * @param PHPTAL_Context $ctx
	 */
	public static function renderInclude($params, $ctx)
	{
		if (!isset($params["type"]))
		{
			$params["type"] = "html";
		}
		$template = TemplateLoader::getInstance()->setPackageName("modules_" . $params['module'])->setMimeContentType($params['type'])
		->load($params['template']);
		unset($params['module']);
		unset($params['type']);
		unset($params['template']);
		if (isset($params["transmitAll"]) && $params["transmitAll"] == "true")
		{
			foreach($ctx as $key => $value)
			{
				if ($key[0] !== '_')
				{
   					$template->setAttribute($key, $value);
				}
			}
			unset($params["transmitAll"]);
		}
		else
		{
			// at least transmit website_page & context. Cf. website_BlockView
			$staticRefs = array("website_page", "context");
			foreach ($staticRefs as $refName)
			{
				$ref = $ctx->__get($refName);
				if ($ref !== null)
				{
					$template->setAttribute($refName, $ref);
				}
			}
		}
		foreach ($params as $name => $value) 
		{
			$template->setAttribute($name, $value);	
		}
		echo $template->execute();
	}
}