<?php

use PhpParser\PrettyPrinter\Standard;
use Hoa\Ruler\Ruler;
use PhpParser\Lexer;
use PhpParser\Parser;

class PHPDumperTest extends PHPUnit_Framework_TestCase {
	protected static $prettyPrinter;
	protected $parser;
	protected $hoaDisassembler;

	public function __construct() {
		$this->parser = new Parser(new Lexer);
		$this->hoaDisassembler = new Hoa\Ruler\Visitor\Disassembly;
	}
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		self::$prettyPrinter = new Standard;
	}

	private function astToPhp($AST) {
		return self::$prettyPrinter->prettyPrint([$AST]);
	}

	private function phpToAst($php) {
		try {
			$stmts = $this->parser->parse($php);
		} catch (PhpParser\Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}

		return $stmts[0];
	}

	public function testAnd() {
		$dumper = new PHPDumper();

		//$this->setOperator('and', function ( $a, $b ) { return $a && $b; });
		$rule = 'true and false';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php true and false;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('true and false', $rule);
	}

	public function testOr() {
		$dumper = new PHPDumper();

		//$this->setOperator('or',  function ( $a, $b ) { return $a || $b; });
		$rule = 'true or false';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php true or false;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('true or false', $rule);
	}

	public function testXor() {
		$dumper = new PHPDumper();

		//$this->setOperator('xor', function ( $a, $b ) { return (bool) ($a ^ $b); });
		$rule = 'true xor false';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php true xor false;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('true xor false', $rule);
	}

	public function testNot() {
		$dumper = new PHPDumper();

		$rule = 'not true';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals('!true;', $php);

		$php = '<?php !true;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('not true', $rule);
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testEqual() {
		$dumper = new PHPDumper();

		//$this->setOperator('=',   function ( $a, $b ) { return $a == $b; });
		$rule = '2 = 3';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals('2 == 3;', $php);

		$php = '<?php 2 == 3;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('(2 = 3)', $rule);
	}

	public function testIs() {
		$dumper = new PHPDumper();

		//$this->setOperator('is',  $this->getOperator('='));
		$rule = '2 is 3';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals('2 == 3;', $php);

		/*
		 * This test can not be performed from PHP to Hoa,
		 * because Hoa defines both '=' and 'is' for '=='
		 * where '=' takes precendence.
		 */
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testNotEqual() {
		$dumper = new PHPDumper();

		//$this->setOperator('!=',  function ( $a, $b ) { return $a != $b; });
		$rule = '2 != 3';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php 2 != 3;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('(2 != 3)', $rule);
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testGreater() {
		$dumper = new PHPDumper();

		//$this->setOperator('>',   function ( $a, $b ) { return $a >  $b; });
		$rule = '2 > 1';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php 2 > 1;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('(2 > 1)', $rule);
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testGreaterOrEqual() {
		$dumper = new PHPDumper();

		//$this->setOperator('>=',   function ( $a, $b ) { return $a >  $b; });
		$rule = '2 >= 1';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php 2 >= 1;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('(2 >= 1)', $rule);
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testSmaller() {
		$dumper = new PHPDumper();

		//$this->setOperator('<',   function ( $a, $b ) { return $a >  $b; });
		$rule = '2 < 1';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php 2 < 1;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('(2 < 1)', $rule);
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testSmallerOrEqual() {
		$dumper = new PHPDumper();

		//$this->setOperator('<=',   function ( $a, $b ) { return $a >  $b; });
		$rule = '2 <= 1';
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php 2 <= 1;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('(2 <= 1)', $rule);
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testInArray() {
		$dumper = new PHPDumper();

		//$this->setOperator('in',  function ( $a, Array $b ) { return in_array($a, $b); });
		$rule = "'foo' in ('foo', 'bar')";
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals("in_array('foo', array('foo', 'bar'));", $php);

		$php = "<?php in_array('foo', array('foo', 'bar'));";
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals("('foo' in ('foo', 'bar'))", $rule);
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testSum() {
		$dumper = new PHPDumper();

		//$this->setOperator('sum', function ( ) { return array_sum(func_get_args()); });
		$rule = "sum(2, 3)";
		$php_ast = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($php_ast);
		$this->assertEquals("array_sum(2, 3);", $php);

		$php = "<?php array_sum(2, 3);";
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals("sum(2, 3)", $rule);
	}
}
