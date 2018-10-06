<?php

use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Template;
use Twig\TwigFunction;

class f_template_TwigDumpExtension extends AbstractExtension
{
	private $cloner;
	private $dumper;

	public function __construct(ClonerInterface $cloner = null, HtmlDumper $dumper = null)
	{
		$this->cloner = $cloner ?: new VarCloner();
		$this->dumper = $dumper;
	}

	public function getFunctions()
	{
		return [
			new TwigFunction('dump', [$this, 'dump'], ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true]),
		];
	}

	public function getName()
	{
		return 'dump';
	}

	public function dump(Environment $env, $context)
	{
		if (!$env->isDebug()) {
			return;
		}
		if (2 === \func_num_args()) {
			$vars = array();
			foreach ($context as $key => $value) {
				if (!$value instanceof Template) {
					$vars[$key] = $value;
				}
			}
			$vars = array($vars);
		} else {
			$vars = \func_get_args();
			unset($vars[0], $vars[1]);
		}
		$dump = fopen('php://memory', 'r+b');
		$this->dumper = $this->dumper ?: new HtmlDumper();
		$this->dumper->setCharset($env->getCharset());
		foreach ($vars as $value) {
			$this->dumper->dump($this->cloner->cloneVar($value), $dump);
		}
		return stream_get_contents($dump, -1, 0);
	}
}
