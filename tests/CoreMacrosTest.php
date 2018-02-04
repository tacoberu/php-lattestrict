<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\LatteStrict\Macros;

use PHPUnit_Framework_TestCase;
use Taco\LatteStrict\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;


class CoreMacrosTest extends PHPUnit_Framework_TestCase
{

	private $latte;


	function setUp()
	{
		$this->macros = new CoreMacros(new Compiler);
	}



	function testRenderer()
	{
		$node = new MacroNode($this->macros, '=', '$foo', '|escape');
		$writer = PhpWriter::using($node);
		$this->assertEquals('echo @call_user_func($this->filters->escape, $foo) /* line  */', $this->macros->macroRender($node, $writer));
	}



	function testRendererNoEscape()
	{
		$node = new MacroNode($this->macros, '=', '$foo', '');
		$writer = PhpWriter::using($node);
		$this->assertEquals('echo @$foo /* line  */', $this->macros->macroRender($node, $writer));
	}



	function testRendererIvalidateVariable()
	{
		$node = new MacroNode($this->macros, '=', '1 + 1', '|escape');
		$writer = PhpWriter::using($node);
		$this->assertEquals('echo call_user_func($this->filters->escape, \'{= 1 + 1}\') /* line  */', $this->macros->macroRender($node, $writer));
	}



	function testMacroIf()
	{
		$node = new MacroNode($this->macros, 'if', '($value = 42) > 1', null);
		$writer = PhpWriter::using($node);
		$this->assertEquals('echo \'{if ($value = 42) > 1}\' /* line  */', $this->macros->macroIf($node, $writer));
	}

}
