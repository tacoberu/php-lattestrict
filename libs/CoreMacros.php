<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 * @author David Grudl (https://davidgrudl.com)
 */

namespace Taco\LatteStrict\Macros;

use Taco\LatteStrict\Helper;
use Latte;
use Latte\CompileException;
use Latte\Compiler;
use Latte\Engine;
use Latte\Helpers;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;


/**
 * Basic macros for Latte.
 *
 * - {if ?} ... {elseif ?} ... {else} ... {/if}
 * - {ifset ?} ... {elseifset ?} ... {/ifset}
 * - {switch $var} … {case value} … {default} … {/switch}
 *
 * - {foreach ?} ... {/foreach}
 * - {dump $var}
 * - {l} {r} to display { }
 */
class CoreMacros extends MacroSet
{
	/** @var array */
	private $overwrittenVars;


	static function install(Compiler $compiler)
	{
		$me = new static($compiler);

		$me->addMacro('if', [$me, 'macroIf'], [$me, 'macroEndIf']);
		$me->addMacro('elseif', '} elseif (%node.args) {');
		$me->addMacro('else', [$me, 'macroElse']);
		$me->addMacro('ifset', 'if (isset(%node.args)) {', '}');
		$me->addMacro('elseifset', '} elseif (isset(%node.args)) {');
		$me->addMacro('ifcontent', [$me, 'macroIfContent'], [$me, 'macroEndIfContent']);

		$me->addMacro('switch', '$this->global->switch[] = (%node.args); if (FALSE) {', '} array_pop($this->global->switch)');
		$me->addMacro('case', '} elseif (end($this->global->switch) === (%node.args)) {');

		$me->addMacro('foreach', '', [$me, 'macroEndForeach']);
		$me->addMacro('first', 'if ($iterator->isFirst(%node.args)) {', '}');
		$me->addMacro('last', 'if ($iterator->isLast(%node.args)) {', '}');
		$me->addMacro('sep', 'if (!$iterator->isLast(%node.args)) {', '}');

		$me->addMacro('dump', [$me, 'macroDump']);
		$me->addMacro('l', '?>{<?php');
		$me->addMacro('r', '?>}<?php');

		$me->addMacro('=', [$me, 'macroRender']);
		$me->addMacro('render', [$me, 'macroRender']);

		$me->addMacro('class', null, null, [$me, 'macroClass']);
		$me->addMacro('attr', null, null, [$me, 'macroAttr']);
	}



	/**
	 * Initializes before template parsing.
	 * @return void
	 */
	function initialize()
	{
		$this->overwrittenVars = [];
	}



	/**
	 * Finishes template parsing.
	 * @return array|null [prolog, epilog]
	 */
	function finalize()
	{
		$code = '';
		foreach ($this->overwrittenVars as $var => $lines) {
			$s = var_export($var, true);
			$code .= 'if (isset($this->params[' . var_export($var, true)
				. "])) trigger_error('Variable $" . addcslashes($var, "'") . ' overwritten in foreach on line ' . implode(', ', $lines) . "'); ";
		}
		return [$code];
	}


	/********************* macros ****************d*g**/


	/**
	 * {if ...}
	 */
	function macroIf(MacroNode $node, PhpWriter $writer)
	{
		if ($node->modifiers) {
			throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
		}
		if ( ! Helper::validateExpression($node->args)) {
			return $writer->write("echo '{{$node->name} {$node->args}}' /* line $node->startLine */");
			throw new CompileException('Expression `' . $node->args . '\' is not supported.');
		}
		if ($node->data->capture = ($node->args === '')) {
			return 'ob_start(function () {})';
		}
		if ($node->prefix === $node::PREFIX_TAG) {
			return $writer->write($node->htmlNode->closing ? 'if (array_pop($this->global->ifs)) {' : 'if ($this->global->ifs[] = (%node.args)) {');
		}
		return $writer->write('if (%node.args) {');
	}



	/**
	 * {/if ...}
	 */
	function macroEndIf(MacroNode $node, PhpWriter $writer)
	{
		if ( ! isset($node->data->capture)) {
			return $writer->write("echo '{/{$node->name}}'");
		}

		if ($node->data->capture) {
			if ($node->args === '') {
				throw new CompileException('Missing condition in {if} macro.');
			}
			return $writer->write('if (%node.args) '
				. (isset($node->data->else) ? '{ ob_end_clean(); echo ob_get_clean(); }' : 'echo ob_get_clean();')
				. ' else '
				. (isset($node->data->else) ? '{ $this->global->else = ob_get_clean(); ob_end_clean(); echo $this->global->else; }' : 'ob_end_clean();')
			);
		}
		return '}';
	}



	/**
	 * {else}
	 */
	function macroElse(MacroNode $node, PhpWriter $writer)
	{
		if ($node->modifiers) {
			throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
		} elseif ($node->args) {
			$hint = Helpers::startsWith($node->args, 'if') ? ', did you mean {elseif}?' : '';
			throw new CompileException('Arguments are not allowed in ' . $node->getNotation() . $hint);
		}
		$ifNode = $node->parentNode;
		if ($ifNode && $ifNode->name === 'if' && $ifNode->data->capture) {
			if (isset($ifNode->data->else)) {
				throw new CompileException('Macro {if} supports only one {else}.');
			}
			$ifNode->data->else = true;
			return 'ob_start(function () {})';
		}
		return '} else {';
	}



	/**
	 * n:ifcontent
	 */
	function macroIfContent(MacroNode $node, PhpWriter $writer)
	{
		if (!$node->prefix || $node->prefix !== MacroNode::PREFIX_NONE) {
			throw new CompileException('Unknown ' . $node->getNotation() . ", use n:{$node->name} attribute.");
		}
	}



	/**
	 * n:ifcontent
	 */
	function macroEndIfContent(MacroNode $node, PhpWriter $writer)
	{
		$node->openingCode = '<?php ob_start(function () {}); ?>';
		$node->innerContent = '<?php ob_start(); ?>' . $node->innerContent . '<?php $this->global->ifcontent = ob_get_flush(); ?>';
		$node->closingCode = '<?php if (rtrim($this->global->ifcontent) === "") ob_end_clean(); else echo ob_get_clean(); ?>';
	}



	/**
	 * {foreach ...}
	 */
	function macroEndForeach(MacroNode $node, PhpWriter $writer)
	{
		if ( ! Helper::validateForeachExpression($node->args)) {
			//~ throw new CompileException('Expression `{' . $node->name . ' ' . $node->args . '}\' is not supported.');
			$node->openingCode = "{foreach {$node->args}}";
			$node->closingCode = '{/foreach}';
			return;
		}
		$noCheck = Helpers::removeFilter($node->modifiers, 'nocheck');
		$noIterator = Helpers::removeFilter($node->modifiers, 'noiterator');
		if ($node->modifiers) {
			throw new CompileException('Only modifiers |noiterator and |nocheck are allowed here.');
		}
		$node->openingCode = '<?php $iterations = 0; ';
		$args = $writer->formatArgs();
		if (!$noCheck) {
			preg_match('#.+\s+as\s*\$(\w+)(?:\s*=>\s*\$(\w+))?#i', $args, $m);
			for ($i = 1; $i < count($m); $i++) {
				$this->overwrittenVars[$m[$i]][] = $node->startLine;
			}
		}
		if (!$noIterator && preg_match('#\W(\$iterator|include|require|get_defined_vars)\W#', $this->getCompiler()->expandTokens($node->content))) {
			$node->openingCode .= 'foreach ($iterator = $this->global->its[] = new LR\CachingIterator('
				. preg_replace('#(.*)\s+as\s+#i', '$1) as ', $args, 1) . ') { ?>';
			$node->closingCode = '<?php $iterations++; } array_pop($this->global->its); $iterator = end($this->global->its); ?>';
		} else {
			$node->openingCode .= 'foreach (' . $args . ') { ?>';
			$node->closingCode = '<?php $iterations++; } ?>';
		}
	}



	/**
	 * n:class="..."
	 */
	function macroClass(MacroNode $node, PhpWriter $writer)
	{
		if (isset($node->htmlNode->attrs['class'])) {
			throw new CompileException('It is not possible to combine class with n:class.');
		}
		return $writer->write('if ($_tmp = array_filter(%node.array)) echo \' class="\', %escape(implode(" ", array_unique($_tmp))), \'"\'');
	}



	/**
	 * n:attr="..."
	 */
	function macroAttr(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('$_tmp = %node.array; echo LR\Filters::htmlAttributes(isset($_tmp[0]) && is_array($_tmp[0]) ? $_tmp[0] : $_tmp);');
	}



	/**
	 * {dump ...}
	 */
	function macroDump(MacroNode $node, PhpWriter $writer)
	{
		if ($node->modifiers) {
			throw new CompileException('Modifiers are not allowed in ' . $node->getNotation());
		}
		$args = $writer->formatArgs();
		return $writer->write(
			'echo ' . Helper::class . '::dump(' . ($args ? "($args)" : 'get_defined_vars()') . ', %var);',
			$args ?: 'variables'
		);
	}



	function macroRender(MacroNode $node, PhpWriter $writer)
	{
		if ($node->name === '?') {
			trigger_error('Macro {? ...} is deprecated, use {php ...}.', E_USER_DEPRECATED);
		}

		if ( ! Helper::validateVariable($node->args)) {
			//~ throw new CompileException('Variable `{' . $node->name . ' ' . $node->args . '}\' is not supported.');
			return $writer->write("echo %modify('{{$node->name} %node.args}') /* line $node->startLine */");
		}

		return $writer->write("echo @%modify(%node.args) /* line $node->startLine */");
	}

}
