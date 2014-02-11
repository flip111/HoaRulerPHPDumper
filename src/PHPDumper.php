<?php

use PhpParser\Lexer;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String;
use PhpParser\Parser;

class PHPDumper {
	protected $parser;

	public function __construct() {
		$this->parser = new Parser(new Lexer);
		$this->counter = 0;
	}

	/**
	 * I don't know how to directly get the Hoa AST tree.
	 * Only output as string was available o_O
	 */
	public function AstConvert($string) {
		try {
			$stmts = $this->parser->parse($string);
		} catch (PhpParser\Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}

		return $this->visit($stmts[1]);
	}

	private function visit($in) {
		if ($in instanceof LNumber) {
			return $this->getCleanClone($in);
		}

		if ($in instanceof String) {
			return $this->getCleanClone($in);
		}

		if ($in instanceof Arg AND
				$in->value instanceof String) {
			return new Arg($this->visit($in->value), $in->byRef);
		}

		if ($in instanceof Arg AND
				$in->value instanceof LNumber) {
			return new Arg($this->visit($in->value), $in->byRef);
		}

		if ($in instanceof ArrayItem) {
			return new ArrayItem($this->visit($in->value), $in->key, $in->byRef);
		}

		if ($in instanceof Arg AND
				$in->value instanceof Array_) {
			unset($return);
			foreach ($in->value->items as $v) {
				$return[] = $this->visit($v);
			}
			return new Array_($return);
		}

		if ($in->value instanceof ConstFetch) {
			return $this->getCleanClone($in->value);
		}

		if ($in->expr instanceof MethodCall) {
			if ($in->expr->name === 'func' AND
					$in->expr->args[0]->value->value === 'sum') {
				unset($return);
				foreach ($in->expr->args as $k => $arg) {
					if ($k === 0) continue;
					$return[] = $this->visit($arg);
				}
				return new FuncCall(new Name('array_sum'), $return);
			}

			unset($return);
			foreach ($in->expr->args as $v) {
				$return[] = $this->visit($v);
			}

			switch ($in->expr->name) {
				case 'or':
				case 'and':
				case 'xor':
					$classname = 'PhpParser\\Node\\Expr\\BinaryOp\\Logical' . $in->expr->name;
					return new $classname($return[0], $return[1]);
					break;
				case 'is':
					return new Equal($return[0]->value, $return[1]->value);
					break;
				case 'in':
					return new FuncCall(new Name('in_array'), $return);
					break;
				case 'not':
					return new BooleanNot($in->expr->args[0]->value);
			}

			if ($in->expr->name instanceof String) {
				switch ($in->expr->name->value) {
					case '=':
						return new Equal($return[0]->value, $return[1]->value);
						break;
					case '!=':
						return new NotEqual($return[0]->value, $return[1]->value);
						break;
					case '>':
						return new Greater($return[0]->value, $return[1]->value);
						break;
					case '>=':
						return new GreaterOrEqual($return[0]->value, $return[1]->value);
						break;
					case '<':
						return new Smaller($return[0]->value, $return[1]->value);
						break;
					case '<=':
						return new SmallerOrEqual($return[0]->value, $return[1]->value);
						break;
				}
			}
		}
	}

	private function getCleanClone($obj) {
		$obj = clone $obj;

		foreach ($obj->getAttributes() as $k => $v) {
			$obj->setAttribute($k, null);
		}

		return $obj;
	}
}
