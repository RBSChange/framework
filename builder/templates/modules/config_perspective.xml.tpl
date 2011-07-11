<?xml version="1.0" encoding="UTF-8"?>
<perspective>
	<models>
		<model name="modules_generic/rootfolder">
			<children>	
				<child model="modules_generic/folder" />
				<child model="modules_generic/systemfolder" />
			</children>
			<drops>
				<drop model="modules_generic/folder" action="move" />
				<drop model="modules_generic/systemfolder" action="move" />
			</drops>
			<contextactions>
				<contextaction name="edit" />
				<contextaction name="createFolder" />
				<contextaction name="openTreeNodeOrder" />
			</contextactions>	
		</model>
		<model name="modules_generic/folder">
			<children>
				<child model="modules_generic/folder" />
			</children>
			<drops>
				<drop model="modules_generic/folder" action="move" />
			</drops>
			<contextactions>
				<contextaction name="edit" />
				<contextaction name="delete" />				
				<contextaction name="createFolder" />
				<contextaction name="openTreeNodeOrder" />
				<contextaction name="openFolder" />
			</contextactions>
		</model>
		<model name="modules_generic/systemfolder">
			<contextactions>
				<contextaction name="openFolder" />
			</contextactions>
		</model>
	</models>
	<toolbar>
		<toolbarbutton name="edit" />
		<toolbarbutton name="delete" />
		<toolbarbutton name="activate" />
		<toolbarbutton name="deactivated" />
		<toolbarbutton name="reactivate" />
	</toolbar>
	<actions>
		<action name="refresh" single="true" icon="refresh" labeli18n="m.uixul.bo.actions.refresh" />
		<action name="edit" single="true" permission="Load" icon="edit" labeli18n="m.uixul.bo.actions.edit" />
		<action name="delete" permission="Delete" icon="delete" labeli18n="m.uixul.bo.actions.delete" />
		<action name="openFolder" single="true" icon="open-folder" labeli18n="m.uixul.bo.actions.openfolder" />
		<action name="move" permission="Move" icon="up_down" labeli18n="m.uixul.bo.actions.move" />
		<action name="openTags" single="true" permission="LoadTags" icon="edit-tags" labeli18n="m.uixul.bo.actions.open-tags-panel" />
		<action name="duplicate" single="true" permission="Duplicate" icon="duplicate" labeli18n="m.uixul.bo.actions.duplicate" />
		<action name="activate" single="true" permission="Activate" icon="activate" labeli18n="m.uixul.bo.actions.activate" />
		<action name="deactivated" permission="Deactivated" icon="deactivated" labeli18n="m.uixul.bo.actions.deactivate" />
		<action name="reactivate" permission="ReActivate" icon="reactivate" labeli18n="m.uixul.bo.actions.reactivate" />
		<action name="openTreeNodeOrder" single="true" permission="Order" icon="sort" labeli18n="m.uixul.bo.actions.set-children-order" />
		<action name="createFolder" single="true" permission="Insert_folder" icon="create-folder" labeli18n="m.uixul.bo.actions.create-folder" />
	</actions>
</perspective>