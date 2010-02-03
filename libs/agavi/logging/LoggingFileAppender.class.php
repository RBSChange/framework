<?php
/**
 * Auto-generated doc comment
 * @package framework.logging
 */
class logging_FileAppender extends FileAppender
{
	protected function _getHandle()
	{
		if (!file_exists($this->_filename))
		{
			if (!touch($this->_filename))
			{
				throw new Exception("Could not create ".$this->_filename);
			}
			if (!chmod($this->_filename, 0775))
			{
				throw new Exception("Could not chmod 775 ".$this->_filename);
			}
		}
		if (!is_writeable($this->_filename))
		{
			throw new Exception("Can not write to ".$this->_filename);
		}
		return parent::_getHandle();
	}
}