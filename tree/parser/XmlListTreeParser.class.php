<?php
/**
 * @package framework.tree.parser
 * Base class for all XML LIST parsers.
 */
class tree_parser_XmlListTreeParser extends tree_parser_XmlTreeParser
{

    /**
     * Generic tree_parser_XmlListTreeParser constructor.
     *
     * A generic XmlListTreeParser (used by <wlist> elements) has the following properties :
     *
     *  - Depth limit : 1.
     *  - Length limit : 30.
     *  - Overwrite the already loaded data (on the client side) : yes (true).
     *  - Ignore children : yes (true).
     *  - Ignore length limit beyond depth : 1.
     *
     */
    public function initialize()
    {
        $this->setDepth(1)
            ->setLength(30)
            ->setOverwrite(true)
            ->setIgnoreChildren(true)
            ->setIgnoreLengthBeyondDepth(1);
    }
}