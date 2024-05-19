<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalContext;

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
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

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

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
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

    // @todo This can be removed when we no longer rely on Drupal state.
    $this->drupalContext->assertCacheClear();
  }

  /**
   * Uninstall a module.
   *
   * @When I disable the module :module
   */
  public function uninstallModule(string $module) : void {
    $this->testBridge->uninstallModules([$module], FALSE);
  }

  /**
   * Uninstall a module and any module that depends on it.
   *
   * @When I disable module :module and its dependants
   */
  public function uninstallModuleAndDependants(string $module) : void {
    $this->testBridge->uninstallModules([$module], TRUE);
  }

  /**
   * Get the optional modules in our code base.
   *
   * The `optionalModules` array can't be constructed before a test has been
   * set-up since it requires parameters from the database.
   *
   * @return array<string, array>
   *    The array of optional modules.
   */
  protected function getOptionalModules() : array {
    $response = $this->testBridge->command('modules-list-optional');
    assert(isset($response['modules']), "Could not fetch optional module list");
    return $response['modules'];
  }

}
