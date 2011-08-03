<?php
/**
 * <{$module}>_<{$name}>Action
 * @package modules.<{$module}>.actions
 */
class <{$module}>_<{$name}>Action extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 * @return string
	 */
	public function _execute($context, $request)
	{
		return change_View::NONE;
	}
}