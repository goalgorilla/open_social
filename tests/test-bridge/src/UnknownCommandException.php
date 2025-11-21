<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge;

/**
 * Exception thrown when an invalid command is requested.
 */
class UnknownCommandException extends \Exception {

  public function __construct(
    protected string $commandName,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct("Command '$this->commandName' not found.", 0, $previous);
  }

  /**
   * Get the command name that's unknown.
   *
   * @return string
   *   The command name.
   */
  public function getCommandName() : string {
    return $this->commandName;
  }

}
