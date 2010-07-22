<?php
/**
 * @package framework.tree.parser
 * Base class for all the tree parsers.
 */
abstract class tree_parser_TreeParser
{

    // Ordering constants :
    const ORDER_COLUMN = 'column';
    const ORDER_DIRECTION = 'direction';
    const ORDER_METHOD = 'method';

    const ORDER_BY_ID = 'id';
    const ORDER_BY_PARENTID = 'parentid';
    const ORDER_BY_LANG = 'lang';
    const ORDER_BY_LABEL = 'label';
    const ORDER_BY_TYPE = 'type';
    const ORDER_BY_CHILDCOUNT = 'childcount';

    const ORDER_ASC = 'asc';
	const ORDER_DESC = 'desc';
	const ORDER_RAND = 'rand';

	// Filtering constants :
	const FILTER_BY = 'by';
    const FILTER_VALUE = 'value';
    const FILTER_METHOD = 'method';

    const FILTER_BY_ID = 'id';
    const FILTER_BY_LANG = 'lang';
    const FILTER_BY_TYPE = 'type';
    const FILTER_BY_LABEL = 'label';
    const FILTER_BY_AUTHOR = 'author';
    const FILTER_BY_STATUS = 'status';

    // Tree types :
    const TYPE_TREE = 'wtree';
    const TYPE_MULTI_TREE = 'wmultitree';
    const TYPE_LIST = 'wlist';
    const TYPE_MULTI_LIST = 'wmultilist';

    protected $treeId = null;
    protected $treeType = null;

    protected $rootComponentId = null;
    protected $relationType = f_persistentdocument_PersistentDocumentArray::RELATION_TREE;

    protected $langsPerDocumentTypes = array();
    protected $statusesPerDocumentTypes = array();
    protected $childrenTypes = array();


    protected $moduleName;
    protected $parserName;

    protected $depth = 0;

    protected $length = 0;
    protected $offset = 0;

    protected $ignoreLengthBeyondDepth = 0;

    protected $ignoreChildren = false;

    protected $publicatedOnly = false;

    protected $publishableOnly = false;

    protected $orderColumn = null;
    protected $orderDirection = null;
    protected $orderMethod = null;

    protected $filter = null;
    protected $filterMethod = null;

	/**
	* @var Boolean
	*/
    protected $useTopic = false;

	/**
    * @var array
    */
    protected $topicPaths = array();
    
	/**
    * @var array
    */
    protected $viewCols = null;

    /**
     * This method should be used to modify the parser properties.
     *
     */
    public function initialize()
    {

    }


    /**
	 * Create an instance of a parser from the given name. If the name does not
	 * correspond to a valid parser, ie if the class cannot be found, an exception
	 * is thrown.
	 *
	 * @param string $parserName Name of the parser.
	 * @param integer $rootId ID of the root component from which the parsing
	 * should begin.
	 * @return tree_parser_TreeParser
	 */
    public static function getInstance($parserName = "Xml", $moduleName = null, $rootId = null, $treeId = null, $treeType = null)
    {
        if (Framework::isDebugEnabled())
        {
            Framework::debug(__METHOD__ ."($parserName, $moduleName, $rootId, $treeId, $treeType)");
        }
        $parserName = ucfirst($parserName);
        $className = 'tree_parser_'. $parserName .'TreeParser';
        
        if (null !== $moduleName)
        {
            $specClassName = $moduleName . '_' .$parserName . 'TreeParser';;
            if (f_util_ClassUtils::classExists($specClassName))
            {
                $className = $specClassName;
            }
        } 		
        $parser = new $className();

        $parser->moduleName = $moduleName;
        $parser->parserName = $parserName;

        $parser->setRootComponentId($rootId);
       	$parser->setTreeId($treeId);
        $parser->setTreeType($treeType);

        $parser->initialize();

        return $parser;
    }


 	abstract protected function getTree($documentId = null, $offset = 0, $order = null, $filter = null);


    /**
	 * Sets the root component ID.
	 *
	 * @param integer $rootComponentId ID of the root component from which the parsing
	 * should begin.
	 * @return tree_parser_TreeParser
	 *
	 */
    public final function setRootComponentId($rootComponentId)
    {
        $this->rootComponentId = $rootComponentId;

        return $this;
    }


