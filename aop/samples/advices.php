<?php
class f_aop_samples_BeforeAdvice
{
	/**
	 * @return void
	 */
	function log()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("Entering ".__METHOD__);
		}
	}
}

class f_aop_samples_AfterReturningAdvice
{
	/**
	 * @param mixed $_returnValue
	 * @return void
	 */
	function log($_returnValue)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("Terminating ".__METHOD__." returning ".$_returnValue);
		}
	}
}

class f_aop_samples_AfterAdvice
{
	/**
	 * @param mixed $_returnValue
	 * @param Boolean $_hasThrowed
	 * @param Exception $_exception
	 * @return void
	 */
	function log($_returnValue, $_hasThrowed, $_exception)
	{
		if (Framework::isDebugEnabled())
		{
			if ($_hasThrowed)
			{
				Framework::debug("Terminating ".__METHOD__." throwing ".get_class($_exception).": ".$_exception->getMessage());
			}
			else
			{
				Framework::debug("Terminating ".__METHOD__." normaly returning ".$_returnValue);
			}
		}
	}
}

class f_aop_samples_AfterThrowingAdvice
{
	/**
	 * @param Exception $_exception
	 * @return mixed
	 */
	function recover($_exception)
	{
		if (Framework::isWarnEnabled())
		{
			Framework::warn(__METHOD__." throwed an ".get_class($_exception)." exception. Try to recover");
		}
		if ($_exception instanceof MyRecoverableException)
		{
			return "MyRecoverableValue";
		}
		Framework::error(__METHOD__." unrecoverable exception ".get_class($_exception));
		// original exception will be re-throwed
	}
}

class f_aop_samples_BenchAdvice
{
	function beforeExecute($message, $benchType)
	{
		Framework::startBench();
	}

	function execute($_hasThrowed, $_exception, $_returnValue, $message, $benchType)
	{
		Framework::endBench($benchType." ".__METHOD__." ".$message);
	}
}

class f_aop_samples_SimpleDocumentCacheAdvice
{
	function beforeExecute($keyParameters, $dependencies)
	{
		$simpleCacheEnabled = f_SimpleCache::isEnabled();
		if ($simpleCacheEnabled)
		{
			$simpleCache = new f_SimpleCache(__METHOD__, $keyParameters, $dependencies);
			if ($simpleCache->exists("return"))
			{
				$return = $simpleCache->readFromCache("return");
				if ($return !== false)
				{
					return DocumentHelper::getDocumentInstance($return);
				}
			}
		}
	}
	
	function execute($_hasThrowed, $_exception, $_returnValue)
	{
		if (!$_hasThrowed && $simpleCacheEnabled)
		{
			$simpleCache->writeToCache("return", $_returnValue->getId());
		}
	}
}

class f_aop_samples_ArroundAdvice
{
	function beforeSave()
	{
		// This is before Save
		if (Framework::isDebugEnabled())
		{
			Framework::debug("Entering ".__METHOD__);
		}
	}

	function save($_hasThrowed, $_exception, $_returnValue)
	{
		if ($_hasThrowed)
		{
			if (Framework::isWarnEnabled())
			{
				Framework::warn(__METHOD__." throwed an exception. Try to recover");
			}
			if ($_exception instanceof MyException)
			{
				Framework::info(__METHOD__." recovered");
				// MyException is recoverable
				return "MyRecoverableValue";
			}
			Framework::error(__METHOD__." unrecoverable");
			// you must manage the exception and re-throw it yourself
			throw $_exception;
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug("Terminating ".__METHOD__." returning ".$_returnValue);
		}
		return $_returnValue;
	}
}

/**
 * This advice adds the management of TrueCriterion
 */
class f_aop_samples_BeforeProcessCriterions
{
	function processCriterion($criterion, $query, $qBuilder)
	{
		if ($criterion instanceof f_aop_samples_TrueCriterion)
		{
			$propertyName = $criterion->getPropertyName();
			$columnName = $qBuilder->getQualifiedColumnName($propertyName);
			$key = $qBuilder->addParam($propertyName, true);
			$qBuilder->addWhere('('.$columnName.' = '.$key.')');
			return;
		}
		if ($criterion instanceof f_aop_samples_FalseCriterion)
		{
			$propertyName = $criterion->getPropertyName();
			$columnName = $qBuilder->getQualifiedColumnName($propertyName);
			$key = $qBuilder->addParam($propertyName, false);
			$qBuilder->addWhere('('.$columnName.' = '.$key.')');
			return;
		}
	}
}