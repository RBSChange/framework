<?php

interface f_mvc_Writer
{
	/**
	 * @param String $text
	 */
	function write($text);
	
	/**
	 * @return String
	 */
	function flush();

}