    /**
	 * Gets the root component ID.
	 *
	 * @return ID of the root component from which the parsing
	 * should begin.
	 */
    public final function getRootComponentId()
    {
        return $this->rootComponentId;
    }


    /**
	 * Sets the tree ID (ID of the UI widget).
	 *
	 * @param string $treeId ID of the UI widget used to display the tree.
	 * @return tree_parser_TreeParser
	 *
	 */
    public final function setTreeId($treeId)
    {
        $this->treeId = $treeId;
        return $this;
    }


    /**
	 * Gets the tree ID (ID of the UI widget).
	 *
	 * @return string
	 */
    public final function getTreeId()
    {
        return $this->treeId;
    }


    /**
	 * Sets the tree type (type of the UI widget : wtree, wlist, etc.).
	 *
	 * @param string $treeType Type of the UI widget used to display the tree.
	 * @return tree_parser_TreeParser
	 *
	 */
    public final function setTreeType($treeType)
    {
        $this->treeType = $treeType;
        return $this;
    }


    /**
	 * Gets the tree type (type of the UI widget : wtree, wlist, etc.).
	 *
	 * @return string
	 */
    public final function getTreeType()
    {
        return $this->treeType;
    }


    /**
	 * Sets the depth of the parsing process.
	 *
	 * @param integer $depth Depth.
	 * @return tree_parser_TreeParser
	 */
    public final function setDepth($depth)
    {
        if (!is_numeric($depth))
        {
            throw new ClassException("depth_is_not_numeric");
        }
        $this->depth = $depth;

        return $this;
    }


    /**
	 * Gets the depth of the parsing process.
	 */
    public final function getDepth()
    {
        return $this->depth;
    }


    /**
	 * Sets the length of the parsing process.
	 *
	 * @param integer $length Length.
	 * @return tree_parser_TreeParser
	 */
    public final function setLength($length)
    {
        if (!is_numeric($length))
        {
            throw new ClassException("length_is_not_numeric");
        }
        $this->length = $length;

        return $this;
    }


    /**
	 * Gets the length of the parsing process.
	 */
    public final function getLength()
    {
        return $this->length;
    }


    /**
     * Sets the depth beyond which the length constraint is ignored.
     *
     * @param integer $depth
	 * @return tree_parser_TreeParser
     */
    public final function setIgnoreLengthBeyondDepth($depth)
    {
        if (!is_numeric($depth))
        {
            throw new ClassException("depth_is_not_numeric");
        }
        $this->ignoreLengthBeyondDepth = $depth;

        return $this;
    }


    /**
     * Gets the depth beyond which the length constraint is ignored.
     *
     * @return integer
     */
    public final function getIgnoreLengthBeyondDepth()
    {
        return $this->ignoreLengthBeyondDepth;
    }


    /**
	 * Gets the available length constraint of the parsing process for the given level.
	 *
	 * @param integer $level
	 * @return integer
	 */
    public final function getAvailableLength($level)
    {
        if ($this->getIgnoreLengthBeyondDepth() && ($level <= $this->getIgnoreLengthBeyondDepth()))
        {
            return $this->getLength();
        }

        return 0;
    }


    /**
     * Ignore children or not...
     *
     * @param boolean $ignoreChildren
	 * @return tree_parser_TreeParser
     */
    public function setIgnoreChildren($ignoreChildren)
    {
        $this->ignoreChildren = ($ignoreChildren == true);

        return $this;
    }


    /**
     * Ignore children or not...
     *
     * @return boolean
     */
    public function getIgnoreChildren()
    {
        return $this->ignoreChildren;
    }


    /**
     * Display publicated documents only or not...
     *
     * @param boolean $publicatedOnly
	 * @return tree_parser_TreeParser
     */
    public function setDisplayPublicatedOnly($publicatedOnly)
    {
        $this->publicatedOnly = ($publicatedOnly == true);

        return $this;
    }


    /**
     * Display publicated documents only or not...
     *
     * @return boolean
     */
    public function getDisplayPublicatedOnly()
    {
        return $this->publicatedOnly;
    }


    /**
     * Display publishable documents only or not...
     *
     * @param boolean $publishableOnly
	 * @return tree_parser_TreeParser
     */
    public function setDisplayPublishableOnly($publishableOnly)
    {
        $this->publishableOnly = ($publishableOnly == true);

        return $this;
    }


