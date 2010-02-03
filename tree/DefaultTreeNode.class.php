<?php
class tree_DefaultTreeNode implements tree_TreeNode
{
	
	protected $childNodes = array();
	protected $parentNode = null;

	/**
	 * @return array
	 */
	public function getChildren()
	{
		return $this->childNodes;
	}
	
	/**
	 * @param Integer $index
	 * @return tree_TreeNode
	 */
	public function getChildAt($index)
	{
		if ($index >= 0 && isset($this->childNodes[$index]))
		{
			return $this->childNodes[$index];
		}
		return null;
	}
	
	/**
	 * @return Integer
	 */
	public function getChildCount()
	{
		return count($this->childNodes);
	}
	
	/**
	 * @return Integer
	 */
	public function getIndex()
	{
		if ($this->isRoot())
		{
			return 0;
		}
		$parentChildren = $this->parentNode->children();
		for ($i=0 ; $i<count($parentChildren) ; $i++)
		{
			if ($this === $parentChildren[$i])
			{
				return $i;
			}
		}
		return -1;
	}
	
	/**
	 * @return tree_TreeNode
	 */
	public function getParent()
	{
		return $this->parentNode;
	}
	
	/**
	 * @return Boolean
	 */
	public function isRoot()
	{
		return is_null($this->parentNode);
	}
	
	/**
	 * @return Boolean
	 */
	public function isLeaf()
	{
		return empty($this->childNodes);
	}
	
	/**
	 * @return Integer
	 */
	public function getLevel()
	{
		if ( ! $this->parentNode )
		{
			return 0;
		}
		return $this->parentNode->getLevel() + 1;
	}
	
	/**
	 * @return tree_TreeNode
	 */
	public function getNextSibling()
	{
		return $this->parentNode->getChildAt($this->index + 1);
	}
	
	/**
	 * @return tree_TreeNode
	 */
	public function getPreviousSibling()
	{
		return $this->parentNode->getChildAt($this->index - 1);
	}

	/**
	 * @return tree_TreeNode
	 */
	public function getRoot()
	{
		$parent = $this;
		while ( ! is_null($parent->getParent()) )
		{
			$parent = $parent->getParent();
		}
		return $parent;
	}

	/**
	 * @param tree_TreeNode $treeNode
	 */
	public function addChild($treeNode)
	{
		$treeNode->parentNode = $this;
		$treeNode->index      = count($this->childNodes);
		$this->childNodes[]   = $treeNode;
	}
	
	
	///////////////////////////////// OPTIONS //////////////////////////////////


	protected $attributes = array();
	
	/**
	 * Sets a named attribute for the node.
	 * 
	 * @param string $name Name of the attribute.
	 * @param mixed value Value of the attribute.
	 */
	public final function setAttribute($name, $value)
	{
		return $this->attributes[$name] = $value;
	}
	/**
	 * @deprecated 1.1 - 2006-05-18
	 * @see setAttribute
	 */
	public final function setOption($name, $value)
	{
		return $this->setAttribute($name, $value);
	}

	
	/**
	 * Gets a named attribute from the node.
	 * 
	 * @param string $name Name of the attribute.
	 * 
	 * @return The attribute value.
	 */
	public final function getAttribute($name)
	{
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
		return null;
	}
	/**
	 * @deprecated 1.1 - 2006-05-18
	 * @see getAttribute
	 */
	public final function getOption($name)
	{
		return $this->getAttribute($name);
	}
	
	/**
	 * Indicates whether the node has a particular attribute or not.
	 * 
	 * @return true or false
	 */
	public final function hasAttribute($name)
	{
		if (isset($this->attributes[$name])) {
			return true;
		}
		return false;
	}
	/**
	 * @deprecated 1.1 - 2006-05-18
	 * @see hasAttribute
	 */
	public final function hasOption($name)
	{
		return $this->hasAttribute($name);
	}
	

	/**
	 * Returns all the attributes defined for this node.
	 * 
	 * @return array Attributes defined for this node.
	 */	
	public final function getAttributes()
	{
		return $this->attributes;
	}
	
}