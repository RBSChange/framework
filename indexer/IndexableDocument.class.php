<?php
/**
 * @package framework.indexer
 */
/**
 * @deprecated
 */
interface indexer_IndexableDocument
{
	/**
	 * Get the indexable document
	 *
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument();
}