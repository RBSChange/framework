<?php
/**
 * @package phptal.namespace
 */
class PHPTAL_Namespace_CHANGE extends PHPTAL_BuiltinNamespace
{
    public function __construct()
    {
        parent::__construct('CHANGE', 'http://xml.zope.org/namespaces/CHANGE');
        PHPTALService::getInstance()->registerAttributes($this);
    }
}

PHPTAL_Dom_Defs::getInstance()->registerNamespace(new PHPTAL_Namespace_CHANGE());