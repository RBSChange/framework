<?php
class f_persistentdocument_PersistentTreeNode implements tree_TreeNode
{
	private $m_treeId;
	private $m_documentId;
	private $m_parentId;
	private $m_order;
	private $m_level;
	private $m_path;
	private $m_childrenCount;
	private $m_documentModel;
	
	/**
	 * @var f_persistentdocument_PersistentTreeNode[]
	 */
	private $m_children;
	
	private $m_ancestorsId;
	
	/**
	 * empty constructor
	 */
	protected function __construct()
	{
		// empty
	}
	
	function __destruct()
	{
		if ($this->m_children !== null)
		{
			$this->m_children = null;
		}
	}
	
	/**
	 * @param array<> $nodeInfo
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public static function getInstance($nodeInfo)
	{
		$node = new f_persistentdocument_PersistentTreeNode();
		$node->m_treeId = intval($nodeInfo['tree_id']);
		$node->m_documentId = intval($nodeInfo['document_id']);
		$node->m_parentId = intval($nodeInfo['parent_id']);
		$node->m_order = intval($nodeInfo['node_order']);
		$node->m_level = intval($nodeInfo['node_level']);
		$node->m_path = $nodeInfo['node_path'];
		$node->m_childrenCount = intval($nodeInfo['children_count']);
		if (isset($nodeInfo['document_model']))
		{
			$node->m_documentModel = $nodeInfo['document_model'];
		}
		return $node;
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param integer $documentId
	 * @param integer $order
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public static function getNewInstanceFromParent($parentNode, $documentId, $order = -1)
	{
		$node = new f_persistentdocument_PersistentTreeNode();
		$node->m_treeId = $parentNode->m_treeId;
		$node->m_documentId = intval($documentId);
		$node->m_parentId = $parentNode->m_documentId;
		$node->m_order = ($order === - 1) ? $parentNode->m_childrenCount : $order;
		$node->m_level = $parentNode->m_level + 1;
		$node->m_path = $parentNode->m_path . $parentNode->m_documentId . '/';
		$node->m_childrenCount = 0;
		return $node;
	}
	
	/**
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public static function getNewRootInstance($documentId)
	{
		$node = new f_persistentdocument_PersistentTreeNode();
		$node->m_treeId = intval($documentId);
		$node->m_documentId = intval($documentId);
		$node->m_parentId = 0;
		$node->m_order = 0;
		$node->m_level = 0;
		$node->m_path = '/';
		$node->m_childrenCount = 0;
		return $node;
	}
	
	/**
	 * Obtient le niveau du noeud
	 * @return Integer
	 */
	public function getLevel()
	{
		return $this->m_level;
	}
	
	/**
	 * Obtient l'id du document géré par le noeud
	 * @return Integer
	 */
	public function getId()
	{
		return $this->m_documentId;
	}
	
	/**
	 * @return Integer
	 */
	public function getTreeId()
	{
		return $this->m_treeId;
	}
	
	/**
	 * @return string
	 * @example '/123/45/'
	 */
	public function getPath()
	{
		return $this->m_path;
	}
	
	/**
	 * @return integer[]
	 */
	public function getAncestorsId()
	{
		if ($this->m_ancestorsId === null)
		{
			$this->m_ancestorsId = array();
			if ($this->m_parentId)
			{
				foreach (explode('/', $this->m_path) as $id)
				{
					if ($id !== "")
					{
						$this->m_ancestorsId[] = intval($id);
					}
				}
			}
		}
		return $this->m_ancestorsId;
	}
	
	/**
	 * Obtient le document associer au noeud
	 *
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getPersistentDocument()
	{
		return $this->getDocumentService()->getDocumentInstance($this->m_documentId);
	}
	
	/**
	 * Obtient le label du noeud
	 * @return String
	 */
	public function getLabel()
	{
		return $this->getPersistentDocument()->getTreeNodeLabel();
	}
	
	/**
	 * Obtient le model du document
	 * @example 'modules_generic/folder'
	 * @return String
	 */
	public function getDocumentModelName()
	{
		if ($this->m_documentModel === null)
		{
			$this->m_documentModel = $this->getPersistentDocument()->getDocumentModelName();
		}
		return $this->m_documentModel;
	}
	
	/**
	 * Verifie si les noeud sont identique
	 * (du point de vu de la persistance)
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return Boolean
	 */
	public function equals($node)
	{
		return $this->getTreeService()->equals($this, $node);
	}
	
