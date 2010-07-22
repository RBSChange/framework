<?php
interface export_ExportableDocument
{
	
	/**
	 * Get the exported document. Return a document use to generate an export file
	 *
	 * @return export_ExportedDocument
	 */
	public function getExportedDocument();
	
}