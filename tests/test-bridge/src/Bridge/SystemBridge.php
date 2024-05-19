<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Bridge;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\social\Installer\OptionalModuleManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;

class SystemBridge {

  protected ?array $optionalModules = NULL;

  public function __construct(
    protected OptionalModuleManager $optionalModuleManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  public static function create(ContainerInterface $container) {
    assert($container instanceof SymfonyContainerInterface);
    return new self(
      OptionalModuleManager::create($container),
      $container->get('module_handler'),
    );
  }

  /**
   * Check whether a module exists.
   *
   * @param string $module
   *   The module name.
   *
   * @return array{exists: bool}
   *   The response.
   */
  #[Command(name: 'module-exists')]
  public function moduleExists(string $module) : array {
    if ($this->moduleHandler->moduleExists($module)) {
      return ['exists' => TRUE];
    }

    return ['exists' => FALSE];
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
  #[Command('modules-list-optional')]
  public function getOptionalModules() : array {
    if ($this->optionalModules === NULL) {
      $this->optionalModules = $this->optionalModuleManager->getOptionalModules();
    }

    return ['modules' => $this->optionalModules];
  }

}
