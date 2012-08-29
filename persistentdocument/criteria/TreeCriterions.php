<?php
interface f_persistentdocument_criteria_TreeCriterion extends f_persistentdocument_criteria_Criterion
{
	
}

class f_persistentdocument_criteria_SiblingOfExpression implements f_persistentdocument_criteria_TreeCriterion
{
	/**
	 * @var Integer
	 */
	private $documentId;

	/**
	 * Default constructor
	 * @param integer $Integer
	 */
	public function __construct($documentId)
	{
		$this->documentId = $documentId;
	}

	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}
}

class f_persistentdocument_criteria_PreviousSiblingOfExpression implements f_persistentdocument_criteria_TreeCriterion
{
	/**
	 * @var Integer
	 */
	private $documentId;
	
	/**
	 * Default constructor
	 * @param integer $documentId
	 */
	public function __construct($documentId)
	{
		$this->documentId = $documentId;
	}

	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}
}

class f_persistentdocument_criteria_NextSiblingOfExpression implements f_persistentdocument_criteria_TreeCriterion
{
	/**
	 * @var Integer
	 */
	private $documentId;
	
	/**
	 * Default constructor
	 * @param integer $documentId
	 */
	public function __construct($documentId)
	{
		$this->documentId = $documentId;
	}

	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}
}

class f_persistentdocument_criteria_AncestorOfExpression implements f_persistentdocument_criteria_TreeCriterion
{
	/**
	 * @var Integer
	 */
	private $documentId;
	/**
	 * @var Integer
	 */
	private $level;

	/**
	 * Default constructor
	 * @param integer $Integer
	 * @param integer $Integer
	 */
	public function __construct($documentId, $level)
	{
		$this->documentId = $documentId;
		$this->level = $level;
	}

	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @return integer
	 */
	public function getLevel()
	{
		return $this->level;
	}
}

class f_persistentdocument_criteria_DescendentOfExpression implements f_persistentdocument_criteria_TreeCriterion
{
	/**
	 * @var Integer
	 */
	private $documentId;
	/**
	 * @var Integer
	 */
	private $level;

	/**
	 * Default constructor
	 * @param integer $Integer
	 * @param integer $Integer
	 */
	public function __construct($documentId, $level)
	{
		$this->documentId = $documentId;
		$this->level = $level;
	}

	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @return integer
	 */
	public function getLevel()
	{
		return $this->level;
	}
}