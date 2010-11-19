<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument
 */
class f_persistentdocument_PersistentDocumentArray extends ArrayObject
{
	const RELATION_CHILD = "CHILD";
	const RELATION_TREE = "TREE";
	const RELATION_REFERENCE = "REFERENCE";

	const DEFAULT_RELATION_CHILD_STORAGE = "f_relation";

	/**
	 * @var f_persistentdocument_PersistentDocument
	 */
	private $m_parentDocument;
	private $m_relationName;
	private $m_relationId;
	private $m_relationType;
	private $m_storageTable;
	private $m_preserveOldValue;

	private $m_modified = true;
	private $m_oldValues;

	/**
	 * Enter description here...
	 *
	 * @param f_persistentdocument_PersistentDocument $parentDocument
	 * @param String $relationName
	 * @param String $relationType element of {RELATION_CHILD, RELATION_TREE} defaults RELATION_CHILD
	 * @param String $storageTable defaults DEFAULT_RELATION_CHILD_STORAGE
	 */
	public function __construct($parentDocument, $relationName,
			$relationType = self::RELATION_CHILD, $storageTable = self::DEFAULT_RELATION_CHILD_STORAGE,
			$preserveOldValue = false)
	{
		$this->m_parentDocument = $parentDocument;
		$this->m_relationName = $relationName;
		$this->m_relationType = $relationType;
		$this->m_storageTable = $storageTable;
		$this->m_preserveOldValue = $preserveOldValue;
	}

    public function __destruct()
    {
        $this->m_parentDocument = null;
    }
    
    /**
     * Called to indicate the array was just persisted (used by persistentProvider)
     */
    public function setIsPersisted()
    {
    	$this->m_modified = false;
    	if ($this->m_preserveOldValue)
    	{
    		$this->m_oldValues = array();
    		foreach ($this as $document)
    		{
    			$this->m_oldValues[] = $document->getId();
    		}
    	}
    }

	/**
	 * Obtient le document contenant la relation
	 *
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getParentDocument()
	{
		return $this->m_parentDocument;
	}

	/**
	 * Retourne le nom de la relation
	 * @return String
	 */
	public function getRelationName()
	{
		return $this->m_relationName;
	}
	
	/**
	 * Retourne l'id de la relation
	 * @return Integer
	 */
	public function getRelationId()
	{
		if ($this->m_relationId === null)
		{
			$this->m_relationId = RelationService::getInstance()->getRelationId($this->m_relationName);
		}
		return $this->m_relationId;
	}	

	/**
	 * Retourne le type de la relation
	 * f_persistentdocument_PersistentDocumentArray::RELATION_CHILD
	 *
	 * @return String
	 */
	public function getRelationType()
	{
		return $this->m_relationType;
	}

	/**
	 * Retourne la table de stockage des elements de la relation
	 *
	 * @return String
	 */
	public function getStorageTable()
	{
		return $this->m_storageTable;
	}

	/**
	 * @param Integer $index
	 * @param f_persistentdocument_PersistentDocument $newval
	 */
	public function offsetSet($index, $newval)
	{
		if (!($newval instanceof f_persistentdocument_PersistentDocument))
		{
			throw new Exception(__METHOD__.": given value is not a persistentdocument");
		}

		// $index parameter can be null, meaning you add a value at
		// the end of the array ($myArray[] = $newValue for instance).
		if ($index === null || !parent::offsetExists($index))
		{
			$this->m_modified = true;
		}
		else
		{
			$oldVal = $this->offsetGet($index);
			if ($oldVal->getId() != $newval->getId())
			{
				$this->m_modified = true; 	
			}
		}
		
		parent::offsetSet($index, $newval);
	}

	/**
	 * @param mixed $index
	 */
	public function offsetUnset($index)
	{
		$this->m_modified = true;
		parent::offsetUnset($index);
		parent::exchangeArray(array_values(parent::getArrayCopy()));
	}


	/**
	 * @param Integer $index
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function offsetGet($index)
	{
		return $this->getDocumentValue(parent::offsetGet($index));
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $newval
	 */
	public function append($newval)
	{
		if (!($newval instanceof f_persistentdocument_PersistentDocument))
		{
			throw new Exception("Document can not be null");
		}
		$this->m_modified = true;
		parent::append($newval);
	}

	public function exchangeArray($array = array())
	{
		$this->m_modified = true;
		parent::exchangeArray($array);
	}

	public function getIterator()
	{
		$this->resolveAllDocument();
		return parent::getIterator();
	}

