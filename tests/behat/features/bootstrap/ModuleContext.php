<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\social\Installer\OptionalModuleManager;

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
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
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
    \Drupal::service('module_installer')->install([$module]);
    $this->drupalContext->assertCacheClear();
  }

  /**
   * Uninstall a module.
   *
   * @When I disable the module :module
   */
  public function uninstallModule(string $module) : void {
    \Drupal::service('module_installer')->uninstall([$module], FALSE);
  }

  /**
   * Uninstall a module and any module that depends on it.
   *
   * @When I disable module :module and its dependants
   */
  public function uninstallModuleAndDependants(string $module) : void {
    \Drupal::service('module_installer')->uninstall([$module], TRUE);
  }

  /**
   * Get the optional modules in our code base.
   *
   * The `optionalModules` array can't be constructed before a test has been
   * set-up since it requires parameters from the database.
   *
   * @return array<string, array>
   */
  protected function getOptionalModules() : array {
    if ($this->optionalModules === NULL) {
      $this->optionalModules = OptionalModuleManager::create(\Drupal::getContainer())->getOptionalModules();
    }

    return $this->optionalModules;
  }

}
