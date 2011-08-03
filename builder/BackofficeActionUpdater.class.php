<?php
class builder_BackofficeActionUpdater
{
	/**
	 * Document model object
	 * @var f_persistentdocument_PersistentDocumentModel
	 */
	private $model = null;

	/**
	 * Load the document model
	 * @param string $model (ex: modules_users/users)
	 */
	public function __construct($modelName)
	{
		$this->model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
	}

	public function updateXmlDocument($parents)
	{
		// config/actions.xml file
		$fileActions = f_util_FileUtils::buildModulesPath($this->model->getModuleName(), 'config', 'actions.xml');

		$domDoc = f_util_DOMUtils::fromPath($fileActions);
		$domDoc->preserveWhiteSpace = false;

		$actionName = 'create' . ucfirst( $this->model->getDocumentName() );

		$actionElems = $this->xPath($domDoc, '//action[@name="' . $actionName . '"]');
		if ($actionElems->length == 0)
		{
			echo "Add action $actionName in $fileActions\n";
			$newAction = $domDoc->createElement('action');
			$newAction->setAttribute('name', $actionName);

			$newParameter = $domDoc->createElement('parameter');
			$newParameter->setAttribute('name', 'listWidget');

			$newBody = $domDoc->createElement('body');

			$cdataContent = "this.createDocumentEditor('modules_" . $this->model->getModuleName() . '_' . $this->model->getDocumentName() . "', listWidget.getSelectedItems()[0].id);";
			$newCData = $domDoc->createCDATASection($cdataContent);

			$newAction->appendChild($newParameter);
			$newBody->appendChild($newCData);
			$newAction->appendChild($newBody);

			$domDoc->documentElement->appendChild($newAction);
			$this->saveFile($fileActions, $domDoc);
		}
		else
		{
			echo "Action $actionName already defined in $fileActions\n";
		}

		// document editor directory
		$editordir = f_util_FileUtils::buildModulesPath($this->model->getModuleName(), 'forms', 'editor', $this->model->getDocumentName());
		if (!is_dir($editordir))
		{
			echo "Create $editordir directory\n";
			f_util_FileUtils::mkdir($editordir);
		}
		else
		{
			echo "$editordir directory already exists\n";
		}

		// module perspective model entry
		$perspectivePath = f_util_FileUtils::buildModulesPath($this->model->getModuleName(), 'config', 'perspective.xml');
		$perspective = f_util_DOMUtils::fromPath($perspectivePath);
		$perspectiveUpdated = false;
		if ($this->xPath($perspective, "models/model[@name = '".$this->model->getName()."']")->length == 0)
		{
			echo "Add model to $perspectivePath\n";
			$modelsElem = $this->xPathUnique($perspective, "models");
			$modelElem = $perspective->createElement("model");
			$modelElem->setAttribute("name", $this->model->getName());
			$modelsElem->appendChild($modelElem);
			
			$actionsElem = $perspective->createElement("contextactions");
			foreach (array("edit", "delete", "activate", "deactivated", "reactivate") as $action)
			{
				$actionElem = $perspective->createElement("contextaction");
				$actionElem->setAttribute("name", $action);
				$actionsElem->appendChild($actionElem);
			}
			$modelElem->appendChild($actionsElem);
			$perspectiveUpdated = true;
		}
		else
		{
			echo "Model already in $perspectivePath\n";
		}

		// module perspective child relationship
		foreach ($parents as $parent)
		{
			$parentModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName("modules_".$parent);

			// Add as child
			if ($this->xPath($perspective, "models/model[@name = '".$parentModel->getName()."']/children/child[@model = '".$this->model->getName()."']")->length == 0)
			{
				echo "Add ".$this->model->getDocumentName()." as $parent child\n";
				$parentElem = $this->xPathUnique($perspective, "models/model[@name = '".$parentModel->getName()."']");
				if ($parentElem === null)
				{
					throw new Exception("$parent is not declared in perspective");
				}
				$childrenElems = $parentElem->getElementsByTagName("children");
				if ($childrenElems->length == 0)
				{
					$childrenElem = $perspective->createElement("children");
					$parentElem->appendChild($childrenElem);
				}
				else
				{
					$childrenElem = $childrenElems->item(0);
				}
				$childElem = $perspective->createElement("child");
				$childElem->setAttribute("model", $this->model->getName());
				$childrenElem->appendChild($childElem);
				$perspectiveUpdated = true;
			}
			else
			{
				echo $this->model->getDocumentName()." is already a $parent child\n";
			}

			// contextmenu action
			if ($this->xPath($perspective, "models/model[@name = '".$parentModel->getName()."']/contextactions/contextaction[@name = '".$actionName."']")->length == 0)
			{
				echo "Add ".$actionName." to $parent contextual menu\n";
				$parentElem = $this->xPathUnique($perspective, "models/model[@name = '".$parentModel->getName()."']");
				if ($parentElem === null)
				{
					throw new Exception("$parent is not declared in perspective");
				}
				$actionElems = $parentElem->getElementsByTagName("contextactions");
				if ($actionElems->length == 0)
				{
					$actionsElem = $perspective->createElement("contextactions");
					$parentElem->appendChild($actionsElem);
				}
				else
				{
					$actionsElem = $actionElems->item(0);
				}
				$actionElem = $perspective->createElement("contextaction");
				$actionElem->setAttribute("name", $actionName);
				$actionsElem->appendChild($actionElem);
				$perspectiveUpdated = true;
			}
			else
			{
				echo $actionName." already in $parent contextual menu\n";
			}
		}

		if ($this->xPath($perspective, "actions/action[@name = '$actionName']")->length == 0)
		{
			echo "Declare $actionName in perspective\n";
			$perspectiveUpdated = true;
			$actionsElem = $this->xPathUnique($perspective, "actions");
			if ($actionsElem === null)
			{
				$actionsElem = $perspective->createElement("actions");
				$perspective->rootElement->appendChild($actionsElem);
			}

			$actionElem = $perspective->createElement("action");
			$actionElem->setAttribute("name", $actionName);
			$actionElem->setAttribute("single", "true");
			$actionElem->setAttribute("permission", "Insert_".$this->model->getDocumentName());
			// TODO: icon parameter ?
			$actionElem->setAttribute("icon", "add");
			
			$actionsElem->appendChild($actionElem);
		}
		else
		{
			echo "$actionName already declared in perspective\n";
		}

		if ($perspectiveUpdated)
		{
			//echo $perspective->saveXML();
			$this->saveFile($perspectivePath, $perspective);
		}
	}

	private function saveFile($file, $domDoc)
	{
		$domDoc->formatOutput = true;
		$content = $domDoc->saveXML();
		f_util_FileUtils::write($file, $content, f_util_FileUtils::OVERRIDE);
	}

	/**
	 * @return DOMNodeList
	 */
	private function xPath($domDoc, $query)
	{
		$xpath = new DOMXPath($domDoc);
		return $xpath->query($query);
	}

	/**
	 * @return DOMElement
	 */
	private function xPathUnique($domDoc, $query)
	{
		$nodes = $this->xPath($domDoc, $query);
		if ($nodes->length == 0)
		{
			return null;
		}
		return $nodes->item(0);
	}
}