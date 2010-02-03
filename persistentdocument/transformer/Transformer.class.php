<?php
/**
 * @package framework.persistentdocument.transformer
 */
interface f_persistentdocument_transformer_Transformer
{
    /**
     * @param f_persistentdocument_PersistentDocument $sourceDocument
     * @param f_persistentdocument_PersistentDocument $destDocument
     */
    function transform($sourceDocument, &$destDocument);
}