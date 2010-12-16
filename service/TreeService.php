<?php
/**
 * @package framework.service
 * TreeService
 */
class TreeService extends BaseService
{
	/**
	 * the singleton instance
	 * @var TreeService
	 */
	private static $instance = null;

	/**
	 * @var boolean
	 */
	private $useTreeNodeCache = true;
	
	/**
	 * Tree instances by id
	 * @var array<Integer, f_persistentdocument_PersistentTreeNode>
	 */
	private $persistentTreeNodes = array();
	
	/**
	 * @return TreeService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param boolean $useTreeNodeCache
	 * @return TreeService
	 */
	public function setTreeNodeCache($useTreeNodeCache)
	{
		$this->useTreeNodeCache = $useTreeNodeCache;
		if (!$this->useTreeNodeCache)
		{
			$this->clearTreeNodeCache();
		}
		return $this;
	}
	
	/**
	 * @return void
	 */
	private function clearTreeNodeCache($documentId = null)
	{
		if ($documentId === null)
		{
			unset($this->persistentTreeNodes);
			$this->persistentTreeNodes = array();
		}
		else
		{
			unset($this->persistentTreeNodes[$documentId]);
		}
	}
	
	private function clearTreeNodeCacheByParentId($parentId)
	{
		foreach ($this->persistentTreeNodes as $documentId => $treeNode) 
		{
			if ($treeNode->getParentId() == $parentId)
			{
				$this->clearTreeNodeCache($documentId);
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	private function putTreeNodeInCache($treeNode)
	{
		if ($this->useTreeNodeCache)
		{
			$this->persistentTreeNodes[$treeNode->getId()] = $treeNode;
		}
		return $treeNode;
	}

	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode or null
	 */
	private function getTreeNodeFromCache($documentId)
	{
		if ($this->useTreeNodeCache && isset($this->persistentTreeNodes[$documentId]))
		{
			return $this->persistentTreeNodes[$documentId];
		}
		return null;
	}
		
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getParentDocument($document)
	{
		if (!$document->getTreeId()) {return null;}
		$node = $this->getTreeNodeFromCache($document->getId());
		if ($node === null)
		{
			$node = $this->getInstanceByDocumentIdAndTreeId($document->getId(), $document->getTreeId());
		}
		
		if ($node === null  || !$node->getParentId()) {return null;}
		return $this->getPersistentProvider()->getDocumentInstance($node->getParentId());
	}
	
	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function getInstanceByDocumentId($documentId)
	{
		$node = $this->getTreeNodeFromCache($documentId);
		if ($node !== null) {return $node;}
		
		$document = $this->getPersistentProvider()->getDocumentInstance($documentId);
		return $this->getInstanceByDocumentIdAndTreeId($document->getId(), $document->getTreeId());
	}
	

	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function getInstanceByDocumentIdInTree($documentId, $treeId)
	{
		$node = $this->getTreeNodeFromCache($documentId);
		if ($node !== null) {return $node;}
		return $this->getInstanceByDocumentIdAndTreeId($documentId, $treeId);
	}

	/**
	 * Obtient le noeud racine de l'arbre $treeId
	 * @param String $treeId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function getRootNode($treeId)
	{
		return $this->getInstanceByDocumentIdInTree($treeId, $treeId);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentTreeNode | null
	 */
	public function getInstanceByDocument($document)
	{
		$node = $this->getTreeNodeFromCache($document->getId());
		if ($node !== null) {return $node;}
		return $this->getInstanceByDocumentIdAndTreeId($document->getId(), $document->getTreeId());
	}
	
	/**
	 * @param Integer $documentId
	 * @param Integer $treeId
	 * @return f_persistentdocument_PersistentTreeNode | null
	 */
	protected function getInstanceByDocumentIdAndTreeId($documentId, $treeId)
	{
		if (intval($treeId) <= 0)  {return null;}
		$info = $this->getPersistentProvider()->getNodeInfo($documentId, intval($treeId));
		if ($info === null) {return null;}
		$node = f_persistentdocument_PersistentTreeNode::getInstance($info);
		$this->putTreeNodeInCache($node);
		return $node;
	}

	/**
	 * Creer un nouvelle arbre
	 * @param String $treeId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function setRootNode($treeId)
	{	
		$rootNode = f_persistentdocument_PersistentTreeNode::getNewRootInstance($treeId);
		$this->putTreeNodeInCache($rootNode);
		return $this->getPersistentProvider()->createTree($rootNode);
	}

	/**
	 * Efface un arbre
	 * @param Integer $treeId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function clearRootNode($treeId)
	{
		$rootNode = $this->getRootNode($treeId);
		if ($rootNode !== null)
		{
			$this->getPersistentProvider()->clearTree($rootNode);	
		}
		return $rootNode;
	}

	/**
	 * Supression du noeud
	 * @param Integer $nodeId
	 */
	public function deleteNodeById($nodeId)
	{
		$treeNode = $this->getInstanceByDocumentId($nodeId);
		if ($treeNode !== null)
		{
			$this->deleteNode($treeNode);
		}
	}

	/**
	 * Supression du noeud
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 */
	public function deleteNode($treeNode)
	{
		if ($treeNode->hasChildren())
		{
			$baseKey = str_replace(array('_', '/'), array('.', '.errors.'), $treeNode->getDocumentModelName());
			throw new BaseException("cannot-delete-non-empty-node", $baseKey .".Cannot-delete-non-empty-node", array('id' => $treeNode->getId()));
		}
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			$this->getPersistentProvider()->deleteEmptyNode($treeNode);
			if ($this->useTreeNodeCache)
			{
				$this->clearTreeNodeCache();
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
	}

	/**
	 * Supression du noeud
	 * @param Integer $nodeId
	 */
	public function deleteNodeRecursivelyById($nodeId)
	{
		$treeNode = $this->getInstanceByDocumentId($nodeId);
		if ($treeNode !== null)
		{
			$this->deleteNodeRecursively($treeNode);
		}
	}
	
	/**
	 * Supression du noeud et de tous ses descendants.
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 */
	public function deleteNodeRecursively($treeNode)
	{
		if (!$treeNode->hasChildren()) 
		{
			$this->deleteNode($treeNode);
			return;
		}

		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			$ids = $this->getPersistentProvider()->deleteNodeRecursively($treeNode);
			if ($this->useTreeNodeCache)
			{
				$this->clearTreeNodeCache();
			}	
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * Ajoute un noeud a la fin de la liste des fils
	 *
	 * @param Integer $parentNodeId
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function newLastChild($parentNodeId, $documentId)
	{
		return $this->newLastChildForNode($this->getInstanceByDocumentId($parentNodeId), $documentId);
	}
	
	/**
	 * Ajoute un noeud a la fin de la liste des fils
	 *
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function newLastChildForNode($parentNode, $documentId)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			$childNode = f_persistentdocument_PersistentTreeNode::getNewInstanceFromParent($parentNode, $documentId);
			$this->putTreeNodeInCache($childNode);
			$this->getPersistentProvider()->appendChildNode($parentNode, $childNode);
			$tm->commit();
			
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
		return $childNode;
	}

	/**
	 * Ajoute un noeud au debut de la liste des fils
	 *
	 * @param Integer $parentNodeId
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function newFirstChild($parentNodeId, $documentId)
	{
		return $this->newChildAtForNode($this->getInstanceByDocumentId($parentNodeId), $documentId, 0);
	}

	/**
	 * Ajoute un noeud a la fin de la liste des fils
	 *
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param integer $documentId
	 * @param integer $order
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function newChildAtForNode($parentNode, $documentId, $order = 0)
	{
		if ($order < 0 || $parentNode->getChildCount() <= $order) 
		{
			return $this->newLastChildForNode($parentNode, $documentId);
		}

		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			$childNode = f_persistentdocument_PersistentTreeNode::getNewInstanceFromParent($parentNode, $documentId, $order);			
			$this->getPersistentProvider()->insertChildNodeAtOrder($parentNode, $childNode);
			if ($this->useTreeNodeCache)
			{
				$this->clearTreeNodeCache();
			}
			$tm->commit();			
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
		return $childNode;
	}
	
	/**
	 * Verifie si les noeud sont identique
	 * (du point de vu de la persistance)
	 * @param f_persistentdocument_PersistentTreeNode $node1
	 * @param f_persistentdocument_PersistentTreeNode $node2
	 * @return Boolean
	 */
	public function equals($node1, $node2)
	{
		if ($node1 === $node2) {return true;}
		if (($node1 instanceof f_persistentdocument_PersistentTreeNode) && ($node2 instanceof f_persistentdocument_PersistentTreeNode))
		{
			if ($node1->getId() == $node2->getId() && $node1->getTreeId() == $node2->getTreeId())
			{
				return true;
			}
		}
		return false;
	}
		
	/**
	 * @param integer $documentId
	 * @param integer $destId
	 */
	public function moveToLastChild($documentId, $destId)
	{
		$movedNode = $this->getInstanceByDocumentId($documentId);
		if ($movedNode === null)
		{
			throw new Exception('Invalid moved node');
		}
	
		$destNode = $this->getInstanceByDocumentIdInTree($destId, $movedNode->getTreeId());
		if ($destNode === null)
		{
			throw new Exception('Destination node is not in tree');
		}
		
		$this->moveToLastChildNode($movedNode, $destNode);
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $movedNode
	 * @param f_persistentdocument_PersistentTreeNode $destNode
	 */
	public function moveToLastChildNode($movedNode, $destNode)
	{
		if ($movedNode->getTreeId() != $destNode->getTreeId())
		{
			throw new Exception('Moved node is not in the same tree of destination node');
		}	
		
		if ($movedNode->isAncestorOf($destNode))
		{
			throw new Exception('Moved node is ancestor of destination node');
		}
	
		$parentNode = $this->getInstanceByDocumentIdInTree($movedNode->getParentId(), $movedNode->getTreeId());
		$this->getPersistentProvider()->moveNode($parentNode, $movedNode, $destNode);		
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $movedNode
	 * @param integer $beforeId
	 */
	public function moveToPrevSiblingForNode($movedNode, $beforeId)
	{
		$siblingNode = $this->getInstanceByDocumentIdInTree($beforeId, $movedNode->getTreeId());
		if ($siblingNode === null) { return; }
		if ($movedNode->getParentId() != $siblingNode->getParentId())
		{
			$this->moveToLastChildNode($movedNode, $siblingNode->getParent());
		}
		
		$parentNode = $this->getInstanceByDocumentIdInTree($siblingNode->getParentId(), $movedNode->getTreeId());
		$originalChildren = $parentNode->getChildren();
		$sortChildren = array();
		$children = array();
		$idx = 0;
		foreach ($originalChildren as $child) 
		{
			if ($movedNode->getId() == $child->getId()) {continue;}
			if ($beforeId == $child->getId())
			{
				$children[] = $movedNode;
				if ($movedNode->getIndex() != $idx)
				{
					$sortChildren[] = $movedNode;
					$movedNode->reOrder($idx);
				}	
				$idx++;
			}
			$children[] = $child;
			if ($child->getIndex() != $idx)
			{
				$sortChildren[] = $child;
				$child->reOrder($idx);
			}
			$idx++;
		}
		
		if (count($sortChildren) > 0)
		{
			$this->getPersistentProvider()->orderNodes($parentNode, $sortChildren);
			$parentNode->setChildren($children);
		}
	}
	
	public function moveToNextSiblingForNode($movedNode, $afterId)
	{
		$siblingNode = $this->getInstanceByDocumentIdInTree($afterId, $movedNode->getTreeId());
		if ($siblingNode === null) { return; }
		if ($movedNode->getParentId() != $siblingNode->getParentId())
		{
			$this->moveToLastChildNode($movedNode, $siblingNode->getParent());
		}
		
		$parentNode = $this->getInstanceByDocumentIdInTree($siblingNode->getParentId(), $movedNode->getTreeId());
		$originalChildren = $parentNode->getChildren();
		$sortChildren = array();
		$children = array();
		$idx = 0;
		foreach ($originalChildren as $child) 
		{
			if ($movedNode->getId() == $child->getId()) {continue;}
			$children[] = $child;
			if ($child->getIndex() != $idx)
			{
				$sortChildren[] = $child;
				$child->reOrder($idx);
			}
			$idx++;			
			if ($afterId == $child->getId())
			{
				$children[] = $movedNode;
				if ($movedNode->getIndex() != $idx)
				{
					$sortChildren[] = $movedNode;
					$movedNode->reOrder($idx);
				}	
				$idx++;
			}
		}
		
		if (count($sortChildren) > 0)
		{
			$this->getPersistentProvider()->orderNodes($parentNode, $sortChildren);
			$parentNode->setChildren($children);
		}		
	}
	
	/**
	 * Order "brother" nodes
	 * @param f_persistentdocument_PersistentTreeNode $parentNode
	 * @param array<Integer, Integer> $childrenOrder (id => order)
	 */
	public function order($parentNode, $childrenOrder)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
			$originalChildren = $parentNode->getChildren();
			
			// add missing nodes to $childrenOrder
			$childrenTmp = array_flip($childrenOrder);
			foreach ($originalChildren as $child)
			{
				if (array_search($child->getId(), $childrenTmp) === false)
				{
					$childrenTmp = $this->insertInArray($childrenTmp, $child->getIndex(), $child->getId());
				}
			}
			$childrenOrder = array_flip($childrenTmp);
			
			//
			foreach ($childrenOrder as $childId => $order)
			{
				$newChildren[$order] = $this->getNode($originalChildren, $childId);
			}
			ksort($newChildren);

			$nodes = array();
			foreach ($newChildren as $order => $node) 
			{
				if ($node->getIndex() != $order)
				{
					$nodes[] = $node;
					$node->reOrder($order);
				}
			}
			
			if (count($nodes) > 0)
			{
				$this->getPersistentProvider()->orderNodes($parentNode, $nodes);
				$parentNode->setChildren(array_values($newChildren));
			}
			
			$tm->commit();
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			$tm->rollBack();
			throw $e;
		}
		
		f_event_EventManager::dispatchEvent('persistentTreeNodeOrdered', $this, array('parentNode' => $parentNode, 'childrenOrder' => $childrenOrder));
	}
	
	private function insertInArray($array, $insertIndex, $element)
	{
		if (!array_key_exists($insertIndex, $array))
		{
			$array[$insertIndex] = $element;
			return $array;
		}
		
		$newArray = array($insertIndex => $element);
		$delta = 0;
		foreach ($array as $index => $elem)
		{
			if ($index == $insertIndex)
			{
				$delta = 1;
			}
			$newArray[$index+$delta] = $elem;
		}
		return $newArray;
	}

	/**
	 * @param PersistentTreeNode[] $treeNodes
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	private function getNode($treeNodes, $documentId)
	{
		foreach ($treeNodes as $node)
		{
			if ($node->getId() == $documentId)
			{
				return $node;
			}
		}
		throw new Exception("Could not find node $documentId");
	}

	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return f_persistentdocument_PersistentTreeNode[]
	 */
	public function loadChildren($node)
	{
		$infos = $this->getPersistentProvider()->getChildrenNodesInfo($node);
		$children = array();
		foreach ($infos as $info) 
		{
			$n = $this->getTreeNodeFromCache($info['document_id']);
			if ($n === null)
			{
				$n = f_persistentdocument_PersistentTreeNode::getInstance($info);
				$this->putTreeNodeInCache($n);
			}
			$children[] = $n;			
		}
		$node->setChildren($children);
		return $node->getChildren();
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return f_persistentdocument_PersistentTreeNode[]
	 */
	public function loadDescendants($node, $deep = -1)
	{
		if ($deep === 1) {return $this->loadChildren($node);}
		
		$infos = $this->getPersistentProvider()->getDescendantsNodesInfo($node, $deep);	
		$children = array();
		$allNodes = array($node->getId() => $node);
		
		foreach ($infos as $info) 
		{
			$cn = $this->getTreeNodeFromCache($info['document_id']);
			if ($cn === null)
			{
				$cn = f_persistentdocument_PersistentTreeNode::getInstance($info);
				$this->putTreeNodeInCache($cn);
			}
			$allNodes[$cn->getId()] = $cn;
			
			$pid = $cn->getParentId();
			if (!isset($children[$pid])) {$children[$pid] = array();}		
			$children[$pid][] = $cn;				
		}
		foreach ($children as $pid => $array) 
		{
			$pn = $allNodes[$pid];
			$pn->setChildren($array);
		}
		return $node->getChildren();
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $node
	 */
	public function loadAncestors($node)
	{
		$ids = $node->getAncestorsId();
		if (count($ids) === 0) return array();	
		if ($this->useTreeNodeCache)
		{
			$loadIds = array();		
			foreach ($ids as $id) {if (!isset($this->persistentTreeNodes[$id])) {$loadIds[] = $id;}}
		}
		else
		{
			$loadIds = $ids;
		}
		if (count($loadIds) > 0)
		{
			$nodesInfo = $this->getPersistentProvider()->getNodesInfo($loadIds, $node->getTreeId());
		}
		
		$result = array(); 
		foreach ($ids as $id) 
		{
			$cn = $this->getTreeNodeFromCache($id);
			if ($cn === null)
			{
				$cn = f_persistentdocument_PersistentTreeNode::getInstance($nodesInfo[$id]);
				$this->putTreeNodeInCache($cn);
			}
			$result[] = $cn;
		}
		return $result;
	}
	
	/**
	 * @param f_persistentdocument_PersistentTreeNode $treeNode
	 * @param Integer $ancestorId
	 */
	public function isAncestor($treeNode, $ancestorId)
	{
		return in_array($ancestorId, $treeNode->getAncestorsId());
	}
	
	/**
	 * @param Integer $rootFolderId
	 * @param String $dateString
	 *
	 * @return generic_persistentdocument_folder
	 */
	public function getFolderOfDate($rootFolderId, $dateString = null)
	{
		$dateCalendar = date_Calendar::getInstance($dateString);

		$pp = $this->getPersistentProvider();

		// Search if folders exist
		$folderDay = $pp->createQuery('modules_generic/folder')
		->add(Restrictions::descendentOf($rootFolderId))
		->add(Restrictions::eq('label', date_DateFormat::format($dateCalendar, 'Y-m-d')))
		->findUnique();

		if ( is_null($folderDay) )
		{
			$this->folderService = generic_FolderService::getInstance();

			// Year folder
			$folderYear = $pp->createQuery('modules_generic/folder')
			->add(Restrictions::childOf($rootFolderId))
			->add(Restrictions::eq('label', date_DateFormat::format($dateCalendar, 'Y')))
			->findUnique();

			if ( is_null($folderYear) )
			{
				// Create the year, month and the day folders
				$folderYear = $this->createFolder($rootFolderId, date_DateFormat::format($dateCalendar, 'Y'));
				$folderMonth = $this->createFolder($folderYear->getId(), date_DateFormat::format($dateCalendar, 'Y-m'));
				$folderDay = $this->createFolder($folderMonth->getId(), date_DateFormat::format($dateCalendar, 'Y-m-d'));
			}
			else
			{
				// Month folder
				$folderMonth = $pp->createQuery('modules_generic/folder')
				->add(Restrictions::childOf($folderYear->getId()))
				->add(Restrictions::eq('label', date_DateFormat::format($dateCalendar, 'Y-m')))
				->findUnique();

				if ( is_null($folderMonth) )
				{
					// Create the month and the day folders
					$folderMonth = $this->createFolder($folderYear->getId(), date_DateFormat::format($dateCalendar, 'Y-m'));
					$folderDay = $this->createFolder($folderMonth->getId(), date_DateFormat::format($dateCalendar, 'Y-m-d'));
				}
				else
				{
					$folderDay = $this->createFolder($folderMonth->getId(), date_DateFormat::format($dateCalendar, 'Y-m-d'));
				}
			}
		}

		return $folderDay;
	}


	/**
	 * @param Integer $parentFolderId
	 * @param String $label
	 * @return generic_persistentdocument_folder
	 */
	private function createFolder($parentFolderId, $label)
	{
		$folder = generic_FolderService::getInstance()->getNewDocumentInstance();
		$folder->setLabel($label);
		$folder->save($parentFolderId);
		return $folder;
	}	
	
    /*
     * *********************************************************************************
     */	
}