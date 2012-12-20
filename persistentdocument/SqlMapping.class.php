<?php
/**
 * @deprecated
 */
class change_SqlMapping extends \Change\Db\SqlMapping
{
	
	private $i18nfieldNames;
	
	/**
	 * @deprecated
	 */
	public function getI18nSuffix()
	{
		return '_i18n';
	}
	
	/**
	 * @deprecated
	 */
	public function escapeParameterName($name)
	{
		return ':p' . $name;
	}
	
	/**
	 * @deprecated
	 */
	public function escapeName($name, $nameSpace = null, $alias = null)
	{
		$sql = ($nameSpace ? '`' . $nameSpace . '`.`' : '`') . $name . '`';
		return ($alias) ?  $sql . ' AS `' . $alias . '`' : $sql;
	}
		
	/**
	 * @deprecated
	 */
	public function getI18nFieldNames()
	{
		if ($this->i18nfieldNames === null)
		{
			$array = array('lang_vo');
			foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang)
			{
				$array[] = 'label_'.$lang;
			}
			$this->i18nfieldNames = $array;
		}
		return $this->i18nfieldNames;
	}
	
	
	/**
	 * @deprecated
	 */
	public function getDbNameByProperty($property, $localised = null)
	{
		$l = $localised === null ? $property->getLocalized() : $localised;
		$pn = strtolower($property->getName());
		switch ($pn)
		{
			case 'id':
				return 'document_id';
			case 'model':
				return 'document_model';
			case 'lang':
				return $l ? 'lang_i18n' : 'document_lang';
			case 'correctionofid':
				return 'document_correctionofid';
			case 'documentversion':
				return 'document_version';
			case 'metastring':
				return 'document_metas';
			case 's18s':
				return 'document_s18s';
			case 'label':
			case 'author':
			case 'authorid':
			case 'creationdate':
			case 'modificationdate':
			case 'publicationstatus':
			case 'modelversion':
			case 'startpublicationdate':
			case 'endpublicationdate':
			case 'correctionid':
				return $l ? 'document_' . $pn . '_i18n' : 'document_' . $pn ;
		}
		$l = $localised === null ? $property->getLocalized() : $localised;
		if ($property->getDbMapping()) {$pn = $property->getDbMapping();}
		return $l ? $pn . '_i18n' : $pn;
	}
	
	/**
	 * @deprecated
	 */
	public function getDbNameByModel($model, $localised = false)
	{
		if ($localised)
		{
			return $model->getTableName() . '_i18n';
		}
		else
		{
			return $model->getTableName();
		}
	}
}