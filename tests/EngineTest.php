<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\LatteStrict;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_Error;
use Latte;


/**
 * Žádné filtry.
 * Pouze omezená množina výrazů pro podmínky.
 * Makro dump()
 * Sanitizaci na <?php a spol.
 *
 * @TODO Makro widget
 */
class EngineTest extends PHPUnit_Framework_TestCase
{

	private $latte;


	function setUp()
	{
		$this->latte = new Engine;
		$this->latte->setLoader(new Latte\Loaders\StringLoader);
	}



	/**
	 * <?php 1 + 1?>
	 *
	 * @dataProvider dataEscapedExpressions
	 */
	function testEscapedExpressions($except, $template)
	{
		$this->assertEqualsRender($except, $template, [
			'foo' => 'ipsum'
		]);
	}



	function dataEscapedExpressions()
	{
		return [
			['abc «?php 1 + 1?» def', 'abc <?php 1 + 1?> def'],
			['{= 1  + 1}', '{=  1  + 1}'],
			['{= 1 + 1}', '{= 1 + 1 }'],
			['{? 1 + 1 }', '{? 1 + 1 }'],
			//~ ['abc{/}efg', 'abc{/}efg'],
			['abc «?php 1 + 1?» def', 'abc <?php 1 + 1?> def'],
			['{= $boo &gt;= 111}', '{= $boo >= 111}'],
			['{var $boo => 111} Lorem ipsum doler ist.', '{var $boo => 111} Lorem {$foo} doler ist.'],
			['{php $boo >= 111}', '{php $boo >= 111}'],
			['{date(\'Y\') - $birth}', '{date(\'Y\') - $birth}'],

			['', '{$unused}'],

			// illegal condition
			['{if ($value = 42) > 1} true {/if}', '{if ($value = 42) > 1} true {/}'],

		];
	}



	/**
	 * @dataProvider dataBracket
	 */
	function testBracket($except, $template)
	{
		$this->assertEqualsRender($except, $template, [
			'foo' => 'ipsum'
		]);
	}



	function dataBracket()
	{
		return [
			['xe {abc def', 'xe {abc def'],
			['xe {1abc def', 'xe {1abc def'],
			//~ ['xe {/} def', 'xe {/} def'],
			['{abc def dd', '{abc def {if 1 == 1}dd{/}'],
			['{abc def', '{abc def {if 1 > 1}dd{/}'],
		];
	}



	/**
	 * @dataProvider dataVariables
	 */
	function testVariables($except, $template)
	{
		$this->assertEqualsRender($except, $template, [
			'foo' => 'ipsum'
		]);
	}



	function dataVariables()
	{
		return [
			['Lorem ipsum doler ist.', 'Lorem {$foo} doler ist.'],
			['Lorem ipsum doler ist.', 'Lorem {= $foo} doler ist.'],
			//~ ['Lorem ipsum doler ist.', 'Lorem {render $foo} doler ist.'],
		];
	}



	function testApplyFilterIsSupported()
	{
		$this->assertEqualsRender('Start Lo... end.', 'Start {$foo|truncate:5,\'...\'} end.', [
			'foo' => 'Lorem ipsum doler ist'
		]);
	}



