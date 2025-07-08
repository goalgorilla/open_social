<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge;

use OpenSocial\TestBridge\Attributes\Command;
use Psr\Container\ContainerInterface;

class CommandInstantiator {

  public function __construct(
    protected ContainerInterface $container
  ) {}

  public function autowireCommands(string $namespace, string $path) : array {
    $commands = [];
    foreach (glob("$path/*.php") as $filename) {
      $class = $namespace . basename($filename, ".php");
      if (class_exists($class)) {
        $instance = NULL;
        $reflection = new \ReflectionClass($class);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
          $commandAttributes = $method->getAttributes(Command::class);
          if (count($commandAttributes) === 0) {
            continue;
          }

          $parameters = [];
          foreach ($method->getParameters() as $parameter) {
            if (!($parameter->getType()?->isBuiltin() ?? TRUE)) {
              throw new \RuntimeException("Parameter {$parameter->name} to $class::{$method->name} is not of scalar type.");
            }

            $parameters[$parameter->name] = [
              'type' => $parameter->getType()?->getName() ?? NULL,
              'nullable' => $parameter->getType()?->allowsNull() ?? TRUE,
            ];
          }

          $instance ??= $this->getInstance($class);
          foreach ($commandAttributes as $attribute) {
            $name = $attribute->newInstance()->name;
            if (isset($commands[$name])) {
              $existing_class = get_class($commands[$name]['instance']);
              throw new \RuntimeException("Command '$name' in '$class::{$method->name}' already exists. Previously defined in '{$existing_class}::{$commands['method']}'.");
            }

            $commands[$name] = [
              'instance' => $instance,
              'method' => $method->name,
              'parameters' => $parameters,
            ];
          }
        }
      }
    }

    return $commands;
  }

  /**
   * Create a new instance of a class.
   *
   * Uses a create method with the current container if available or
   * instantiates a class without arguments otherwise.
   *
   * @param class-string<T> $class
   *   The class to get an instance from.
   *
   * @return T
   *   The new instance of the class.
   *
   * @template T
   */
  protected function getInstance(string $class) : object {
    $reflection = new \ReflectionClass($class);
    if ($reflection->hasMethod("create") && $reflection->getMethod('create')->isStatic()) {
      return $class::create($this->container);
    }
    return new $class();
  }

}
