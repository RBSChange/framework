<?php
class tree_parser_XmlTreeParser extends tree_parser_TreeParser
{
	/**
	 * Since the generated XML document has to be as compact as possible,
	 * all tags and attributes are defined by a single character.
	 *
	 * The following constants are used to make all this stuff more explicit...
	 *
	 */
	
	// <d> - a document node :
	const DOCUMENT_NODE = 'd';
	
	// <p> - a document's attribute node :
	const DOCUMENT_ATTRIBUTE = 'p';
	
	// <p n="name"> - name of the document's attribute :
	const ATTRIBUTE_NAME = 'n';
	
	// <d i="666"> - document's ID :
	const ATTRIBUTE_ID = 'i';
	
	// <d p="999"> - document's parent ID :
	const ATTRIBUTE_PARENT_ID = 'p';
	
	// <d t="modules_website_page"> - document's type :
	const ATTRIBUTE_TYPE = 't';
	
	// <d pu="1"> - document is publicated :
	const ATTRIBUTE_PUBLICATED = 'pu';
	
	// <d pa="1"> - document is publishable :
	const ATTRIBUTE_PUBLISHABLE = 'pa';
	
	// <d w="1"> - document is waiting for a workflow validation :
	const ATTRIBUTE_WORKFLOW = 'w';
	
	// <d cr="1"> - document is a correction :
	const ATTRIBUTE_CORRECTION = 'cr';
	
	/**
	 * <d l="fr"> - langue du context de la requete
	 */
	const ATTRIBUTE_LANG = 'l';
	
	/**
	 * <d la="true">
	 */
	const ATTRIBUTE_CONTEXT_LANG_AVAILABLE = 'la';
	
	/**
	 * <d dl="fr en de">
	 * la premiere langue est la vo du document
	 */
	const ATTRIBUTE_DOCUMENT_LANGS = 'dl';
	
	/**
	 * <d v="0"> - document's version :
	 * @deprecated
	 */
	const ATTRIBUTE_VERSION = 'v';
	
	/**
	 * <d s="ACTIVE"> - document's status :
	 */
	const ATTRIBUTE_STATUS = 's';
	
	// <d m="..."> - document's modification date :
	const ATTRIBUTE_MODIFICATION_DATE = 'm';
	
	// <d tp="informative/topic/path"> - informative topic path (used for modules relying on topics) :
	const ATTRIBUTE_TOPIC_PATH = 'tp';
	
	// <d cc="123"> - child count :
	const ATTRIBUTE_CHILDCOUNT = 'cc';
	
	// <d o="3"> - number of pages needed to display all the document's children, according to the defined "length" property :
	const ATTRIBUTE_PAGE_TOTAL = 'o';
	
	// <d x="2"> - number of the next displayable page, if available :
	const ATTRIBUTE_PAGE_NEXT = 'x';
	
	// <d e="1"> - number of the currently displayed page, if applicable :
	const ATTRIBUTE_PAGE_INDEX = 'e';
	
	// <d c="1"> - if set to "1", the document contains some children beyond the defined "depth" property :
	const ATTRIBUTE_CUT_IN_DEPTH = 'c';
	
	// Common name for the "label" attribute - <p name="label">value</p> :
	const LABEL_ATTRIBUTE = 'label';
	
	// Common name for the "type" attribute - <p name="type">modules_website/page</p> :
	const TYPE_ATTRIBUTE = 'type';
	
	// Common name for the "htmllink" attribute - <p name="htmllink"><a class="link" href="...">...</a></p> :
	const HTMLLINK_ATTRIBUTE = 'htmllink';
	
	// Common name for the "plainlink" attribute - <p name="plainlink">http://...</p> :
	const PLAINLINK_ATTRIBUTE = 'plainlink';
	
	// Common name for the "block" attribute - <p name="block">...</p> :
	const BLOCK_ATTRIBUTE = 'block';
	
	// Default HTML link template :
	const DEFAULT_HTMLLINK_TEMPLATE = '<a class="link" href="#" rel="cmpref:%s" lang="%s" xml:lang="%s">%s</a>';
	
	// "Root folder" document's type :
	const ROOT_NODE_TYPE = 'modules_generic/rootfolder';
	
	// "Folder" document's type :
	const FOLDER_NODE_TYPE = 'modules_generic/folder';
	
	// "Empty" document's type :
	const EMPTY_NODE_TYPE = 'empty';
	
	// Internal attribute's name used to bypass the global "ignoreChildren" property :
	const FOLLOW_CHILDREN = '_follow_children';
	
	// Internal attribute's name used to bypass the global "ignoreChildren" property :
	const SKIP_CHILDREN = '_skip_children';
	
	// Default method for getting documents navigation label :
	const DEFAULT_NAVIGATIONLABEL_METHOD = 'getNavigationtitle';
	
	// The XML document being made :
	protected $xmlDoc = null;
	
