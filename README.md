HoaRulerPHPDumper
=================

## Introduction ##
There are several libraries out for PHP which allow you to interpret another language in PHP. For example [Symfony Expression Language Component](http://symfony.com/doc/current/components/expression_language/index.html) and the [Hoa\Ruler](https://github.com/hoaproject/Ruler). However when executing this "expression language" or "rule" it has overhead from the given libraries instead of being able to execute the code directly. This library attempts to convert the rule from `Hoa\Ruler` to native PHP code.

This opens new possiblities for this library, namely:
1. The native PHP code can be cached for fast execution in production environments.
2. To be used as a code generator.

## State ##
This library is mainly a _proof of concept_ at the moment. It's fully working and tested, but likely needs more integration and cleanup. Please follow [this thread](https://github.com/hoaproject/Ruler/issues/2) about the active discussion around this library.

## How it works internally ##
The library depends on [PHP-Parser](https://github.com/nikic/PHP-Parser) to convert the model made by `Hoa\Ruler` to PHP. It does this by first reading the Abstract Syntax Tree (AST) of `Hoa\Ruler\Model\Model` and then convert it do an AST that describes PHP. The final step is then to convert to PHP-AST to PHP code.

## Examples ##
Please see the unit tests for more examples. This readme will only show one example which is derived from the one in the `Hoa\Ruler` [readme](https://github.com/hoaproject/Ruler/blob/master/README.md).

```php
<?php
/*
 * This part is taken from the Hoa\Ruler readme
 */

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
$asserter = new Asserter($context);
$asserter->setOperator('logged', function ( User $user ) {

		return $user::CONNECTED === $user->getStatus();
});

$ruler = new Ruler();
$ruler->setAsserter($asserter);

/*
 * This is the part where the PHPDumper makes the conversion
 */
$dumper = new PHPDumper(); // Initialize a new PHPDumper object

$closuresPhpAst = $dumper->getClosures($ruler); // Get the PHP-AST for the Hoa\Ruler Operators

$phpAst = $dumper->L(Ruler::interprete($rule)); // Get the PHP-AST for the Hoa\Ruler model

$packedPhpAst = $dumper->pack($phpAst, $closuresPhpAst); // Pack both the rule and it's operators into a single closure (still outputs PHP-AST)

$packedPhp = $this->astToPhp($packedPhpAst); // Finally make actual PHP code

/*
our $packedPhp contains php code as string which looks like this:

$closure = function ($user, $group, $points) {
	$c_logged = function (User $user) {
		return $user::CONNECTED =## $user->getStatus();
	};

	return $c_logged($user) and (in_array($group, array('customer', 'guest')) and $points > 30);
};
*/
```