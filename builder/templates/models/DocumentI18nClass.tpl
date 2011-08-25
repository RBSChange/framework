/**
 * <{$model->getDocumentClassName()}>I18n
 * @package modules.<{$model->getModuleName()}>.persistentdocument
 * Class for internationalization of the document
 * @internal For framework internal usage only
 */
class <{$model->getDocumentClassName()}>I18n <{$model->getExtendI18nClassName()}> 
{
<{if !$model->hasParentModel()}>
	private $m_document_id;
	private $m_lang;
	private $isNew;
	
	protected $m_label;
	protected $modifiedProperties = array();
	protected $m_modified = false;
	
	/**
	 * @param Integer $document_id
	 * @param String $lang
	 * @param Boolean $isNew
	 */
	public function __construct($document_id, $lang, $isNew)
	{
		$this->m_document_id = $document_id;
		$this->m_lang = $lang;
		$this->isNew = $isNew;
	}	

	public final function setModifiedProperties($modifiedProperties = array())
	{
		$this->modifiedProperties = $modifiedProperties;
		$this->m_modified = count($modifiedProperties) > 0;
	}
	
	public final function getModifiedProperties()
	{
		return $this->modifiedProperties;
	}

	/**
	 * @return Integer
	 */
	public final function getId()
	{
		return $this->m_document_id;
	}

	/**
	 * @param Integer $document_id
	 */
	public final function setId($document_id)
	{
		$this->m_document_id = $document_id;
	}
	
	/**
	 * @return String
	 */
	public final function getLang()
	{
		return $this->m_lang;
	}

	/**
	 * @return Boolean
	 */
	public final function isModified()
	{
		return $this->m_modified;
	}

	public final function setIsPersisted()
	{
		$this->isNew = false;
		$this->setModifiedProperties();
	}
	
	/**
	 * @param integer $documentId
	 * @param f_persistentdocument_I18nPersistentDocument $sourceDocument
	 */
	public final function copyMutateSource($documentId, $sourceDocument)
	{
		$this->m_document_id = $documentId;
		$this->isNew = false;
	}

	/**
	 * @return Boolean
	 */
	public final function isNew()
	{
		return $this->isNew;
	}
	
	/**
	 * @param String $label
	 * @return void
	 */
	public final function setLabel($label)
	{
		if ($this->m_label !== $label)
		{
			$this->m_label = $label;
			$this->modifiedProperties['label'] = $this->m_label;
			$this->m_modified = true;
			return true;
		}
		return false;
	}

	/**
	 * @return String
	 */
	public final function getLabel()
	{
		return $this->m_label;
	}
	
	/**
	 * @param String $propertyName
	 * @return Boolean
	 */
	public final function isPropertyModified($propertyName)
	{
		return array_key_exists($propertyName, $this->modifiedProperties);
	}

	public final function getPreserveOldValues()
	{
		return $this->modifiedProperties;
	}		
<{/if}>	

<{if !$model->hasParentModel()}>
	/**
	 * @return void
	 */
	public function setDefaultValues()
	{
<{$model->getPhpDefaultI18nValues()}>
		$this->setModifiedProperties();
	}
<{elseif $model->getPhpDefaultI18nValues() != ''}>
	/**
	 * @return void
	 */
	public function setDefaultValues()
	{
		parent::setDefaultValues();
<{$model->getPhpDefaultI18nValues()}>
		$this->setModifiedProperties();
	}		
<{/if}>

<{if !$model->hasParentModel() || count($model->getI18nClassMember())}>
    /**
     * @internal For framework internal usage only
     * @param array<String, mixed> $propertyBag
     * @return void
     */
    public function setDocumentProperties($propertyBag)
	{
<{if !$model->hasParentModel()}>
		if (isset($propertyBag['label'])) {$this->m_label = $propertyBag['label'];}
<{else}>
		parent::setDocumentProperties($propertyBag);
<{/if}>
<{if $model->getInitI18nSerializedproperties()}>
		$this->m_s18sArray = null;
<{/if}>
<{if count($model->getI18nClassMember())}>
		foreach ($propertyBag as $propertyName => $propertyValue)
		{
			switch ($propertyName)
			{
<{foreach from=$model->getI18nClassMember() item=property}>
<{if $property->getType() == "Boolean"}>
                case '<{$property->getName()}>' : $this->m_<{$property->getName()}> = (bool)$propertyValue; break;
<{elseif $property->getType() == "Integer"}>
                case '<{$property->getName()}>' : $this->m_<{$property->getName()}> = (null === $propertyValue) ? null : intval($propertyValue); break;
<{elseif $property->getType() == "Double"}>
                case '<{$property->getName()}>' : $this->m_<{$property->getName()}> = (null === $propertyValue) ? null : floatval($propertyValue); break;
<{else}>
				case '<{$property->getName()}>' : $this->m_<{$property->getName()}> = $propertyValue; break;
<{/if}>
<{/foreach}>
			}
		}
<{/if}>
	}
<{/if}>
<{if !$model->hasParentModel() || count($model->getI18nClassMember())}>
	/**
	 * @internal For framework internal usage only
     * @return array<String, mixed>
     */
	public function getDocumentProperties()
	{
<{if !$model->hasParentModel()}>
		$propertyBag = array();
		$propertyBag['label'] = $this->m_label;
<{else}>
		$propertyBag = parent::getDocumentProperties();
<{/if}>
<{if $model->getInitI18nSerializedproperties()}>
		$this->serializeS18s();
<{/if}>
<{if count($model->getI18nClassMember())}>
<{foreach from=$model->getI18nClassMember() item=property}>
		$propertyBag['<{$property->getName()}>'] = $this->m_<{$property->getName()}>;
<{/foreach}>
<{/if}>
		return $propertyBag;
	}
<{/if}>			
<{foreach from=$model->getI18nClassMember() item=property}>
	private $m_<{$property->getName()}>;
<{/foreach}>
<{if $model->getInitI18nSerializedproperties()}>