	/**
	 * @param mixed $value
	 * @return Integer
	 */
	private function getIdValue($value)
	{
		if ($value instanceof f_persistentdocument_PersistentDocument)
		{
			return $value->getId();
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 * @return f_persistentdocument_PersistentDocument
	 */
	private function getDocumentValue($value)
	{
		if (is_numeric($value))
		{
			return f_persistentdocument_DocumentService::getInstance()->getDocumentInstance($value);
		}
		return $value;
	}

	/**
	 * @return array<mixed>
	 */
	private function getOriginalArray()
	{
		return parent::getArrayCopy();
	}

	private function resolveAllDocument()
	{
		if(Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__);
		}
		$values = parent::getArrayCopy();
		parent::exchangeArray(array());
		foreach ($values as $value)
		{
			parent::offsetSet(null, $this->getDocumentValue($value));
		}
	}


	/**
	 * @param Integer $minOccurence
	 * @param Integer $maxOccurence
	 * @param Integer $accceptType
	 * @return Boolean
	 *
	 */
	public function isValid($minOccurence = 0, $maxOccurence = 1, $accceptType = "*", $cascade = false)
	{
		if ($minOccurence > $this->count() || ($maxOccurence != -1 && $maxOccurence < $this->count()))
		{
			$this->m_parentDocument->addValidationError(f_Locale::translate('&framework.validation.validator.persisentdocumentarray;', array('field' => $this->m_relationName, 'minOccurs' => $minOccurence, 'maxOccurs' => $maxOccurence, 'count' => $this->count())));
			return false;
		}
		elseif ($cascade)
		{
			$valid = true;
			if ($this->count() > 0)
			{
				$iterator = $this->getIterator();
				while ($iterator->valid())
				{
					$valid = $valid && $iterator->current()->isValid();
					$iterator->next();
				}
			}
			return $valid;
		}
		return true;
	}

	/**
	 * @return void
	 */
	public function removeAll()
	{
		if ($this->count() > 0)
		{
			$this->m_modified = true;
			parent::exchangeArray(array());
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document the document to search
	 * @return f_persistentdocument_PersistentDocument document founded or null
	 */
	public function get($document)
	{
		if (!($document instanceof f_persistentdocument_PersistentDocument))
		{
			return null;
		}
		return $this->getById($document->getId());
	}

	/**
	 * @param Integer $documentId the id of the document to found
	 * @return f_persistentdocument_PersistentDocument document founded or null
	 */
	public function getById($documentId)
	{
		$iterator = parent::getIterator();
		while ($iterator->valid())
		{
			if ($this->getIdValue($iterator->current()) == $documentId)
			{
				return $iterator->current();
			}
			$iterator->next();
		}
		return null;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document the document to search
	 * @return Integer index of the founded document or -1
	 */
	public function indexOf($document)
	{
		if (!($document instanceof f_persistentdocument_PersistentDocument))
		{
			return -1;
		}
		return $this->indexOfById($document->getId());
	}

	/**
	 * @param Integer $documentId the id of the document to found
	 * @return Integer index of the founded document or -1
	 */
	public function indexOfById($documentId)
	{
		$iterator = parent::getIterator();
		while ($iterator->valid())
		{
			if ($this->getIdValue($iterator->current()) == $documentId)
			{
				return $iterator->key();
			}
			$iterator->next();
		}
		return -1;
	}

	/**
	 * @return re-indexed parent::getArrayCopy()
	 * @see ArrayObject
	 */
	public function getArrayCopy()
	{
		$this->resolveAllDocument();
		return array_values(parent::getArrayCopy());
	}


	/**
	 * @param f_persistentdocument_PersistentDocumentArray $documentArray
	 */
	public function mergeWith($documentArray)
	{
		if ($documentArray instanceof f_persistentdocument_PersistentDocumentArray)
		{
			foreach ($documentArray->getOriginalArray() as $value)
			{
				if ($this->indexOfById($this->getIdValue($value)) == -1)
				{
					$this->m_modified = true;
					parent::append($value);
				}
			}
		}
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentArray $documentArray
	 */
	public function replaceWith($documentArray)
	{
		if ($documentArray instanceof f_persistentdocument_PersistentDocumentArray)
		{
			$this->m_modified = true;
			parent::exchangeArray($documentArray->getOriginalArray());
		}
	}

	/**
	 * @return Boolean
	 */
	public function isModified()
	{
		return $this->m_modified;
	}

	/**
	 * @internal used by PersistentProvider only
	 * @param array<array<'document_id' => id>> $documentIds
	 */
	public function loadDocumentIds($documentIds)
	{
		$this->removeAll();

		$useOldValue = $this->m_preserveOldValue;
		$this->m_oldValues =  ($useOldValue) ? array() : null;

		foreach ($documentIds as $documentId)
		{
			$id = intval($documentId['document_id']);
			parent::offsetSet(null, $id);
			if ($useOldValue) { $this->m_oldValues[] = $id;}
		}
		$this->m_modified = false;
	}
	
	/**
	 * @return array<integer | PersistentDocument>
	 */
	public function getInternalArray()
	{
		return parent::getArrayCopy();
	}
	
	/**
	 * @internal used by PersistentDocument only for serialized property
	 * @param array<id> $documentIds
	 */
	public function loadSerializedDocumentIds($documentIds)
	{
		$this->removeAll();
		$useOldValue = $this->m_preserveOldValue;
		$this->m_oldValues =  ($useOldValue) ? array() : null;
		foreach ($documentIds as $documentId)
		{
			$id = intval($documentId);
			parent::offsetSet(null, $id);
			if ($useOldValue) { $this->m_oldValues[] = $id;}
		}
		$this->m_modified = false;
	}

	/**
	 * @internal used by PersistentDocument only
	 * @param Integer $documentId
	 */
	public function loadDocumentId($documentId)
	{
		$this->removeAll();
		if ($documentId)
		{
			parent::offsetSet(null, intval($documentId));
			if ($this->m_preserveOldValue)
			{
				$this->m_oldValues = array(intval($documentId));
			}
			else
			{
				$this->m_oldValues = null;
			}
		}
		$this->m_modified = false;
	}

	/**
	 * @return array<Integer>
	 */
	public function getOldValues()
	{
		if ($this->m_preserveOldValue && $this->m_oldValues)
		{
			return $this->m_oldValues;
		}

		return array();
	}
		
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use DocumentArray->isModified
	 */
	public function checkModified($oldRelation)
	{
		return $this->isModified();
	}
}