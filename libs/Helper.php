<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\LatteStrict;

use Tracy;


/**
 * Validation of expression like 1 + 1, $a == 1, etc
 */
class Helper
{

	static function validateExpression($expr)
	{
		$expr = strtr($expr, ',', ' ');
		$tokens = self::parser($expr);
		foreach ($tokens as $token) {
			if ( ! self::validToken($token)) {
				return False;
			}
		}
		return True;
	}



	static function validateForeachExpression($expr)
	{
		if (preg_match('~^\$[\w]+$~', $expr)) {
			// @TODO Zatím nepodporujem.
			return False;
		}
		if (preg_match('~^\$[\w]+\s+as\s+\$[\w]+$~', $expr)) {
			return True;
		}
		if (preg_match('~^\$[\w]+\s+as\s+\$[\w]+\s+\=\>\s+\$[\w]+$~', $expr)) {
			return True;
		}
		return False;
	}



	static function validateVariable($var)
	{
		return self::validToken($var);
	}



	static function validToken($token) {
		if (is_numeric($token)) {
			return True;
		}
		// $foo
		// $fooBoo
		// $foo->boo
		if (preg_match('~^\$[\w]+(\-\>[\w]+)?$~', $token)) {
			return True;
		}
		if (in_array(strtolower($token), ['==', '!=', '<', '>', '<=', '>=', 'true', 'false'], True)) {
			return True;
		}
		if (preg_match('~^["\'].*["\']$~', $token)) {
			return True;
		}
	}



	/**
	 * Rozbije to text na tokeny oddělené mezerou. Přičemž zachovává sekvence uvozené uvozovkama a apostrfama.
	 *
	 * @TODO Není to úplně přesný. Pro řetězec ~abc def"veta"pok xyz~ to na urovni uvozovek zalomí.
	 * @deprecated
	 */
	static function parser($src) {
		$res = [];
		// Najdeme první uvozovku. Kod do ní zpracujeme.
		while ($quote = self::find($src)) {
			$head = substr($src, 0, $quote[1]);
			$res = array_merge($res, self::split(rtrim($head)));
			$src = substr($src, $quote[1]);

			// Najdeme druhou uvozovku.
			$quote = self::findQuotes($quote[0], $src, 1);
			$res[] = substr($src, 0, $quote[1] + 1);
			$src = trim(substr($src, $quote[1] + 1));
		}

		if ($src) {
			$res = array_merge($res, self::split($src));
		}

		return array_values(array_filter($res));
	}



	static function dump(array $vars, $name)
	{
		foreach ($vars as $k => $var) {
			if (in_array($k, ['template'], True)) {
				unset($vars[$k]);
			}
			if ($k{0} === '_') {
				unset($vars[$k]);
			}
		}
		return Tracy\Dumper::toText([
			$name => $vars
		]);
	}



	private static function findQuotes($quote, $s, $offset = 0)
	{
		$pos = strpos($s, $quote,             $offset);
		$esc = strpos($s, addslashes($quote), $offset);
		// Escapovaná uvozovka.
		if ($esc && $pos && $esc + 1 === $pos) {
			return self::findQuotes($quote, $s, $pos + 1);
		}

		if ($pos) {
			return [$quote, $pos];
		}

		return False;
	}



	/**
	 * Nejbližší uvozovka.
	 * @return [", abs-pos] | [', abs-pos] | false
	 */
	private static function find($src) {
		$a = self::findQuotes('"', $src);
		$b = self::findQuotes("'", $src);
		if ($a && $b) {
			return $a[1] > $b[1] ? $b : $a;
		}
		return $a ?: $b;
	}



	private static function split($s)
	{
		return explode(' ', $s);
	}

}
