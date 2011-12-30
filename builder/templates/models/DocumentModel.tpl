/**
 * <{if $model->inject()}>This model is not the original <{$model->getFinalDocumentClassName()}> model. It is in realty <{$model->getDocumentClassName()}>'s model that injects it.<{/if}> 
 * <{$model->getFinalDocumentClassName()}>model
 * @package modules.<{$model->getFinalModuleName()}>.persistentdocument
 */
class <{$model->getFinalDocumentClassName()}>model extends f_persistentdocument_PersistentDocumentModel
{
	/**
	 * Constructor of <{$model->getFinalDocumentClassName()}>model
	 */
	protected function __construct()
	{
		parent::__construct($this->getName());
		$this->m_preservedPropertiesNames = array(<{foreach from=$model->getPreservedPropertiesNames() item=name}>'<{$name}>' => true,<{/foreach}>);
		$this->m_statuses = array(<{foreach from=$model->getStatuses() item=status}>'<{$status}>',<{/foreach}>);
<{if ($model->getFinalChildren())}>	
		$this->m_childrenNames = array(
<{foreach from=$model->getFinalChildren() item=children}>
			'<{$children->getName()}>',
<{/foreach}>
		);
<{/if}>
		
<{if ($model->hasFinalParentModel())}>
		$this->m_parentName = '<{$model->getFinalParentModelName()}>';
<{/if}> 
	}

	protected final function loadProperties()
	{
		$this->m_properties = array(
<{foreach from=$model->getProperties() item=property}>
			'<{$property->getName()}>' => new PropertyInfo('<{$property->getName()}>', '<{$property->getType()}>', <{$property->getMinOccurs()}>, <{$property->getMaxOccurs()}>, '<{$property->getDbName()}>', '<{$model->getTableName()}>',
				<{$model->escapeBoolean($property->isPrimaryKey())}>, <{$model->escapeBoolean($property->isCascadeDelete())}>, <{$model->escapeBoolean($property->isTreeNode())}>, <{$model->escapeBoolean($property->isArray())}>, <{$model->escapeBoolean($property->isDocument())}>, <{$model->escapeString($property->getDefaultValue())}>, <{$model->escapeString($property->getConstraints())}>, <{$model->escapeBoolean($property->isLocalized())}>, <{$model->escapeBoolean($property->isIndexed())}>, <{$model->escapeBoolean($property->hasSpecificIndex())}>, <{$model->escapeString($property->getFromList())}>),
<{/foreach}>
		);
	}
	
	protected final function loadSerialisedProperties()
	{
		$this->m_serialisedproperties = array(
<{foreach from=$model->getSerializedProperties() item=property}>
			'<{$property->getName()}>' => new PropertyInfo('<{$property->getName()}>', '<{$property->getType()}>', <{$property->getMinOccurs()}>, <{$property->getMaxOccurs()}>, null, null,
				false, false, false, <{$model->escapeBoolean($property->isArray())}>, <{$model->escapeBoolean($property->isDocument())}>, <{$model->escapeString($property->getDefaultValue())}>, <{$model->escapeString($property->getConstraints())}>, <{$model->escapeBoolean($property->isLocalized())}>, <{$model->escapeBoolean($property->isIndexed())}>, <{$model->escapeBoolean($property->hasSpecificIndex())}>, <{$model->escapeString($property->getFromList())}>),
<{/foreach}>
		);	
	}
	
	protected final function loadFormProperties()
	{
<{if ($model->useDocumentEditor())}>	
		$this->m_formProperties = array();		
<{else}>
		$this->m_formProperties = array(
<{foreach from=$model->getFormProperties() item=property}>
			'<{$property->getName()}>' => new FormPropertyInfo('<{$property->getName()}>', '<{$property->getControlType()}>', '<{$property->getDisplay()}>', <{$model->escapeBoolean($property->isRequired())}>, '<{$property->getLabel()}>', '<{$property->getSerializedAttributes()}>'),
<{/foreach}>
		);		
<{/if}> 
	}
	
	protected final function loadInvertProperties()
	{
		// These properties are order by "inheritance order": the parent before the child.
		// This is required in f_persistentdocument_PersistentDocumentModel::findTreePropertiesNamesByType().
		$this->m_invertProperties = array(
<{foreach from=$model->getInverseProperties() item=property}>
			'<{$property->getName()}>' => new PropertyInfo('<{$property->getName()}>', '<{$property->getType()}>', <{$property->getMinOccurs()}>, <{$property->getMaxOccurs()}>, '<{$property->getRelationName()}>', '<{$property->getTableName()}>',
				<{$model->escapeBoolean($property->isPrimaryKey())}>, <{$model->escapeBoolean($property->isCascadeDelete())}>, <{$model->escapeBoolean($property->isTreeNode())}>, <{$model->escapeBoolean($property->isArray())}>, <{$model->escapeBoolean($property->isDocument())}>, <{$model->escapeString($property->getDefaultValue())}>, <{$model->escapeString($property->getConstraints())}>, <{$model->escapeBoolean($property->isLocalized())}>, <{$model->escapeBoolean($property->isIndexed())}>, <{$model->escapeBoolean($property->hasSpecificIndex())}>, <{$model->escapeString($property->getFromList())}>),
<{/foreach}>
		);
	}	
	
