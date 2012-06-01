<?php
/* @var $arguments array */
$arguments = isset($arguments) ? $arguments : array();

if (!count($arguments))
{
	Framework::error(__FILE__ . " invalid arguments " . implode(', ', $arguments));
	echo 'ERROR';
}
else
{
	$cmd = $arguments[0];
	$ls = LocaleService::getInstance();
	if ($cmd === 'reset' || $cmd === 'init')
	{
		list(, $modelName, $lastId) = $arguments;
		$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
		if ($model->getName() !== $modelName || !$model->isLocalized())
		{
			echo 'OK';
			exit(0);
		}
		
		$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery($modelName, false);
		$ids = $query->add(Restrictions::gt('id', $lastId))
				  ->addOrder(Order::asc('id'))
				  ->setMaxResults(100)
				  ->setProjection(Projections::property('id', 'id'))
				  ->findColumn('id');
		
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
			foreach ($ids as $id)
			{
				$lastId = $id;
				if ($cmd === 'init')
				{
					$ls->initSynchroForDocumentId($lastId);
				}
				else
				{
					$ls->resetSynchroForDocumentId($lastId);
				}
			}
			$tm->commit();
		} 
		catch (Exception $e) 
		{
			$tm->rollback($e);
		}

		if (count($ids) === 100)
		{
			echo f_util_ArrayUtils::lastElement($ids);
		}
		else
		{	
			echo 'OK';
		}
	}
	elseif ($cmd === 'synchro')
	{
		$ids = $ls->getDocumentIdsToSynchronize();
		foreach ($ids as $id)
		{
			$ls->synchronizeDocumentId($id);
		}
		
		if (count($ids))
		{
			echo count($ids);
		}
		else
		{
			echo 'OK';
		}
	}
}