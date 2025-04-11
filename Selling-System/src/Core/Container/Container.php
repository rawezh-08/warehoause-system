<?php

namespace App\Core\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;
use App\Core\Container\Exceptions\ContainerException;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function get(string $abstract)
    {
        // If we have an instance, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete implementation
        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

        // If the concrete is a Closure, execute it
        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        // If it's a singleton, store the instance
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    private function build(string $concrete)
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = array_map(
            function (ReflectionParameter $param) {
                $type = $param->getType();
                
                if (is_null($type)) {
                    throw new ContainerException(
                        "Failed to resolve class {$param->getDeclaringClass()->getName()} because param {$param->getName()} is missing a type hint"
                    );
                }

                if ($type->isBuiltin()) {
                    if ($param->isDefaultValueAvailable()) {
                        return $param->getDefaultValue();
                    }
                    throw new ContainerException(
                        "Failed to resolve class {$param->getDeclaringClass()->getName()} because of invalid param {$param->getName()}"
                    );
                }

                return $this->get($type->getName());
            },
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
} 