    /**
     * Display publishable documents only or not...
     *
     * @return boolean
     */
    public function getDisplayPublishableOnly()
    {
        return $this->publishableOnly;
    }


    /**
	 * Sets the offset (page index) of the parsing process (used when Length is set).
	 *
	 * @param integer $offset Offset.
	 * @return tree_parser_TreeParser
	 */
    public final function setOffset($offset)
    {
        if (!is_numeric($offset))
        {
            throw new ClassException("offset_is_not_numeric");
        }
        $this->offset = $offset;

        return $this;
    }


    /**
	 * Gets the offset (page index) of the parsing process (used when Length is set).
	 */
    public final function getOffset()
    {
        return $this->offset;
    }


    /**
	 * Sets the order data for the parsing process.
	 *
	 * @param mixed $order Order.
	 * @return tree_parser_TreeParser
	 */
    public final function setOrder($order)
    {
    	if (is_null($order))
    	{
    		 $this->setOrderColumn(null);
    		 $this->setOrderDirection(null);
    		 $this->setOrderMethod(null);
    		 return;
    	}
        else if (!is_array($order))
        {
            $order = trim(strtolower($order));

            if (strpos($order, '/') !== false)
            {
                list($columnValue, $directionValue) = explode('/', $order);
                $order = array(
                    self::ORDER_COLUMN => $columnValue,
                    self::ORDER_DIRECTION => $directionValue
                );
            }
            else
            {
                $order = array(
                    self::ORDER_COLUMN => $order
                );
            }
        }

        if (isset($order[self::ORDER_COLUMN]) && $order[self::ORDER_COLUMN])
        {
            $this->setOrderColumn($order[self::ORDER_COLUMN]);
        }
        else
        {
            $this->setOrderColumn(null);
        }

        if (isset($order[self::ORDER_METHOD]))
        {
            $this->setOrderMethod($order[self::ORDER_METHOD]);
        }

        if (isset($order[self::ORDER_DIRECTION]) && $order[self::ORDER_DIRECTION])
        {
            switch ($order[self::ORDER_DIRECTION])
            {
                case self::ORDER_ASC:
                case self::ORDER_DESC:
                case self::ORDER_RAND:
                    $this->setOrderDirection($order[self::ORDER_DIRECTION]);
                    break;

                default:
                    $this->setOrderDirection(self::ORDER_ASC);
                    break;
            }
        }
        else
        {
            $this->setOrderDirection(self::ORDER_ASC);
        }
    }


    /**
	 * Gets the order data for the parsing process.
	 *
	 * @return array
	 */
    public final function getOrder()
    {
        return array(
            self::ORDER_COLUMN => $this->getOrderColumn(),
            self::ORDER_DIRECTION => $this->getOrderDirection(),
            self::ORDER_METHOD => $this->getOrderMethod()
        );
    }


    /**
     * Returns true if ordering properties are available.
     *
     * @return boolean
     */
    public final function hasOrdering()
    {
        return (!is_null($this->getOrderColumn()) || !is_null($this->getOrderMethod()));
    }


    /**
     * Returns the defined order column.
     *
     * @return string
     */
    public final function getOrderColumn()
    {
        return $this->orderColumn;
    }


    /**
     * Returns the defined order direction.
     *
     * @return string
     */
    public final function getOrderDirection()
    {
        return $this->orderDirection;
    }


    /**
     * Returns the defined order method.
     *
     * @return string
     */
    public final function getOrderMethod()
    {
        return $this->orderMethod;
    }


    /**
     * Sets the order column.
     *
     * @param string $column
     * @return tree_parser_TreeParser
     */
    public final function setOrderColumn($column)
    {
        $this->orderColumn = $column;

        return $this;
    }


    /**
     * Sets the order direction.
     *
     * @param string $direction
     * @return tree_parser_TreeParser
     */
    public final function setOrderDirection($direction)
    {
        $this->orderDirection = $direction;

        return $this;
    }


    /**
     * Sets the order method.
     *
     * @param string $method
     * @return tree_parser_TreeParser
     */
    public final function setOrderMethod($method)
    {
        $this->orderMethod = $method;

        return $this;
    }

