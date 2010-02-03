<?php

class <{$aggregate->getClassName()}> implements f_mvc_Bean
{
	<{assign var='beanClassNames' value=$aggregate->getBeanClassNames()}>
	<{assign var='beanNames' value=$aggregate->getBeanNames()}>
	
	private $beanInstances = array();
	private static $beanClasses = <{php}>
	$aggregateGenerator = $this->get_template_vars('aggregate');
	var_export($aggregateGenerator->getBeanClassNames()); 
	<{/php}>;
	
	private static $beanNames = <{php}>
	$aggregateGenerator = $this->get_template_vars('aggregate');
	var_export($aggregateGenerator->getBeanNames()); 
	<{/php}>;
	
	private $model;
	
	/**
	 * @return <{$aggregate->getModelClassName()}>
	 */
	function getBeanModel()
	{
		if ($this->model ===  null)
		{
			$model = new BeanAggregateModel();
<{foreach from=$beanNames item=uniqueBeanName key=classIndex name=classIterator}>
			$model->addBeanModel('<{$uniqueBeanName}>', BeanUtils::getBeanModel(<{$beanClassNames.$classIndex}>::getNewInstance()));
<{/foreach}>
			$this->model = $model;
		}
		return $this->model;
	}
	
	/**
	 * @return Mixed
	 */
	function getBeanId()
	{
		return <{foreach from=$beanClassNames item=toto key=classNameIndex name=classNameIterator}>
$this->beanInstances[<{$classNameIndex}>]->getBeanId()<{if !$smarty.foreach.classNameIterator.last}> . ",". <{else}>;<{/if}>
<{/foreach}>
	
	}
	
<{foreach from=$beanNames item=uniqueBeanName key=index name=classIterator}>
	/**
	 * @return <{$beanClassNames.$index}>
	 */	
	public function get<{$uniqueBeanName|capitalize:true}>()
	{
		return $this->beanInstances[<{$index}>];
	}
<{/foreach}>
	
	
	/**
	 * @param String $id
	 * @return <{$aggregate->getClassName()}>
	 */
	static function getInstanceById($id)
	{
		$identifierArray = explode(",", $id);
		$instance = new <{$aggregate->getClassName()}>();
		if (count($identifierArray) != count(self::$beanClasses))
		{
			throw new Exception("Invalid id $id for aggregagate");
		}
<{foreach from=$beanClassNames item=className key=classIndex}>
		$id = $identifierArray[<{$classIndex}>];
		if (f_util_StringUtils::isEmpty($id))
		{
			$instance->beanInstances[] = <{$beanClassNames.$classIndex}>::getNewInstance();
		}
		else
		{
			$instance->beanInstances[] = <{$beanClassNames.$classIndex}>::getInstanceById(trim($id));
		}
<{/foreach}>
		return $instance;
	}
	
	/**
	 * @return <{$aggregate->getClassName()}>
	 */
	static function getNewInstance()
	{
		$instance = new <{$aggregate->getClassName()}>();
<{foreach from=$aggregate->getBeanClassNames() item=className}>
  		$instance->beanInstances[] = <{$className}>::getNewInstance();
<{/foreach}>	
		
		return $instance;
		}

<{foreach from=$aggregate->getBeanNames() item=beanName key=i}>
<{foreach from=$aggregate->getMethodForBean($beanName) item=methodProps}>	
	<{$methodProps.phpdoc}>
	public function <{$methodProps.call}>
  	{
  		return $this->beanInstances[<{$i}>]-><{$methodProps.orginalCall}>;
  	}
<{/foreach}>
<{/foreach}>
}
