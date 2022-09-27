<?php

namespace Drupal\social\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\social\Installer\OptionalModuleManager;

/**
 * Defines test steps around management of modules.
 */
class ModuleContext extends RawMinkContext {

  /**
   * The Open Social optional module manager.
   *
   * @phpstan-var array<string, array>
   */
  private array $optionalModules;

  /**
   * Create a new ModuleContext instance.
   */
  public function __construct() {
    $this->optionalModules = OptionalModuleManager::create(\Drupal::getContainer())->getOptionalModules();
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
    if (!isset($this->optionalModules[$module])) {
      throw new \Exception("$module is not an optional module, does it have a module.installer_options.yml file?");
    }

    \Drupal::service('module_installer')->install([$module]);
  }

  /**
   * Enable an arbitrary Drupal module.
   *
   * @Given I enable the module :module
   */
  public function iEnableTheModule(string $module) : void {
    \Drupal::service('module_installer')->install([$module]);
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

}
