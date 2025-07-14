<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Symfony\Component\Process\Process;

/**
 * Makes it easy to run Drush commands in test contexts.
 *
 * This trait assumes that Drush is available as an executable binary from our
 * test runner instance.
 */
trait DrushTrait {

  /**
   * Execute a Drush command.
   *
   * This is a wrapper around Symfony\Component\Process\Process.
   *
   * @param array $arguments
   *   The command and arguments that should be passed to Drush.
   *
   * @return \Drupal\social\Behat\DrushResult
   *   The result of running Drush.
   *
   * @throws Symfony\Component\Process\Exception\RuntimeException
   *   When the process can't be launched.
   * @throws Symfony\Component\Process\Exception\RuntimeException
   *   When the process is already running.
   * @throws Symfony\Component\Process\Exception\ProcessTimedOutException
   *   When the process timed out.
   * @throws Symfony\Component\Process\Exception\ProcessSignaledException
   *   When the process stopped after receiving a signal.
   */
  public function drush(array $arguments) : DrushResult {
    $process = new Process(['drush', ...$arguments]);
    $process->setTimeout(3600);
    $process->run();

    return new DrushResult(
      // Type-cast since ::run() forces the application to be done.
      exitCode: (int) $process->getExitCode(),
      output: $process->getOutput(),
      errorOutput: $process->getErrorOutput(),
    );
  }

}
