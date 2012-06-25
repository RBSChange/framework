/**
 * <{$model->getDocumentClassName()}>model
 * @package modules.<{$model->getModuleName()}>.persistentdocument
 */
class <{$model->getDocumentClassName()}>model extends <{$model->getBaseModelClassName()}>
{
	/**
	 * Constructor of <{$model->getDocumentClassName()}>model
	 */
	protected function __construct()
	{
		parent::__construct();	
<{if (!$model->inject()) }>
<{if (count($model->getChildren()))}>
		$this->m_childrenNames = array(<{foreach from=$model->getChildren() item=children}>'<{$children->getName()}>',<{/foreach}>);
<{else}>
		$this->m_childrenNames = array();
<{/if}>
<{/if}>
<{if ($model->hasParentModel())}>
<{if (!$model->inject()) }>
		$this->m_parentName = '<{$model->getParentModelName()}>';
<{/if}>
<{if (count($model->getPreservedPropertiesNames()))}>
		$this->m_preservedPropertiesNames = array_merge($this->m_preservedPropertiesNames, array(<{foreach from=$model->getPreservedPropertiesNames() item=name}>'<{$name}>' => true,<{/foreach}>));
<{/if}>
<{else}>
		$this->m_preservedPropertiesNames = array(<{foreach from=$model->getPreservedPropertiesNames() item=name}>'<{$name}>' => true,<{/foreach}>);
<{/if}>
	}
<{if (count($model->getProperties()))}>

	protected function loadProperties()
	{
		parent::loadProperties();
<{foreach from=$model->getProperties() item=property}>
		$p = new PropertyInfo(<{$model->escapeString($property->getName())}>, <{$model->escapeString($property->getType())}>);
		$p->setDbTable(<{$model->escapeString($model->getTableName())}>)->setDbMapping(<{$model->escapeString($property->getDbName())}>)<{if ($property->getMinOccurs() != 0)}>->setMinOccurs(<{$property->getMinOccurs()}>)<{/if}>
<{if ($property->getMaxOccurs() != 1)}>->setMaxOccurs(<{$property->getMaxOccurs()}>)<{/if}>
<{if ($property->isCascadeDelete())}>->setCascadeDelete(true)<{/if}>
<{if ($property->isTreeNode())}>->setTreeNode(true)<{/if}>
<{if ($property->getDefaultValue() != null)}>->setDefaultValue(<{$model->escapeString($property->getDefaultValue())}>)<{/if}>
<{if ($property->getConstraintArray() != null)}>->setConstraints(<{$property->buildPhpConstraintArray()}>)<{/if}>
<{if ($property->isLocalized())}>->setLocalized(true)<{/if}>
<{if ($property->getIndexed() != 'none')}>->setIndexed(<{$model->escapeString($property->getIndexed())}>)<{/if}>
<{if ($property->getFromList() != null)}>->setFromList(<{$model->escapeString($property->getFromList())}>)<{/if}>;
		$this->m_properties[$p->getName()] = $p;
<{/foreach}>
	}
<{/if}>
<{if (count($model->getSerializedProperties()))}>

	protected function loadSerialisedProperties()
	{
		parent::loadSerialisedProperties();
<{foreach from=$model->getSerializedProperties() item=property}>
		$p = new PropertyInfo(<{$model->escapeString($property->getName())}>);
		$p->setType(<{$model->escapeString($property->getType())}>)<{if ($property->getMinOccurs() != 0)}>->setMinOccurs(<{$property->getMinOccurs()}>)<{/if}>
<{if ($property->getMaxOccurs() != 1)}>->setMaxOccurs(<{$property->getMaxOccurs()}>)<{/if}>
<{if ($property->getDefaultValue() != null)}>->setDefaultValue(<{$model->escapeString($property->getDefaultValue())}>)<{/if}>
<{if ($property->getConstraintArray() != null)}>->setConstraints(<{$property->buildPhpConstraintArray()}>)<{/if}>
<{if ($property->isLocalized())}>->setLocalized(true)<{/if}>
<{if ($property->getIndexed() != 'none')}>->setIndexed(<{$model->escapeString($property->getIndexed())}>)<{/if}>
<{if ($property->getFromList() != null)}>->setFromList(<{$model->escapeString($property->getFromList())}>)<{/if}>;
		$this->m_serialisedproperties[$p->getName()] = $p;
<{/foreach}>
	}
<{/if}>
<{if (count($model->getInverseProperties()))}>

	protected function loadInvertProperties()
	{
		parent::loadInvertProperties();
<{foreach from=$model->getInverseProperties() item=property}>
		$p = new PropertyInfo(<{$model->escapeString($property->getName())}>);
		$p->setDbTable(<{$model->escapeString($property->getTableName())}>)->setDbMapping(<{$model->escapeString($property->getRelationName())}>)<{if ($property->getType() != null)}>->setType(<{$model->escapeString($property->getType())}>)<{/if}>
<{if ($property->getMinOccurs() != 0)}>->setMinOccurs(<{$property->getMinOccurs()}>)<{/if}>
<{if ($property->getMaxOccurs() != 1)}>->setMaxOccurs(<{$property->getMaxOccurs()}>)<{/if}>
<{if ($property->isCascadeDelete())}>->setCascadeDelete(true)<{/if}>
<{if ($property->isTreeNode())}>->setTreeNode(true)<{/if}>
<{if ($property->getDefaultValue() != null)}>->setDefaultValue(<{$model->escapeString($property->getDefaultValue())}>)<{/if}>
<{if ($property->getConstraintArray() != null)}>->setConstraints(<{$property->buildPhpConstraintArray()}>)<{/if}>
<{if ($property->isLocalized())}>->setLocalized(true)<{/if}>
<{if ($property->getIndexed() != 'none')}>->setIndexed(<{$model->escapeString($property->getIndexed())}>)<{/if}>;
		$this->m_invertProperties[$p->getName()] = $p;
<{/foreach}>
	}	
<{/if}>
<{if (count($model->getChildrenProperties()))}>

