<?php
interface tree_TreeModel
{
	/**
	 * Enter description here...
	 * @return tree_TreeNode
	 */
	public function getRoot();
	
	/**
	 * Enter description here...
	 *
	 * @param tree_TreeNode $treeNode
	 * @return Boolean
	 */
	public function isLeaf($treeNode);
	
	/**
	 * Enter description here...
	 *
	 * @param tree_TreeNode $treeNode
	 * @return Integer
	 */
	public function getChildCount($treeNode);
	
}