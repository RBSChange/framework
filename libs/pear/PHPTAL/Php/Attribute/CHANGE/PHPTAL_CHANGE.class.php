<?php
class framework_PHPTAL_CHANGE
{
	/**
	 * @param PHPTAL_Namespace_CHANGE $namespaceCHANGE
	 */
	public static function addAttributes($namespaceCHANGE)
	{
        $namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeReplace('date', 13));
		$namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeReplace('datetime', 14));
		$namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeSurround('i18nattr', 7));
		$namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeSurround('id', 10));
        $namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeReplace('include', 10));
        $namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeReplace('javascript', 10));
        $namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeReplace('price', 10));
        $namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeReplace('select', 10));
		$namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeContent('translate', 8));
	}
}

/**
 * @param String $expression
 * @param Boolean $nothrow
 */
function phptal_tales_escape($expression, $nothrow)
{
	$expr = PHPTAL_TalesInternal::path($expression, $nothrow);
	return 'f_util_HtmlUtils::textToHtml('.$expr.')';
}