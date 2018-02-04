<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\LatteStrict;

use Latte;


/**
 * Latte compiler.
 */
class Compiler extends Latte\Compiler
{

	private $macros = [];

	/**
	 * Compiles tokens to PHP code.
	 * @param  Latte\Token[]
	 * @return string
	 */
	function compile(array $tokens, $className)
	{
		foreach ($tokens as $token) {
			if ($token->text === '{/}') {
				continue;
			}
			if ( ! $this->knowsMacro($token->name)) {
				$token->type = 'text';
			}
		}

		return parent::compile($tokens, $className);
	}



	/**
	 * Adds new macro with Latte\IMacro flags.
	 * @param  string
	 * @return static
	 */
	function addMacro($name, Latte\IMacro $macro, $flags = null)
	{
		$this->macros[] = $name;
		parent::addMacro($name, $macro, $flags);
		return $this;
	}



	function knowsMacro($name)
	{
		return in_array($name, $this->macros);
	}

}
