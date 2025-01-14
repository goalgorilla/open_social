<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Preprocess\BootstrapDropdown;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "bootstrap_dropdown" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("bootstrap_dropdown")
 */
class Dropdown extends BootstrapDropdown implements ContainerFactoryPluginInterface {

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    if (
      !!mb_strpos($variables['theme_hook_original'], 'operations') &&
      (
        $this->routeMatch->getRouteObject()->hasOption('_operation_route') ||
        in_array($this->routeMatch->getRouteName(), [
          'view.event_manage_enrollments.page_manage_enrollments',
          'view.group_manage_members.page_group_manage_members'
        ])
      )
    ) {
      $variables['default_button'] = FALSE;
      $variables['toggle_label'] = $this->t('Actions');
    }

    if (isset($variables['attributes']['no-split'])) {
      $variables['default_button'] = FALSE;
      $variables['toggle_label'] = $variables['attributes']['no-split']['title'];
      $variables['alignment'] = $variables['attributes']['no-split']['alignment'];
    }

    parent::preprocess($variables, $hook, $info);

    if (isset($variables['items']['#items']['publish']['element']['#button_type']) && $variables['items']['#items']['publish']['element']['#button_type'] === 'primary') {
      $variables['alignment'] = 'right';

      if (isset($variables['toggle'])) {
        $variables['toggle']['#button_type'] = 'primary';
        $variables['toggle']['#button_level'] = 'raised';

      }

    }
  }

  /**
   * Function to preprocess the links.
   */
  protected function preprocessLinks(Variables $variables): void {
    parent::preprocessLinks($variables);

    $operations = !!mb_strpos($variables->theme_hook_original, 'operations');

    // Make operations button small, not smaller ;).
    // Bootstrap basetheme override.
    if ($operations) {
      $variables->toggle['#attributes']['class'] = ['btn-sm'];
      $variables['btn_context'] = 'operations';
      $variables['alignment'] = 'right';
    }

  }

}