	// Start node Id
	private $startNodeId = 0;
	
	// XML specific property - "does the new XML content overwrite the already loaded one ?" :
	protected $overwrite = false;
	
	/**
	 *  Additions for permissions
	 */
	
	const ATTRIBUTE_HAS_PERMISSIONS = 'r';
	const ATTRIBUTE_IS_VIRTUAL_NODE = 'vn';
	
	const ATTRIBUTE_PERMISSION = 'pe';
	const ATTRIBUTE_ROOT_PERMISSION = 'rpe';
	
	/**
	 * @var Array<Integer, Boolean>
	 */
	protected $permissionned_nodes = null;
	
	/**
	 * @var users_persistentdocument_backenduser
	 */
	private $currentUser;
	
	/**
	 * @var integer
	 */
	private $startingLevel = 0;
	
	/**
	 * This method should be used to modify the parser properties.
	 * A generic XmlTreeParser (used by <wtree> elements) has the following properties :
	 *
	 *  - Depth limit : 1.
	 *  - Length limit : none (0).
	 *  - Overwrite the already loaded data (on the client side) : no (false).
	 *  - Ignore children : no (false).
	 *  - Ignore length limit beyond depth : n/a (0).
	 */
	public function initialize()
	{
		$this->setDepth(1)->setLength(0)->setOverwrite(false)->setIgnoreChildren(false)->setIgnoreLengthBeyondDepth(0);
	}
	
	/**
	 * @return f_persistentdocument_DocumentService
	 */
	private function getDocumentService()
	{
		return f_persistentdocument_DocumentService::getInstance();
	}
	
	/**
	 * @param Integer $nodeId
	 * @return Boolean
	 */
	private function isStartNode($nodeId)
	{
		return $nodeId == $this->startNodeId;
	}
	
	/**
	 * Check validity of the root node of the tree
	 * @param Integer $nodeId
	 */
	private function getParentTreeRootNode($nodeId)
	{
		$parentId = null;
		
		$treeNode = TreeService::getInstance()->getInstanceByDocumentId($nodeId);
		if (! is_null($treeNode))
		{
			$rootDocument = DocumentHelper::getDocumentInstance(ModuleService::getInstance()->getRootFolderId($this->moduleName));
			if ($treeNode->getTreeId() != $rootDocument->getId())
			{
				$parentId = $rootDocument->getId();
				
				$ids = DocumentHelper::getIdArrayFromDocumentArray($treeNode->getAncestors());
				$ids[] = intval($nodeId);
				
				$topicsIds = DocumentHelper::getIdArrayFromDocumentArray($rootDocument->getTopicsArray());
				
				if ($rootDocument->getId() != $nodeId && count(array_intersect($ids, $topicsIds)) == 0)
				{
					$e = new BaseException('object-not-found', 'framework.exception.Object-not-found', array('id' => $nodeId));
					throw $e;
				}
			
			}
			else if ($treeNode->getId() != $treeNode->getTreeId())
			{
				$parentId = $treeNode->getParent()->getId();
			}
		}
		return $parentId;
	}
	
	/**
	 * Main method used to retrieve the tree data.
	 *
	 * @param integer $documentId ID of the root component from which the parsing
	 * should begin.
	 * @param integer $offset Pagination index.
	 * @param string $order User-defined ordering data.
	 * @param string $filter User-defined filtering data.
	 * @return DOMDocument XML tree data
	 */
	public function getTree($documentId = null, $offset = 0, $order = null, $filter = null)
	{
		try
		{
			// If no root component is defined, then we use the "root component" of the currrent module :
			if (is_null($documentId))
			{
				$documentId = $this->getRootComponentId();
			}
			
			/**
			 * Check for correction
			 */
			$document = DocumentHelper::getDocumentInstance($documentId);
			if ($this->getTreeType() != self::TYPE_MULTI_LIST && 
				$document->getPersistentModel()->useCorrection() && $document->getCorrectionofid())
			{
				$documentId = $document->getCorrectionofid();
			}
			
			$this->startNodeId = $documentId;
			$parentId = $this->getParentTreeRootNode($documentId);
			
			if ($_documentTreeNode = TreeService::getInstance()->getInstanceByDocument($document))
			{
				$this->startingLevel = $_documentTreeNode->getLevel();
			}
			
			// User-defined offset (pagination) :
			if ($offset)
			{
				$this->setOffset($offset);
			}
			
			// User-defined order (overwrite the predefined one, if any) :
			if ($order)
			{
				$this->setOrder($order);
			}
			
			// User-defined filter (overwrite the predefined one, if any) :
			if ($filter)
			{
				$this->setFilter($filter);
			}
			
			/**
			 * Charge tout les identifiants de noeud possedant des permissions
			 */
			$this->permissionned_nodes = f_persistentdocument_PersistentProvider::getInstance()->getPermissionDefinitionPoints('modules_' . $this->moduleName);
			
			// "Topic Mode" (on trees only) with ordering by Parent ID :
			if (($this->getTreeType() == self::TYPE_TREE) && defined('MOD_' . strtoupper($this->moduleName) . '_USETOPIC') && (constant('MOD_' . strtoupper($this->moduleName) . '_USETOPIC') == true))
			{
				$this->useTopic = true;
				// Sorting is now done in generic_persistentdocument_rootfolder::getTopicsArray()
				// instead of here.
			}
			
			// Retrieve the tree data :
			return $this->getTreeData($documentId, $parentId);
		
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return $this->getXmlErrorMessage($e->getMessage());
		}
	}
	
