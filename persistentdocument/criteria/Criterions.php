<?php
interface f_persistentdocument_criteria_Criterion
{
	
}

class f_persistentdocument_criteria_BetweenExpression implements f_persistentdocument_criteria_Criterion
{
	/** @var String */
	private $propertyName;
	/** @var Integer */
	private $min;
	/** @var Integer */
	private $max;

	/**
         * Default constructor
         * @param String $String
         * @param Integer $Integer
         * @param Integer $Integer
         */
	public function __construct($propertyName, $min, $max)
	{
		$this->propertyName = $propertyName;
		$this->min = $min;
		$this->max = $max;
	}

	/**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];	
		}
		return null;
	}
	
	/**
     * @return Integer
     */
	public function getMin()
	{
		return $this->min;
	}

	/**
         * @return Integer
         */
	public function getMax()
	{
		return $this->max;
	}
	
	/**
	 * @return string
	 */
	function __toString()
	{
		return "Between: $min, $max";
	}
}

class f_persistentdocument_criteria_EmptyExpression implements f_persistentdocument_criteria_Criterion
{
	private $propertyName;
	
	public function __construct($propertyName)
	{
		$this->propertyName = $propertyName;
	}
	
    /**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];	
		}
		return null;
	}
	
	function __toString()
	{
		return "Empty: ".$this->propertyName;
	}
}

class f_persistentdocument_criteria_IsTaggedExpression implements f_persistentdocument_criteria_Criterion
{
	function __toString()
	{
		return "IsTagged";
	}
}

class f_persistentdocument_criteria_HasTagExpression implements f_persistentdocument_criteria_Criterion
{
	private $tagName;
	
	public function __construct($tagName)
	{
		$this->tagName = $tagName;
	}
	
	/**
     * @return String
     */
	public function getTagName()
	{
		return $this->tagName;
	}
	
	function __toString()
	{
		return "Has tag: ".$this->tagName();
	}
}

class f_persistentdocument_criteria_InExpression implements f_persistentdocument_criteria_Criterion
{
	private $propertyName;
	private $values;
	private $not;
	
	/**
	 * @param String $propertyName
	 * @param array $values
	 */
	public function __construct($propertyName, $values, $not = false)
	{
		if (f_util_ArrayUtils::isNotEmpty($values) && f_util_ArrayUtils::firstElement($values) instanceof f_persistentdocument_PersistentDocument)
		{
			$this->propertyName = $propertyName.".id";
			$this->values = DocumentHelper::getIdArrayFromDocumentArray($values);
		}
		else
		{
			$this->propertyName = $propertyName;
			$this->values = $values;
		}
		$this->not = $not;
	}
	
	/**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab, 1));
			return $tab[0];
		}
		return null;
	}	

	/**
     * @return array
     */
	public function getValues()
	{
		return $this->values;
	}
	
	/**
	 * @return boolean
	 */
	public function getNot()
	{
		return $this->not;
	}
	
	function __toString()
	{
		$str = ($this->getNot()) ? "!" : "";
		$str .= "In ".$this->propertyName."(".join(",", $this->values).")";
		return $str;
	}
}

/**
 * Used for f_persistentdocument_criteria_LikeExpression
 */
class MatchMode
{
	private $code;
	private function __construct($code)
	{
		$this->code = $code;
	}

	public static function ANYWHERE()
	{
		return new MatchMode(0);
	}
	public static function END()
	{
		return new MatchMode(1);
	}
	public static function EXACT()
	{
		return new MatchMode(2);
	}
	public static function START()
	{
		return new MatchMode(3);
	}

	public function toMatchString($pattern)
	{
		switch ($this->code)
		{
			case 0:
				return '%'.$pattern.'%';
				break;
			case 1:
				return '%'.$pattern;
				break;
			case 2:
				return $pattern;
				break;
			case 3:
				return $pattern.'%';
				break;
		}
	}
}

class f_persistentdocument_criteria_LikeExpression implements f_persistentdocument_criteria_Criterion
{
	/** @var String */
	private $propertyName;
	/** @var mixed */
	private $value;
	/** @var MatchMode */
	private $matchMode;
	/** @var Boolean */
	private $ignoreCase;
	/** @var Boolean */
	private $not;
	
