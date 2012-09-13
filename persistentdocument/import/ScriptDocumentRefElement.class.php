<?php
class import_ScriptDocumentRefElement extends import_ScriptDocumentElement
{
	/**
	 * @return void
	 */
	public function process()
	{
		// Check persistent document existence.
		$this->getPersistentDocument();
		
		// Do nothing else: document ref must not be modified.
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function initPersistentDocument()
	{
		$attrs = array();
		foreach ($this->attributes as $key => $value)
		{
			$attrs[] = $key . ' = "' . $value . '"';
		}
		throw new Exception('No document found for documentRef with attributes '.implode(', ', $attrs));
	}
}