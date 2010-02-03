<?php
/**
 * @package framework.validation
 */
class validation_UniqueInParentNodeValidator extends validation_UniqueValidator
{
	/**
	 * Validate $data and append error message in $errors.
	 *
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 *
	 * @return void
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		if ($this->getParameter() == true)
		{
			if ( empty($this->documentPropertyName) )
			{
				throw new ValidatorConfigurationException('validation_UniqueInParentValidator requires a valid document property name.');
			}

			if ($this->document instanceof f_persistentdocument_PersistentDocument)
			{
				$this->documentModelName = $this->document->getDocumentModelName();
				if (is_null($this->pp))
				{
					$this->pp = $this->document->getProvider();
				}
				$model = $this->document->getPersistentModel();
			}
			else
			{
				if ( ! $this->pp instanceof f_persistentdocument_PersistentProvider )
				{
					throw new ValidatorConfigurationException('validation_UniqueInParentValidator requires a valid PersistentProvider.');
				}
				if ( empty($this->documentModelName) )
				{
					throw new ValidatorConfigurationException('validation_UniqueInParentValidator requires a valid Document Model name.');
				}
				$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->documentModelName);
			}

			$query = $this->pp->createQuery($this->documentModelName);

			// For string properties, the UniqueValidator is case INsensitive.
			$propertyObject = $model->getProperty($this->documentPropertyName);
			if ($propertyObject->isDocument())
			{
				$query->add(Restrictions::eq($this->documentPropertyName.'.id', $field->getValue()));
			}
			else
			{
				$query->add(Restrictions::eq($this->documentPropertyName, $field->getValue(), $propertyObject->isString()));
			}

			//
			if ($this->document === null)
			{
				$query->add(Restrictions::childOf($this->parentId));
			}
			else
			{
				if ($this->document->isNew())
				{
					$query->add(Restrictions::childOf($this->document->getParentNodeId()));
				}
				else
				{
					$node = TreeService::getInstance()->getInstanceByDocument($this->document);
					if ($node === null)
					{
						throw new Exception("No node attached to document ".$this->document->getId());
					}
					$parentNode = $node->getParent();
					if ($parentNode === null)
					{
						throw new Exception("No parent node for document ".$this->document->getId());
					}
					$query->add(Restrictions::childOf($parentNode->getId()));
				}
			}

			$result = $this->pp->findUnique($query);

			if ( ! is_null($result) && (is_null($this->document) || $this->document->isNew() || !DocumentHelper::equals($this->document, $result)))
			{
				$this->reject($field->getName(), $errors);
			}
		}
	}
}