    /**
     * Callback method used for ordering trees.
     *
     * @param f_persistentdocument_PersistentDocumentImpl $a
     * @param f_persistentdocument_PersistentDocumentImpl $b
     * @return integer (-1 if (a < b) ; 0 if (a = b) ; 1 if (a > b))
     */
    public function sortTreeNodes($a, $b)
    {
        $sort = 0;
        $value_a = null;
        $value_b = null;

        // Skip all processes if ORDER_RAND is used :
        if ($this->getOrderDirection() != self::ORDER_RAND)
        {
            // No order method - use the given column string :
            if (is_null($this->getOrderMethod()))
            {
                // Speed up common orderings :
                switch ($this->getOrderColumn())
                {
                    case self::ORDER_BY_ID:
                        $value_a = $a->getId();
                        $value_b = $b->getId();
						break;		
				    case self::ORDER_BY_PARENTID:
						$_a = $a;
						if ($_a instanceof f_persistentdocument_PersistentDocumentImpl)
						{
							$_a = TreeService::getInstance()->getInstanceByDocument($_a);
						}
						if ($_a && $_a->getParent())
						{
							$_a = $_a->getParent();
						}
						$value_a = $_a->getId();
						$_b = $b;
						if ($_b instanceof f_persistentdocument_PersistentDocumentImpl)
						{
							$_b = TreeService::getInstance()->getInstanceByDocument($_b);
						}
						if ($_b && $_b->getParent())
						{
							$_b = $_b->getParent();
						}
						$value_b = $_b->getId();
                        break;
                    case self::ORDER_BY_LANG:
                        $value_a = $a->getLang();
                        $value_b = $b->getLang();
                        break;

                    case self::ORDER_BY_LABEL:
                        $value_a = $a->getLabel();
                        $value_b = $b->getLabel();
                        break;

                    case self::ORDER_BY_TYPE:
                        $value_a = $a->getDocumentModelName();
                        $value_b = $b->getDocumentModelName();
                        break;

                    case self::ORDER_BY_CHILDCOUNT:
                        $treeNode_a = TreeService::getInstance()->getInstanceByDocument($a);
                        if ($treeNode_a)
                        {
                            $value_a = $treeNode_a->getChildCount();
                        }
                        $treeNode_b = TreeService::getInstance()->getInstanceByDocument($b);
                        if ($treeNode_b)
                        {
                            $value_b = $treeNode_b->getChildCount();
                        }
                        break;

                    default:
                        // Try to use any "get<ColumnName>()" method :
                        if (f_util_ClassUtils::methodExists($a, 'get' . ucfirst($this->getOrderColumn())))
                        {
                            $value_a = f_util_ClassUtils::callMethodOn($a, 'get' . ucfirst($this->getOrderColumn()));
                        }
                        if (f_util_ClassUtils::methodExists($b, 'get' . ucfirst($this->getOrderColumn())))
                        {
                            $value_b = f_util_ClassUtils::callMethodOn($b, 'get' . ucfirst($this->getOrderColumn()));
                        }
                        break;
                }

                // Common sort evaluation (values comparison) :
                if (!is_null($value_a) && !is_null($value_b))
                {
                    if (is_numeric($value_a) && is_numeric($value_b))
                    {
                        if ($value_a < $value_b)
                        {
                            $sort = -1;
                        }
                        else if ($value_a > $value_b)
                        {
                            $sort = 1;
                        }
                    }
                    else
                    {
                        // If values are not numeric, use the natural CASE INSENSITIVE comparison function :
                        $sort = strnatcasecmp(strval($value_a), strval($value_b));
                    }
                }
                else if (is_null($value_a) && !is_null($value_b))
                {
                    $sort = -1;
                }
                else if (!is_null($value_a) && is_null($value_b))
                {
                    $sort = 1;
                }
            }
            else
            {
                // Use given order method :
                if (f_util_ClassUtils::methodExists($this, $this->getOrderMethod()))
                {
                    $sort = f_util_ClassUtils::callMethodOn($this, $this->getOrderMethod(), $a, $b);
                }
            }
        }

        switch ($this->getOrderDirection())
        {
            case self::ORDER_DESC:
                $sort = -$sort;
                break;

            case self::ORDER_RAND:
                $sort = mt_rand(-1, 1);
                break;
        }

	// "Topic Mode" - within the same topic, order documents by id :
	if ($this->useTopic && ($this->getOrderColumn() == self::ORDER_BY_PARENTID) && ($sort == 0))
	{
		$sort = strnatcasecmp($a->getId(), $b->getId());
	}

        return $sort;
    }


