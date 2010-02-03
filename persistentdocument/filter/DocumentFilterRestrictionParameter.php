<?php
/**
 * @author intportg
 * @package framework.persistentdocument.filter
 */
class f_persistentdocument_DocumentFilterRestrictionParameter extends f_persistentdocument_DocumentFilterParameter
{
	/**
	 * @var Array
	 */
	private $restrictionType;
	
	/**
	 * @param BeanPropertyInfo $propertyInfo
	 * @param String $restriction
	 */
	protected function __construct($propertyInfo = null, $restriction = null)
	{
		if ($propertyInfo !== null)
		{
			$this->propertyInfoLocked = true;
			$this->propertyInfo = $propertyInfo;
			$this->propertyName = $propertyInfo->getName();
		}
		if ($restriction !== null)
		{
			$this->restrictionLocked = true;
			$this->restriction = $restriction;
		}
	}
	
	/**
	 * @param BeanPropertyInfo $propertyInfo
	 * @param String $restriction
	 * @return f_persistentdocument_DocumentFilterRestrictionParameter
	 */
	static function getNewInstance($propertyInfo = null, $restriction = null)
	{
		$param = new f_persistentdocument_DocumentFilterRestrictionParameter($propertyInfo, $restriction);
		$param->restrictionType = 'Restrictions';
		return $param;
	}
	
	/**
	 * @param BeanPropertyInfo $propertyInfo
	 * @param String $restriction
	 * @return f_persistentdocument_DocumentFilterRestrictionParameter
	 */
	static function getNewHavingInstance($propertyInfo = null, $restriction = null)
	{
		$param = new f_persistentdocument_DocumentFilterRestrictionParameter($propertyInfo, $restriction);
		$param->restrictionType = 'HavingRestrictions';
		return $param;
	}

	/**
	 * @var Boolean
	 */
	private $propertyInfoLocked = false;

	/**
	 * @var BeanPropertyName
	 */
	private $propertyName;

	/**
	 * @var BeanPropertyInfo
	 */
	private $propertyInfo;
	
	/**
	 * @return string
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	/**
	 * @param BeanPropertyInfo $value
	 */
	public function setPropertyInfo($value)
	{
		if ($this->propertyInfoLocked)
		{
			throw new Exception('Property info locked!');
		}
		$this->propertyInfo = $value;
	}
	
	/**
	 * @return BeanPropertyInfo
	 */
	public function getPropertyInfo()
	{
		return $this->propertyInfo;
	}
	
	/**
	 * modules_<moduleName>/<documentName>.<propertyName>
	 * @param String $name
	 */
	public function setPropertyName($name)
	{
		if (isset($this->allowedPropertyInfos[$name]))
		{
			$this->propertyName = $name;
			$this->setPropertyInfo($this->allowedPropertyInfos[$name]);
		}
		else
		{
			Framework::fatal(var_export($this, true));
			throw new Exception('Invalid property name :'.$name);
		}
	}

	private $allowedPropertyInfos = array();
	
	/**
	 * modules_<moduleName>/<documentName>.<propertyName>
	 * @param String[] $names
	 */
	public function setAllowedPropertyNames($names)
	{
		$dfs = f_persistentdocument_DocumentFilterService::getInstance();
		$this->allowedPropertyInfos = array();
		foreach ($names as $name)
		{
			$this->allowedPropertyInfos[$name] = $dfs->getPropertyInfoByName($name);
		}
	}
	
	/**
	 * @param String $name
	 * @param BeanPropertyInfo $beanPropertyInfo
	 * @return f_persistentdocument_DocumentFilterRestrictionParameter $this
	 */
	public function addAllowedProperty($name, $beanPropertyInfo)
	{
		$this->allowedPropertyInfos[$name] = $beanPropertyInfo;
		return $this;
	}
	
