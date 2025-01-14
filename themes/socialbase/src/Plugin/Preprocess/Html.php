<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "html" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("html")
 */
class Html extends PreprocessBase implements ContainerFactoryPluginInterface {

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The current path object.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected CurrentPathStack $currentPathStack;

  /**
   * Theme extension list service.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected ThemeExtensionList $themeExtensionList;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    CurrentPathStack $current_path_stack,
    ThemeExtensionList $theme_extension_list,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPathStack = $current_path_stack;
    $this->themeExtensionList = $theme_extension_list;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('extension.list.theme'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    parent::preprocess($variables, $hook, $info);

    // Identify the difference between nodes and node/add & node/edit.
    if ($variables['root_path'] == 'node') {
      $current_path = $this->currentPathStack->getPath();
      $path_pieces = explode("/", $current_path);
      $path_target = ['add', 'edit'];
      if (count(array_intersect($path_pieces, $path_target)) > 0) {
        $variables['node_edit'] = TRUE;
      }
    }

    // Get all SVG Icons.
    $variables['svg_icons'] = file_get_contents($this->themeExtensionList->getPath('socialbase') . '/assets/icons/icons.svg');

  }

}