	protected function loadChildrenProperties()
	{
		parent::loadChildrenProperties();
<{foreach from=$model->getChildrenProperties() item=property}>
			$p = new ChildPropertyInfo('<{$property->getName()}>', '<{$property->getType()}>');
			$this->m_childrenProperties[$p->getName()] = $p;
<{/foreach}>
	}
<{/if}>
	
	/**
	 * @return string
	 */
	public function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return '<{$model->getIcon()}>';
	}
<{if (!$model->inject()) }>

	/**
	 * @return string
	 */
	public function getName()
	{
		return '<{$model->getName()}>';
	}

	/**
	 * @return string
	 */
	public function getBaseName()
	{
		return <{$model->escapeString($model->getBaseName())}>;
	}

	/**
	 * @return string
	 */
	public function getModuleName()
	{
		return <{$model->escapeString($model->getModuleName())}>;
	}

	/**
	 * @return string
	 */
	public function getDocumentName()
	{
		return <{$model->escapeString($model->getDocumentName())}>;
	}
	
	/**
	 * @return string[]
	 */
	public function getAncestorModelNames()
	{
		return array(<{foreach from=$model->getAncestorModels() item=modelName}>'<{$modelName}>',<{/foreach}>);
	}
	
	/**
	 * @return <{$model->getServiceClassName()}>
	 */
	public function getDocumentService()
	{
		return <{$model->getServiceClassName()}>::getInstance();
	}

	/**
	 * @return string
	 */
	public function getLabelKey()
	{
		return 'm.<{$model->getModuleName()}>.document.<{$model->getDocumentName()}>.document-name';
	}
<{/if}>	
<{if (!$model->hasParentModel())}>

	/**
	 * @return string
	 */
	public final function getTableName()
	{
		return <{$model->escapeString($model->getTableName())}>;
	}
<{/if}>

	/**
	 * @return boolean
	 */
	public function isLocalized()
	{
		return <{$model->escapeBoolean($model->isLocalized())}>;
	}
	
	/**
	 * @return boolean
	 */
	public function hasURL()
	{
		return <{$model->escapeBoolean($model->hasURL())}>;
	}
	
	/**
	 * @return boolean
	 */
	public function useRewriteURL()
	{
<{if ($model->useRewriteURL() && $model->escapeBoolean($model->hasURL()))}>
		return true;
<{else}>
		return false;
<{/if}>	
	}
	
	/**
	 * @return boolean
	 */
	public function isIndexable()
	{
<{if ($model->hasURL() && $model->isIndexable())}>
		return (!defined('MOD_<{$model->getModuleName()|upper}>_<{$model->getDocumentName()|upper}>_DISABLE_INDEXATION') || !MOD_<{$model->getModuleName()|upper}>_<{$model->getDocumentName()|upper}>_DISABLE_INDEXATION);
<{else}>
		return false;
<{/if}>	
	}
	
	/**
	 * @return boolean
	 */
	public function isBackofficeIndexable()
	{
<{if ($model->isBackofficeIndexable())}>
		return (!defined('MOD_<{$model->getModuleName()|upper}>_<{$model->getDocumentName()|upper}>_DISABLE_BACKOFFICE_INDEXATION') || !MOD_<{$model->getModuleName()|upper}>_<{$model->getDocumentName()|upper}>_DISABLE_BACKOFFICE_INDEXATION);
<{else}>
		return false;
<{/if}>	
	}

	/**
	 * @return string
	 */
	public function getDefaultNewInstanceStatus()
	{
		return <{$model->escapeString($model->getDefaultStatus())}>;
	}

	/**
	 * Return if the document has 2 special properties (correctionid, correctionofid)
	 * @return boolean
	 */
	public function useCorrection()
	{
<{if ($model->hasCorrection())}>
		return CHANGE_USE_CORRECTION;
<{else}>
		return false;
<{/if}>	
	}

	/**
	 * @return boolean
	 */
	public function hasWorkflow()
	{
<{if ($model->hasWorkflow())}>
		return CHANGE_USE_CORRECTION && CHANGE_USE_WORKFLOW &&
		  (!defined('MOD_<{$model->getModuleName()|upper}>_DISABLE_WORKFLOW') || !MOD_<{$model->getModuleName()|upper}>_DISABLE_WORKFLOW);
<{else}>
		return false;
<{/if}>	
	}

	/**
	 * @return string
	 */
	public function getWorkflowStartTask()
	{
<{if ($model->hasWorkflow() && $model->getWorkflowStartTask())}>
		return $this->hasWorkflow() ? <{$model->escapeString($model->getWorkflowStartTask())}> : null;
<{else}>
		return null;
<{/if}>	
	}

	/**
	 * @return array<String, String>
	 */
	public function getWorkflowParameters()
	{
		return <{$model->getSerializedWorkflowParameters()}>;
	}

	/**
	 * @return Boolean
	 */
	public function usePublicationDates()
	{
		return <{$model->escapeBoolean($model->usePublicationDates())}>;
	}
}