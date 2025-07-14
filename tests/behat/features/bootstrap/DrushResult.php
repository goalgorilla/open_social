<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

readonly class DrushResult {

  /**
   * @param int $exitCode
   *   The exit code returned by the process.
   * @param string $output
   *   The output of the process (STDOUT).
   * @param string $errorOutput
   *   The error output of the process (STDERR).
   */
  public function __construct(
    public int $exitCode,
    public string $output,
    public string $errorOutput,
  ) {}

}
