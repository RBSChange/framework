<?php

interface f_tree_parser_AttributesBuilder
{
	const LABEL_ATTRIBUTE = 'label';

	const TYPE_ATTRIBUTE = 'type';

	const HTMLLINK_ATTRIBUTE = 'htmllink';

	const PLAINLINK_ATTRIBUTE = 'plainlink';

	const BLOCK_ATTRIBUTE = 'block';
	
	const TOOLBARTYPE_ATTRIBUTE = 'toolbartype';
	
	
     /**
      * @param f_persistentdocument_PersistentDocument $document
      * @param array<string, string> $attributeArray
      */
     function build($document, &$attributeArray);
}