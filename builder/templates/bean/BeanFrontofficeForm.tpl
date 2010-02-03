<form change:form="beanClass <{$beanClassName}>; beanName <{$beanName}>">
	<div change:errors=""></div>
	<div><input change:field="name beanId" hidden="true" /></div>
	<ol>
<{foreach from=$model->getBeanPropertiesInfos() key=propertyName item=beanProperty name=propertyIterator}>
<{if !$beanProperty->isHidden()}>
		<li<{if $smarty.foreach.propertyIterator.last}> class="last" <{/if}>><input change:field="name <{$propertyName}>" /></li>
<{/if}>
<{/foreach}>
	</ol>
	<p>
		<input change:submit="label &modules.website.frontoffice.form.Submit;"/>
	</p>
</form>