<?php
/**
 * <{$module}>_<{$name}>Action
 * @package modules.<{$module}>.actions
 */
class <{$module}>_<{$name}>Action extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 * @return string
	 */
	public function _execute($context, $request)
	{
		$result = array();

		// Write your code here to set content in $result.

		return $this->sendJSON($result);
	}
}