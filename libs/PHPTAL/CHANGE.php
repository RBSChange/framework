<?php
/**
 * @package phptal.namespace
 */
class PHPTAL_Namespace_CHANGE extends PHPTAL_Namespace_Builtin
{
	const NAMESPACE_URI = 'http://xml.zope.org/namespaces/CHANGE';
	
	public function __construct()
	{
		parent::__construct('change', self::NAMESPACE_URI);
		PHPTALService::getInstance()->registerAttributes($this);
	}
}