	/**
	 * @return BeanPropertyInfo[]
	 */
	public function getAllowedPropertyInfos()
	{
		// TODO: if allowedPropertyNames not set, use the filter document model.
		return $this->allowedPropertyInfos;
	}
	
	/**
	 * @param string $name
	 * @return BeanPropertyInfo
	 */
	public function getPropertyInfoByName($name)
	{
		if ($this->getPropertyName() === $name)
		{
			return $this->getPropertyInfo();
		}
		if (isset($this->allowedPropertyInfos[$name]))
		{
			return $this->allowedPropertyInfos[$name];
		}
		throw new Exception('Invalid property name :' . $name);
	}
	
	/**
	 * @var Boolean
	 */
	private $restrictionLocked = false;
	
	/**
	 * @var String
	 */
	private $restriction;
	
	/**
	 * @param String $value
	 */
	public function setRestriction($value)
	{
		if ($this->restrictionLocked)
		{
			throw new Exception('Restriction locked!');
		}
		$this->restriction = $value;
	}
	
	/**
	 * @return String
	 */
	public function getRestriction()
	{
		return $this->restriction;
	}
	
	/**
	 * @var Array<String => String[]>
	 * @example array(
	 * 		'modules_users/backenduser.email' => array('like', 'ilike'),
	 *  	'modules_users/backenduser.login' => array('like'))
	 */
	private $allowedRestrictions = array();
	
	/**
	 * @param String $name
	 * @param String[] $restrictions
	 */
	public function setAllowedRestrictions($name, $restrictions)
	{
		$this->allowedRestrictions[$name] = $restrictions;
	}
	
	/**
	 * @param String $name
	 * @return String[]
	 */
	public function getAllowedRestrictions($name)
	{
		if (isset($this->allowedRestrictions[$name]))
		{
			return $this->allowedRestrictions[$name]; 
		}
		else 
		{
			$propertyInfo = $this->getPropertyInfoByName($name);
			switch ($propertyInfo->getType())
			{
				case 'String' : 
					return array('eq', 'ieq', 'like', 'ilike', 'notLike');
					break;
				
				case 'XHTMLFragment' : 
				case 'LongString' :
					return array('like', 'ilike', 'notLike');
					break;
					
				case 'Boolean' :
					return array('eq', 'ne'); 
					break;
					
				case 'Integer' : 
				case 'Double' : 
					return array('eq', 'ge', 'gt', 'le', 'lt', 'ne');
					// in/notIn ?
					break;
					
				case 'DateTime' : 
					return array('ge', 'gt', 'le', 'lt');
					break;
								
				case 'Document' :
					 return array('in', 'notin');
			}
			return array();
		}		
	}
	
	/**
	 * @var f_persistentdocument_DocumentFilterValueParameter
	 */
	private $parameter;
	
	/**
	 * @return f_persistentdocument_DocumentFilterValueParameter $value
	 */
	public function getParameter()
	{
		if ($this->parameter === null && $this->propertyInfo instanceof BeanPropertyInfo)
		{
			$this->parameter = new f_persistentdocument_DocumentFilterValueParameter($this->propertyInfo);
		}
		return $this->parameter;
	}
	
	/**
	 * @param Mixed $value
	 */
	public function setParameterValue($value)
	{
		$this->getParameter()->setValue($value);
	}
	
	/**
	 * @return f_persistentdocument_criteria_Criterion
	 */
	public function getValueForQuery()
	{
		$this->validate(true);
		$value = $this->parameter->getValueForQuery();
		
		//TODO For compatibility
		if (is_array($value))
		{
			switch ($this->restriction) {
				case 'eq':
					$this->restriction = 'in';
					break;
				case 'ne':
					$this->restriction = 'notin';
					break;
			}
		}
		$arguments = array($this->propertyInfo->getName(), $value);
		return f_util_ClassUtils::callMethodArgs($this->restrictionType, $this->restriction, $arguments);
	}
	
