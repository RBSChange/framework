<?php
class tree_DocumentTreeNode extends tree_DefaultTreeNode
{
	protected $document = null;
	protected $reference = null;
	
	public function __construct(f_component_BaseComponent $document)
	{
		$this->setDocument($document);
	}
	
	public function setDocument(f_component_BaseComponent $document)
	{
		$this->document = $document;
	}
	
	public function setReference(f_component_ReferenceComponent $reference)
	{
		$this->reference = $reference;
	}
	
	public function getDocument()
	{
		return $this->document;
	}
	
	public function getReference()
	{
		return $this->reference;
	}
	
	public function hasReference()
	{
		return ! is_null($this->reference);
	}

}