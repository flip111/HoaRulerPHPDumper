<?php

use Hoa\Ruler\Model\Bag\RulerArray;
use Hoa\Ruler\Model\Bag\Scalar;
use Hoa\Ruler\Model\Model;
use Hoa\Ruler\Model\Operator;
use PhpParser\Lexer;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
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
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String;
use PhpParser\Parser;

class PHPDumper {
	protected $parser;

	public function __construct() {
		$this->parser = new Parser(new Lexer);
//		$this->counterL = 0;
//		$this->counterR = 0;
	}

	public function L($in) {
//echo 'L ' . $this->counterL++ . PHP_EOL;
//var_dump($in);
//echo PHP_EOL;

		if ($in instanceof Scalar) {
			$val = $in->getValue();
			if (is_bool($val)) {
				return new ConstFetch(new Name($val ? 'true' : 'false'));
			}
			if (is_numeric($val)) { // is_numeric ??
				return new LNumber($val);
			}
			if (is_string($val)) {
				return new String($val);
			}
		}

		if ($in instanceof RulerArray) {
			unset($return);
			foreach ($in->getArray() as $v) {
				$return[] = new ArrayItem($this->L($v));
			}
			return new Array_($return);
		}

		$expr = $in->getExpression();
		$name = $expr->getName();
		$args = $expr->getArguments();
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
				break;
		}
	}

	private function getHoaModel($operator, $args, $function = false) {
		$model = new Model();
		$model->_root = new Operator($operator, $args, $function);
		return $model;
	}
}
