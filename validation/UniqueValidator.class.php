<?php
/**
 * @package framework.validation
 */
class validation_UniqueValidator extends validation_ValidatorImpl implements validation_Validator
{
	/**
	 * @var f_persistentdocument_PersistentDocument
	 */
	protected $document;
	
	/**
	 * @var String
	 */
	protected $documentModelName;
	
	/**
	 * @var String
	 */
	protected $documentPropertyName;
	
	/**
	 * @var Integer
	 */
	protected $parentId;
	
	/**
	 * @var f_persistentdocument_PersistentProvider
	 */
	protected $pp;
	
	/**
	 * Validate $data and append error message in $errors.
	 *
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 * @return void
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		if ($this->getParameter() == true)
		{
			if (empty($this->documentPropertyName))
			{
				throw new ValidatorConfigurationException('validation_UniqueValidator requires a valid document property name.');
			}
			
			if ($this->document instanceof f_persistentdocument_PersistentDocument)
			{
				$this->documentModelName = $this->document->getDocumentModelName();
				if ($this->pp === null)
				{
					$this->pp = $this->document->getProvider();
				}
				$model = $this->document->getPersistentModel();
			}
			else
			{
				if (!$this->pp instanceof f_persistentdocument_PersistentProvider)
				{
					throw new ValidatorConfigurationException('validation_UniqueValidator requires a valid PersistentProvider.');
				}
				if (empty($this->documentModelName))
				{
					throw new ValidatorConfigurationException('validation_UniqueValidator requires a valid Document Model name.');
				}
				$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->documentModelName);
			}
			
			$query = $this->pp->createQuery($this->documentModelName);
			
			// For string properties, the UniqueValidator is case INsensitive.
			$propertyObject = $model->getProperty($this->documentPropertyName);
			if ($propertyObject->isDocument())
			{
				$query->add(Restrictions::eq($this->documentPropertyName . '.id', $field->getValue()));
			}
			else
			{
				$query->add(Restrictions::eq($this->documentPropertyName, $field->getValue(), $propertyObject->isString()));
			}
			
			$result = $this->pp->findUnique($query);
			
			if ($result !== null && ($this->document === null || $this->document->isNew() || !DocumentHelper::equals($this->document, $result)))
			{
				$this->reject($field->getName(), $errors);
			}
		}
	}
	
	/**
	 * @param integer $parentId
	 */
	public final function setParentNodeId($parentId)
	{
		$this->parentId = $parentId;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public final function setDocument($document)
	{
		$this->document = $document;
	}
	
	/**
	 * @param string $documentModelName
	 */
	public final function setDocumentModelName($documentModelName)
	{
		$this->documentModelName = $documentModelName;
	}
	
	/**
	 * @param string $documentPropertyName
	 */
	public final function setDocumentPropertyName($documentPropertyName)
	{
		$this->documentPropertyName = $documentPropertyName;
	}
	
	/**
	 * @param f_persistentdocument_PersistentProvider $pp
	 */
	public final function setPersistentProvider($pp)
	{
		$this->pp = $pp;
	}
	
	/**
	 * Sets the value of the unique validator's parameter.
	 *
	 * @param mixed $value
	 */
	public function setParameter($value)
	{
		if ($value && strpos($value, ','))
		{
			$this->setPersistentProvider(f_persistentdocument_PersistentProvider::getInstance());
			$parts = explode(',', $value);
			$this->setDocumentModelName($parts[0]);
			$this->setDocumentPropertyName($parts[1]);
			if (isset($parts[2]) && $parts[2] !== '')
			{
				$doc = DocumentHelper::getDocumentInstance($parts[2]);
				if ($doc->getDocumentModelName() === $parts[0])
				{
					$this->setDocument($doc);
				}
				else
				{
					$this->setParentNodeId($parts[2]);
				}
			}
			parent::setParameter(true);
		}
		else
		{
			parent::setParameter(validation_BooleanValueParser::getValue($value));
		}
	}
}