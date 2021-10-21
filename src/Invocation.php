<?php
namespace Compose\Common;


use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Closure;

/**
 *
 */
class Invocation
{
    protected null|ReflectionFunctionAbstract $reflection = null;
    protected Closure $closure;
    protected ?array $params;

    /**
     * @param callable $callable
     * @param array|null $params
     */
    public function __construct(callable $callable, array $params = null)
    {
        $this->closure = Closure::fromCallable($callable);
        $this->params = $params;
    }

    /**
     * @param callable $callable
     * @param array|null $params
     * @return static|null
     */
    static public function fromCallable(callable $callable, array $params = null) : ?self
    {
        return new self($callable, $params);
    }

    /**
     * @return array|null
     */
    public function getParameters(): ?array
    {
        return $this->params;
    }

    /**
     * @return Closure
     */
    public function getClosure(): Closure
    {
        return $this->closure;
    }

    /**
     * @return ReflectionFunction|null
     */
    public function getReflection() : ?ReflectionFunction
    {
        if(!$this->reflection) {
            try {
                $this->reflection = new ReflectionFunction($this->closure);
            } catch (ReflectionException $e) {
                return null;
            }
        }

        return $this->reflection;
    }

    /**
     * @param ...$params
     * @return mixed
     */
    public function __invoke(...$params) : mixed
    {
        $reflection = $this->getReflection();
        $params = $params ?: $this->getParameters();

        $this->verify($reflection, $params);

        return call_user_func_array($this->closure, $params);
    }

    /**
     * @param ReflectionFunctionAbstract $method
     * @param array $args
     */
    protected function verify(ReflectionFunctionAbstract $method, array $args = [])
    {
        // now we will validate the function with given $args
        $argsCount = count($args);
        $paramsCount = $method->getNumberOfParameters();
        $requiredParamsCount = 0;

        if (!$method->isVariadic()) { // for non-variadic methods, we can do traditional checks for params
            $requiredParamsCount = $method->getNumberOfRequiredParameters();
        } else {
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->isVariadic()) {
                    // if we find variadic params
                    // we need to allow all other input arguments
                    $paramsCount = $argsCount;
                    break;
                }

                if ($parameter->isOptional()) {
                    break;
                }

                $requiredParamsCount++;
            }
        }

        if ($argsCount < $requiredParamsCount) {
            throw new InvalidArgumentException("{$method->getName()}: Invalid Param count. (Params ({$argsCount}) are less then method anticipates ({$requiredParamsCount}))");
        }

        if ($argsCount > $paramsCount) {
            throw new InvalidArgumentException("{$method->getName()}: Invalid Param count. (Params ({$argsCount}) are more than method anticipates ({$requiredParamsCount}))");
        }
    }


    /**
     * @param int $index
     * @return null|string
     */
    public function getArgumentTypeAtIndex(int $index) : ?string
    {
        $reflection = $this->getReflection();
        $param = $reflection->getParameters()[$index] ?? null;
        if(!$param) return null;
        if($param->getType()) {
            return $param->getType()->getName();
        }

        return null;
    }
}