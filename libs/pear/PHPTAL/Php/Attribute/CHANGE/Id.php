<?php
/**
 * @package phptal.php.attribute.change
 */
class PHPTAL_Php_Attribute_CHANGE_id extends PHPTAL_Php_Attribute
{
    public function start()
    {
    	// Just set the attribute...
        $this->tag->attributes['change:id'] = $this->expression;
    }

    public function end()
    {
    }
}
?>