	/**
	 * @return f_permission_PermissionService
	 */
	private function getPermissionService()
	{
		return f_permission_PermissionService::getInstance();
	}
	
	/**
	 * @return users_persistentdocument_backenduser
	 */
	private function getCurrentBackEndUser()
	{
		if (is_null($this->currentUser))
		{
			$this->currentUser = users_UserService::getInstance()->getCurrentBackEndUser();
		}
		return $this->currentUser;
	}
	
	protected final function isDefinitionPointNode($nodeId)
	{
		return array_search($nodeId, $this->permissionned_nodes) !== false;
	}
	
	/**
	 * @param DOMElement $currentNode
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Boolean $virtualnode;
	 */
	private function addPermissionInfo($currentNode, $document, $virtualnode = false)
	{
		if (count($this->permissionned_nodes) == 0)
		{
			if ($this->isStartNode($document->getId()))
			{
				$currentNode->setAttribute(self::ATTRIBUTE_PERMISSION, f_permission_PermissionService::ALL_PERMISSIONS);
			}
			return;
		}
		
		if ($virtualnode)
		{
			$currentNode->setAttribute(self::ATTRIBUTE_IS_VIRTUAL_NODE, 'true');
		}
		$ps = $this->getPermissionService();
		
		if ($this->isDefinitionPointNode($document->getId()))
		{
			$currentNode->setAttribute(self::ATTRIBUTE_HAS_PERMISSIONS, '1');
			$permissions = $ps->getPermissionsForUserByDefPointNodeId($this->getCurrentBackEndUser(), $document->getId());
			$currentNode->setAttribute(self::ATTRIBUTE_PERMISSION, $this->getBackOfficeActionsNames($permissions));
		}
		elseif ($this->isStartNode($document->getId()))
		{
			$defPointId = $ps->getDefinitionPointForPackage($document->getId(), 'modules_' . $this->moduleName);
			if (!is_null($defPointId))
			{
				$permissions = $ps->getPermissionsForUserByDefPointNodeId($this->getCurrentBackEndUser(), $defPointId);
				$currentNode->setAttribute(self::ATTRIBUTE_PERMISSION, $this->getBackOfficeActionsNames($permissions));
			}
			else
			{
				$currentNode->setAttribute(self::ATTRIBUTE_PERMISSION, f_permission_PermissionService::ALL_PERMISSIONS);
			}
		}
	}
	
