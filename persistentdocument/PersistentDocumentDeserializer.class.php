<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument
 */
class f_persistentdocument_PersistentDocumentDeserializer
{
	public function deserialize($persistentDocument, $data)
	{
		$this->debug("Deserialisation du document\n");
		$xml = simplexml_load_string($data);
		$prop = $persistentDocument->getPersistentModel()->getPropertiesInfo();
		foreach ($xml->children() as $datanode)
		{
			$name = $datanode->getName();
			$infos = self::getProperty($prop, $name);
			if (!is_null($infos))
			{
				if ($infos['type'] == f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENT)
				{
					$docList = $persistentDocument->{"get".ucfirst($infos['name'])."PVal"}();


					//Internal deserialisation
					foreach ($datanode->children() as $ref)
					{
						$class = $this->getDocumentClassFromModel(strval($ref['type']));
						$doc = new $class();
						$doc->initialize(strval($ref['documentid']));
						$docList[] = $doc;
					}
				}
				else if ($infos['isArray'])
				{
					$persistentDocument->{"set".ucfirst($infos['name'])."PVal"}(explode("||", strval($datanode)));
				}
				else
				{
					$persistentDocument->{"set".ucfirst($infos['name'])."PVal"}(strval($datanode));
				}
			}
		}
	}
}