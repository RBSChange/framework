<?xml version="1.0" encoding="UTF-8"?>
<rights>
	<actions>
		<document name="rootfolder" />
		<document name="folder" />
	</actions>
	<roles>	
		<role name="Admin">
			<permission name="*" />
			<permission name="LoadPermissions.*" />
			<permission name="SavePermissions.*" />
			<permission name="GetPreferencesDocument" />
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
			<permission name="Update.*" />
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