	/**
	 * Transform permissions list to backOffice action name
	 *
	 * @param unknown_type $permissions
	 * @return unknown
	 */
	private function getBackOfficeActionsNames($permissions)
	{
		if (count($permissions) == 1 && $permissions[0] == 'allpermissions')
		{
			return 'allpermissions';
		}
		else if (count($permissions) == 0)
		{
			return 'noaction';
		}
		
		$ps = $this->getPermissionService();
		$roleservice = $ps->getRoleServiceByModuleName($this->moduleName);
		
		$names = $roleservice->getBackOfficeActions();
		foreach ($roleservice->getActionsByPermissions($permissions) as $actionName)
		{
			$backName = $roleservice->getBackOfficeActionName($actionName);
			$index = array_search($backName, $names);
			if ($index === false)
			{
				$names[] = $backName;
			}
		}
		
		return implode(',', $names);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	private function getPermissionName($document)
	{
		return 'modules_' . $this->moduleName . '.List.' . $document->getPersistentModel()->getDocumentName();
	}
	
	/**
	 * Return XML error message.
	 *
	 * @param string $message
	 * @return DOMDocument
	 */
	protected function getXmlErrorMessage($message, $action = 'GetXmlTree')
	{
		$xmlDoc = new DOMDocument('1.0', 'utf-8');
		$xmlDocRoot = $xmlDoc->createElement('response');
		$xmlDoc->appendChild($xmlDocRoot);
		$xmlStatus = $xmlDoc->createElement('status', 'ERROR');
		$xmlDocRoot->appendChild($xmlStatus);
		$xmlAction = $xmlDoc->createElement('action', $action);
		$xmlDocRoot->appendChild($xmlAction);
		$xmlLang = $xmlDoc->createElement('lang', RequestContext::getInstance()->getLang());
		$xmlDocRoot->appendChild($xmlLang);
		$xmlOverwrite = $xmlDoc->createElement('overwrite', 'true');
		$xmlDocRoot->appendChild($xmlOverwrite);
		$xmlMsg = $xmlDoc->createElement('message', $message);
		$xmlDocRoot->appendChild($xmlMsg);
		
		return $xmlDoc;
	}
	
	/**
	 * This recursive method returns the XML tree data for the given level.
	 *
	 * @param Integer $nodeId ID of the root component from which the parsing should begin.
	 * @param Integer $parentId Root component parent ID.
	 * @param DOMElement $currentXmlDocRoot Current XML node.
	 * @param Integer $level Tree's current level.
	 * @param Boolean $virtualNode
	 * @return DOMDocument XML tree data
	 */
	protected function getTreeData($nodeId, $parentId = null, $currentXmlDocument = null, $level = 0, $firstCall = true)
	{
		// First iteration - create the XML document :
		if (is_null($this->xmlDoc))
		{
			$this->xmlDoc = $this->createXmldocRootElement();
			$currentXmlDocument = $this->getXmlDocRootNode();
		}
		
		// Get the document related to the current node :
		try
		{
			$virtualNode = false;
			$document = DocumentHelper::getDocumentInstance($nodeId);
			$node = TreeService::getInstance()->getInstanceByDocument($document);
			$virtualNode = (is_null($node) || $node->getId() != $parentId);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			
			return $this->xmlDoc;
		}
		
		if ($this->checkDocumentVisibility($document, $parentId, $level))
		{
			$followChildren = false;
			
			$skipChildren = false;
			
			// Create a tree node then populate it :
			$currentNode = $this->buildNodeXmlElement($document, $parentId, $virtualNode, $followChildren, $skipChildren);
			if (is_null($currentNode))
			{
				return $this->xmlDoc;
			}
			
			// Children are not ignored or current level within the applicable depth limit - children are retrieved :
			if (! $skipChildren && (! $this->getIgnoreChildren() || ($level == 0) || $followChildren) && (! $this->getDepth() || (($level + 1) <= $this->getDepth())))
			{
				$pageCount = 0;
				$visibleCount = 0;
				$childComponents = $this->getChildrenDocument($document, $level, $pageCount, $visibleCount);
				if ($pageCount > 0)
				{
					$currentNode->setAttribute(self::ATTRIBUTE_PAGE_TOTAL, $pageCount);
				}
				if (($visibleCount > 0) && (($this->getTreeType() == self::TYPE_MULTI_LIST) || ($this->getTreeType() == self::TYPE_LIST)))
				{
					if ($visibleCount > $this->getLength())
					{
						$_liststart = ($this->getOffset() * $this->getLength()) + 1;
						$_listend = min($_liststart + $this->getLength() - 1, $visibleCount);
						if ($_liststart == $visibleCount)
						{
							$currentNode->setAttribute(self::ATTRIBUTE_CHILDCOUNT, f_Locale::translateUI("&modules.uixul.bo.general.listspansingle;", array('total' => $visibleCount)));
						}
						else
						{
							$currentNode->setAttribute(self::ATTRIBUTE_CHILDCOUNT, f_Locale::translateUI("&modules.uixul.bo.general.listspanplural;", array('start' => $_liststart, 'end' => $_listend, 'total' => $visibleCount)));
						}
					}
					else if ($visibleCount == 1)
					{
						$currentNode->setAttribute(self::ATTRIBUTE_CHILDCOUNT, f_Locale::translateUI("&modules.uixul.bo.general.listsingle;"));
					}
					else
					{
						$currentNode->setAttribute(self::ATTRIBUTE_CHILDCOUNT, f_Locale::translateUI("&modules.uixul.bo.general.listplural;", array('count' => $visibleCount)));
					}
				}
				$count = 0;
				foreach ($childComponents as $component)
				{
					$this->getTreeData($component->getId(), $document->getId(), $currentNode, $level + 1, false);
					$count ++;
										
					if ($this->getAvailableLength($level + 1) && ($count >= $this->getAvailableLength($level + 1)))
					{
						if ($count < count($childComponents))
						{
							$currentNode->setAttribute(self::ATTRIBUTE_PAGE_NEXT, $this->getOffset() + 1);
						}
						break;
					}
				}
			}
			else if (! $skipChildren && (! $this->getIgnoreChildren() || ($followChildren)))
			{
				if ($this->hasChildrenDocument($document))
				{
					// There are children and they are not ignored,
					// or the parser is forced to take account of them (thanks to the internal FOLLOW_CHILDREN attribute) :
					$currentNode->setAttribute(self::ATTRIBUTE_CUT_IN_DEPTH, 1);
				}
			}
			
			if ($currentNode->hasAttribute(self::ATTRIBUTE_PAGE_TOTAL) && ($this->getOffset() > 0))
			{
				$currentNode->setAttribute(self::ATTRIBUTE_PAGE_INDEX, $this->getOffset());
			}
			
			$currentXmlDocument->appendChild($currentNode);
		
		}
		
		return $this->xmlDoc;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $parentId
	 * @param Integer $level
	 * @return Boolean
	 */
	private function checkDocumentVisibility($document, $parentId, $level)
	{
		$nodeId = $document->getId();
		$documentModelName = $document->getDocumentModelName();
		
		if ((count($this->childrenTypes) == 0) || in_array($documentModelName, $this->childrenTypes) || ($level == 0))
		{			
			//Check security
			if ($this->isDefinitionPointNode($nodeId) || $this->isStartNode($nodeId))
			{
				$backEndUser = $this->getCurrentBackEndUser();
				$permission = $this->getPermissionName($document);
				$result = f_permission_PermissionService::getInstance()->hasPermission($backEndUser, $permission, $nodeId);
				return $result;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $originalDocument
	 * @param Integer $parentId
	 * @param Boolean $virtualNode
	 * @param Boolean $followChildren
	 * @param Boolean $skipChildren
	 * @return DOMElement
	 */
	private function buildNodeXmlElement($originalDocument, $parentId, $virtualNode, &$followChildren, &$skipChildren)
	{
		if ($this->getTreeType() != self::TYPE_MULTI_LIST)
		{
			$document = DocumentHelper::getCorrection($originalDocument);
		}
		else
		{
			$document = $originalDocument;
		}
		
		$currentNode = $this->createNodeFromDocument($document);
		if (!is_null($parentId))
		{
			$currentNode->setAttribute(self::ATTRIBUTE_PARENT_ID, $parentId);
		}
		
		$this->addPermissionInfo($currentNode, $document, $virtualNode);
		
		$nodeAttribute = null;
		
		// Merge the "standard" attributes with the specific ones,
		// defined by the commonly overridden "getAttributes" method :
		try
		{
			$nodeAttributes = array_merge($this->getStandardAttributes($document), $this->getAttributes($document, $currentNode, null));
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			$nodeAttributes = array();
		}
		
		$document->buildTreeAttributes($this->moduleName, $this->getTreeType(), $nodeAttributes);
		
		if (isset($nodeAttributes[self::ATTRIBUTE_PUBLICATED]))
		{
			$currentNode->setAttribute(self::ATTRIBUTE_PUBLICATED, $nodeAttributes[self::ATTRIBUTE_PUBLICATED]);
		}
		
		if (! array_key_exists(self::LABEL_ATTRIBUTE, $nodeAttributes) || strlen($nodeAttributes[self::LABEL_ATTRIBUTE]) == 0)
		{
			if ($this->getTreeType() != self::TYPE_LIST)
			{
				$label = $document->getTreeNodeLabel();
			}
			elseif ($document->isContextLangAvailable())
			{
				$label = $document->getI18nInfo()->getLabel();
			}
			else
			{
				$label = $document->getI18nInfo()->getVoLabel();
			}
			$nodeAttributes[self::LABEL_ATTRIBUTE] = $this->normalizeValue($label);
		}
		
		foreach ($nodeAttributes as $attribute => $value)
		{
			// Skip the internal FOLLOW_CHILDREN attribute (see below) :
			if ($attribute == self::FOLLOW_CHILDREN)
			{
				$followChildren = true;
				continue;
			}
			
			// Skip the internal SKIP_CHILDREN attribute (see below) :
			if ($attribute == self::SKIP_CHILDREN)
			{
				$skipChildren = true;
				continue;
			}
			
			if (! is_object($value) && ! is_array($value) && ! is_null($value))
			{
				$nodeAttribute = $this->xmlDoc->createElement(self::DOCUMENT_ATTRIBUTE);
				$nodeAttribute->appendChild($this->xmlDoc->createTextNode($value));
				$nodeAttribute->setAttribute(self::ATTRIBUTE_NAME, $attribute);
				
				$currentNode->appendChild($nodeAttribute);
				
				$nodeAttribute = null;
				
				if ($attribute == self::TYPE_ATTRIBUTE)
				{
					$currentNode->setAttribute(self::ATTRIBUTE_TYPE, $this->normalizeType($value));
				}
			}
		}
		
		return $currentNode;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $level
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	protected function getTreeChildren($document, $level)
	{
		$childComponents = array();
		$ts = TreeService::getInstance();
		$treenode = $ts->getInstanceByDocument($document);
		if (!is_null($treenode))
		{
			if ($this->getDepth() != 1)
			{
				// FIXME : This is OLD stuff : is there any XmlTreeParser using depth != 1 ??
				// If no : remove these lines
				

				if ($level == 0)
				{
					$children = $ts->loadDescendants($treenode, $this->getDepth());
				}
				else
				{
					$children = $treenode->getChildren();
				}
				foreach ($children as $childNode)
				{
					if (empty($this->childrenTypes) || in_array($childNode->getDocumentModelName(), $this->childrenTypes))
					{
						$childComponents[$childNode->getId()] = $childNode->getPersistentDocument();
					}
				}
			
			}
			else
			{
				$query = $this->getPersitentProvider()->createQuery()->add(Restrictions::childOf($document->getId()));
				if (! empty($this->childrenTypes))
				{
					$query->add(Restrictions::in('document_model', $this->childrenTypes));
				}
				
				$children = $query->find();
				foreach ($children as $child)
				{
					$childComponents[$child->getId()] = $child;
				}
			}
		}
		return $childComponents;
	}
	
	/**
	 * @param unknown_type $document
	 * @return boolean
	 */
	private function hasChildrenDocument($document)
	{
		return $this->hasTreeChildren($document) || $this->hasVirtualChildren($document);
	}
	
	/**
	 * @param unknown_type $document
	 * @return boolean
	 */
	private function hasTreeChildren($document)
	{
		$query = $this->getPersitentProvider()->createQuery()->add(Restrictions::childOf($document->getId()));
		if (! empty($this->childrenTypes))
		{
			$query->add(Restrictions::in('document_model', $this->childrenTypes));
		}
		$query->setProjection(Projections::rowCount("count"));
		$result = $query->find();
		return $result[0]["count"] > 0;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $level
	 * @param Integer $pageCount
	 * @param Integer $visibleCount
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	private function getChildrenDocument($document, $level, &$pageCount, &$visibleCount)
	{
		$childComponents = $this->getTreeChildren($document, $level);
		$virtualChildren = $this->getVirtualChildren($document);
		
		// Merge the "standard" children with the specific ones,
		// defined by the commonly overridden "getVirtualChildren" method :
		$childComponents = array_values(array_merge($childComponents, $virtualChildren));
		
		// Children are filtered :
		if ($this->hasFiltering())
		{
			$childComponents = array_filter($childComponents, array($this, 'filterTreeNodes'));
			
			// Refresh indexes.
			$childComponents = array_values($childComponents);
		}
		
		// Apply common publication filters :
		if ($this->getDisplayPublicatedOnly())
		{
			$childComponents = array_filter($childComponents, array($this, 'filterPublicatedTreeNodes'));
						
			// Refresh indexes.
			$childComponents = array_values($childComponents);
		}
		else if ($this->getDisplayPublishableOnly())
		{
			$childComponents = array_filter($childComponents, array($this, 'filterPublishableTreeNodes'));
						
			// Refresh indexes.
			$childComponents = array_values($childComponents);
		}
		
		// FIX#768: Sorting must be done before handling pagination.
		//			This will be very slow with many documents...
		// 			This sorting can't be done by the database system 
		//			because columns may not be directly the values of
		//			documents properties.
		// Children are ordered :
		if ($this->hasOrdering())
		{
			usort($childComponents, array($this, 'sortTreeNodes'));
		}
		
		$pageIndex = $this->getOffset();
		$pageLength = $this->getLength();			
		$offset = $pageIndex * $pageLength;
		$offsetStart = 0;
		$offsetFound = false;
		$locateStart = 0;
		$locateFound = false;
		$searchedIds = array_flip($this->getSearchedIds());
		$checkIds = count($searchedIds) > 0;
		$visibleCount = 0;
		foreach ($childComponents as $childComponent)
		{
			if ($visibleCount == $offset)
			{
				$offsetFound = true;
			}
			if ($checkIds && isset($searchedIds[$childComponent->getId()]))
			{
				$locateFound = true;
			}				
			$visibleCount++;
			if (!$offsetFound)
			{
				$offsetStart++;
			}
			if ($checkIds && !$locateFound)
			{
				$locateStart++;
			}
		}
				
		// Length constraint applicable - children are sliced:
		if ($this->getAvailableLength($level + 1) && ($visibleCount > $this->getAvailableLength($level + 1)))
		{
			$pageCount = ceil($visibleCount / $pageLength);
			$tooManyChildComponents = $childComponents;
			$childComponents = array();
			$documentCount = count($tooManyChildComponents);
			
			$count = 0;
			if ($locateFound)
			{
				$pageIndex = intval($locateStart / $pageLength);
				$index = $pageIndex * $pageLength;
			}
			else
			{
				$index = $offsetStart;
			}
			
			while ($count < $pageLength && $index < $documentCount)
			{
				$document = $tooManyChildComponents[$index];
				$childComponents[] = $document;
				$count++;
				$index++;
			}
		}

		$this->setOffset($pageIndex);
				
		return $childComponents;
	}
	
	/**
	 * Returns the document's predefined attributes.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array<mixed>
	 */
	protected function getStandardAttributes($document)
	{
		$model = $document->getPersistentModel();
		$propertiesNames = array();
		$viewCols = $this->viewCols;
		foreach ($model->getPropertiesInfos() as $propertyName => $propertyInfo)
		{
			if ($viewCols === null)
			{
				if (! $propertyInfo->isDocument() && $propertyName != 'label')
				{
					$formProperty = $model->getFormProperty($propertyName);
					if (! is_null($formProperty) && ! $formProperty->isHidden())
					{
						$propertiesNames[] = $propertyName;
					}
				}
			}
			else
			{
				if (! $propertyInfo->isDocument() && $propertyName != 'label' && in_array($propertyName, $viewCols))
				{
					$propertiesNames[] = $propertyName;
				}
			}
		}
		if ($model->useCorrection() && $document->getCorrectionofid())
		{
			$propertiesNames[] = 'correctionofid';
		}
		
		if (count($propertiesNames) > 0)
		{
			$attributes = DocumentHelper::getPropertiesListOf($document, $propertiesNames);
		}
		else
		{
			$attributes = array();
		}
		
		switch ($this->getTreeType())
		{
			case self::TYPE_LIST :
			case self::TYPE_MULTI_TREE :
			case self::TYPE_MULTI_LIST :
				if (f_util_ClassUtils::methodExists($document, self::DEFAULT_NAVIGATIONLABEL_METHOD))
				{
					$label = f_util_ClassUtils::callMethodOn($document, self::DEFAULT_NAVIGATIONLABEL_METHOD);
				}
				else
				{
					$label = $document->getLabel();
				}
				
				$attributes[self::HTMLLINK_ATTRIBUTE] = sprintf(self::DEFAULT_HTMLLINK_TEMPLATE, $document->getId(), RequestContext::getInstance()->getLang(), RequestContext::getInstance()->getLang(), f_Locale::translateUI($label));
				
				if (! $this->isStartNode($document->getId()))
				{
					if ($document->getDocumentModelName() == self::FOLDER_NODE_TYPE)
					{
						$attributes[self::BLOCK_ATTRIBUTE] = $this->normalizeType('modules_' . $this->moduleName . '/folder');
					}
					else
					{
						$attributes[self::BLOCK_ATTRIBUTE] = $this->normalizeType($document->getDocumentModelName());
					}
				}
				else if ($document->getDocumentModelName() == self::ROOT_NODE_TYPE)
				{
					$attributes[self::BLOCK_ATTRIBUTE] = $this->normalizeType('modules_' . $this->moduleName . '/folder');
				}
				
				break;
		}
		
		return $attributes;
	}
	
	/**
	 * Returns the document's specific and/or overridden attributes.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param XmlElement $treeNode
	 * @param f_persistentdocument_PersistentDocument $reference
	 * @return array<mixed>
	 */
	protected function getAttributes($document, $treeNode, $reference = null)
	{
		return array();
	}
	
	/**
	 * Create a DOM node based on the given document.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return DOMElement
	 */
	protected function createNodeFromDocument($document)
	{
		$lang = RequestContext::getInstance()->getLang();
		$isContextLangAvailable = $document->isContextLangAvailable();
		$langs = $document->getI18nInfo()->getLangs();
		
		$label = $isContextLangAvailable ? $document->getI18nInfo()->getLabel() : $document->getI18nInfo()->getVoLabel();
		
		$correction = ($document->getPersistentModel()->useCorrection() && $document->getCorrectionofid());
		$publicated = $this->filterPublicatedTreeNodes($document);
		$publishable = $this->filterPublishableTreeNodes($document);
		
		return $this->createNode($document->getId(), $label, $document->getDocumentModelName(), $lang, join(' ', $langs), $isContextLangAvailable, $document->getPublicationstatus(), $document->getUIModificationdate(), $publicated, $publishable, $correction);
	}
	
	/**
	 * Create a DOM node based on the given data.
	 *
	 * @param integer $id
	 * @param string $label
	 * @param string $modelname
	 * @param string $lang
	 * @param string $documentLangs
	 * @param boolean $contextLangAvailable
	 * @param string $status
	 * @param string $modificationDate
	 * @return DOMElement
	 */
	protected function createNode($id, $label, $modelname, $lang, $documentLangs, $contextLangAvailable, $status, $modificationDate, $publicated, $publishable, $correction)
	{
		$currentNode = $this->xmlDoc->createElement(self::DOCUMENT_NODE);
		$currentNode->setAttribute(self::ATTRIBUTE_ID, $id);
		$currentNode->setAttribute(self::ATTRIBUTE_TYPE, $this->normalizeType($modelname));
		$currentNode->setAttribute(self::ATTRIBUTE_LANG, $lang);
		$currentNode->setAttribute(self::ATTRIBUTE_DOCUMENT_LANGS, $documentLangs);
		$currentNode->setAttribute(self::ATTRIBUTE_CONTEXT_LANG_AVAILABLE, $contextLangAvailable ? '1' : '0');
		$currentNode->setAttribute(self::ATTRIBUTE_STATUS, $status);
		$currentNode->setAttribute(self::ATTRIBUTE_MODIFICATION_DATE, $this->normalizeDate($modificationDate));
		$currentNode->setAttribute(self::ATTRIBUTE_PUBLICATED, $publicated ? '1' : '0');
		$currentNode->setAttribute(self::ATTRIBUTE_PUBLISHABLE, $publishable ? '1' : '0');
		$currentNode->setAttribute(self::ATTRIBUTE_WORKFLOW, ($status == 'WORKFLOW') ? '1' : '0');
		$currentNode->setAttribute(self::ATTRIBUTE_CORRECTION, $correction ? '1' : '0');
		
		// "Topic Mode" - pass topic full path as attribute :
		if ($this->useTopic && ($modelname == 'modules_website/topic') && ($this->startingLevel < 2))
		{
			$currentNode->setAttribute(self::ATTRIBUTE_TOPIC_PATH, $this->getTopicPath($id));
		}
		
		return $currentNode;
	}
	
	/**
	 * Returns the root of the current XML tree data.
	 *
	 * @return DOMElement
	 */
	public function getXmlDocRootNode()
	{
		$list = $this->xmlDoc->getElementsByTagName('response');
		$responseNode = $list->item(0);
		
		return $responseNode;
	}
	
	/**
	 * Returns a new XML tree document.
	 *
	 * @param string $action Related action.
	 * @return DOMDocument
	 */
	public function createXmldocRootElement($action = 'GetXmlTree')
	{
		$xmlDoc = new DOMDocument('1.0', 'utf-8');
		$xmlDocRoot = $xmlDoc->createElement('response');
		$xmlDoc->appendChild($xmlDocRoot);
		$xmlStatus = $xmlDoc->createElement('status', 'OK');
		$xmlDocRoot->appendChild($xmlStatus);
		$xmlAction = $xmlDoc->createElement('action', $action);
		$xmlDocRoot->appendChild($xmlAction);
		$xmlLang = $xmlDoc->createElement('lang', RequestContext::getInstance()->getLang());
		$xmlDocRoot->appendChild($xmlLang);
		
		// "Topic Mode" declared :
		if ($this->useTopic)
		{
			$xmlUseTopic = $xmlDoc->createElement('usetopic', 'true');
			$xmlDocRoot->appendChild($xmlUseTopic);
		}
		
		// Output the actual "overwrite" property :
		if ($this->getOverwrite())
		{
			$xmlOverwrite = $xmlDoc->createElement('overwrite', 'true');
			$xmlDocRoot->appendChild($xmlOverwrite);
		}
		
		// Output the actual "ordering" properties :
		if ($this->hasOrdering())
		{
			$xmlSortOn = $xmlDoc->createElement('orderby', $this->getOrderColumn());
			$xmlDocRoot->appendChild($xmlSortOn);
			$xmlSortDir = $xmlDoc->createElement('orderdir', $this->getOrderDirection());
			$xmlDocRoot->appendChild($xmlSortDir);
		}
		
		return $xmlDoc;
	}
	
	/**
	 * Normalize the given value for XML tree data.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function normalizeValue($value)
	{
		return str_replace(array('&nbsp;', '&'), array(' ', '&amp;'), html_entity_decode(f_Locale::translateUI($value), ENT_NOQUOTES, 'UTF-8'));
	}
	
	/**
	 * Normalize the given "type" (document model name) for XML tree data.
	 *
	 * @param string $type
	 * @return string
	 */
	protected function normalizeType($type)
	{
		return str_replace('/', '_', $type);
	}
	
	/**
	 * Normalize the given "date" for XML tree data.
	 *
	 * @param string $date
	 * @return string
	 */
	protected function normalizeDate($date)
	{
		return date_DateFormat::format(date_Calendar::getInstance($date), 'D d M Y, H:i', RequestContext::getInstance()->getUILang());
	}
	
	/**
	 * Sets the overwrite property.
	 *
	 * @param boolean $overwrite
	 * @return tree_parser_XmlTreeParser
	 */
	public function setOverwrite($overwrite)
	{
		$this->overwrite = ($overwrite == true);
		
		return $this;
	}
	
	/**
	 * Gets the overwrite property.
	 *
	 * @return boolean
	 */
	public function getOverwrite()
	{
		return ($this->overwrite == true);
	}
	
	/**
	 * @var Integer[]
	 */
	private $searchedIds = array();
	
	/**
	 * @return Integer[]
	 */
	private function getSearchedIds()
	{
		return $this->searchedIds;
	}
	
	/**
	 * @param Integer[] $ids
	 */
	public function setSearchedIds($ids)
	{
		$lastId = array_pop($ids);
		if ($lastId)
		{
			$document = DocumentHelper::getDocumentInstance($lastId);
			if ($document->getPersistentModel()->useCorrection() && $document->getCorrectionofid())
			{
				array_push($ids, $document->getCorrectionofid());
			}
			else
			{
				array_push($ids, $document->getId());
			}
		}
		$this->searchedIds = $ids;		
	}
}