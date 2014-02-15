<?php

use Hoa\Ruler\Model\Bag\Context;
use Hoa\Ruler\Model\Bag\RulerArray;
use Hoa\Ruler\Model\Bag\Scalar;
use Hoa\Ruler\Model\Model;
use Hoa\Ruler\Model\Operator;
use Hoa\Ruler\Visitor\Asserter;
use PhpParser\Lexer;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BinaryOp\LogicalXor;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;

class PHPDumper {
	protected $parser;
	protected $prettyPrinter;
	protected $paramStack;

	public function __construct() {
		$this->parser = new Parser(new Lexer);
		$this->prettyPrinter = new Standard();
//		$this->counterL = 0;
//		$this->counterR = 0;
	}

	private function phpToAst($php) {
		try {
			return $this->parser->parse($php);
		} catch (PhpParser\Error $e) {
			throw new \RuntimeException('Parse Error: ' . $e->getMessage());
		}
	}

	private function getHoaModel($operator, $args = [], $function = false) {
		$model = new Model();
		$model->_root = new Operator($operator, $args, $function);
		return $model;
	}

	public function L($in) {
//echo 'L ' . $this->counterL++ . PHP_EOL;
//var_dump($in);
//echo PHP_EOL;

		if ( ! is_object($in)) {
			if (is_bool($in)) {
				return new ConstFetch(new Name($in ? 'true' : 'false'));
			}
			if (is_numeric($in)) {
				return new LNumber($in);
			}
			if (is_string($in)) {
				return new String($in);
			}
		}

		if ($in instanceof Scalar) {
			return $this->L($in->getValue());
		}

		if ($in instanceof RulerArray) {
			unset($return);
			foreach ($in->getArray() as $v) {
				$return[] = new ArrayItem($this->L($v));
			}
			return new Array_($return);
		}

		// unwrap the model
		if ($in instanceof Model) {
			return $this->L($in->getExpression());
		}

		if ($in instanceof Context) {
			return new Variable($in->getId());
		}

		$name = $in->getName();
		$args = $in->getArguments();

		switch ($name) {
			case 'and':
			case 'or':
			case 'xor':
				$classname = 'PhpParser\\Node\\Expr\\BinaryOp\\Logical' . $name;
				return new $classname($this->L($args[0]), $this->L($args[1]));
				break;
			case 'not':
				return new BooleanNot($this->L($args[0]));
				break;
			case '=':
			case 'is':
				return new Equal($this->L($args[0]), $this->L($args[1]));
				break;
			case '!=':
				return new NotEqual($this->L($args[0]), $this->L($args[1]));
				break;
			case '>':
				return new Greater($this->L($args[0]), $this->L($args[1]));
				break;
			case '>=':
				return new GreaterOrEqual($this->L($args[0]), $this->L($args[1]));
				break;
			case '<':
				return new Smaller($this->L($args[0]), $this->L($args[1]));
				break;
			case '<=':
				return new SmallerOrEqual($this->L($args[0]), $this->L($args[1]));
				break;
			case 'in':
				// Packing argument
				$args = [new Arg($this->L($args[0])), new Arg($this->L($args[1]))];
				return new FuncCall(new Name('in_array'), $args);
				break;
			case 'sum':
				unset($returnArgs);
				foreach ($args as $v) {
					$returnArgs[] = new Arg($this->L($v));
				}
				return new FuncCall(new Name('array_sum'), $returnArgs);
		}

		if ($in->isFunction()) { // closure
			$returnArgs = [];
			foreach ($args as $v) {
				$returnArgs[] = new Arg($this->L($v));
			}

			return new FuncCall(new Variable($name), $returnArgs);
		}
	}

	public function R($in) {
//echo 'R ' . $this->counterR++ . PHP_EOL;
//var_dump($in);
//echo PHP_EOL;

		// Unpacking the argument
		if ($in instanceof Arg) {
			$in = $in->value;
		}

		$fqcn = get_class($in);
		$class = substr($fqcn, strrpos($fqcn, '\\') + 1);

		if ($in instanceof Variable) {
			return new Context($in->name);
		}

		if ($in instanceof Array_) {
			unset($return);
			foreach ($in->items as $v) {
				$return[] = $this->R($v->value);
			}
			return new RulerArray($return);
		}

		if ($in instanceof ConstFetch) {
			if ($in->name->parts[0] === 'true') {
				return new Scalar(true);
			}
			if ($in->name->parts[0] === 'false') {
				return new Scalar(false);
			}
		}

		if (($in instanceof LNumber) OR
				($in instanceof String)) {
			return new Scalar($in->value);
		}

		if (substr($class, 0, 7) === 'Logical') {
			$operator = strtolower(substr($class, 7));
			return $this->getHoaModel($operator, [$this->R($in->left), $this->R($in->right)]);
		}

		switch ($class) {
			case 'NotEqual':
				return $this->getHoaModel('!=', [$this->R($in->left), $this->R($in->right)]);
				break;
			case 'Equal':
				return $this->getHoaModel('=', [$this->R($in->left), $this->R($in->right)]);
				break;
			case 'BooleanNot':
				return $this->getHoaModel('not', [$this->R($in->expr)]);
				break;
			case 'Greater':
				return $this->getHoaModel('>', [$this->R($in->left), $this->R($in->right)]);
				break;
			case 'GreaterOrEqual':
				return $this->getHoaModel('>=', [$this->R($in->left), $this->R($in->right)]);
				break;
			case 'Smaller':
				return $this->getHoaModel('<', [$this->R($in->left), $this->R($in->right)]);
				break;
			case 'SmallerOrEqual':
				return $this->getHoaModel('<=', [$this->R($in->left), $this->R($in->right)]);
				break;
			case 'FuncCall':
				if ($in->name->parts[0] === 'in_array') {
					return $this->getHoaModel('in', [$this->R($in->args[0]), $this->R($in->args[1])]);
				}

				if ($in->name->parts[0] === 'array_sum') {
					unset($returnArgs);
					foreach ($in->args as $v) {
						$returnArgs[] = $this->R($v);
					}
					return $this->getHoaModel('sum', $returnArgs, true);
				}

				if ($in->name instanceof Variable) { // closure
					$returnArgs = [];
					foreach ($in->args as $v) {
						$returnArgs[] = $this->R($v);
					}
					return $this->getHoaModel($in->name->name, $returnArgs, true);
				}

				break;
		}
	}

