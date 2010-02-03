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
	
	public static function renderInclude($params)
	{
		$template = TemplateLoader::getInstance()->setPackageName("modules_" . $params['module'])->setMimeContentType($params['type'])
		->load($params['template']);
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