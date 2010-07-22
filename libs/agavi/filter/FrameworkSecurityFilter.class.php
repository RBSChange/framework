<?php
/**
 * @package framework.libs.agavi.filter
 */
class FrameworkSecurityFilter extends SecurityFilter
{

	public function execute ($filterChain)
	{

		// get the cool stuff
		$context    = $this->getContext();
		$controller = $context->getController();
		$request    = $context->getRequest();
		$user       = $context->getUser();

		// get the current action instance
		$actionEntry    = $controller->getActionStack()->getLastEntry();
		$actionInstance = $actionEntry->getActionInstance();



		// credentials can be anything you wish; a string, array, object, etc.
		// as long as you add the same exact data to the user as a credential,
		// it will use it and authorize the user as having the credential
		//
		// NOTE: the nice thing about the Action class is that getCredential()
		//       is vague enough to describe any level of security and can be
		//       used to retrieve such data and should never have to be altered
		if ($user->isAuthenticated())
		{
			$allowed=false;
			// the user is authenticated
			// get the credential required for this action
			if (method_exists($actionInstance,"hasCredential"))
			{
				$allowed = $actionInstance->hasCredential();
			}
			else
			{
				$credential = $actionInstance->getCredential();
				if ($credential === null || $user->hasCredential($credential))
				{
					$allowed = true;
				}
			}

			if ($allowed)
			{

				// the user has access, continue
				$filterChain->execute();

			}
			else
			{

				// the user doesn't have access, exit stage left
				$controller->forward(AG_SECURE_MODULE, AG_SECURE_ACTION);

			}

		} else
		{

			// the user is not authenticated
			$controller->forward(AG_LOGIN_MODULE, AG_LOGIN_ACTION);

		}

	}

}

?>