	public function getClosures($ruler) {
		$operators = $ruler->getDefaultAsserter()->getOperators();

		$callbacks = [];
		foreach ($operators as $name => $operator) {
			if (in_array($name, ['and', 'or', 'xor', 'not', '=', 'is', '!=', '>', '>=', '<', '<=', 'in', 'sum'])) {
				// Skip operator if it was not defined by the user.
				continue;
			}

			$callbacks[$name] = $operator->getValidCallback();
		}

		$closures = [];
		foreach ($callbacks as $name => $callback) {
			$refl = ReflectionFunction::export($callback, true);
			$refl = explode("\n", $refl);
			preg_match('/  @@ (?P<path>.*) (?P<from_line>\d+) - (?P<to_line>\d+)/i', $refl[1], $codeInfo);

			$file = file_get_contents($codeInfo['path']);

			$closure = $this->findAstByLine($this->phpToAst($file), $codeInfo['from_line']);

			$closures[$name] = new Assign(new Variable('c_'.$name), $closure);
		}

		return $closures;
	}

	private function findAstByLine($stmts, $start = null, $end = null) {
		if ( ! is_array($stmts)) {
			$stmts = [$stmts];
		}

		foreach ($stmts as $stmt) {
			// When hitting a literal value which doesn't contain the lines we want
			// then the search for the right piece of code is dead for this leave in the AST tree
			if ( ! is_object($stmt)) {
				continue;
			}

			if (isset($start) AND isset($end) AND
				$start == $stmt->getAttribute('startLine') AND
				$end == $stmt->getAttribute('endLine')) {
					// The return statement is specific for a statement like this:
					// $ruler->getDefaultAsserter()->setOperator('foo', function() {
					// 	return 'bar';
					// });
					return $stmt->args[1]->value;
			} elseif (isset($start) AND ! isset($end) AND
				$start == $stmt->getAttribute('startLine')) {
					return $stmt->args[1]->value;
			} elseif (isset($end) AND ! isset($start) AND
				$end == $stmt->getAttribute('endLine')) {
					return $stmt->args[1]->value;
			}

			foreach ($stmt->getSubNodeNames() as $name) {
				$return = $this->findAstByLine($stmt->$name, $start, $end);
				if ($return !== null) {
					return $return;
				}
			}
		}

		// When hitting a literal value which doesn't contain the lines we want
		// then the search for the right piece of code is dead for this leave in the AST tree
	}

	public function astToPhp($phpAst) {
		return $this->prettyPrinter->prettyPrint([$phpAst]);
	}

	public function pack($phpAst, $closuresAst = []) {
		$closureNames = array_keys($closuresAst);
		$params = $this->getParams($phpAst, $closureNames);

		$ruleAst = new Return_($phpAst);

		// Rename the closure variable in the rule-php
		foreach ($closureNames as $name) {
			$this->renameClosureVariable($phpAst, $name);
		}

		$closuresAst[] = $ruleAst;

		return new Assign(new Variable('closure'), new Closure([
			'static' => false,
			'byRef' => false,
			'params' => $params,
			'uses' => [],
			'stmts' => $closuresAst
		]));
	}

	private function renameClosureVariable($stmts, $searchName) {
		if ( ! is_array($stmts)) {
			$stmts = [$stmts];
		}

		foreach ($stmts as $stmt) {
			if ( ! is_object($stmt)) {
				continue;
			}

			// Function name is a variable .. thus a closure is being used here.
			if ($stmt instanceof FuncCall AND
					$stmt->name instanceof Variable) {
				if ($stmt->name->name == $searchName) {
					$stmt->name->name = 'c_' . $searchName;
				}
			}

			foreach ($stmt->getSubNodeNames() as $name) {
				$return = $this->renameClosureVariable($stmt->$name, $searchName);
				if ($return !== null) {
					return $return;
				}
			}

		}
	}

	private function getParams($stmts, array $ignore) {
		$this->paramStack = []; // Clear stack
		$this->findParams($stmts);
		$paramStack = array_unique($this->paramStack);
		$params = [];
		foreach ($paramStack as $name) {
			if (in_array($name, $ignore)) {
				continue;
			}

			$params[] = new Param($name);
		}
		return $params;
	}

	private function findParams($stmts) {
		if ( ! is_array($stmts)) {
			$stmts = [$stmts];
		}

		foreach ($stmts as $stmt) {
			if ( ! is_object($stmt)) {
				continue;
			}

			// Function name is a variable .. thus a closure is being used here.
			if ($stmt instanceof Variable) {
				$this->paramStack[] = $stmt->name; // push on stack
			}

			foreach ($stmt->getSubNodeNames() as $name) {
				$return = $this->findParams($stmt->$name);
				if ($return !== null) {
					return $return;
				}
			}
		}
	}
}
