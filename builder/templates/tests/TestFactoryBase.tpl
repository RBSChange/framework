<?php
/**
 * @package modules.<{$module}>
 */
abstract class <{$module}>_TestFactoryBase
{

<{foreach from=$models item=model}>
	private $m_<{$model->getDocumentName()}>_properties = array();
<{/foreach}>

	abstract protected function init();
	
	protected function __construct()
	{
		$this->init();
	}

	public final function reset()
	{
<{foreach from=$models item=model}>
		$this->m_<{$model->getDocumentName()}>_properties = array();
<{/foreach}>

		$this->init();	
	}
	
<{foreach from=$models item=model}>
	/**
	 * @param String $name
	 * @param Mixed $value
	 * @return void
	 */
	public final function set<{$model->getDocumentName()|capitalize}>DefaultProperty($name, $value)
	{
		$this->m_<{$model->getDocumentName()}>_properties[$name] = $value;
	}
	
	/**
	 * @param String $name
	 * @return Mixed
	 */
	public final function get<{$model->getDocumentName()|capitalize}>DefaultProperty($name)
	{
		if (isset($this->m_<{$model->getDocumentName()}>_properties[$name]))
		{
			return $this->m_<{$model->getDocumentName()}>_properties[$name];
		}
		
		return null;
	}
	
	/**
	 * @param <{$module}>_persistentdocument_<{$model->getDocumentName()}> $document
	 * @param array<String, Mixed> $properties
	 * @return void
	 */
	protected function set<{$model->getDocumentName()|capitalize}>Properties($document, $properties)
	{
		DocumentHelper::setPropertiesTo(array_merge($this->m_<{$model->getDocumentName()}>_properties, $properties), $document);
		return null;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $parent
	 * @param array<String, Mixed> $properties
	 * @return <{$module}>_persistentdocument_<{$model->getDocumentName()}>
	 */
	public final function getNew<{$model->getDocumentName()|capitalize}>($parent = null, $properties = array())
	{
		$documentService = <{$module}>_<{$model->getDocumentName()|capitalize}>Service::getInstance();
		$document = $documentService->getNewDocumentInstance();

		$this->set<{$model->getDocumentName()|capitalize}>Properties($document, $properties);
		
		$parentId = null;
		if (! is_null($parent))
		{
			$parentId = $parent->getId();
		}
		$document->save($parentId);
		
		return $document;
	}

<{/foreach}>		

}