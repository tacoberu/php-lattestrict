<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\LatteStrict;

use PHPUnit_Framework_TestCase;


class HelperTest extends PHPUnit_Framework_TestCase
{

	function testQuotes() {
		$src = 'abx cdx \"efx \\\'ghx aaa"chx ijk" lm ne "a jak se" máš';
		$this->assertEquals([
			'abx', 'cdx', '\"efx', '\\\'ghx', 'aaa', '"chx ijk"', 'lm', 'ne', '"a jak se"', 'máš'
		], Helper::parser($src));
	}



	function testWhiteChars() {
		$src = 'abx cdx';
		$this->assertEquals([
			'abx', 'cdx'
		], Helper::parser($src));
	}



	function testManyWhiteChars() {
		$src = 'abx  cdx';
		$this->assertEquals([
			'abx', 'cdx'
		], Helper::parser($src));
	}



	function testDump() {
		$args = ['abx  cdx'];
		$this->assertEquals('array (1)
   name => array (1)
   |  0 => "abx  cdx" (8)

', Helper::dump($args, 'name'));
	}



	function testDumpB() {
		$args = [
			'a' => 'abx  cdx'
		];
		$this->assertEquals('array (1)
   name => array (1)
   |  a => "abx  cdx" (8)

', Helper::dump($args, 'name'));
	}



	function testDumpC() {
		$args = [
			'a' => 'abx  cdx',
			'_renderblock' => null,
			'template' => 111,
			'_l' => (object) [],
			'_g' => (object) [],
			'_b' => (object) [],
		];
		$this->assertEquals('array (1)
   name => array (1)
   |  a => "abx  cdx" (8)

', Helper::dump($args, 'name'));
	}



	/**
	 * @dataProvider dataValidateExpressionCorrect
	 */
	function testValidateExpressionCorrect($x) {
		$this->assertTrue(Helper::validateExpression($x));
	}



	/**
	 * @dataProvider dataValidateExpressionIllegal
	 */
	function testValidateExpressionIllegal($x) {
		$this->assertFalse(Helper::validateExpression($x));
	}



	function dataValidateExpressionCorrect()
	{
		return [
			['$foo'],
			['$scoByDoo'],
			['$scoByDoo '],
			['$scoByDoo $boo'],
			['$scoByDoo == $boo'],
			['$scoByDoo == "fo"'],
			["\$scoByDoo == 'fo'"],
			['$scoByDoo == 11'],
			['$scoByDoo == true'],
			['$scoByDoo == false'],
			['$foo, $boo, $doo'],
			['$foo->lee, $boo->goo, $doo'],
			//~ ['[1, 2, 3]'],
		];
	}


	function dataValidateExpressionIllegal()
	{
		return [
			['foo'],
			['Foo'],
			['foo()'],
			['$foo()'],
		];
	}



	/**
	 * @dataProvider dataValidateForeachExpressionCorrect
	 */
	function testValidateForeachExpressionCorrect($x) {
		$this->assertTrue(Helper::validateForeachExpression($x));
	}



	function dataValidateForeachExpressionCorrect()
	{
		return [
			//~ ['$foo'], // @TODO
			['$foo as $x'],
			['$foo as $xy'],
			['$scoByDoo as $x'],
			['$foo as $i => $x'],
			['$foo as $i => $xy'],
		];
	}



	/**
	 * @dataProvider dataValidateForeachExpressionFail
	 */
	function testValidateForeachExpressionFail($x) {
		$this->assertFalse(Helper::validateForeachExpression($x));
	}



	function dataValidateForeachExpressionFail()
	{
		return [
			['$foo'],
			['foo'],
			['foo as $x'],
		];
	}

}