	/**
     * Default constructor
     * @param String $String
     * @param mixed $value
     * @param MatchMode $MatchMode by default, MatchMode is MatchMode::ANYWHERE()
     * @param Boolean $Boolean
     */
	public function __construct($propertyName, $value, $matchMode = null, $ignoreCase = false, $not = false)
	{
		$this->propertyName = $propertyName;
		$this->value = $value;
		if (is_null($matchMode))
		{
			$this->matchMode = MatchMode::ANYWHERE();
		}
		else
		{
			$this->matchMode = $matchMode;
		}
		$this->ignoreCase = $ignoreCase;
		$this->not = $not;
	}

	/**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];	
		}
		return null;
	}	

	/**
     * @return mixed
     */
	public function getValue()
	{
		return $this->value;
	}

	/**
     * @return MatchMode
     */
	public function getMatchMode()
	{
		return $this->matchMode;
	}

	/**
     * @return Boolean
     */
	public function getIgnoreCase()
	{
		return $this->ignoreCase;
	}
	
	/**
	 * @return boolean
	 */
	public function getNot()
	{
		return $this->not;
	}
	
	function __toString()
	{
		$str = ($this->not) ? "!" : "";
		$str .= ($this->ignoreCase) ? "i" : "";
		$str .= "Like ".$this->propertyName.", ".$this->matchMode->toMatchString($this->value);
		return $str;
	}
}

class f_persistentdocument_criteria_NotEmptyExpression implements f_persistentdocument_criteria_Criterion
{
	private $propertyName;
	
	public function __construct($propertyName)
	{
		$this->propertyName = $propertyName;
	}
	
	/**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
		
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];	
		}
		return null;
	}
	
	function __toString()
	{
		return "Not empty ".$this->propertyName;
	}
}

class f_persistentdocument_criteria_NotNullExpression implements f_persistentdocument_criteria_Criterion
{
	private $propertyName;
	
	public function __construct($propertyName)
	{
		$this->propertyName = $propertyName;
	}
	
	/**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];	
		}
		return null;
	}
	
	function __toString()
	{
		return "Not null ".$this->propertyName;
	}	
}

class f_persistentdocument_criteria_NullExpression implements f_persistentdocument_criteria_Criterion
{
	private $propertyName;
	
	public function __construct($propertyName)
	{
		$this->propertyName = $propertyName;
	}
	
	/**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];	
		}
		return null;
	}
	
	function __toString()
	{
		return "Is null ".$this->propertyName;
	}
}

class f_persistentdocument_criteria_PropertyExpression implements f_persistentdocument_criteria_Criterion
{
	private $propertyName;
	private $otherPropertyName;
	private $op;
	
	/**
	 * @param String $propertyName
	 * @param String $otherPropertyName
	 * @param String $op
	 */
	public function __construct($propertyName, $otherPropertyName, $op)
	{
		$this->propertyName = $propertyName;
		$this->otherPropertyName = $otherPropertyName;
		$this->op = $op;
	}
	
	/**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];	
		}
		return null;
	}
	
	/**
     * @return String
     */
    public function getOtherPropertyName()
    {
        return $this->otherPropertyName;
    }

    /**
     * @return String
     */
    public function getOp()
    {
        return $this->op;
    }
    
    function __toString()
	{
		return $this->propertyName." ".$this->op." ".$this->otherPropertyName;
	}
}

class f_persistentdocument_criteria_SimpleExpression implements f_persistentdocument_criteria_Criterion
{
	/** @var String */
	private $propertyName;
	/** @var mixed */
	private $value;
	/** @var String */
	private $op;
	/** @var Boolean */
	private $ignoreCase;

	/**
	 * @param String $propertyName
	 * @param mixed $value
	 * @param String $op
	 * @param Boolean $ignoreCase
	 */
	public function __construct($propertyName, $value, $op, $ignoreCase = false)
	{
		if ($value instanceof f_persistentdocument_PersistentDocument && ("=" == $op || "!=" == $op))
		{
			$this->propertyName = $propertyName.".id";
			$this->value = $value->getId();
		}
		else
		{
			$this->propertyName = $propertyName;
			$this->value = $value;	
		}
		$this->op = $op;
		$this->ignoreCase = $ignoreCase;
	}
	
	public function ignoreCase()
	{
		$this->ignoreCase = true;
	}
	
	/**
     * @return String
     */
	public function getPropertyName()
	{
		return $this->propertyName;
	}
	
	public function popPropertyName()
	{
		if (strpos($this->propertyName, '.') !== false)
		{
			$tab = explode('.', $this->propertyName);
			$this->propertyName = implode('.', array_slice($tab,1));
			return $tab[0];	
		}
		return null;
	}
	
