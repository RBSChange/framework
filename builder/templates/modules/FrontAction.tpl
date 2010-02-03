<?php
/**
 * <{$module}>_<{$name}>Action
 * @package modules.<{$module}>.actions
 */
class <{$module}>_<{$name}>Action extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		return self::getSuccessView();
	}
}