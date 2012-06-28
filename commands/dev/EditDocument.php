<?php
class commands_EditDocument extends c_ChangescriptCommand
{
	private $actions = array("add-property", "del-property", "rename-property", "set-indexable", "set-localized");

	/**
	 * @return String
	 */
	function getUsage()
	{
		$usage = "<moduleName> <documentName> <action>\nWhere action in:
- add-property <propertyName> <propertyType>
- del-property <propertyName>
- rename-property <propertyName> <newPropertyName>";
/*
- change-property <propertyName>
- manage-constraints <propertyName>
- set-indexable <true|false>
- set-backoffice-indexable <true|false>
*/
		return $usage;
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "edit an existing document";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) >= 3 && in_array($params[2], $this->actions) &&
		file_exists("modules/".$params[0]."/persistentdocument/".$params[1].".xml")
		&& (count($params) >= 5 || ($params[2] != "rename-property" && $params[2] != "add-property"));
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
		$moduleName = $params[0];
		if ($completeParamCount == 1)
		{
			$docs = array();
			foreach (glob("modules/$moduleName/persistentdocument/*.xml") as $doc)
			{
				$docs[] = basename($doc, '.xml');
			}
			return $docs;
		}
		$documentName = $params[1];
		if ($completeParamCount == 2)
		{
			return $this->actions;
		}
		$action = $params[2];
		if ($completeParamCount == 3)
		{
			switch ($action)
			{
				case "del-property":
				case "rename-property":
				case "change-property":
					return $this->getEditableProperties($moduleName, $documentName);
					break;
				case "add-property":
					return null;
					break;
			}
		}
		if ($completeParamCount == 4)
		{
			switch ($action)
			{
				case "add-property":
					$this->loadFramework();
					return builder_DocumentGenerator::getPropertyTypes();
					break;
			}
		}
		return null;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Edit document ==");

		$moduleName = $params[0];
		$documentName = $params[1];
		$actionName = $params[2];

		$this->loadFramework();

