<?php
namespace Compose\Common;

use PHPUnit\Framework\TestCase;

class InvokerTest extends TestCase
{

    public function test__construct()
    {
        $this->assertInstanceOf(
            Invocation::class,
            new Invocation([$this, 'callable1'])
        );
    }


    public function testCanBeCreatedFromClosure()
    {
        $this->assertInstanceOf(
            Invocation::class,
            Invocation::fromCallable(function() { return; })
        );
    }

    public function testCanCreateReflection()
    {
        $invoker = new Invocation(function() {
            return true;
        });

        $this->assertInstanceOf(\ReflectionFunction::class, $invoker->getReflection());
    }

    public function testVerifyWorks()
    {
        $closure = function() {
            return true;
        };


        $invoker = new Invocation($closure);
        $this->expectException(\InvalidArgumentException::class);
        $invoker(true);

        $this->assertIsBool($invoker());

        $closure = function(int $a, string $b = "hello") {
            return $b;
        };

        $this->assertEquals('hello', $closure(4));

        $this->expectException(\InvalidArgumentException::class);
        $closure(4, "1", 3);
    }

    public function testGetArgumentTypeAtIndex()
    {
        $closure = function(int $a, string $b, object $c, ...$args) {
            return true;
        };

        $invoker = new Invocation($closure);

        $this->assertEquals('int', $invoker->getArgumentTypeAtIndex(0));
        $this->assertEquals('string', $invoker->getArgumentTypeAtIndex(1));
        $this->assertEquals('object', $invoker->getArgumentTypeAtIndex(2));
    }



    public function callable1() {

    }
}