	private $m_s18sArray;
	
	protected function serializeS18s()
	{
		if ($this->m_s18sArray !== null)
		{
			$this->setS18s(serialize($this->m_s18sArray));
			$this->m_s18sArray = null;
		}
	}
	
	protected function unserializeS18s()
	{
		$string = $this->getS18s();
		if ($string === null)
		{
			$this->m_s18sArray = array();
		}
		else
		{
			$this->m_s18sArray = unserialize($string);
		}
	}
	
	public function getS18sProperty($name)
	{
		if ($this->m_s18sArray === null) {$this->unserializeS18s();}
		if (isset($this->m_s18sArray[$name]))
		{
			return $this->m_s18sArray[$name];
		}
		return null;
	}
	
	public function setS18sProperty($name, $value)
	{
		if ($this->m_s18sArray === null) {$this->unserializeS18s();}
		$this->m_s18sArray[$name] = $value;
		$this->modifiedProperties['s18s'] = null;
		$this->m_modified = true;
	}
<{/if}>
<{foreach from=$model->getI18nClassMember() item=property}>

<{if $property->getType() == "Double"}>
	/**
	 * @param <{$property->getCommentaryType()}> $<{$property->getName()}>
	 * @return Boolean
	 */
	public final function set<{$property->getPhpName()}>($<{$property->getName()}>)
	{
		$<{$property->getName()}> = $<{$property->getName()}> !== null ? floatval($<{$property->getName()}>) : null;
		$modified = false;
		if ($this->m_<{$property->getName()}> === null || $<{$property->getName()}> === null)
		{
			$modified = ($this->m_<{$property->getName()}> !== $<{$property->getName()}>);
		}
		else
		{
			$modified = (abs($this->m_<{$property->getName()}> - $<{$property->getName()}>) > 0.0001);
		}

		if ($modified)
		{
			$this->m_<{$property->getName()}> = $<{$property->getName()}>;
			if (!array_key_exists('<{$property->getName()}>', $this->modifiedProperties))
			{
				$this->modifiedProperties['<{$property->getName()}>'] = null;
			}
			$this->m_modified = true;
		}

		return $modified;
	}
<{else}>
	/**
	 * @param <{$property->getCommentaryType()}> $<{$property->getName()}>
	 * @return Boolean
	 */
	public final function set<{$property->getPhpName()}>($<{$property->getName()}>)
	{
<{if $property->getName() == "s18s"}>
	$this->m_s18sArray = null;
<{/if}>
<{if $property->getType() == "DateTime"}>
		if ($<{$property->getName()}> instanceof date_Calendar)
		{
			$<{$property->getName()}> = date_Formatter::format($<{$property->getName()}>, date_Formatter::SQL_DATE_FORMAT);
		}
		else if (is_long($<{$property->getName()}>))
		{
			$<{$property->getName()}> = date(date_Formatter::SQL_DATE_FORMAT, $<{$property->getName()}>);
		}
<{/if}>
		if ($this->m_<{$property->getName()}> !== $<{$property->getName()}>)
		{
			if (!array_key_exists('<{$property->getName()}>', $this->modifiedProperties))
			{
<{if $property->getPreserveOldValue()}>
				$this->modifiedProperties['<{$property->getName()}>'] = $this->m_<{$property->getName()}>;
<{else}>
				$this->modifiedProperties['<{$property->getName()}>'] = null;
<{/if}>
			}
			$this->m_<{$property->getName()}> = $<{$property->getName()}>;
			$this->m_modified = true;
			return true;
		}
		return false;
	}
<{/if}>

	/**
	 * @return <{$property->getCommentaryType()}>
	 */
	public final function get<{$property->getPhpName()}>()
	{
		return $this->m_<{$property->getName()}>;
	}

<{if $property->getPreserveOldValue()}>
	/**
	 * @return <{$property->getCommentaryType()}>
	 */
	public final function get<{$property->getPhpName()}>OldValue()
	{
		return array_key_exists('<{$property->getName()}>', $this->modifiedProperties) ? $this->modifiedProperties['<{$property->getName()}>'] : null;
	}
<{/if}>
<{/foreach}>
}