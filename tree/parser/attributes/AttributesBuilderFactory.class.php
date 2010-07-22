<?php

class f_tree_parser_AttributesBuilderFactory
{
    const NOOP = 'f_tree_parser_NoopAttributesBuilder';
    private static $instances;
    
    /**
     * @param string $moduleName
     * @param string $treeParseType ([wtree]|wmultitree|wlist|wmultilist)
     * @return f_tree_parser_AttributesBuilder
     */
    public static function getAttributesBuilderInstance($moduleName, $treeParseType)
    {
        if (self::$instances === NULL)
        {
            self::$instances = array(self::NOOP => new f_tree_parser_NoopAttributesBuilder());
        }
        if ($treeParseType === NULL)
        {
           $treeParseType = 'wtree';
        }
        
        $className = $moduleName .  '_'.ucfirst(substr($treeParseType,1) . 'AttributesBuilder');
        if (array_key_exists($className, self::$instances))
        {
            return self::$instances[$className];
        }
        
        if (Framework::isDebugEnabled())
        {
            Framework::debug(__METHOD__ . ' check :' . $className);
        }
        if (f_util_ClassUtils::classExists($className))
        {
            self::$instances[$className] = new $className();
        }
        else
        {
            self::$instances[$className] = self::$instances[self::NOOP];
        }
        
        return self::$instances[$className];
    }
}