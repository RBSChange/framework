<?php
class framework_PHPTAL_CHANGE
{
	/**
	 * @param PHPTAL_Namespace_CHANGE $namespaceCHANGE
	 */
	public static function addAttributes($namespaceCHANGE)
	{
        $namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeReplace('include', 10));
	}
}