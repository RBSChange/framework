<?php
interface tree_TreeNode
{
	/**
	 * Get the child nodes of the current node.
	 * 
	 * @return array Array of child nodes.
	 */
	public function getChildren();
	
	/**
	 * Get the child node at the specified index.
	 * 
	 * @return tree_TreeNode The TreeNode at the specified index, or null if no
	 * 	child node is at this index.
	 */
	public function getChildAt($index);
	
	/**
	 * Get the number of child nodes.
	 * 
	 * @return integer The number of child nodes.
	 */
	public function getChildCount();
	
	/**
	 * Get the index of this node in its parent node children array.
	 * 
	 * @return integer Index of this node.
	 */
	public function getIndex();
	
	/**
	 * Get the parent node.
	 * 
	 * @return tree_TreeNode The parent node, or null if this node is the root node.
	 */
	public function getParent();
	
	/**
	 * Indicate if the node is a leaf node (with no child node) or not.
	 * 
	 * @return boolean true id the node is a leaf node, false otherwise.
	 */
	public function isLeaf();
	
	/**
	 * Indicate if the node is the root node or not.
	 * 
	 * @return boolean true if the node is the root node (has no parent node).
	 */
	public function isRoot();
	
	/**
	 * Get the level if this node.
	 * 
	 * @return integer Level of this node, root node has level 0.
	 */
	public function getLevel();
	
	/**
	 * Add a child to this node.
	 * 
	 * @param tree_TreeNode $treeNode The TreeNode to add has a child of the current node.
	 * 		The current node becomes its parent node.
	 * @return integer The number of child nodes.
	 */
	public function addChild($treeNode);
}