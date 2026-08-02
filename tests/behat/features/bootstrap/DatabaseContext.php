<?php

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Hook\Scope\HookScope;
use Drupal\Core\Cache\Cache;
use Drupal\Driver\DrushDriver;

/**
 * Performs database set-up before scenarios.
 *
 * This ensures that every scenario runs in a predictable environment and is not
 * affected by test steps performed in another scenario.
 */
class DatabaseContext implements Context {

  public const DATABASE_ENV = "TEST_DATABASE";
  public const ARCHIVE_ENV = "TEST_ARCHIVE";
  public const FALLBACK_ARCHIVE = "fallback.tar.gz";

  /**
   * Whether the environment for the given scenario was validated.
   */
  private bool $hasValidatedEnvironment = FALSE;

  /**
   * The driver that allows us to execute drush commands.
   */
  private DrushDriver $drushDriver;

  /**
   * The Behat environment we're operating in.
   */
  private InitializedContextEnvironment $environment;

  /**
   * Configure the context.
   *
   * @param string $fixturePath
   *   The path to our database fixtures.
   */
  public function __construct(private string $fixturePath) {}

  /**
   * Ensures the drush driver is available in other hooks and steps.
   *
   * Should be the first BeforeScenario hook in this class.
   *
   * @BeforeScenario
   */
  public function getDrushDriver(BeforeScenarioScope $scope) : void {
    $this->environment= $scope->getEnvironment();
    $drupal_context = $this->environment->getContext(SocialDrupalContext::class);
    if (!$drupal_context instanceof SocialDrupalContext) {
      throw new \RuntimeException("Expected " . SocialDrupalContext::class . " to be configured for Behat.");
    }
    // Call getDriver without arguments to boostrap the default driver.
    $drupal_context->getDriver();
    $driver = $drupal_context->getDriver("drush");
    if (!$driver instanceof DrushDriver) {
      throw new \RuntimeException("Could not load the Drush driver. Make sure the DrupalExtension is configured to enable it.");
    }
    $this->drushDriver = $driver;
  }

  /**
   * Ensure our environment is correct.
   *
   * Checks for a database environment variable and creates a database dump if
   * needed.
   *
   * Ideally we'd do this in a BeforeSuite hook, but then we don't have access
   * to our context configuration.
   */
  public function ensureValidatedEnvironment(HookScope $scope) : void {
    if ($this->hasValidatedEnvironment) {
      return;
    }

    $archive_file = getenv(self::ARCHIVE_ENV);
    $database_file = getenv(self::DATABASE_ENV);

    if (!empty($archive_file)) {
      if ($archive_file[0] !== "/") {
        // Make the relative path absolute.
        putenv(self::ARCHIVE_ENV . "=" . $this->fixturePath . DIRECTORY_SEPARATOR . $archive_file);
      }
    }
    elseif (!empty($database_file)) {
      // @trigger_error is used instead of throwing so that developers relying
      // on this for local development are not blocked while we transition
      // fully to packed site archives.
      @trigger_error(
        "Loading a scaffold from a database dump via '" . self::DATABASE_ENV . "' is deprecated and will be removed. Use '" . self::ARCHIVE_ENV . "' with a packed site archive (see `drush os:pack-site`) instead.",
        E_USER_DEPRECATED
      );
      if ($database_file[0] !== "/") {
        // Make the relative path absolute.
        putenv(self::DATABASE_ENV . "=" . $this->fixturePath . DIRECTORY_SEPARATOR . $database_file);
      }
    }
    else {
      $fallback = $this->fixturePath . DIRECTORY_SEPARATOR . self::FALLBACK_ARCHIVE;
      if (!is_file($fallback)) {
        fwrite(STDERR, "No database or archive specified, creating a fallback archive at '$fallback'. Specify an archive using '" . self::ARCHIVE_ENV . "'." . PHP_EOL);
        try {
          $this->drushDriver->drush("os:pack-site", [$fallback]);
        }
        catch (\RuntimeException $e) {
          throw new \RuntimeException("Could not create fallback site archive.", 0, $e);
        }
      }
      else {
        fwrite(STDERR, "No database or archive specified, using the fallback archive at '$fallback'. Specify an archive using '" . self::ARCHIVE_ENV . "'." . PHP_EOL);
      }
      putenv(self::ARCHIVE_ENV . "=" . $fallback);
    }

    $this->hasValidatedEnvironment = TRUE;
  }

  /**
   * Loads a fresh database from the provided dump before very scenario.
   *
   * Features can opt out of this by specifying the "fixture" tag.
   *
   * @BeforeScenario
   */
  public function ensureCleanDatabase(BeforeScenarioScope $scope) : void {
    if ($scope->getFeature()->hasTag("no-database")) {
      return;
    }

    $this->ensureValidatedEnvironment($scope);

    $archive_file = getenv(self::ARCHIVE_ENV);
    if (!empty($archive_file)) {
      $this->unpackArchive($archive_file);
      return;
    }

    $database_file = getenv(self::DATABASE_ENV);
    assert(is_string($database_file), "SetupContext::validateEnvironmentVariables has not correctly validated the environment variables.");

    $this->loadDatabase($database_file);
  }

