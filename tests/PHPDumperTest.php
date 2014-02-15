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

	private function astToPhp($phpAst) {
		return self::$prettyPrinter->prettyPrint([$phpAst]);
	}

	private function phpToAst($php) {
		try {
			$stmts = $this->parser->parse($php);
		} catch (PhpParser\Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}

		return $stmts[0];
	}

	private function stripWhiteSpace($string) {
		return preg_replace('/\s+/', '', $string);
	}

	public function testAnd() {
		$dumper = new PHPDumper();

		//$this->setOperator('and', function ( $a, $b ) { return $a && $b; });
		$rule = 'true and false';
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
		$this->assertEquals($rule . ';', $php);

		$php = '<?php true xor false;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('true xor false', $rule);
	}

	public function testNot() {
		$dumper = new PHPDumper();

		$rule = 'not true';
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
		$this->assertEquals('2 == 3;', $php);

		//
		// This test can not be performed from PHP to Hoa,
		// because Hoa defines both '=' and 'is' for '=='
		// where '=' takes precendence.
		//
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testNotEqual() {
		$dumper = new PHPDumper();

		//$this->setOperator('!=',  function ( $a, $b ) { return $a != $b; });
		$rule = '2 != 3';
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
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
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
		$this->assertEquals("array_sum(2, 3);", $php);

		$php = "<?php array_sum(2, 3);";
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals("sum(2, 3)", $rule);
	}

	// https://github.com/hoaproject/Ruler/issues/4
	public function testCombination() {
		$dumper = new PHPDumper();

		$rule = '"foo" in ("foo", "bar") and 50 > 30';
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
		$this->assertEquals("in_array('foo', array('foo', 'bar')) and 50 > 30;", $php);

		$php = "<?php in_array('foo', array('foo', 'bar')) and 50 > 30;";
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals("('foo' in ('foo', 'bar')) and (50 > 30)", $rule);
	}

	public function testVariable() {
		$dumper = new PHPDumper();

		$rule = 'foo';
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
		$this->assertEquals('$foo;', $php);

		$php = '<?php $foo;';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals("foo", $rule);
	}

	public function testOperator1() {
		$dumper = new PHPDumper();

		$rule = 'foo()';
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
		$this->assertEquals('$foo();', $php);

		$php = '<?php $foo();';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals("foo()", $rule);
	}

	public function testOperator2() {
		$dumper = new PHPDumper();

		$rule = 'foo("bar")';
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$php = $this->astToPhp($phpAst);
		$this->assertEquals('$foo(\'bar\');', $php);

		$php = '<?php $foo("bar");';
		$hoa_ast = $dumper->R($this->phpToAst($php));
		$rule = $this->hoaDisassembler->visit($hoa_ast);
		$this->assertEquals('foo(\'bar\')', $rule);
	}

	// Reversed operation not supported at the moment ...
	public function testClosure1() {
		$dumper = new PHPDumper();

		$ruler = new Ruler();
		$ruler->getDefaultAsserter()->setOperator('foo', function() {
			return 'bar';
		});

		$phpAst = $dumper->getClosures($ruler)['foo'];
		$php = $this->astToPhp($phpAst);
		// For testing we don't care about whitespaces
		// Also since the generator php will likely not be edited by the programmer
		$php = $this->stripWhiteSpace($php);
		$this->assertEquals("\$c_foo=function(){return'bar';};", $php);
	}

	public function testPack1() { // Single value
		$dumper = new PHPDumper();

		$rule = 'true';
		$phpAst = $dumper->L(Ruler::interprete($rule));

		$packedPhpAst = $dumper->pack($phpAst);
		$packedPhp = $this->astToPhp($packedPhpAst);
		$packedPhp = $this->stripWhiteSpace($packedPhp);

		$ref = <<<'EOD'
$closure = function() {
    return true;
};
EOD;
		$ref = $this->stripWhiteSpace($ref);

		$this->assertEquals($ref, $packedPhp);
	}

	public function testPack2() { // Just to be sure .. let's test with a more complex example
		$dumper = new PHPDumper();

		$rule = '"foo" in ("foo", "bar") and 50 > 30';
		$phpAst = $dumper->L(Ruler::interprete($rule));

		$packedPhpAst = $dumper->pack($phpAst);
		$packedPhp = $this->astToPhp($packedPhpAst);
		$packedPhp = $this->stripWhiteSpace($packedPhp);

		$ref = <<<'EOD'
$closure = function() {
    return in_array('foo', array('foo', 'bar')) and 50 > 30;
};
EOD;
		$ref = $this->stripWhiteSpace($ref);

		$this->assertEquals($ref, $packedPhp);
	}

	public function testPack3() { // With variables
		$dumper = new PHPDumper();

		$rule = 'foo';
		$phpAst = $dumper->L(Ruler::interprete($rule));

		$packedPhpAst = $dumper->pack($phpAst);
		$packedPhp = $this->astToPhp($packedPhpAst);
		$packedPhp = $this->stripWhiteSpace($packedPhp);

		$ref = <<<'EOD'
$closure = function($foo) {
    return $foo;
};
EOD;
		$ref = $this->stripWhiteSpace($ref);

		$this->assertEquals($ref, $packedPhp);


	}

	public function testPack4() { // With closures
		$dumper = new PHPDumper();

		// Setup the closure
		$ruler = new Ruler();
		$ruler->getDefaultAsserter()->setOperator('foo', function() {
			return 'bar';
		});

		$closuresPhpAst = $dumper->getClosures($ruler);

		$rule = 'foo()';
		$phpAst = $dumper->L(Ruler::interprete($rule));

		$packedPhpAst = $dumper->pack($phpAst, $closuresPhpAst);
		$packedPhp = $this->astToPhp($packedPhpAst);
		$packedPhp = $this->stripWhiteSpace($packedPhp);

		$ref = <<<'EOD'
$closure = function() {
	$c_foo = function(){
		return'bar';
	};

	return $c_foo();
};
EOD;
		$ref = $this->stripWhiteSpace($ref);

		$this->assertEquals($ref, $packedPhp);
	}

	// Warning: This test conflicts with testPack4() because of:
	// https://github.com/hoaproject/Ruler/issues/6
	//
	// This tests the official example of the documentation
	public function testExample() {
		$dumper = new PHPDumper();

		require_once 'user.php'; // eeeww :(

		$ruler = new Hoa\Ruler\Ruler();

		// New rule.
		$rule  = 'logged(user) and group in ("customer", "guest") and points > 30';

		// New context.
		$context         = new Hoa\Ruler\Context();
		$context['user'] = function ( ) use ( $context ) {

				$user              = new User();
				$context['group']  = $user->group;
				$context['points'] = $user->points;

				return $user;
		};

		// We add the logged() operator.
		$ruler->getDefaultAsserter()->setOperator('logged', function ( User $user ) {

				return $user::CONNECTED === $user->getStatus();
		});

		// We add the secret sauce.
		$closuresPhpAst = $dumper->getClosures($ruler);
		$phpAst = $dumper->L(Ruler::interprete($rule));
		$packedPhpAst = $dumper->pack($phpAst, $closuresPhpAst);
		$packedPhp = $this->astToPhp($packedPhpAst);

		$ref = <<<'EOD'
$closure = function ($user, $group, $points) {
    $c_logged = function (User $user) {
        return $user::CONNECTED === $user->getStatus();
    };
    return $c_logged($user) and (in_array($group, array('customer', 'guest')) and $points > 30);
};
EOD;

		$packedPhp = $this->stripWhiteSpace($packedPhp);
		$ref = $this->stripWhiteSpace($ref);

		$this->assertEquals($ref, $packedPhp);
	}

}
