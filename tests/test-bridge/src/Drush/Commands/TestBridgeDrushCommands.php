<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Drush\Commands;

use Consolidation\AnnotatedCommand\Input\StdinAwareInterface;
use Consolidation\AnnotatedCommand\Input\StdinAwareTrait;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use OpenSocial\TestBridge\CommandInstantiator;
use Psr\Container\ContainerInterface;

/**
 * Provides a bridge between the Drupal installation and an external program.
 *
 * The bridge communicates in JSON using STDIN/STDOUT. Each JSON object should
 * be on its own line, terminated by a newline. A command object should contain
 * at least `{ "command": "<command>" }`. The other properties in the object
 * should be the arguments that get passed to the command.
 */
final class TestBridgeDrushCommands extends DrushCommands implements StdinAwareInterface {

  use StdinAwareTrait;

  protected function __construct(
    protected CommandInstantiator $commandInstantiator,
  ) {}

  /**
   * Use container injection to be able to auto-wire bridge command classes.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The container for the application.
   *
   * @return self
   *   A new command instance.
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      new CommandInstantiator($container),
    );
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

    $this->commandInstantiator->discoverCommands(
      "OpenSocial\\TestBridge\\Bridge\\",
      __DIR__ . "/../../Bridge"
    );

    while ($input = fgets(STDIN)) {
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

      try {
        $this->outputJson(
          $this->commandInstantiator->callCommand($commandName, $commandObject)
        );
      }
      catch (\Exception $exception) {
        $this->signalError($exception->getMessage());
        break;
      }
    }
  }

  /**
   * Provide a response that the previous command is ok without output.
   */
  protected function signalOk() : void {
    $this->outputJson(["status" => "ok"]);
  }

  /**
   * Provide a response that the previous command errored.
   *
   * @param string $error
   *   The error.
   */
  protected function signalError(string $error) : void {
    $this->outputJson(["status" => "error", "error" => $error]);
  }

  /**
   * Provide a response.
   *
   * @param mixed $output
   *   The output to send.
   */
  protected function outputJson(mixed $output) : void {
    echo json_encode($output) . "\n";
  }

}
