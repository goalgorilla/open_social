<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\social\Installer\OptionalModuleManager;
use OpenSocial\Behat\Extension\TestControlInterface\Definition\TciOpenApiSearchEngine;

/**
 * Defines test steps around management of modules.
 */
class ModuleContext extends RawMinkContext {

  /**
   * The Open Social optional module manager.
   *
   * @var array<string, array>|null
   */
  private ?array $optionalModules = NULL;

  /**
   * The test bridge that allows running code in the Drupal installation.
   */
  private TestBridgeContext $testBridge;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->testBridge = $environment->getContext(TestBridgeContext::class);
  }

  /**
   * Enable an optional module.
   *
   * Throws an error in case the selected module isn't optional. All Open Social
   * optional modules must have a module.installer_options.yml file.
   *
   * @Given I enable the optional module :module
   */
  public function enableOptionalModule(string $module) : void {
    if (!isset($this->getOptionalModules()[$module])) {
      throw new \Exception("$module is not an optional module, does it have a module.installer_options.yml file?");
    }

    $this->iEnableTheModule($module);
  }

  /**
   * Enable an arbitrary Drupal module.
   *
   * @Given I enable the module :module
   */
  public function iEnableTheModule(string $module) : void {
    $this->testBridge->installModules([$module]);
    $this->resyncDrupalState();
    TciOpenApiSearchEngine::requestRefresh();
  }

  /**
   * Uninstall a module.
   *
   * @When I disable the module :module
   */
  public function uninstallModule(string $module) : void {
    $this->testBridge->uninstallModules([$module], FALSE);
    $this->resyncDrupalState();
    TciOpenApiSearchEngine::requestRefresh();
  }

  /**
   * Uninstall a module and any module that depends on it.
   *
   * @When I disable module :module and its dependants
   */
  public function uninstallModuleAndDependants(string $module) : void {
    $this->testBridge->uninstallModules([$module], TRUE);
    $this->resyncDrupalState();
    TciOpenApiSearchEngine::requestRefresh();
  }

  /**
   * Forces Drupal to notice a module list change made mid-scenario.
   *
   * Behat and the site under test share one PHP process for the whole
   * scenario, so installing or uninstalling a module partway through does
   * not by itself make the rest of the process aware of it: the container
   * still has the old module list compiled in, and
   * ModuleHandler::loadAll() is a no-op once it has already loaded once in
   * this request. Without an explicit resync, a module's procedural file
   * (and anything it defines, e.g. constants or hooks) can end up never
   * loaded even though the module is enabled or disabled in the database.
   *
   * This rebuilds the container so it picks up the new module list, then
   * forces the module handler to reload and re-run loadAll() so the
   * (un)installed module is actually reflected in this process.
   *
   * @todo This can be removed when we no longer rely on Drupal state.
   *
   * @see \Drupal\social\Behat\DatabaseContext::resetDrupalState()
   *   Deliberately duplicates part of that method's sequence, but for a
   *   narrower resync after a single module (un)install rather than a full
   *   database swap. This intentionally skips steps that method has and
   *   this one doesn't need: `drush cr` (no external caches like Redis need
   *   invalidating, we didn't swap the database), removing the
   *   install-mode global (irrelevant mid-scenario), triggerOnDatabaseLoaded()
   *   (that hook is for a database swap, not a module change), and
   *   currentUser()->setInitialAccountId(0) (this can run after a real
   *   login, and that call throws if an account is already set). If you
   *   change one of these two methods, check whether the other needs the
   *   same change.
   */
  private function resyncDrupalState() : void {
    $kernel = \Drupal::service('kernel');
    $kernel->invalidateContainer();
    $kernel->rebuildContainer();

    \Drupal::moduleHandler()->reload();

    drupal_flush_all_caches();

    \Drupal::moduleHandler()->loadAll();
  }

  /**
   * Get the optional modules in our code base.
   *
   * The `optionalModules` array can't be constructed before a test has been
   * set-up since it requires parameters from the database.
   *
   * @return array<string, array>
   *   The array of optional modules.
   */
  protected function getOptionalModules() : array {
    if ($this->optionalModules === NULL) {
      $this->optionalModules = OptionalModuleManager::create(\Drupal::getContainer())->getOptionalModules();
    }

    return $this->optionalModules;
  }

}
