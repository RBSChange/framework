<?xml version="1.0" encoding="UTF-8"?>
<perspective>
	<models>
<{if $useFolder}>
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
<{elseif $useTopic}>	
		<model name="modules_generic/rootfolder">
			<children>
				<child model="modules_website/websitetopicsfolder" />
			</children>
			<drops>
				<drop model="modules_website/websitetopicsfolder" action="move" />
			</drops>
			<contextactions>
				<contextaction name="edit" />
				<contextaction name="addTopic" />
				<contextaction name="openTreeNodeOrder" />		
			</contextactions>
		</model>
		<model name="modules_website/websitetopicsfolder">
			<children>
				<child model="modules_website/topic" from="topics" />
				<child model="modules_website/systemtopic" from="topics" />
			</children>
			<columns>
				<column name="pathOf" label="PathOf" flex="3" />
			</columns>
			<contextactions>
				<contextaction name="openFolder" />
				<contextaction name="openTopicOrder"/>
			</contextactions>
		</model>
		<model name="modules_website/topic">
			<children>
				<child model="modules_website/topic" />
				<child model="modules_website/systemtopic" />
			</children>
			<contextactions>
				<contextaction name="openFolder" />
				<contextaction name="edit" />
			</contextactions>
		</model>
		<model name="modules_website/systemtopic">
			<children>
				<child model="modules_website/topic" />
				<child model="modules_website/systemtopic" />
			</children>
			<contextactions>
				<contextaction name="openFolder" />
				<contextaction name="edit" />
			</contextactions>
		</model>			
<{/if}>	
	</models>
	<toolbar>
		<toolbarbutton name="edit" />
		<toolbarbutton name="delete" />
		<toolbarbutton name="activate" />
		<toolbarbutton name="deactivated" />
		<toolbarbutton name="reactivate" />
	</toolbar>
	<actions>
		<action name="refresh" single="true" icon="refresh" label="&amp;modules.uixul.bo.actions.Refresh;" />
		<action name="edit" single="true" permission="Load" icon="edit" label="&amp;modules.uixul.bo.actions.Edit;" />
		<action name="delete" permission="Delete" icon="delete" label="&amp;modules.uixul.bo.actions.Delete;" />
		<action name="openFolder" single="true" icon="open-folder" label="&amp;modules.uixul.bo.actions.OpenFolder;" />
		<action name="move" permission="Move" icon="up_down" label="&amp;modules.uixul.bo.actions.Move;" />
		<action name="openTags" single="true" permission="LoadTags" icon="edit-tags" label="&amp;modules.uixul.bo.actions.Open-tags-panel;" />
		<action name="duplicate" single="true" permission="Duplicate" icon="duplicate" label="&amp;modules.uixul.bo.actions.Duplicate;" />
		<action name="activate" single="true" permission="Activate" icon="activate" label="&amp;modules.uixul.bo.actions.Activate;" />
		<action name="deactivated" permission="Deactivated" icon="deactivated" label="&amp;modules.uixul.bo.actions.Deactivate;" />
		<action name="reactivate" permission="ReActivate" icon="reactivate" label="&amp;modules.uixul.bo.actions.ReActivate;" />
		<action name="openTreeNodeOrder" single="true" permission="Order" icon="sort" label="&amp;modules.uixul.bo.actions.Set-children-order;" />
<{if $useTopic}>
		<action name="addTopic" single="true" permission="Update_rootfolder" icon="add-topic" label="&amp;modules.uixul.bo.actions.AddTopic;" />
		<action name="openTopicOrder" single="true" permission="Order" icon="sort" label="&amp;modules.uixul.bo.actions.Set-children-order;" />
<{elseif $useFolder}>
		<action name="createFolder" single="true" permission="Insert_folder" icon="create-folder" label="&amp;modules.uixul.bo.actions.Create-folder;" />
<{/if}>
	</actions>
</perspective>