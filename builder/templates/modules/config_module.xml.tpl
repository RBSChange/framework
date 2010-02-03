<?xml version="1.0" encoding="UTF-8"?>
<config>
	<module>
		<enabled>true</enabled>
		<visible>true</visible>
		<icon><{$icon}></icon>
<{if $category}>		<category><{$category}></category>
<{/if}>		<usetopic><{if $front == 1}>true<{else}>false<{/if}></usetopic>
	</module>
</config>