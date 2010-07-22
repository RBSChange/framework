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
		$template = TemplateLoader::getInstance()->setPackageName("modules_" . $params['module'])->setMimeContentType($params['type'])
		->load($params['template']);
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
		foreach ($params as $name => $value) 
		{
			if ($name == "template" || $name == "module" || $name == "type")
			{
				continue;
			}
			$template->setAttribute($name, $value);
			
		}
		echo $template->execute();
	}
}