	/**
	 * Test si le noeud est le noeud racine
	 *
	 * @return Boolean
	 */
	public function isRoot()
	{
		return $this->m_level == 0;
	}
	
	/**
	 * Test si le noeud possede des fils
	 *
	 * @return Boolean
	 */
	public function hasChildren()
	{
		return $this->m_childrenCount > 0;
	}
	
	/**
	 * Obtient un tableau des noeuds fils direct
	 *
	 * @return f_persistentdocument_PersistentTreeNode[]
	 */
	public function getChildren($modelName = null)
	{
		if ($this->m_children === null)
		{
			$this->getTreeService()->loadChildren($this);
		}
		if ($modelName === null)
		{
			return $this->m_children;
		}
		
		$children = array();
		foreach ($this->m_children as $child)
		{
			if ($child->getDocumentModelName() === $modelName)
			{
				$children[] = $child;
			}
		}
		return $children;
	}
	
	public function setChildren($children)
	{
		$this->m_children = $children;
		if ($children !== null && count($children) != $this->m_childrenCount)
		{
			throw new Exception('Brocken children data for node(' . $this->m_documentId . '). Setted ' . count($children) . ' => Expected ' . $this->m_childrenCount);
		}
	}
	
	public function hasLoadedChildren()
	{
		return $this->m_children !== null;
	}
	
	public function setEmpty()
	{
		$this->m_children = null;
		$this->m_childrenCount = 0;
	}
	
