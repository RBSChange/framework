function onInit()
{
	wCore.executeOnMethodExists(this.toolbar, 'hideResetButton', 
		function(xbl){xbl.hideResetButton(); xbl.hideCreateButton(); xbl.hideSubmitButton()});
}