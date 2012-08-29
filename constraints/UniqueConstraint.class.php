<?php
class change_UniqueConstraint extends Zend_Validate_Abstract
{
	const NOTUNIQUE = 'notUnique';
	
	/**
	 * @var array
	 */
	protected $_messageVariables = array(
		'propertyName' => '_propertyName'
	);

	/**
	 * @var string
	 */
	protected $_modelName;

	/**
	 * @var string
	 */
	protected $_propertyName;
	
	 /**
	 * @var integer
	 */
	protected $_documentId = 0;
	
 	/**
	 * @param array $params <modelName => modelName, propertyName => propertyName, [documentId => documentId]>
	 */   
	public function __construct($params = array())
	{
		$this->_messageTemplates = array(self::NOTUNIQUE => change_Constraints::getI18nConstraintValue(self::NOTUNIQUE));
	
		if (isset($params['modelName']))
		{
			$this->_modelName = $params['modelName'];
		}
		if (isset($params['propertyName']))
		{
			$this->_propertyName = $params['propertyName'];
		}
		if (isset($params['documentId']) && intval($params['documentId']) > 0)
		{
			$this->_documentId = intval($params['documentId']);
		}
	}
	
	/**
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		if (empty($this->_modelName) || empty($this->_propertyName))
		{
			throw new Exception('Invalid configuration');
		}
		if (f_util_StringUtils::isEmpty($value)) {$value = null;}
		$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->_modelName);
		$property = $model->getProperty($this->_propertyName);
		if ($property === null)
		{
			throw new Exception('Invalid property '. $this->_propertyName . ' for document '. $this->_modelName);
		}
		
		$this->_setValue($value);
		
		$ds = $model->getDocumentService();
		$query = $ds->createQuery()->setProjection(Projections::property('id', 'id'))->setMaxResults(1);
		
		if ($property->isDocument())
		{
			if ($value === null)
			{
				$query->add(Restrictions::isEmpty($property->getName()));	
			}
			elseif ($value instanceof f_persistentdocument_PersistentDocument) 
			{
				$query->add(Restrictions::eq($property->getName(), $value));
			}
			else
			{
				$query->add(Restrictions::eq($property->getName() . '.id', intval($value)));
			}
		}
		else
		{
			if ($value === null)
			{
				$query->add(Restrictions::isNull($property->getName()));
			}	
			else
			{
				$query->add(Restrictions::eq($property->getName(), $value));
			}
		}
		$row = $query->findUnique();
		if ($row !== null && intval($row['id']) != $this->_documentId)
		{
			Framework::fatal(__METHOD__ . ' ' . $this->_modelName . ' '. $this->_propertyName . ' '. $this->_documentId . ' -> ' . $value);
			$this->_error(self::NOTUNIQUE);
			return false;
		}
		return true;
	}	
}