<hbox flex="1">
	<stack width="250">
		<vbox flex="1" style="opacity:0.99">
			<cnavigationtree flex="1" id="navigationTree"/>
		</vbox>
		<chelppanel hidden="true" flex="1" />
	</stack>
	<splitter collapse="before">
		<wsplitterbutton />
	</splitter>
	<deck flex="1" anonid="mainViewDeck">
		<vbox flex="1" anonid="documentlistmode">
			<cmoduletoolbar id="moduletoolbar" />
			<cmodulelist id="documentlist" flex="1" />			
		</vbox>
		<tal:block change:documenteditors="module <{$name}>" />				
	</deck>
	<splitter collapse="after">
		<wsplitterbutton />
	</splitter>
	<cressourcesselector width="210" id="ressourcesSelector" collapsed="true" />
</hbox>
