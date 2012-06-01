<?php
/**
 * <{$className}>
 * @package modules.<{$moduleName}>
 */
class <{$className}> extends <{$extendClass}>
{
	/**
	 * @var <{$className}>
	 */
	private static $instance;

	/**
	 * @return <{$className}>
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

<{if !$inject}>	
	/**
	 * @return <{$moduleName}>_persistentdocument_<{$documentName}>
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_<{$moduleName}>/<{$documentName}>');
	}

	/**
	 * Create a query based on 'modules_<{$moduleName}>/<{$documentName}>' model.
	 * Return document that are instance of <{$moduleName}>_persistentdocument_<{$documentName}>,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_<{$moduleName}>/<{$documentName}>');
	}
	
	/**
	 * Create a query based on 'modules_<{$moduleName}>/<{$documentName}>' model.
	 * Only documents that are strictly instance of <{$moduleName}>_persistentdocument_<{$documentName}>
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_<{$moduleName}>/<{$documentName}>', false);
	}
<{/if}>
	
	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId)
//	{
<{if $hasParentModel}>
//		parent::preSave($document, $parentNodeId);
<{/if}>
//
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId)
//	{
<{if $hasParentModel}>
//		parent::preInsert($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId)
//	{
<{if $hasParentModel}>
//		parent::postInsert($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId)
//	{
<{if $hasParentModel}>
//		parent::preUpdate($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId)
//	{
<{if $hasParentModel}>
//		parent::postUpdate($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId)
//	{
<{if $hasParentModel}>
//		parent::postSave($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
<{if $hasParentModel}>
//		parent::preDelete($document);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
<{if $hasParentModel}>
//		parent::preDeleteLocalized($document);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
<{if $hasParentModel}>
//		parent::postDelete($document);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
<{if $hasParentModel}>
//		parent::postDeleteLocalized($document);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
//	public function isPublishable($document)
//	{
//		$result = parent::isPublishable($document);
//		return $result;
//	}


	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
<{if $hasParentModel}>
//		parent::publicationStatusChanged($document, $oldPublicationStatus, $params);
<{/if}>
//	}

	/**
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
<{if $hasParentModel}>
//		parent::onCorrectionActivated($document, $args);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
<{if $hasParentModel}>
//		parent::tagAdded($document, $tag);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
<{if $hasParentModel}>
//		parent::tagRemoved($document, $tag);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
<{if $hasParentModel}>
//		parent::tagMovedFrom($fromDocument, $toDocument, $tag);
<{/if}>
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
<{if $hasParentModel}>
//		parent::tagMovedTo($fromDocument, $toDocument, $tag);
<{/if}>
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
<{if $hasParentModel}>
//		parent::onMoveToStart($document, $destId);
<{/if}>
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
<{if $hasParentModel}>
//		parent::onDocumentMoved($document, $destId);
<{/if}>
//	}

	/**
	 * this method is call before saving the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $newDocument
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * this method is call after saving the duplicate document.
	 * $newDocument has an id affected.
	 * Traitment of the children of $originalDocument.
	 *
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $newDocument
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//	}

	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
//	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
//	{
//		return null;
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//		return parent::getWebsiteId($document);
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @return integer[] | null
	 */
//	public function getWebsiteIds($document)
//	{
//		return parent::getWebsiteIds($document);
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//		return parent::getDisplayPage($document);
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
//	public function getResume($document, $forModuleName, $allowedSections = null)
//	{
//		$resume = parent::getResume($document, $forModuleName, $allowedSections);
//		return $resume;
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
//	public function getSolrsearchResultItemTemplate($document, $bockName)
//	{
//		return array('module' => '<{$moduleName}>', 'template' => '<{$moduleUCFirst}>-Inc-<{$nameUCFirst}>ResultDetail');
//	}

	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param <{$moduleName}>_persistentdocument_<{$documentName}> $document
	 * @param String[] $propertiesName
	 * @param Array $datas
	 */
//	public function addFormProperties($document, $propertiesName, &$datas)
//	{
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document (Read only)
	 * @param array $defaultSynchroConfig string : string[]
	 * @return array string : string[]
	 */
//	public function getI18nSynchroConfig($document, $defaultSynchroConfig)
//	{
//		return parent::getI18nSynchroConfig($document, $defaultSynchroConfig);
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document (Read only)
	 * @param <{$module}>_persistentdocument_<{$name}>I18n $from (Read only)
	 * @param <{$module}>_persistentdocument_<{$name}>I18n $to
	 * @return boolean
	 */
//	public function synchronizeI18nProperties($document, $from, $to)
//	{
//		return parent::synchronizeI18nProperties($document, $from, $to);
//	}	
}