<?php
class f_util_TagReplacer {

	protected $replacements  = array();
	protected $search   = array();
	protected $replace  = array();
	protected $modified = true;
	protected $content = "";

	//--- Overridable functions for specific binding processing :

	protected function preRun()
	{}

	protected function postRun()
	{}

	//---

	public function setReplacement($search, $replace)
	{
		$this->replacements[$search] = $replace;
		$this->modified = true;
	}

	public function addReplacements($replacements)
	{
		$this->replacements = array_merge($this->replacements, $replacements);
		$this->modified = true;
	}

	public function setReplacements($replacements)
	{
		$this->replacements = $replacements;
		$this->modified = true;
	}

	public function clear()
	{
		$this->replacements = array();
		$this->modified = true;
	}

	public function run($content, $localize = false)
	{
	    $this->content = $content;

	    $this->preRun();

	    $this->content = $this->replaceConstants($this->content);

		// rebuild the two arrays for str_replace() if needed
		if ($this->modified)
		{
			reset($this->replacements);
			$this->search  = array();
			$this->replace = array();
			foreach ($this->replacements as $key => $value)
			{
				$this->search[]  = '{'.$key.'}';
				$this->replace[] = $value;
			}
			$this->modified = false;
		}
		$this->content = str_replace($this->search, $this->replace, $this->content);

		if ($localize === true)
		{
			$this->content = $this->replaceLocalizedStrings($this->content);
		}

		$this->postRun();

		return $this->content;
	}

	protected final function replaceConstants($content)
	{
		$matches = array();
		preg_match_all("/{([A-Z0-9_:]+)}/", $content, $matches);
		$constants = $matches[1];
		foreach ($constants as $constant)
		{
			if (defined($constant))
			{
				$content = str_replace("{".$constant."}", constant($constant), $content);
			}
		}
		preg_match_all("/{([a-zA-Z0-9_]+::[A-Z0-9_]+)}/", $content, $matches);
		$constants = $matches[1];
		foreach ($constants as $constant)
		{
			if (defined($constant))
			{
				$content = str_replace("{".$constant."}", constant($constant), $content);
			}
		}
		return $content;
	}

	protected final function replaceLocalizedStrings($content)
	{
		$matches = null;
		preg_match_all('/&(?:amp;){0,1}([a-zA-Z_-]+\.[a-zA-Z0-9_.-]*);/', $content, $matches);
		$nb = count($matches[0]);
		if ($nb > 0)
		{
			$toreplace = array();
			$replacements = array();
			for ($i=0; $i<$nb; $i++)
			{
				$toreplace[] = $matches[0][$i];
				$replacements[] = str_replace("\n", '\n', f_Locale::translateUI('&' . $matches[1][$i] . ';'));
			}
			$content = str_replace($toreplace, $replacements, $content);
		}
		return $content;
	}

	protected function getTranslation($label)
	{
		return $label;
	}
}