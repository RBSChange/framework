<?php 
class f_aop_samples_amodule_AClass
{
	/**
	 * @var f_persistentdocument_PersistentProvider
	 */
	protected $pp = null;

	/**
	 * @var f_persistentdocument_TransactionManager
	 */
	protected $tm = null;

	protected function __construct()
	{
		// bla
	}

	/**
	 * @var f_persistentdocument_DocumentService
	 */
	private static $instance;

	/**
	 * @return f_persistentdocument_DocumentService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Saves a document with the use of a database transaction, and optionnally inserts
	 * the document in the tree (during the same transaction) if $parentNodeId is given.
	 *
	 * @param f_persistentdocument_PersistentDocument $document The document to save.
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 *
	 * @throws IllegalArgumentException
	 * @throws ValidationException
	 * @throws Exception
	 */
	public final function save($document, $parentNodeId = null)
	{
		if ($this !== $document->getDocumentService())
		{
			$document->getDocumentService()->save($document, $parentNodeId);
			return;
		}
		
		// apply metas if some of the setMetaXX() methods was called
		$document->applyMetas();
		
		$document->setParentNodeId($parentNodeId);
		if ($document->isNew())
		{
			return $this->insert($document, $parentNodeId);
		}
		else
		{
			return $this->update($document, $parentNodeId);
		}
	}

	/**
	 * Gets all the tags of the document.
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array<string> Array with all the tags.
	 *
	 * @throws IllegalArgumentException
	 */
	public function getTags($document)
	{
		return $this->getTagService()->getTags($document);
	}
}

class f_aop_samples_AClass
{
	
}

class f_aop_samples_AnOtherClass extends f_aop_samples_AClass
{
	function __construct($toto, $titi)
	{
		// bli	
	}
	
	function bli()
	{
		echo "Bli";
	}
}

// The new Criterions example

/**
 * Very dummy criterion that match property value equals true
 */
class f_aop_samples_TrueCriterion implements f_persistentdocument_criteria_Criterion
{
	private $propertyName;
	
	/** 
	 * @param String $propertyName
	 */
	function __construct($propertyName)
	{
		$this->propertyName = $propertyName;
	}
	
	function getPropertyName()
	{
		return $this->propertyName;
	}
}

/**
 * Very dummy criterion that match property value equals false
 */
class f_aop_samples_FalseCriterion implements f_persistentdocument_criteria_Criterion
{
	private $propertyName;
	
	/** 
	 * @param String $propertyName
	 */
	function __construct($propertyName)
	{
		$this->propertyName = $propertyName;
	}
	
	function getPropertyName()
	{
		return $this->propertyName;
	}
}