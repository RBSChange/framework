<?php
/**
 * <{$module}>_<{$nameUCFirst}>Service
 * @package modules.<{$module}>
 */
class <{$module}>_<{$nameUCFirst}>Service extends <{$model->getBaseServiceClassName()}>
{
	/**
	 * @var <{$module}>_<{$nameUCFirst}>Service
	 */
	private static $instance;

	/**
	 * @return <{$module}>_<{$nameUCFirst}>Service
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return <{$module}>_persistentdocument_<{$name}>
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_<{$module}>/<{$name}>');
	}

	/**
	 * Create a query based on 'modules_<{$module}>/<{$name}>' model.
	 * Return document that are instance of modules_<{$module}>/<{$name}>,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_<{$module}>/<{$name}>');
	}
	
	/**
	 * Create a query based on 'modules_<{$module}>/<{$name}>' model.
	 * Only documents that are strictly instance of modules_<{$module}>/<{$name}>
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_<{$module}>/<{$name}>', false);
	}
	
	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId)
//	{
<{if $model->hasParentModel()}>
//		parent::preSave($document, $parentNodeId);
<{/if}>
//
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId)
//	{
<{if $model->hasParentModel()}>
//		parent::preInsert($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId)
//	{
<{if $model->hasParentModel()}>
//		parent::postInsert($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId)
//	{
<{if $model->hasParentModel()}>
//		parent::preUpdate($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId)
//	{
<{if $model->hasParentModel()}>
//		parent::postUpdate($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId)
//	{
<{if $model->hasParentModel()}>
//		parent::postSave($document, $parentNodeId);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
<{if $model->hasParentModel()}>
//		parent::preDelete($document);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
<{if $model->hasParentModel()}>
//		parent::preDeleteLocalized($document);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
<{if $model->hasParentModel()}>
//		parent::postDelete($document);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
<{if $model->hasParentModel()}>
//		parent::postDeleteLocalized($document);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
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
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
<{if $model->hasParentModel()}>
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
<{if $model->hasParentModel()}>
//		parent::onCorrectionActivated($document, $args);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
<{if $model->hasParentModel()}>
//		parent::tagAdded($document, $tag);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
<{if $model->hasParentModel()}>
//		parent::tagRemoved($document, $tag);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
<{if $model->hasParentModel()}>
//		parent::tagMovedFrom($fromDocument, $toDocument, $tag);
<{/if}>
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param <{$module}>_persistentdocument_<{$name}> $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
<{if $model->hasParentModel()}>
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
<{if $model->hasParentModel()}>
//		parent::onMoveToStart($document, $destId);
<{/if}>
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
<{if $model->hasParentModel()}>
//		parent::onDocumentMoved($document, $destId);
<{/if}>
//	}

	/**
	 * this method is call before saving the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param <{$module}>_persistentdocument_<{$name}> $newDocument
	 * @param <{$module}>_persistentdocument_<{$name}> $originalDocument
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
	 * @param <{$module}>_persistentdocument_<{$name}> $newDocument
	 * @param <{$module}>_persistentdocument_<{$name}> $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//	}

	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param <{$module}>_persistentdocument_<{$name}> $document
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
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//		return parent::getWebsiteId($document);
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @return integer[] | null
	 */
//	public function getWebsiteIds($document)
//	{
//		return parent::getWebsiteIds($document);
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//		return parent::getDisplayPage($document);
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
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
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
//	public function getSolrsearchResultItemTemplate($document, $bockName)
//	{
//		return array('module' => '<{$module}>', 'template' => '<{$moduleUCFirst}>-Inc-<{$nameUCFirst}>ResultDetail');
//	}

	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param <{$module}>_persistentdocument_<{$name}> $document
	 * @param String[] $propertiesName
	 * @param Array $datas
	 */
//	public function addFormProperties($document, $propertiesName, &$datas)
//	{
//	}
		
}