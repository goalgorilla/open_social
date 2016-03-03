<?php

/**
 * @file
 * Contains \Drupal\composer_manager\Controller\Packages.
 */

namespace Drupal\composer_manager\Controller;

use Drupal\composer_manager\PackageManagerInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for displaying the list of required packages.
 */
class PackageController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The package manager.
   *
   * @var \Drupal\composer_manager\PackageManagerInterface
   */
  protected $packageManager;

  /**
   * The module data from system_get_info().
   *
   * @var array
   */
  protected $moduleData;

  /**
   * Constructs a PackageController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\composer_manager\PackageManagerInterface $package_manager
   *   The package manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, PackageManagerInterface $package_manager, TranslationInterface $string_translation) {
    $this->moduleHandler = $module_handler;
    $this->packageManager = $package_manager;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('composer_manager.package_manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Shows the status of all required packages.
   *
   * @return array
   *   Returns a render array as expected by drupal_render().
   */
  public function page() {
    if (!composer_manager_initialized()) {
      $message = t("Composer Manager needs to be initialized before usage. Run the module's <code>init.php</code> script on the command line.");
      drupal_set_message($message, 'warning');
      return [];
    }

    try {
      $packages = $this->packageManager->getRequiredPackages();
    }
    catch (\RuntimeException $e) {
      drupal_set_message(Xss::filterAdmin($e->getMessage()), 'error');
      return [];
    }

    $rows = [];
    foreach ($packages as $package_name => $package) {
      $package_column = [];
      if (!empty($package['homepage'])) {
        $package_column[] = [
          '#type' => 'link',
          '#title' => $package_name,
          '#url' => Url::fromUri($package['homepage']),
          '#options' => [
            'attributes' => ['target' => '_blank'],
          ],
        ];
      }
      else {
        $package_column[] = [
          '#plain_text' => $package_name,
        ];
      }
      if (!empty($package['description'])) {
        $package_column[] = [
          '#prefix' => '<div class="description">',
          '#plain_text' => $package['description'],
          '#suffix' => '</div>',
        ];
      }

      // Prepare the installed and required versions.
      $installed_version = $package['version'] ? $package['version'] : $this->t('Not installed');
      $required_version = $this->buildRequiredVersion($package['constraint'], $package['required_by']);

      // Prepare the row classes.
      $class = [];
      if (empty($package['version'])) {
        $class[] = 'error';
      }
      elseif (empty($package['required_by'])) {
        $class[] = 'warning';
      }

      $rows[$package_name] = [
        'class' => $class,
        'data' => [
          'package' => [
            'data' => $package_column,
          ],
          'installed_version' => $installed_version,
          'required_version' => [
            'data' => $required_version,
          ],
        ],
      ];
    }

    $build = [];
    $build['packages'] = [
      '#theme' => 'table',
      '#header' => [
        'package' => $this->t('Package'),
        'installed_version' => $this->t('Installed Version'),
        'required_version' => $this->t('Required Version'),
      ],
      '#rows' => $rows,
      '#caption' => $this->t('Status of Packages Managed by Composer'),
      '#attributes' => [
        'class' => ['system-status-report'],
      ],
    ];

    // Display any errors returned by hook_requirements().
    $this->moduleHandler->loadInclude('composer_manager', 'install');
    $requirements = composer_manager_requirements('runtime');
    if ($requirements['composer_manager']['severity'] == REQUIREMENT_ERROR) {
      drupal_set_message($requirements['composer_manager']['description'], 'warning');
    }

    return $build;
  }

  /**
   * Builds the render array for the required version column.
   *
   * @param string $contraint
   *   The package constraint.
   * @param array $required_by
   *   The names of dependent packages.
   *
   * @return array
   *   The requirements render array.
   */
  protected function buildRequiredVersion($constraint, array $required_by) {
    // Filter out non-Drupal packages.
    $drupal_required_by = array_filter($required_by, function($package_name) {
      return strpos($package_name, 'drupal/') !== FALSE;
    });

    if (empty($required_by)) {
      $constraint = $this->t('No longer required');
      $description = $this->t('Package will be removed on the next Composer update');
    }
    elseif (empty($drupal_required_by)) {
      // The package is here as a requirement of other packages, list them.
      $constraint = $this->t('N/A');
      $description = $this->t('Required by: ') . join(', ', $required_by);
    }
    else {
      if (!isset($this->moduleData)) {
        $this->moduleData = system_get_info('module');
      }

      $modules = [];
      foreach ($drupal_required_by as $package_name) {
        $name_parts = explode('/', $package_name);
        $module_name = $name_parts[1];

        if ($module_name == 'core') {
          $modules[] = $this->t('Drupal');
        }
        elseif (isset($this->moduleData[$module_name])) {
          $modules[] = $this->moduleData[$module_name]['name'];
        }
        else {
          $modules[] = $module_name;
        }
      }

      $description = $this->t('Required by: ') . join(', ', $modules);
    }

    $required_version = [];
    $required_version[] = [
      '#plain_text' => $constraint,
    ];
    $required_version[] = [
      '#prefix' => '<div class="description">',
      '#plain_text' => $description,
      '#suffix' => '</div>',
    ];

    return $required_version;
  }

}
