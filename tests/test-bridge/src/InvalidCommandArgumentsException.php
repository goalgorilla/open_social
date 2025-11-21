<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge;

/**
 * Exception thrown when invalid arguments are provided to a command.
 */
class InvalidCommandArgumentsException extends \Exception {

  public function __construct(
    protected string $commandName,
    protected array $errors,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct("Invalid arguments provided to the command '$this->commandName': " . implode(", ", $this->errors), 0, $previous);
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

  /**
   * Get the errors for the parameters.
   *
   * @return array
   *   The validation errors.
   */
  public function getErrors() : array {
    return $this->errors;
  }

}
