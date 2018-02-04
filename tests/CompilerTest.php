<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\LatteStrict;

use PHPUnit_Framework_TestCase;
use Latte\Parser;


class CompilerTest extends PHPUnit_Framework_TestCase
{

	private $parser;
	private $compiler;


	function setUp()
	{
		$this->parser = new Parser;
		$this->compiler = new Compiler;
		Macros\CoreMacros::install($this->compiler);

	}



	/**
	 * @dataProvider dataEscapedExpressions
	 */
	function testEscapedExpressions($except, $template) {
		$this->assertEqualsRender($except, $template);
	}



	function dataEscapedExpressions()
	{
		return [
			['', ''],
			['abc', 'abc'],
			['$abc', '$abc'],
			['{abc}', '{abc}'],
			["<?php echo LR\Filters::escapeHtmlText('{= 1  + 1}') /* line 1 */ ?>\n", '{=  1  + 1}'],
			["<?php echo LR\Filters::escapeHtmlText('{= 1 + 1}') /* line 1 */ ?>\n", '{= 1 + 1 }'],
			["{? 1 + 1 }", '{? 1 + 1 }'],
			["<?php echo LR\Filters::escapeHtmlText('{= \$boo >= 111}') /* line 1 */ ?>\n", '{= $boo >= 111}'],
			['{var $boo => 111} Lorem <?php echo @LR\Filters::escapeHtmlText($foo) /* line 1 */ ?> doler ist.',
				'{var $boo => 111} Lorem {$foo} doler ist.'],
			["<?php echo LR\Filters::escapeHtmlText('{= date('Y') - \$birth}') /* line 1 */ ?>\n",
				'{date(\'Y\') - $birth}'],
			['{php $boo >= 111}', '{php $boo >= 111}'],

			// render
			["<?php echo @LR\Filters::escapeHtmlText(\$abc) /* line 1 */ ?>\n", '{$abc}'],
			["<p>You name is: <?php echo @LR\Filters::escapeHtmlText(\$name) /* line 1 */ ?></p>", '<p>You name is: {$name}</p>'],

			// if
			["<p><?php if (\$name) { ?><?php echo @LR\Filters::escapeHtmlText(\$name) /* line 1 */ ?><?php } ?></p>", '<p>{if $name}{$name}{/if}</p>'],
			["<p><?php if (\$name == 1) { ?><?php echo @LR\Filters::escapeHtmlText(\$name) /* line 1 */ ?><?php } ?></p>", '<p>{if $name == 1}{$name}{/if}</p>'],
			["<p><?php if (\$name) { ?><?php echo @LR\Filters::escapeHtmlText(\$name) /* line 1 */ ?><?php } ?></p>", '<p>{if $name}{$name}{/}</p>'],
			["<p><?php if (\$name == 1) { ?><?php echo @LR\Filters::escapeHtmlText(\$name) /* line 1 */ ?><?php } ?></p>", '<p>{if $name == 1}{$name}{/}</p>'],

			// dump
			["<?php echo Taco\LatteStrict\Helper::dump(get_defined_vars(), 'variables'); ?>\n", '{dump}'],
			["<?php echo Taco\LatteStrict\Helper::dump((\$foo), '\$foo'); ?>\n", '{dump $foo}'],

			/*
			['abc «?php 1 + 1?» def', 'abc <?php 1 + 1?> def'],
			['abc «?php 1 + 1?» def', 'abc <?php 1 + 1?> def'],
			*/
			//~ ['', '{if $a in [1, 2, 3]}true{/if}']
		];
	}



	private function assertEqualsRender($except, $template)
	{
		$this->assertSame($this->exceptResult($except), $this->renderToString($template));
	}



	private function renderToString($code)
	{
		$tokens = $this->parser->parse($code);
		return $this->compiler->compile($tokens, '__T__');
	}



	private function exceptResult($desc)
	{
		return "<?php\n"
			. "use Latte\\Runtime as LR;\n\n"
			. "class __T__ extends Latte\\Runtime\\Template\n{\n\n"
			. "\tfunction main()\n"
			. "\t{\n"
			. "\t\textract(\$this->params);?>\n"
			. $desc
			. "<?php return get_defined_vars();\n"
			. "\t}\n"
			. "\n}\n";
	}


}
