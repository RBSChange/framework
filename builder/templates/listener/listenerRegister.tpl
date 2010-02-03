<?php
if (Framework::isDebugEnabled())
{
	Framework::debug("Register all compiled listeners.");
}
<{foreach from=$listeners item=listener}>
f_event_EventManager::register(new <{$listener->listenerClass}>, <{$listener->eventName}>, <{$listener->listenerMethod}>);
<{/foreach}>