	function testMacroDump()
	{
		$this->assertEqualsRender('array (1)
   variables => array (1)
   |  foo => "Lorem ipsum doler ist" (21)', '{dump}', [
			'foo' => 'Lorem ipsum doler ist'
		]);
	}



	/**
	 * @dataProvider dataConditionIf
	 */
	function testConditionIf($except, $template)
	{
		$this->assertEqualsRender($except, $template, [
			'age' => 10
		]);
	}



	function dataConditionIf()
	{
		return [
			['<p>Lorem ipsum.</p>', '{if $age < 18} <p>Lorem ipsum.</p> {/}'],
			['<p>Lorem ipsum.</p>', '{if $age < 18} <p>Lorem ipsum.</p> {/if}'],
			['<p>Lorem ipsum.</p>', '{if 15 < 18} <p>Lorem ipsum.</p> {/if}'],
			//~ ['<p>Lorem ipsum.</p>', '<p n:if="$age < 18">Lorem ipsum.</p>'],

			['', '{if $age > 18} <p>Lorem ipsum.</p> {/}'],
			//~ ['', '<p n:if="$age > 18">Lorem ipsum.</p>'],
		];
	}



	function testConditionIfEmpty()
	{
		$this->setExpectedException(PHPUnit_Framework_Error::class, 'Undefined variable: age');
		$this->assertEquals('', trim($this->latte->renderToString('{if $age < 18} <p>Nezletilým nenaléváme.</p> {/}', [])));
	}



	/**
	 * @dataProvider dataConditionIfFail
	 */
	function testConditionIfFail($except, $template)
	{
		$this->assertEquals($except, $this->latte->renderToString($template, [
			'def' => 5
		]));
	}



	function dataConditionIfFail()
	{
		return [
			['{if fn() < 18} call function {/if}',
				'{if fn() < 18} call function {/if}'],
			['{if fn() < 18} call function {/if}',
				'{if fn() < 18} call function {/if}'],
			['{if DEF < 18} define {/if}',
				'{if DEF < 18} define {/}'],
			['{if Def::def() < 18} define {/if}',
				'{if Def::def() < 18} define {/}'],
			['{if def < 18} define {/if}',
				'{if def < 18} define {/}'],
		];
	}



	/**
	 * @dataProvider dataConditionIfElse
	 */
	function testConditionIfElse($except, $template)
	{
		$this->assertEqualsRender($except, $template, [
			'person' => (object) ['sex' => 'male', 'name' => 'Item'],
		]);
	}



	function dataConditionIfElse()
	{
		return [
			['Le Item', '{if $person->sex == \'male\'}Le {$person->name}{else}La {$person->name}{/}'],
			['La Item', '{if $person->sex == \'female\'}Le {$person->name}{else}La {$person->name}{/}'],
		];
	}



	function testConditionIfElseComplex()
	{
		$src = '{if $stock}
    Skladem
{elseif $onWay}
    Na cestě
{else}
    Není dostupné
{/if}';
		$this->assertEqualsRender('Skladem', $src, [
			'stock' => True,
		]);
		$this->assertEqualsRender('Na cestě', $src, [
			'stock' => False,
			'onWay' => True,
		]);
		$this->assertEqualsRender('Není dostupné', $src, [
			'stock' => False,
			'onWay' => False,
		]);
	}



	function testForeach()
	{
		$src = '{foreach $items as $item}
    <li>{$item}</li>
{/foreach}';
		$desc = '<li>one</li>
    <li>two</li>
    <li>three</li>';
		$args = [
			'items' => ['one', 'two', 'three']
		];
		$this->assertEqualsRender($desc, $src, $args);
	}



	function testForeachIter()
	{
		$src = '
{foreach $items as $item}
  {$item}
{/}
';
		$args = [
			'items' => ['one', 'two', 'three']
		];
		$this->assertEquals("\n  one\n  two\n  three\n", $this->latte->renderToString($src, $args));
	}



	function testForeachWithSep()
	{
		$src = '{foreach $items as $item}
    {$item}{sep}, {/sep}
{/foreach}';
		$desc = "one, \n    two, \n    three";
		$args = [
			'items' => ['one', 'two', 'three']
		];
		$this->assertEqualsRender($desc, $src, $args);
	}



	function testForeachWithKey()
	{
		$src = '{foreach $items as $key => $item}
    <li>{$item}</li>
{/foreach}';
		$desc = '<li>one</li>
    <li>two</li>
    <li>three</li>';
		$args = [
			'items' => ['one', 'two', 'three']
		];
		$this->assertEqualsRender($desc, $src, $args);
	}



	function testForeachFail()
	{
		$src = '{foreach Environment::getContext()->getByType("NDB\\\\Connection")->query(\'SELECT * FROM "table" WHERE `a` = 11\') as $key => $value} ... {/}';
		$args = [
			'items' => ['one', 'two', 'three']
		];
		$this->assertEquals('{foreach Environment::getContext()->getByType("NDB\\\\Connection")->query(\'SELECT * FROM "table" WHERE `a` = 11\') as $key => $value} ... {/foreach}', $this->latte->renderToString($src, $args));
	}



	function testSwitch()
	{
		$src = '
{switch $transport}
    {case train}
        Vlakem
    {case plane}
        Letecky
    {default}
        Jinak
{/switch}
';
		$desc = 'Vlakem';
		$args = [
			'transport' => 'train'
		];
		$this->assertEqualsRender($desc, $src, $args);
	}



	private function assertEqualsRender($except, $template, array $args)
	{
		$this->assertEquals($except, trim($this->latte->renderToString($template, $args)));
	}

}
