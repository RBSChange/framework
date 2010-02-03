<?xml version="1.0" encoding="UTF-8"?>
<rights>
	<actions>
		<document name="rootfolder" />
<{if $useTopic}>
		<document name="topic" />
<{elseif $useFolder}>
		<document name="folder" />
<{/if}>
	</actions>
	<roles>	
		<role name="Admin">
			<permission name="*" />
			<permission name="LoadPermissions.*" />
			<permission name="SavePermissions.*" />
			<permission name="GetPreferencesDocument" />
			<permission name="EditLocale" />
<{if $useTopic}>
			<permission name="Update.rootfolder" />
<{/if}>
		</role>
		<role name="Writer" extend="Guest">
			<permission name="Order" />
			<permission name="LoadTags" />
			<permission name="Load.*" />
			<permission name="Rename.*" />
			<permission name="PutInTrash.*" />
			<permission name="Delete.*" />
			<permission name="Insert.*" />
			<permission name="Move.*" />
<{if $useTopic}>
			<!-- The rootfolder can be edited only by admin so here you need 
				to add the permission for each other document explicitly. -->
			<!-- permission name="Update.xxx" /-->
<{else}>
			<permission name="Update.*" />
<{/if}>
		</role>
		<role name="Translator" extend="Guest">
			<permission name="Load.*" />
			<permission name="LoadForTranslation.*" />
			<permission name="UpdateTranslation.*" />
		</role>
		<role name="Validator" extend="Guest">
			<permission name="Load.*" />
			<permission name="Activate.*" />
			<permission name="Cancel.*" />
			<permission name="Deactivated.*" />
			<permission name="ReActivate.*" />
		</role>
		<role name="Guest" extend="User">
			<permission name="Enabled" />
		</role>
		<role name="User">
			<permission name="List.*" />
		</role>
	</roles>
</rights>