  /**
   * Load a packed site archive as fixture.
   *
   * Unlike loadDatabase() this also restores the public/private files that
   * were packed alongside the database, and does not need a separate drop of
   * the existing database since os:unpack-site already does this itself.
   *
   * @param string $archive_file
   *   The path to the packed site archive (see os:pack-site). May be
   *   relative to the configured fixture path or an absolute path.
   */
  private function unpackArchive(string $archive_file) : void {
    // Ensure we have an absolute path.
    if ($archive_file[0] !== DIRECTORY_SEPARATOR) {
      $archive_file = $this->fixturePath . DIRECTORY_SEPARATOR . $archive_file;
    }

    if (!is_file($archive_file)) {
      throw new \RuntimeException("Scaffold archive '$archive_file' does not exist.");
    }

    try {
      $this->drushDriver->drush("os:unpack-site", ["-y", $archive_file]);
    }
    catch (\RuntimeException $e) {
      throw new \RuntimeException("Could not unpack site archive.", 0, $e);
    }

    $this->resetDrupalState();
  }

  /**
   * Load a specified database file as fixture.
   *
   * Example: Given the fixture open-social-2.sql
   *
   * @param string $database_file
   *   The name of the sql file containing the database. May be relative to the
   *   configured fixture path or an absolute path.
   *
   * @Given the fixture :database_file
   */
  public function loadDatabase(string $database_file) : void {
    // Ensure we have an absolute path.
    if ($database_file[0] !== DIRECTORY_SEPARATOR) {
      $database_file = $this->fixturePath . DIRECTORY_SEPARATOR . $database_file;
    }

    if (!is_file($database_file)) {
      throw new \RuntimeException("Scaffold file '$database_file' does not exist.");
    }

    try {
      $this->drushDriver->drush("sql:drop",  ["-y"]);
    }
    catch (\RuntimeException $e) {
      throw new \RuntimeException("Could not drop existing database.", 0, $e);
    }
    try {
      $this->drushDriver->drush("sql:query",  ["--file", $database_file]);
    }
    catch (\RuntimeException $e) {
      throw new \RuntimeException("Could not drop existing database.", 0, $e);
    }

    $this->resetDrupalState();
  }

  /**
   * Gets Drupal out of install mode and clears caches after a database load.
   *
   * Shared by loadDatabase() and unpackArchive() since both replace the
   * database out from under a running Drupal.
   */
  private function resetDrupalState() : void {
    // Drush CR is run because it ensures that all caches are cleared, including
    // non-database caches like Redis.
    // The additional database clears below duplicate effort but are needed to
    // clear the memory of Behat.
    $this->drushDriver->drush("cr", ["-y"]);

    // When there's no database Drupal kicks into Install mode which sets up a
    // read only config. Now that we have a database loaded we need to get
    // Drupal out of that mode.
    // Steps need to be in a specific order here since the install mode also
    // doesn't load the system module (which every module under the sun assumes
    // is loaded).
    //
    // 1.Remove the global that keeps the container in install mode
    unset($GLOBALS['conf']['container_service_providers']['InstallerServiceProvider']);
    // 2. Rebuild the container to ensure the Module Handler gets a new module
    //    list
    $kernel = \Drupal::service('kernel');
    $kernel->invalidateContainer();
    $kernel->rebuildContainer();
    // 3. Reload all the modules to ensure the system module is loaded
    \Drupal::moduleHandler()->reload();
    // 4. Flush all the caches to ensure we don't cache data from the previously
    //    loaded database. This will trigger another container rebuild but
    //    that's fine.
    drupal_flush_all_caches();
    // 5. We must clear the current user, since the container rebuild saves it,
    //    but it references a non-existent user now.
    \Drupal::currentUser()->setInitialAccountId(0);

    $this->triggerOnDatabaseLoaded();
  }

  /**
   * Run updates using Drush
   *
   * @Given run pending updates
   * @When I run pending updates
   */
  public function executeUpdates() : void {
    $output = $this->drushDriver->drush("updatedb", ['-y']);
    // @todo Drush requires -y to continue running updates when the list of updates are presented but -y also bypasses
    // the question about requirements check errors so we must manually check for the output until a better solutions is
    // found in https://github.com/drush-ops/drush/issues/5806.
    if (str_contains($output, "Requirements check reports errors. Do you wish to continue?")) {
      throw new \Exception("The update showed requirement errors, manually run the updates using drush against the fixture used for the test to find the errors.");
    }
  }

  /**
   * Calls `onDatabaseLoaded` in all contexts that have it.
   *
   * This allows contexts to do database related set-ups (e.g. log detection).
   *
   * @return void
   */
  private function triggerOnDatabaseLoaded() : void {
    foreach ($this->environment->getContexts() as $context) {
      $hook = [$context, "onDatabaseLoaded"];
      if (is_callable($hook)) {
        $hook();
      }
    }
  }

}
