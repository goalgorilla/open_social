<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Uses the test-bridge to communicate with Open Social.
 *
 * The test-bridge is a long running Drush command that can run code within the
 * context of a Drupal website. This allows building a version agnostic API that
 * may be used by multiple external tools.
 *
 * This context provides a way for Behat contexts to interact with the bridge.
 */
final class TestBridgeContext implements Context {

  /**
   * The open process.
   */
  private Process $process;

  /**
   * The input stream used to send commands over the bridge.
   */
  private InputStream $inputStream;

  /**
   * The buffer of incremental output for the bridge.
   */
  private string $buffer = "";

  /**
   * Start the bridge connection.
   *
   * @BeforeScenario
   */
  public function startBridge() : void {
    $this->inputStream = new InputStream();

    $this->process = new Process(
      ["drush", "test-bridge"]
    );
    $this->process->setTimeout(3600);
    $this->process->setInput($this->inputStream);
    $this->process->start();
  }

  /**
   * Stop the bridge connection.
   *
   * @AfterScenario
   */
  public function stopBridge() : void {
    $this->command("exit");
    $this->inputStream->close();
    $this->process->wait();
  }

  /**
   * Restart the bridge connection.
   *
   * Should be used in case actions are taken that may change the container
   * used in the process (e.g. enabling or disabling a module).
   */
  public function restart() : void {
    $this->stopBridge();
    $this->startBridge();
  }

  /**
   * Send a command to the bridge process.
   *
   * @param string $command
   *   The command that is registered with the bridge.
   * @param mixed ...$data
   *   Named arguments that correspond to the name and type of the data the
   *   command expects.
   *
   * @return array
   *   The returned response from the bridge.
   */
  public function command(string $command, ...$data) : array {
    $this->inputStream->write(json_encode([...$data, "command" => $command]) . "\n");
    $line = $this->getNextLine();
    if ($line === NULL) {
      $error = $this->process->getErrorOutput();
      throw new \RuntimeException("Bridge process exited unexpectedly: $error");
    }
    if (!json_validate($line)) {
      throw new \RuntimeException("Bridge process returned malformed JSON: '$line'");
    }
    return json_decode($line, TRUE);
  }

  /**
   * Run Drush.
   *
   * @param array $arguments
   *   The arguments to provide.
   *
   * @return string
   *   The output if there is any and the error output otherwise.
   */
  public function drush(array $arguments) : string {
    $process = new Process(['drush', ...$arguments]);
    $process->setTimeout(3600);
    $process->run();

    // Some drush commands write to standard error output (for example enable
    // use drush_log which default to _drush_print_log) instead of returning a
    // string (drush status use drush_print_pipe).
    if (!$process->getOutput()) {
      return $process->getErrorOutput();
    }

    return $process->getOutput();
  }

  /**
   * Determines whether a given module is enabled.
   *
   * @param string $module
   *   The name of the module (without the .module extension).
   *
   * @return bool
   *   TRUE if the module is both installed and enabled.
   */
  public function moduleExists(string $module) : bool {
    $response = $this->command('module-exists', module: $module);
    return $response['exists'];
  }

  /**
   * Install modules using Drush.
   *
   * Ensures the bridge is properly restarted afterwards.
   *
   * @param array $modules
   *   The modules to install.
   *
   * @return string
   *   The output if there is any and the error output otherwise.
   */
  public function installModules(array $modules) : string {
    $this->stopBridge();

    $process = new Process(['drush', 'pm:install', '-y', ...$modules]);
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    $this->startBridge();

    // Some drush commands write to standard error output (for example enable
    // use drush_log which default to _drush_print_log) instead of returning a
    // string (drush status use drush_print_pipe).
    if (!$process->getOutput()) {
      return $process->getErrorOutput();
    }

    return $process->getOutput();
  }

  /**
   * Install modules using Drush.
   *
   * Ensures the bridge is properly restarted afterwards.
   *
   * If $uninstall_dependents is TRUE but this would uninstall non-whitelisted
   * modules then this throws an error.
   *
   * @param array $modules
   *   The modules to install.
   * @param bool $uninstall_dependents
   *   Whether modules depending on the ones to uninstall should also be
   *   uninstalled.
   *
   * @return string
   *   The output if there is any and the error output otherwise.
   */
  public function uninstallModules(array $modules, bool $uninstall_dependents = TRUE) : string {
    $this->stopBridge();

    $process = new Process(['drush', 'pm:uninstall', '--simulate', ...$modules]);
    $process->setTimeout(3600);
    $process->run();

    if (!$uninstall_dependents) {
      $output = $process->getOutput();
      $output = trim(str_replace("The following extensions will be uninstalled: ", "", $output));
      $candidates = explode(", ", $output);

      $casualties = array_diff($candidates, $uninstall_dependents);
      if ($casualties !== []) {
        throw new \RuntimeException("Uninstalling " . implode(", ", $modules) . " would also uninstall the following unlisted modules: " . implode(", ", $casualties));
      }
    }

    $process = new Process(['drush', 'pm:uninstall', '-y', ...$modules]);
    $process->setTimeout(3600);
    $process->run();

    $this->startBridge();

    // Some drush commands write to standard error output (for example enable
    // use drush_log which default to _drush_print_log) instead of returning a
    // string (drush status use drush_print_pipe).
    if (!$process->getOutput()) {
      return $process->getErrorOutput();
    }

    return $process->getOutput();
  }

  /**
   * Get the next line of output.
   *
   * @return string|null
   *   The next line or NULL in case the process exited before a newline was
   *   printed.
   */
  protected function getNextLine() : ?string {
    do {
      $this->buffer .= $this->process->getIncrementalOutput();
    }
    while (!str_contains($this->buffer, "\n") && $this->process->isRunning());

    if (str_contains($this->buffer, "\n")) {
      $pos = strpos($this->buffer, "\n");
      $line = substr($this->buffer, 0, $pos);
      $this->buffer = substr($this->buffer, $pos + 1);
      return $line;
    }

    return NULL;
  }

}
