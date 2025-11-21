<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge;

/**
 * Exception thrown when a command could not be instantiated.
 */
class MissingCommandDependenciesException extends \Exception {

  public function __construct(
    protected string $className,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct("Command class '$this->className' is missing one or more dependencies. Perhaps the module it depends on is not enabled.", 0, $previous);
  }

  /**
   * Get the class that is missing dependencies.
   *
   * @return string
   *   The class name.
   */
  public function getClassName() : string {
    return $this->className;
  }

}
