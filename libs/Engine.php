<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 * @author David Grudl (https://davidgrudl.com)
 */

namespace Taco\LatteStrict;

use Latte;
use Latte\CompileException;
use Latte\PhpHelpers;


/**
 * @author Martin Takáč <martin@takac.name>
 */
class Engine extends Latte\Engine
{

	private $compiler;

	/** @var string */
	private $contentType = self::CONTENT_HTML;


	/**
	 * Nechceme defaultní makra. Chceme naše defaultní makra.
	 *
	 * @return Compiler
	 */
	function getCompiler()
	{
		if ( ! $this->compiler) {
			$this->compiler = new Compiler;
			Macros\CoreMacros::install($this->compiler);
		}
		return $this->compiler;
	}



	/**
	 * Evidujeme tu samou hodnotu, i u předka, protože je private.
	 * @return static
	 */
	function setContentType($type)
	{
		$this->contentType = $type;
		$this->setContentType($type);
		return $this;
	}



	/**
	 * Compiles template to PHP code.
	 * @return string
	 */
	function compile($name)
	{
		foreach ($this->onCompile ?: [] as $cb) {
			call_user_func(Helpers::checkCallback($cb), $this);
		}
		$this->onCompile = [];

		$source = $this->getLoader()->getContent($name);
		$source = $this->escapePHP($source);
		$source = $this->escapeBracket($source);

		try {
			$tokens = $this->getParser()->setContentType($this->contentType)
				->parse($source);

			$code = $this->getCompiler()->setContentType($this->contentType)
				->compile($tokens, $this->getTemplateClass($name));

		} catch (\Exception $e) {
			if (!$e instanceof CompileException) {
				$e = new CompileException("Thrown exception '{$e->getMessage()}'", 0, $e);
			}
			$line = isset($tokens) ? $this->getCompiler()->getLine() : $this->getParser()->getLine();
			throw $e->setSource($source, $line, $name);
		}

		if (!preg_match('#\n|\?#', $name)) {
			$code = "<?php\n// source: $name\n?>" . $code;
		}
		$code = PhpHelpers::reformatCode($code);
		return $code;
	}



	private function escapePHP($content)
	{
		switch ($this->contentType) {
			case self::CONTENT_HTML:
				$open = '&#60;';
				$close = '&#62;';
			// Nahradíme pomocí unicode
			default:
				$open = '«';
				$close = '»';
		}
		return strtr($content, [
			'<?php' => $open . '?php',
			'<?=' => $open . '?=',
			'<?' => $open . '?',
			'?>' => '?' . $close,
		]);
	}



	private function escapeBracket($content)
	{
		return preg_replace_callback('~\{\w+~', function($matches) {
			$name = substr(reset($matches), 1);
			if ( ! $this->getCompiler()->knowsMacro($name)) {
				return '{l}' . $name;
			}
			return '{' . $name;
		}, $content);
	}

}