	/**
	 * Obtient un tableau de tous les noeuds ancetres. Le premier élément du tableau
	 * est le rootNode.
	 * @return f_persistentdocument_PersistentTreeNode[]
	 */
	public function getAncestors()
	{
		if (! $this->m_parentId)
			return array();
		return $this->getTreeService()->loadAncestors($this);
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return boolean
	 */
	public function isAncestorOf($node)
	{
		return in_array($this->getId(), $node->getAncestorsId());
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return boolean
	 */
	public function isDescendantOf($node)
	{
		return in_array($node->getId(), $this->getAncestorsId());
	}
	
	public function __toString()
	{
		return $this->asString();
	}
	
	public function asString($indent = '', $printChildren = false)
	{
		$str = $indent . 'PersitentTreeNode[treeId=' . $this->m_treeId . ' left=' . $this->m_left . ' right=' . $this->m_right . ' id=' . $this->m_documentId . ' level=' . $this->m_level . ' maxLevelLoaded=' . $this->maxChildrenLevelLoaded . ') => ' . $this->getDocumentModelName() . ' \'' . $this->getLabel() . "'\n";
		if ($printChildren)
		{
			if (! is_null($this->m_children))
			{
				foreach ($this->m_children as $child)
				{
					$str .= $child->asString($indent . '  ', $printChildren);
				}
			}
		}
		return $str;
	}
	
	/**
	 * Retourne le TreeNode associer au $documentId si celui-ci existe dans la collection des fils directs
	 * @param integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function getChildByDocumentId($documentId)
	{
		if ($this->hasChildren())
		{
			$children = $this->getChildren();
			foreach ($children as $child)
			{
				if ($documentId == $child->getId())
				{
					return $child;
				}
			}
		}
		return null;
	}
	
	/**
	 * Get the child node at the specified index.
	 *
	 * @return f_util_tree_TreeNode The TreeNode at the specified index, or null if no
	 * 	child node is at this index.
	 */
	public function getChildAt($index)
	{
		if ($index < $this->m_childrenCount)
		{
			$children = $this->getChildren();
			return $children[$index];
		}
		return null;
	}
	
	/**
	 * Get the number of child nodes.
	 *
	 * @return integer The number of child nodes.
	 */
	public function getChildCount()
	{
		return $this->m_childrenCount;
	}
	
	/**
	 * Get the index of this node in its parent node children array.
	 *
	 * @return integer Index of this node.
	 */
	public function getIndex()
	{
		return $this->m_order;
	}
	
	/**
	 * @return integer
	 */
	public function getParentId()
	{
		return $this->m_parentId;
	}
	
	/**
	 * Get the parent node.
	 * @return f_persistentdocument_PersistentTreeNode The parent node, or null if this node is the root node.
	 */
	public function getParent()
	{
		if ($this->m_parentId)
		{
			return $this->getTreeService()->getInstanceByDocumentIdInTree($this->m_parentId, $this->m_treeId);
		}
		return null;
	}
	
	/**
	 * Indicate if the node is a leaf node (with no child node) or not.
	 *
	 * @return boolean true id the node is a leaf node, false otherwise.
	 */
	public function isLeaf()
	{
		return ! $this->hasChildren();
	}
	
	/**
	 * Add a child to this node.
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 */
	public function addChild($treeNode)
	{
		$this->m_childrenCount = $this->m_childrenCount + 1;
		if ($this->m_children !== null)
		{
			$this->m_children[] = $treeNode;
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 */
	public function moveTo($node)
	{
		$this->m_parentId = $node->m_documentId;
		$this->m_order = $node->m_childrenCount;
		$this->m_level = $node->m_level + 1;
		$this->m_path = $node->m_path . $node->m_documentId . '/';
		$this->m_ancestorsId = null;
		$node->addChild($this);
	}
	
	/**
	 * @param integer $order
	 */
	public function reOrder($order)
	{
		$this->m_order = $order;
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 */
	public function insertChildAt($treeNode)
	{
		$this->m_childrenCount = $this->m_childrenCount + 1;
		if ($this->m_children !== null)
		{
			$children = array();
			$idx = 0;
			foreach ($this->m_children as $child)
			{
				if ($child->m_order == $treeNode->m_order)
				{
					$children[] = $treeNode;
					$idx ++;
				}
				$child->m_order = $idx;
				$children[] = $child;
				$idx ++;
			}
			$this->m_children = $children;
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 */
	public function removeChild($node)
	{
		$this->m_childrenCount = $this->m_childrenCount - 1;
		if ($this->m_children !== null)
		{
			$documentid = $node->getId();
			$children = array();
			$idx = 0;
			foreach ($this->m_children as $child)
			{
				if ($child->getId() == $documentid)
				{
					continue;
				}
				$child->m_order = $idx;
				$children[] = $child;
				$idx ++;
			}
			$this->m_children = $children;
		}
	}
	
	/**
	 * @return TreeService
	 */
	private function getTreeService()
	{
		return TreeService::getInstance();
	}
	
	/**
	 * @return f_persistentdocument_DocumentService
	 */
	private function getDocumentService()
	{
		return f_persistentdocument_DocumentService::getInstance();
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use TreeService::getInstance()->getInstanceByDocument
	 */
	public static function getInstanceByDocument($document)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::warn('DEPRECATED CALL :' . __METHOD__);
			//Framework::warn(f_util_ProcessUtils::getBackTrace());
		}
		return TreeService::getInstance()->getInstanceByDocument($document);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use TreeService::getInstance()->getInstanceByDocumentId
	 */
	public static function getInstanceByDocumentId($documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::warn('DEPRECATED CALL :' . __METHOD__);
			//Framework::warn(f_util_ProcessUtils::getBackTrace());
		}
		return TreeService::getInstance()->getInstanceByDocumentId($documentId);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use TreeService::getInstance()->loadDescendants
	 */
	public function getDescendents($level = -1)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::warn('DEPRECATED CALL :' . __METHOD__);
			//Framework::warn(f_util_ProcessUtils::getBackTrace());
		}
		
		return $this->getTreeService()->loadDescendants($this, $level);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use TreeService::getInstance()->loadDescendants
	 */
	public function getDescendentsByModelNames($level = -1, $modelNames = null)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::warn('DEPRECATED CALL :' . __METHOD__);
			//Framework::warn(f_util_ProcessUtils::getBackTrace());
		}
		return $this->getTreeService()->loadDescendants($this, $level);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use TreeService::getInstance()->newLastChildForNode
	 */
	public function newLastChild($document)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::warn('DEPRECATED CALL :' . __METHOD__);
			//Framework::warn(f_util_ProcessUtils::getBackTrace());
		}
		return $this->getTreeService()->newLastChildForNode($this, $document->getId());
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use TreeService::getInstance()->deleteNode
	 */
	public function deleteNode($node)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::warn('DEPRECATED CALL :' . __METHOD__);
			//Framework::warn(f_util_ProcessUtils::getBackTrace());
		}
		$this->getTreeService()->deleteNode($node);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use TreeService::getInstance()->newLastChildForNode
	 */
	public static function addNewChild($parentNode, $document)
	{
		return TreeService::getInstance()->newLastChildForNode($parentNode, $document->getId());
	}
}