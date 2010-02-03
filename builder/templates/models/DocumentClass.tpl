<?php
/**
 * Class where to put your custom methods for document <{$model->getDocumentClassName()}>
 * @package <{$model->getModuleName()}>.persistentdocument
 */
class <{$model->getDocumentClassName()}> extends <{$model->getDocumentClassName()}>base <{if $model->isIndexable()}>implements indexer_IndexableDocument<{/if}>

{
<{if $model->isIndexable()}>
	/**
	 * Get the indexable document
	 *
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		$indexedDoc = new indexer_IndexedDocument();
		// TODO : set the different properties you want in you indexedDocument :
		// - please verify that id, documentModel, label and lang are correct according your requirements
		// - please set text value.
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel('<{$model->getName()}>');
		$indexedDoc->setLabel($this->getLabel());
<{if $model->isInternationalized() }>
		$indexedDoc->setLang(RequestContext::getInstance()->getLang());
<{else}>
		$indexedDoc->setLang($this->getLang());
<{/if}>
		$indexedDoc->setText(null); // TODO : please fill text property
		return $indexedDoc;
	}
	
<{/if}>
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
//	public function addFormProperties($propertiesNames, &$formProperties)
//	{	
//	}
}