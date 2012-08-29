<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument
 */
class f_persistentdocument_PersistentRelation
{
	private $m_documentId1;
	private $m_documentModel1;

	private $m_documentId2;
	private $m_documentModel2;

	private $m_type;
	private $m_name;
	private $m_order;

	const RELATION_TABLE_NAME = "f_relation";

	/**
	 * @param integer $documentId1
	 * @param string $documentModel1
	 * @param integer $documentId2
	 * @param string $documentModel2
	 * @param string $type 'CHILD'
	 * @param string $name
	 * @param integer $order
	 */
	public function __construct($documentId1, $documentModel1, $documentId2, $documentModel2, $type, $name, $order)
	{
		$this->m_documentId1 = $documentId1;
		$this->m_documentId2 = $documentId2;
		$this->m_documentModel1 = $documentModel1;
		$this->m_documentModel2 = $documentModel2;
		$this->m_name = $name;
		$this->m_type = 'CHILD';
		$this->m_order = $order;
	}

	public function getDocumentId1()
	{
		return $this->m_documentId1;
	}

	public function getDocumentId2()
	{
		return $this->m_documentId2;
	}

	public function getDocumentModel1()
	{
		return $this->m_documentModel1;
	}

	public function getDocumentModel2()
	{
		return $this->m_documentModel2;
	}

	public function getName()
	{
		return $this->m_name;
	}

	public function getType()
	{
		return $this->m_type;
	}

	public function getOrder()
	{
		return $this->m_order;
	}
}