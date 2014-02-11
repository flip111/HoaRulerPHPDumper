<?php

use PhpParser\PrettyPrinter\Standard;

class PHPDumperTest extends PHPUnit_Framework_TestCase {
	protected static $prettyPrinter;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		self::$prettyPrinter = new Standard;
	}

	private function getPHP($AST) {
		return self::$prettyPrinter->prettyPrint([$AST]);
	}

	private function RuleToCode($rule) {
		return '<?php' . PHP_EOL . Hoa\Ruler\Ruler::interprete($rule);
	}

	public function testAstConvert() {
		$dumper = new PHPDumper();

		//$this->setOperator('and', function ( $a, $b ) { return $a && $b; });
		$rule = 'true and true';
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals($rule . ';', $PHP);

		//$this->setOperator('or',  function ( $a, $b ) { return $a || $b; });
		$rule = 'true or false';
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals($rule . ';', $PHP);

		//$this->setOperator('xor', function ( $a, $b ) { return (bool) ($a ^ $b); });
		$rule = 'true xor false';
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals($rule . ';', $PHP);

		//$this->setOperator('not', function ( $a )     { return !$a; });
		$rule = "not true";
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals("!true;", $PHP);

		//$this->setOperator('=',   function ( $a, $b ) { return $a == $b; });
		$rule = "2 = 3";
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals("2 == 3;", $PHP);

		//$this->setOperator('is',  $this->getOperator('='));
		$rule = "2 is 3";
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals("2 == 3;", $PHP);

		//$this->setOperator('!=',  function ( $a, $b ) { return $a != $b; });
		$rule = '2 != 3';
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals($rule . ';', $PHP);

		//$this->setOperator('>',   function ( $a, $b ) { return $a >  $b; });
		$rule = '2 > 1';
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals($rule . ';', $PHP);

		//$this->setOperator('>=',  function ( $a, $b ) { return $a >= $b; });
		$rule = '2 >= 1';
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals($rule . ';', $PHP);

		//$this->setOperator('<',   function ( $a, $b ) { return $a <  $b; });
		$rule = '1 < 2';
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals($rule . ';', $PHP);

		//$this->setOperator('<=',  function ( $a, $b ) { return $a <= $b; });
		$rule = '1 <= 2';
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals($rule . ';', $PHP);

		//$this->setOperator('in',  function ( $a, Array $b ) { return in_array($a, $b); });
		$rule = "'foo' in ('foo', 'bar')";
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals("in_array('foo', array('foo', 'bar'));", $PHP);

		//$this->setOperator('sum', function ( ) { return array_sum(func_get_args()); });
		$rule = "sum (2, 3)";
		$AST = $dumper->AstConvert($this->RuleToCode($rule));
		$PHP = $this->getPHP($AST);
		$this->assertEquals("array_sum(2, 3);", $PHP);
	}
}
