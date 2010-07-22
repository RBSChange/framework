<?php
class import_ScriptDocumentRefElement extends import_ScriptDocumentElement
{
	/**
	 * @return void
	 */
	public function process()
	{
		// Do nothing.
		// Document ref must not be modified.
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function initPersistentDocument()
	{
		throw new Exception('No document found for documentRef with type '.$this->attributes['type']);
	}
}