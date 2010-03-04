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

		$this->m_properties = array(
<{foreach from=$model->getProperties() item=property}>
			'<{$property->getName()}>' => new PropertyInfo('<{$property->getName()}>', '<{$property->getType()}>', <{$property->getMinOccurs()}>, <{$property->getMaxOccurs()}>, '<{$property->getDbName()}>', '<{$model->getTableName()}>',
				<{$model->escapeBoolean($property->isPrimaryKey())}>, <{$model->escapeBoolean($property->isCascadeDelete())}>, <{$model->escapeBoolean($property->isTreeNode())}>, <{$model->escapeBoolean($property->isArray())}>, <{$model->escapeBoolean($property->isDocument())}>, <{$model->escapeString($property->getDefaultValue())}>, <{$model->escapeString($property->getConstraints())}>, <{$model->escapeBoolean($property->isLocalized())}>, <{$model->escapeBoolean($property->isIndexed())}>, <{$model->escapeBoolean($property->hasSpecificIndex())}>, <{$model->escapeString($property->getFromList())}>),
<{/foreach}>
		);
		
		$this->m_serialisedproperties = array(
<{foreach from=$model->getSerializedProperties() item=property}>
			'<{$property->getName()}>' => new PropertyInfo('<{$property->getName()}>', '<{$property->getType()}>', <{$property->getMinOccurs()}>, <{$property->getMaxOccurs()}>, null, null,
				false, false, false, <{$model->escapeBoolean($property->isArray())}>, <{$model->escapeBoolean($property->isDocument())}>, <{$model->escapeString($property->getDefaultValue())}>, <{$model->escapeString($property->getConstraints())}>, <{$model->escapeBoolean($property->isLocalized())}>, <{$model->escapeBoolean($property->isIndexed())}>, <{$model->escapeBoolean($property->hasSpecificIndex())}>, <{$model->escapeString($property->getFromList())}>),
<{/foreach}>
		);

		$this->m_preservedPropertiesNames = array(<{foreach from=$model->getPreservedPropertiesNames() item=name}>'<{$name}>' => true,<{/foreach}>);

		$this->m_formProperties = array(
<{foreach from=$model->getFormProperties() item=property}>
			'<{$property->getName()}>' => new FormPropertyInfo('<{$property->getName()}>', '<{$property->getControlType()}>', '<{$property->getDisplay()}>', <{$model->escapeBoolean($property->isRequired())}>, '<{$property->getLabel()}>', '<{$property->getSerializedAttributes()}>'),
<{/foreach}>
		);

		$this->m_childrenProperties = array(
<{foreach from=$model->getChildrenProperties() item=property}>
			'<{$property->getName()}>' => new ChildPropertyInfo('<{$property->getName()}>', '<{$property->getType()}>'),
<{/foreach}>
		);

		$this->m_statuses = array(<{foreach from=$model->getStatuses() item=status}>'<{$status}>',<{/foreach}>);

		// These properties are order by "inheritance order": the parent before the child.
		// This is required in f_persistentdocument_PersistentDocumentModel::findTreePropertiesNamesByType().
		$this->m_invertProperties = array(
<{foreach from=$model->getInverseProperties() item=property}>
			'<{$property->getName()}>' => new PropertyInfo('<{$property->getName()}>', '<{$property->getType()}>', <{$property->getMinOccurs()}>, <{$property->getMaxOccurs()}>, '<{$property->getRelationName()}>', '<{$property->getTableName()}>',
				<{$model->escapeBoolean($property->isPrimaryKey())}>, <{$model->escapeBoolean($property->isCascadeDelete())}>, <{$model->escapeBoolean($property->isTreeNode())}>, <{$model->escapeBoolean($property->isArray())}>, <{$model->escapeBoolean($property->isDocument())}>, <{$model->escapeString($property->getDefaultValue())}>, <{$model->escapeString($property->getConstraints())}>, <{$model->escapeBoolean($property->isLocalized())}>, <{$model->escapeBoolean($property->isIndexed())}>, <{$model->escapeBoolean($property->hasSpecificIndex())}>, <{$model->escapeString($property->getFromList())}>),
<{/foreach}>
		);

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
	 * @example modules_generic/folder
	 */
	public final function getName()
	{
		return '<{$model->getFinalName()}>';
	}

	/**
	 * @return String
	 * @example modules_generic/reference or null
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
	 * @example generic
	 */
	public final function getModuleName()
	{
		return <{$model->escapeString($model->getFinalModuleName())}>;
	}

	/**
	 * @return String
	 * @example folder
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
	 * @param String $modelName
	 * @return Boolean
	 */
	public final function isModelCompatible($modelName)
	{
		// TODO: enhance generating a switch case or an "OR" condition ?
		return array_search($modelName, array(<{foreach from=$model->getCompatibleModel() item=modelName}>'<{$modelName}>',<{/foreach}>)) !== false;
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
	
	/**
	 * @return String or null
	 */
	public function getEditModule()
	{
		return <{$model->escapeString($model->getEditModule())}>;
	}

	/**
	 * @return String
	 */
	public function __toString()
	{
		return $this->getName();
	}

	/**
	 * @deprecated use isLocalized()
	 * @return Boolean
	 */
	public final function isInternationalized()
	{
		return $this->isLocalized();
	}

	/**
	 * @deprecated For compatibility only
	 * @return String
	 */
	public final function getComponentClassName()
	{
		return <{$model->escapeString($model->getComponentClassName())}>;
	}

	/**
	 * @deprecated For compatibility only
	 * @return String
	 */
	public final function getClassName()
	{
		return $this->getComponentClassName();
	}

	/**
	 * @deprecated For compatibility only
	 * @return array<mixed>
	 */
	public final function getSynchronize()
	{
		return array();
	}
}