		switch ($actionName)
		{
			case "del-property":
				return $this->delProperty($moduleName, $documentName, $params[3]);
				break;
			case "rename-property":
				return $this->renameProperty($moduleName, $documentName, $params[3], $params[4]);
				break;
			case "change-property":
				return $this->changeProperty($moduleName, $documentName, $params[3]);
				break;
			case "add-property":
				return $this->addProperty($moduleName, $documentName, $params[3], $params[4]);
				break;
		}
		return null;
	}

	// Private methods.

	private function getEditableProperties($moduleName, $documentName)
	{
		$doc = $this->getDom($moduleName, $documentName);
		if ($doc === null)
		{
			return null;
		}
		$props = array();
		$genericProps = generic_persistentdocument_documentmodel::getGenericDocumentPropertiesNames();
		foreach ($doc->find("//c:add") as $addElem)
		{
			$name = $addElem->getAttribute("name");
			if (!in_array($name, $genericProps))
			{
				$props[] = $name;
			}
		}
		return $props;
	}

	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @return f_util_DOMDocument
	 */
	private function getDom($moduleName, $documentName)
	{
		$path = "modules/$moduleName/persistentdocument/$documentName.xml";
		if (!file_exists($path))
		{
			return null;
		}
		$doc = f_util_DOMUtils::fromPath($path);
		$doc->registerNamespace("c", "http://www.rbs.fr/schema/change-document/1.0");
		return $doc;
	}

	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @param f_util_DOMDocument $doc
	 * @return String the path of archive
	 */
	private function updateDom($moduleName, $documentName, $doc, $makeArchive = true)
	{
		$path = "modules/$moduleName/persistentdocument/$documentName.xml";
		if ($makeArchive)
		{
			$archiveDir = "modules/$moduleName/persistentdocument/old";
			$revision = 0;
			if (!is_dir($archiveDir))
			{
				f_util_FileUtils::mkdir($archiveDir);
			}
			else
			{
				foreach (glob("$archiveDir/$documentName*.xml") as $archive)
				{
					$matches = null;
					if (preg_match('/^.*\/'.$documentName.'-([0-9]*).xml$/', $archive, $matches))
					{
						$archiveRevision = (int) $matches[1];
						if ($archiveRevision > $revision)
						{
							$revision = $archiveRevision;
						}
					}
				}
			}
			$revision++;
			$archivePath = $archiveDir."/$documentName-$revision.xml";
			f_util_FileUtils::cp($path, $archivePath);
		}
		else
		{
			$archivePath = null;
		}
		f_util_DOMUtils::save($doc, $path);
		return $archivePath;
	}

	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	private function getModel($moduleName, $documentName)
	{
		return f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $documentName);
	}

	private function delProperty($moduleName, $documentName, $propertyName)
	{
		$editableProperties = $this->getEditableProperties($moduleName, $documentName);
		if (!in_array($propertyName, $editableProperties))
		{
			return $this->quitError("Can not edit $moduleName/$documentName.$propertyName: does not exists or is not editable");
		}
		if (!$this->yesNo("Are you sure you want to delete property '$propertyName' from document '$moduleName/$documentName' ?"))
		{
			return $this->quitOk("Task canceled. Nothing was done.");
		}

		$doc = $this->getDom($moduleName, $documentName);
		$oldModel = generator_PersistentModel::loadModelFromString($doc->saveXML(), $moduleName, $documentName);
		$oldProp = $oldModel->getPropertyByName($propertyName);
		
		$schemaManager = f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager();
		
		$script = $schemaManager->delProperty($moduleName, $documentName, $oldProp);
		$this->okMessage("Database updated");

		$doc->findAndRemove("//c:properties/c:add[@name = '$propertyName']");
		$archivePath = $this->updateDom($moduleName, $documentName, $doc);
		$this->okMessage("XML updated. See $archivePath for backup.");

		// TODO: locales, forms

		$this->executeCommand("compile-documents");

		$this->message("Executed SQL:
$script

You may create a new patch to handle this modification.
Use '" . $this->getChangeCmdName() . " create-patch $moduleName' to initiate the patch and copy-paste the following:

\$archivePath = f_util_FileUtils::buildProjectPath('$archivePath');
\$oldModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read(\$archivePath), '$moduleName', '$documentName');
\$oldProp = \$oldModel->getPropertyByName('$propertyName');
f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager()->('$moduleName', '$documentName', \$oldProp);
");

		return $this->quitOk("Property $moduleName/$documentName.$propertyName deleted");
	}

	private function renameProperty($moduleName, $documentName, $propertyName, $newPropertyName)
	{
		$editableProperties = $this->getEditableProperties($moduleName, $documentName);
		if (!in_array($propertyName, $editableProperties))
		{
			return $this->quitError("Can not edit $moduleName/$documentName.$propertyName: does not exists or is not editable");
		}
		if (!$this->yesNo("Are you sure you want to rename property '$propertyName' from document '$moduleName/$documentName' to '$newPropertyName' ?"))
		{
			return $this->quitOk("Task canceled. Nothing was done.");
		}

		$model = $this->getModel($moduleName, $documentName);
		$beanProperty = $model->getBeanPropertyInfo($propertyName);

		$doc = $this->getDom($moduleName, $documentName);
		$oldModel = generator_PersistentModel::loadModelFromString($doc->saveXML(), $moduleName, $documentName);
		$oldProp = $oldModel->getPropertyByName($propertyName);

		$propElem = $doc->findUnique("//c:properties/c:add[@name = '$propertyName']");
		$propElem->setAttribute("name", $newPropertyName);
		$newModel = generator_PersistentModel::loadModelFromString($doc->saveXML(), $moduleName, $documentName);
		$newProp = $newModel->getPropertyByName($newPropertyName);

		$schemaManager = f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager();
		
		$script = $schemaManager->renameProperty($moduleName, $documentName, $oldProp, $newProp);
		$this->okMessage("Database updated");

		$archivePath = $this->updateDom($moduleName, $documentName, $doc);
		$this->okMessage("XML updated. See $archivePath for backup.");

		// TODO: locales, forms

		$this->executeCommand("compile-documents");
		if ($newProp->isDocument())
		{
			$this->executeCommand("compile-db-schema");
			$compileSchema = '$this->execChangeCommand(\'compile-db-schema\');' . "\n";
		}
		
		$newPath = "modules/$moduleName/persistentdocument/$documentName.xml";
		$this->message("Executed SQL:
$script.

You may create a new patch to handle this modification.
Use '" . $this->getChangeCmdName() . " create-patch $moduleName' to initiate the patch and copy-paste the following:

\$archivePath = f_util_FileUtils::buildProjectPath('$archivePath');
\$newPath = f_util_FileUtils::buildProjectPath('$newPath');
\$oldModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read(\$archivePath), '$moduleName', '$documentName');
\$oldProp = \$oldModel->getPropertyByName('$propertyName');
\$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read(\$newPath), '$moduleName', '$documentName');
\$newProp = \$newModel->getPropertyByName('$newPropertyName');
f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager()->renameProperty('$moduleName', '$documentName', \$oldProp, \$newProp);
$compileSchema");

		$this->warnMessage("Do not forget to rename any method call to ".$beanProperty->getSetterName()."() or ".$beanProperty->getGetterName()."() methods in PHP or template code.");

		return $this->quitOk("Property $moduleName/$documentName.$propertyName renamed to $newPropertyName");
	}

	private function changeProperty($moduleName, $documentName, $propertyName)
	{

	}

	private function addProperty($moduleName, $documentName, $propertyName, $propertyType)
	{
		$model = $this->getModel($moduleName, $documentName);
		if ($model->hasProperty($propertyName))
		{
			return $this->quitError("Property $moduleName/$documentName.$propertyName already exists");
		}

		if (!in_array($propertyType, builder_DocumentGenerator::getPropertyTypes()))
		{
			return $this->quitError("Invalid property type $propertyType\nSupported types are: ".join(", ", builder_DocumentGenerator::getPropertyTypes()).".");
		}

		$isDocument = preg_match('/^modules_.*\/.*$/', $propertyType);

		// Property infos.
		$propInfo = array("min-occurs" => null, "from-list" => null, "db-mapping" => null);
		if ($isDocument)
		{
			$propInfo["max-occurs"] = null;
			$propInfo["tree-node"] = null;
			$propInfo["cascade-delete"] = null;
			$propInfo["inverse"] = null;
		}
		else
		{
			$propInfo["db-size"] = null;
			$propInfo["localized"] = null;
			$propInfo["default-value"] = null;
		}
		ksort($propInfo);

		// Default values.
		$defaultValues = array("min-occurs" => "0", "db-mapping" => strtolower($propertyName),
			 "max-occurs" => "1", "tree-node" => "false", "cascade-delete" => "false",
			 "inverse" => "false", "localized" => "false");
		
		if ($propertyType == "Boolean")
		{
			$defaultValues["default-value"] = "false";
		}

		$answer = null;
		do
		{
			if ($answer !== null)
			{
				if (f_util_StringUtils::isEmpty($answer))
				{
					return $this->quitOk("Task cancelled: nothing was done");
				}
				elseif (is_numeric($answer) && $answer > 0 && $answer <= count($propInfo))
				{
					$index = 1;
					foreach ($propInfo as $attrName => $attrValue)
					{
						if ($index == $answer)
						{
							break;
						}
						$index++;
					}
					$attrQuestion = "== Please provide a value for '$attrName'";
					if ($attrValue !== null)
					{
						$attrQuestion .= ". Provide 'NULL' to blank it";
					}
					$attrQuestion .= " or type [Enter] to leave as it is:";
					$attrValue = $this->question($attrQuestion, null, false);
					if (f_util_StringUtils::isEmpty($attrValue))
					{
						$this->okMessage("=> Do nothing on '$attrName' attribute");
					}
					else
					{
						$error = false;
						
						$computedPropInfo = array();
						foreach ($defaultValues as $key => $value)
						{
							$computedPropInfo[$key] = isset($propInfo[$key]) ? $propInfo[$key]: $value;
						}
						
						if ($attrValue == "NULL")
						{
							$attrValue = null;
						}
						else
						{
							switch ($attrName)
							{
								case "min-occurs":
									if (!ctype_digit($attrValue) || $attrValue < 0 || $computedPropInfo["max-occurs"] < $attrValue)
									{
										$this->errorMessage("Invalid value '$attrValue' for min-occurs attribute");
										$error = true;
									}
									break;
								case "max-occurs":
									if ($attrValue != -1 && (!ctype_digit($attrValue) || $attrValue < 1 || $computedPropInfo["min-occurs"] > $attrValue))
									{
										$this->errorMessage("Invalid value '$attrValue' for max-occurs attribute");
										$error = true;
									}
									break;
								case "from-list":
									$list = list_ListService::getInstance()->getByListId($attrValue);
									if ($list === null)
									{
										$this->errorMessage("List '$attrValue' does not exists");
										$error = true;
									}
									break;
								case "db-mapping":
								case "default-value":
									break;
								case "tree-node":
								case "cascade-delete":
								case "localized":
								case "inverse":
									if ($attrValue != "false" && $attrValue != "true")
									{
										$this->errorMessage("Invalid value '$attrValue' for $attrName attribute");
										$error = true;
									}
									break;
								case "db-size":
									if (!is_int($attrValue) || $attrValue < 0)
									{
										$this->errorMessage("Invalid value '$attrValue' for db-size attribute");
										$error = true;
									}
									break;
							}
						}
						if (!$error)
						{
							$propInfo[$attrName] = $attrValue;
						}
					}
				}
				else
				{
					$this->errorMessage("Invalid attribute index '$answer'");
				}
			}

			$msg = "\n** Current attributes of '$propertyName' property, type '$propertyType' are:\n";
			$index = 1;
			foreach ($propInfo as $attrName => $attrValue)
			{
				$msg .= $index.") ";
				$msg .= $attrName.":	";
				if ($attrValue === null)
				{
					if (!isset($defaultValues[$attrName]))
					{
						$msg .= " NULL";
					}
					else
					{
						$msg .= " NULL, default value '".$defaultValues[$attrName]."'";
					}
				}
				else
				{
					$msg .= $attrValue;
				}
				$msg .= "\n";
				$index++;
			}
			$this->message($msg);
		} while (($answer = $this->question("== Please provide the index of attribute to edit, confirm with 'OK' or type [Enter] to cancel:", "", false)) != 'OK');

		// Use typed "OK". Let's do it
		$doc = $this->getDom($moduleName, $documentName);
		// Curious we do not have to use createElementNS()...
		$newElem = $doc->createElement("add");
		$newElem->setAttribute("name", $propertyName);
		$newElem->setAttribute("type", $propertyType);
		foreach ($propInfo as $attrName => $attrValue)
		{
			if ($attrValue !== null)
			{
				$newElem->setAttribute($attrName, $attrValue);
			}
		}
		$doc->findUnique("c:properties")->appendChild($newElem);

		$model = generator_PersistentModel::loadModelFromString($doc->saveXML(), $moduleName, $documentName);
		$prop = $model->getPropertyByName($propertyName);
		
		$schemaManager = f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager();
				
		$script = $schemaManager->addProperty($moduleName, $documentName, $prop);

		$this->updateDom($moduleName, $documentName, $doc, false);
		
		$this->executeCommand("compile-documents");
		if ($prop->isDocument())
		{
			$this->executeCommand("compile-db-schema");
			$compileSchema = '$this->execChangeCommand(\'compile-db-schema\');' . "\n";
		}
		else
		{
			$compileSchema = '';
		}
		
		$newPath = "modules/$moduleName/persistentdocument/$documentName.xml";
		$this->message("Executed SQL:
$script

You may create a new patch to handle this modification.
Use '" . $this->getChangeCmdName() . " create-patch $moduleName' to initiate the patch and copy-paste the following:

\$newPath = f_util_FileUtils::buildProjectPath('$newPath');
\$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read(\$newPath), '$moduleName', '$documentName');
\$newProp = \$newModel->getPropertyByName('$propertyName');
f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager()->addProperty('$moduleName', '$documentName', \$newProp);
$compileSchema");
		return null;
	}
}