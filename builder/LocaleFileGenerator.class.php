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
		if ($this->isModelInjected)
		{
			$baseDir = f_util_FileUtils::buildOverridePath('modules');
		}
		else
		{
			$baseDir = f_util_FileUtils::buildWebeditPath('modules');
		}
		$srcLocaleFile = $destLocaleFile = $baseDir . DIRECTORY_SEPARATOR . $this->model->getModuleName() . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . 'document' . DIRECTORY_SEPARATOR . $this->model->getDocumentName() . '.xml';
		$builderLocaleFile = FRAMEWORK_HOME . DIRECTORY_SEPARATOR . 'builder' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . 'documentLocalizationTemplate.all.all.xml';

		$addOtherLocales = array();

		if ( ! is_readable( $srcLocaleFile ) )
		{
			$srcLocaleFile = $builderLocaleFile;
			echo "Generating $destLocaleFile\n";
		}
		else
		{
			echo "Updating $destLocaleFile\n";
		}

		$domDoc = f_util_DOMUtils::fromPath($srcLocaleFile);

		// Get the list of properties
		$properties = array();

		if ($this->propNames === null)
		{
			if ($this->model->getParentName() !== null)
			{
				echo "Parent: ".$this->model->getParentName()."\n";
				$parentModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->model->getParentName());
				$domDoc->documentElement->setAttribute("extend", "modules.".$parentModel->getModuleName().".document.".$parentModel->getDocumentName());
			}
			else
			{
				$parentModel = null;
			}
			$addOtherLocales['document-name'] = $this->model->getDocumentName();
			$hiddenProperties = array("author", "authorid", "creationdate", "modificationdate", "label",
				"publicationstatus", "startpublicationdate", "endpublicationdate",
				"metas", "lang", "modelversion", "documentversion", "metastring", "s18s");
			foreach ($this->model->getPropertiesNames() as $propertyName)
			{
				if (in_array($propertyName, $hiddenProperties)) {continue;}
				if ($parentModel === null || !$parentModel->hasProperty($propertyName))
				{
					$properties[$propertyName] = "[TO TRANSLATE] $propertyName";
					$properties[$propertyName . '-help'] = "[TO TRANSLATE] $propertyName-help";
				}
			}
				
			if ($this->model->publishOnDayChange() && ($parentModel === null || !$parentModel->publishOnDayChange()))
			{
				$properties['startpublicationdate'] = f_Locale::translate('&modules.generic.document.Document.startpublicationdate;');
				$properties['startpublicationdate-help'] = f_Locale::translate('&modules.generic.document.Document.startpublicationdate-help;');

				$properties['endpublicationdate'] = f_Locale::translate('&modules.generic.document.Document.endpublicationdate;');
				$properties['endpublicationdate-help'] = f_Locale::translate('&modules.generic.document.Document.endpublicationdate-help;');
			}
		}
		else
		{
			foreach ($this->propNames as $propertyName)
			{
				$properties[$propertyName] = "[TO TRANSLATE] $propertyName";
				$properties[$propertyName . '-help'] = "[TO TRANSLATE] $propertyName-help";
			}
		}

		$properties = array_merge($addOtherLocales, $properties);

		foreach ($properties as $propertyName => $propertyValue)
		{

			$entity = $this->XPathQuery($domDoc, '//*[@id="'.$propertyName.'"]');
			$entity = $entity->item(0);

			if ( ! isset($entity) )
			{
				// Add a new entity
				// An entity has many localization. One per lang
				$newEntity = $domDoc->createElement('entity');
				$newEntity->setAttribute('id', $propertyName);

				$this->createLocalesTagsOfEntity($newEntity, $this->managedLocaleList, $domDoc, $propertyValue);

				// Add Entity to the end of file in root element
				$domDoc->documentElement->appendChild($newEntity);
			}
			else
			{
				// List of new lang
				$newLang = array();
				$newLang = $this->managedLocaleList;
				$newLang = array_flip($newLang);

				// Get children of entity
				foreach ( $entity->childNodes as $child)
				{
					if ( isset($newLang[$child->getAttribute('lang')]) )
					{
						unset( $newLang[$child->getAttribute('lang')] );
					}
				}

				$newLang = array_keys($newLang);

				if ( count($newLang) > 0 )
				{
					$this->createLocalesTagsOfEntity($entity, $newLang, $domDoc, $propertyValue);
				}
			}
		}

		$this->saveFile($destLocaleFile, $domDoc);
	}

	function updateBoLocale()
	{
		// Update actions.xml file: add 'createDoc' if needed.
		$srcLocaleFile = $destLocaleFile = AG_MODULE_DIR . DIRECTORY_SEPARATOR . $this->model->getModuleName() . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . 'bo' . DIRECTORY_SEPARATOR . 'actions.xml';

		$domDoc = f_util_DOMUtils::fromPath($srcLocaleFile);
		$entityId = "Create-".ucfirst($this->model->getDocumentName());
		if (!$domDoc->exists('entity[@id="'.$entityId.'"]'))
		{
			echo "Add entity $entityId in $destLocaleFile\n";
			$createLocales = array("fr" => "Créer", "en" => "Create", "de" => "Neu");

			$newEntity = $domDoc->createElement('entity');
			$newEntity->setAttribute('id', $entityId);
			foreach ($this->managedLocaleList as $lang)
			{
				if (isset($createLocales[$lang]))
				{
					$locale = $createLocales[$lang];
				}
				else
				{
					$locale = $createLocales["en"];
				}
				echo "Add localization $lang\n";
				$this->addLocalization($newEntity, $lang, $locale." ".$this->model->getDocumentName());
			}
			$domDoc->documentElement->appendChild($newEntity);
			f_util_DOMUtils::save($domDoc, $destLocaleFile);
		}
		else
		{
			echo "Entity $entityId already defined in $srcLocaleFile\n";
		}
	}

	/**
	 * @param DOMElement $entity
	 * @param String $lang
	 * @param String $value
	 */
	private function addLocalization($entity, $lang, $value)
	{
		$newLocale = $entity->ownerDocument->createElement('locale', $value);
		$newLocale->setAttribute('lang', $lang);
		$entity->appendChild($newLocale);
	}

	/**
	 * @param f_util_DOMDocument $doc
	 * @param String $key
	 * @param array $values
	 */
	private function addEntity($doc, $key, $values)
	{
		$entity = $doc->findUnique("entity[@id = '$key']");
		if ($entity === null)
		{
			$entity = $doc->createElement("entity");
			$entity->setAttribute("id", $key);
			$doc->documentElement->appendChild($entity);
		}
		foreach ($values as $lang => $value)
		{
			$this->addLocalization($entity, $lang, $value);
		}
	}

	private function createLocalesTagsOfEntity($entity, $localesList, $domDoc, $localeValue = '')
	{
		foreach ($localesList as $locale)
		{
			$this->addLocalization($entity, $locale, $localeValue);
		}
	}

	private function saveFile($file, $domDoc)
	{
		$domDoc->formatOutput = true;
		$content = $domDoc->saveXML();
		f_util_FileUtils::writeAndCreateContainer($file, $content, f_util_FileUtils::OVERRIDE);
	}

	private function XPathQuery($domDoc, $query)
	{
		if (is_null($this->XPathObject))
		{
			$this->XPathObject = new DOMXPath($domDoc);
		}
		return $this->XPathObject->query($query);
	}

}
