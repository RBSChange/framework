<?php
/**
 * @package framework.builder
 */
class builder_LocaleFileGenerator
{

	/**
	 * @var Boolean
	 */
	private $isModelInjected;
	/**
	 * Document model object
	 * @var f_persistentdocument_PersistentDocumentModel
	 */
	private $model = null;
	private $propNames;

	private $managedLocaleList;

	private $XPathObject = null;
	

	/**
	 * Load the document model
	 *
	 * @param generator_PersistentModel $model
	 */
	public function __construct($model)
	{
		$this->managedLocaleList = explode(" ", AG_UI_SUPPORTED_LANGUAGES);
		$this->isModelInjected = $model->inject();
		if ($this->isModelInjected)
		{
			$this->model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($model->getFinalName());
			$this->propNames = array();
			foreach ($model->getProperties() as $property)
			{
				if (!$property->isOverride())
				{
					$this->propNames[] = $property->getName();
				}
			}
		}
		else
		{
			$this->model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($model->getName());
		}
	}

	/**
	 * Generate or update document locale file
	 *
	 * @return unknown
	 */
	public function updateLocale()
	{
		// Document locale file
		$ls = LocaleService::getInstance();
		$override =  ($this->isModelInjected);
		$baseKey = 'm.' . $this->model->getModuleName() . '.document.' . $this->model->getDocumentName();
		$includes = '';
		
		// Get the list of properties
		$properties = array('document-name' => $this->model->getDocumentName());

		if ($this->propNames === null)
		{
			if ($this->model->getParentName() !== null)
			{
				echo "Parent: ".$this->model->getParentName()."\n";
				$parentModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->model->getParentName());
				$includes = 'm.' . $parentModel->getModuleName().".document.".$parentModel->getDocumentName();
			}
			else
			{
				$parentModel = null;		
				$includes = 'm.generic.document.document';
			}
			
			$hiddenProperties = array("author", "authorid", "creationdate", "modificationdate",
				"publicationstatus", "startpublicationdate", "endpublicationdate",
				"metas", "lang", "modelversion", "documentversion", "metastring", "s18s");
			
			foreach ($this->model->getPropertiesNames() as $propertyName)
			{
				$keyId = strtolower($propertyName);
				if (in_array($keyId, $hiddenProperties)) {continue;}		
				if (($parentModel === null || !$parentModel->hasProperty($propertyName)))
				{
					$properties[$keyId] = "[TO TRANSLATE] $propertyName";
					$properties[$keyId . '-help'] = "[TO TRANSLATE] $propertyName-help";
				}
			}
		}
		else
		{
			foreach ($this->propNames as $propertyName)
			{
				$keyId = strtolower($propertyName);
				$properties[$keyId] = "[TO TRANSLATE] $propertyName";
				$properties[$keyId . '-help'] = "[TO TRANSLATE] $propertyName-help";
			}
		}
		
		$keyInfos = array();
		foreach ($this->managedLocaleList as $lang) 
		{
			$lcid = $ls->getLCID($lang);
			$keyInfos[$lcid] = $properties;
		}
		
		$ls->updatePackage($baseKey, $keyInfos, $override, true, $includes);
	}

	function updateBoLocale()
	{
		// Update actions.xml file: add 'createDoc' if needed.
		$ls = LocaleService::getInstance();
		$baseKey = 'm.' . $this->model->getModuleName() . '.bo.actions';
		$keysInfos = array();
		$id  = "create" .$this->model->getDocumentName();
		$createLocales = array("fr" => "créer", "en" => "create", "de" => "neu");
		foreach ($createLocales as $lang => $text) 
		{
			$infos = array($id => $text);
			$keysInfos[$ls->getLCID($lang)] = $infos;
		}	
		$ls->updatePackage($baseKey, $keysInfos, false, true);
	}
}
