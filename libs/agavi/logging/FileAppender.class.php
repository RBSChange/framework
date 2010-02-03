<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2005  Sean Kerr.                                       |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

/**
 *
 *
 * @package    agavi
 * @subpackage logging
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @copyright (c) Sean Kerr, {@link http://www.mojavi.org}
 * @since     0.9.0
 * @version   $Id: FileAppender.class.php 87 2005-06-03 21:19:23Z bob $
 */
class FileAppender extends Appender
{

	protected $_handle = null;
	protected $_filename = '';

	/**
	 * Initialize the FileAppender.
	 *
	 * @param array An array of parameters.
	 *
	 * @return void
	 *
	 * @author Bob Zoller (bob@agavi.org)
	 * @since 0.9.1
	 */
	public function initialize($params)
	{
		if (isset($params['file'])) {
			$this->_filename = ConfigHandler::replaceConstants ($params['file']);
		}
	}

	/**
	 * Retrieve the file handle for this FileAppender.
	 *
	 * @throws <b>LoggingException</b> if file cannot be opened for appending.
	 *
	 * @return integer
	 *
	 * @author Bob Zoller (bob@agavi.org)
	 * @since 0.9.1
	 */
	protected function _getHandle()
	{
		if (is_null($this->_handle)) {
			if (!$this->_handle = fopen($this->_filename, 'a')) {
				throw new LoggingException("Cannot open file ({$this->_filename})");
			}
		}
		return $this->_handle;
	}

	/**
	 * Execute the shutdown procedure.
	 *
	 * If open, close the filehandle.
	 *
	 * return void
	 *
	 * @author Bob Zoller (bob@agavi.org)
	 * @since 0.9.1
	 */
	public function shutdown()
	{
		if (!is_null($this->_handle)) 
		{
			@fclose($this->_handle);
		}
		$this->_handle = null;
	}

	/**
	 * Write a Message to the file.
	 *
	 * @param Message
	 *
	 * @throws <b>LoggingException</b> if no Layout is set or the file
	 *         cannot be written.
	 *
	 * @return void
	 *
	 * @author Bob Zoller (bob@agavi.org)
	 * @since 0.9.1
	 */
	function write (&$data)
	{
		if ($layout = $this->getLayout() === null) 
		{
			throw new LoggingException('No Layout set');
		}

		$str  = $this->getLayout()->format($data)."\n";

		if (fwrite($this->_getHandle(), $str) === FALSE) 
		{
			throw new LoggingException("Cannot write to file ({$this->_filename})");
		}
	}

	/**
	 * Set the layout.
	 *
	 * @param Layout A Layout instance.
	 *
	 * @return Appender
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function setLayout ($layout)
	{
		parent::setLayout($layout);
		return $this;
	}
}

?>