    /**
	 * Sets the filter.
	 *
	 * @param mixed $filter Filter.
     * @return tree_parser_TreeParser
	 */
    public final function setFilter($filter)
    {
    	if (is_null($filter))
    	{
    		$this->filter = array();
    		return;
    	}

        if (!is_array($filter))
        {
            $filter = trim($filter);

            if (f_util_StringUtils::beginsWith($filter, 'method:'))
            {
                $filter = array(self::FILTER_METHOD => substr($filter, strpos($filter, ':') + 1));
            }
            else if (strpos($filter, ':') !== false)
            {
                $filterPrefix = substr($filter, 0, strpos($filter, ':'));
                $filterValue = substr($filter, strpos($filter, ':') + 1);
                $filter = array(
                    self::FILTER_BY => $filterPrefix,
                    self::FILTER_VALUE => $filterValue
                );
            }
            else
            {
                $filter = array(
                    self::FILTER_BY => self::FILTER_BY_LABEL,
                    self::FILTER_VALUE => $filter
                );
            }

        }

        if (isset($filter[self::FILTER_BY]) && $filter[self::FILTER_BY]
        && isset($filter[self::FILTER_VALUE]) && $filter[self::FILTER_VALUE])
        {
            $this->filter = $filter;
        }
        else
        {
            $this->filter = array();
        }

        if (isset($filter[self::FILTER_METHOD]))
        {
            $this->setFilterMethod($filter[self::FILTER_METHOD]);
        }

        return $this;
    }


    /**
	 * Gets the filter.
	 *
	 * @return array
	 */
    public final function getFilter()
    {
        return $this->filter;
    }


    /**
	 * Sets the filter method.
	 *
	 * @param string $filterMethod Filter method name.
     * @return tree_parser_TreeParser
	 */
    public final function setFilterMethod($filterMethod)
    {
        $this->filterMethod = $filterMethod;

        return $this;
    }


    /**
	 * Gets the filter method.
	 *
	 * @return mixed
	 */
    public final function getFilterMethod()
    {
        return $this->filterMethod;
    }


    /**
	 * Has a filter.
	 *
	 * @return boolean
	 */
    public final function hasFiltering()
    {
        return (!is_null($this->getFilter()) || !is_null($this->getFilterMethod()));
    }


    /**
     * Callback method used for filtering trees.
     *
     * @param f_persistentdocument_PersistentDocumentImpl $document
     * @return boolean
     */
    public function filterTreeNodes($document)
    {
        $filtered = false;
        
        $filter = $this->getFilter();
		
        // No filter method - use the given filter string :
        if (is_null($this->getFilterMethod()))
        {
        	if (isset($filter[self::FILTER_BY]) && isset($filter[self::FILTER_VALUE]))
            {
                // Speed up common filterings :
                $filterBy = trim(strtolower($filter[self::FILTER_BY]));
                $filterValue = trim($filter[self::FILTER_VALUE]);
                switch ($filterBy)
                {
                    case self::FILTER_BY_ID:
                        if ($filterValue)
                        {
                            $filtered = f_util_StringUtils::beginsWith(
                                strval($document->getId()),
                                $filterValue
                            );
                        }
                        else
                        {
                            $filtered = true;
                        }
                        break;

                    case self::FILTER_BY_LANG:
                        if ($filterValue)
                        {
                            $filtered = (strtolower($document->getLang()) == strtolower($filterValue));
                        }
                        else
                        {
                            $filtered = true;
                        }
                        break;

                    case self::FILTER_BY_TYPE:
                        if ($filterValue)
                        {
                            $filtered = f_util_StringUtils::endsWith(
                                $document->getDocumentModelName(),
                                $filterValue
                            );
                        }
                        else
                        {
                            $filtered = true;
                        }
                        break;

                    case self::FILTER_BY_AUTHOR:
                        if ($filterValue)
                        {
                            $filtered = stripos(
                                $document->getAuthor(),
                                $filterValue
                            );
                        }
                        else
                        {
                            $filtered = true;
                        }
                        break;

                    case self::FILTER_BY_STATUS:
                        if ($filterValue)
                        {
                            $filtered = (strtolower($document->getPublicationstatus()) == strtolower($filterValue));
                        }
                        else
                        {
                            $filtered = true;
                        }
                        break;

                    case self::FILTER_BY_LABEL:
                        if ($filterValue)
                        {
                            $filtered = stripos(
                                $document->getLabel(),
                                $filterValue
                            );
                        }
                        else
                        {
                            $filtered = true;
                        }
                        break;

                    default:
                        // Try to use any "get<FilterPrefix>()" method :
                        if (f_util_ClassUtils::methodExists($document, 'get' . ucfirst($filterBy)))
                        {
                            if ($filterValue)
                            {
                                $filtered = stripos(
                                    f_util_ClassUtils::callMethodOn($document, 'get' . ucfirst($filterBy)),
                                    $filterValue
                                );
                            }
                            else
                            {
                                $filtered = true;
                            }
                        }
                        else
                        {
                            // Else filter all by label :
                            $filtered = stripos(
                                $document->getLabel(),
                                $filter[self::FILTER_BY] . ':' . $filter[self::FILTER_VALUE]
                            );
                        }
                        break;
                }
            }
        }
        else
        {
        	// Use given filter method :
            if (f_util_ClassUtils::methodExists($this, $this->getFilterMethod()))
            {
                if (isset($filter[self::FILTER_VALUE]))
                {
                    $value = trim($filter[self::FILTER_VALUE]);
                }
                else
                {
                    $value = null;
                }
                                
                $filtered = f_util_ClassUtils::callMethodOn($this, $this->getFilterMethod(), $document, $value);
            }
        }

        return ($filtered !== false);
    }


