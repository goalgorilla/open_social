<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Drush\Commands;

use Consolidation\AnnotatedCommand\Input\StdinAwareInterface;
use Consolidation\AnnotatedCommand\Input\StdinAwareTrait;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use OpenSocial\TestBridge\CommandInstantiator;
use Psr\Container\ContainerInterface;

final class TestBridgeDrushCommands extends DrushCommands implements StdinAwareInterface {

  use StdinAwareTrait;

  protected function __construct(
    protected array $commands,
  ) {}

  /**
   * Use container injection to be able to auto-wire bridge command classes.
   *
   * @param \Psr\Container\ContainerInterface $container
   *
   * @return self
   */
  public static function create(ContainerInterface $container): self {
    $commands = self::autoWireCommands($container);

    return new self($commands);
  }

  /**
   * Run the test bridge.
   */
  #[CLI\Command(name: "test-bridge")]
  public function start() {
    $stream = $this->stdin()->getStream();
    if ($stream === FALSE) {
      $this->stderr()->writeln("Could not read from input.");
    }

    while($input = fgets(STDIN)){
      if (!json_validate($input)) {
        $this->stderr()->writeln("Invalid JSON input, expected one valid JSON object per line.");
        break;
      }

      $commandObject = json_decode($input, TRUE);

      if (!isset($commandObject['command'])) {
        $this->signalError("Must specify 'command' in JSON object.");
        break;
      }

      $commandName = $commandObject['command'];

      if ($commandName === "exit") {
        $this->signalOk();
        break;
      }

      if (!isset($this->commands[$commandName])) {
        $this->signalError("Command '$commandName' not found");
        break;
      }

      $command = $this->commands[$commandName];
      $errors = $this->validateCommand($command, $commandObject);
      if ($errors !== []) {
        $this->signalError(implode(", ", $errors));
        break;
      }

      $arguments = array_intersect_key($commandObject, $command['parameters']);
      $this->outputJson(call_user_func_array([$command['instance'], $command['method']], $arguments));
    }
  }

  protected static function autowireCommands(ContainerInterface $container) : array {
    return (new CommandInstantiator($container))->autowireCommands(
      "OpenSocial\\TestBridge\\Bridge\\",
      __DIR__ . "/../../Bridge"
    );
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
   *   instance: T,
   *   parameters: array<string, array{ type: ?string, nullable: bool }>
   * } $command
   * @param array $commandObject
   *
   * @return array
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

  protected function signalOk() : void {
    $this->outputJson(["status" => "ok"]);
  }

  protected function signalError(string $error) : void {
    $this->outputJson(["status" => "error", "error" => $error]);
  }

  protected function outputJson(mixed $output) : void {
    echo json_encode($output) . "\n";
  }

}
