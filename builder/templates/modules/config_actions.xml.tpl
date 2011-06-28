<?xml version="1.0" encoding="UTF-8"?>
<actions>
	<!-- 
	Define here backoffice action
	Example:
	<action name="createDocumentName">
		<parameter name="listWidget" />
		<body><![CDATA[
			this.createDocumentEditor('modules_MODULENAME_DOCUMENTNAME', listWidget.getSelectedItems()[0].id);
		]]></body>
	</action>
	
	Return true if the action 'createDocumentName' is active.
	<action name="createDocumentNameIsActive">
		<parameter name="itemData"/>
		<body><![CDATA[
			//Ex : le document existe dans la langue de travail
			return itemData.langAvailable;
		]]></body>
	</action>
	-->
	<action name="createFolder">
		<parameter name="listWidget" />
		<body><![CDATA[this.createDocumentEditor('modules_<{$name}>_folder', listWidget.getSelectedItems()[0].id);]]></body>
	</action>
</actions>