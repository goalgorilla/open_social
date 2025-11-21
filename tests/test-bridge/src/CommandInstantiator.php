<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge;

use League\Container\Exception\NotFoundException;
use OpenSocial\TestBridge\Attributes\Command;
use Psr\Container\ContainerInterface;

/**
 * The command instantiator.
 *
 * Responsible for discovering and managing test bridge commands.
 */
class CommandInstantiator {

  /**
   * The command information for this test bridge.
   *
   * @var array<array{
   *    class: class-string,
   *    method: string,
   *    parameters: array<string, array{ type: ?string, nullable: bool }>
   *  }>
   */
  protected array $commands = [];

  /**
   * The instances of command classes.
   *
   * @var array<class-string<T>, T>
   *
   * @template T
   */
  private array $instances = [];

  /**
   * Create a new command instantiator.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The Drupal container.
   */
  public function __construct(
    protected ContainerInterface $container,
  ) {}

  /**
   * Discover test bridge commands.
   *
   * @param string $namespace
   *   The namespace in which the commands are expected.
   * @param string $path
   *   The path in which to search.
   */
  public function discoverCommands(string $namespace, string $path) : void {
    foreach (glob("$path/*.php") as $filename) {
      $class = $namespace . basename($filename, ".php");
      if (class_exists($class)) {
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

          foreach ($commandAttributes as $attribute) {
            $name = $attribute->newInstance()->name;
            if (isset($this->commands[$name])) {
              throw new \RuntimeException("Command '$name' in '$class::$method->name' already exists. Previously defined in '{$this->commands[$name]['class']}::{$this->commands[$name]['method']}'.");
            }

            $this->commands[$name] = [
              'class' => $class,
              'method' => $method->name,
              'parameters' => $parameters,
            ];
          }
        }
      }
    }
  }

  /**
   * Call a command with the provided arguments.
   *
   * @param string $commandName
   *   The name of the command to call.
   * @param array $commandObject
   *   The arguments that should be provided to the command.
   *
   * @return array
   *   The response array of the executed command.
   *
   * @throws \OpenSocial\TestBridge\InvalidCommandArgumentsException
   *   In case the arguments provided for the command are invalid.
   * @throws \OpenSocial\TestBridge\UnknownCommandException
   *   In case the requested command is not registered.
   */
  public function callCommand(string $commandName, array $commandObject) : array {
    if (!isset($this->commands[$commandName])) {
      throw new UnknownCommandException($commandName);
    }

    $command = $this->commands[$commandName];
    $errors = $this->validateCommand($command, $commandObject);
    if ($errors !== []) {
      throw new InvalidCommandArgumentsException($commandName, $errors);
    }

    $instance = $this->getInstance($command['class']);
    $arguments = array_intersect_key($commandObject, $command['parameters']);
    return $instance->{$command['method']}(...$arguments);
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
    if (!isset($this->instances[$class])) {
      $reflection = new \ReflectionClass($class);
      if ($reflection->hasMethod("create") && $reflection->getMethod('create')->isStatic()) {
        try {
          $this->instances[$class] = $class::create($this->container);
        }
        catch (NotFoundException $exception) {
          throw new MissingCommandDependenciesException($class, $exception);
        }
      }
      else {
        $this->instances[$class] = new $class();
      }
    }

    return $this->instances[$class];
  }

  /**
   * Validate that needed data for the command is provided.
   *
   * Does not check for data that's provided but that's not needed. This allows
   * applications to provide data which may be valid in different versions of
   * an implementation.
   *
   * @param array{
   *   class: class-string<T>,
   *   method: string,
   *   parameters: array<string, array{ type: ?string, nullable: bool }>
   * } $command
   *   The discovered command metadata for the command to validate.
   * @param array $commandObject
   *   The arguments that should be provided to the command.
   *
   * @return string[]
   *   An array of errors.
   *
   * @template T
   */
  protected function validateCommand(array $command, array $commandObject) : array {
    $errors = [];

    foreach ($command['parameters'] as $name => $parameter) {
      if (!isset($commandObject[$name])) {
        // Allow nullable parameters to be omitted.
        if ($parameter['nullable']) {
          continue;
        }

        $errors[] = "Missing required argument '$name' of type '{$parameter['type']}'.";
        continue;
      }
      // If the type is null we don't need to validate it.
      if ($parameter['type'] === NULL) {
        continue;
      }

      $actual = get_debug_type($commandObject[$name]);
      if ($actual !== $parameter['type']) {
        $errors[] = "Expected '$name' to be of type '{$parameter['type']}' but received '$actual'.";
      }
    }

    return $errors;
  }

}
