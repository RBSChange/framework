<?php
class f_aop_samples_AnOtherClassReplacement extends f_aop_samples_AnOtherClass
{
	function __construct($toto, $titi = null)
	{
		if ($titi === null)
		{
			$titi = "haha";
		}
		parent::__construct($toto, $titi);
	}
	
	function bli()
	{
		return parent::bli()." haha";
	}
}

class f_aop_samples_MyRestrictions extends Restrictions
{
	/**
	 * Apply my criterion to the named property
	 * @param String $propertyName
	 * @return f_aop_sample_MyCriterion
	 */
	static function true($propertyName)
	{
		return new f_aop_samples_TrueCriterion($propertyName);
	}
	
	/**
	 * Apply my criterion to the named property
	 * @param String $propertyName
	 * @return f_aop_sample_MyCriterion
	 */
	static function false($propertyName)
	{
		return new f_aop_samples_FalseCriterion($propertyName);
	}
}