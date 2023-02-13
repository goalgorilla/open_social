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
  public const FALLBACK_DUMP = "fallback.dump.sql";

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

    $database_file = getenv(self::DATABASE_ENV);

    if (empty($database_file)) {
      $fallback = $this->fixturePath . DIRECTORY_SEPARATOR . self::FALLBACK_DUMP;
      if (!is_file($fallback)) {
        fwrite(STDERR, "No database file specified, creating a fallback at '$fallback'. Specify a database file using '" . self::DATABASE_ENV . "'." . PHP_EOL);
        try {
          $this->drushDriver->drush("sql-dump",  ["--result-file='$fallback'"]);
        }
        catch (\RuntimeException $e) {
          throw new \RuntimeException("Could not create fallback database dump.", 0, $e);
        }
      }
      else {
        fwrite(STDERR, "No database file specified, using the fallback at '$fallback'. Specify a database file using '" . self::DATABASE_ENV . "'." . PHP_EOL);
      }
      putenv(self::DATABASE_ENV . "=" . $fallback);
    }
    elseif ($database_file[0] !== "/") {
      // Make the relative path absolute.
      putenv(self::DATABASE_ENV . "=" . $this->fixturePath . DIRECTORY_SEPARATOR . $database_file);
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
    $database_file = getenv(self::DATABASE_ENV);
    assert(is_string($database_file), "SetupContext::validateEnvironmentVariables has not correctly validated the environment variables.");

    $this->loadDatabase($database_file);
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


    $data = [
      'pre-reset' => [
        'dblog.settings' => \Drupal::config("dblog.settings")->getRawData(),
        'core.extension' => \Drupal::config("core.extension")->getRawData(),
      ],
    ];

    try {
      $data['pre-reset']['database'] = \Drupal::database()->query("SELECT * FROM config WHERE name='core.extension';")->fetchAll();
    } catch (\Exception $e) { $data['database'] = NULL; }

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
    $this->drushDriver->drush("updatedb", ['-y']);
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
