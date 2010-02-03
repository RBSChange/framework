<?xml version="1.0" encoding="UTF-8"?>
<datasources>
<{if $front == 1}>
<!-- 
  	<datasource treecomponents="modules_website/topic" 
  		listcomponents="modules_<{$name}>/DOCUMENTNAME,modules_<{$name}>/DOCUMENTNAME2"/> 
-->
<{else}>
<!-- 
  	<datasource treecomponents="modules_generic/folder" 
  		listcomponents="modules_<{$name}>/DOCUMENTNAME,modules_<{$name}>/DOCUMENTNAME2"/> 
-->
<{/if}>
</datasources>