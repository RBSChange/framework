<?php
class import_ScriptDebugElement extends import_ScriptBaseElement
{
		public function process()
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__.": ".$this->attributes['msg']);
			}
		}

		public function endProcess()
		{
		}
}