    /**
     * Filter method : returns TRUE if the given document is publicated.
     *
     * @param f_persistentdocument_PersistentDocument $document
     * @return boolean
     */
    public function filterPublicatedTreeNodes($document)
    {
        if ($document->isContextLangAvailable() && $document->isPublished())
        {
            return true;
        }

        return false;
    }


    /**
     * Filter method : returns TRUE if the given document is publishable.
     *
     * @param f_persistentdocument_PersistentDocument $document
     * @return boolean
     */
    public function filterPublishableTreeNodes($document)
    {
        if ($document->isContextLangAvailable())
        {
            $currentStatus = $document->getPublicationstatus();
		    return $currentStatus == 'PUBLICATED' || $currentStatus == 'ACTIVE';
        }

        return false;
    }


    /**
	 * Sets the relation type to use.
	 *
	 * @see RelationComponent
     * @return tree_parser_TreeParser
	 */
    public final function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }


   /**
	 * Sets the children types to use.
	 *
	 * @param array $childrenTypes
     * @return tree_parser_TreeParser
	 */
    public final function setChildrenTypes($childrenTypes)
    {
    	if (is_null($childrenTypes))
    	{
    		$childrenTypes = array();
    	}
    	elseif (is_string($childrenTypes) )
        {
            $childrenTypes = array($childrenTypes);
        }

        if (!is_array($childrenTypes) )
        {
            throw new BadArgumentException('childrenTypes', 'array');
        }

        $finalChildrenTypes = $this->childrenTypes;

        foreach ($childrenTypes as $childrenType)
        {
        	if (trim($childrenType))
            {
            	$finalChildrenTypes[] = trim($childrenType);
            	try
            	{
	                $model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($childrenType);
	                $finalChildrenTypes[] = $model->getName();
            	}
            	// TODO intbonjf 2007-10-24:
            	// Catch another Exception when the PersistentDocumentModel throws "better Exceptions".
            	catch (Exception $e)
            	{
					if (Framework::isWarnEnabled())
					{
						Framework::warn($e->getMessage());
					}
            	}
            }
        }
        $this->childrenTypes = array_unique($finalChildrenTypes);

        return $this;
    }


    public final function setViewCols($colsnames)
    {
    	if (!f_util_StringUtils::isEmpty($colsnames))
    	{
    		$this->viewCols = explode(',', $colsnames);
    	}
    	else
    	{
    		$this->viewCols = null;
    	}
    }
    
    /**
	 * Gets the children types to use.
	 *
     * @return array
	 */
    public function getChildrenTypes()
    {
        return $this->childrenTypes;
    }


    /**
	 * Use this method to specify the desired languages to use per document type.
	 *
	 * @param string $documentType
	 * @param array $langs
     * @return tree_parser_TreeParser
	 */
    public function setLangs($documentType, $langs)
    {
        // TODO intbonjf 2006-06-20:
        // check the validity of the languages (with IsoCountryCodeValidator)
        if ( ! is_array($langs) || empty($langs) )
        {
            throw new BadArgumentException("langs", "array of languages");
        }
        $this->langsPerDocumentTypes[$documentType] = $langs;

        return $this;
    }


    /**
	 * Use this method to specify the desired statuses to use per document type.
	 *
	 * @param string $documentType
	 * @param array $statuses
     * @return tree_parser_TreeParser
	 */
    public function setStatuses($documentType, $statuses = null)
    {
        // TODO intbonjf 2006-06-20:
        // check the the validity of the statuses for the document type
        if ( ! is_array($statuses) || empty($statuses) )
        {
            throw new BadArgumentException("statuses", "array of statuses");
        }
        $this->statusesPerDocumentTypes[$documentType] = $statuses;

        return $this;
    }


    /**
	 * This method is used internally to switch the documents before attaching
	 * them to a TreeNode.
	 * Please note that before changeset [6844], the documents were
	 * switchVersioned in the *RdfWriters.
	 * @deprecated
	 * @param f_persistentdocument_PersistentDocument $document
	 */
    protected function componentSwitchVersion($document)
    {
    }


    /**
	 * Enter description here...
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
    protected function getVirtualChildren($document)
    {
        $result = array();
        foreach ($document->getPersistentModel()->getPropertiesInfos() as $propertyName => $propertyValue)
        {
            if ($propertyValue->isTreeNode() && (empty($this->childrenTypes) || in_array($propertyValue->getType(), $this->childrenTypes)))
            {
                if ($propertyValue->isArray())
                {
                    $subdocs = 	$document->{'get'.ucfirst($propertyName).'Array'}();
                }
                else
                {
                    $subdocs = array($document->{'get'.ucfirst($propertyName)}());
                }

                foreach ($subdocs as $subdoc)
                {
                	if (!is_null($subdoc))
                    {
                        $result[$subdoc->getId()] = $subdoc;
                    }
                }
            }
        }

        foreach ($document->getPersistentModel()->getInverseProperties() as $propertyName => $propertyValue)
        {
            if ($propertyValue->isTreeNode() && (empty($this->childrenTypes) || in_array($propertyValue->getType(), $this->childrenTypes)))
            {
                $subdocs = 	$document->{'get'.ucfirst($propertyName).'ArrayInverse'}();

                foreach ($subdocs as $subdoc)
                {
                	$result[$subdoc->getId()] = $subdoc;
                }
            }
        }

        return $result;
    }

    /**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
    protected function hasVirtualChildren($document)
    {
    	foreach ($document->getPersistentModel()->getPropertiesInfos() as $propertyName => $propertyValue)
        {
            if ($propertyValue->isTreeNode() && (empty($this->childrenTypes) || in_array($propertyValue->getType(), $this->childrenTypes)))
            {
                if ($propertyValue->isArray())
                {
                    if ($document->{'get'.ucfirst($propertyName).'Count'}() > 0)
                    {
                    	return true;
                    }
                }
                else
                {
                    if (!is_null(($document->{'get'.ucfirst($propertyName)}())))
                    {
                    	return true;
                    }
                }
            }
        }

        foreach ($document->getPersistentModel()->getInverseProperties() as $propertyName => $propertyValue)
        {
            if ($propertyValue->isTreeNode() && (empty($this->childrenTypes) || in_array($propertyValue->getType(), $this->childrenTypes)))
            {
            	if ($document->{'get'.ucfirst($propertyName).'CountInverse'}() > 0)
                {
                	return true;
                }
            }
        }
        return false;
    }

    /**
	 * @return f_persistentdocument_PersistentProvider
	 */
    protected function getPersitentProvider()
    {
        return f_persistentdocument_PersistentProvider::getInstance();
    }


	/**
         * Return an informative topic path.
	 * 
         * @param integer $id
	 * @return string
	 */
	protected function getTopicPath($id)
	{
		if (!isset($this->topicPaths[$id]))
		{
			$document = DocumentHelper::getDocumentInstance($id);
			$this->topicPaths[$id] = str_replace(array('&nbsp;', '&'), array(' ', '&amp;'),
				html_entity_decode($document->getDocumentService()->getPathOf($document), ENT_NOQUOTES, 'UTF-8'));
		}
		return $this->topicPaths[$id];
	}
}
