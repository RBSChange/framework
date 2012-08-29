<?php
class tree_DefaultTreeModel implements tree_TreeModel
{
	
	protected $rootNode;
	
	/**
	 * Enter description here...
	 *
	 * @param tree_TreeNode $rootNode
	 */
	public function __construct($rootNode)
	{
		$this->rootNode = $rootNode;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return tree_TreeNode
	 */
	public function getRoot()
	{
		return $this->rootNode;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param tree_TreeNode $treeNode
	 * @return boolean
	 */
	public function isLeaf($treeNode)
	{
		return $treeNode->isLeaf();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param tree_TreeNode $treeNode
	 * @return integer
	 */
	public function getChildCount($treeNode)
	{
		return $treeNode->getChildCount();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param tree_TreeNode $treeNode
	 * @return integer
	 */
	public function getIndex($treeNode)
	{
		return $treeNode->getIndex();
	}
}