	/**
	 * @return String
	 */
	public function getValueAsText()
	{
		$propertyLabel = $this->getPropertyLabelAsText();
		$restriction = $this->getRestrictionLabelAsText();		
		$value = $this->getValueLabelAsText();		
		return $propertyLabel . ' ' . $restriction . ' ' . $value;
	}

	/**
	 * @return Array
	 */
	public function getValueForXul()
	{
		$values = array();
				
		$pattern = '';
		if (!$this->propertyInfoLocked)
		{
			$values['property'] = $this->propertyName;
			$pattern .= '<cFilterParameterProperty>' . $this->getPropertyLabelAsText() . '</cFilterParameterProperty>';
		}
		else 
		{
			$pattern .= $this->getPropertyLabelAsText(); 
		}
		$pattern .= ' ';
		
		if (!$this->restrictionLocked)
		{
			$values['restriction'] = $this->restriction;
			$pattern .= '<cFilterParameterRestriction>' . $this->getRestrictionLabelAsText() . '</cFilterParameterRestriction>';
		}
		else 
		{
			$pattern .= $this->getRestrictionLabelAsText(); 
		}		
		$pattern .= ' ';
		
		$valueParameter = $this->getParameter();
		if ($valueParameter && ($valueParameter->getValue() !== null))
		{
			$value = $valueParameter->getValueForXul();
			$values['value'] = $value['value'];
			$pattern .= $value['pattern'];
		}
		else
		{
			$pattern .= '<cFilterParameterValue>...</cFilterParameterValue>';
		}		
		$values['pattern'] = $pattern;
		
		return $values;
	}	
	
	/**
	 * @return String
	 */
	public function getValueForJson()
	{
		$propertyName = null;
		if (!$this->propertyInfoLocked && $this->propertyName !== null)
		{
			$propertyName = $this->propertyName;
		}
		
		$restriction = null;
		if (!$this->restrictionLocked && $this->restriction !== null)
		{
			$restriction = $this->restriction;
		}
		
		$parameterValue = null;
		$parameter = $this->getParameter();
		if ($parameter !== null && $parameter->getValue() !== null)
		{
			$parameterValue = $parameter->getValue();
		}
		
		return array($propertyName, $restriction, $parameterValue);
	}
	
	/**
	 * @param Boolean $throwException
	 * @return Boolean
	 * @throws ValidationException
	 */
	public function validate($throwException)
	{
		if ($this->propertyInfo === null)
		{
			if ($throwException)
			{
				throw new ValidationException('Invalid parameter: no propertyInfo');
			}
			return false;
		}
		else if ($this->restriction === null)
		{
			if ($throwException)
			{
				throw new ValidationException('Invalid parameter: no restriction');
			}
			return false;
		}
		else if ($this->parameter === null)
		{
			if ($throwException)
			{
				throw new ValidationException('Invalid parameter: no parameter');
			}
			return false;
		}
		else if (!$this->parameter->validate($throwException))
		{
			return false;
		}
		return true;
	}
	
	/**
	 * @return String
	 */
	private function getPropertyLabelAsText()
	{
		$propertyLabel = '...';
		if ($this->propertyInfo instanceof BeanPropertyInfo)
		{
			$propertyLabel = f_Locale::translateUI($this->propertyInfo->getLabelKey());
		}
		return $propertyLabel;
	}
	
	/**
	 * @return String
	 */
	private function getRestrictionLabelAsText()
	{
		$restriction = '...';
		if ($this->restriction !== null)
		{
			$restriction = f_persistentdocument_DocumentFilterService::getInstance()->getRestrictionAsText($this->restriction);
		}
		return $restriction;
	}
	
	/**
	 * @return String
	 */
	private function getValueLabelAsText()
	{
		$value = '...';
		if ($this->parameter instanceof f_persistentdocument_DocumentFilterParameter)
		{
			$value = $this->parameter->getValueAsText();
		}
		return $value;
	}
}