	/**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
	 * @return String
	 */
	public function getOp()
	{
		return $this->op;
	}
	
	/**
     * @return Boolean
     */
    public function getIgnoreCase()
    {
        return $this->ignoreCase;
    }
    
 	function __toString()
	{
		$str = $this->propertyName." ";
		if ($this->ignoreCase)
		{
			$str .= "i";
		}
		$str .= $this->op." ".$this->value;
		return $str;
	}
}

class Example implements f_persistentdocument_criteria_Criterion
{
	private $likeEnabled = false;
	private $matchMode = null;
	private $ignoreCase = false;
	private $primaryOnly = false;
	private $excludeNulls = true;
	private $excludeEmptys = true;

	/**
	 * @var array
	 */
	private $excludedProperties = array();

	/**
	 * @var f_persistentdocument_PersistentDocument
	 */
	private $documentInstance;

	private function __construct($documentInstance)
	{
		$this->documentInstance = $documentInstance;
		$this->excludeProperty('id');
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $documentInstance
	 * @return Example
	 */
	public static function create($documentInstance)
	{
		return new Example($documentInstance);
	}

	/**
	 * @param MatchMode $matchMode
	 * @return Example
	 */
	public function enableLike($matchMode = null)
	{
		$this->likeEnabled = true;
		$this->matchMode = $matchMode;
		return $this;
	}

	/**
	 * exclude null or empty values
	 * @param boolean $exclude
	 * @return Example
	 */
	public function excludeZeroes($exclude = true)
	{
		$this->excludeNulls = $exclude;
		$this->excludeEmptys = $exclude;
		return $this;
	}

	/**
	 * exclude empty values
	 * @param boolean $exclude
	 * @return Example
	 */
	public function excludeEmpty($exclude = true)
	{
		$this->excludeEmptys = $exclude;
		return $this;
	}

	/**
	 * exclude null values
	 * @param boolean $exclude
	 * @return Example
	 */
	public function excludeNull($exclude = true)
	{
		$this->excludeEmptys = $exclude;
		return $this;
	}

	/**
	 * @return Example
	 */
	public function excludeNone()
	{
		$this->excludeNulls = false;
		$this->excludeEmptys = false;
		return $this;
	}

	/**
	 * @return Example
	 */
	public function primaryOnly()
	{
		$this->primaryOnly = true;
		return $this;
	}

	/**
	 * @return Example
	 */
	public function ignoreCase()
	{
		$this->ignoreCase = true;
		return $this;
	}

	/**
	 * @param String $propertyName
	 * @return Example
	 */
	public function excludeProperty($propertyName)
	{
		$this->excludedProperties[] = $propertyName;
		return $this;
	}

	/**
	 * @param Array $propertieNames
	 * @return Example
	 */
	public function excludeProperties($propertieNames)
	{
		$this->excludedProperties = array_merge($this->excludedProperties, $propertieNames);
		return $this;
	}

	/**
     * @return array ('scalars' => array(), 'documents' => array())
     */
	public function getDocumentInstanceProperties()
	{
		$props = array();
		$props['scalars'] = array();
		$props['documents'] = array();
		$model = $this->documentInstance->getPersistentModel();
		foreach ($this->documentInstance->getDocumentProperties() as $propertyName => $value)
		{
			
			if (in_array($propertyName, $this->excludedProperties))
			{
				continue;
			}
			if (!$this->primaryOnly || $model->getProperty($propertyName)->isPrimaryKey())
			{
				if ($this->excludeEmptys && empty($value) && !is_null($value))
				{
					continue;
				}
				if ($this->excludeNulls && is_null($value))
				{
					continue;
				}
				if ($model->getProperty($propertyName)->isDocument())
				{
					$props['documents'][$propertyName] = $value;
				}
				else
				{
					$props['scalars'][$propertyName] = $value;
				}
			}
		}
		return $props;
	}

	/**
     * @return MatchMode
     */
	public function getMatchMode()
	{
		return $this->matchMode;
	}

	/**
     * @return Boolean
     */
	public function getIgnoreCase()
	{
		return $this->ignoreCase;
	}

	/**
     * @return Boolean
     */
	public function getLikeEnabled()
	{
		return $this->likeEnabled;
	}

	/**
     * @return Boolean
     */
	public function getPrimaryOnly()
	{
		return $this->primaryOnly;
	}
}