	protected final function loadChildrenProperties()
	{
		$this->m_childrenProperties = array(
<{foreach from=$model->getChildrenProperties() item=property}>
			'<{$property->getName()}>' => new ChildPropertyInfo('<{$property->getName()}>', '<{$property->getType()}>'),
<{/foreach}>
		);
	}
	
	/**
	 * @return String
	 */
	public final function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * @return String
	 */
	public final function getIcon()
	{
		return '<{$model->getIcon()}>';
	}

	/**
	 * @return String
	 */
	public final function getName()
	{
		return '<{$model->getFinalName()}>';
	}

	/**
	 * @return String
	 */
	public final function getBaseName()
	{
		return <{$model->escapeString($model->getBaseName())}>;
	}

	/**
	 * @return String
	 */
	public final function getLabel()
	{
		return '&modules.<{$model->getFinalModuleName()}>.document.<{$model->getFinalDocumentName()}>.document-name;';
	}

	/**
	 * @return String
	 */
	public final function getLabelKey()
	{
		return 'm.<{$model->getFinalModuleName()}>.document.<{$model->getFinalDocumentName()}>.document-name';
	}

	/**
	 * @return String
	 */
	public final function getModuleName()
	{
		return <{$model->escapeString($model->getFinalModuleName())}>;
	}

	/**
	 * @return String
	 */
	public final function getDocumentName()
	{
		return <{$model->escapeString($model->getFinalDocumentName())}>;
	}

	/**
	 * @return String
	 */
	public final function getTableName()
	{
		return <{$model->escapeString($model->getTableName())}>;
	}

	/**
	 * @return Boolean
	 */
	public final function isLocalized()
	{
		return <{$model->escapeBoolean($model->isInternationalized())}>;
	}

	/**
	 * @return Boolean
	 */
	public final function isLinkedToRootFolder()
	{
		return <{$model->escapeBoolean($model->isLinkedToRootModule())}>;
	}

	/**
	 * @return Boolean
	 */
	public final function hasURL()
	{
		return <{$model->escapeBoolean($model->hasURL())}>;
	}
	
	/**
	 * @return Boolean
	 */
	public final function useRewriteURL()
	{
		return <{$model->escapeBoolean($model->useRewriteURL())}> &&  <{$model->escapeBoolean($model->hasURL())}>;
	}
	
	/**
	 * @return Boolean
	 */
	public final function isIndexable()
	{
		return <{$model->escapeBoolean($model->hasURL())}> && <{$model->escapeBoolean($model->isIndexable())}> &&
		  (!defined('MOD_<{$model->getModuleName()|upper}>_<{$model->getDocumentName()|upper}>_DISABLE_INDEXATION') || !MOD_<{$model->getModuleName()|upper}>_<{$model->getDocumentName()|upper}>_DISABLE_INDEXATION);
	}
	
	/**
	 * @return Boolean
	 */
	public final function isBackofficeIndexable()
	{
		return <{$model->escapeBoolean($model->isBackofficeIndexable())}> &&
		  (!defined('MOD_<{$model->getModuleName()|upper}>_<{$model->getDocumentName()|upper}>_DISABLE_BACKOFFICE_INDEXATION') || !MOD_<{$model->getModuleName()|upper}>_<{$model->getDocumentName()|upper}>_DISABLE_BACKOFFICE_INDEXATION);
	}

	/**
	 * @return string[]
	 */
	public final function getAncestorModelNames()
	{
		return array(<{foreach from=$model->getAncestorModels() item=modelName}>'<{$modelName}>',<{/foreach}>);
	}

	/**
	 * @return String
	 */
	public final function getDefaultNewInstanceStatus()
	{
		return <{$model->escapeString($model->getDefaultStatus())}>;
	}

	/**
	 * Return if the document has 2 special properties (correctionid, correctionofid)
	 * @return Boolean
	 */
	public final function useCorrection()
	{
		return <{$model->escapeBoolean($model->hasCorrection())}> && CHANGE_USE_CORRECTION;
	}

	/**
	 * @return Boolean
	 */
	public final function hasWorkflow()
	{
		return <{$model->escapeBoolean($model->hasWorkflow())}> && CHANGE_USE_CORRECTION && CHANGE_USE_WORKFLOW &&
		  (!defined('MOD_<{$model->getModuleName()|upper}>_DISABLE_WORKFLOW') || !MOD_<{$model->getModuleName()|upper}>_DISABLE_WORKFLOW);
	}

	/**
	 * @return String
	 */
	public final function getWorkflowStartTask()
	{
		return $this->hasWorkflow() ? <{$model->escapeString($model->getWorkflowStartTask())}> : null;
	}

	/**
	 * @return array<String, String>
	 */
	public final function getWorkflowParameters()
	{
		return <{$model->getSerializedWorkflowParameters()}>;
	}

	/**
	 * @return Boolean
	 */
	public final function publishOnDayChange()
	{
		return <{$model->escapeBoolean($model->hasPublishOnDayChange())}>;
	}
	
	/**
	 * @return <{$model->getFinalServiceClassName()}>
	 */
	public function getDocumentService()
	{
		return <{$model->getFinalServiceClassName()}>::getInstance();
	}
}