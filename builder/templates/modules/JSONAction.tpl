<?php
/**
 * <{$module}>_<{$name}>Action
 * @package modules.<{$module}>.actions
 */
class <{$module}>_<{$name}>Action extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		// Write your code here to set content in $result.

		return $this->sendJSON($result);
	}
}